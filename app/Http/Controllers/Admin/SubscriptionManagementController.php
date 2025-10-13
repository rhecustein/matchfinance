<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySubscription;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementController extends Controller
{
    /**
     * Display a listing of subscriptions
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = CompanySubscription::with(['company', 'plan']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('company', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()->paginate(20);

        $statusCounts = [
            'all' => CompanySubscription::count(),
            'active' => CompanySubscription::where('status', 'active')->count(),
            'cancelled' => CompanySubscription::where('status', 'cancelled')->count(),
            'expired' => CompanySubscription::where('status', 'expired')->count(),
            'past_due' => CompanySubscription::where('status', 'past_due')->count(),
        ];

        $plans = Plan::where('is_active', true)->get(['id', 'name']);

        return view('admin.subscriptions.index', compact('subscriptions', 'statusCounts', 'plans'));
    }

    /**
     * Display the specified subscription
     */
    public function show(CompanySubscription $subscription)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $subscription->load(['company.owner', 'plan']);

        // History
        $history = CompanySubscription::where('company_id', $subscription->company_id)
            ->with('plan')
            ->latest()
            ->get();

        return view('admin.subscriptions.show', compact('subscription', 'history'));
    }

    /**
     * Cancel subscription
     */
    public function cancel(CompanySubscription $subscription)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if (!$subscription->canBeCancelled()) {
            return back()->with('error', 'Subscription ini tidak dapat dibatalkan.');
        }

        $subscription->cancel();

        return back()->with('success', 'Subscription berhasil dibatalkan.');
    }

    /**
     * Renew subscription
     */
    public function renew(CompanySubscription $subscription)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if (!$subscription->canBeRenewed()) {
            return back()->with('error', 'Subscription ini tidak dapat diperpanjang.');
        }

        $subscription->renew();

        return back()->with('success', 'Subscription berhasil diperpanjang.');
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Request $request, CompanySubscription $subscription)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($validated['plan_id']);

        DB::beginTransaction();
        try {
            // Update subscription
            $subscription->switchPlan($newPlan);
            $subscription->calculateEndDate();

            DB::commit();

            return back()->with('success', "Plan berhasil diubah ke '{$newPlan->name}'.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengubah plan: ' . $e->getMessage());
        }
    }
}