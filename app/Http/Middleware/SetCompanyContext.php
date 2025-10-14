<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    /**
     * Handle an incoming request.
     * Set company context for multi-tenant operations
     * 
     * This middleware sets the current company context in config
     * and shares it with views for easy access throughout the application.
     * 
     * Benefits:
     * - Access company via config('app.current_company.id')
     * - Access company in views via $currentCompany
     * - Enables tenant-scoped queries
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Set company context only for authenticated company users
        if ($user && $user->company_id) {
            try {
                // Eager load company with subscription
                $company = $user->company()->with('activeSubscription.plan')->first();

                if ($company) {
                    // Set company context in config
                    config([
                        'app.current_company' => [
                            'id' => $company->id,
                            'uuid' => $company->uuid ?? null,
                            'slug' => $company->slug ?? null,
                            'name' => $company->name ?? null,
                            'is_active' => $company->is_active ?? false,
                        ]
                    ]);

                    // Share company data with all views
                    view()->share('currentCompany', $company);

                    // Optional: Set timezone based on company settings
                    if (isset($company->timezone)) {
                        config(['app.timezone' => $company->timezone]);
                        date_default_timezone_set($company->timezone);
                    }

                    Log::debug('Company context set', [
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'route' => $request->path(),
                    ]);
                } else {
                    Log::warning('User has company_id but company not found', [
                        'user_id' => $user->id,
                        'company_id' => $user->company_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to set company context', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($user && $user->isSuperAdmin()) {
            // For super admin, set a special context
            config(['app.current_company' => null]);
            view()->share('currentCompany', null);
            
            Log::debug('Super Admin context - no company', [
                'user_id' => $user->id,
            ]);
        }

        return $next($request);
    }
}