<?php

namespace App\Services;
use App\Models\StatementTransaction;

class EnhancedKeywordExtractor
{
    /**
     * Extract potential keywords dari description
     */
    public function extractKeywords(string $description): array
    {
        $keywords = [];
        
        // 1. Extract merchant names (UPPERCASE words)
        if (preg_match_all('/\b[A-Z]{3,}(?:\s+[A-Z]+)*\b/', $description, $matches)) {
            $keywords = array_merge($keywords, $matches[0]);
        }
        
        // 2. Extract common payment methods
        $paymentMethods = ['QR', 'QRIS', 'EDC', 'ATM', 'TRANSFER', 'TRSF'];
        foreach ($paymentMethods as $method) {
            if (stripos($description, $method) !== false) {
                $keywords[] = $method;
            }
        }
        
        // 3. Extract e-wallets & apps
        $ewallets = ['OVO', 'GOPAY', 'DANA', 'SHOPEEPAY', 'LINKAJA'];
        foreach ($ewallets as $wallet) {
            if (stripos($description, $wallet) !== false) {
                $keywords[] = $wallet;
            }
        }
        
        // 4. Extract recurring patterns (untuk subscription)
        if (preg_match('/RECURRING|MONTHLY|SUBSCRIPTION/', $description)) {
            $keywords[] = 'RECURRING';
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Suggest keywords based on unmatched transactions
     */
    public function suggestKeywords($companyId)
    {
        $unmatchedTransactions = StatementTransaction::where('company_id', $companyId)
            ->whereNull('matched_keyword_id')
            ->get();
        
        $suggestions = [];
        
        foreach ($unmatchedTransactions as $transaction) {
            $extracted = $this->extractKeywords($transaction->description);
            
            foreach ($extracted as $keyword) {
                if (!isset($suggestions[$keyword])) {
                    $suggestions[$keyword] = [
                        'keyword' => $keyword,
                        'count' => 0,
                        'sample_descriptions' => [],
                        'total_amount' => 0
                    ];
                }
                
                $suggestions[$keyword]['count']++;
                $suggestions[$keyword]['total_amount'] += $transaction->amount;
                
                if (count($suggestions[$keyword]['sample_descriptions']) < 3) {
                    $suggestions[$keyword]['sample_descriptions'][] = $transaction->description;
                }
            }
        }
        
        // Sort by frequency
        uasort($suggestions, fn($a, $b) => $b['count'] <=> $a['count']);
        
        return $suggestions;
    }
}