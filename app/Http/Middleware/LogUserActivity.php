<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     * Log important user activities for audit trail
     * 
     * This middleware logs:
     * - All write operations (POST, PUT, PATCH, DELETE)
     * - Critical actions (verify, reconcile, match, approve)
     * - Financial operations
     * - Admin actions
     * 
     * Logged to: storage/logs/user-activity.log
     * 
     * Usage:
     * - Apply globally for all authenticated routes
     * - Or selectively on specific route groups
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process the request first
        $response = $next($request);

        // Only log for authenticated users
        if (!$user = $request->user()) {
            return $response;
        }

        $method = $request->method();
        $path = $request->path();
        $routeName = $request->route()->getName();

        // Determine if this action should be logged
        $shouldLog = $this->shouldLogActivity($method, $path, $routeName, $response);

        if ($shouldLog) {
            $activityData = $this->buildActivityData($request, $user, $response);

            // Use different log levels based on action type
            $logLevel = $this->getLogLevel($method, $path, $response);

            Log::channel('user-activity')->log($logLevel, 'User activity', $activityData);

            // Also log to main log for critical actions
            if ($this->isCriticalAction($path, $routeName)) {
                Log::log($logLevel, 'Critical user activity', $activityData);
            }
        }

        return $response;
    }

    /**
     * Determine if activity should be logged
     */
    private function shouldLogActivity(string $method, string $path, ?string $routeName, Response $response): bool
    {
        // Always log write operations that succeeded
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && $response->isSuccessful()) {
            return true;
        }

        // Log critical read operations
        $criticalPaths = [
            'verify', 'reconcile', 'match', 'approve', 'reject',
            'suspend', 'activate', 'cancel', 'restore',
            'export', 'download', 'import',
            'impersonate', 'reset-password', 'change-role',
        ];

        foreach ($criticalPaths as $critical) {
            if (str_contains($path, $critical)) {
                return true;
            }
        }

        // Log admin panel access
        if (str_starts_with($path, 'admin/') && $method === 'GET') {
            return true;
        }

        return false;
    }

    /**
     * Build activity data array
     */
    private function buildActivityData(Request $request, $user, Response $response): array
    {
        $data = [
            // User Information
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'company_id' => $user->company_id,
            'company_name' => $user->company->name ?? null,

            // Request Information
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $request->route()->getName(),
            'url' => $request->fullUrl(),

            // Response Information
            'status_code' => $response->getStatusCode(),
            'success' => $response->isSuccessful(),

            // Network Information
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),

            // Timestamp
            'timestamp' => now()->toIso8601String(),
        ];

        // Add request data for write operations (sanitized)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $data['request_data'] = $this->sanitizeRequestData($request->except([
                'password', 
                'password_confirmation',
                '_token',
                '_method',
                'current_password',
            ]));
        }

        // Add specific information based on action type
        $data = $this->addContextualData($request, $data);

        return $data;
    }

    /**
     * Add contextual data based on action type
     */
    private function addContextualData(Request $request, array $data): array
    {
        $path = $request->path();

        // Bank Statement actions
        if (str_contains($path, 'bank-statements')) {
            $data['entity_type'] = 'bank_statement';
            if ($id = $request->route('bankStatement')) {
                $data['entity_id'] = $id;
            }
        }

        // Transaction actions
        if (str_contains($path, 'transactions')) {
            $data['entity_type'] = 'transaction';
            if ($id = $request->route('transaction')) {
                $data['entity_id'] = $id;
            }
        }

        // User management actions
        if (str_contains($path, 'users') && !str_contains($path, 'api')) {
            $data['entity_type'] = 'user';
            if ($id = $request->route('user')) {
                $data['entity_id'] = $id;
            }
        }

        // Company management actions
        if (str_contains($path, 'companies')) {
            $data['entity_type'] = 'company';
            if ($id = $request->route('company')) {
                $data['entity_id'] = $id;
            }
        }

        // Subscription actions
        if (str_contains($path, 'subscription')) {
            $data['entity_type'] = 'subscription';
        }

        return $data;
    }

    /**
     * Sanitize request data (remove sensitive information)
     */
    private function sanitizeRequestData(array $data): array
    {
        // Remove any keys that might contain sensitive data
        $sensitiveKeys = [
            'password', 'token', 'secret', 'api_key', 
            'credit_card', 'cvv', 'ssn', 'bank_account'
        ];

        foreach ($data as $key => $value) {
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains(strtolower($key), $sensitive)) {
                    $data[$key] = '[REDACTED]';
                }
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $data[$key] = $this->sanitizeRequestData($value);
            }
        }

        return $data;
    }

    /**
     * Get appropriate log level based on action
     */
    private function getLogLevel(string $method, string $path, Response $response): string
    {
        // Error responses
        if (!$response->isSuccessful()) {
            return 'warning';
        }

        // Critical actions
        if ($this->isCriticalAction($path, null)) {
            return 'warning';
        }

        // Delete operations
        if ($method === 'DELETE') {
            return 'warning';
        }

        // Normal operations
        return 'info';
    }

    /**
     * Check if action is critical
     */
    private function isCriticalAction(string $path, ?string $routeName): bool
    {
        $criticalActions = [
            'delete', 'destroy', 'suspend', 'cancel',
            'reset-password', 'change-role', 'impersonate',
            'reconcile', 'verify-all', 'bulk-delete',
        ];

        foreach ($criticalActions as $action) {
            if (str_contains($path, $action)) {
                return true;
            }
        }

        return false;
    }
}