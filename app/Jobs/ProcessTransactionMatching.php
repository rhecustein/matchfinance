<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\Keyword;
use App\Models\MatchingLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransactionMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankStatement $bankStatement
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting transaction matching for Bank Statement ID: {$this->bankStatement->id}");

            // Get all unmatched transactions from this statement
            $transactions = StatementTransaction::where('bank_statement_id', $this->bankStatement->id)
                ->whereNull('matched_keyword_id')
                ->get();

            if ($transactions->isEmpty()) {
                Log::info("No unmatched transactions found for Bank Statement ID: {$this->bankStatement->id}");
                return;
            }

            // Get all active keywords with their relations
            $keywords = Keyword::with(['subCategory.category.type'])
                ->where('is_active', true)
                ->get();

            if ($keywords->isEmpty()) {
                Log::warning("No active keywords found for matching");
                return;
            }

            $matchedCount = 0;
            $unmatchedCount = 0;

            DB::beginTransaction();

            foreach ($transactions as $transaction) {
                $bestMatch = $this->findBestMatch($transaction, $keywords);

                if ($bestMatch) {
                    // Update transaction with matched data
                    $transaction->update([
                        'matched_keyword_id' => $bestMatch['keyword']->id,
                        'confidence_score' => $bestMatch['score'],
                        'type_id' => $bestMatch['keyword']->subCategory->category->type_id,
                        'category_id' => $bestMatch['keyword']->subCategory->category_id,
                        'sub_category_id' => $bestMatch['keyword']->sub_category_id,
                        'is_manual_category' => false,
                    ]);

                    // Log the match
                    MatchingLog::create([
                        'statement_transaction_id' => $transaction->id,
                        'keyword_id' => $bestMatch['keyword']->id,
                        'matched_text' => $bestMatch['matched_text'],
                        'confidence_score' => $bestMatch['score'],
                        'match_metadata' => [
                            'method' => $bestMatch['method'],
                            'keyword_text' => $bestMatch['keyword']->keyword,
                            'description' => $transaction->description,
                        ],
                        'matched_at' => now(),
                    ]);

                    $matchedCount++;
                } else {
                    $unmatchedCount++;
                }
            }

            // Update bank statement statistics
            $this->bankStatement->update([
                'matched_transactions' => $matchedCount,
                'unmatched_transactions' => $unmatchedCount,
            ]);

            DB::commit();

            Log::info("Transaction matching completed for Bank Statement ID: {$this->bankStatement->id}", [
                'matched' => $matchedCount,
                'unmatched' => $unmatchedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Transaction matching failed for Bank Statement ID: {$this->bankStatement->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Find the best matching keyword for a transaction
     */
    private function findBestMatch(StatementTransaction $transaction, $keywords): ?array
    {
        $description = strtolower($transaction->description ?? '');
        $bestMatch = null;
        $highestScore = 0;

        foreach ($keywords as $keyword) {
            $keywordText = strtolower($keyword->keyword);
            $score = 0;
            $method = '';
            $matchedText = '';

            // Method 1: Exact match (highest priority)
            if (strpos($description, $keywordText) !== false) {
                $score = 100;
                $method = 'exact_match';
                $matchedText = $keywordText;
            }
            // Method 2: Partial match with word boundaries
            elseif (preg_match('/\b' . preg_quote($keywordText, '/') . '\b/i', $description)) {
                $score = 90;
                $method = 'word_boundary_match';
                $matchedText = $keywordText;
            }
            // Method 3: Similar text (using similar_text function)
            else {
                similar_text($keywordText, $description, $percent);
                if ($percent > 70) {
                    $score = (int) $percent;
                    $method = 'similarity_match';
                    $matchedText = $keywordText;
                }
            }

            // Apply priority weight
            $weightedScore = $score * ($keyword->priority / 10);

            // Keep the best match
            if ($weightedScore > $highestScore) {
                $highestScore = $weightedScore;
                $bestMatch = [
                    'keyword' => $keyword,
                    'score' => (int) $score,
                    'weighted_score' => (int) $weightedScore,
                    'method' => $method,
                    'matched_text' => $matchedText,
                ];
            }
        }

        // Only return matches with confidence score >= 70
        return ($bestMatch && $bestMatch['score'] >= 70) ? $bestMatch : null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Transaction matching job failed permanently for Bank Statement ID: {$this->bankStatement->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}