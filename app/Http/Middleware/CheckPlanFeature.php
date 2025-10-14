<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    /**
     * Handle an incoming request.
     * Check if company plan has specific feature
     * 
     * This middleware checks if a company's subscription plan
     * includes access to a specific feature.
     * 
     * Common Features:
     * - advanced_reports
     * - ai_chat
     * - bulk_upload
     * - api_access
     * - custom_categories
     * - export_data
     * - multi_user
     * 
     * Usage: ->middleware('feature:advanced_reports')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to feature-protected route', [
                'feature' => $feature,
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Validate feature parameter
        if (empty($feature)) {
            Log::error('CheckPlanFeature middleware called without feature parameter', [
                'route' => $request->path(),
                'user_id' => $user->id,
            ]);

            abort(500, 'Feature middleware misconfigured: no feature specified.');
        }

        // Super admin has access to all features (bypass)
        if ($user->isSuperAdmin()) {
            Log::debug('Super Admin bypassed feature check', [
                'user_id' => $user->id,
                'feature' => $feature,
                'route' => $request->path(),
            ]);

            return $next($request);
        }

        // Check if user has company
        if (!$user->company_id) {
            Log::error('User without company trying to access feature-protected resource', [
                'user_id' => $user->id,
                'feature' => $feature,
                'route' => $request->path(),
            ]);

            abort(403, 'You must belong to a company to access this resource.');
        }

        $company = $user->company;

        // Check if company exists
        if (!$company) {
            Log::error('Company not found for user', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'feature' => $feature,
            ]);

            abort(500, 'Company not found. Please contact support.');
        }

        // Check company status first
        if (!$company->is_active) {
            Log::warning('Feature access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'feature' => $feature,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account is currently inactive. Please contact support.');
        }

        if ($company->status === 'suspended') {
            Log::warning('Feature access attempt from suspended company', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'feature' => $feature,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account is suspended. Please contact support.');
        }

        if ($company->status === 'cancelled') {
            Log::warning('Feature access attempt from cancelled company', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'feature' => $feature,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account has been cancelled. Please contact support to reactivate.');
        }

        // Get active subscription
        $subscription = $company->activeSubscription;

        // Check if company has active subscription or is in trial
        if (!$subscription) {
            // Check if in trial period
            if ($company->status === 'trial') {
                if (!$company->isTrialExpired()) {
                    // Trial is still active - allow access to all features
                    Log::info('Trial company accessed feature', [
                        'company_id' => $company->id,
                        'feature' => $feature,
                        'trial_ends_at' => $company->trial_ends_at,
                    ]);

                    return $next($request);
                } else {
                    // Trial expired
                    Log::warning('Trial expired - feature access denied', [
                        'company_id' => $company->id,
                        'feature' => $feature,
                        'trial_ended_at' => $company->trial_ends_at,
                    ]);

                    return redirect()->route('subscription.expired')
                        ->with('error', 'Your trial period has expired. Please subscribe to access this feature.');
                }
            }

            // No subscription and not in trial
            Log::warning('No active subscription - feature access denied', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'feature' => $feature,
            ]);

            return redirect()->route('subscription.expired')
                ->with('error', 'Please subscribe to access this feature.');
        }

        // Check if subscription is active
        if (!$subscription->isActive()) {
            Log::warning('Inactive subscription - feature access denied', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
                'feature' => $feature,
            ]);

            return redirect()->route('subscription.expired')
                ->with('error', 'Your subscription is not active. Please renew to continue.');
        }

        // Check if plan has the feature
        if (!$subscription->hasFeature($feature)) {
            Log::info('Feature not available in current plan', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $subscription->plan->name ?? 'Unknown',
                'feature' => $feature,
                'route' => $request->path(),
            ]);

            return redirect()->back()
                ->with('error', 'This feature is not available in your current plan. Please upgrade to access it.');
        }

        // Feature check passed
        Log::debug('Feature check passed', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'feature' => $feature,
            'plan' => $subscription->plan->name ?? 'Unknown',
        ]);

        return $next($request);
    }
}