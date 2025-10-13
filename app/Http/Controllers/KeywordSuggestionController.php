<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use App\Services\KeywordSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class KeywordSuggestionController extends Controller
{
    public function __construct(
        private KeywordSuggestionService $suggestionService
    ) {}

    /**
     * Analyze bank statement and show keyword suggestions
     */
    public function analyze(BankStatement $bankStatement, Request $request)
    {
        try {
            Log::info('Analyzing bank statement for keyword suggestions', [
                'statement_id' => $bankStatement->id,
                'user_id' => Auth::id(),
            ]);

            // Get filters from request
            $filters = [
                'min_frequency' => $request->input('min_frequency', 2),
                'min_amount' => $request->input('min_amount', 0),
                'transaction_type' => $request->input('transaction_type'),
                'sort_by' => $request->input('sort_by', 'frequency'),
                'include_similar' => $request->boolean('include_similar', true),
            ];

            // Use cache for performance (5 minutes)
            $cacheKey = "suggestions_{$bankStatement->id}_" . md5(json_encode($filters));
            
            $suggestions = Cache::remember($cacheKey, 300, function() use ($bankStatement, $filters) {
                return $this->suggestionService->analyzeBankStatement($bankStatement->id, $filters);
            });

            if (empty($suggestions)) {
                return back()->with('info', 'No keyword suggestions found. All transactions may already be categorized or filters are too strict.');
            }

            // Calculate statistics
            $stats = $this->calculateStatistics($bankStatement, $suggestions);

            // Get AI category recommendations
            $categoryRecommendations = $this->suggestionService->getAICategoryRecommendations($suggestions);

            // Get sub categories grouped by hierarchy
            $subCategories = $this->getGroupedSubCategories();

            // Get existing keywords for duplicate detection
            $existingKeywords = Keyword::where('is_active', true)
                ->pluck('keyword')
                ->toArray();

            // Detect potential duplicates
            foreach ($suggestions as &$suggestion) {
                $duplicates = $this->suggestionService->detectDuplicates(
                    $suggestion['suggested_keyword'],
                    $existingKeywords
                );
                $suggestion['potential_duplicates'] = $duplicates;
                $suggestion['has_duplicates'] = !empty($duplicates);
            }

            Log::info('Analysis complete', [
                'suggestions_count' => count($suggestions),
                'total_transactions' => $stats['total_transactions'],
            ]);

            return view('keyword-suggestions.analyze', compact(
                'bankStatement', 
                'suggestions', 
                'subCategories',
                'stats',
                'categoryRecommendations',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Keyword analysis failed', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Create keyword from suggestion
     */
    public function createFromSuggestion(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'priority' => 'integer|min:1|max:10',
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
            'apply_immediately' => 'boolean',
            'pattern_description' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Check for duplicate
            $existing = Keyword::where('keyword', $validated['keyword'])
                ->where('is_active', true)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "Keyword '{$validated['keyword']}' already exists!",
                ], 422);
            }

            // Create keyword
            $keyword = Keyword::create([
                'sub_category_id' => $validated['sub_category_id'],
                'keyword' => $validated['keyword'],
                'is_regex' => $validated['is_regex'] ?? false,
                'case_sensitive' => $validated['case_sensitive'] ?? false,
                'match_type' => $validated['match_type'],
                'priority' => $validated['priority'] ?? 5,
                'pattern_description' => $validated['pattern_description'],
                'is_active' => true,
            ]);

            // Apply to transactions if requested
            $appliedCount = 0;
            if ($validated['apply_immediately'] ?? true) {
                $appliedCount = $this->suggestionService->applyKeywordToTransactions(
                    $keyword->id,
                    $validated['transaction_ids']
                );
            }

            DB::commit();

            Log::info('Keyword created from suggestion', [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'applied_count' => $appliedCount,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Keyword created successfully! Applied to {$appliedCount} transaction(s).",
                'data' => [
                    'keyword_id' => $keyword->id,
                    'applied_count' => $appliedCount,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create keyword from suggestion', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create keyword: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch create keywords from multiple suggestions
     */
    public function batchCreate(Request $request)
    {
        $validated = $request->validate([
            'suggestions' => 'required|array|min:1',
            'suggestions.*.sub_category_id' => 'required|exists:sub_categories,id',
            'suggestions.*.keyword' => 'required|string|max:255',
            'suggestions.*.transaction_ids' => 'required|array',
            'suggestions.*.match_type' => 'required|in:exact,contains,starts_with,ends_with,regex',
            'suggestions.*.priority' => 'integer|min:1|max:10',
        ]);

        DB::beginTransaction();

        try {
            $created = 0;
            $skipped = 0;
            $appliedTotal = 0;
            $errors = [];

            foreach ($validated['suggestions'] as $index => $suggestionData) {
                try {
                    // Check for duplicate
                    $existing = Keyword::where('keyword', $suggestionData['keyword'])
                        ->where('is_active', true)
                        ->first();

                    if ($existing) {
                        $skipped++;
                        $errors[] = "Keyword '{$suggestionData['keyword']}' already exists";
                        continue;
                    }

                    // Create keyword
                    $keyword = Keyword::create([
                        'sub_category_id' => $suggestionData['sub_category_id'],
                        'keyword' => $suggestionData['keyword'],
                        'match_type' => $suggestionData['match_type'],
                        'priority' => $suggestionData['priority'] ?? 5,
                        'is_active' => true,
                    ]);

                    // Apply to transactions
                    $applied = $this->suggestionService->applyKeywordToTransactions(
                        $keyword->id,
                        $suggestionData['transaction_ids']
                    );

                    $created++;
                    $appliedTotal += $applied;

                } catch (\Exception $e) {
                    $errors[] = "Failed to create keyword at index {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Batch keyword creation completed', [
                'created' => $created,
                'skipped' => $skipped,
                'applied_total' => $appliedTotal,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Created {$created} keyword(s), skipped {$skipped}. Applied to {$appliedTotal} transaction(s).",
                'data' => [
                    'created' => $created,
                    'skipped' => $skipped,
                    'applied_total' => $appliedTotal,
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Batch keyword creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply existing keyword to suggested transactions
     */
    public function applyExistingKeyword(Request $request)
    {
        $validated = $request->validate([
            'keyword_id' => 'required|exists:keywords,id',
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
        ]);

        try {
            $appliedCount = $this->suggestionService->applyKeywordToTransactions(
                $validated['keyword_id'],
                $validated['transaction_ids']
            );

            Log::info('Existing keyword applied', [
                'keyword_id' => $validated['keyword_id'],
                'applied_count' => $appliedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Applied to {$appliedCount} transaction(s).",
                'data' => ['applied_count' => $appliedCount],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to apply existing keyword', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply keyword: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get keyword statistics
     */
    public function statistics()
    {
        $stats = $this->suggestionService->getKeywordStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate statistics from suggestions
     */
    private function calculateStatistics(BankStatement $bankStatement, array $suggestions): array
    {
        $totalTransactions = array_sum(array_column($suggestions, 'transaction_count'));
        $totalAmount = array_sum(array_column($suggestions, 'total_amount'));
        $allTransactionsCount = $bankStatement->transactions()->count();
        $unmatchedCount = $bankStatement->transactions()->whereNull('sub_category_id')->count();

        return [
            'total_suggestions' => count($suggestions),
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
            'coverage_percentage' => $allTransactionsCount > 0 
                ? round(($totalTransactions / $allTransactionsCount) * 100, 2)
                : 0,
            'unmatched_count' => $unmatchedCount,
            'potential_savings' => count($suggestions) * 2, // Estimated minutes saved
        ];
    }

    /**
     * Get sub categories grouped by type and category
     */
    private function getGroupedSubCategories(): array
    {
        return SubCategory::with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy(function($item) {
                return $item->category->type->name;
            })
            ->map(function($typeGroup) {
                return $typeGroup->groupBy(function($item) {
                    return $item->category->name;
                });
            })
            ->toArray();
    }
}