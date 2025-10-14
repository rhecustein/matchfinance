<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\KeywordSuggestion;
use App\Models\StatementTransaction;
use App\Services\EnhancedKeywordExtractor;


class KeywordLearningService
{
    /**
     * Learn from manual corrections
     */
    public function learnFromCorrection($transactionId, $correctSubCategoryId)
    {
        $transaction = StatementTransaction::find($transactionId);
        
        // Extract keywords dari description
        $extractor = new EnhancedKeywordExtractor();
        $keywords = $extractor->extractKeywords($transaction->description);
        
        foreach ($keywords as $keyword) {
            // Check if keyword exists for this subcategory
            $existingKeyword = Keyword::where('company_id', $transaction->company_id)
                ->where('sub_category_id', $correctSubCategoryId)
                ->where('keyword', $keyword)
                ->first();
            
            if (!$existingKeyword) {
                // Suggest new keyword
                KeywordSuggestion::create([
                    'company_id' => $transaction->company_id,
                    'sub_category_id' => $correctSubCategoryId,
                    'keyword' => $keyword,
                    'source_transaction_id' => $transactionId,
                    'confidence' => 80,
                    'status' => 'pending'
                ]);
            } else {
                // Increase match count
                $existingKeyword->increment('match_count');
                $existingKeyword->update(['last_matched_at' => now()]);
            }
        }
    }
    
    /**
     * Auto-approve high confidence suggestions
     */
    public function autoApproveSuggestions($companyId)
    {
        $suggestions = KeywordSuggestion::where('company_id', $companyId)
            ->where('status', 'pending')
            ->where('confidence', '>=', 90)
            ->whereRaw('occurrence_count >= 5')
            ->get();
        
        foreach ($suggestions as $suggestion) {
            Keyword::create([
                'company_id' => $suggestion->company_id,
                'sub_category_id' => $suggestion->sub_category_id,
                'keyword' => $suggestion->keyword,
                'match_type' => 'contains',
                'priority' => 5,
                'is_active' => true,
                'auto_learned' => true
            ]);
            
            $suggestion->update(['status' => 'approved']);
        }
    }
}