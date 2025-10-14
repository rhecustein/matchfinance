<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyManager
{
    /**
     * Handle an incoming request.
     * Allow managers and above (Owner, Admin, Manager)
     * 
     * This middleware is for management-level routes that require
     * oversight but not necessarily full admin access.
     * 
     * Allowed roles:
     * - owner (company owner)
     * - admin (company admin)
     * - manager (company manager)
     * 
     * NOT allowed:
     * - super_admin (different context)
     * - staff, user
     * 
     * Use Cases:
     * - Viewing reports
     * - Exporting data
     * - Verifying transactions
     * - Approving statements
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to manager route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super Admin should use super_admin middleware
        if ($user->isSuperAdmin()) {
            Log::warning('Super Admin trying to use company.manager middleware', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            abort(403, 'Super Admin should use admin panel routes, not company routes.');
        }

        // Check if user has company
        if (!$user->company_id) {
            Log::error('User without company trying to access manager resource', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
            ]);

            abort(403, 'You must belong to a company to access this resource.');
        }

        // Check if user has management access
        if (!$user->hasManagementAccess()) {
            Log::warning('Unauthorized management access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'This action requires Manager privileges or higher.');
        }

        // Check if company exists and is active
        if (!$user->company) {
            Log::error('User has company_id but company not found', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(500, 'System error: Company not found. Please contact support.');
        }

        if (!$user->company->is_active) {
            Log::warning('Manager access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Your company account is currently inactive. Please contact support.');
        }

        // Check if user account is active
        if (!$user->is_active || $user->is_suspended) {
            Log::warning('Inactive/suspended manager access attempt', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'is_suspended' => $user->is_suspended,
                'route' => $request->path(),
            ]);

            abort(403, 'Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        return $next($request);
    }
}