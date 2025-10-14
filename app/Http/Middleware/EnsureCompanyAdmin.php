<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to company admin route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super Admin should use super_admin middleware
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

        // Check if user has admin-level access
        if (!$user->hasAdminAccess()) {
            Log::warning('Unauthorized company admin access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. This action requires Admin or Owner privileges.');
        }

        // Check if company exists
        if (!$user->company) {
            Log::error('User has company_id but company not found', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(500, 'System error: Company not found. Please contact support.');
        }

        // FIXED: Check company status (bukan is_active)
        if (in_array($user->company->status, ['suspended', 'cancelled'])) {
            Log::warning('Admin access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'company_name' => $user->company->name,
                'company_status' => $user->company->status,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your company account is currently ' . $user->company->status . '. Please contact support.');
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

            Log::info('Admin access allowed despite expired subscription (grace period)', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
            ]);
        }

        return $next($request);
    }
}