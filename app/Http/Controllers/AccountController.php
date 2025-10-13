<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Account::where('company_id', $user->company_id)
            ->withCount('transactions');

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
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('code')->paginate(20);

        $stats = [
            'total' => Account::where('company_id', $user->company_id)->count(),
            'active' => Account::where('company_id', $user->company_id)->where('is_active', true)->count(),
            'inactive' => Account::where('company_id', $user->company_id)->where('is_active', false)->count(),
        ];

        return view('accounts.index', compact('accounts', 'stats'));
    }

    /**
     * Show the form for creating a new account
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'account_number' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account = Account::create([
            'uuid' => Str::uuid(),
            'company_id' => auth()->user()->company_id,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'account_number' => $validated['account_number'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('accounts.index')
            ->with('success', "Account '{$account->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified account
     */
    public function show(Account $account)
    {
        abort_unless($account->company_id === auth()->user()->company_id, 403);

        $account->loadCount('transactions');

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
        abort_unless($account->company_id === auth()->user()->company_id, 403);

        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified account
     */
    public function update(Request $request, Account $account)
    {
        abort_unless($account->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'account_number' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'account_number' => $validated['account_number'],
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
        abort_unless($account->company_id === auth()->user()->company_id, 403);

        // Check if account has transactions
        if ($account->transactions()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus account yang memiliki transactions.');
        }

        $accountName = $account->name;
        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', "Account '{$accountName}' berhasil dihapus.");
    }

    /**
     * Toggle account active status
     */
    public function toggleActive(Account $account)
    {
        abort_unless($account->company_id === auth()->user()->company_id, 403);

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Account '{$account->name}' berhasil {$status}.");
    }

    /**
     * API: Search accounts (for autocomplete)
     */
    public function apiSearch(Request $request)
    {
        $search = $request->get('q', '');
        
        $accounts = Account::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'uuid', 'code', 'name', 'account_type']);

        return response()->json($accounts);
    }
}