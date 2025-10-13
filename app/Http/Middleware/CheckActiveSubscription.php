<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     * Ensure company has active subscription
     */
    public function handle(Request $request, Closure $next): Response
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

        // Check company status
        if ($company->status === 'suspended') {
            return redirect()->route('dashboard')
                ->with('error', 'Your company account is suspended. Please contact support.');
        }

        if ($company->status === 'cancelled') {
            return redirect()->route('dashboard')
                ->with('error', 'Your company account has been cancelled. Please contact support.');
        }

        // Check if subscription exists and is active
        if (!$company->hasActiveSubscription() && $company->status !== 'trial') {
            return redirect()->route('subscription.expired')
                ->with('error', 'Your subscription has expired. Please renew to continue.');
        }

        // Check if trial expired
        if ($company->status === 'trial' && $company->isTrialExpired()) {
            return redirect()->route('subscription.expired')
                ->with('error', 'Your trial period has expired. Please subscribe to continue.');
        }

        return $next($request);
    }
}