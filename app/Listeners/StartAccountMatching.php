<?php

namespace App\Listeners;

use App\Events\TransactionMatchingCompleted;
use App\Jobs\ProcessAccountMatching;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class StartAccountMatching implements ShouldQueue
{
    /**
     * Handle the event.
     * 
     * Triggered after transaction matching completes.
     * This listener checks prerequisites and dispatches account matching job.
     */
    public function handle(TransactionMatchingCompleted $event): void
    {
        $bankStatement = $event->bankStatement;
        
        // Base log context untuk konsistensi
        $logContext = [
            'listener' => 'StartAccountMatching',
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
        ];
        
        // 🟢 LOG: Event Received
        Log::info('📩 [ACCOUNT MATCHING] Listener received TransactionMatchingCompleted event', array_merge($logContext, [
            'matched_transactions' => $event->matchedCount,
            'unmatched_transactions' => $event->unmatchedCount,
            'timestamp' => now()->toDateTimeString(),
        ]));

        // =========================================
        // STEP 1: Check Active Accounts
        // =========================================
        $activeAccountsCount = \App\Models\Account::where('company_id', $bankStatement->company_id)
            ->where('is_active', true)
            ->count();

        // 🟢 LOG: Account Check Result
        Log::info('🏪 [ACCOUNT MATCHING] Checking active accounts', array_merge($logContext, [
            'active_accounts' => $activeAccountsCount,
        ]));

        if ($activeAccountsCount === 0) {
            Log::warning('⚠️ [ACCOUNT MATCHING] Skipping - No active accounts found', $logContext);
            
            // Optional: Update bank statement status
            $bankStatement->update([
                'account_matching_status' => 'skipped',
                'account_matching_notes' => 'No active accounts available',
            ]);
            
            return;
        }

        // =========================================
        // STEP 2: Check Active Account Keywords
        // =========================================
        $activeKeywordsCount = \App\Models\AccountKeyword::where('company_id', $bankStatement->company_id)
            ->where('is_active', true)
            ->count();

        // 🟢 LOG: Keyword Check Result
        Log::info('🔑 [ACCOUNT MATCHING] Checking active account keywords', array_merge($logContext, [
            'active_keywords' => $activeKeywordsCount,
        ]));

        if ($activeKeywordsCount === 0) {
            Log::warning('⚠️ [ACCOUNT MATCHING] Skipping - No active account keywords found', $logContext);
            
            // Optional: Update bank statement status
            $bankStatement->update([
                'account_matching_status' => 'skipped',
                'account_matching_notes' => 'No active account keywords available',
            ]);
            
            return;
        }

        // =========================================
        // STEP 3: Check Transactions to Match
        // =========================================
        $transactionsToMatch = \App\Models\StatementTransaction::where('bank_statement_id', $bankStatement->id)
            ->where('company_id', $bankStatement->company_id)
            ->whereNull('account_id')
            ->where('is_manual_account', false)
            ->count();

        // 🟢 LOG: Transaction Check
        Log::info('📦 [ACCOUNT MATCHING] Checking transactions to match', array_merge($logContext, [
            'transactions_to_match' => $transactionsToMatch,
            'total_transactions' => $bankStatement->total_transactions,
        ]));

        if ($transactionsToMatch === 0) {
            Log::info('ℹ️ [ACCOUNT MATCHING] Skipping - No transactions need account matching', $logContext);
            return;
        }

        // =========================================
        // STEP 4: Dispatch Account Matching Job
        // =========================================
        $delaySeconds = 3;
        $queueName = 'matching';

        // 🟢 LOG: Preparing to Dispatch
        Log::info('🚀 [ACCOUNT MATCHING] Preparing to dispatch job', array_merge($logContext, [
            'queue' => $queueName,
            'delay_seconds' => $delaySeconds,
            'force_rematch' => false,
            'dispatch_at' => now()->addSeconds($delaySeconds)->toDateTimeString(),
        ]));

        try {
            // Dispatch job
            ProcessAccountMatching::dispatch($bankStatement, false)
                ->onQueue($queueName)
                ->delay(now()->addSeconds($delaySeconds));

            // 🟢 LOG: Job Successfully Dispatched
            Log::info('✅ [ACCOUNT MATCHING] Job successfully dispatched', array_merge($logContext, [
                'job_class' => 'ProcessAccountMatching',
                'will_execute_at' => now()->addSeconds($delaySeconds)->toDateTimeString(),
            ]));

        } catch (\Exception $e) {
            // 🔴 LOG: Dispatch Failed
            Log::error('❌ [ACCOUNT MATCHING] Failed to dispatch job', array_merge($logContext, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            // Optional: Update bank statement status
            $bankStatement->update([
                'account_matching_status' => 'failed',
                'account_matching_notes' => 'Failed to dispatch job: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a failed job.
     * 
     * Called when the listener fails after all retry attempts.
     */
    public function failed(TransactionMatchingCompleted $event, \Throwable $exception): void
    {
        $bankStatement = $event->bankStatement;

        // 🔴 LOG: Listener Failed Permanently
        Log::error('❌ [ACCOUNT MATCHING] Listener failed permanently', [
            'listener' => 'StartAccountMatching',
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Update bank statement status
        $bankStatement->update([
            'account_matching_status' => 'failed',
            'account_matching_notes' => 'Listener failed: ' . $exception->getMessage(),
        ]);
    }
}