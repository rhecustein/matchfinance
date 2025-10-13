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

class ProcessAccountMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankStatement $bankStatement,
        public bool $forceRematch = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AccountMatchingService $accountMatchingService): void
    {
        try {
            Log::info("Starting account matching for Bank Statement ID: {$this->bankStatement->id}", [
                'company_id' => $this->bankStatement->company_id,
                'total_transactions' => $this->bankStatement->total_transactions,
                'force_rematch' => $this->forceRematch,
            ]);

            // Get transactions to process
            $query = StatementTransaction::where('bank_statement_id', $this->bankStatement->id);

            // If not force rematch, only process unmatched accounts
            if (!$this->forceRematch) {
                $query->whereNull('account_id');
            }

            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                Log::info("No transactions to match for Bank Statement ID: {$this->bankStatement->id}");
                return;
            }

            Log::info("Found {$transactions->count()} transaction(s) to process for account matching");

            $matchedCount = 0;
            $unmatchedCount = 0;
            $errorCount = 0;

            DB::beginTransaction();

            try {
                foreach ($transactions as $transaction) {
                    try {
                        // If force rematch, clear existing account data
                        if ($this->forceRematch && $transaction->account_id) {
                            $transaction->update([
                                'account_id' => null,
                                'matched_account_keyword_id' => null,
                                'account_confidence_score' => null,
                                'is_manual_account' => false,
                            ]);

                            // Delete existing account matching logs
                            $transaction->accountMatchingLogs()->delete();
                        }

                        // Perform account matching
                        $matchResult = $accountMatchingService->matchTransaction($transaction, $this->forceRematch);

                        if ($matchResult) {
                            // Assign account to transaction
                            $accountMatchingService->assignAccountToTransaction(
                                $transaction,
                                $matchResult['account_id'],
                                $matchResult['keyword_id'],
                                $matchResult['score'],
                                false, // not manual
                                $matchResult['reason']
                            );

                            $matchedCount++;

                            Log::debug("Account matched for transaction {$transaction->id}", [
                                'account_id' => $matchResult['account_id'],
                                'confidence_score' => $matchResult['score'],
                            ]);
                        } else {
                            $unmatchedCount++;

                            Log::debug("No account match found for transaction {$transaction->id}", [
                                'description' => $transaction->description,
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        
                        Log::error("Error matching account for transaction {$transaction->id}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                DB::commit();

                // Update bank statement statistics
                $this->bankStatement->refresh();
                $this->updateStatistics();

                Log::info("Account matching completed for Bank Statement ID: {$this->bankStatement->id}", [
                    'matched' => $matchedCount,
                    'unmatched' => $unmatchedCount,
                    'errors' => $errorCount,
                    'total_processed' => $transactions->count(),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error("Account matching job failed for Bank Statement ID: {$this->bankStatement->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update bank statement account matching statistics
     */
    protected function updateStatistics(): void
    {
        try {
            $withAccount = $this->bankStatement->transactions()
                ->whereNotNull('account_id')
                ->count();

            $withoutAccount = $this->bankStatement->transactions()
                ->whereNull('account_id')
                ->count();

            // Calculate average confidence score for matched accounts
            $avgConfidence = $this->bankStatement->transactions()
                ->whereNotNull('account_id')
                ->whereNotNull('account_confidence_score')
                ->avg('account_confidence_score');

            Log::info("Updated account statistics for Bank Statement ID: {$this->bankStatement->id}", [
                'with_account' => $withAccount,
                'without_account' => $withoutAccount,
                'avg_confidence' => round($avgConfidence ?? 0, 2),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to update account statistics", [
                'bank_statement_id' => $this->bankStatement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Account matching job failed permanently for Bank Statement ID: {$this->bankStatement->id}", [
            'company_id' => $this->bankStatement->company_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optional: Send notification to admin or user
        // You can dispatch a notification job here if needed
    }

    /**
     * Calculate the number of seconds the job can run.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'account-matching',
            'company:' . $this->bankStatement->company_id,
            'bank-statement:' . $this->bankStatement->id,
        ];
    }
}