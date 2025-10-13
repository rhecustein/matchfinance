<?php

namespace App\Services;

use App\Models\StatementTransaction;
use App\Models\Keyword;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeywordSuggestionService
{
    /**
     * Analyze bank statement and suggest keywords
     */
    public function analyzeBankStatement(int $bankStatementId, array $filters = []): array
    {
        Log::info('Analyzing transactions for keyword suggestions', [
            'bank_statement_id' => $bankStatementId,
            'filters' => $filters
        ]);

        // Get uncategorized transactions
        $query = StatementTransaction::where('bank_statement_id', $bankStatementId)
            ->whereNull('sub_category_id') // Uncategorized
            ->orderBy('transaction_date', 'desc');

        // Apply filters
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        // Group transactions by pattern
        $suggestions = $this->groupTransactionsByPattern($transactions, $filters);

        // Sort suggestions
        $suggestions = $this->sortSuggestions($suggestions, $filters['sort_by'] ?? 'frequency');

        Log::info('Analysis complete', [
            'suggestions_count' => count($suggestions),
            'total_transactions' => $transactions->count(),
        ]);

        return $suggestions;
    }

    /**
     * Group transactions by similar patterns
     */
    private function groupTransactionsByPattern($transactions, array $filters): array
    {
        $groups = [];
        $minFrequency = $filters['min_frequency'] ?? 2;
        $includeSimilar = $filters['include_similar'] ?? true;

        foreach ($transactions as $transaction) {
            $description = $transaction->description;
            
            // Extract potential keywords
            $keywords = $this->extractKeywords($description);
            
            if (empty($keywords)) {
                continue;
            }

            // Get best keyword (most specific)
            $suggestedKeyword = $this->getBestKeyword($keywords);

            // Find similar transactions
            $similarTransactions = $this->findSimilarTransactions(
                $transaction->bank_statement_id,
                $keywords,
                $transaction->id
            );

            // Skip if frequency too low
            if (count($similarTransactions) + 1 < $minFrequency) {
                continue;
            }

            // Calculate group stats
            $allTransactionIds = array_merge([$transaction->id], $similarTransactions);
            $groupTransactions = StatementTransaction::whereIn('id', $allTransactionIds)->get();

            $totalAmount = $groupTransactions->sum('amount');
            $avgAmount = $groupTransactions->avg('amount');

            // Create suggestion
            $groupKey = md5($suggestedKeyword);
            
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'suggested_keyword' => $suggestedKeyword,
                    'alternative_keywords' => array_values(array_unique($keywords)),
                    'transaction_count' => count($allTransactionIds),
                    'transaction_ids' => $allTransactionIds,
                    'description_sample' => $description,
                    'transaction_type' => $transaction->transaction_type,
                    'total_amount' => $totalAmount,
                    'average_amount' => $avgAmount,
                    'frequency' => count($allTransactionIds),
                    'priority_score' => $this->calculatePriorityScore($totalAmount, count($allTransactionIds)),
                    'recommended_match_type' => $this->recommendMatchType($suggestedKeyword),
                    'sample_transactions' => $groupTransactions->take(3)->map(function($t) {
                        return [
                            'id' => $t->id,
                            'date' => $t->transaction_date->format('d M Y'),
                            'description' => $t->description,
                            'amount' => $t->amount,
                        ];
                    })->toArray(),
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * Extract keywords from transaction description
     */
    private function extractKeywords(string $description): array
    {
        $description = strtoupper(trim($description));
        $keywords = [];

        // Pattern 1: Merchant names (uppercase words)
        // Example: "TRSF E-WALLET OVO TOPUP"
        if (preg_match_all('/\b[A-Z]{3,}(?:\s+[A-Z]{3,}){0,2}\b/', $description, $matches)) {
            foreach ($matches[0] as $match) {
                $keywords[] = trim($match);
            }
        }

        // Pattern 2: Words with numbers (account/reference numbers)
        // Example: "GIRO 1234567"
        if (preg_match_all('/\b[A-Z]+\s+\d+/', $description, $matches)) {
            foreach ($matches[0] as $match) {
                // Extract only the text part
                if (preg_match('/^([A-Z]+)/', $match, $textMatch)) {
                    $keywords[] = $textMatch[1];
                }
            }
        }

        // Pattern 3: Common transaction types
        $commonTypes = ['TRANSFER', 'TRSF', 'DEBIT', 'CREDIT', 'PAYMENT', 'BAYAR', 'TOPUP', 'TARIK'];
        foreach ($commonTypes as $type) {
            if (str_contains($description, $type)) {
                // Get context around this word
                $pattern = '/\b' . $type . '\b\s+([A-Z]+(?:\s+[A-Z]+){0,2})/';
                if (preg_match($pattern, $description, $match)) {
                    $keywords[] = trim($match[1]);
                }
            }
        }

        // Pattern 4: E-wallet and digital services
        $digitalServices = ['OVO', 'GOPAY', 'DANA', 'SHOPEEPAY', 'LINKAJA', 'TOKOPEDIA', 'SHOPEE', 'LAZADA', 'GRAB'];
        foreach ($digitalServices as $service) {
            if (str_contains($description, $service)) {
                $keywords[] = $service;
            }
        }

        // Pattern 5: ATM/CDM locations
        if (preg_match('/ATM|CDM/', $description)) {
            $keywords[] = 'ATM';
        }

        // Remove noise words
        $noiseWords = ['THE', 'AND', 'FOR', 'WITH', 'FROM', 'TO', 'IN', 'ON', 'AT', 'BY', 'IDR', 'RP'];
        $keywords = array_filter($keywords, function($keyword) use ($noiseWords) {
            return !in_array($keyword, $noiseWords) && strlen($keyword) >= 3;
        });

        // Remove duplicates and return
        return array_values(array_unique($keywords));
    }

    /**
     * Get best keyword from extracted keywords
     */
    private function getBestKeyword(array $keywords): string
    {
        if (empty($keywords)) {
            return 'UNKNOWN';
        }

        // Prefer longer, more specific keywords
        usort($keywords, function($a, $b) {
            // Prioritize keywords with spaces (more specific)
            $scoreA = strlen($a) + (substr_count($a, ' ') * 10);
            $scoreB = strlen($b) + (substr_count($b, ' ') * 10);
            return $scoreB - $scoreA;
        });

        return $keywords[0];
    }

    /**
     * Find similar transactions based on keywords
     */
    private function findSimilarTransactions(int $bankStatementId, array $keywords, int $excludeId): array
    {
        $query = StatementTransaction::where('bank_statement_id', $bankStatementId)
            ->where('id', '!=', $excludeId)
            ->whereNull('sub_category_id'); // Only uncategorized

        // Build search conditions
        $query->where(function($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('description', 'LIKE', "%{$keyword}%");
            }
        });

        return $query->pluck('id')->toArray();
    }

    /**
     * Calculate priority score for suggestion
     */
    private function calculatePriorityScore(float $totalAmount, int $frequency): int
    {
        // Score based on:
        // - Frequency: more frequent = higher priority
        // - Amount: higher amounts = higher priority
        
        $frequencyScore = min($frequency * 2, 50); // Max 50 points
        $amountScore = min(($totalAmount / 1000000) * 10, 50); // Max 50 points (normalize to millions)
        
        return (int) ($frequencyScore + $amountScore);
    }

    /**
     * Recommend match type based on keyword pattern
     */
    private function recommendMatchType(string $keyword): string
    {
        // If contains special chars, recommend regex
        if (preg_match('/[^A-Z0-9\s]/', $keyword)) {
            return 'regex';
        }

        // If single word, recommend exact
        if (!str_contains($keyword, ' ')) {
            return 'exact';
        }

        // Default: contains
        return 'contains';
    }

    /**
     * Sort suggestions by specified criteria
     */
    private function sortSuggestions(array $suggestions, string $sortBy): array
    {
        usort($suggestions, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'amount':
                    return $b['total_amount'] <=> $a['total_amount'];
                case 'count':
                    return $b['transaction_count'] <=> $a['transaction_count'];
                case 'frequency':
                default:
                    return $b['frequency'] <=> $a['frequency'];
            }
        });

        return $suggestions;
    }

    /**
     * Get AI-powered category recommendations
     */
    public function getAICategoryRecommendations(array $suggestions): array
    {
        $recommendations = [];

        foreach ($suggestions as $suggestion) {
            $keyword = $suggestion['suggested_keyword'];
            $transactionType = $suggestion['transaction_type'];
            $avgAmount = $suggestion['average_amount'];

            // Simple rule-based AI (can be enhanced with ML)
            $category = $this->predictCategory($keyword, $transactionType, $avgAmount);

            if ($category) {
                $recommendations[$keyword] = [
                    'category' => $category['name'],
                    'sub_category' => $category['sub_category'] ?? null,
                    'confidence' => $category['confidence'],
                    'reason' => $category['reason'],
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Predict category based on keyword patterns
     */
    private function predictCategory(string $keyword, string $transactionType, float $avgAmount): ?array
    {
        $keyword = strtoupper($keyword);

        // E-commerce & Shopping
        if (preg_match('/TOKOPEDIA|SHOPEE|LAZADA|BUKALAPAK|BLIBLI/', $keyword)) {
            return [
                'name' => 'Shopping',
                'sub_category' => 'E-commerce',
                'confidence' => 95,
                'reason' => 'E-commerce platform detected'
            ];
        }

        // Food & Dining
        if (preg_match('/GOFOOD|GRABFOOD|SHOPEEFOOD|RESTAURANT|CAFE|MCDONALD|KFC/', $keyword)) {
            return [
                'name' => 'Food & Dining',
                'sub_category' => 'Food Delivery',
                'confidence' => 90,
                'reason' => 'Food delivery or restaurant detected'
            ];
        }

        // Transportation
        if (preg_match('/GOJEK|GRAB|TAXI|UBER|TRANSPORT/', $keyword)) {
            return [
                'name' => 'Transportation',
                'sub_category' => 'Ride Hailing',
                'confidence' => 90,
                'reason' => 'Transportation service detected'
            ];
        }

        // Digital Wallet
        if (preg_match('/OVO|GOPAY|DANA|SHOPEEPAY|LINKAJA|TOPUP/', $keyword)) {
            return [
                'name' => 'Transfer',
                'sub_category' => 'E-Wallet',
                'confidence' => 95,
                'reason' => 'Digital wallet detected'
            ];
        }

        // Utilities
        if (preg_match('/PLN|PDAM|TELKOM|INTERNET|LISTRIK|AIR/', $keyword)) {
            return [
                'name' => 'Utilities',
                'sub_category' => 'Bills',
                'confidence' => 85,
                'reason' => 'Utility payment detected'
            ];
        }

        // ATM Withdrawal
        if (preg_match('/ATM|TARIK TUNAI|CASH|CDM/', $keyword)) {
            return [
                'name' => 'Cash & ATM',
                'sub_category' => 'Withdrawal',
                'confidence' => 90,
                'reason' => 'ATM transaction detected'
            ];
        }

        // Transfer
        if (preg_match('/TRANSFER|TRSF|KIRIM/', $keyword)) {
            return [
                'name' => 'Transfer',
                'sub_category' => $transactionType === 'debit' ? 'Transfer Out' : 'Transfer In',
                'confidence' => 70,
                'reason' => 'Transfer transaction detected'
            ];
        }

        // Salary (high amount credit)
        if ($transactionType === 'credit' && $avgAmount > 3000000) {
            return [
                'name' => 'Income',
                'sub_category' => 'Salary',
                'confidence' => 60,
                'reason' => 'Large credit transaction suggests salary'
            ];
        }

        return null;
    }

    /**
     * Apply keyword to transactions
     */
    public function applyKeywordToTransactions(int $keywordId, array $transactionIds): int
    {
        $keyword = Keyword::with('subCategory.category.type')->findOrFail($keywordId);
        
        $updated = StatementTransaction::whereIn('id', $transactionIds)
            ->update([
                'matched_keyword_id' => $keyword->id,
                'sub_category_id' => $keyword->sub_category_id,
                'category_id' => $keyword->subCategory->category_id,
                'type_id' => $keyword->subCategory->category->type_id,
                'confidence_score' => 85, // AI-suggested = 85% confidence
                'is_manual_category' => false,
                'matching_reason' => "Auto-matched using AI-suggested keyword: {$keyword->keyword}",
            ]);

        // Update keyword stats
        $keyword->increment('match_count', $updated);
        $keyword->update(['last_matched_at' => now()]);

        Log::info('Keyword applied to transactions', [
            'keyword_id' => $keywordId,
            'transactions_updated' => $updated,
        ]);

        return $updated;
    }

    /**
     * Detect potential duplicate keywords
     */
    public function detectDuplicates(string $keyword, array $existingKeywords): array
    {
        $duplicates = [];
        $keywordLower = strtolower($keyword);

        foreach ($existingKeywords as $existing) {
            $existingLower = strtolower($existing);

            // Exact match
            if ($keywordLower === $existingLower) {
                $duplicates[] = [
                    'keyword' => $existing,
                    'match_type' => 'exact',
                    'similarity' => 100,
                ];
            }
            // Similar (Levenshtein distance <= 2)
            else if (levenshtein($keywordLower, $existingLower) <= 2) {
                $similarity = round((1 - levenshtein($keywordLower, $existingLower) / max(strlen($keywordLower), strlen($existingLower))) * 100);
                $duplicates[] = [
                    'keyword' => $existing,
                    'match_type' => 'similar',
                    'similarity' => $similarity,
                ];
            }
            // Substring
            else if (str_contains($existingLower, $keywordLower) || str_contains($keywordLower, $existingLower)) {
                $duplicates[] = [
                    'keyword' => $existing,
                    'match_type' => 'substring',
                    'similarity' => 70,
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Get keyword statistics
     */
    public function getKeywordStats(): array
    {
        return [
            'total_keywords' => Keyword::count(),
            'active_keywords' => Keyword::where('is_active', true)->count(),
            'regex_keywords' => Keyword::where('is_regex', true)->count(),
            'unused_keywords' => Keyword::doesntHave('matchedTransactions')->count(),
            'avg_match_count' => round(Keyword::avg('match_count'), 2),
        ];
    }
}