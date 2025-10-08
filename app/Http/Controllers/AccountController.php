<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountKeyword;
use App\Services\AccountMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    protected AccountMatchingService $matchingService;

    public function __construct(AccountMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Display listing of accounts
     */
    public function index(Request $request)
    {
        $query = Account::with(['keywords' => function ($q) {
            $q->where('is_active', true);
        }]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by type
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $accounts = $query->byPriority()->paginate(20)->withQueryString();

        // Get account types untuk filter
        $accountTypes = Account::distinct()->pluck('account_type')->filter();

        return view('accounts.index', compact('accounts', 'accountTypes'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store new account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:accounts,code',
            'description' => 'nullable|string',
            'account_type' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'keywords' => 'array',
            'keywords.*.keyword' => 'required|string',
            'keywords.*.match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'keywords.*.is_regex' => 'boolean',
            'keywords.*.case_sensitive' => 'boolean',
            'keywords.*.priority' => 'required|integer|min:1|max:10',
            'keywords.*.is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Create account
            $account = Account::create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'account_type' => $validated['account_type'] ?? null,
                'color' => $validated['color'] ?? '#3B82F6',
                'priority' => $validated['priority'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create keywords
            if (!empty($validated['keywords'])) {
                foreach ($validated['keywords'] as $keywordData) {
                    $account->keywords()->create([
                        'keyword' => $keywordData['keyword'],
                        'match_type' => $keywordData['match_type'],
                        'is_regex' => $keywordData['is_regex'] ?? false,
                        'case_sensitive' => $keywordData['case_sensitive'] ?? false,
                        'priority' => $keywordData['priority'],
                        'is_active' => $keywordData['is_active'] ?? true,
                    ]);
                }
            }

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            DB::commit();

            return redirect()
                ->route('accounts.show', $account)
                ->with('success', 'Account created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create account: ' . $e->getMessage());
        }
    }

    /**
     * Show account detail
     */
    public function show(Account $account)
    {
        $account->load([
            'keywords' => function ($q) {
                $q->orderBy('priority', 'desc');
            },
            'transactions' => function ($q) {
                $q->latest()->limit(10);
            }
        ]);

        // Get statistics
        $statistics = $this->matchingService->getAccountStatistics($account->id);

        return view('accounts.show', compact('account', 'statistics'));
    }

    /**
     * Show edit form
     */
    public function edit(Account $account)
    {
        $account->load('keywords');
        return view('accounts.edit', compact('account'));
    }

    /**
     * Update account
     */
    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:accounts,code,' . $account->id,
            'description' => 'nullable|string',
            'account_type' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $account->update($validated);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return redirect()
                ->route('accounts.show', $account)
                ->with('success', 'Account updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update account: ' . $e->getMessage());
        }
    }

    /**
     * Delete account
     */
    public function destroy(Account $account)
    {
        try {
            // Check if account has transactions
            $transactionCount = $account->transactions()->count();
            
            if ($transactionCount > 0) {
                return back()->with('error', "Cannot delete account with {$transactionCount} associated transactions.");
            }

            $account->delete();

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return redirect()
                ->route('accounts.index')
                ->with('success', 'Account deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    /**
     * Toggle account status
     */
    public function toggleStatus(Account $account)
    {
        try {
            $account->update(['is_active' => !$account->is_active]);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            $status = $account->is_active ? 'activated' : 'deactivated';
            return back()->with('success', "Account {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Rematch all transactions for this account
     */
    public function rematch(Account $account)
    {
        try {
            $transactionIds = $account->transactions()->pluck('id')->toArray();
            
            if (empty($transactionIds)) {
                return back()->with('info', 'No transactions to rematch for this account.');
            }

            $results = $this->matchingService->processBatchTransactions($transactionIds, true);

            return back()->with('success', "Rematched {$results['matched']} transactions successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to rematch transactions: ' . $e->getMessage());
        }
    }

    /**
     * Get account statistics
     */
    public function statistics(Account $account)
    {
        $statistics = $this->matchingService->getAccountStatistics($account->id);
        return response()->json($statistics);
    }
}