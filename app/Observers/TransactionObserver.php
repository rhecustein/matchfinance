<?php

namespace App\Observers;

use App\Models\StatementTransaction;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Handle the StatementTransaction "created" event.
     */
    public function created(StatementTransaction $transaction): void
    {
        $this->clearRelatedCaches();
        Log::info('Transaction created, cache cleared', ['transaction_id' => $transaction->id]);
    }

    /**
     * Handle the StatementTransaction "updated" event.
     */
    public function updated(StatementTransaction $transaction): void
    {
        $this->clearRelatedCaches();
        Log::info('Transaction updated, cache cleared', ['transaction_id' => $transaction->id]);
    }

    /**
     * Handle the StatementTransaction "deleted" event.
     */
    public function deleted(StatementTransaction $transaction): void
    {
        $this->clearRelatedCaches();
        Log::info('Transaction deleted, cache cleared', ['transaction_id' => $transaction->id]);
    }

    /**
     * Handle the StatementTransaction "restored" event.
     */
    public function restored(StatementTransaction $transaction): void
    {
        $this->clearRelatedCaches();
    }

    /**
     * Clear all related caches
     */
    private function clearRelatedCaches(): void
    {
        // Clear dashboard caches
        CacheService::clearDashboard();
        
        // Clear report caches
        CacheService::clearReports();
    }
}