<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyMember
{
    /**
     * Handle an incoming request.
     * Ensure user belongs to an active company (any role)
     * 
     * This is the most basic company middleware.
     * Simply checks if user belongs to a company and is active.
     * 
     * Allowed: ALL company users (owner, admin, manager, staff, user)
     * NOT allowed: super_admin, users without company
     * 
     * Use Cases:
     * - Dashboard access
     * - Viewing transactions
     * - Basic read-only operations
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to company route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super Admin doesn't belong to company
        if ($user->isSuperAdmin()) {
            Log::warning('Super Admin trying to access company member route', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            abort(403, 'Super Admin should use admin panel routes.');
        }

        // Check if user belongs to a company
        if (!$user->company_id) {
            Log::error('User without company trying to access company resource', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            abort(403, 'You must belong to a company to access this resource.');
        }

        // Load company if not already loaded
        if (!$user->relationLoaded('company')) {
            $user->load('company');
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

        $company = $user->company;

        // Check company status (assuming you have status field: active, inactive, suspended, cancelled)
        // If you're using is_active boolean field, adapt accordingly
        
        // Option 1: If using 'status' field
        if (property_exists($company, 'status')) {
            if ($company->status === 'inactive') {
                Log::warning('Access attempt from inactive company', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'route' => $request->path(),
                ]);

                abort(403, 'Your company account is currently inactive. Please contact support.');
            }

            if ($company->status === 'suspended') {
                Log::warning('Access attempt from suspended company', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'route' => $request->path(),
                ]);

                abort(403, 'Your company account has been suspended. Please contact support.');
            }

            if ($company->status === 'cancelled') {
                Log::warning('Access attempt from cancelled company', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'route' => $request->path(),
                ]);

                abort(403, 'Your company account has been cancelled. Please contact support.');
            }
        }
        
        // Option 2: If using 'is_active' boolean field
        if (property_exists($company, 'is_active') && !$company->is_active) {
            Log::warning('Access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'route' => $request->path(),
            ]);

            abort(403, 'Your company account is currently inactive. Please contact support.');
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('Inactive user access attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Your account is not active. Please contact your administrator.');
        }

        // Check if user is suspended
        if ($user->is_suspended) {
            Log::warning('Suspended user access attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'company_id' => $user->company_id,
                'suspension_reason' => $user->suspension_reason,
                'suspended_at' => $user->suspended_at,
                'route' => $request->path(),
            ]);

            $message = 'Your account has been suspended.';
            if ($user->suspension_reason) {
                $message .= ' Reason: ' . $user->suspension_reason;
            }
            $message .= ' Please contact your administrator.';

            abort(403, $message);
        }

        // Check if user is locked
        if ($user->isLocked()) {
            Log::warning('Locked user access attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'company_id' => $user->company_id,
                'locked_until' => $user->locked_until,
                'route' => $request->path(),
            ]);

            abort(403, 'Your account is temporarily locked until ' . $user->locked_until->format('d M Y H:i') . '. Please try again later.');
        }

        return $next($request);
    }
}