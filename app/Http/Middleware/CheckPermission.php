<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Check if user has specific permission(s)
     * 
     * Usage: ->middleware('permission:users.create')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        // Super admin has all permissions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the specified permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}