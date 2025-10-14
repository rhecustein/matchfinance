<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\StatementTransaction;
use App\Models\KeywordSuggestion;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class KeywordSuggestionService
{
    /**
     * Extract potential keywords dari description
     * 
     * @param string $description
     * @return array
     */
    public function extractPotentialKeywords(string $description): array
    {
        $keywords = [];
        $description = strtoupper(trim($description));
        
        // Pattern 1: Extract nama merchant/toko (kata UPPERCASE berurutan)
        // Contoh: "APOTEK KIMIA FARMA" atau "INDOMARET POINT"
        if (preg_match_all('/\b[A-Z]{2,}(?:\s+[A-Z]+){0,3}\b/', $description, $matches)) {
            foreach ($matches[0] as $match) {
                $match = trim($match);
                if (strlen($match) >= 3 && !$this->isNoiseWord($match)) {
                    $keywords[] = $match;
                }
            }
        }
        
        // Pattern 2: Extract payment methods
        $paymentPatterns = [
            'QRIS', 'QR', 'EDC', 'ATM', 'TRANSFER', 'TRSF', 
            'DEBIT', 'CREDIT', 'TOPUP', 'TARIK', 'SETOR'
        ];
        
        foreach ($paymentPatterns as $pattern) {
            if (stripos($description, $pattern) !== false) {
                // Get context around payment method
                $contextPattern = '/(\b\w+\s+)?' . preg_quote($pattern, '/') . '(\s+\w+)?/i';
                if (preg_match($contextPattern, $description, $contextMatch)) {
                    $keywords[] = trim($contextMatch[0]);
                }
            }
        }
        
        // Pattern 3: E-wallets dan digital services
        $digitalServices = [
            'OVO', 'GOPAY', 'DANA', 'SHOPEEPAY', 'LINKAJA',
            'TOKOPEDIA', 'SHOPEE', 'LAZADA', 'GRAB', 'GOJEK',
            'BLIBLI', 'BUKALAPAK', 'TRAVELOKA'
        ];
        
        foreach ($digitalServices as $service) {
            if (stripos($description, $service) !== false) {
                $keywords[] = $service;
                
                // Also extract variant (e.g., "GOPAY COINS")
                $variantPattern = '/' . preg_quote($service, '/') . '\s+\w+/i';
                if (preg_match($variantPattern, $description, $variantMatch)) {
                    $keywords[] = strtoupper(trim($variantMatch[0]));
                }
            }
        }
        
        // Pattern 4: Kode unik / reference numbers yang berulang
        // Contoh: "TRX-123456" atau "INV/2024/001"
        if (preg_match_all('/\b[A-Z]{2,}-\d+|\b[A-Z]+\/\d+/', $description, $refMatches)) {
            foreach ($refMatches[0] as $ref) {
                // Extract only the prefix part
                if (preg_match('/^([A-Z]{2,})/', $ref, $prefixMatch)) {
                    $keywords[] = $prefixMatch[1];
                }
            }
        }
        
        // Pattern 5: Recurring/subscription indicators
        if (preg_match('/(RECURRING|MONTHLY|BULANAN|SUBSCRIPTION|LANGGANAN)/i', $description)) {
            $keywords[] = 'RECURRING';
        }
        
        // Remove duplicates dan filter noise
        $keywords = array_unique($keywords);
        $keywords = array_filter($keywords, function($keyword) {
            return strlen($keyword) >= 3 && !$this->isNoiseWord($keyword);
        });
        
        return array_values($keywords);
    }
    
    /**
     * Check if word is noise/common word
     */
    private function isNoiseWord(string $word): bool
    {
        $noiseWords = [
            'THE', 'AND', 'FOR', 'WITH', 'FROM', 'TO', 'IN', 'ON', 'AT', 'BY',
            'IDR', 'RP', 'USD', 'DARI', 'KE', 'UNTUK', 'YANG', 'DAN', 'ATAU'
        ];
        
        return in_array($word, $noiseWords) || is_numeric($word);
    }
    
    /**
     * Analyze unmatched transactions and suggest keywords
     * 
     * @param int $companyId
     * @param int $limit
     * @return array
     */
    public function analyzeUnmatchedTransactions(int $companyId, int $limit = 100): array
    {
        $unmatched = StatementTransaction::where('company_id', $companyId)
            ->whereNull('matched_keyword_id')
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();
        
        $suggestions = [];
        
        foreach ($unmatched as $transaction) {
            $potentialKeywords = $this->extractPotentialKeywords($transaction->description);
            
            foreach ($potentialKeywords as $keyword) {
                $key = strtoupper($keyword);
                
                if (!isset($suggestions[$key])) {
                    $suggestions[$key] = [
                        'keyword' => $keyword,
                        'occurrences' => 0,
                        'total_debit' => 0,
                        'total_credit' => 0,
                        'sample_descriptions' => [],
                        'transaction_ids' => [],
                        'date_range' => [
                            'first' => $transaction->transaction_date,
                            'last' => $transaction->transaction_date
                        ]
                    ];
                }
                
                $suggestions[$key]['occurrences']++;
                $suggestions[$key]['total_debit'] += $transaction->debit_amount;
                $suggestions[$key]['total_credit'] += $transaction->credit_amount;
                $suggestions[$key]['transaction_ids'][] = $transaction->id;
                
                // Update date range
                if ($transaction->transaction_date < $suggestions[$key]['date_range']['first']) {
                    $suggestions[$key]['date_range']['first'] = $transaction->transaction_date;
                }
                if ($transaction->transaction_date > $suggestions[$key]['date_range']['last']) {
                    $suggestions[$key]['date_range']['last'] = $transaction->transaction_date;
                }
                
                // Keep max 3 sample descriptions
                if (count($suggestions[$key]['sample_descriptions']) < 3) {
                    $suggestions[$key]['sample_descriptions'][] = [
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'date' => $transaction->transaction_date->format('Y-m-d')
                    ];
                }
            }
        }
        
        // Calculate confidence score
        foreach ($suggestions as &$suggestion) {
            $suggestion['confidence'] = $this->calculateSuggestionConfidence($suggestion);
            $suggestion['suggested_category'] = $this->suggestCategory($suggestion, $companyId);
        }
        
        // Sort by confidence and occurrences
        uasort($suggestions, function($a, $b) {
            if ($a['confidence'] == $b['confidence']) {
                return $b['occurrences'] <=> $a['occurrences'];
            }
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return $suggestions;
    }
    
    /**
     * Calculate confidence score for keyword suggestion
     */
    private function calculateSuggestionConfidence(array $suggestion): int
    {
        $score = 0;
        
        // Occurrence weight (max 40 points)
        if ($suggestion['occurrences'] >= 10) {
            $score += 40;
        } elseif ($suggestion['occurrences'] >= 5) {
            $score += 30;
        } elseif ($suggestion['occurrences'] >= 3) {
            $score += 20;
        } else {
            $score += 10;
        }
        
        // Keyword length weight (max 20 points)
        $keywordLength = strlen($suggestion['keyword']);
        if ($keywordLength >= 10) {
            $score += 20; // Specific keywords
        } elseif ($keywordLength >= 5) {
            $score += 15;
        } else {
            $score += 5;
        }
        
        // Pattern consistency (max 20 points)
        // Check if amounts are similar
        $hasConsistentAmount = false;
        if ($suggestion['occurrences'] > 1) {
            $amounts = [];
            foreach ($suggestion['sample_descriptions'] as $sample) {
                $amounts[] = $sample['amount'];
            }
            
            if (count(array_unique($amounts)) == 1) {
                $score += 20; // Same amount = likely subscription
                $hasConsistentAmount = true;
            } elseif ($this->hasConsistentAmountRange($amounts)) {
                $score += 10; // Similar amount range
            }
        }
        
        // Date pattern weight (max 20 points)
        if ($suggestion['occurrences'] >= 3) {
            $daysBetween = $suggestion['date_range']['first']->diffInDays($suggestion['date_range']['last']);
            $avgDaysBetween = $daysBetween / ($suggestion['occurrences'] - 1);
            
            if ($avgDaysBetween >= 28 && $avgDaysBetween <= 31) {
                $score += 20; // Monthly pattern
            } elseif ($avgDaysBetween >= 7 && $avgDaysBetween <= 7.5) {
                $score += 15; // Weekly pattern
            }
        }
        
        return min($score, 100);
    }
    
    /**
     * Check if amounts are in consistent range
     */
    private function hasConsistentAmountRange(array $amounts): bool
    {
        if (count($amounts) < 2) return false;
        
        $min = min($amounts);
        $max = max($amounts);
        $avg = array_sum($amounts) / count($amounts);
        
        // Check if range is within 20% of average
        $tolerance = $avg * 0.2;
        
        return ($max - $min) <= $tolerance;
    }
    
    /**
     * Suggest category based on keyword pattern
     */
    private function suggestCategory(array $suggestion, int $companyId): ?array
    {
        $keyword = strtoupper($suggestion['keyword']);
        
        // Map common patterns to categories
        $categoryPatterns = [
            'Food & Beverage' => ['COFFEE', 'RESTAURANT', 'FOOD', 'MAKAN', 'CAFE', 'BAKERY'],
            'Transportation' => ['GRAB', 'GOJEK', 'TAXI', 'UBER', 'TRANSJAKARTA', 'MRT', 'KRL'],
            'E-Commerce' => ['TOKOPEDIA', 'SHOPEE', 'LAZADA', 'BUKALAPAK', 'BLIBLI'],
            'Utilities' => ['PLN', 'PDAM', 'TELKOM', 'INDIHOME', 'INTERNET'],
            'Healthcare' => ['APOTEK', 'PHARMACY', 'HOSPITAL', 'KLINIK', 'DOCTOR'],
            'Groceries' => ['INDOMARET', 'ALFAMART', 'SUPERINDO', 'HYPERMART', 'CARREFOUR'],
            'Entertainment' => ['NETFLIX', 'SPOTIFY', 'YOUTUBE', 'DISNEY', 'CINEMA', 'XXI'],
            'Subscription' => ['RECURRING', 'MONTHLY', 'SUBSCRIPTION', 'PREMIUM']
        ];
        
        foreach ($categoryPatterns as $categoryName => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($keyword, $pattern) !== false) {
                    // Try to find existing category
                    $category = DB::table('categories')
                        ->where('company_id', $companyId)
                        ->where('name', 'LIKE', '%' . $categoryName . '%')
                        ->first();
                    
                    if ($category) {
                        // Find most used subcategory for this pattern
                        $subCategory = DB::table('sub_categories')
                            ->where('category_id', $category->id)
                            ->first();
                        
                        return [
                            'category_id' => $category->id,
                            'category_name' => $category->name,
                            'sub_category_id' => $subCategory->id ?? null,
                            'sub_category_name' => $subCategory->name ?? null,
                            'reason' => "Pattern match: {$pattern}"
                        ];
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Create keyword from suggestion
     */
    public function createKeywordFromSuggestion(array $suggestionData, int $subCategoryId): Keyword
    {
        return DB::transaction(function() use ($suggestionData, $subCategoryId) {
            $subCategory = SubCategory::findOrFail($subCategoryId);
            
            // Check if keyword already exists
            $existing = Keyword::where('company_id', $subCategory->company_id)
                ->where('sub_category_id', $subCategoryId)
                ->where('keyword', $suggestionData['keyword'])
                ->first();
            
            if ($existing) {
                // Update existing keyword
                $existing->increment('match_count', $suggestionData['occurrences'] ?? 1);
                $existing->update(['last_matched_at' => now()]);
                return $existing;
            }
            
            // Create new keyword
            $keyword = Keyword::create([
                'uuid' => Str::uuid(),
                'company_id' => $subCategory->company_id,
                'sub_category_id' => $subCategoryId,
                'keyword' => $suggestionData['keyword'],
                'is_regex' => false,
                'case_sensitive' => false,
                'match_type' => $this->determineMatchType($suggestionData['keyword']),
                'pattern_description' => 'Auto-generated from transaction analysis',
                'priority' => $this->calculatePriority($suggestionData),
                'is_active' => true,
                'match_count' => $suggestionData['occurrences'] ?? 1
            ]);
            
            // Auto-match transactions with this keyword
            if (!empty($suggestionData['transaction_ids'])) {
                $this->applyKeywordToTransactions($keyword, $suggestionData['transaction_ids']);
            }
            
            // Clear keyword cache
            Cache::forget('active_keywords_with_relations');
            
            Log::info('Keyword created from suggestion', [
                'keyword' => $keyword->keyword,
                'sub_category_id' => $subCategoryId,
                'occurrences' => $suggestionData['occurrences'] ?? 0
            ]);
            
            return $keyword;
        });
    }
    
    /**
     * Determine best match type for keyword
     */
    private function determineMatchType(string $keyword): string
    {
        // If keyword has special chars or spaces, use contains
        if (preg_match('/[\s\-\/]/', $keyword)) {
            return 'contains';
        }
        
        // If keyword is short (< 5 chars), use exact to avoid false positives
        if (strlen($keyword) < 5) {
            return 'exact';
        }
        
        return 'contains';
    }
    
    /**
     * Calculate priority based on suggestion data
     */
    private function calculatePriority(array $suggestionData): int
    {
        $confidence = $suggestionData['confidence'] ?? 50;
        
        if ($confidence >= 90) return 8;
        if ($confidence >= 70) return 6;
        if ($confidence >= 50) return 5;
        return 4;
    }
    
    /**
     * Apply keyword to matching transactions
     */
    private function applyKeywordToTransactions(Keyword $keyword, array $transactionIds): void
    {
        StatementTransaction::whereIn('id', $transactionIds)
            ->whereNull('matched_keyword_id')
            ->update([
                'matched_keyword_id' => $keyword->id,
                'sub_category_id' => $keyword->sub_category_id,
                'category_id' => $keyword->subCategory->category_id,
                'type_id' => $keyword->subCategory->category->type_id,
                'confidence_score' => 85,
                'matching_reason' => 'Auto-matched from keyword learning'
            ]);
    }
    
    /**
     * Learn from manual correction
     */
    public function learnFromCorrection(int $transactionId, int $subCategoryId): void
    {
        $transaction = StatementTransaction::findOrFail($transactionId);
        $keywords = $this->extractPotentialKeywords($transaction->description);
        
        foreach ($keywords as $keyword) {
            // Store as suggestion for review
            DB::table('keyword_suggestions')->insertOrIgnore([
                'uuid' => Str::uuid(),
                'company_id' => $transaction->company_id,
                'sub_category_id' => $subCategoryId,
                'keyword' => $keyword,
                'source_transaction_id' => $transactionId,
                'confidence' => 70,
                'occurrence_count' => 1,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}