<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\BankStatement;
use App\Models\StatementTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Display admin dashboard with system-wide statistics
     */
    public function index()
    {
        // Ensure only super_admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Unauthorized access');

        // Company Statistics
        $companyStats = [
            'total' => Company::count(),
            'active' => Company::where('status', 'active')->count(),
            'trial' => Company::where('status', 'trial')->count(),
            'suspended' => Company::where('status', 'suspended')->count(),
            'cancelled' => Company::where('status', 'cancelled')->count(),
            'trial_expiring_soon' => Company::where('status', 'trial')
                ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                ->count(),
        ];

        // Subscription Statistics
        $subscriptionStats = [
            'total' => CompanySubscription::count(),
            'active' => CompanySubscription::where('status', 'active')->count(),
            'expired' => CompanySubscription::where('status', 'expired')->count(),
            'cancelled' => CompanySubscription::where('status', 'cancelled')->count(),
            'expiring_soon' => CompanySubscription::where('status', 'active')
                ->whereBetween('ends_at', [now(), now()->addDays(30)])
                ->count(),
        ];

        // Revenue Statistics (Monthly Recurring Revenue)
        $mrr = CompanySubscription::where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->billing_period === 'monthly' 
                    ? $subscription->plan->price 
                    : ($subscription->plan->price / 12);
            });

        $arr = $mrr * 12; // Annual Recurring Revenue

        // Plan Statistics
        $planStats = Plan::withCount(['activeSubscriptions'])
            ->get()
            ->map(function ($plan) {
                return [
                    'name' => $plan->name,
                    'subscribers' => $plan->active_subscriptions_count,
                    'price' => $plan->price,
                    'billing_period' => $plan->billing_period,
                ];
            });

        // User Statistics
        $userStats = [
            'total' => User::count(),
            'super_admins' => User::whereNull('company_id')->where('role', 'super_admin')->count(),
            'company_users' => User::whereNotNull('company_id')->count(),
            'active' => User::where('is_active', true)->count(),
            'suspended' => User::where('is_suspended', true)->count(),
        ];

        // Transaction Statistics (All Companies)
        $transactionStats = [
            'total' => StatementTransaction::count(),
            'this_month' => StatementTransaction::whereYear('transaction_date', now()->year)
                ->whereMonth('transaction_date', now()->month)
                ->count(),
            'verified' => StatementTransaction::where('is_verified', true)->count(),
            'matched' => StatementTransaction::whereNotNull('sub_category_id')->count(),
        ];

        // Recent Activities
        $recentCompanies = Company::with('owner')
            ->latest()
            ->limit(5)
            ->get();

        $recentSubscriptions = CompanySubscription::with(['company', 'plan'])
            ->latest()
            ->limit(5)
            ->get();

        // Monthly Growth (Last 6 months)
        $monthlyGrowth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyGrowth[] = [
                'month' => $date->format('M Y'),
                'companies' => Company::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'subscriptions' => CompanySubscription::whereYear('starts_at', $date->year)
                    ->whereMonth('starts_at', $date->month)
                    ->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'companyStats',
            'subscriptionStats',
            'mrr',
            'arr',
            'planStats',
            'userStats',
            'transactionStats',
            'recentCompanies',
            'recentSubscriptions',
            'monthlyGrowth'
        ));
    }

    /**
     * Get real-time statistics via AJAX
     */
    public function stats()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return response()->json([
            'companies' => [
                'total' => Company::count(),
                'active' => Company::where('status', 'active')->count(),
            ],
            'subscriptions' => [
                'active' => CompanySubscription::where('status', 'active')->count(),
            ],
            'users' => [
                'total' => User::count(),
                'online' => User::where('last_login_at', '>', now()->subMinutes(5))->count(),
            ],
        ]);
    }
}