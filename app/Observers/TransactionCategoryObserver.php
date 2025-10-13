<?php

namespace App\Observers;

use App\Models\TransactionCategory;

class TransactionCategoryObserver
{
    public function updated(TransactionCategory $transactionCategory)
    {
        if ($transactionCategory->is_primary && $transactionCategory->isDirty('is_primary')) {
            // Sync denormalized fields
            $transactionCategory->statementTransaction->update([
                'sub_category_id' => $transactionCategory->sub_category_id,
                'category_id' => $transactionCategory->category_id,
                'type_id' => $transactionCategory->type_id,
                'confidence_score' => $transactionCategory->confidence_score,
                'matched_keyword_id' => $transactionCategory->matched_keyword_id,
            ]);
        }
    }
}