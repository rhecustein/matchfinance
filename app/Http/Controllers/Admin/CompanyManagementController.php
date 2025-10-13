<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class CompanyManagementController extends Controller
{
    /**
     * Display a listing of companies
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = Company::with(['owner', 'subscription.plan', 'users'])
            ->withCount(['users', 'bankStatements']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->paginate(20);

        $statusCounts = [
            'all' => Company::count(),
            'active' => Company::where('status', 'active')->count(),
            'trial' => Company::where('status', 'trial')->count(),
            'suspended' => Company::where('status', 'suspended')->count(),
            'cancelled' => Company::where('status', 'cancelled')->count(),
        ];

        return view('admin.companies.index', compact('companies', 'statusCounts'));
    }

    /**
     * Show the form for creating a new company
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plans = Plan::where('is_active', true)->get();

        return view('admin.companies.create', compact('plans'));
    }

    /**
     * Store a newly created company
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:companies,slug',
            'subdomain' => 'required|string|max:255|unique:companies,subdomain',
            'status' => 'required|in:active,trial,suspended,cancelled',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'plan_id' => 'nullable|exists:plans,id',
            
            // Owner details
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            // Create Company
            $company = Company::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug']),
                'subdomain' => Str::slug($validated['subdomain']),
                'status' => $validated['status'],
                'trial_ends_at' => $validated['status'] === 'trial' 
                    ? now()->addDays($validated['trial_days'] ?? 14)
                    : null,
            ]);

            // Create Owner User
            $owner = User::create([
                'uuid' => Str::uuid(),
                'company_id' => $company->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'role' => 'owner',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Create Subscription if plan selected
            if ($request->filled('plan_id')) {
                $plan = Plan::findOrFail($validated['plan_id']);
                
                $company->subscriptions()->create([
                    'uuid' => Str::uuid(),
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($plan->billing_period === 'monthly' ? 1 : 12),
                ]);

                // Update company status
                $company->update(['status' => 'active']);
            }

            DB::commit();

            return redirect()->route('admin.companies.index')
                ->with('success', "Company '{$company->name}' berhasil dibuat dengan owner: {$owner->email}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Gagal membuat company: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified company
     */
    public function show(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $company->load([
            'owner',
            'users' => fn($q) => $q->latest(),
            'subscription.plan',
            'subscriptions' => fn($q) => $q->with('plan')->latest(),
            'bankStatements' => fn($q) => $q->with('bank')->latest()->limit(10),
        ]);

        // Statistics
        $stats = [
            'total_users' => $company->users()->count(),
            'active_users' => $company->users()->where('is_active', true)->count(),
            'total_statements' => $company->bankStatements()->count(),
            'total_transactions' => DB::table('statement_transactions')
                ->where('company_id', $company->id)
                ->count(),
            'verified_transactions' => DB::table('statement_transactions')
                ->where('company_id', $company->id)
                ->where('is_verified', true)
                ->count(),
        ];

        return view('admin.companies.show', compact('company', 'stats'));
    }

    /**
     * Show the form for editing the specified company
     */
    public function edit(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.companies.edit', compact('company'));
    }

    /**
     * Update the specified company
     */
    public function update(Request $request, Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:companies,slug,' . $company->id,
            'subdomain' => 'required|string|max:255|unique:companies,subdomain,' . $company->id,
            'domain' => 'nullable|string|max:255|unique:companies,domain,' . $company->id,
            'logo' => 'nullable|image|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/logos');
            $validated['logo'] = str_replace('public/', 'storage/', $path);
        }

        $company->update($validated);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Company berhasil diupdate.');
    }

    /**
     * Remove the specified company
     */
    public function destroy(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        DB::beginTransaction();
        try {
            $companyName = $company->name;
            
            // Soft delete company (cascade will handle related data)
            $company->delete();

            DB::commit();

            return redirect()->route('admin.companies.index')
                ->with('success', "Company '{$companyName}' berhasil dihapus.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus company: ' . $e->getMessage());
        }
    }

    /**
     * Suspend company
     */
    public function suspend(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $company->suspend();

        return back()->with('success', "Company '{$company->name}' berhasil disuspend.");
    }

    /**
     * Activate company
     */
    public function activate(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $company->activate();

        return back()->with('success', "Company '{$company->name}' berhasil diaktifkan.");
    }

    /**
     * Cancel company subscription
     */
    public function cancel(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $company->cancel();

        return back()->with('success', "Company '{$company->name}' berhasil dibatalkan.");
    }

    /**
     * Get company statistics (AJAX)
     */
    public function stats(Company $company)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return response()->json([
            'users' => $company->users()->count(),
            'bank_statements' => $company->bankStatements()->count(),
            'transactions' => DB::table('statement_transactions')
                ->where('company_id', $company->id)
                ->count(),
            'storage_used_mb' => DB::table('bank_statements')
                ->where('company_id', $company->id)
                ->sum('file_size') / 1024 / 1024,
        ]);
    }
}