<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    /**
     * Handle an incoming request.
     * Check if company has reached plan limit
     * 
     * Usage: ->middleware('limit:max_users')
     */
    public function handle(Request $request, Closure $next, string $limitKey): Response
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
        $subscription = $company->activeSubscription ?? $company->subscription;

        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'Please subscribe to continue.');
        }

        // Check if unlimited
        if ($subscription->isUnlimited($limitKey)) {
            return $next($request);
        }

        // Get limit
        $limit = $subscription->getFeatureLimit($limitKey, 0);

        // Get current count based on limit key
        $currentCount = $this->getCurrentCount($company, $limitKey);

        if ($currentCount >= $limit) {
            return redirect()->back()
                ->with('error', "You have reached the limit for {$limitKey}. Please upgrade your plan.");
        }

        return $next($request);
    }

    /**
     * Get current count based on limit key
     */
    private function getCurrentCount($company, string $limitKey): int
    {
        return match($limitKey) {
            'max_users' => $company->users()->count(),
            'max_products' => $company->products()->count() ?? 0,
            'max_transactions' => \App\Models\StatementTransaction::where('company_id', $company->id)->count(),
            'max_bank_statements' => $company->bankStatements()->count(),
            default => 0,
        };
    }
}