<?php
// app/Listeners/StartTransactionMatching.php

namespace App\Listeners;

use App\Events\BankStatementOcrCompleted;
use App\Jobs\ProcessTransactionMatching;
use App\Jobs\ProcessAccountMatching;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class StartTransactionMatching implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'matching';
    public $tries = 3;
    public $timeout = 120;

    /**
     * Handle the event.
     */
    public function handle(BankStatementOcrCompleted $event): void
    {
        $bankStatement = $event->bankStatement;
        
        Log::info('Auto-starting transaction matching after OCR completion', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'total_transactions' => $event->totalTransactions,
        ]);

        // Only proceed if there are transactions
        if ($event->totalTransactions === 0) {
            Log::warning('No transactions found, skipping matching', [
                'statement_id' => $bankStatement->id,
            ]);
            return;
        }

        // Check if company has active keywords
        $hasKeywords = \App\Models\Keyword::where('company_id', $bankStatement->company_id)
            ->where('is_active', true)
            ->exists();

        if (!$hasKeywords) {
            Log::warning('No active keywords found, skipping matching', [
                'statement_id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
            ]);
            
            // Update statement with info
            $bankStatement->update([
                'matching_status' => 'skipped',
                'matching_notes' => 'No active keywords available for matching',
            ]);
            
            return;
        }

        // Dispatch Transaction Matching Job
        ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching')
            ->delay(now()->addSeconds(5)); // Small delay to ensure DB consistency

        Log::info('Transaction matching job dispatched', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(BankStatementOcrCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to start transaction matching', [
            'statement_id' => $event->bankStatement->id,
            'error' => $exception->getMessage(),
        ]);

        $event->bankStatement->update([
            'matching_status' => 'failed',
            'matching_error' => 'Failed to start matching: ' . $exception->getMessage(),
        ]);
    }
}