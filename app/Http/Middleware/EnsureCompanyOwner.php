<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyOwner
{
    /**
     * Handle an incoming request.
     * Only allow company OWNERS (highest company-level privilege)
     * 
     * This middleware is for OWNER-ONLY routes within a company.
     * More restrictive than company.admin middleware.
     * 
     * Allowed:
     * - owner ONLY (company owner)
     * 
     * NOT allowed:
     * - super_admin (different context, should use admin routes)
     * - admin, manager, staff, user
     * 
     * Requirements:
     * - User must have role = 'owner'
     * - User must have company_id
     * - Company must exist
     * - User must be active and not suspended
     * 
     * Use Cases:
     * - Company billing/subscription management
     * - Transferring company ownership
     * - Deleting company
     * - Critical company settings
     * - Inviting/removing admins
     * - Subscription upgrades/downgrades
     * - Company cancellation
     * 
     * Usage:
     * - Route::middleware('company.owner')->post('/company/billing');
     * - Route::middleware(['auth', 'company.owner'])->group(...);
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to owner-only route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super Admin should not use company.owner middleware
        if ($user->isSuperAdmin()) {
            Log::warning('Super Admin trying to use company.owner middleware', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            abort(403, 'Super Admin should use admin panel routes, not company owner routes.');
        }

        // Check if user has company
        if (!$user->company_id) {
            Log::error('User without company trying to access owner resource', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
            ]);

            abort(403, 'You must belong to a company to access this resource.');
        }

        // Check if user is owner
        if (!$user->isOwner()) {
            Log::warning('Unauthorized owner-only access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'route' => $request->path(),
                'method' => $request->method(),
            ]);

            abort(403, 'Access denied. This action requires Company Owner privileges.');
        }

        // Check if company exists
        if (!$user->company) {
            Log::error('Owner has company_id but company not found', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(500, 'System error: Company not found. Please contact support.');
        }

        $company = $user->company;

        // IMPORTANT: Allow owner to access even if company is inactive
        // This allows owner to reactivate or manage inactive companies
        if (!$company->is_active) {
            Log::info('Owner accessing inactive company (allowed for management)', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_status' => $company->status,
                'route' => $request->path(),
            ]);

            // Show warning but allow access
            session()->flash('warning', 'Your company account is currently inactive. Some features may be limited.');
        }

        // Check if owner account is active
        if (!$user->is_active) {
            Log::warning('Inactive owner access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your account is currently inactive. Please contact support.');
        }

        // Check if owner is suspended
        if ($user->is_suspended) {
            Log::warning('Suspended owner access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'suspension_reason' => $user->suspension_reason,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your account has been suspended. Please contact support.');
        }

        // Log owner access for audit (important for sensitive operations)
        Log::info('Company Owner accessed protected route', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'company_id' => $company->id,
            'company_name' => $company->name,
            'route' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}