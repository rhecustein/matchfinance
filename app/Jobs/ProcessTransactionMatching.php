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
use Illuminate\Support\Facades\Cache;

class ProcessTransactionMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    private const MIN_CONFIDENCE_THRESHOLD = 50;
    private const PRIMARY_CONFIDENCE_THRESHOLD = 70;
    private const MAX_SUGGESTIONS = 5;
    private const CACHE_TTL = 3600;

    public function __construct(
        public BankStatement $bankStatement
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("🔍 [TRANSACTION MATCHING] Starting for Bank Statement ID: {$this->bankStatement->id}", [
                'company_id' => $this->bankStatement->company_id,
                'total_transactions' => $this->bankStatement->total_transactions,
            ]);

            // ✅ UPDATE: Set matching status to processing
            $this->bankStatement->update([
                'matching_status' => 'processing',
                'matching_started_at' => now(),
            ]);

            $transactions = StatementTransaction::where('bank_statement_id', $this->bankStatement->id)
                ->where('company_id', $this->bankStatement->company_id)
                ->whereNull('matched_keyword_id')
                ->get();

            if ($transactions->isEmpty()) {
                Log::info("No unmatched transactions found for Bank Statement ID: {$this->bankStatement->id}");
                
                $this->bankStatement->update([
                    'matching_status' => 'completed',
                    'matching_completed_at' => now(),
                ]);
                
                return;
            }

            // ✅ Get keywords from KEYWORD SEEDER (sub_categories relation)
            $keywords = $this->getActiveKeywords($this->bankStatement->company_id);

            if ($keywords->isEmpty()) {
                Log::warning("No active keywords found for matching", [
                    'company_id' => $this->bankStatement->company_id
                ]);
                
                $this->bankStatement->update([
                    'matching_status' => 'skipped',
                    'matching_notes' => 'No active keywords available',
                    'matching_completed_at' => now(),
                ]);
                
                return;
            }

            $matchedCount = 0;
            $unmatchedCount = 0;
            $lowConfidenceCount = 0;

            DB::beginTransaction();

            foreach ($transactions as $transaction) {
                $matchingStartTime = microtime(true);
                
                $allMatches = $this->findAllMatches($transaction, $keywords);
                
                $matchingDuration = round((microtime(true) - $matchingStartTime) * 1000);

                if (empty($allMatches)) {
                    $this->handleNoMatch($transaction, $matchingDuration);
                    $unmatchedCount++;
                } else {
                    $result = $this->processMatches($transaction, $allMatches, $matchingDuration);
                    
                    if ($result['primary_score'] >= self::PRIMARY_CONFIDENCE_THRESHOLD) {
                        $matchedCount++;
                    } else {
                        $lowConfidenceCount++;
                    }
                }
            }

            // ✅ UPDATE: Set matching status to completed
            $this->bankStatement->update([
                'matched_transactions' => $matchedCount,
                'unmatched_transactions' => $unmatchedCount,
                'low_confidence_transactions' => $lowConfidenceCount,
                'matching_status' => 'completed',
                'matching_completed_at' => now(),
            ]);

            DB::commit();

            $totalDuration = round((microtime(true) - $startTime) * 1000);

            Log::info("✅ [TRANSACTION MATCHING] Completed for Bank Statement ID: {$this->bankStatement->id}", [
                'matched' => $matchedCount,
                'unmatched' => $unmatchedCount,
                'low_confidence' => $lowConfidenceCount,
                'duration_ms' => $totalDuration,
                'avg_per_transaction_ms' => round($totalDuration / $transactions->count()),
            ]);

            // ✅ FIRE EVENT - This triggers ACCOUNT MATCHING
            event(new \App\Events\TransactionMatchingCompleted(
                $this->bankStatement->fresh(),
                $matchedCount,
                $unmatchedCount
            ));

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ UPDATE: Set matching status to failed
            $this->bankStatement->update([
                'matching_status' => 'failed',
                'matching_notes' => $e->getMessage(),
                'matching_completed_at' => now(),
            ]);

            Log::error("❌ [TRANSACTION MATCHING] Failed for Bank Statement ID: {$this->bankStatement->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function getActiveKeywords(int $companyId)
    {
        $cacheKey = "keywords_active_company_{$companyId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            // ✅ PENTING: Load relasi lengkap (type → category → subCategory)
            return Keyword::with(['subCategory.category.type'])
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    private function findAllMatches(StatementTransaction $transaction, $keywords): array
    {
        $description = $this->normalizeDescription($transaction->description ?? '');
        $allMatches = [];

        foreach ($keywords as $keyword) {
            $matchResult = $this->matchKeyword($description, $keyword, $transaction);
            
            if ($matchResult && $matchResult['score'] >= self::MIN_CONFIDENCE_THRESHOLD) {
                $allMatches[] = $matchResult;
            }
        }

        usort($allMatches, function($a, $b) {
            return $b['weighted_score'] <=> $a['weighted_score'];
        });

        return $allMatches;
    }

    private function matchKeyword(string $description, Keyword $keyword, StatementTransaction $transaction): ?array
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

        if ($keyword->is_regex) {
            try {
                if (preg_match('/' . $keyword->keyword . '/u', $searchText, $matches)) {
                    $score = 85;
                    $method = 'regex';
                    $matchedText = $matches[0] ?? $keywordText;
                }
            } catch (\Exception $e) {
                Log::warning("Invalid regex pattern", [
                    'keyword_id' => $keyword->id,
                    'pattern' => $keyword->keyword,
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

        $priorityMultiplier = $keyword->priority / 10;
        $weightedScore = (int) ($score * $priorityMultiplier);
        $finalScore = $this->calculateFinalConfidence($score, $keyword, $transaction, $method);

        return [
            'keyword' => $keyword,
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
            ]
        ];
    }

    private function calculateFinalConfidence(int $baseScore, Keyword $keyword, StatementTransaction $transaction, string $method): int
    {
        $score = $baseScore;

        if ($keyword->match_count > 10) {
            $score += 2;
        }

        if ($transaction->transaction_type === 'debit' && $keyword->subCategory->category->type->name === 'Outlet') {
            $score += 1;
        }

        if ($method === 'regex') {
            $score -= 3;
        }

        if ($method === 'similarity') {
            $score -= 5;
        }

        return max(0, min(100, $score));
    }

    private function processMatches(StatementTransaction $transaction, array $allMatches, int $matchingDuration): array
    {
        $topMatches = array_slice($allMatches, 0, self::MAX_SUGGESTIONS);
        $primaryMatch = $topMatches[0];

        $suggestions = [];
        foreach ($topMatches as $index => $match) {
            $suggestions[] = [
                'rank' => $index + 1,
                'keyword_id' => $match['keyword']->id,
                'keyword' => $match['keyword']->keyword,
                'sub_category_id' => $match['keyword']->sub_category_id,
                'sub_category_name' => $match['keyword']->subCategory->name,
                'category_id' => $match['keyword']->subCategory->category_id,
                'category_name' => $match['keyword']->subCategory->category->name,
                'type_id' => $match['keyword']->subCategory->category->type_id,
                'type_name' => $match['keyword']->subCategory->category->type->name,
                'confidence_score' => $match['score'],
                'raw_score' => $match['raw_score'],
                'weighted_score' => $match['weighted_score'],
                'matched_text' => $match['matched_text'],
                'match_method' => $match['method'],
                'source' => $match['source'],
            ];
        }

        $alternativeCategories = [
            'suggestions' => $suggestions,
            'generation_timestamp' => now()->toIso8601String(),
            'total_suggestions' => count($suggestions),
            'total_candidates' => count($allMatches),
            'primary_suggestion' => [
                'keyword_id' => $primaryMatch['keyword']->id,
                'confidence_score' => $primaryMatch['score'],
                'method' => $primaryMatch['method'],
            ],
            'matching_stats' => [
                'duration_ms' => $matchingDuration,
                'keywords_evaluated' => count($allMatches),
            ]
        ];

        $extractedKeywords = $this->extractKeywordsFromDescription($transaction->description);

        // ✅ UPDATE: Isi type_id, category_id, sub_category_id dari KEYWORD SEEDER
        $transaction->update([
            'matched_keyword_id' => $primaryMatch['keyword']->id,
            'confidence_score' => $primaryMatch['score'],
            'type_id' => $primaryMatch['keyword']->subCategory->category->type_id,
            'category_id' => $primaryMatch['keyword']->subCategory->category_id,
            'sub_category_id' => $primaryMatch['keyword']->sub_category_id,
            'alternative_categories' => $alternativeCategories,
            'match_method' => $primaryMatch['method'],
            'match_metadata' => [
                'keyword_text' => $primaryMatch['keyword']->keyword,
                'matched_text' => $primaryMatch['matched_text'],
                'raw_score' => $primaryMatch['raw_score'],
                'weighted_score' => $primaryMatch['weighted_score'],
                'priority' => $primaryMatch['keyword']->priority,
                'matched_at' => now()->toIso8601String(),
                'description' => $transaction->description,
                'total_alternatives' => count($suggestions) - 1,
            ],
            'extracted_keywords' => $extractedKeywords,
            'normalized_description' => $this->normalizeDescription($transaction->description),
            'matching_duration_ms' => $matchingDuration,
            'matching_attempts' => 1,
            'feedback_status' => 'pending',
            'is_manual_category' => false,
            'is_approved' => false,
            'is_rejected' => false,
        ]);

        MatchingLog::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'company_id' => $transaction->company_id,
            'statement_transaction_id' => $transaction->id,
            'keyword_id' => $primaryMatch['keyword']->id,
            'matched_text' => $primaryMatch['matched_text'],
            'confidence_score' => $primaryMatch['score'],
            'match_metadata' => [
                'method' => $primaryMatch['method'],
                'keyword_text' => $primaryMatch['keyword']->keyword,
                'description' => $transaction->description,
                'raw_score' => $primaryMatch['raw_score'],
                'weighted_score' => $primaryMatch['weighted_score'],
                'alternatives_count' => count($suggestions) - 1,
            ],
            'matched_at' => now(),
        ]);

        $primaryMatch['keyword']->increment('match_count');
        $primaryMatch['keyword']->update(['last_matched_at' => now()]);

        return [
            'success' => true,
            'primary_score' => $primaryMatch['score'],
            'suggestions_count' => count($suggestions),
        ];
    }

    private function handleNoMatch(StatementTransaction $transaction, int $matchingDuration): void
    {
        $transaction->update([
            'matched_keyword_id' => null,
            'confidence_score' => 0,
            'type_id' => null,
            'category_id' => null,
            'sub_category_id' => null,
            'alternative_categories' => [
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
            'match_method' => 'no_match',
            'match_metadata' => [
                'reason' => 'No keywords matched the transaction description',
                'description' => $transaction->description,
            ],
            'extracted_keywords' => $this->extractKeywordsFromDescription($transaction->description),
            'normalized_description' => $this->normalizeDescription($transaction->description),
            'matching_duration_ms' => $matchingDuration,
            'matching_attempts' => 1,
            'feedback_status' => 'pending',
            'is_manual_category' => false,
            'is_approved' => false,
            'is_rejected' => false,
        ]);
    }

    private function normalizeDescription(string $description): string
    {
        $normalized = strtolower($description);
        $normalized = preg_replace('/[^a-z0-9\s]/u', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    private function extractKeywordsFromDescription(string $description): array
    {
        $normalized = $this->normalizeDescription($description);
        $words = explode(' ', $normalized);
        $commonWords = ['dari', 'ke', 'untuk', 'pada', 'di', 'dan', 'atau', 'yang', 'by', 'from', 'to', 'the', 'a', 'an', 'in', 'on', 'at'];
        
        $keywords = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 2 && !in_array($word, $commonWords);
        });
        
        return array_values(array_unique(array_slice($keywords, 0, 10)));
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
        Log::error("❌ [TRANSACTION MATCHING] Job failed permanently for Bank Statement ID: {$this->bankStatement->id}", [
            'company_id' => $this->bankStatement->company_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->bankStatement->update([
            'matching_status' => 'failed',
            'matching_notes' => $exception->getMessage(),
            'matching_completed_at' => now(),
        ]);
    }
}