<?php

namespace App\Services;

use App\Models\StatementTransaction;
use Illuminate\Support\Facades\Http;

class MLKeywordMatcher
{
    /**
     * Use ML model untuk predict category
     */
    public function predictCategory($description, $companyId)
    {
        // Get training data dari transactions yang sudah verified
        $trainingData = StatementTransaction::where('company_id', $companyId)
            ->where('is_verified', true)
            ->whereNotNull('sub_category_id')
            ->select('description', 'sub_category_id')
            ->limit(1000)
            ->get();
        
        if ($trainingData->count() < 100) {
            // Not enough training data
            return null;
        }
        
        // Call ML API (contoh menggunakan Python FastAPI)
        $response = Http::timeout(30)->post(config('services.ml.url') . '/predict', [
            'description' => $description,
            'training_data' => $trainingData->toArray(),
            'company_id' => $companyId
        ]);
        
        if ($response->successful()) {
            return [
                'sub_category_id' => $response->json('sub_category_id'),
                'confidence' => $response->json('confidence'),
                'method' => 'ml_prediction'
            ];
        }
        
        return null;
    }
}