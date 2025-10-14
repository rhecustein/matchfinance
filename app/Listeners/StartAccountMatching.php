<?php

namespace App\Listeners;

use App\Events\TransactionMatchingCompleted;
use App\Jobs\ProcessAccountMatching;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class StartAccountMatching implements ShouldQueue
{
    public function handle(TransactionMatchingCompleted $event): void
    {
        $bankStatement = $event->bankStatement;
        
        Log::info('Starting account matching after transaction matching', [
            'statement_id' => $bankStatement->id,
            'matched_transactions' => $event->matchedCount,
        ]);

        // Check if company has active accounts
        $hasAccounts = \App\Models\Account::where('company_id', $bankStatement->company_id)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccounts) {
            Log::info('No active accounts, skipping account matching', [
                'statement_id' => $bankStatement->id,
            ]);
            return;
        }

        // Dispatch account matching job
        ProcessAccountMatching::dispatch($bankStatement, false)
            ->onQueue('matching')
            ->delay(now()->addSeconds(3));

        Log::info('Account matching job dispatched', [
            'statement_id' => $bankStatement->id,
        ]);
    }
}