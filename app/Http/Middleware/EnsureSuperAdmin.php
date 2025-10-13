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

        return $next($request);
    }
}