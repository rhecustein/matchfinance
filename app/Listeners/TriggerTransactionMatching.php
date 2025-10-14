<?php

namespace App\Listeners;

use App\Events\BankStatementOcrCompleted;
use App\Jobs\ProcessTransactionMatching;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TriggerTransactionMatching implements ShouldQueue
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
    public function handle(BankStatementOcrCompleted $event): void
    {
        Log::info("Triggering transaction matching after OCR completed", [
            'bank_statement_id' => $event->bankStatement->id,
            'company_id' => $event->bankStatement->company_id,
            'total_transactions' => $event->totalTransactions ?? 0,
        ]);

        // Dispatch transaction matching job
        ProcessTransactionMatching::dispatch($event->bankStatement)
            ->onQueue('matching');
    }
}