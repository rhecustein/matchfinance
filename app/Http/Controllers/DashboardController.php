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
     * Admin Dashboard with full statistics
     */
    private function adminDashboard()
    {
        // Statistics
        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::admins()->count(),
            'total_regular_users' => User::regularUsers()->count(),
            'total_banks' => Bank::count(),
            'total_bank_statements' => BankStatement::count(),
            'total_transactions' => StatementTransaction::count(),
            'verified_transactions' => StatementTransaction::where('is_verified', true)->count(),
            'unverified_transactions' => StatementTransaction::where('is_verified', false)->count(),
            'total_types' => Type::count(),
            'total_categories' => Category::count(),
        ];

        // Recent Transactions
        $recentTransactions = StatementTransaction::with([
            'bankStatement.bank',
            'subCategory.category.type',
            'verifiedBy'
        ])
        ->latest()
        ->limit(10)
        ->get();

        // Recent Bank Statements
        $recentStatements = BankStatement::with('bank')
            ->latest()
            ->limit(5)
            ->get();

        // Transaction by Type Chart Data
        $transactionsByType = StatementTransaction::select(
                'types.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->join('types', 'statement_transactions.type_id', '=', 'types.id')
            ->groupBy('types.id', 'types.name')
            ->get();

        // Monthly Transaction Trend (last 6 months)
        $monthlyTrend = StatementTransaction::select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('transaction_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Recent Users
        $recentUsers = User::latest()
            ->limit(5)
            ->get();

        return view('dashboard.admin', compact(
            'stats',
            'recentTransactions',
            'recentStatements',
            'transactionsByType',
            'monthlyTrend',
            'recentUsers'
        ));
    }

    /**
     * User Dashboard with limited view
     */
    private function userDashboard()
    {
        // Statistics for user (limited view)
        $stats = [
            'total_transactions' => StatementTransaction::count(),
            'verified_transactions' => StatementTransaction::where('is_verified', true)->count(),
            'unverified_transactions' => StatementTransaction::where('is_verified', false)->count(),
            'total_bank_statements' => BankStatement::count(),
        ];

        // Recent Transactions (user can only view)
        $recentTransactions = StatementTransaction::with([
            'bankStatement.bank',
            'subCategory.category.type',
            'verifiedBy'
        ])
        ->latest()
        ->limit(10)
        ->get();

        // Transaction by Type Chart Data
        $transactionsByType = StatementTransaction::select(
                'types.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->join('types', 'statement_transactions.type_id', '=', 'types.id')
            ->groupBy('types.id', 'types.name')
            ->get();

        return view('dashboard.user', compact(
            'stats',
            'recentTransactions',
            'transactionsByType'
        ));
    }
}