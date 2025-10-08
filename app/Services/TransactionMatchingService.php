<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\StatementTransaction;
use App\Models\MatchingLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionMatchingService
{
    /**
     * Match a transaction description against keywords
     * IMPROVED VERSION dengan fuzzy matching dan better scoring
     */
    public function matchTransaction(string $description): ?array
    {
        if (empty(trim($description))) {
            return null;
        }

        // Normalize description
        $normalizedDesc = $this->normalizeText($description);

        // Get all active keywords (cached)
        $keywords = Cache::remember('active_keywords_with_relations', 3600, function () {
            return Keyword::with('subCategory.category.type')
                ->active()
                ->orderByDesc('priority')
                ->get()
                ->toArray();
        });

        $bestMatch = null;
        $highestScore = 0;

        foreach ($keywords as $keyword) {
            $match = $this->checkKeywordMatch($normalizedDesc, $description, $keyword);
            
            if ($match && $match['score'] > $highestScore) {
                $highestScore = $match['score'];
                $bestMatch = $match;
            }

            // Early exit for perfect matches
            if ($highestScore >= 100) {
                break;
            }
        }

        return $bestMatch;
    }

    /**
     * Normalize text for better matching
     */
    private function normalizeText(string $text): string
    {
        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters but keep alphanumeric and spaces
        $text = preg_replace('/[^A-Za-z0-9\s]/', '', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }

    /**
     * Check if keyword matches description
     * IMPROVED dengan fuzzy matching
     */
    private function checkKeywordMatch(string $normalizedDesc, string $originalDesc, array $keyword): ?array
    {
        $keywordText = $keyword['keyword'];
        $priority = $keyword['priority'];
        $isRegex = $keyword['is_regex'];
        $caseSensitive = $keyword['case_sensitive'];

        $matched = false;
        $matchedText = '';
        $matchType = 'none';

        if ($isRegex) {
            // Regex matching
            try {
                $pattern = $caseSensitive ? $keywordText : $keywordText . 'i';
                if (preg_match('/' . $pattern . '/', $originalDesc, $matches)) {
                    $matched = true;
                    $matchedText = $matches[0];
                    $matchType = 'regex';
                }
            } catch (\Exception $e) {
                return null;
            }
        } else {
            // Multi-level matching: exact → contains → fuzzy
            
            // Level 1: Exact match (case-insensitive by default)
            $descForMatch = $caseSensitive ? $originalDesc : strtoupper($originalDesc);
            $keywordForMatch = $caseSensitive ? $keywordText : strtoupper($keywordText);
            
            if ($descForMatch === $keywordForMatch) {
                $matched = true;
                $matchedText = $keywordText;
                $matchType = 'exact';
            }
            
            // Level 2: Contains match
            elseif (strpos($descForMatch, $keywordForMatch) !== false) {
                $matched = true;
                $matchedText = $keywordText;
                $matchType = 'contains';
            }
            
            // Level 3: Fuzzy match (Levenshtein distance)
            else {
                $similarity = $this->calculateSimilarity($normalizedDesc, $this->normalizeText($keywordText));
                
                // Threshold: 80% similarity
                if ($similarity >= 80) {
                    $matched = true;
                    $matchedText = $keywordText;
                    $matchType = 'fuzzy';
                }
            }
        }

        if (!$matched) {
            return null;
        }

        // Calculate confidence score dengan improved algorithm
        $score = $this->calculateConfidenceScore(
            $originalDesc,
            $normalizedDesc,
            $matchedText,
            $priority,
            $matchType,
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
                'match_type' => $matchType,
            ]
        ];
    }

    /**
     * Calculate text similarity (0-100)
     * Using combined Levenshtein + character ratio
     */
    private function calculateSimilarity(string $text1, string $text2): float
    {
        $text1 = strtoupper($text1);
        $text2 = strtoupper($text2);

        // Levenshtein distance (with length limit for performance)
        $maxLen = 255;
        if (strlen($text1) > $maxLen) $text1 = substr($text1, 0, $maxLen);
        if (strlen($text2) > $maxLen) $text2 = substr($text2, 0, $maxLen);

        $lev = levenshtein($text1, $text2);
        $maxLength = max(strlen($text1), strlen($text2));
        
        if ($maxLength === 0) return 100;
        
        $levSimilarity = (1 - ($lev / $maxLength)) * 100;

        // Character overlap ratio
        $intersection = count(array_intersect(str_split($text1), str_split($text2)));
        $union = count(array_unique(array_merge(str_split($text1), str_split($text2))));
        
        $charSimilarity = $union > 0 ? ($intersection / $union) * 100 : 0;

        // Weighted average: 70% Levenshtein, 30% Character
        $similarity = ($levSimilarity * 0.7) + ($charSimilarity * 0.3);

        return round($similarity, 2);
    }

    /**
     * Calculate confidence score (0-100)
     * IMPROVED dengan match type consideration
     */
    private function calculateConfidenceScore(
        string $originalDesc,
        string $normalizedDesc,
        string $matchedText,
        int $priority,
        string $matchType,
        bool $isRegex
    ): int {
        $score = 0;

        // Base score from match type
        switch ($matchType) {
            case 'exact':
                $score = 100; // Perfect match
                return $score;
                
            case 'contains':
                // Priority-based: 70-95
                $score = 70 + ($priority * 2.5);
                break;
                
            case 'fuzzy':
                // Fuzzy match: 60-85
                $score = 60 + ($priority * 2.5);
                break;
                
            case 'regex':
                // Regex: 55-85
                $score = 55 + ($priority * 3);
                break;
                
            default:
                $score = 40 + ($priority * 6);
        }

        // Bonus: Match length ratio (semakin panjang match, semakin yakin)
        $matchRatio = strlen($matchedText) / strlen($originalDesc);
        $lengthBonus = min(10, (int)($matchRatio * 15));
        $score += $lengthBonus;

        // Bonus: Keyword di awal description (lebih relevan)
        if (stripos($originalDesc, $matchedText) === 0) {
            $score += 5;
        }

        // Penalty: Regex kurang precise
        if ($isRegex) {
            $score -= 10;
        }

        // Penalty: Description terlalu panjang vs keyword pendek (possible false positive)
        if (strlen($originalDesc) > 50 && strlen($matchedText) < 10) {
            $score -= 5;
        }

        // Ensure 0-100 range
        return max(0, min(100, (int)$score));
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
            'medium_confidence' => 0,
            'low_confidence' => 0,
            'exact_matches' => 0,
            'fuzzy_matches' => 0,
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

                    // Update keyword match count
                    Keyword::where('id', $match['keyword_id'])->increment('match_count');

                    $stats['matched']++;
                    
                    // Categorize by confidence
                    if ($match['score'] >= 90) {
                        $stats['high_confidence']++;
                    } elseif ($match['score'] >= 70) {
                        $stats['medium_confidence']++;
                    } else {
                        $stats['low_confidence']++;
                    }

                    // Track match types
                    if ($match['metadata']['match_type'] === 'exact') {
                        $stats['exact_matches']++;
                    } elseif ($match['metadata']['match_type'] === 'fuzzy') {
                        $stats['fuzzy_matches']++;
                    }
                } else {
                    $stats['unmatched']++;
                }
            }

            DB::commit();

            Log::info('Transaction matching completed', $stats);

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction matching failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Rematch a single transaction
     */
    public function rematchTransaction(StatementTransaction $transaction): bool
    {
        $match = $this->matchTransaction($transaction->description);

        if ($match) {
            $transaction->update([
                'matched_keyword_id' => $match['keyword_id'],
                'sub_category_id' => $match['sub_category_id'],
                'category_id' => $match['category_id'],
                'type_id' => $match['type_id'],
                'confidence_score' => $match['score'],
            ]);

            MatchingLog::create([
                'statement_transaction_id' => $transaction->id,
                'keyword_id' => $match['keyword_id'],
                'matched_text' => $match['matched_text'],
                'confidence_score' => $match['score'],
                'match_metadata' => $match['metadata'],
                'matched_at' => now(),
            ]);

            Keyword::where('id', $match['keyword_id'])->increment('match_count');

            return true;
        }

        return false;
    }

    /**
     * Clear cache untuk keyword updates
     */
    public function clearKeywordCache(): void
    {
        Cache::forget('active_keywords_with_relations');
    }
}