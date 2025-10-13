<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyOwner
{
    /**
     * Handle an incoming request.
     * Only allow company owners
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

        // Check if user is owner
        if (!$user->isOwner()) {
            abort(403, 'This action requires Owner privileges.');
        }

        return $next($request);
    }
}
