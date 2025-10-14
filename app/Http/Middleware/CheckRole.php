<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Check if user has specific role(s)
     * 
     * Usage Examples:
     * - Single role: ->middleware('role:admin')
     * - Multiple roles: ->middleware('role:owner,admin')
     * - In routes: Route::middleware('role:admin')->group(...)
     * 
     * Supported roles:
     * - super_admin (system-wide access, no company)
     * - owner (company owner)
     * - admin (company admin)
     * - manager (company manager)
     * - staff (company staff)
     * - user (basic company user)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check authentication
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Validate roles parameter
        if (empty($roles)) {
            Log::warning('CheckRole middleware called without roles parameter', [
                'route' => $request->path(),
                'user_id' => $user->id,
            ]);
            abort(500, 'Role middleware misconfigured: no roles specified.');
        }

        // Super admin has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the specified roles
        if (!$user->hasAnyRole($roles)) {
            Log::warning('Access denied: User does not have required role', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'route' => $request->path(),
                'company_id' => $user->company_id,
            ]);

            abort(403, 'Access denied. You do not have the required role to access this resource.');
        }

        // Additional check: Ensure company users belong to a company
        if (!$user->isSuperAdmin() && !$user->company_id) {
            Log::error('User without company trying to access company resource', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. You must belong to a company to access this resource.');
        }

        return $next($request);
    }
}