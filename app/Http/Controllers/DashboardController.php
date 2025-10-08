<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\Type;
use App\Models\Category;
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
        $stats = Cache::remember('dashboard_admin_stats', 300, function () {
            return [
                'total_users' => User::count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'total_regular_users' => User::where('role', 'user')->count(),
                'total_banks' => Bank::count(),
                'total_bank_statements' => BankStatement::count(),
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
                'total_types' => Type::count(),
                'total_categories' => Category::count(),
            ];
        });

        // Recent Transactions - Optimized with specific columns and index
        $recentTransactions = StatementTransaction::select([
                'id',
                'bank_statement_id',
                'transaction_date',
                'description',
                'amount',
                'transaction_type',
                'sub_category_id',
                'is_verified',
                'verified_by',
                'confidence_score',
                'created_at'
            ])
            ->with([
                'bankStatement:id,bank_id,original_filename',
                'bankStatement.bank:id,name,logo',
                'subCategory:id,name,category_id',
                'subCategory.category:id,name,type_id',
                'subCategory.category.type:id,name',
                'verifiedBy:id,name'
            ])
            ->latest('id') // Use primary key instead of created_at for better performance
            ->limit(10)
            ->get();

        // Recent Bank Statements - Optimized
        $recentStatements = BankStatement::select([
                'id',
                'bank_id',
                'original_filename',
                'ocr_status',
                'uploaded_at',
                'created_at'
            ])
            ->with('bank:id,name,logo')
            ->latest('id')
            ->limit(5)
            ->get();

        // Transaction by Type Chart Data - Optimized with proper indexing
        $transactionsByType = Cache::remember('dashboard_transactions_by_type', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'types.name',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('CAST(SUM(statement_transactions.amount) as DECIMAL(15,2)) as total_amount')
                )
                ->join('types', 'statement_transactions.type_id', '=', 'types.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('types.id', 'types.name')
                ->get();
        });

        // Monthly Transaction Trend (last 6 months) - Optimized
        $monthlyTrend = Cache::remember('dashboard_monthly_trend', 300, function () {
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            return DB::table('statement_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('CAST(SUM(amount) as DECIMAL(15,2)) as total_amount')
                )
                ->where('transaction_date', '>=', $sixMonthsAgo)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->get();
        });

        // Recent Users - Optimized
        $recentUsers = User::select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest('id')
            ->limit(5)
            ->get();

        // Matching Statistics
        $matchingStats = Cache::remember('dashboard_matching_stats', 300, function () {
            return [
                'matched_count' => DB::table('statement_transactions')
                    ->whereNotNull('matched_keyword_id')
                    ->whereNull('deleted_at')
                    ->count(),
                'unmatched_count' => DB::table('statement_transactions')
                    ->whereNull('matched_keyword_id')
                    ->whereNull('deleted_at')
                    ->count(),
                'low_confidence_count' => DB::table('statement_transactions')
                    ->where('confidence_score', '<', 80)
                    ->whereNotNull('matched_keyword_id')
                    ->whereNull('deleted_at')
                    ->count(),
            ];
        });

        return view('dashboard.admin', compact(
            'stats',
            'recentTransactions',
            'recentStatements',
            'transactionsByType',
            'monthlyTrend',
            'recentUsers',
            'matchingStats'
        ));
    }

    /**
     * User Dashboard with limited view (Optimized)
     */
    private function userDashboard()
    {
        // Cache statistics for 5 minutes
        $stats = Cache::remember('dashboard_user_stats', 300, function () {
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
            ];
        });

        // Recent Transactions - Optimized
        $recentTransactions = StatementTransaction::select([
                'id',
                'bank_statement_id',
                'transaction_date',
                'description',
                'amount',
                'transaction_type',
                'sub_category_id',
                'is_verified',
                'verified_by',
                'confidence_score',
                'created_at'
            ])
            ->with([
                'bankStatement:id,bank_id,original_filename',
                'bankStatement.bank:id,name,logo',
                'subCategory:id,name,category_id',
                'subCategory.category:id,name,type_id',
                'subCategory.category.type:id,name',
                'verifiedBy:id,name'
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        // Transaction by Type Chart Data - Cached
        $transactionsByType = Cache::remember('dashboard_user_transactions_by_type', 300, function () {
            return DB::table('statement_transactions')
                ->select(
                    'types.name',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('CAST(SUM(statement_transactions.amount) as DECIMAL(15,2)) as total_amount')
                )
                ->join('types', 'statement_transactions.type_id', '=', 'types.id')
                ->whereNull('statement_transactions.deleted_at')
                ->groupBy('types.id', 'types.name')
                ->get();
        });

        // Monthly Trend for User
        $monthlyTrend = Cache::remember('dashboard_user_monthly_trend', 300, function () {
            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            
            return DB::table('statement_transactions')
                ->select(
                    DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('CAST(SUM(amount) as DECIMAL(15,2)) as total_amount')
                )
                ->where('transaction_date', '>=', $sixMonthsAgo)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'))
                ->get();
        });

        return view('dashboard.user', compact(
            'stats',
            'recentTransactions',
            'transactionsByType',
            'monthlyTrend'
        ));
    }

    /**
     * Clear dashboard cache (untuk admin)
     */
    public function clearCache()
    {
        Cache::forget('dashboard_admin_stats');
        Cache::forget('dashboard_transactions_by_type');
        Cache::forget('dashboard_monthly_trend');
        Cache::forget('dashboard_matching_stats');
        Cache::forget('dashboard_user_stats');
        Cache::forget('dashboard_user_transactions_by_type');
        Cache::forget('dashboard_user_monthly_trend');

        return back()->with('success', 'Dashboard cache cleared successfully!');
    }
}