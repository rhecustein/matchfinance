<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware ensures that ONLY Super Admin users can access 
     * the protected routes. Super Admin is the highest privilege level
     * in the system with system-wide access across all companies.
     * 
     * Super Admin characteristics:
     * - role = 'super_admin'
     * - company_id = NULL (not tied to any company)
     * - Has access to all system resources
     * - Can manage companies, plans, subscriptions
     * - Can impersonate other users
     * 
     * Allowed:
     * - super_admin ONLY
     * 
     * NOT allowed:
     * - owner, admin, manager, staff, user (even company owners)
     * 
     * Usage:
     * - Route::middleware('super_admin')->get('/admin/dashboard');
     * - Route::middleware(['auth', 'super_admin'])->group(...);
     * 
     * Routes typically protected by this middleware:
     * - /admin/dashboard (Super Admin Dashboard)
     * - /admin/companies/* (Company Management)
     * - /admin/plans/* (Plan Management)
     * - /admin/subscriptions/* (Subscription Management)
     * - /admin/system-settings/* (System Settings)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to super admin route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Check if user is Super Admin
        if (!$user->isSuperAdmin()) {
            Log::warning('Unauthorized super admin access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            abort(403, 'Access denied. This action requires Super Admin privileges.');
        }

        // Additional validation: Super Admin should NOT have company_id
        if ($user->company_id !== null) {
            Log::error('Super Admin with company_id detected', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(500, 'System error: Super Admin account misconfigured. Please contact system administrator.');
        }

        // Check if Super Admin account is active and not suspended
        if (!$user->is_active || $user->is_suspended) {
            Log::critical('Suspended/inactive Super Admin access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'is_active' => $user->is_active,
                'is_suspended' => $user->is_suspended,
                'suspension_reason' => $user->suspension_reason,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your Super Admin account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '. Please contact system administrator.');
        }

        // Optional: Check for 2FA if enabled for Super Admin
        if ($user->two_factor_enabled && !session('2fa_verified')) {
            Log::warning('Super Admin 2FA verification required', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            return redirect()->route('two-factor.verify')
                ->with('warning', 'Two-factor authentication required for Super Admin access.');
        }

        // Log successful Super Admin access for audit trail
        Log::info('Super Admin accessed protected route', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'route' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}