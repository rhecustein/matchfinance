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
    public $maxExceptions = 3;

    private const BATCH_SIZE = 100;

    public function __construct(
        public BankStatement $bankStatement,
        public bool $forceRematch = false
    ) {}

    public function handle(AccountMatchingService $accountMatchingService): void
    {
        $startTime = microtime(true);
        
        try {
            // =========================================
            // STEP 1: Validation & Logging
            // =========================================
            Log::info("🔍 [ACCOUNT MATCHING PREP] Starting", [
                'bank_statement_id' => $this->bankStatement->id,
                'company_id' => $this->bankStatement->company_id,
                'total_transactions' => $this->bankStatement->total_transactions,
                'force_rematch' => $this->forceRematch,
            ]);

            // Validate bank statement
            if (!$this->bankStatement->exists) {
                throw new \Exception("Bank Statement not found");
            }

            // =========================================
            // STEP 2: Update Status to Processing
            // =========================================
            $this->bankStatement->update([
                'account_matching_status' => 'processing',
                'account_matching_started_at' => now(),
                'account_matching_notes' => null,
                'account_matching_error' => null,
            ]);

            // =========================================
            // STEP 3: Retrieve Transaction IDs
            // =========================================
            $transactionIds = $this->getTransactionIds();

            // Check if there are transactions to process
            if (empty($transactionIds)) {
                Log::info("ℹ️ [ACCOUNT MATCHING PREP] No transactions to process", [
                    'bank_statement_id' => $this->bankStatement->id,
                ]);

                $this->bankStatement->update([
                    'account_matching_status' => 'completed',
                    'account_matching_completed_at' => now(),
                    'account_matching_notes' => 'No transactions need account matching',
                ]);

                return;
            }

            Log::info("📊 [ACCOUNT MATCHING PREP] Transactions retrieved", [
                'bank_statement_id' => $this->bankStatement->id,
                'transaction_count' => count($transactionIds),
            ]);

            // =========================================
            // STEP 4: Process Transactions in Batches
            // =========================================
            $processedCount = $this->processTransactionBatches($accountMatchingService, $transactionIds);

            // =========================================
            // STEP 5: Update Status to Completed
            // =========================================
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->bankStatement->update([
                'account_matching_status' => 'completed',
                'account_matching_completed_at' => now(),
                'account_matching_notes' => "Processed {$processedCount} transactions in {$duration}s",
            ]);

            Log::info("✅ [ACCOUNT MATCHING PREP] Completed", [
                'bank_statement_id' => $this->bankStatement->id,
                'processed_count' => $processedCount,
                'duration_seconds' => $duration,
            ]);

            // =========================================
            // STEP 6: No need to trigger ProcessAccountMatching
            // Account matching is done directly in this job via service
            // =========================================

        } catch (\Exception $e) {
            // =========================================
            // Error Handling
            // =========================================
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Ambil ID transaksi yang akan diproses
     */
    private function getTransactionIds(): array
    {
        try {
            $query = StatementTransaction::where('bank_statement_id', $this->bankStatement->id)
                ->where('company_id', $this->bankStatement->company_id);

            if ($this->forceRematch) {
                // Force rematch: ambil semua transaksi kecuali yang manual
                $query->where('is_manual_account', false);
                
                Log::info("[ACCOUNT MATCHING PREP] Force rematch enabled - processing all non-manual transactions");
            } else {
                // Normal: hanya yang belum ada account_id dan bukan manual
                $query->whereNull('account_id')
                      ->whereNull('matched_account_keyword_id')
                      ->where('is_manual_account', false);
            }

            $transactionIds = $query->pluck('id')->toArray();

            Log::info("[ACCOUNT MATCHING PREP] Transaction IDs retrieved", [
                'count' => count($transactionIds),
                'force_rematch' => $this->forceRematch,
            ]);

            return $transactionIds;

        } catch (\Exception $e) {
            Log::error("[ACCOUNT MATCHING PREP] Failed to retrieve transaction IDs", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Proses transaksi dalam batch
     */
    private function processTransactionBatches(AccountMatchingService $accountMatchingService, array $transactionIds): int
    {
        $processedCount = 0;
        $totalBatches = (int) ceil(count($transactionIds) / self::BATCH_SIZE);
        $currentBatch = 0;

        Log::info("[ACCOUNT MATCHING PREP] Starting batch processing", [
            'total_transactions' => count($transactionIds),
            'batch_size' => self::BATCH_SIZE,
            'total_batches' => $totalBatches,
        ]);

        // Proses dalam batch
        foreach (array_chunk($transactionIds, self::BATCH_SIZE) as $batch) {
            $currentBatch++;
            
            try {
                $batchStartTime = microtime(true);

                // Process batch menggunakan service
                $batchResults = $accountMatchingService->processBatchTransactions($batch, $this->forceRematch);
                
                $processedCount += $batchResults['matched'] + $batchResults['unmatched'];
                $batchDuration = round(microtime(true) - $batchStartTime, 2);

                // Log batch progress
                Log::info("[ACCOUNT MATCHING PREP] Batch processed", [
                    'batch' => "{$currentBatch}/{$totalBatches}",
                    'batch_size' => count($batch),
                    'matched' => $batchResults['matched'],
                    'unmatched' => $batchResults['unmatched'],
                    'errors' => $batchResults['errors'],
                    'duration_seconds' => $batchDuration,
                ]);

            } catch (\Exception $e) {
                Log::error("[ACCOUNT MATCHING PREP] Batch processing failed", [
                    'batch' => "{$currentBatch}/{$totalBatches}",
                    'batch_size' => count($batch),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Continue with next batch instead of failing completely
                continue;
            }
        }

        return $processedCount;
    }

    /**
     * Handle job failure
     */
    private function handleFailure(\Exception $e): void
    {
        Log::error("❌ [ACCOUNT MATCHING PREP] Job failed", [
            'bank_statement_id' => $this->bankStatement->id,
            'company_id' => $this->bankStatement->company_id,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        $this->bankStatement->update([
            'account_matching_status' => 'failed',
            'account_matching_notes' => 'Job processing failed',
            'account_matching_error' => $e->getMessage(),
            'account_matching_completed_at' => now(),
        ]);
    }

    /**
     * Handle permanent job failure (after all retries)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ [ACCOUNT MATCHING PREP] Job failed permanently", [
            'bank_statement_id' => $this->bankStatement->id,
            'company_id' => $this->bankStatement->company_id,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        $this->bankStatement->update([
            'account_matching_status' => 'failed',
            'account_matching_notes' => "Job failed after {$this->attempts()} attempts",
            'account_matching_error' => $exception->getMessage(),
            'account_matching_completed_at' => now(),
        ]);
    }
}