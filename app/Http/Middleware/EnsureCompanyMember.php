<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyMember
{
    /**
     * Handle an incoming request.
     * Ensure user belongs to a company (any role)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        // Check if user belongs to a company
        if (!$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        // Check if user is active
        if (!$user->isActive()) {
            abort(403, 'Your account is not active. Please contact your administrator.');
        }

        return $next($request);
    }
}