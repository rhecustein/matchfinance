<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\StatementTransaction;
use App\Models\MatchingLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TransactionMatchingService
{
    private array $keywords;
    
    public function __construct()
    {
        $this->loadKeywords();
    }

    /**
     * Load all active keywords with relations (cached for performance)
     */
    private function loadKeywords(): void
    {
        $this->keywords = Cache::remember('active_keywords', 3600, function () {
            return Keyword::active()
                ->with(['subCategory.category.type'])
                ->byPriority()
                ->get()
                ->toArray();
        });
    }

    /**
     * Match a single transaction description
     */
    public function matchTransaction(string $description): ?array
    {
        $bestMatch = null;
        $highestScore = 0;

        foreach ($this->keywords as $keyword) {
            $result = $this->matchKeyword($description, $keyword);
            
            if ($result && $result['score'] > $highestScore) {
                $highestScore = $result['score'];
                $bestMatch = $result;
            }
        }

        return $bestMatch;
    }

    /**
     * Match keyword against description
     */
    private function matchKeyword(string $description, array $keyword): ?array
    {
        $keywordText = $keyword['keyword'];
        $isRegex = $keyword['is_regex'];
        $caseSensitive = $keyword['case_sensitive'];
        $priority = $keyword['priority'];

        $matched = false;
        $matchedText = '';

        if ($isRegex) {
            // Regex matching
            try {
                $pattern = $caseSensitive ? $keywordText : $keywordText . 'i';
                if (preg_match('/' . $pattern . '/', $description, $matches)) {
                    $matched = true;
                    $matchedText = $matches[0];
                }
            } catch (\Exception $e) {
                // Invalid regex, skip
                return null;
            }
        } else {
            // Simple string matching
            $descToMatch = $caseSensitive ? $description : strtoupper($description);
            $keywordToMatch = $caseSensitive ? $keywordText : strtoupper($keywordText);

            if (strpos($descToMatch, $keywordToMatch) !== false) {
                $matched = true;
                $matchedText = $keywordText;
            }
        }

        if (!$matched) {
            return null;
        }

        // Calculate confidence score
        $score = $this->calculateConfidenceScore(
            $description,
            $matchedText,
            $priority,
            $isRegex
        );

        return [
            'keyword_id' => $keyword['id'],
            'sub_category_id' => $keyword['sub_category_id'],
            'category_id' => $keyword['sub_category']['category_id'],
            'type_id' => $keyword['sub_category']['category']['type_id'],
            'matched_text' => $matchedText,
            'score' => $score,
            'metadata' => [
                'keyword' => $keywordText,
                'priority' => $priority,
                'is_regex' => $isRegex,
            ]
        ];
    }

    /**
     * Calculate confidence score (0-100)
     */
    private function calculateConfidenceScore(
        string $description,
        string $matchedText,
        int $priority,
        bool $isRegex
    ): int {
        $score = 0;

        // Base score from priority (1-10 = 40-100 points)
        $score += ($priority * 6) + 40;

        // Exact match bonus
        if (strtoupper(trim($description)) === strtoupper(trim($matchedText))) {
            $score = 100;
            return $score;
        }

        // Match length ratio bonus (max 10 points)
        $matchRatio = strlen($matchedText) / strlen($description);
        $score += (int)($matchRatio * 10);

        // Regex match penalty (regex less precise)
        if ($isRegex) {
            $score -= 5;
        }

        // Ensure score is within 0-100
        return max(0, min(100, $score));
    }

    /**
     * Process all transactions for a bank statement
     */
    public function processStatementTransactions(int $bankStatementId): array
    {
        $transactions = StatementTransaction::where('bank_statement_id', $bankStatementId)
            ->whereNull('matched_keyword_id')
            ->get();

        $stats = [
            'total' => $transactions->count(),
            'matched' => 0,
            'unmatched' => 0,
            'high_confidence' => 0,
            'low_confidence' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                $match = $this->matchTransaction($transaction->description);

                if ($match) {
                    // Update transaction
                    $transaction->update([
                        'matched_keyword_id' => $match['keyword_id'],
                        'sub_category_id' => $match['sub_category_id'],
                        'category_id' => $match['category_id'],
                        'type_id' => $match['type_id'],
                        'confidence_score' => $match['score'],
                    ]);

                    // Create matching log
                    MatchingLog::create([
                        'statement_transaction_id' => $transaction->id,
                        'keyword_id' => $match['keyword_id'],
                        'matched_text' => $match['matched_text'],
                        'confidence_score' => $match['score'],
                        'match_metadata' => $match['metadata'],
                        'matched_at' => now(),
                    ]);

                    $stats['matched']++;
                    if ($match['score'] >= 80) {
                        $stats['high_confidence']++;
                    } else {
                        $stats['low_confidence']++;
                    }
                } else {
                    $stats['unmatched']++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $stats;
    }

    /**
     * Re-match a specific transaction
     */
    public function rematchTransaction(StatementTransaction $transaction): bool
    {
        $match = $this->matchTransaction($transaction->description);

        if ($match) {
            DB::beginTransaction();
            try {
                $transaction->update([
                    'matched_keyword_id' => $match['keyword_id'],
                    'sub_category_id' => $match['sub_category_id'],
                    'category_id' => $match['category_id'],
                    'type_id' => $match['type_id'],
                    'confidence_score' => $match['score'],
                    'is_verified' => false,
                ]);

                MatchingLog::create([
                    'statement_transaction_id' => $transaction->id,
                    'keyword_id' => $match['keyword_id'],
                    'matched_text' => $match['matched_text'],
                    'confidence_score' => $match['score'],
                    'match_metadata' => $match['metadata'],
                    'matched_at' => now(),
                ]);

                DB::commit();
                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return false;
    }

    /**
     * Clear keywords cache (call after updating keywords)
     */
    public static function clearCache(): void
    {
        Cache::forget('active_keywords');
    }
}