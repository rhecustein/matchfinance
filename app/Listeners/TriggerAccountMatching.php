<?php

namespace App\Listeners;

use App\Events\TransactionMatchingCompleted;
use App\Jobs\ProcessAccountMatching;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TriggerAccountMatching implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionMatchingCompleted $event): void
    {
        Log::info("Triggering account matching after transaction matching completed", [
            'bank_statement_id' => $event->bankStatement->id,
            'company_id' => $event->bankStatement->company_id,
            'matched_transactions' => $event->matchedCount,
            'unmatched_transactions' => $event->unmatchedCount,
        ]);

        // Dispatch account matching job
        ProcessAccountMatching::dispatch($event->bankStatement, false)
            ->onQueue('matching');
    }
}