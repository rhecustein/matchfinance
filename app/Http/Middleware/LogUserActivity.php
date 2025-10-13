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
     * Log important user activities
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if ($user = $request->user()) {
            // Log important actions
            $method = $request->method();
            $path = $request->path();

            // Actions to log
            $shouldLog = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) ||
                         str_contains($path, 'verify') ||
                         str_contains($path, 'reconcile') ||
                         str_contains($path, 'match');

            if ($shouldLog && $response->isSuccessful()) {
                Log::info('User activity', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'role' => $user->role,
                    'method' => $method,
                    'path' => $path,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        }

        return $response;
    }
}