<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     * Ensure company has active subscription
     * 
     * This middleware blocks access to the application if:
     * - Company subscription has expired
     * - Company trial has ended
     * - Company is suspended or cancelled
     * 
     * Bypass conditions:
     * - Super Admin (always has access)
     * - Specific routes (dashboard, subscription pages)
     * 
     * Usage:
     * - Apply globally in Kernel or specific route groups
     * - Route::middleware('subscription')->group(...);
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to subscription-protected route', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Super admin has unlimited access (bypass)
        if ($user->isSuperAdmin()) {
            Log::debug('Super Admin bypassed subscription check', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            return $next($request);
        }

        // Allow access to certain routes even without active subscription
        $allowedRoutes = [
            'dashboard',
            'subscription.*',
            'profile.*',
            'logout',
        ];

        if ($this->isRouteAllowed($request, $allowedRoutes)) {
            return $next($request);
        }

        // Check if user has company
        if (!$user->company_id) {
            Log::error('User without company trying to access subscription-protected resource', [
                'user_id' => $user->id,
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
            ]);

            abort(500, 'Company not found. Please contact support.');
        }

        // Check company status - Suspended
        if ($company->status === 'suspended') {
            Log::warning('Suspended company access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'suspended_at' => $company->suspended_at,
                'route' => $request->path(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account is suspended. Please contact support at support@matchfinance.com.');
        }

        // Check company status - Cancelled
        if ($company->status === 'cancelled') {
            Log::warning('Cancelled company access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'cancelled_at' => $company->cancelled_at,
                'route' => $request->path(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account has been cancelled. Please contact support to reactivate.');
        }

        // Check if company is inactive
        if (!$company->is_active) {
            Log::warning('Inactive company access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'route' => $request->path(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account is currently inactive. Please contact support.');
        }

        // Check Trial Status
        if ($company->status === 'trial') {
            if ($company->isTrialExpired()) {
                Log::info('Trial expired - redirecting to subscription', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'trial_ended_at' => $company->trial_ends_at,
                    'route' => $request->path(),
                ]);

                return redirect()->route('subscription.expired')
                    ->with('error', 'Your trial period has expired. Please subscribe to continue using MatchFinance.');
            } else {
                // Trial is still active - show warning if approaching expiration
                $daysLeft = now()->diffInDays($company->trial_ends_at);
                
                if ($daysLeft <= 3) {
                    session()->flash('warning', "Your trial expires in {$daysLeft} days. Subscribe now to avoid service interruption.");
                }

                return $next($request);
            }
        }

        // Check Active Subscription
        if (!$company->hasActiveSubscription()) {
            Log::warning('No active subscription - access denied', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'last_subscription_ended' => $company->activeSubscription->ends_at ?? null,
                'route' => $request->path(),
            ]);

            return redirect()->route('subscription.expired')
                ->with('error', 'Your subscription has expired. Please renew to continue using MatchFinance.');
        }

        // Validate subscription status
        $subscription = $company->activeSubscription;

        if (!$subscription->isActive()) {
            Log::warning('Inactive subscription detected', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
                'ends_at' => $subscription->ends_at,
            ]);

            return redirect()->route('subscription.expired')
                ->with('error', 'Your subscription is not active. Please renew to continue.');
        }

        // Check if subscription is about to expire (within 7 days)
        if ($subscription->ends_at && $subscription->ends_at->diffInDays(now()) <= 7) {
            $daysLeft = $subscription->ends_at->diffInDays(now());
            session()->flash('warning', "Your subscription expires in {$daysLeft} days. Renew now to avoid service interruption.");
        }

        // Check if subscription is past due (grace period)
        if ($subscription->status === 'past_due') {
            Log::warning('Past due subscription accessed', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'ends_at' => $subscription->ends_at,
            ]);

            session()->flash('warning', 'Your subscription payment is past due. Please update your payment method to avoid service interruption.');
        }

        return $next($request);
    }

    /**
     * Check if route is allowed without active subscription
     */
    private function isRouteAllowed(Request $request, array $allowedRoutes): bool
    {
        $currentRoute = $request->route()->getName();

        foreach ($allowedRoutes as $pattern) {
            if (str_contains($pattern, '*')) {
                // Wildcard pattern
                $pattern = str_replace('*', '', $pattern);
                if (str_starts_with($currentRoute, $pattern)) {
                    return true;
                }
            } else {
                // Exact match
                if ($currentRoute === $pattern) {
                    return true;
                }
            }
        }

        return false;
    }
}