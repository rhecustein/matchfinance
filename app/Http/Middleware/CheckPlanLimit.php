<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    /**
     * Handle an incoming request.
     * Check if company has reached plan limit
     * 
     * This middleware prevents actions when plan limits are reached.
     * 
     * Common Limits:
     * - max_users (number of users)
     * - max_bank_statements (monthly uploads)
     * - max_transactions (monthly transactions)
     * - max_storage (file storage in MB)
     * - max_api_calls (API requests per month)
     * 
     * Usage: ->middleware('limit:max_users')
     */
    public function handle(Request $request, Closure $next, string $limitKey): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to limit-protected route', [
                'limit' => $limitKey,
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Validate limit key
        if (empty($limitKey)) {
            Log::error('CheckPlanLimit middleware called without limit key', [
                'route' => $request->path(),
                'user_id' => $user->id,
            ]);

            abort(500, 'Limit middleware misconfigured: no limit key specified.');
        }

        // Super admin has unlimited access (bypass)
        if ($user->isSuperAdmin()) {
            Log::debug('Super Admin bypassed limit check', [
                'user_id' => $user->id,
                'limit' => $limitKey,
            ]);

            return $next($request);
        }

        // Check if user has company
        if (!$user->company_id) {
            Log::error('User without company trying to access limit-protected resource', [
                'user_id' => $user->id,
                'limit' => $limitKey,
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

        // Get active subscription
        $subscription = $company->activeSubscription;

        if (!$subscription) {
            // Check if in trial
            if ($company->status === 'trial' && !$company->isTrialExpired()) {
                // During trial, use trial limits (usually generous)
                $limit = $this->getTrialLimit($limitKey);
                
                Log::info('Using trial limits', [
                    'company_id' => $company->id,
                    'limit_key' => $limitKey,
                    'limit' => $limit,
                ]);
            } else {
                Log::warning('No active subscription - limit check failed', [
                    'company_id' => $company->id,
                    'limit_key' => $limitKey,
                ]);

                return redirect()->back()
                    ->with('error', 'Please subscribe to continue.');
            }
        } else {
            // Check if unlimited
            if ($subscription->isUnlimited($limitKey)) {
                Log::debug('Unlimited access for limit', [
                    'company_id' => $company->id,
                    'limit_key' => $limitKey,
                    'plan' => $subscription->plan->name ?? 'Unknown',
                ]);

                return $next($request);
            }

            // Get limit from subscription
            $limit = $subscription->getFeatureLimit($limitKey, 0);
        }

        // Get current count
        $currentCount = $this->getCurrentCount($company, $limitKey);

        // Check if limit reached
        if ($currentCount >= $limit) {
            $limitName = $this->getLimitDisplayName($limitKey);

            Log::warning('Plan limit reached', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'limit_key' => $limitKey,
                'limit' => $limit,
                'current' => $currentCount,
                'plan' => $subscription->plan->name ?? 'Trial',
            ]);

            return redirect()->back()
                ->with('error', "You have reached your plan limit for {$limitName} ({$currentCount}/{$limit}). Please upgrade your plan to continue.");
        }

        // Log usage for tracking
        Log::info('Plan limit check passed', [
            'company_id' => $company->id,
            'limit_key' => $limitKey,
            'current' => $currentCount,
            'limit' => $limit,
            'percentage' => round(($currentCount / $limit) * 100, 2) . '%',
        ]);

        // Warn if approaching limit (>80%)
        if ($currentCount >= ($limit * 0.8)) {
            session()->flash('warning', "You are approaching your plan limit for {$this->getLimitDisplayName($limitKey)} ({$currentCount}/{$limit}). Consider upgrading soon.");
        }

        return $next($request);
    }

    /**
     * Get current count based on limit key
     */
    private function getCurrentCount($company, string $limitKey): int
    {
        try {
            return match($limitKey) {
                'max_users' => $company->users()->count(),
                'max_bank_statements' => $company->bankStatements()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'max_transactions' => \App\Models\StatementTransaction::where('company_id', $company->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'max_storage' => $this->getStorageUsage($company), // in MB
                'max_api_calls' => $this->getApiCallsCount($company),
                'max_chat_sessions' => $company->chatSessions()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'max_document_collections' => $company->documentCollections()->count(),
                default => 0,
            };
        } catch (\Exception $e) {
            Log::error('Error getting current count for limit', [
                'company_id' => $company->id,
                'limit_key' => $limitKey,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get storage usage in MB
     */
    private function getStorageUsage($company): int
    {
        try {
            $totalBytes = $company->bankStatements()->sum('file_size') ?? 0;
            return (int) ceil($totalBytes / 1024 / 1024); // Convert to MB
        } catch (\Exception $e) {
            Log::error('Error calculating storage usage', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get API calls count (current month)
     */
    private function getApiCallsCount($company): int
    {
        // Implement based on your API usage tracking
        // This is a placeholder
        return 0;
    }

    /**
     * Get trial limit (usually more generous)
     */
    private function getTrialLimit(string $limitKey): int
    {
        return match($limitKey) {
            'max_users' => 5,
            'max_bank_statements' => 10,
            'max_transactions' => 1000,
            'max_storage' => 100, // MB
            'max_api_calls' => 500,
            'max_chat_sessions' => 10,
            'max_document_collections' => 3,
            default => 10,
        };
    }

    /**
     * Get human-readable limit name
     */
    private function getLimitDisplayName(string $limitKey): string
    {
        return match($limitKey) {
            'max_users' => 'users',
            'max_bank_statements' => 'bank statements (monthly)',
            'max_transactions' => 'transactions (monthly)',
            'max_storage' => 'storage',
            'max_api_calls' => 'API calls (monthly)',
            'max_chat_sessions' => 'AI chat sessions (monthly)',
            'max_document_collections' => 'document collections',
            default => $limitKey,
        };
    }
}