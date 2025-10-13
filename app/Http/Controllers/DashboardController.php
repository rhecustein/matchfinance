<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bank;
use App\Models\Company;
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
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }
        
        return $this->userDashboard();
    }

    /**
     * Super Admin Dashboard - All Companies Statistics
     */
    private function superAdminDashboard()
    {
        $cacheKey = 'dashboard_super_admin_stats_v3';
        
        // Cache statistics for 5 minutes
        $stats = Cache::remember($cacheKey, 300, function () {
            return [
                // Company Statistics
                'total_companies' => Company::count(),
                'active_companies' => Company::where('status', 'active')->count(),
                'trial_companies' => Company::where('status', 'trial')->count(),
                'suspended_companies' => Company::where('status', 'suspended')->count(),
                
                // User Statistics (All Companies)
                'total_users' => User::count(),
                'total_admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
                'total_regular_users' => User::where('role', 'user')->count(),
                'active_users' => User::where('is_active', true)->count(),
                
                // Global Bank Data (Not Company Specific)
                'total_banks' => Bank::count(),
                'active_banks' => Bank::where('is_active', true)->count(),
                
                // Bank Statements (All Companies)
                'total_bank_statements' => BankStatement::count(),
                'pending_ocr' => BankStatement::where('ocr_status', 'pending')->count(),
                'processing_ocr' => BankStatement::where('ocr_status', 'processing')->count(),
                'completed_ocr' => BankStatement::where('ocr_status', 'completed')->count(),
                'failed_ocr' => BankStatement::where('ocr_status', 'failed')->count(),
                
                // Transaction Statistics (All Companies)
                'total_transactions' => StatementTransaction::count(),
                'verified_transactions' => StatementTransaction::where('is_verified', true)->count(),
                'unverified_transactions' => StatementTransaction::where('is_verified', false)->count(),
                
                // Master Data Statistics (Company Specific)
                'total_types' => Type::count(),
                'total_categories' => Category::count(),
                'total_sub_categories' => SubCategory::count(),
                'total_keywords' => Keyword::count(),
                'active_keywords' => Keyword::where('is_active', true)->count(),
            ];
        });

        // Company List with Stats
        $companies = Company::withCount([
            'users',
            'bankStatements',
            'types',
            'categories'
        ])->orderBy('name')->get();

        // Recent Activities (All Companies)
        $recentStatements = BankStatement::with([
                'bank:id,name,logo',
                'user:id,name,email,company_id',
                'user.company:id,name',
                'company:id,name'
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        // Bank Distribution (Global - All Companies)
        $bankDistribution = Cache::remember('dashboard_super_bank_distribution', 300, function () {
            return DB::table('bank_statements')
                ->select(
                    'banks.name',
                    'banks.slug',
                    'banks.logo',
                    DB::raw('COUNT(bank_statements.id) as statement_count'),
                    DB::raw('SUM(bank_statements.total_transactions) as transaction_count')
                )
                ->join('banks', 'bank_statements.bank_id', '=', 'banks.id')
                ->groupBy('banks.id', 'banks.name', 'banks.slug', 'banks.logo')
                ->orderBy('statement_count', 'desc')
                ->get();
        });

        // OCR Processing Status
        $ocrStatus = BankStatement::select('ocr_status', DB::raw('count(*) as count'))
            ->groupBy('ocr_status')
            ->pluck('count', 'ocr_status')
            ->toArray();

        // Company Performance
        $companyPerformance = Cache::remember('dashboard_company_performance', 300, function () {
            return Company::select('companies.*')
                ->withCount([
                    'bankStatements',
                    'users',
                    'types',
                    'categories'
                ])
                ->with(['subscription'])
                ->orderBy('bank_statements_count', 'desc')
                ->limit(10)
                ->get();
        });

        return view('dashboard.super-admin', compact(
            'stats',
            'companies',
            'recentStatements',
            'bankDistribution',
            'ocrStatus',
            'companyPerformance'
        ));
    }

    /**
     * Admin Dashboard - Company Scoped Statistics
     */
    private function adminDashboard()
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $cacheKey = "dashboard_admin_stats_company_{$companyId}_v3";
        
        // Cache statistics for 5 minutes (Company Scoped)
        $stats = Cache::remember($cacheKey, 300, function () use ($companyId) {
            return [
                // User Statistics (Company Scoped)
                'total_users' => User::where('company_id', $companyId)->count(),
                'total_admins' => User::where('company_id', $companyId)->where('role', 'admin')->count(),
                'total_regular_users' => User::where('company_id', $companyId)->where('role', 'user')->count(),
                'active_users' => User::where('company_id', $companyId)->where('is_active', true)->count(),
                
                // Global Banks (Not Company Specific)
                'total_banks' => Bank::where('is_active', true)->count(),
                
                // Bank Statements (Company Scoped)
                'total_bank_statements' => BankStatement::where('company_id', $companyId)->count(),
                'pending_ocr' => BankStatement::where('company_id', $companyId)->where('ocr_status', 'pending')->count(),
                'processing_ocr' => BankStatement::where('company_id', $companyId)->where('ocr_status', 'processing')->count(),
                'completed_ocr' => BankStatement::where('company_id', $companyId)->where('ocr_status', 'completed')->count(),
                'failed_ocr' => BankStatement::where('company_id', $companyId)->where('ocr_status', 'failed')->count(),
                
                // Transaction Statistics (Company Scoped)
                'total_transactions' => StatementTransaction::where('company_id', $companyId)->count(),
                'verified_transactions' => StatementTransaction::where('company_id', $companyId)->where('is_verified', true)->count(),
                'unverified_transactions' => StatementTransaction::where('company_id', $companyId)->where('is_verified', false)->count(),
                
                // Master Data Statistics (Company Scoped)
                'total_types' => Type::where('company_id', $companyId)->count(),
                'total_categories' => Category::where('company_id', $companyId)->count(),
                'total_sub_categories' => SubCategory::where('company_id', $companyId)->count(),
                'total_keywords' => Keyword::where('company_id', $companyId)->count(),
                'active_keywords' => Keyword::where('company_id', $companyId)->where('is_active', true)->count(),
            ];
        });

        // OCR Processing Status (Company Scoped)
        $ocrStatus = Cache::remember("dashboard_ocr_status_company_{$companyId}", 60, function () use ($companyId) {
            return BankStatement::where('company_id', $companyId)
                ->select('ocr_status', DB::raw('count(*) as count'))
                ->groupBy('ocr_status')
                ->pluck('count', 'ocr_status')
                ->toArray();
        });

        // Recent Transactions (Company Scoped)
        $recentTransactions = StatementTransaction::where('company_id', $companyId)
            ->with([
                'bankStatement:id,bank_id,original_filename,account_number,company_id',
                'bankStatement.bank:id,name,logo,slug',
                'subCategory:id,name,category_id',
                'category:id,name,type_id,color',
                'type:id,name',
                'verifiedBy:id,name'
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        // Recent Bank Statements (Company Scoped)
        $recentStatements = BankStatement::where('company_id', $companyId)
            ->with([
                'bank:id,name,logo,slug',
                'user:id,name,email'
            ])
            ->latest('id')
            ->limit(8)
            ->get();

        // Transaction by Type Chart Data (Company Scoped)
        $transactionsByType = Cache::remember("dashboard_transactions_by_type_company_{$companyId}_v3", 300, function () use ($companyId) {
            return DB::table('statement_transactions')
                ->select(
                    'types.name',
                    'types.id',
                    DB::raw('COUNT(statement_transactions.id) as count'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "credit" THEN statement_transactions.credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "debit" THEN statement_transactions.debit_amount ELSE 0 END) as total_debit')
                )
                ->join('types', 'statement_transactions.type_id', '=', 'types.id')
                ->where('statement_transactions.company_id', $companyId)
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('types.id', 'types.name')
                ->orderBy('count', 'desc')
                ->get();
        });

        // Transaction by Category (Company Scoped)
        $transactionsByCategory = Cache::remember("dashboard_transactions_by_category_company_{$companyId}", 300, function () use ($companyId) {
            return DB::table('statement_transactions')
                ->select(
                    'categories.name',
                    'categories.color',
                    DB::raw('COUNT(statement_transactions.id) as count'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "credit" THEN statement_transactions.credit_amount ELSE statement_transactions.debit_amount END) as total_amount')
                )
                ->join('categories', 'statement_transactions.category_id', '=', 'categories.id')
                ->where('statement_transactions.company_id', $companyId)
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('categories.id', 'categories.name', 'categories.color')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
        });

        // Monthly Transaction Trend (Company Scoped)
        $monthlyTrend = Cache::remember("dashboard_monthly_trend_company_{$companyId}_v3", 300, function () use ($companyId) {
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            return DB::table('statement_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN debit_amount ELSE 0 END) as total_debit')
                )
                ->where('company_id', $companyId)
                ->where('transaction_date', '>=', $sixMonthsAgo)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->get();
        });

        // Bank Distribution (Company Scoped - Global Banks)
        $bankDistribution = Cache::remember("dashboard_bank_distribution_company_{$companyId}", 300, function () use ($companyId) {
            return DB::table('bank_statements')
                ->select(
                    'banks.name',
                    'banks.slug',
                    'banks.logo',
                    DB::raw('COUNT(bank_statements.id) as statement_count'),
                    DB::raw('SUM(bank_statements.total_transactions) as transaction_count')
                )
                ->join('banks', 'bank_statements.bank_id', '=', 'banks.id')
                ->where('bank_statements.company_id', $companyId)
                ->groupBy('banks.id', 'banks.name', 'banks.slug', 'banks.logo')
                ->orderBy('statement_count', 'desc')
                ->get();
        });

        // Recent Users (Company Scoped)
        $recentUsers = User::where('company_id', $companyId)
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest('id')
            ->limit(5)
            ->get();

        // Matching Statistics (Company Scoped)
        $matchingStats = Cache::remember("dashboard_matching_stats_company_{$companyId}_v3", 300, function () use ($companyId) {
            $total = StatementTransaction::where('company_id', $companyId)->count();
            $matched = StatementTransaction::where('company_id', $companyId)->whereNotNull('matched_keyword_id')->count();
            $unmatched = $total - $matched;
            
            $lowConfidence = StatementTransaction::where('company_id', $companyId)
                ->where('confidence_score', '<', 70)
                ->whereNotNull('matched_keyword_id')
                ->count();
            
            $highConfidence = StatementTransaction::where('company_id', $companyId)
                ->where('confidence_score', '>=', 90)
                ->whereNotNull('matched_keyword_id')
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

        // Top Matched Keywords (Company Scoped)
        $topKeywords = Cache::remember("dashboard_top_keywords_company_{$companyId}", 300, function () use ($companyId) {
            return DB::table('statement_transactions')
                ->select(
                    'keywords.keyword',
                    'keywords.id',
                    'sub_categories.name as sub_category_name',
                    DB::raw('COUNT(statement_transactions.id) as match_count')
                )
                ->join('keywords', 'statement_transactions.matched_keyword_id', '=', 'keywords.id')
                ->join('sub_categories', 'keywords.sub_category_id', '=', 'sub_categories.id')
                ->where('statement_transactions.company_id', $companyId)
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('keywords.id', 'keywords.keyword', 'sub_categories.name')
                ->orderBy('match_count', 'desc')
                ->limit(10)
                ->get();
        });

        // Average Processing Time for OCR (Company Scoped)
        $avgProcessingTime = Cache::remember("dashboard_avg_processing_time_company_{$companyId}", 300, function () use ($companyId) {
            return BankStatement::where('company_id', $companyId)
                ->whereNotNull('ocr_completed_at')
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
     * User Dashboard - Company Scoped, Limited View
     */
    private function userDashboard()
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $cacheKey = "dashboard_user_stats_company_{$companyId}_v3";
        
        // Cache statistics for 5 minutes (Company Scoped)
        $stats = Cache::remember($cacheKey, 300, function () use ($companyId) {
            return [
                'total_transactions' => StatementTransaction::where('company_id', $companyId)->count(),
                'verified_transactions' => StatementTransaction::where('company_id', $companyId)->where('is_verified', true)->count(),
                'unverified_transactions' => StatementTransaction::where('company_id', $companyId)->where('is_verified', false)->count(),
                'total_bank_statements' => BankStatement::where('company_id', $companyId)->count(),
                'matched_transactions' => StatementTransaction::where('company_id', $companyId)->whereNotNull('matched_keyword_id')->count(),
            ];
        });

        // Recent Transactions (Company Scoped)
        $recentTransactions = StatementTransaction::where('company_id', $companyId)
            ->with([
                'bankStatement:id,bank_id,original_filename,company_id',
                'bankStatement.bank:id,name,logo',
                'subCategory:id,name,category_id',
                'category:id,name,color',
                'type:id,name'
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        // Transaction by Type (Company Scoped)
        $transactionsByType = Cache::remember("dashboard_user_transactions_by_type_company_{$companyId}_v3", 300, function () use ($companyId) {
            return DB::table('statement_transactions')
                ->select(
                    'types.name',
                    DB::raw('COUNT(statement_transactions.id) as count'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "credit" THEN statement_transactions.credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN statement_transactions.transaction_type = "debit" THEN statement_transactions.debit_amount ELSE 0 END) as total_debit')
                )
                ->join('types', 'statement_transactions.type_id', '=', 'types.id')
                ->where('statement_transactions.company_id', $companyId)
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('types.id', 'types.name')
                ->get();
        });

        // Transaction by Category (Company Scoped)
        $transactionsByCategory = Cache::remember("dashboard_user_transactions_by_category_company_{$companyId}", 300, function () use ($companyId) {
            return DB::table('statement_transactions')
                ->select(
                    'categories.name',
                    'categories.color',
                    DB::raw('COUNT(statement_transactions.id) as count')
                )
                ->join('categories', 'statement_transactions.category_id', '=', 'categories.id')
                ->where('statement_transactions.company_id', $companyId)
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('categories.id', 'categories.name', 'categories.color')
                ->orderBy('count', 'desc')
                ->limit(8)
                ->get();
        });

        // Monthly Trend (Company Scoped)
        $monthlyTrend = Cache::remember("dashboard_user_monthly_trend_company_{$companyId}_v3", 300, function () use ($companyId) {
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            return DB::table('statement_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN credit_amount ELSE 0 END) as total_credit'),
                    DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN debit_amount ELSE 0 END) as total_debit')
                )
                ->where('company_id', $companyId)
                ->where('transaction_date', '>=', $sixMonthsAgo)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->get();
        });

        // Recent Bank Statements (Company Scoped)
        $recentStatements = BankStatement::where('company_id', $companyId)
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
     * Clear dashboard cache (untuk admin & super admin)
     */
    public function clearCache()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            // Clear all caches
            Cache::flush();
        } else {
            // Clear company-specific caches
            $companyId = $user->company_id;
            $cacheKeys = [
                "dashboard_admin_stats_company_{$companyId}_v3",
                "dashboard_ocr_status_company_{$companyId}",
                "dashboard_transactions_by_type_company_{$companyId}_v3",
                "dashboard_transactions_by_category_company_{$companyId}",
                "dashboard_monthly_trend_company_{$companyId}_v3",
                "dashboard_bank_distribution_company_{$companyId}",
                "dashboard_matching_stats_company_{$companyId}_v3",
                "dashboard_top_keywords_company_{$companyId}",
                "dashboard_avg_processing_time_company_{$companyId}",
                "dashboard_user_stats_company_{$companyId}_v3",
                "dashboard_user_transactions_by_type_company_{$companyId}_v3",
                "dashboard_user_transactions_by_category_company_{$companyId}",
                "dashboard_user_monthly_trend_company_{$companyId}_v3",
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        }

        return back()->with('success', 'Dashboard cache cleared successfully!');
    }

    /**
     * Get dashboard data as JSON (for AJAX refresh)
     */
    public function getStats(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $type = $request->get('type', 'overview');

        // Super Admin: All data
        // Regular User/Admin: Company scoped
        $query = $user->isSuperAdmin() 
            ? StatementTransaction::query()
            : StatementTransaction::where('company_id', $companyId);

        switch ($type) {
            case 'transactions':
                return response()->json([
                    'total' => (clone $query)->count(),
                    'verified' => (clone $query)->where('is_verified', true)->count(),
                    'unverified' => (clone $query)->where('is_verified', false)->count(),
                    'matched' => (clone $query)->whereNotNull('matched_keyword_id')->count(),
                    'unmatched' => (clone $query)->whereNull('matched_keyword_id')->count(),
                ]);

            case 'matching':
                $total = (clone $query)->count();
                $matched = (clone $query)->whereNotNull('matched_keyword_id')->count();
                
                return response()->json([
                    'total' => $total,
                    'matched' => $matched,
                    'unmatched' => $total - $matched,
                    'percentage' => $total > 0 ? round(($matched / $total) * 100, 2) : 0,
                    'low_confidence' => (clone $query)
                        ->where('confidence_score', '<', 70)
                        ->whereNotNull('matched_keyword_id')
                        ->count(),
                    'high_confidence' => (clone $query)
                        ->where('confidence_score', '>=', 90)
                        ->whereNotNull('matched_keyword_id')
                        ->count(),
                ]);

            case 'ocr':
                $statementQuery = $user->isSuperAdmin() 
                    ? BankStatement::query()
                    : BankStatement::where('company_id', $companyId);

                return response()->json([
                    'pending' => (clone $statementQuery)->where('ocr_status', 'pending')->count(),
                    'processing' => (clone $statementQuery)->where('ocr_status', 'processing')->count(),
                    'completed' => (clone $statementQuery)->where('ocr_status', 'completed')->count(),
                    'failed' => (clone $statementQuery)->where('ocr_status', 'failed')->count(),
                ]);

            default: // overview
                if ($user->isSuperAdmin()) {
                    return response()->json([
                        'companies' => Company::count(),
                        'users' => User::count(),
                        'banks' => Bank::count(),
                        'statements' => BankStatement::count(),
                        'transactions' => StatementTransaction::count(),
                    ]);
                }

                return response()->json([
                    'users' => User::where('company_id', $companyId)->count(),
                    'banks' => Bank::where('is_active', true)->count(),
                    'statements' => BankStatement::where('company_id', $companyId)->count(),
                    'transactions' => StatementTransaction::where('company_id', $companyId)->count(),
                    'types' => Type::where('company_id', $companyId)->count(),
                    'categories' => Category::where('company_id', $companyId)->count(),
                    'keywords' => Keyword::where('company_id', $companyId)->where('is_active', true)->count(),
                ]);
        }
    }
}