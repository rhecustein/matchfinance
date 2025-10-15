<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Services\AccountMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAccountMatchingPrep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    private const BATCH_SIZE = 100;

    public function __construct(
        public BankStatement $bankStatement,
        public bool $forceRematch = false
    ) {}

    public function handle(AccountMatchingService $accountMatchingService): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("🔍 [ACCOUNT MATCHING PREP] Starting for Bank Statement ID: {$this->bankStatement->id}", [
                'company_id' => $this->bankStatement->company_id,
                'total_transactions' => $this->bankStatement->total_transactions,
                'force_rematch' => $this->forceRematch,
            ]);

            // 1. Update bank statement status
            $this->bankStatement->update([
                'account_matching_prep_status' => 'processing',
                'account_matching_prep_started_at' => now(),
            ]);

            // 2. Retrieve unmatched transactions
            $transactionIds = $this->getTransactionIds();

            // 3. Process transactions in batches
            $processedCount = $this->processTransactionBatches($accountMatchingService, $transactionIds);

            // 4. Update bank statement status
            $this->bankStatement->update([
                'account_matching_prep_status' => 'completed',
                'account_matching_prep_completed_at' => now(),
                'account_matching_prep_count' => $processedCount,
            ]);

            // 5. Log performance
            $duration = round(microtime(true) - $startTime, 2);
            Log::info("✅ [ACCOUNT MATCHING PREP] Completed", [
                'bank_statement_id' => $this->bankStatement->id,
                'processed_count' => $processedCount,
                'duration_seconds' => $duration,
            ]);

            // 6. Trigger next job
            ProcessAccountMatching::dispatch($this->bankStatement, $this->forceRematch)
                ->onQueue('matching');

        } catch (\Exception $e) {
            // Error handling
            Log::error("❌ [ACCOUNT MATCHING PREP] Failed", [
                'bank_statement_id' => $this->bankStatement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->bankStatement->update([
                'account_matching_prep_status' => 'failed',
                'account_matching_prep_notes' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ambil ID transaksi yang akan diproses
     */
    private function getTransactionIds(): array
    {
        $query = StatementTransaction::where('bank_statement_id', $this->bankStatement->id)
            ->whereNull('account_id');

        // Force rematch jika diperlukan
        if (!$this->forceRematch) {
            $query->whereNull('matched_account_keyword_id');
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * Proses transaksi dalam batch
     */
    private function processTransactionBatches(AccountMatchingService $accountMatchingService, array $transactionIds): int
    {
        $processedCount = 0;

        // Proses dalam batch
        foreach (array_chunk($transactionIds, self::BATCH_SIZE) as $batch) {
            $batchResults = $accountMatchingService->processBatchTransactions($batch, $this->forceRematch);
            
            $processedCount += $batchResults['matched'] + $batchResults['unmatched'];

            // Log batch progress
            Log::info("[ACCOUNT MATCHING PREP] Batch processed", [
                'batch_size' => count($batch),
                'matched' => $batchResults['matched'],
                'unmatched' => $batchResults['unmatched'],
                'errors' => $batchResults['errors'],
            ]);
        }

        return $processedCount;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ [ACCOUNT MATCHING PREP] Job failed permanently", [
            'bank_statement_id' => $this->bankStatement->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        $this->bankStatement->update([
            'account_matching_prep_status' => 'failed',
            'account_matching_prep_notes' => $exception->getMessage(),
        ]);
    }
}