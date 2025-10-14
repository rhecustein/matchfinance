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
     * 
     * Ensure user has management access (Manager, Admin, or Owner)
     * in their company and company is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super admin should use admin panel routes
        if ($user->isSuperAdmin()) {
            abort(403, 'Super Admin should use admin panel routes.');
        }

        // User must belong to a company
        if (!$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        // User must have management access (Manager, Admin, or Owner)
        // Method yang benar: hasManagementAccess() bukan hasManagerAccess()
        if (!$user->hasManagementAccess()) {
            abort(403, 'This action requires Manager, Admin, or Owner privileges.');
        }

        // Company must exist
        if (!$user->company) {
            abort(500, 'System error: Company not found. Please contact support.');
        }

        // Check company status
        if (in_array($user->company->status, ['suspended', 'cancelled'])) {
            Log::warning('Manager access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'company_status' => $user->company->status,
                'route' => $request->path(),
            ]);

            abort(403, 'Your company account is currently ' . $user->company->status . '. Please contact support.');
        }

        // Check user account status
        if (!$user->is_active || $user->is_suspended) {
            abort(403, 'Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        return $next($request);
    }
}