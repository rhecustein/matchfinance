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
    public function analyzeBankStatement(int $bankStatementId): array
    {
        Log::info('=== ANALYZING TRANSACTIONS FOR KEYWORDS ===', [
            'bank_statement_id' => $bankStatementId
        ]);

        // Get all unmatched transactions
        $transactions = StatementTransaction::where('bank_statement_id', $bankStatementId)
            ->whereNull('matched_keyword_id')
            ->get();

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

            // Only suggest if there are multiple similar transactions
            if (count($similarTransactions) >= 2) {
                $suggestions[] = [
                    'suggested_keyword' => $this->getBestKeyword($keywords),
                    'alternative_keywords' => $keywords,
                    'description_sample' => $transaction->description,
                    'transaction_count' => count($similarTransactions) + 1, // +1 for current
                    'transaction_ids' => array_merge([$transaction->id], $similarTransactions),
                    'transaction_type' => $transaction->transaction_type,
                    'avg_amount' => $this->calculateAverageAmount($bankStatementId, $similarTransactions, $transaction->id),
                    'frequency' => 'recurring', // Could be enhanced
                ];

                $processedDescriptions[] = $description;
            }
        }

        // Sort by transaction count (most frequent first)
        usort($suggestions, function($a, $b) {
            return $b['transaction_count'] - $a['transaction_count'];
        });

        Log::info('Analysis complete', [
            'suggestions_count' => count($suggestions)
        ]);

        return $suggestions;
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
            
            // General merchants
            '/\b([A-Z]{3,}(?:\s+[A-Z]{3,}){0,2})\b/', // 3+ consecutive uppercase words
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
     * Calculate average amount for similar transactions
     */
    private function calculateAverageAmount(int $bankStatementId, array $transactionIds, int $currentId): float
    {
        $allIds = array_merge($transactionIds, [$currentId]);
        
        $sum = StatementTransaction::whereIn('id', $allIds)
            ->sum(DB::raw('CASE WHEN transaction_type = "debit" THEN debit_amount ELSE credit_amount END'));

        return $sum / count($allIds);
    }

    /**
     * Create keyword from suggestion
     */
    public function createKeywordFromSuggestion(array $suggestion, int $subCategoryId, array $options = []): Keyword
    {
        $keyword = Keyword::create([
            'sub_category_id' => $subCategoryId,
            'keyword' => $options['custom_keyword'] ?? $suggestion['suggested_keyword'],
            'is_regex' => $options['is_regex'] ?? false,
            'case_sensitive' => $options['case_sensitive'] ?? false,
            'priority' => $options['priority'] ?? 5,
            'is_active' => true,
            'description' => "Auto-generated from {$suggestion['transaction_count']} similar transactions",
        ]);

        Log::info('Keyword created from suggestion', [
            'keyword_id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'transaction_count' => $suggestion['transaction_count'],
        ]);

        return $keyword;
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
            'active_keywords' => Keyword::active()->count(),
            'regex_keywords' => Keyword::where('is_regex', true)->count(),
            'case_sensitive_keywords' => Keyword::where('case_sensitive', true)->count(),
            'unused_keywords' => Keyword::doesntHave('matchedTransactions')->count(),
        ];
    }
}