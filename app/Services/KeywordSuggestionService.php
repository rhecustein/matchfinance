<?php

namespace App\Services;

use App\Models\StatementTransaction;
use App\Models\Keyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeywordSuggestionService
{
    /**
     * Analyze unmatched transactions and suggest keywords
     */
    public function analyzeBankStatement(int $bankStatementId, array $filters = []): array
    {
        Log::info('=== ANALYZING TRANSACTIONS FOR KEYWORDS ===', [
            'bank_statement_id' => $bankStatementId,
            'filters' => $filters
        ]);

        // Get all unmatched transactions
        $query = StatementTransaction::where('bank_statement_id', $bankStatementId)
            ->whereNull('matched_keyword_id');

        // Apply filters
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        // Extract and group patterns
        $suggestions = [];
        $processedDescriptions = [];

        foreach ($transactions as $transaction) {
            $description = strtoupper($transaction->description);
            
            // Skip if already processed similar description
            if ($this->isSimilarToProcessed($description, $processedDescriptions)) {
                continue;
            }

            // Extract potential keywords from description
            $keywords = $this->extractKeywords($description);
            
            if (empty($keywords)) {
                continue;
            }

            // Find similar transactions
            $similarTransactions = $this->findSimilarTransactions(
                $bankStatementId, 
                $keywords,
                $transaction->id
            );

            $minFrequency = $filters['min_frequency'] ?? 2;

            // Only suggest if there are multiple similar transactions
            if (count($similarTransactions) >= ($minFrequency - 1)) {
                $allTransactionIds = array_merge([$transaction->id], $similarTransactions);
                
                // Calculate amounts
                $amounts = $this->calculateAmounts($allTransactionIds);
                
                // Apply min_amount filter
                if (!empty($filters['min_amount']) && $amounts['avg_amount'] < $filters['min_amount']) {
                    continue;
                }

                $suggestions[] = [
                    'suggested_keyword' => $this->getBestKeyword($keywords),
                    'alternative_keywords' => $keywords,
                    'description_sample' => $transaction->description,
                    'transaction_count' => count($allTransactionIds),
                    'transaction_ids' => $allTransactionIds,
                    'transaction_type' => $transaction->transaction_type,
                    'avg_amount' => $amounts['avg_amount'],
                    'total_amount' => $amounts['total_amount'], // ✅ FIXED: Added total_amount
                    'frequency' => $this->determineFrequency(count($allTransactionIds)),
                ];

                $processedDescriptions[] = $description;
            }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'frequency';
        usort($suggestions, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'amount':
                    return $b['total_amount'] - $a['total_amount'];
                case 'count':
                    return $b['transaction_count'] - $a['transaction_count'];
                case 'frequency':
                default:
                    return $b['transaction_count'] - $a['transaction_count'];
            }
        });

        Log::info('Analysis complete', [
            'suggestions_count' => count($suggestions)
        ]);

        return $suggestions;
    }

    /**
     * ✅ NEW: Calculate amounts for transactions
     */
    private function calculateAmounts(array $transactionIds): array
    {
        $transactions = StatementTransaction::whereIn('id', $transactionIds)->get();
        
        $totalAmount = 0;
        foreach ($transactions as $transaction) {
            $amount = $transaction->transaction_type === 'debit' 
                ? $transaction->debit_amount 
                : $transaction->credit_amount;
            $totalAmount += $amount;
        }
        
        $avgAmount = count($transactions) > 0 ? $totalAmount / count($transactions) : 0;

        return [
            'total_amount' => $totalAmount,
            'avg_amount' => $avgAmount,
        ];
    }

    /**
     * ✅ NEW: Determine frequency pattern
     */
    private function determineFrequency(int $count): string
    {
        if ($count >= 20) return 'very_frequent';
        if ($count >= 10) return 'frequent';
        if ($count >= 5) return 'regular';
        if ($count >= 2) return 'occasional';
        return 'rare';
    }

    /**
     * Extract potential keywords from description
     */
    private function extractKeywords(string $description): array
    {
        $keywords = [];

        // Common patterns to extract
        $patterns = [
            // Bank names
            '/\b(BCA|MANDIRI|BRI|BNI|BTN|CIMB|DANAMON|PERMATA|BANK\s+\w+)\b/i',
            
            // E-commerce & payment
            '/\b(TOKOPEDIA|SHOPEE|LAZADA|BUKALAPAK|BLIBLI|GOPAY|OVO|DANA|SHOPEEPAY|LINKAJA)\b/i',
            
            // Retail stores
            '/\b(INDOMARET|ALFAMART|ALFAMIDI|HYPERMART|SUPERINDO|CARREFOUR|GIANT|TRANSMART)\b/i',
            
            // Pharmacy
            '/\b(APOTEK|KIMIA\s*FARMA|GUARDIAN|CENTURY|VIVA|FARMASI)\b/i',
            
            // Restaurant & Food
            '/\b(MCDONALD|KFC|PIZZA\s*HUT|STARBUCKS|DUNKIN|BURGER\s*KING|RESTO|RESTAURANT|WARUNG|CAFE)\b/i',
            
            // Transportation
            '/\b(GOJEK|GRAB|UBER|TAXI|BLUE\s*BIRD|TRANSJAKARTA|MRT|LRT)\b/i',
            
            // Utilities
            '/\b(PLN|PDAM|TELKOM|INDIHOME|XL|TELKOMSEL|INDOSAT|SMARTFREN|TRI)\b/i',
            
            // Transfer & Payment
            '/\b(TRANSFER|TRF|OVERBOOKING|TUNAI|CASH|ATM|EDC|QRIS|QR)\b/i',
            
            // General merchants (3+ consecutive uppercase words)
            '/\b([A-Z]{3,}(?:\s+[A-Z]{3,}){0,2})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $description, $matches)) {
                foreach ($matches[0] as $match) {
                    $cleaned = trim($match);
                    if (strlen($cleaned) >= 3 && !in_array($cleaned, $keywords)) {
                        $keywords[] = $cleaned;
                    }
                }
            }
        }

        // Remove common noise words
        $noiseWords = ['THE', 'AND', 'FOR', 'WITH', 'FROM', 'TO', 'IN', 'ON', 'AT', 'BY'];
        $keywords = array_filter($keywords, function($keyword) use ($noiseWords) {
            return !in_array($keyword, $noiseWords);
        });

        // Limit to top 5 keywords
        return array_slice($keywords, 0, 5);
    }

    /**
     * Get best keyword from extracted keywords
     */
    private function getBestKeyword(array $keywords): string
    {
        if (empty($keywords)) {
            return '';
        }

        // Prefer longer, more specific keywords
        usort($keywords, function($a, $b) {
            $scoreA = strlen($a) + (substr_count($a, ' ') * 5);
            $scoreB = strlen($b) + (substr_count($b, ' ') * 5);
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
            ->whereNull('matched_keyword_id');

        // Build OR conditions for each keyword
        $query->where(function($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('description', 'LIKE', "%{$keyword}%");
            }
        });

        return $query->pluck('id')->toArray();
    }

    /**
     * Check if description is similar to already processed ones
     */
    private function isSimilarToProcessed(string $description, array $processedDescriptions): bool
    {
        foreach ($processedDescriptions as $processed) {
            similar_text($description, $processed, $percent);
            if ($percent > 70) { // 70% similarity threshold
                return true;
            }
        }
        return false;
    }

    /**
     * Apply keyword to suggested transactions
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
                'confidence_score' => 85, // Auto-suggested = 85% confidence
            ]);

        Log::info('Keyword applied to transactions', [
            'keyword_id' => $keywordId,
            'transactions_updated' => $updated,
        ]);

        return $updated;
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
            'case_sensitive_keywords' => Keyword::where('case_sensitive', true)->count(),
            'unused_keywords' => Keyword::doesntHave('matchedTransactions')->count(),
        ];
    }
}