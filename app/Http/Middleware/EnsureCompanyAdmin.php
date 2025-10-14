<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAdmin
{
    /**
     * Handle an incoming request.
     * Only allow company admins (Owner or Admin role) with active company
     * 
     * This middleware is specifically for COMPANY-LEVEL admin routes.
     * Super Admin is NOT allowed (they have their own routes).
     * 
     * Allowed roles:
     * - owner (company owner)
     * - admin (company admin)
     * 
     * NOT allowed:
     * - super_admin (should use super_admin middleware instead)
     * - manager, staff, user
     * 
     * Requirements:
     * - User must have company_id (belong to a company)
     * - Company must be active
     * - User must be active and not suspended
     * - User role must be owner or admin
     * 
     * Use Cases:
     * - Master data management (banks, types, categories, keywords)
     * - User management within company
     * - Company settings
     * - Reports management
     * 
     * Usage:
     * - Route::middleware('company.admin')->get('/banks');
     * - Route::middleware(['auth', 'company.admin'])->group(...);
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to company admin route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super Admin should use super_admin middleware, not this one
        if ($user->isSuperAdmin()) {
            Log::warning('Super Admin trying to use company.admin middleware', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            abort(403, 'Super Admin should use admin panel routes, not company routes.');
        }

        // Check if user has company
        if (!$user->company_id) {
            Log::error('User without company trying to access company admin resource', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. You must belong to a company to access this resource.');
        }

        // Check if user has admin-level access (owner or admin)
        if (!$user->hasAdminAccess()) {
            Log::warning('Unauthorized company admin access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. This action requires Admin or Owner privileges.');
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
            Log::warning('Admin access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'company_name' => $user->company->name,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your company account is currently inactive. Please contact support.');
        }

        // Check if user account is active and not suspended
        if (!$user->is_active || $user->is_suspended) {
            Log::warning('Inactive/suspended admin access attempt', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'is_suspended' => $user->is_suspended,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        // Optional: Check subscription status
        if ($user->company->activeSubscription && !$user->company->activeSubscription->isActive()) {
            Log::warning('Admin access attempt from company with expired subscription', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'subscription_status' => $user->company->activeSubscription->status,
                'route' => $request->path(),
            ]);

            // You can choose to block or allow with warning
            // For now, we'll allow but you can change to abort(403)
            Log::info('Admin access allowed despite expired subscription (grace period)', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
            ]);
        }

        return $next($request);
    }
}