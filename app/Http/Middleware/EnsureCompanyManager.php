<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyManager
{
    /**
     * Handle an incoming request.
     * Allow managers and above (owner, admin, manager)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        // Check if user has company
        if (!$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        // Check if user has management access
        if (!$user->hasManagementAccess()) {
            abort(403, 'This action requires Manager privileges or higher.');
        }

        return $next($request);
    }
}