<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     * Only allow super_admin users (company_id = NULL)
     * 
     * LIGHT VERSION - For quick super admin checks without logging
     * Use SuperAdminMiddleware for more comprehensive checks
     * 
     * Usage: ->middleware('super_admin.light')
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        // Check if user is super admin
        if (!$user->isSuperAdmin()) {
            abort(403, 'This action requires Super Admin privileges.');
        }

        // Additional validation: Super Admin should NOT have company_id
        if ($user->company_id !== null) {
            abort(500, 'System error: Super Admin account misconfigured.');
        }

        // Check if Super Admin is active
        if (!$user->is_active || $user->is_suspended) {
            abort(403, 'Your Super Admin account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        return $next($request);
    }
}