<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySubscription;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionManagementController
 * 
 * Khusus untuk Super Admin mengelola subscription semua company
 * 
 * Access Level: SUPER ADMIN ONLY (role = 'super_admin', company_id = NULL)
 * 
 * Features:
 * - View all subscriptions across all companies
 * - Filter by status, plan, company name
 * - View subscription details & history
 * - Cancel, renew, change plan
 * - Statistics dashboard
 */
class SubscriptionManagementController extends Controller
{
    /**
     * Display a listing of subscriptions
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // CRITICAL: Only Super Admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Access denied. Super Admin only.');

        // Query subscriptions dengan eager loading
        $query = CompanySubscription::with(['company', 'plan']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Search by company name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('company', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Pagination
        $subscriptions = $query->latest()->paginate(20);

        // Status statistics untuk filter tabs
        $statusCounts = [
            'all' => CompanySubscription::count(),
            'active' => CompanySubscription::where('status', 'active')->count(),
            'cancelled' => CompanySubscription::where('status', 'cancelled')->count(),
            'expired' => CompanySubscription::where('status', 'expired')->count(),
            'past_due' => CompanySubscription::where('status', 'past_due')->count(),
        ];

        // Available plans untuk filter
        $plans = Plan::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.subscriptions.index', compact(
            'subscriptions',
            'statusCounts',
            'plans'
        ));
    }

    /**
     * Display the specified subscription
     * 
     * @param CompanySubscription $subscription
     * @return \Illuminate\View\View
     */
    public function show(CompanySubscription $subscription)
    {
        // CRITICAL: Only Super Admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Access denied. Super Admin only.');

        // Load relationships
        $subscription->load([
            'company.owner',  // Company dengan owner user
            'plan'
        ]);

        // Subscription history untuk company ini
        $history = CompanySubscription::where('company_id', $subscription->company_id)
            ->with('plan')
            ->latest()
            ->get();

        return view('admin.subscriptions.show', compact(
            'subscription',
            'history'
        ));
    }

    /**
     * Cancel subscription
     * 
     * @param CompanySubscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(CompanySubscription $subscription)
    {
        // CRITICAL: Only Super Admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Access denied. Super Admin only.');

        // Validasi: Check apakah subscription bisa di-cancel
        if (!$subscription->canBeCancelled()) {
            Log::warning('Attempt to cancel non-cancellable subscription', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'super_admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Subscription ini tidak dapat dibatalkan. Status saat ini: ' . $subscription->status);
        }

        DB::beginTransaction();
        try {
            // Cancel subscription (akan set status = 'cancelled' dan cancelled_at = now())
            $subscription->cancel();

            // Log activity
            Log::info('Subscription cancelled by Super Admin', [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'company_name' => $subscription->company->name,
                'plan_name' => $subscription->plan->name,
                'super_admin_id' => auth()->id(),
                'super_admin_email' => auth()->user()->email,
            ]);

            DB::commit();

            return back()->with('success', 'Subscription berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'super_admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Gagal membatalkan subscription: ' . $e->getMessage());
        }
    }

    /**
     * Renew subscription
     * 
     * @param CompanySubscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function renew(CompanySubscription $subscription)
    {
        // CRITICAL: Only Super Admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Access denied. Super Admin only.');

        // Validasi: Check apakah subscription bisa di-renew
        if (!$subscription->canBeRenewed()) {
            Log::warning('Attempt to renew non-renewable subscription', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'super_admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Subscription ini tidak dapat diperpanjang. Status saat ini: ' . $subscription->status);
        }

        DB::beginTransaction();
        try {
            // Renew subscription
            // Method renew() akan:
            // - Set status = 'active'
            // - Update starts_at dan ends_at
            // - Clear cancelled_at
            $subscription->renew();

            // Log activity
            Log::info('Subscription renewed by Super Admin', [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'company_name' => $subscription->company->name,
                'plan_name' => $subscription->plan->name,
                'new_ends_at' => $subscription->ends_at,
                'super_admin_id' => auth()->id(),
                'super_admin_email' => auth()->user()->email,
            ]);

            DB::commit();

            return back()->with('success', 'Subscription berhasil diperpanjang hingga ' . $subscription->ends_at->format('d M Y'));

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to renew subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'super_admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Gagal memperpanjang subscription: ' . $e->getMessage());
        }
    }

    /**
     * Change subscription plan
     * 
     * @param Request $request
     * @param CompanySubscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePlan(Request $request, CompanySubscription $subscription)
    {
        // CRITICAL: Only Super Admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Access denied. Super Admin only.');

        // Validate input
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($validated['plan_id']);

        // Validasi: Check plan berbeda
        if ($subscription->plan_id === $newPlan->id) {
            return back()->with('warning', 'Subscription sudah menggunakan plan ini.');
        }

        // Store old plan untuk logging
        $oldPlan = $subscription->plan;

        DB::beginTransaction();
        try {
            // Switch plan (update plan_id)
            $subscription->switchPlan($newPlan);
            
            // Recalculate end date berdasarkan billing period plan baru
            $subscription->calculateEndDate();

            // Log activity
            Log::info('Subscription plan changed by Super Admin', [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'company_name' => $subscription->company->name,
                'old_plan' => $oldPlan->name,
                'new_plan' => $newPlan->name,
                'new_ends_at' => $subscription->ends_at,
                'super_admin_id' => auth()->id(),
                'super_admin_email' => auth()->user()->email,
            ]);

            DB::commit();

            return back()->with('success', "Plan berhasil diubah dari '{$oldPlan->name}' ke '{$newPlan->name}'.");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
                'super_admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Gagal mengubah plan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle subscription active status (activate/suspend)
     * 
     * @param CompanySubscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(CompanySubscription $subscription)
    {
        // CRITICAL: Only Super Admin can access
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Access denied. Super Admin only.');

        DB::beginTransaction();
        try {
            if ($subscription->isActive()) {
                // Suspend subscription
                $subscription->update(['status' => 'past_due']);
                $message = 'Subscription berhasil di-suspend.';
                $action = 'suspended';
            } else {
                // Activate subscription
                $subscription->activate();
                $message = 'Subscription berhasil diaktifkan.';
                $action = 'activated';
            }

            // Log activity
            Log::info("Subscription {$action} by Super Admin", [
                'subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'company_name' => $subscription->company->name,
                'new_status' => $subscription->status,
                'super_admin_id' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to toggle subscription status', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'super_admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Gagal mengubah status subscription: ' . $e->getMessage());
        }
    }
}