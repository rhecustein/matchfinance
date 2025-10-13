<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\Type;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Keyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role
     */
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return $this->adminDashboard();
        }
        
        return $this->userDashboard();
    }

    /**
     * Admin Dashboard with full statistics (Optimized for large datasets)
     */
    private function adminDashboard()
    {
        // Cache statistics for 5 minutes to reduce database load
        $stats = Cache::remember('dashboard_admin_stats_v2', 300, function () {
            return [
                // User Statistics
                'total_users' => User::count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'total_regular_users' => User::where('role', 'user')->count(),
                
                // Bank & Statement Statistics
                'total_banks' => Bank::count(),
                'total_bank_statements' => BankStatement::count(),
                'pending_ocr' => BankStatement::where('ocr_status', 'pending')->count(),
                'processing_ocr' => BankStatement::where('ocr_status', 'processing')->count(),
                'completed_ocr' => BankStatement::where('ocr_status', 'completed')->count(),
                'failed_ocr' => BankStatement::where('ocr_status', 'failed')->count(),
                
                // Transaction Statistics
                'total_transactions' => DB::table('statement_transactions')
                    ->whereNull('deleted_at')
                    ->count(),
                'verified_transactions' => DB::table('statement_transactions')
                    ->where('is_verified', true)
                    ->whereNull('deleted_at')
                    ->count(),
                'unverified_transactions' => DB::table('statement_transactions')
                    ->where('is_verified', false)
                    ->whereNull('deleted_at')
                    ->count(),
                
                // Master Data Statistics
                'total_types' => Type::count(),
                'total_categories' => Category::count(),
                'total_sub_categories' => SubCategory::count(),
                'total_keywords' => Keyword::count(),
                'active_keywords' => Keyword::where('is_active', true)->count(),
            ];
        });

        // OCR Processing Status - For monitoring queue
        $ocrStatus = Cache::remember('dashboard_ocr_status', 60, function () {
            return BankStatement::select('ocr_status', DB::raw('count(*) as count'))
                ->groupBy('ocr_status')
                ->pluck('count', 'ocr_status')
                ->toArray();
        });

        // Recent Transactions - Optimized with specific columns and eager loading
        $recentTransactions = StatementTransaction::select([
                'id',
                'bank_statement_id',
                'transaction_date',
                'transaction_time',
                'description',
                'debit_amount',
                'credit_amount',
                'transaction_type',
                'sub_category_id',
                'category_id',
                'type_id',
                'is_verified',
                'verified_by',
                'confidence_score',
                'created_at'
            ])
            ->with([
                'bankStatement:id,bank_id,original_filename,account_number',
                'bankStatement.bank:id,name,logo,slug',
                'subCategory:id,name,category_id',
                'category:id,name,type_id,color',
                'type:id,name',
                'verifiedBy:id,name'
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        // Recent Bank Statements - Optimized with OCR status
        $recentStatements = BankStatement::select([
                'id',
                'bank_id',
                'user_id',
                'original_filename',
                'ocr_status',
                'period_from',
                'period_to',
                'total_transactions',
                'matched_transactions',
                'unmatched_transactions',
                'uploaded_at',
                'ocr_completed_at'
            ])
            ->with([
                'bank:id,name,logo,slug',
                'user:id,name,email'
            ])
            ->latest('id')
            ->limit(8)
            ->get();

        // Transaction by Type Chart Data - Optimized
        $transactionsByType = Cache::remember('dashboard_transactions_by_type_v2', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'types.name',
                    'types.id',
                    DB::raw('COUNT(statement_transactions.id) as count'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "credit" THEN statement_transactions.credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "debit" THEN statement_transactions.debit_amount ELSE 0 END) as total_debit')
                )
                ->join('types', 'statement_transactions.type_id', '=', 'types.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('types.id', 'types.name')
                ->orderBy('count', 'desc')
                ->get();
        });

        // Transaction by Category (Top 10) - For pie chart
        $transactionsByCategory = Cache::remember('dashboard_transactions_by_category', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'categories.name',
                    'categories.color',
                    DB::raw('COUNT(statement_transactions.id) as count'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "credit" THEN statement_transactions.credit_amount ELSE statement_transactions.debit_amount END) as total_amount')
                )
                ->join('categories', 'statement_transactions.category_id', '=', 'categories.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('categories.id', 'categories.name', 'categories.color')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
        });

        // Monthly Transaction Trend (last 6 months) - Optimized
        $monthlyTrend = Cache::remember('dashboard_monthly_trend_v2', 300, function () {
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            return DB::table('statement_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN debit_amount ELSE 0 END) as total_debit')
                )
                ->where('transaction_date', '>=', $sixMonthsAgo)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->get();
        });

        // Bank Distribution - Show which bank has most statements
        $bankDistribution = Cache::remember('dashboard_bank_distribution', 300, function () {
            return DB::table('bank_statements')
                ->select(
                    'banks.name',
                    'banks.slug',
                    DB::raw('COUNT(bank_statements.id) as statement_count'),
                    DB::raw('SUM(bank_statements.total_transactions) as transaction_count')
                )
                ->join('banks', 'bank_statements.bank_id', '=', 'banks.id')
                ->groupBy('banks.id', 'banks.name', 'banks.slug')
                ->orderBy('statement_count', 'desc')
                ->get();
        });

        // Recent Users - Optimized
        $recentUsers = User::select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest('id')
            ->limit(5)
            ->get();

        // Matching Statistics - Updated to reflect actual matching system
        $matchingStats = Cache::remember('dashboard_matching_stats_v2', 300, function () {
            $total = DB::table('statement_transactions')
                ->whereNull('deleted_at')
                ->count();
                
            $matched = DB::table('statement_transactions')
                ->whereNotNull('matched_keyword_id')
                ->whereNull('deleted_at')
                ->count();
                
            $unmatched = $total - $matched;
            
            $lowConfidence = DB::table('statement_transactions')
                ->where('confidence_score', '<', 70)
                ->whereNotNull('matched_keyword_id')
                ->whereNull('deleted_at')
                ->count();
            
            $highConfidence = DB::table('statement_transactions')
                ->where('confidence_score', '>=', 90)
                ->whereNotNull('matched_keyword_id')
                ->whereNull('deleted_at')
                ->count();

            return [
                'total' => $total,
                'matched_count' => $matched,
                'unmatched_count' => $unmatched,
                'low_confidence_count' => $lowConfidence,
                'high_confidence_count' => $highConfidence,
                'matching_percentage' => $total > 0 ? round(($matched / $total) * 100, 2) : 0,
            ];
        });

        // Top Matched Keywords - Show most used keywords
        $topKeywords = Cache::remember('dashboard_top_keywords', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'keywords.keyword',
                    'keywords.id',
                    'sub_categories.name as sub_category_name',
                    DB::raw('COUNT(statement_transactions.id) as match_count')
                )
                ->join('keywords', 'statement_transactions.matched_keyword_id', '=', 'keywords.id')
                ->join('sub_categories', 'keywords.sub_category_id', '=', 'sub_categories.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('keywords.id', 'keywords.keyword', 'sub_categories.name')
                ->orderBy('match_count', 'desc')
                ->limit(10)
                ->get();
        });

        // Average Processing Time for OCR
        $avgProcessingTime = Cache::remember('dashboard_avg_processing_time', 300, function () {
            return BankStatement::whereNotNull('ocr_completed_at')
                ->whereNotNull('ocr_started_at')
                ->where('ocr_status', 'completed')
                ->where('ocr_completed_at', '>=', now()->subDays(7))
                ->get()
                ->avg(function ($statement) {
                    return $statement->ocr_completed_at->diffInSeconds($statement->ocr_started_at);
                });
        });

        return view('dashboard.admin', compact(
            'stats',
            'ocrStatus',
            'recentTransactions',
            'recentStatements',
            'transactionsByType',
            'transactionsByCategory',
            'monthlyTrend',
            'bankDistribution',
            'recentUsers',
            'matchingStats',
            'topKeywords',
            'avgProcessingTime'
        ));
    }

    /**
     * User Dashboard with limited view (Optimized)
     */
    private function userDashboard()
    {
        // Cache statistics for 5 minutes
        $stats = Cache::remember('dashboard_user_stats_v2', 300, function () {
            return [
                'total_transactions' => DB::table('statement_transactions')
                    ->whereNull('deleted_at')
                    ->count(),
                'verified_transactions' => DB::table('statement_transactions')
                    ->where('is_verified', true)
                    ->whereNull('deleted_at')
                    ->count(),
                'unverified_transactions' => DB::table('statement_transactions')
                    ->where('is_verified', false)
                    ->whereNull('deleted_at')
                    ->count(),
                'total_bank_statements' => BankStatement::count(),
                'matched_transactions' => DB::table('statement_transactions')
                    ->whereNotNull('matched_keyword_id')
                    ->whereNull('deleted_at')
                    ->count(),
            ];
        });

        // Recent Transactions - Optimized
        $recentTransactions = StatementTransaction::select([
                'id',
                'bank_statement_id',
                'transaction_date',
                'transaction_time',
                'description',
                'debit_amount',
                'credit_amount',
                'transaction_type',
                'sub_category_id',
                'category_id',
                'type_id',
                'is_verified',
                'confidence_score',
                'created_at'
            ])
            ->with([
                'bankStatement:id,bank_id,original_filename',
                'bankStatement.bank:id,name,logo',
                'subCategory:id,name,category_id',
                'category:id,name,color',
                'type:id,name'
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        // Transaction by Type Chart Data - Cached
        $transactionsByType = Cache::remember('dashboard_user_transactions_by_type_v2', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'types.name',
                    DB::raw('COUNT(statement_transactions.id) as count'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "credit" THEN statement_transactions.credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "debit" THEN statement_transactions.debit_amount ELSE 0 END) as total_debit')
                )
                ->join('types', 'statement_transactions.type_id', '=', 'types.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('types.id', 'types.name')
                ->get();
        });

        // Transaction by Category
        $transactionsByCategory = Cache::remember('dashboard_user_transactions_by_category', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'categories.name',
                    'categories.color',
                    DB::raw('COUNT(statement_transactions.id) as count')
                )
                ->join('categories', 'statement_transactions.category_id', '=', 'categories.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('categories.id', 'categories.name', 'categories.color')
                ->orderBy('count', 'desc')
                ->limit(8)
                ->get();
        });

        // Monthly Trend for User
        $monthlyTrend = Cache::remember('dashboard_user_monthly_trend_v2', 300, function () {
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            return DB::table('statement_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN debit_amount ELSE 0 END) as total_debit')
                )
                ->where('transaction_date', '>=', $sixMonthsAgo)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->get();
        });

        // Recent Bank Statements
        $recentStatements = BankStatement::select([
                'id',
                'bank_id',
                'original_filename',
                'ocr_status',
                'total_transactions',
                'matched_transactions',
                'uploaded_at'
            ])
            ->with('bank:id,name,logo')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard.user', compact(
            'stats',
            'recentTransactions',
            'transactionsByType',
            'transactionsByCategory',
            'monthlyTrend',
            'recentStatements'
        ));
    }

    /**
     * Clear dashboard cache (untuk admin)
     */
    public function clearCache()
    {
        // Clear all dashboard caches
        $cacheKeys = [
            'dashboard_admin_stats_v2',
            'dashboard_ocr_status',
            'dashboard_transactions_by_type_v2',
            'dashboard_transactions_by_category',
            'dashboard_monthly_trend_v2',
            'dashboard_bank_distribution',
            'dashboard_matching_stats_v2',
            'dashboard_top_keywords',
            'dashboard_avg_processing_time',
            'dashboard_user_stats_v2',
            'dashboard_user_transactions_by_type_v2',
            'dashboard_user_transactions_by_category',
            'dashboard_user_monthly_trend_v2',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        return back()->with('success', 'Dashboard cache cleared successfully!');
    }

    /**
     * Get dashboard data as JSON (for AJAX refresh)
     */
    public function getStats(Request $request)
    {
        $type = $request->get('type', 'overview'); // overview, transactions, matching, ocr

        switch ($type) {
            case 'transactions':
                return response()->json([
                    'total' => StatementTransaction::count(),
                    'verified' => StatementTransaction::verified()->count(),
                    'unverified' => StatementTransaction::unverified()->count(),
                    'matched' => StatementTransaction::matched()->count(),
                    'unmatched' => StatementTransaction::unmatched()->count(),
                ]);

            case 'matching':
                $total = StatementTransaction::count();
                $matched = StatementTransaction::matched()->count();
                
                return response()->json([
                    'total' => $total,
                    'matched' => $matched,
                    'unmatched' => $total - $matched,
                    'percentage' => $total > 0 ? round(($matched / $total) * 100, 2) : 0,
                    'low_confidence' => StatementTransaction::where('confidence_score', '<', 70)
                        ->whereNotNull('matched_keyword_id')
                        ->count(),
                    'high_confidence' => StatementTransaction::where('confidence_score', '>=', 90)
                        ->whereNotNull('matched_keyword_id')
                        ->count(),
                ]);

            case 'ocr':
                return response()->json([
                    'pending' => BankStatement::where('ocr_status', 'pending')->count(),
                    'processing' => BankStatement::where('ocr_status', 'processing')->count(),
                    'completed' => BankStatement::where('ocr_status', 'completed')->count(),
                    'failed' => BankStatement::where('ocr_status', 'failed')->count(),
                ]);

            default: // overview
                return response()->json([
                    'users' => User::count(),
                    'banks' => Bank::count(),
                    'statements' => BankStatement::count(),
                    'transactions' => StatementTransaction::count(),
                    'types' => Type::count(),
                    'categories' => Category::count(),
                    'keywords' => Keyword::where('is_active', true)->count(),
                ]);
        }
    }
}