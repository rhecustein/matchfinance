<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts
     * 
     * - Super Admin: Can view accounts from ALL companies
     * - Regular roles: Only view accounts from their own company
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Build query based on user role
        if ($user->isSuperAdmin()) {
            // Super Admin: Access all companies
            $query = Account::withoutGlobalScope('company')
                ->with('company:id,name')
                ->withCount('transactions');
        } else {
            // Regular users: Only their company (automatic via BelongsToTenant trait)
            $query = Account::where('company_id', $user->company_id)
                ->withCount('transactions');
        }

        // Filter by company (Super Admin only)
        if ($user->isSuperAdmin() && $request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by account type
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('code')->paginate(20);

        // Stats based on role
        if ($user->isSuperAdmin()) {
            $statsQuery = Account::withoutGlobalScope('company');
            
            if ($request->filled('company_id')) {
                $statsQuery->where('company_id', $request->company_id);
            }
        } else {
            $statsQuery = Account::where('company_id', $user->company_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
        ];

        return view('accounts.index', compact('accounts', 'stats'));
    }

    /**
     * Show the form for creating a new account
     */
    public function create()
    {
        $user = auth()->user();

        // Check access permission
        abort_unless($user->hasAdminAccess(), 403, 'Anda tidak memiliki akses untuk membuat account.');

        return view('accounts.create');
    }

    /**
     * Store a newly created account
     * 
     * - Super Admin: Must specify company_id
     * - Regular roles: Auto-assigned to their company
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check access permission
        abort_unless($user->hasAdminAccess(), 403, 'Anda tidak memiliki akses untuk membuat account.');

        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // Super Admin must select company
        if ($user->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $validated = $request->validate($rules);

        // Determine company_id
        $companyId = $user->isSuperAdmin() 
            ? $validated['company_id'] 
            : $user->company_id;

        // Check for duplicate code within company
        $exists = Account::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('code', strtoupper($validated['code']))
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode account sudah digunakan di company ini.']);
        }

        $account = Account::create([
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('accounts.index')
            ->with('success', "Account '{$account->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified account
     * 
     * - Super Admin: Can view any account
     * - Regular roles: Only accounts from their company
     */
    public function show(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $account->loadCount('transactions');
        $account->load('company:id,name');

        // Get recent transactions
        $recentTransactions = $account->transactions()
            ->with('bankStatement.bank')
            ->latest('transaction_date')
            ->limit(15)
            ->get();

        // Get keywords
        $keywords = $account->keywords()
            ->orderBy('priority', 'desc')
            ->get();

        return view('accounts.show', compact('account', 'recentTransactions', 'keywords'));
    }

    /**
     * Show the form for editing the specified account
     */
    public function edit(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 'Anda tidak memiliki akses untuk mengedit account.');

        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $account->load('company:id,name');

        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified account
     */
    public function update(Request $request, Account $account)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 'Anda tidak memiliki akses untuk mengupdate account.');

        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate code within company (exclude current account)
        $exists = Account::withoutGlobalScope('company')
            ->where('company_id', $account->company_id)
            ->where('code', strtoupper($validated['code']))
            ->where('id', '!=', $account->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode account sudah digunakan di company ini.']);
        }

        $account->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? $account->is_active,
        ]);

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Account berhasil diupdate.');
    }

    /**
     * Remove the specified account
     */
    public function destroy(Account $account)
    {
        $user = auth()->user();

        // Authorization check - Only super admin and owner can delete
        abort_unless($user->isSuperAdmin() || $user->isOwner(), 403, 
            'Hanya Super Admin atau Owner yang dapat menghapus account.');

        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        // Check if account has transactions
        if ($account->transactions()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus account yang memiliki transactions.');
        }

        // Check if account has keywords
        if ($account->keywords()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus account yang memiliki keywords. Hapus keywords terlebih dahulu.');
        }

        $accountName = $account->name;
        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', "Account '{$accountName}' berhasil dihapus.");
    }

    /**
     * Toggle account active status
     */
    public function toggleStatus(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 'Anda tidak memiliki akses untuk mengubah status account.');

        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Account '{$account->name}' berhasil {$status}.");
    }

    /**
     * API: Search accounts (for autocomplete)
     * 
     * - Super Admin: Can search across all companies
     * - Regular roles: Only search in their company
     */
    public function apiSearch(Request $request)
    {
        $user = auth()->user();
        $search = $request->get('q', '');
        
        // Build query based on role
        if ($user->isSuperAdmin()) {
            $query = Account::withoutGlobalScope('company')
                ->with('company:id,name');
            
            // Optional company filter for super admin
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query = Account::where('company_id', $user->company_id);
        }

        $accounts = $query->where('is_active', true)
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'uuid', 'code', 'name', 'account_type', 'company_id']);

        return response()->json($accounts);
    }

    /**
     * Display account statistics
     */
    public function statistics(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $account->load('company:id,name');

        // Get transaction statistics
        $stats = [
            'total_transactions' => $account->transactions()->count(),
            'total_debit' => $account->transactions()->sum('debit_amount'),
            'total_credit' => $account->transactions()->sum('credit_amount'),
            'active_keywords' => $account->keywords()->where('is_active', true)->count(),
            'total_keywords' => $account->keywords()->count(),
            'last_transaction' => $account->transactions()->latest('transaction_date')->first(),
        ];

        return view('accounts.statistics', compact('account', 'stats'));
    }

    /**
     * Display account keywords
     */
    public function keywords(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $account->load(['company:id,name', 'keywords' => function($query) {
            $query->orderBy('priority', 'desc');
        }]);

        return view('accounts.keywords', compact('account'));
    }

    /**
     * Rematch transactions for this account
     */
    public function rematch(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 'Anda tidak memiliki akses untuk rematch account.');

        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        // TODO: Implement rematch logic
        // This should trigger the account matching service to re-match all transactions

        return back()->with('success', "Rematch untuk account '{$account->name}' berhasil dijadwalkan.");
    }
}