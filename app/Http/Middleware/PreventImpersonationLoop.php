<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventImpersonationLoop
{
    /**
     * Handle an incoming request.
     * Prevent impersonation of super admin or impersonation loops
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if currently impersonating
        if (session()->has('impersonate_from')) {
            $originalUserId = session('impersonate_from');
            $currentUserId = auth()->id();

            // Prevent impersonating while already impersonating
            if ($request->route()->getName() === 'admin.system-users.impersonate') {
                return redirect()->back()
                    ->with('error', 'You cannot impersonate while already impersonating another user.');
            }

            // Prevent loops
            if ($originalUserId === $currentUserId) {
                session()->forget('impersonate_from');
            }
        }

        return $next($request);
    }
}