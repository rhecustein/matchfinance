<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check role
        $allowed = match($role) {
            'super_admin' => $user->isSuperAdmin(),
            'admin' => $user->isAdmin() || $user->isSuperAdmin(),
            'manager' => $user->isManager() || $user->isAdmin() || $user->isSuperAdmin(),
            'owner' => $user->isOwner() || $user->isSuperAdmin(),
            default => false,
        };

        if (!$allowed) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}