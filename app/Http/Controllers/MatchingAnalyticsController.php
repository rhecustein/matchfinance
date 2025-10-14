<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\StatementTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MatchingAnalyticsController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        
        // Matching statistics
        $stats = [
            'total_transactions' => StatementTransaction::where('company_id', $companyId)->count(),
            'matched' => StatementTransaction::where('company_id', $companyId)
                ->whereNotNull('matched_keyword_id')->count(),
            'unmatched' => StatementTransaction::where('company_id', $companyId)
                ->whereNull('matched_keyword_id')->count(),
            'verified' => StatementTransaction::where('company_id', $companyId)
                ->where('is_verified', true)->count(),
        ];
        
        // Top keywords
        $topKeywords = Keyword::where('company_id', $companyId)
            ->orderBy('match_count', 'desc')
            ->limit(10)
            ->get();
        
        // Categories distribution
        $categoryDistribution = DB::table('statement_transactions')
            ->where('company_id', $companyId)
            ->whereNotNull('category_id')
            ->join('categories', 'statement_transactions.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->get();
        
        // Confidence score distribution
        $confidenceDistribution = DB::table('statement_transactions')
            ->where('company_id', $companyId)
            ->selectRaw('
                CASE 
                    WHEN confidence_score >= 90 THEN "90-100"
                    WHEN confidence_score >= 70 THEN "70-89"
                    WHEN confidence_score >= 50 THEN "50-69"
                    ELSE "Below 50"
                END as range,
                COUNT(*) as count
            ')
            ->groupBy('range')
            ->get();
        
        return view('analytics.matching', compact(
            'stats', 'topKeywords', 'categoryDistribution', 'confidenceDistribution'
        ));
    }
}