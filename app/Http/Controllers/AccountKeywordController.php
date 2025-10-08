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
     * Show create form
     */
    public function create(Request $request)
    {
        $accountId = $request->get('account_id');
        $account = $accountId ? Account::findOrFail($accountId) : null;

        return view('account-keywords.create', compact('account'));
    }

    /**
     * Store new keyword
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'pattern_description' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $keyword = AccountKeyword::create($validated);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'keyword' => $keyword,
                    'message' => 'Keyword created successfully!'
                ]);
            }

            return redirect()
                ->route('accounts.show', $validated['account_id'])
                ->with('success', 'Keyword created successfully!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create keyword: ' . $e->getMessage()
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create keyword: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(AccountKeyword $accountKeyword)
    {
        $accountKeyword->load('account');
        return view('account-keywords.edit', compact('accountKeyword'));
    }

    /**
     * Update keyword
     */
    public function update(Request $request, AccountKeyword $accountKeyword)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'pattern_description' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $accountKeyword->update($validated);

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'keyword' => $accountKeyword->fresh(),
                    'message' => 'Keyword updated successfully!'
                ]);
            }

            return redirect()
                ->route('accounts.show', $accountKeyword->account_id)
                ->with('success', 'Keyword updated successfully!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update keyword: ' . $e->getMessage()
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update keyword: ' . $e->getMessage());
        }
    }

    /**
     * Delete keyword
     */
    public function destroy(Request $request, AccountKeyword $accountKeyword)
    {
        try {
            $accountId = $accountKeyword->account_id;
            $accountKeyword->delete();

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Keyword deleted successfully!'
                ]);
            }

            return redirect()
                ->route('accounts.show', $accountId)
                ->with('success', 'Keyword deleted successfully!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete keyword: ' . $e->getMessage()
                ], 422);
            }

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
            
            return response()->json([
                'success' => true,
                'is_active' => $accountKeyword->is_active,
                'message' => "Keyword {$status} successfully!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Test keyword matching
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'keyword_id' => 'required|exists:account_keywords,id',
            'test_text' => 'required|string',
        ]);

        $keyword = AccountKeyword::findOrFail($validated['keyword_id']);
        $matched = $keyword->matches($validated['test_text']);

        return response()->json([
            'success' => true,
            'matched' => $matched,
            'keyword' => $keyword->keyword,
            'match_type' => $keyword->match_type,
            'test_text' => $validated['test_text'],
        ]);
    }

    /**
     * Bulk create keywords
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'keywords' => 'required|array|min:1',
            'keywords.*.keyword' => 'required|string',
            'keywords.*.match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'keywords.*.priority' => 'required|integer|min:1|max:10',
        ]);

        try {
            $created = 0;
            foreach ($validated['keywords'] as $keywordData) {
                AccountKeyword::create([
                    'account_id' => $validated['account_id'],
                    'keyword' => $keywordData['keyword'],
                    'match_type' => $keywordData['match_type'],
                    'priority' => $keywordData['priority'],
                    'is_active' => true,
                ]);
                $created++;
            }

            // Clear cache
            $this->matchingService->clearKeywordsCache();

            return response()->json([
                'success' => true,
                'created' => $created,
                'message' => "{$created} keywords created successfully!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create keywords: ' . $e->getMessage()
            ], 422);
        }
    }
}