<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountKeyword;
use App\Services\AccountMatchingService;
use Illuminate\Http\Request;

class AccountKeywordController extends Controller
{
    protected AccountMatchingService $matchingService;

    public function __construct(AccountMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Store a new keyword for an account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $account = Account::findOrFail($validated['account_id']);

            $keyword = $account->keywords()->create([
                'keyword' => $validated['keyword'],
                'match_type' => $validated['match_type'],
                'is_regex' => $validated['is_regex'] ?? false,
                'case_sensitive' => $validated['case_sensitive'] ?? false,
                'priority' => $validated['priority'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return back()->with('success', 'Keyword added successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to add keyword: ' . $e->getMessage());
        }
    }

    /**
     * Update a keyword
     */
    public function update(Request $request, AccountKeyword $accountKeyword)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $accountKeyword->update($validated);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return back()->with('success', 'Keyword updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update keyword: ' . $e->getMessage());
        }
    }

    /**
     * Delete a keyword
     */
    public function destroy(AccountKeyword $accountKeyword)
    {
        try {
            $accountKeyword->delete();

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return back()->with('success', 'Keyword deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete keyword: ' . $e->getMessage());
        }
    }

    /**
     * Toggle keyword status
     */
    public function toggleStatus(AccountKeyword $accountKeyword)
    {
        try {
            $accountKeyword->update(['is_active' => !$accountKeyword->is_active]);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            $status = $accountKeyword->is_active ? 'activated' : 'deactivated';
            return back()->with('success', "Keyword {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Get keyword details (for AJAX)
     */
    public function show(AccountKeyword $accountKeyword)
    {
        return response()->json([
            'id' => $accountKeyword->id,
            'keyword' => $accountKeyword->keyword,
            'match_type' => $accountKeyword->match_type,
            'is_regex' => $accountKeyword->is_regex,
            'case_sensitive' => $accountKeyword->case_sensitive,
            'priority' => $accountKeyword->priority,
            'is_active' => $accountKeyword->is_active,
            'match_count' => $accountKeyword->match_count ?? 0,
        ]);
    }
}