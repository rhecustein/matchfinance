<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckActiveSubscription Middleware - FIXED VERSION
 * 
 * Memastikan company memiliki status yang aktif dan subscription yang valid
 * 
 * Company Status (enum):
 * - 'active': Company dengan subscription aktif
 * - 'trial': Company dalam masa trial
 * - 'suspended': Company di-suspend (tidak bisa akses)
 * - 'cancelled': Company dibatalkan (tidak bisa akses)
 */
class CheckActiveSubscription
{
    /**
     * Routes yang diperbolehkan akses tanpa subscription check
     */
    protected array $allowedRoutes = [
        'dashboard',
        'subscription.*',
        'profile.*',
        'logout',
    ];

    /**
     * Handle an incoming request.
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
        if ($this->isRouteAllowed($request)) {
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

        // ========================================================================
        // COMPANY STATUS CHECKS (berdasarkan field 'status', bukan 'is_active')
        // ========================================================================

        // 1. Check if company is SUSPENDED
        if ($company->status === 'suspended') {
            Log::warning('Suspended company access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'route' => $request->path(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account is suspended. Please contact support at support@matchfinance.com.');
        }

        // 2. Check if company is CANCELLED
        if ($company->status === 'cancelled') {
            Log::warning('Cancelled company access attempt', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'route' => $request->path(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Your company account has been cancelled. Please contact support to reactivate.');
        }

        // ========================================================================
        // TRIAL STATUS CHECKS
        // ========================================================================

        if ($company->status === 'trial') {
            // Check if trial expired
            if ($company->isTrialExpired()) {
                Log::info('Trial expired - redirecting to subscription', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'trial_ended_at' => $company->trial_ends_at,
                    'route' => $request->path(),
                ]);

                return redirect()->route('subscription.plans')
                    ->with('error', 'Your trial period has expired. Please subscribe to continue using MatchFinance.');
            }

            // Trial is still active - show warning if approaching expiration
            if ($company->trial_ends_at) {
                $daysLeft = now()->diffInDays($company->trial_ends_at, false);
                $daysLeft = max(0, (int) $daysLeft);
                
                if ($daysLeft <= 3 && $daysLeft > 0) {
                    session()->flash('warning', "Your trial expires in {$daysLeft} days. Subscribe now to avoid service interruption.");
                }
            }

            return $next($request);
        }

        // ========================================================================
        // ACTIVE SUBSCRIPTION CHECKS
        // ========================================================================

        // Check if company has active subscription
        if (!$company->hasActiveSubscription()) {
            Log::warning('No active subscription - access denied', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_status' => $company->status,
                'last_subscription_ended' => optional($company->activeSubscription)->ends_at,
                'route' => $request->path(),
            ]);

            return redirect()->route('subscription.plans')
                ->with('error', 'Your subscription has expired. Please renew to continue using MatchFinance.');
        }

        // Get subscription
        $subscription = $company->activeSubscription;

        // Validate subscription status
        if (!$subscription->isActive()) {
            Log::warning('Inactive subscription detected', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
                'ends_at' => $subscription->ends_at,
            ]);

            return redirect()->route('subscription.plans')
                ->with('error', 'Your subscription is not active. Please renew to continue.');
        }

        // Check if subscription is about to expire (within 7 days)
        if ($subscription->ends_at) {
            $daysLeft = now()->diffInDays($subscription->ends_at, false);
            $daysLeft = max(0, (int) $daysLeft);
            
            if ($daysLeft <= 7 && $daysLeft > 0) {
                session()->flash('warning', "Your subscription expires in {$daysLeft} days. Renew now to avoid service interruption.");
            }
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
     * Check if current route is in allowed list
     */
    protected function isRouteAllowed(Request $request): bool
    {
        foreach ($this->allowedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}