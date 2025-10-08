<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Services\KeywordSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeywordSuggestionController extends Controller
{
    public function __construct(
        private KeywordSuggestionService $suggestionService
    ) {}

    /**
     * Analyze bank statement and show keyword suggestions
     */
    public function analyze(BankStatement $bankStatement)
    {
        try {
            Log::info('Analyzing bank statement for keyword suggestions', [
                'statement_id' => $bankStatement->id
            ]);

            $suggestions = $this->suggestionService->analyzeBankStatement($bankStatement->id);

            if (empty($suggestions)) {
                return back()->with('info', 'No keyword suggestions found. All transactions may already be matched.');
            }

            // Get sub categories for selection
            $subCategories = SubCategory::with('category.type')
                ->orderBy('name')
                ->get()
                ->groupBy('category.type.name');

            return view('keyword-suggestions.analyze', compact('bankStatement', 'suggestions', 'subCategories'));

        } catch (\Exception $e) {
            Log::error('Failed to analyze for keywords', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Create keyword from suggestion
     */
    public function createFromSuggestion(Request $request)
    {
        $request->validate([
            'suggestion_index' => 'required|integer',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'priority' => 'integer|min:1|max:10',
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
            'apply_immediately' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // Create the keyword
            $keyword = Keyword::create([
                'sub_category_id' => $request->sub_category_id,
                'keyword' => $request->keyword,
                'is_regex' => $request->is_regex ?? false,
                'case_sensitive' => $request->case_sensitive ?? false,
                'priority' => $request->priority ?? 5,
                'is_active' => true,
                'description' => "Auto-suggested from " . count($request->transaction_ids) . " transactions",
            ]);

            Log::info('Keyword created from suggestion', [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
            ]);

            // Apply to transactions if requested
            if ($request->apply_immediately) {
                $updated = $this->suggestionService->applyKeywordToTransactions(
                    $keyword->id,
                    $request->transaction_ids
                );

                Log::info('Keyword applied immediately', [
                    'keyword_id' => $keyword->id,
                    'transactions_updated' => $updated,
                ]);
            }

            DB::commit();

            return back()->with('success', "Keyword '{$keyword->keyword}' created successfully!" . 
                ($request->apply_immediately ? " Applied to {$updated} transactions." : ""));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create keyword from suggestion', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to create keyword: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Batch create keywords from multiple suggestions
     */
    public function batchCreate(Request $request)
    {
        $request->validate([
            'suggestions' => 'required|array',
            'suggestions.*.keyword' => 'required|string|max:255',
            'suggestions.*.sub_category_id' => 'required|exists:sub_categories,id',
            'suggestions.*.transaction_ids' => 'required|array',
            'suggestions.*.priority' => 'nullable|integer|min:1|max:10',
        ]);

        DB::beginTransaction();

        try {
            $created = 0;
            $applied = 0;

            foreach ($request->suggestions as $suggestion) {
                $keyword = Keyword::create([
                    'sub_category_id' => $suggestion['sub_category_id'],
                    'keyword' => $suggestion['keyword'],
                    'is_regex' => false,
                    'case_sensitive' => false,
                    'priority' => $suggestion['priority'] ?? 5,
                    'is_active' => true,
                    'description' => "Batch created from suggestion",
                ]);

                $created++;

                // Apply to transactions
                $updated = $this->suggestionService->applyKeywordToTransactions(
                    $keyword->id,
                    $suggestion['transaction_ids']
                );

                $applied += $updated;
            }

            DB::commit();

            Log::info('Batch keyword creation completed', [
                'created' => $created,
                'applied' => $applied,
            ]);

            return redirect()
                ->route('keywords.index')
                ->with('success', "{$created} keywords created and applied to {$applied} transactions!");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Batch keyword creation failed', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Batch creation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Dismiss/ignore a suggestion
     */
    public function dismiss(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
        ]);

        try {
            // Mark transactions as manually reviewed (add a flag if needed)
            // For now, just log the dismissal
            Log::info('Suggestion dismissed', [
                'transaction_ids' => $request->transaction_ids,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Suggestion dismissed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get suggestion preview (AJAX)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
        ]);

        try {
            $transactions = \App\Models\StatementTransaction::with('bankStatement.bank')
                ->whereIn('id', $request->transaction_ids)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'count' => $transactions->count(),
                    'total_amount' => $transactions->sum(function($t) {
                        return $t->transaction_type === 'debit' ? $t->debit_amount : $t->credit_amount;
                    }),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load preview: ' . $e->getMessage()
            ], 500);
        }
    }
}