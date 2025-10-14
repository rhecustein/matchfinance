<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware ensures that only users with Admin role (or higher) 
     * within a company can access the protected routes.
     * 
     * Allowed roles:
     * - super_admin (system-wide access, bypasses company check)
     * - owner (company owner, has admin access)
     * - admin (company admin)
     * 
     * NOT allowed:
     * - manager, staff, user
     * 
     * Usage:
     * - Route::middleware('admin')->get('/admin-panel');
     * - Route::middleware(['auth', 'admin'])->group(...);
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to admin route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super admin has full access (bypasses company check)
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has admin-level access (owner or admin)
        if (!$user->hasAdminAccess()) {
            Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
                'company_id' => $user->company_id,
            ]);

            abort(403, 'Access denied. This action requires Admin privileges.');
        }

        // Additional security: Ensure company users belong to a company
        if (!$user->company_id) {
            Log::error('Admin user without company trying to access company resource', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. You must belong to a company to access this resource.');
        }

        // Check if user's company is active (optional but recommended)
        if ($user->company && !$user->company->is_active) {
            Log::warning('Admin access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your company account is currently inactive. Please contact support.');
        }

        // Check if user account is active and not suspended
        if (!$user->is_active || $user->is_suspended) {
            Log::warning('Admin access attempt from inactive/suspended user', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'is_suspended' => $user->is_suspended,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        return $next($request);
    }
}