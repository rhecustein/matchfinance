<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\Keyword;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index(Request $request)
    {
        // Date range filter (default: current month)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // General Statistics
        $stats = [
            'total_banks' => Bank::active()->count(),
            'total_statements' => BankStatement::count(),
            'total_transactions' => StatementTransaction::count(),
            'total_keywords' => Keyword::active()->count(),
            
            // Statements by status
            'pending_statements' => BankStatement::status('pending')->count(),
            'processing_statements' => BankStatement::status('processing')->count(),
            'completed_statements' => BankStatement::status('completed')->count(),
            'failed_statements' => BankStatement::status('failed')->count(),
            
            // Transactions stats
            'matched_transactions' => StatementTransaction::matched()->count(),
            'unmatched_transactions' => StatementTransaction::unmatched()->count(),
            'verified_transactions' => StatementTransaction::verified()->count(),
            'low_confidence_transactions' => StatementTransaction::lowConfidence()->count(),
        ];

        // Recent Bank Statements
        $recentStatements = BankStatement::with(['bank', 'user'])
            ->latest('uploaded_at')
            ->limit(10)
            ->get();

        // Transactions by Category (for period)
        $transactionsByCategory = StatementTransaction::select(
                'categories.name as category_name',
                'categories.color',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE 0 END) as total_credit')
            )
            ->join('categories', 'statement_transactions.category_id', '=', 'categories.id')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total_transactions')
            ->limit(10)
            ->get();

        // Transactions by Bank (for period)
        $transactionsByBank = StatementTransaction::select(
                'banks.name as bank_name',
                'banks.code',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE 0 END) as total_credit')
            )
            ->join('bank_statements', 'statement_transactions.bank_statement_id', '=', 'bank_statements.id')
            ->join('banks', 'bank_statements.bank_id', '=', 'banks.id')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('banks.id', 'banks.name', 'banks.code')
            ->orderByDesc('total_transactions')
            ->get();

        // Daily Transaction Trend (last 30 days)
        $dailyTrend = StatementTransaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN amount ELSE 0 END) as debit'),
                DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE 0 END) as credit')
            )
            ->whereBetween('transaction_date', [
                Carbon::now()->subDays(30),
                Carbon::now()
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top 10 Keywords by Usage
        $topKeywords = Keyword::select(
                'keywords.id',
                'keywords.keyword',
                'sub_categories.name as sub_category_name',
                DB::raw('COUNT(statement_transactions.id) as usage_count')
            )
            ->join('statement_transactions', 'keywords.id', '=', 'statement_transactions.matched_keyword_id')
            ->join('sub_categories', 'keywords.sub_category_id', '=', 'sub_categories.id')
            ->groupBy('keywords.id', 'keywords.keyword', 'sub_categories.name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // Matching Accuracy
        $totalMatched = StatementTransaction::matched()->count();
        $totalTransactions = StatementTransaction::count();
        $matchingAccuracy = $totalTransactions > 0 
            ? round(($totalMatched / $totalTransactions) * 100, 2) 
            : 0;

        // Verification Rate
        $totalVerified = StatementTransaction::verified()->count();
        $verificationRate = $totalTransactions > 0 
            ? round(($totalVerified / $totalTransactions) * 100, 2) 
            : 0;

        // Recent Unmatched Transactions
        $unmatchedTransactions = StatementTransaction::with(['bankStatement.bank'])
            ->unmatched()
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'stats',
            'recentStatements',
            'transactionsByCategory',
            'transactionsByBank',
            'dailyTrend',
            'topKeywords',
            'matchingAccuracy',
            'verificationRate',
            'unmatchedTransactions',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get statistics for AJAX requests
     */
    public function getStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $stats = [
            'total_transactions' => StatementTransaction::whereBetween('transaction_date', [$startDate, $endDate])->count(),
            'total_debit' => StatementTransaction::where('transaction_type', 'debit')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount'),
            'total_credit' => StatementTransaction::where('transaction_type', 'credit')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount'),
            'matched_rate' => $this->calculateMatchedRate($startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Calculate matched rate for period
     */
    private function calculateMatchedRate($startDate, $endDate): float
    {
        $total = StatementTransaction::whereBetween('transaction_date', [$startDate, $endDate])->count();
        $matched = StatementTransaction::matched()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->count();

        return $total > 0 ? round(($matched / $total) * 100, 2) : 0;
    }
}