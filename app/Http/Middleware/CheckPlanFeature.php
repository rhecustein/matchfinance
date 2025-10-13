<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    /**
     * Handle an incoming request.
     * Check if company plan has specific feature
     * 
     * Usage: ->middleware('feature:advanced_reports')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        // Super admin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has company
        if (!$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        $company = $user->company;

        // Check if company has active subscription
        if (!$company->hasActiveSubscription() && $company->status !== 'trial') {
            return redirect()->route('subscription.expired')
                ->with('error', 'Please subscribe to access this feature.');
        }

        // Check if plan has the feature
        $subscription = $company->activeSubscription ?? $company->subscription;
        
        if (!$subscription || !$subscription->hasFeature($feature)) {
            return redirect()->back()
                ->with('error', 'This feature is not available in your current plan. Please upgrade.');
        }

        return $next($request);
    }
}
