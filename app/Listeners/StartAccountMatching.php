<?php
// app/Listeners/StartAccountMatching.php

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
        
        // Only if company has accounts configured
        $hasAccounts = \App\Models\Account::where('company_id', $bankStatement->company_id)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccounts) {
            Log::info('No active accounts found, skipping account matching', [
                'statement_id' => $bankStatement->id,
            ]);
            return;
        }

        ProcessAccountMatching::dispatch($bankStatement)
            ->onQueue('matching')
            ->delay(now()->addSeconds(5));

        Log::info('Account matching job dispatched', [
            'statement_id' => $bankStatement->id,
            'matched_transactions' => $event->matchedCount,
        ]);
    }
}