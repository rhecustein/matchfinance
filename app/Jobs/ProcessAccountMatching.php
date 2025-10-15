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

    public $timeout = 600;
    public $tries = 3;

    private const MIN_CONFIDENCE_THRESHOLD = 50;
    private const MAX_SUGGESTIONS = 5;
    private const CACHE_TTL = 3600;

    public function __construct(
        public BankStatement $bankStatement,
        public bool $forceRematch = false
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("🏪 [ACCOUNT MATCHING] Starting for Bank Statement ID: {$this->bankStatement->id}", [
                'company_id' => $this->bankStatement->company_id,
                'total_transactions' => $this->bankStatement->total_transactions,
                'force_rematch' => $this->forceRematch,
            ]);

            // ✅ UPDATE: Set account matching status to processing
            $this->bankStatement->update([
                'account_matching_status' => 'processing',
                'account_matching_started_at' => now(),
            ]);

            $query = StatementTransaction::where('bank_statement_id', $this->bankStatement->id)
                ->where('company_id', $this->bankStatement->company_id);

            if (!$this->forceRematch) {
                $query->whereNull('account_id')
                      ->where('is_manual_account', false);
            } else {
                $query->where('is_manual_account', false);
            }

            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                Log::info("No transactions to match for account matching (Bank Statement ID: {$this->bankStatement->id})");
                
                $this->bankStatement->update([
                    'account_matching_status' => 'completed',
                    'account_matching_completed_at' => now(),
                ]);
                
                return;
            }

            // ✅ Get account keywords from ACCOUNT KEYWORD SEEDER
            $accountKeywords = $this->getActiveAccountKeywords($this->bankStatement->company_id);

            if ($accountKeywords->isEmpty()) {
                Log::warning("No active account keywords found for matching", [
                    'company_id' => $this->bankStatement->company_id
                ]);
                
                $this->bankStatement->update([
                    'account_matching_status' => 'skipped',
                    'account_matching_notes' => 'No active account keywords available',
                    'account_matching_completed_at' => now(),
                ]);
                
                return;
            }

            $matchedCount = 0;
            $unmatchedCount = 0;

            DB::beginTransaction();

            foreach ($transactions as $transaction) {
                $matchingStartTime = microtime(true);
                
                $allMatches = $this->findAllAccountMatches($transaction, $accountKeywords);
                
                $matchingDuration = round((microtime(true) - $matchingStartTime) * 1000);

                if (empty($allMatches)) {
                    $this->handleNoAccountMatch($transaction, $matchingDuration);
                    $unmatchedCount++;
                } else {
                    $result = $this->processAccountMatches($transaction, $allMatches, $matchingDuration);
                    
                    if ($result['success']) {
                        $matchedCount++;
                    }
                }
            }

            // ✅ UPDATE: Set account matching status to completed
            $this->bankStatement->update([
                'account_matching_status' => 'completed',
                'account_matching_completed_at' => now(),
            ]);

            DB::commit();

            $totalDuration = round((microtime(true) - $startTime) * 1000);

            Log::info("✅ [ACCOUNT MATCHING] Completed for Bank Statement ID: {$this->bankStatement->id}", [
                'matched' => $matchedCount,
                'unmatched' => $unmatchedCount,
                'duration_ms' => $totalDuration,
                'avg_per_transaction_ms' => round($totalDuration / $transactions->count()),
            ]);

            // ✅ FIRE EVENT (optional untuk future use)
            event(new \App\Events\AccountMatchingCompleted(
                $this->bankStatement->fresh(),
                $matchedCount,
                $unmatchedCount
            ));

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ UPDATE: Set account matching status to failed
            $this->bankStatement->update([
                'account_matching_status' => 'failed',
                'account_matching_notes' => $e->getMessage(),
                'account_matching_completed_at' => now(),
            ]);

            Log::error("❌ [ACCOUNT MATCHING] Failed for Bank Statement ID: {$this->bankStatement->id}", [
                'company_id' => $this->bankStatement->company_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function getActiveAccountKeywords(int $companyId)
    {
        $cacheKey = "account_keywords_active_company_{$companyId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            // ✅ PENTING: Load relasi account untuk dapat account details
            return AccountKeyword::with(['account'])
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    private function findAllAccountMatches(StatementTransaction $transaction, $accountKeywords): array
    {
        $description = $this->normalizeDescription($transaction->description ?? '');
        $allMatches = [];

        foreach ($accountKeywords as $accountKeyword) {
            $matchResult = $this->matchAccountKeyword($description, $accountKeyword, $transaction);
            
            if ($matchResult && $matchResult['score'] >= self::MIN_CONFIDENCE_THRESHOLD) {
                $allMatches[] = $matchResult;
            }
        }

        usort($allMatches, function($a, $b) {
            return $b['weighted_score'] <=> $a['weighted_score'];
        });

        return $allMatches;
    }

    private function matchAccountKeyword(string $description, AccountKeyword $accountKeyword, StatementTransaction $transaction): ?array
    {
        $keywordText = $accountKeyword->case_sensitive 
            ? $accountKeyword->keyword 
            : strtolower($accountKeyword->keyword);
        
        $searchText = $accountKeyword->case_sensitive 
            ? $transaction->description 
            : $description;

        $score = 0;
        $method = '';
        $matchedText = '';

        if ($accountKeyword->is_regex) {
            try {
                if (preg_match('/' . $accountKeyword->keyword . '/u', $searchText, $matches)) {
                    $score = 85;
                    $method = 'regex';
                    $matchedText = $matches[0] ?? $keywordText;
                }
            } catch (\Exception $e) {
                Log::warning("Invalid regex pattern in account keyword", [
                    'account_keyword_id' => $accountKeyword->id,
                    'pattern' => $accountKeyword->keyword,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        elseif ($searchText === $keywordText) {
            $score = 100;
            $method = 'exact_match';
            $matchedText = $keywordText;
        }
        elseif (strpos($searchText, $keywordText) !== false) {
            $score = 95;
            $method = 'contains';
            $matchedText = $keywordText;
            
            if (strpos($searchText, $keywordText) === 0) {
                $score = 98;
                $method = 'starts_with';
            }
        }
        elseif (preg_match('/\b' . preg_quote($keywordText, '/') . '\b/ui', $searchText)) {
            $score = 90;
            $method = 'word_boundary';
            $matchedText = $keywordText;
        }
        elseif ($this->containsPartialMatch($searchText, $keywordText)) {
            $score = 80;
            $method = 'partial_word';
            $matchedText = $keywordText;
        }
        else {
            similar_text($keywordText, $searchText, $percent);
            if ($percent >= 70) {
                $score = (int) $percent;
                $method = 'similarity';
                $matchedText = $keywordText;
            }
        }

        if ($score === 0) {
            return null;
        }

        $priorityMultiplier = $accountKeyword->priority / 10;
        $weightedScore = (int) ($score * $priorityMultiplier);

        return [
            'account_keyword' => $accountKeyword,
            'account_id' => $accountKeyword->account_id,
            'score' => $score,
            'raw_score' => $score,
            'weighted_score' => $weightedScore,
            'method' => $method,
            'matched_text' => $matchedText,
            'source' => 'auto_match',
        ];
    }

    private function processAccountMatches(StatementTransaction $transaction, array $allMatches, int $matchingDuration): array
    {
        $topMatches = array_slice($allMatches, 0, self::MAX_SUGGESTIONS);
        $primaryMatch = $topMatches[0];

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

        // ✅ UPDATE: Isi account_id dari ACCOUNT KEYWORD SEEDER
        $transaction->update([
            'account_id' => $primaryMatch['account_id'],
            'matched_account_keyword_id' => $primaryMatch['account_keyword']->id,
            'account_confidence_score' => $primaryMatch['score'],
            'match_metadata' => array_merge($transaction->match_metadata ?? [], [
                'account_suggestions' => $accountSuggestions,
                'account_matched_at' => now()->toIso8601String(),
            ]),
            'is_manual_account' => false,
        ]);

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
            'match_reason' => "Automatic match via account keyword: {$primaryMatch['account_keyword']->keyword}",
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

        $primaryMatch['account_keyword']->increment('match_count');
        $primaryMatch['account_keyword']->update(['last_matched_at' => now()]);

        return [
            'success' => true,
            'primary_score' => $primaryMatch['score'],
            'suggestions_count' => count($suggestions),
        ];
    }

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

    private function normalizeDescription(string $description): string
    {
        $normalized = strtolower($description);
        $normalized = preg_replace('/[^a-z0-9\s]/u', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

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

    public function failed(\Throwable $exception): void
    {
        Log::error("❌ [ACCOUNT MATCHING] Job failed permanently for Bank Statement ID: {$this->bankStatement->id}", [
            'company_id' => $this->bankStatement->company_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        $this->bankStatement->update([
            'account_matching_status' => 'failed',
            'account_matching_notes' => $exception->getMessage(),
            'account_matching_completed_at' => now(),
        ]);
    }
}