<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    /**
     * Handle an incoming request.
     * Set company context for multi-tenant operations
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->company_id) {
            // Set company context in config
            config(['app.current_company.id' => $user->company_id]);
            config(['app.current_company.slug' => $user->company->slug ?? null]);

            // Share company data with views
            view()->share('currentCompany', $user->company);
        }

        return $next($request);
    }
}