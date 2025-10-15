<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountKeyword;
use App\Models\AccountMatchingLog;
use App\Models\StatementTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountMatchingService
{
    protected Collection $activeKeywords;

    public function __construct()
    {
        $this->activeKeywords = collect();
    }

    /**
     * Match single transaction ke Account
     */
    public function matchTransaction(StatementTransaction $transaction, bool $forceRematch = false): ?array
    {
        // Skip jika sudah ada manual account assignment (kecuali force rematch)
        if (!$forceRematch && $transaction->is_manual_account) {
            return null;
        }

        $searchText = $this->prepareSearchText($transaction);
        
        // Load active keywords dengan cache
        $this->loadActiveKeywords();

        $bestMatch = null;
        $highestScore = 0;
        $matchLogs = [];

        // Loop semua account keywords berdasarkan priority
        foreach ($this->activeKeywords as $keyword) {
            $matchResult = $this->checkKeywordMatch($keyword, $searchText);
            
            // Log untuk tracking
            $matchLogs[] = [
                'account_id' => $keyword->account_id,
                'account_keyword_id' => $keyword->id,
                'confidence_score' => $matchResult['score'],
                'is_matched' => $matchResult['matched'],
                'match_reason' => $matchResult['reason'],
                'match_details' => $matchResult['details'],
            ];

            // Update best match jika score lebih tinggi
            if ($matchResult['matched'] && $matchResult['score'] > $highestScore) {
                $highestScore = $matchResult['score'];
                $bestMatch = [
                    'account_id' => $keyword->account_id,
                    'keyword_id' => $keyword->id,
                    'score' => $matchResult['score'],
                    'reason' => $matchResult['reason'],
                    'details' => $matchResult['details'],
                ];
            }
        }

        // Save logs
        $this->saveMatchingLogs($transaction->id, $matchLogs);

        return $bestMatch;
    }

    /**
     * Assign account ke transaction
     */
    public function assignAccountToTransaction(
        StatementTransaction $transaction,
        ?int $accountId,
        ?int $keywordId = null,
        int $confidenceScore = 0,
        bool $isManual = false,
        ?string $reason = null
    ): bool {
        try {
            $transaction->update([
                'account_id' => $accountId,
                'matched_account_keyword_id' => $keywordId,
                'account_confidence_score' => $confidenceScore,
                'is_manual_account' => $isManual,
            ]);

            // Update match count untuk account dan keyword
            if ($accountId) {
                Account::find($accountId)?->incrementMatchCount();
                
                if ($keywordId) {
                    AccountKeyword::find($keywordId)?->incrementMatchCount();
                }
            }

            Log::info('Account assigned to transaction', [
                'transaction_id' => $transaction->id,
                'account_id' => $accountId,
                'confidence_score' => $confidenceScore,
                'is_manual' => $isManual,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to assign account', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process batch transactions untuk account matching
     */
    public function processBatchTransactions(array $transactionIds, bool $forceRematch = false): array
    {
        $results = [
            'total' => count($transactionIds),
            'matched' => 0,
            'unmatched' => 0,
            'errors' => 0,
        ];

        foreach ($transactionIds as $transactionId) {
            try {
                $transaction = StatementTransaction::find($transactionId);
                
                if (!$transaction) {
                    $results['errors']++;
                    continue;
                }

                $match = $this->matchTransaction($transaction, $forceRematch);

                if ($match) {
                    $this->assignAccountToTransaction(
                        $transaction,
                        $match['account_id'],
                        $match['keyword_id'],
                        $match['score'],
                        false,
                        $match['reason']
                    );
                    $results['matched']++;
                } else {
                    $results['unmatched']++;
                }
            } catch (\Exception $e) {
                Log::error('Error processing transaction for account', [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage()
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Rematch single transaction
     */
    public function rematchTransaction(StatementTransaction $transaction): ?array
    {
        // Clear existing account assignment
        $transaction->update([
            'account_id' => null,
            'matched_account_keyword_id' => null,
            'account_confidence_score' => null,
            'is_manual_account' => false,
        ]);

        // Perform new matching
        $match = $this->matchTransaction($transaction, true);

        if ($match) {
            $this->assignAccountToTransaction(
                $transaction,
                $match['account_id'],
                $match['keyword_id'],
                $match['score'],
                false,
                $match['reason']
            );
        }

        return $match;
    }

    /**
     * Load active keywords dengan caching
     * 
     * ⚠️ FIX: Jangan convert ke array, pakai Collection
     */
    protected function loadActiveKeywords(): void
    {
        if ($this->activeKeywords->isNotEmpty()) {
            return;
        }

        // Cache selama 1 jam, return Collection instead of array
        $this->activeKeywords = Cache::remember('active_account_keywords', 3600, function () {
            return AccountKeyword::with('account')
                ->whereHas('account', function ($query) {
                    $query->where('is_active', true);
                })
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('account_id')
                ->get(); // ✅ Jangan ->toArray()
        });
    }

    /**
     * Clear cache keywords (panggil saat ada update keywords)
     */
    public function clearKeywordsCache(): void
    {
        Cache::forget('active_account_keywords');
        $this->activeKeywords = collect();
    }

    /**
     * Prepare text untuk matching (description + reference)
     */
    protected function prepareSearchText(StatementTransaction $transaction): string
    {
        return implode(' ', array_filter([
            $transaction->description,
            $transaction->reference_no,
        ]));
    }

    /**
     * Check apakah keyword match dengan text
     * 
     * @param AccountKeyword $keyword ✅ Type hint tetap AccountKeyword
     */
    protected function checkKeywordMatch(AccountKeyword $keyword, string $text): array
    {
        $matched = $keyword->matches($text);
        $score = 0;
        $reason = '';
        $details = [];

        if ($matched) {
            // Calculate confidence score berdasarkan match type dan priority
            $score = $this->calculateConfidenceScore($keyword, $text);
            $reason = "Matched with '{$keyword->keyword}' ({$keyword->match_type})";
            $details = [
                'keyword' => $keyword->keyword,
                'match_type' => $keyword->match_type,
                'priority' => $keyword->priority,
                'account_name' => $keyword->account->name ?? 'Unknown',
            ];
        }

        return [
            'matched' => $matched,
            'score' => $score,
            'reason' => $reason,
            'details' => $details,
        ];
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidenceScore(AccountKeyword $keyword, string $text): int
    {
        $baseScore = 50;

        // Bonus untuk priority tinggi (max +30)
        $priorityBonus = ($keyword->priority / 10) * 30;

        // Bonus untuk match type yang lebih spesifik
        $matchTypeBonus = match($keyword->match_type) {
            'exact' => 20,
            'regex' => 15,
            'starts_with', 'ends_with' => 10,
            'contains' => 5,
            default => 0,
        };

        // Bonus untuk case sensitive
        $caseSensitiveBonus = $keyword->case_sensitive ? 5 : 0;

        // Total score (max 100)
        $totalScore = min(100, $baseScore + $priorityBonus + $matchTypeBonus + $caseSensitiveBonus);

        return (int) $totalScore;
    }

    /**
     * Save matching logs
     */
    protected function saveMatchingLogs(int $transactionId, array $logs): void
    {
        try {
            foreach ($logs as $log) {
                AccountMatchingLog::create([
                    'statement_transaction_id' => $transactionId,
                    'account_id' => $log['account_id'],
                    'account_keyword_id' => $log['account_keyword_id'],
                    'confidence_score' => $log['confidence_score'],
                    'is_matched' => $log['is_matched'],
                    'match_reason' => $log['match_reason'],
                    'match_details' => $log['match_details'],
                    'matched_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to save matching logs', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}