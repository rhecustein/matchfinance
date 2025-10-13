<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanManagementController extends Controller
{
    /**
     * Display a listing of plans
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plans = Plan::withCount(['subscriptions', 'activeSubscriptions'])
            ->get()
            ->groupBy('billing_period');

        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.plans.create');
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'is_active' => 'boolean',
            
            // Features
            'max_users' => 'required|integer|min:-1',
            'max_products' => 'required|integer|min:-1',
            'max_transactions' => 'required|integer|min:-1',
            'max_storage_mb' => 'required|integer|min:-1',
            'bank_statements' => 'boolean',
            'advanced_reports' => 'boolean',
            'api_access' => 'boolean',
            'priority_support' => 'boolean',
            'custom_branding' => 'boolean',
        ]);

        $plan = Plan::create([
            'uuid' => Str::uuid(),
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'billing_period' => $validated['billing_period'],
            'is_active' => $validated['is_active'] ?? true,
            'features' => [
                'max_users' => $validated['max_users'],
                'max_products' => $validated['max_products'],
                'max_transactions' => $validated['max_transactions'],
                'max_storage_mb' => $validated['max_storage_mb'],
                'bank_statements' => $validated['bank_statements'] ?? false,
                'advanced_reports' => $validated['advanced_reports'] ?? false,
                'api_access' => $validated['api_access'] ?? false,
                'priority_support' => $validated['priority_support'] ?? false,
                'custom_branding' => $validated['custom_branding'] ?? false,
            ],
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' berhasil dibuat.");
    }

    /**
     * Display the specified plan
     */
    public function show(Plan $plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plan->loadCount(['subscriptions', 'activeSubscriptions']);
        
        $subscribers = $plan->activeSubscriptions()
            ->with('company')
            ->latest()
            ->paginate(20);

        // Revenue stats
        $monthlyRevenue = $plan->billing_period === 'monthly' 
            ? $plan->price * $plan->active_subscriptions_count
            : ($plan->price / 12) * $plan->active_subscriptions_count;

        return view('admin.plans.show', compact('plan', 'subscribers', 'monthlyRevenue'));
    }

    /**
     * Show the form for editing the specified plan
     */
    public function edit(Plan $plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update the specified plan
     */
    public function update(Request $request, Plan $plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'is_active' => 'boolean',
            
            // Features
            'max_users' => 'required|integer|min:-1',
            'max_products' => 'required|integer|min:-1',
            'max_transactions' => 'required|integer|min:-1',
            'max_storage_mb' => 'required|integer|min:-1',
            'bank_statements' => 'boolean',
            'advanced_reports' => 'boolean',
            'api_access' => 'boolean',
            'priority_support' => 'boolean',
            'custom_branding' => 'boolean',
        ]);

        $plan->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $plan->slug,
            'description' => $validated['description'],
            'price' => $validated['price'],
            'billing_period' => $validated['billing_period'],
            'is_active' => $validated['is_active'] ?? $plan->is_active,
            'features' => [
                'max_users' => $validated['max_users'],
                'max_products' => $validated['max_products'],
                'max_transactions' => $validated['max_transactions'],
                'max_storage_mb' => $validated['max_storage_mb'],
                'bank_statements' => $validated['bank_statements'] ?? false,
                'advanced_reports' => $validated['advanced_reports'] ?? false,
                'api_access' => $validated['api_access'] ?? false,
                'priority_support' => $validated['priority_support'] ?? false,
                'custom_branding' => $validated['custom_branding'] ?? false,
            ],
        ]);

        return redirect()->route('admin.plans.show', $plan)
            ->with('success', 'Plan berhasil diupdate.');
    }

    /**
     * Remove the specified plan
     */
    public function destroy(Plan $plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Check if plan has active subscriptions
        if ($plan->activeSubscriptions()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus plan yang masih memiliki subscriber aktif.');
        }

        $planName = $plan->name;
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$planName}' berhasil dihapus.");
    }

    /**
     * Toggle plan active status
     */
    public function toggleActive(Plan $plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Plan '{$plan->name}' berhasil {$status}.");
    }

    /**
     * Get plan subscribers
     */
    public function subscribers(Plan $plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $subscribers = $plan->subscriptions()
            ->with(['company' => fn($q) => $q->withCount('users')])
            ->latest()
            ->paginate(50);

        return view('admin.plans.subscribers', compact('plan', 'subscribers'));
    }
}