<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountKeyword;
use App\Services\AccountMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountKeywordController extends Controller
{
    protected AccountMatchingService $matchingService;

    public function __construct(AccountMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Display keywords for an account
     */
    public function index(Account $account)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke account ini.');
        }

        $account->load('company:id,name');
        
        $keywords = $account->keywords()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('account-keywords.index', compact('account', 'keywords'));
    }

    /**
     * Show form to create new keyword
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // Check permission
        abort_unless($user->hasAdminAccess(), 403, 
            'Anda tidak memiliki akses untuk menambah keyword.');

        // Get account_uuid from request
        $accountUuid = $request->query('account');
        
        if ($accountUuid) {
            if ($user->isSuperAdmin()) {
                $account = Account::withoutGlobalScope('company')
                    ->where('uuid', $accountUuid)
                    ->firstOrFail();
            } else {
                $account = Account::where('company_id', $user->company_id)
                    ->where('uuid', $accountUuid)
                    ->firstOrFail();
            }

            return view('account-keywords.create', compact('account'));
        }

        // If no account specified, show account selection
        if ($user->isSuperAdmin()) {
            $accounts = Account::withoutGlobalScope('company')
                ->with('company:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $accounts = Account::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return view('account-keywords.create', compact('accounts'));
    }

    /**
     * Store a new keyword for an account
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check permission
        abort_unless($user->hasAdminAccess(), 403, 
            'Anda tidak memiliki akses untuk menambah keyword.');

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'pattern_description' => 'nullable|string|max:500',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        // Get account and verify ownership
        if ($user->isSuperAdmin()) {
            $account = Account::withoutGlobalScope('company')
                ->findOrFail($validated['account_id']);
        } else {
            $account = Account::where('company_id', $user->company_id)
                ->findOrFail($validated['account_id']);
        }

        try {
            $keyword = AccountKeyword::create([
                'uuid' => Str::uuid(),
                'company_id' => $account->company_id,
                'account_id' => $account->id,
                'keyword' => $validated['keyword'],
                'match_type' => $validated['match_type'],
                'pattern_description' => $validated['pattern_description'] ?? null,
                'is_regex' => $validated['is_regex'] ?? false,
                'case_sensitive' => $validated['case_sensitive'] ?? false,
                'priority' => $validated['priority'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return redirect()->route('accounts.show', $account)
                ->with('success', "Keyword '{$keyword->keyword}' berhasil ditambahkan!");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan keyword: ' . $e->getMessage());
        }
    }

    /**
     * Show form to edit keyword
     */
    public function edit(AccountKeyword $accountKeyword)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 
            'Anda tidak memiliki akses untuk mengedit keyword.');

        if (!$user->isSuperAdmin()) {
            abort_unless($accountKeyword->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke keyword ini.');
        }

        $accountKeyword->load('account.company');

        return view('account-keywords.edit', compact('accountKeyword'));
    }

    /**
     * Update a keyword
     */
    public function update(Request $request, AccountKeyword $accountKeyword)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 
            'Anda tidak memiliki akses untuk mengupdate keyword.');

        if (!$user->isSuperAdmin()) {
            abort_unless($accountKeyword->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke keyword ini.');
        }

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'pattern_description' => 'nullable|string|max:500',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $accountKeyword->update([
                'keyword' => $validated['keyword'],
                'match_type' => $validated['match_type'],
                'pattern_description' => $validated['pattern_description'] ?? null,
                'is_regex' => $validated['is_regex'] ?? false,
                'case_sensitive' => $validated['case_sensitive'] ?? false,
                'priority' => $validated['priority'],
                'is_active' => $validated['is_active'] ?? $accountKeyword->is_active,
            ]);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return redirect()->route('accounts.show', $accountKeyword->account)
                ->with('success', 'Keyword berhasil diupdate!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal mengupdate keyword: ' . $e->getMessage());
        }
    }

    /**
     * Delete a keyword
     */
    public function destroy(AccountKeyword $accountKeyword)
    {
        $user = auth()->user();

        // Authorization check - Only super admin and owner can delete
        abort_unless($user->isSuperAdmin() || $user->isOwner(), 403, 
            'Hanya Super Admin atau Owner yang dapat menghapus keyword.');

        if (!$user->isSuperAdmin()) {
            abort_unless($accountKeyword->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke keyword ini.');
        }

        try {
            $keywordText = $accountKeyword->keyword;
            $account = $accountKeyword->account;
            
            $accountKeyword->delete();

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return redirect()->route('accounts.show', $account)
                ->with('success', "Keyword '{$keywordText}' berhasil dihapus!");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus keyword: ' . $e->getMessage());
        }
    }

    /**
     * Toggle keyword status
     */
    public function toggleStatus(AccountKeyword $accountKeyword)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403, 
            'Anda tidak memiliki akses untuk mengubah status keyword.');

        if (!$user->isSuperAdmin()) {
            abort_unless($accountKeyword->company_id === $user->company_id, 403, 
                'Anda tidak memiliki akses ke keyword ini.');
        }

        try {
            $accountKeyword->update(['is_active' => !$accountKeyword->is_active]);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            $status = $accountKeyword->is_active ? 'diaktifkan' : 'dinonaktifkan';
            
            return back()->with('success', "Keyword '{$accountKeyword->keyword}' berhasil {$status}!");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    /**
     * Get keyword details (for AJAX)
     */
    public function show(AccountKeyword $accountKeyword)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->isSuperAdmin()) {
            abort_unless($accountKeyword->company_id === $user->company_id, 403);
        }

        return response()->json([
            'id' => $accountKeyword->id,
            'uuid' => $accountKeyword->uuid,
            'keyword' => $accountKeyword->keyword,
            'match_type' => $accountKeyword->match_type,
            'pattern_description' => $accountKeyword->pattern_description,
            'is_regex' => $accountKeyword->is_regex,
            'case_sensitive' => $accountKeyword->case_sensitive,
            'priority' => $accountKeyword->priority,
            'is_active' => $accountKeyword->is_active,
            'match_count' => $accountKeyword->match_count ?? 0,
            'last_matched_at' => $accountKeyword->last_matched_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Bulk create keywords for an account
     */
    public function bulkStore(Request $request, Account $account)
    {
        $user = auth()->user();

        // Authorization check
        abort_unless($user->hasAdminAccess(), 403);

        if (!$user->isSuperAdmin()) {
            abort_unless($account->company_id === $user->company_id, 403);
        }

        $validated = $request->validate([
            'keywords' => 'required|array|min:1',
            'keywords.*.keyword' => 'required|string|max:255',
            'keywords.*.match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'keywords.*.priority' => 'required|integer|min:1|max:10',
        ]);

        try {
            $created = 0;
            foreach ($validated['keywords'] as $keywordData) {
                AccountKeyword::create([
                    'uuid' => Str::uuid(),
                    'company_id' => $account->company_id,
                    'account_id' => $account->id,
                    'keyword' => $keywordData['keyword'],
                    'match_type' => $keywordData['match_type'],
                    'priority' => $keywordData['priority'],
                    'is_active' => true,
                ]);
                $created++;
            }

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return back()->with('success', "{$created} keywords berhasil ditambahkan!");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan keywords: ' . $e->getMessage());
        }
    }
}