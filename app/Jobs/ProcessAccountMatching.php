<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\AccountKeyword;
use App\Models\AccountMatchingLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessAccountMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    // Configuration
    private const MIN_CONFIDENCE_THRESHOLD = 50; // Minimum score untuk assign account
    private const MAX_SUGGESTIONS = 5; // Top 5 account suggestions
    private const CACHE_TTL = 3600; // 1 hour cache untuk account keywords

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
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("Starting account matching for Bank Statement ID: {$this->bankStatement->id}", [
                'company_id' => $this->bankStatement->company_id,
                'total_transactions' => $this->bankStatement->total_transactions,
                'force_rematch' => $this->forceRematch,
            ]);

            // Get transactions to process
            $query = StatementTransaction::where('bank_statement_id', $this->bankStatement->id)
                ->where('company_id', $this->bankStatement->company_id); // Security: company scoped

            // If not force rematch, only process unmatched accounts
            if (!$this->forceRematch) {
                $query->whereNull('account_id')
                      ->where('is_manual_account', false); // Don't override manual assignments
            } else {
                // On force rematch, skip manual assignments
                $query->where('is_manual_account', false);
            }

            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                Log::info("No transactions to match for account matching (Bank Statement ID: {$this->bankStatement->id})");
                return;
            }

            // Get all active account keywords with relations (with caching)
            $accountKeywords = $this->getActiveAccountKeywords($this->bankStatement->company_id);

            if ($accountKeywords->isEmpty()) {
                Log::warning("No active account keywords found for matching", [
                    'company_id' => $this->bankStatement->company_id
                ]);
                return;
            }

            $matchedCount = 0;
            $unmatchedCount = 0;

            DB::beginTransaction();

            foreach ($transactions as $transaction) {
                $matchingStartTime = microtime(true);
                
                // If force rematch, clear existing account data
                if ($this->forceRematch && $transaction->account_id) {
                    $transaction->update([
                        'account_id' => null,
                        'matched_account_keyword_id' => null,
                        'account_confidence_score' => null,
                    ]);

                    // Delete existing account matching logs
                    AccountMatchingLog::where('statement_transaction_id', $transaction->id)->delete();
                }

                // Find ALL possible account matches
                $allMatches = $this->findAllAccountMatches($transaction, $accountKeywords);
                
                $matchingDuration = round((microtime(true) - $matchingStartTime) * 1000); // milliseconds

                if (empty($allMatches)) {
                    // No match found
                    $this->handleNoAccountMatch($transaction, $matchingDuration);
                    $unmatchedCount++;
                } else {
                    // Process matches and assign account
                    $result = $this->processAccountMatches($transaction, $allMatches, $matchingDuration);
                    
                    if ($result['primary_score'] >= self::MIN_CONFIDENCE_THRESHOLD) {
                        $matchedCount++;
                    } else {
                        $unmatchedCount++;
                    }
                }
            }

            DB::commit();

            $totalDuration = round((microtime(true) - $startTime) * 1000);

            Log::info("Account matching completed for Bank Statement ID: {$this->bankStatement->id}", [
                'matched' => $matchedCount,
                'unmatched' => $unmatchedCount,
                'duration_ms' => $totalDuration,
                'avg_per_transaction_ms' => round($totalDuration / $transactions->count()),
            ]);

            // ✅ FIRE EVENT - Account matching completed
            event(new \App\Events\AccountMatchingCompleted(
                $this->bankStatement->fresh(),
                $matchedCount,
                $unmatchedCount
            ));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Account matching failed for Bank Statement ID: {$this->bankStatement->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get active account keywords with caching
     */
    private function getActiveAccountKeywords(int $companyId)
    {
        $cacheKey = "account_keywords_active_company_{$companyId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            return AccountKeyword::with(['account'])
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    /**
     * Find ALL possible account matches for a transaction
     * Returns array of matches with scores
     */
    private function findAllAccountMatches(StatementTransaction $transaction, $accountKeywords): array
    {
        $description = $this->normalizeDescription($transaction->description ?? '');
        $allMatches = [];

        foreach ($accountKeywords as $keyword) {
            $matchResult = $this->matchAccountKeyword($description, $keyword, $transaction);
            
            if ($matchResult && $matchResult['score'] >= self::MIN_CONFIDENCE_THRESHOLD) {
                $allMatches[] = $matchResult;
            }
        }

        // Sort by score (descending)
        usort($allMatches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $allMatches;
    }

    /**
     * Match a single account keyword against description
     */
    private function matchAccountKeyword(string $description, AccountKeyword $keyword, StatementTransaction $transaction): ?array
    {
        $keywordText = $keyword->case_sensitive 
            ? $keyword->keyword 
            : strtolower($keyword->keyword);
        
        $searchText = $keyword->case_sensitive 
            ? $transaction->description 
            : $description;

        $score = 0;
        $method = '';
        $matchedText = '';

        // REGEX PATTERN MATCHING
        if ($keyword->is_regex) {
            try {
                if (preg_match('/' . $keyword->keyword . '/u', $searchText, $matches)) {
                    $score = 85; // Regex match gets high score
                    $method = 'regex';
                    $matchedText = $matches[0] ?? $keywordText;
                }
            } catch (\Exception $e) {
                Log::warning("Invalid regex pattern in account keyword", [
                    'keyword_id' => $keyword->id,
                    'pattern' => $keyword->keyword,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        // EXACT MATCH (highest confidence)
        elseif ($searchText === $keywordText) {
            $score = 100;
            $method = 'exact_match';
            $matchedText = $keywordText;
        }
        // CONTAINS MATCH
        elseif (strpos($searchText, $keywordText) !== false) {
            $score = 95;
            $method = 'contains';
            $matchedText = $keywordText;
            
            // Bonus: if match is at start of string
            if (strpos($searchText, $keywordText) === 0) {
                $score = 98;
                $method = 'starts_with';
            }
        }
        // WORD BOUNDARY MATCH
        elseif (preg_match('/\b' . preg_quote($keywordText, '/') . '\b/ui', $searchText)) {
            $score = 90;
            $method = 'word_boundary';
            $matchedText = $keywordText;
        }
        // PARTIAL WORD MATCH
        elseif ($this->containsPartialMatch($searchText, $keywordText)) {
            $score = 80;
            $method = 'partial_word';
            $matchedText = $keywordText;
        }
        // SIMILARITY MATCH (fuzzy matching)
        else {
            similar_text($keywordText, $searchText, $percent);
            if ($percent >= 70) {
                $score = (int) $percent;
                $method = 'similarity';
                $matchedText = $keywordText;
            }
        }

        // No match found
        if ($score === 0) {
            return null;
        }

        // Apply priority weighting
        $priorityMultiplier = $keyword->priority / 10;
        $weightedScore = (int) ($score * $priorityMultiplier);

        // Calculate final confidence
        $finalScore = $this->calculateFinalConfidence($score, $keyword, $transaction, $method);

        return [
            'account_keyword' => $keyword,
            'account_id' => $keyword->account_id,
            'score' => $finalScore,
            'raw_score' => $score,
            'weighted_score' => $weightedScore,
            'method' => $method,
            'matched_text' => $matchedText,
            'source' => 'auto_match',
            'match_metadata' => [
                'keyword_text' => $keyword->keyword,
                'priority' => $keyword->priority,
                'is_regex' => $keyword->is_regex,
                'case_sensitive' => $keyword->case_sensitive,
                'account_name' => $keyword->account->name,
                'account_code' => $keyword->account->code,
            ]
        ];
    }

    /**
     * Calculate final confidence score with additional factors
     */
    private function calculateFinalConfidence(
        int $baseScore, 
        AccountKeyword $keyword, 
        StatementTransaction $transaction,
        string $method
    ): int {
        $score = $baseScore;

        // Factor 1: Keyword match history (performance bonus)
        if ($keyword->match_count > 10) {
            $score += 2; // Proven keyword gets bonus
        }

        // Factor 2: Transaction type alignment with account type
        if ($transaction->transaction_type === 'credit' && $keyword->account->account_type === 'revenue') {
            $score += 3; // Type alignment bonus for revenue
        } elseif ($transaction->transaction_type === 'debit' && $keyword->account->account_type === 'expense') {
            $score += 3; // Type alignment bonus for expense
        }

        // Factor 3: Regex penalty (less reliable than exact match)
        if ($method === 'regex') {
            $score -= 3;
        }

        // Factor 4: Similarity penalty (least reliable)
        if ($method === 'similarity') {
            $score -= 5;
        }

        // Ensure score stays in 0-100 range
        return max(0, min(100, $score));
    }

    /**
     * Process account matches and assign to transaction
     */
    private function processAccountMatches(
        StatementTransaction $transaction, 
        array $allMatches, 
        int $matchingDuration
    ): array {
        // Take top N suggestions
        $topMatches = array_slice($allMatches, 0, self::MAX_SUGGESTIONS);
        
        // Primary match (best score)
        $primaryMatch = $topMatches[0];

        // Build account suggestions JSON structure
        $suggestions = [];
        foreach ($topMatches as $index => $match) {
            $suggestions[] = [
                'rank' => $index + 1,
                'account_id' => $match['account_id'],
                'account_name' => $match['account_keyword']->account->name,
                'account_code' => $match['account_keyword']->account->code,
                'account_type' => $match['account_keyword']->account->account_type,
                'keyword_id' => $match['account_keyword']->id,
                'keyword' => $match['account_keyword']->keyword,
                'confidence_score' => $match['score'],
                'raw_score' => $match['raw_score'],
                'weighted_score' => $match['weighted_score'],
                'matched_text' => $match['matched_text'],
                'match_method' => $match['method'],
                'source' => $match['source'],
            ];
        }

        $accountSuggestions = [
            'suggestions' => $suggestions,
            'generation_timestamp' => now()->toIso8601String(),
            'total_suggestions' => count($suggestions),
            'total_candidates' => count($allMatches),
            'primary_suggestion' => [
                'account_id' => $primaryMatch['account_id'],
                'confidence_score' => $primaryMatch['score'],
                'method' => $primaryMatch['method'],
            ],
            'matching_stats' => [
                'duration_ms' => $matchingDuration,
                'keywords_evaluated' => count($allMatches),
            ]
        ];

        // Update transaction with primary account match
        $transaction->update([
            // Primary Account Match
            'account_id' => $primaryMatch['account_id'],
            'matched_account_keyword_id' => $primaryMatch['account_keyword']->id,
            'account_confidence_score' => $primaryMatch['score'],
            
            // Alternative Suggestions (stored in match_metadata)
            'match_metadata' => array_merge($transaction->match_metadata ?? [], [
                'account_suggestions' => $accountSuggestions,
                'account_matched_at' => now()->toIso8601String(),
            ]),
            
            // Flags
            'is_manual_account' => false,
        ]);

        // Log primary match to account_matching_logs
        AccountMatchingLog::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'company_id' => $transaction->company_id,
            'statement_transaction_id' => $transaction->id,
            'account_id' => $primaryMatch['account_id'],
            'account_keyword_id' => $primaryMatch['account_keyword']->id,
            'matched_text' => $primaryMatch['matched_text'],
            'confidence_score' => $primaryMatch['score'],
            'match_type' => $primaryMatch['method'],
            'is_matched' => true,
            'is_selected' => true,
            'priority_score' => $primaryMatch['weighted_score'],
            'match_reason' => "Automatic match via keyword: {$primaryMatch['account_keyword']->keyword}",
            'match_details' => [
                'method' => $primaryMatch['method'],
                'keyword_text' => $primaryMatch['account_keyword']->keyword,
                'description' => $transaction->description,
                'raw_score' => $primaryMatch['raw_score'],
                'weighted_score' => $primaryMatch['weighted_score'],
                'alternatives_count' => count($suggestions) - 1,
            ],
            'matching_engine' => 'auto',
            'processing_time_ms' => $matchingDuration,
        ]);

        // Update keyword statistics
        $primaryMatch['account_keyword']->increment('match_count');
        $primaryMatch['account_keyword']->update(['last_matched_at' => now()]);

        return [
            'success' => true,
            'primary_score' => $primaryMatch['score'],
            'suggestions_count' => count($suggestions),
        ];
    }

    /**
     * Handle transaction with no account match
     */
    private function handleNoAccountMatch(StatementTransaction $transaction, int $matchingDuration): void
    {
        $transaction->update([
            'account_id' => null,
            'matched_account_keyword_id' => null,
            'account_confidence_score' => 0,
            
            'match_metadata' => array_merge($transaction->match_metadata ?? [], [
                'account_suggestions' => [
                    'suggestions' => [],
                    'generation_timestamp' => now()->toIso8601String(),
                    'total_suggestions' => 0,
                    'primary_suggestion' => null,
                    'matching_stats' => [
                        'duration_ms' => $matchingDuration,
                        'keywords_evaluated' => 0,
                        'result' => 'no_match'
                    ]
                ],
            ]),
            
            'is_manual_account' => false,
        ]);
    }

    /**
     * Normalize description for better matching
     */
    private function normalizeDescription(string $description): string
    {
        // Convert to lowercase
        $normalized = strtolower($description);
        
        // Remove special characters but keep spaces
        $normalized = preg_replace('/[^a-z0-9\s]/u', ' ', $normalized);
        
        // Remove extra whitespaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Trim
        $normalized = trim($normalized);
        
        return $normalized;
    }

    /**
     * Check if description contains partial word match
     */
    private function containsPartialMatch(string $description, string $keyword): bool
    {
        $descWords = explode(' ', $description);
        $keywordWords = explode(' ', $keyword);
        
        foreach ($keywordWords as $keywordWord) {
            foreach ($descWords as $descWord) {
                if (strlen($keywordWord) > 3 && strlen($descWord) > 3) {
                    similar_text($keywordWord, $descWord, $percent);
                    if ($percent >= 80) {
                        return true;
                    }
                }
            }
        }
        
        return false;
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
    }
}