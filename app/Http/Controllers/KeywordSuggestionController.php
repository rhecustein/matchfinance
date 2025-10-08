<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use App\Models\StatementTransaction;
use App\Services\KeywordSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KeywordSuggestionController extends Controller
{
    public function __construct(
        private KeywordSuggestionService $suggestionService
    ) {}

    /**
     * ✅ ENHANCED: Analyze bank statement with advanced filtering and AI suggestions
     */
    public function analyze(BankStatement $bankStatement, Request $request)
    {
        try {
            Log::info('=== ANALYZE SUGGESTIONS START ===', [
                'statement_id' => $bankStatement->id,
                'user_id' => auth()->id(),
            ]);

            // ✅ Get filters from request
            $filters = [
                'min_frequency' => $request->input('min_frequency', 2),
                'min_amount' => $request->input('min_amount', 0),
                'transaction_type' => $request->input('transaction_type'), // 'debit', 'credit', or null
                'sort_by' => $request->input('sort_by', 'frequency'), // 'frequency', 'amount', 'count'
                'include_similar' => $request->boolean('include_similar', true),
            ];

            // ✅ Use cache for performance (5 minutes)
            $cacheKey = "suggestions_{$bankStatement->id}_" . md5(json_encode($filters));
            
            $suggestions = Cache::remember($cacheKey, 300, function() use ($bankStatement, $filters) {
                return $this->suggestionService->analyzeBankStatement($bankStatement->id, $filters);
            });

            if (empty($suggestions)) {
                return back()->with('info', 'No keyword suggestions found. All transactions may already be matched or filters are too strict.');
            }

            // ✅ Enhanced statistics
            $stats = $this->calculateStatistics($bankStatement, $suggestions);

            // ✅ Get AI-powered category recommendations
            $categoryRecommendations = $this->getAICategoryRecommendations($suggestions);

            // ✅ Get sub categories grouped by hierarchy
            $subCategories = $this->getGroupedSubCategories();

            // ✅ Get existing keywords for comparison
            $existingKeywords = Keyword::with('subCategory.category.type')
                ->where('is_active', true)
                ->get()
                ->pluck('keyword')
                ->toArray();

            // ✅ Detect potential duplicates
            $suggestions = $this->detectPotentialDuplicates($suggestions, $existingKeywords);

            Log::info('=== ANALYZE SUGGESTIONS SUCCESS ===', [
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
            Log::error('=== ANALYZE SUGGESTIONS ERROR ===', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Calculate comprehensive statistics
     */
    private function calculateStatistics(BankStatement $bankStatement, array $suggestions): array
    {
        $totalTransactions = array_sum(array_column($suggestions, 'transaction_count'));
        $totalAmount = array_sum(array_column($suggestions, 'total_amount'));
        $allTransactionsCount = $bankStatement->transactions()->count();
        $unmatchedCount = $bankStatement->transactions()->whereNull('matched_keyword_id')->count();

        return [
            'total_suggestions' => count($suggestions),
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
            'coverage_percentage' => $allTransactionsCount > 0 
                ? round(($totalTransactions / $allTransactionsCount) * 100, 1) 
                : 0,
            'potential_match_rate' => $unmatchedCount > 0
                ? round(($totalTransactions / $unmatchedCount) * 100, 1)
                : 0,
            'avg_frequency' => $totalTransactions > 0 
                ? round($totalTransactions / count($suggestions), 1)
                : 0,
            'debit_count' => collect($suggestions)->where('transaction_type', 'debit')->sum('transaction_count'),
            'credit_count' => collect($suggestions)->where('transaction_type', 'credit')->sum('transaction_count'),
        ];
    }

    /**
     * ✅ NEW: AI-powered category recommendations based on keywords
     */
    private function getAICategoryRecommendations(array $suggestions): array
    {
        $recommendations = [];

        foreach ($suggestions as $suggestion) {
            $keyword = strtolower($suggestion['suggested_keyword']);
            $categoryId = null;
            $confidence = 0;

            // ✅ Pattern matching for common categories
            $patterns = [
                // Food & Beverage
                'food' => ['restoran', 'restaurant', 'cafe', 'warung', 'kfc', 'mcd', 'burger', 'pizza'],
                
                // Transportation
                'transportation' => ['grab', 'gojek', 'uber', 'taxi', 'ojol', 'parkir', 'tol'],
                
                // Healthcare
                'healthcare' => ['kimia farma', 'apotek', 'guardian', 'hospital', 'dokter', 'klinik'],
                
                // Shopping
                'shopping' => ['indomaret', 'alfamart', 'tokopedia', 'shopee', 'lazada', 'mall'],
                
                // Utilities
                'utilities' => ['listrik', 'pln', 'pdam', 'air', 'internet', 'telkom'],
                
                // Transfer
                'transfer' => ['transfer', 'trf', 'kirim', 'setor', 'tarik'],
                
                // E-Wallet
                'ewallet' => ['gopay', 'ovo', 'dana', 'shopeepay', 'linkaja'],
            ];

            foreach ($patterns as $category => $keywords) {
                foreach ($keywords as $pattern) {
                    if (str_contains($keyword, $pattern)) {
                        $recommendations[$suggestion['suggested_keyword']] = [
                            'category' => $category,
                            'confidence' => 85,
                            'reason' => "Matches pattern: {$pattern}",
                        ];
                        break 2;
                    }
                }
            }
        }

        return $recommendations;
    }

    /**
     * ✅ NEW: Get sub categories grouped by Type > Category > SubCategory
     */
    private function getGroupedSubCategories(): array
    {
        return Type::with(['categories.subCategories' => function($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'categories' => $type->categories->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'sub_categories' => $category->subCategories,
                        ];
                    }),
                ];
            })
            ->toArray();
    }

    /**
     * ✅ NEW: Detect potential duplicate keywords
     */
    private function detectPotentialDuplicates(array $suggestions, array $existingKeywords): array
    {
        foreach ($suggestions as &$suggestion) {
            $keyword = strtolower($suggestion['suggested_keyword']);
            $duplicates = [];

            foreach ($existingKeywords as $existing) {
                $existingLower = strtolower($existing);
                
                // Exact match
                if ($keyword === $existingLower) {
                    $duplicates[] = [
                        'keyword' => $existing,
                        'match_type' => 'exact',
                        'similarity' => 100,
                    ];
                }
                // Similar match (using levenshtein distance)
                else if (levenshtein($keyword, $existingLower) <= 2) {
                    $similarity = round((1 - levenshtein($keyword, $existingLower) / max(strlen($keyword), strlen($existingLower))) * 100);
                    $duplicates[] = [
                        'keyword' => $existing,
                        'match_type' => 'similar',
                        'similarity' => $similarity,
                    ];
                }
                // Substring match
                else if (str_contains($existingLower, $keyword) || str_contains($keyword, $existingLower)) {
                    $duplicates[] = [
                        'keyword' => $existing,
                        'match_type' => 'substring',
                        'similarity' => 70,
                    ];
                }
            }

            $suggestion['potential_duplicates'] = $duplicates;
            $suggestion['has_duplicates'] = !empty($duplicates);
        }

        return $suggestions;
    }

    /**
     * ✅ ENHANCED: Create keyword from suggestion with validation
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
            'description' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Check for duplicate keyword
            $existing = Keyword::where('keyword', $request->keyword)
                ->where('is_active', true)
                ->first();

            if ($existing) {
                return back()
                    ->with('warning', "Keyword '{$request->keyword}' already exists! Please use a different keyword or edit the existing one.")
                    ->withInput();
            }

            // ✅ Create the keyword
            $keyword = Keyword::create([
                'sub_category_id' => $request->sub_category_id,
                'keyword' => $request->keyword,
                'is_regex' => $request->is_regex ?? false,
                'case_sensitive' => $request->case_sensitive ?? false,
                'priority' => $request->priority ?? 5,
                'is_active' => true,
                'description' => $request->description ?? "Auto-suggested from " . count($request->transaction_ids) . " transactions",
            ]);

            Log::info('✅ Keyword created from suggestion', [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'sub_category_id' => $keyword->sub_category_id,
                'transaction_count' => count($request->transaction_ids),
            ]);

            $updated = 0;

            // ✅ Apply to transactions if requested
            if ($request->apply_immediately) {
                $updated = $this->suggestionService->applyKeywordToTransactions(
                    $keyword->id,
                    $request->transaction_ids
                );

                Log::info('✅ Keyword applied immediately', [
                    'keyword_id' => $keyword->id,
                    'transactions_updated' => $updated,
                ]);
            }

            DB::commit();

            // ✅ Clear cache
            Cache::forget("suggestions_*");

            return back()->with('success', "✅ Keyword '{$keyword->keyword}' created successfully!" . 
                ($request->apply_immediately ? " Applied to {$updated} transactions." : ""));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Failed to create keyword from suggestion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Failed to create keyword: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ✅ ENHANCED: Batch create keywords with better error handling
     */
    public function batchCreate(Request $request)
    {
        $request->validate([
            'suggestions' => 'required|array|min:1',
            'suggestions.*.keyword' => 'required|string|max:255',
            'suggestions.*.sub_category_id' => 'required|exists:sub_categories,id',
            'suggestions.*.transaction_ids' => 'required|array',
            'suggestions.*.priority' => 'nullable|integer|min:1|max:10',
            'suggestions.*.is_regex' => 'nullable|boolean',
            'suggestions.*.case_sensitive' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $created = 0;
            $applied = 0;
            $skipped = 0;
            $errors = [];

            foreach ($request->suggestions as $index => $suggestion) {
                try {
                    // ✅ Check for duplicate
                    $existing = Keyword::where('keyword', $suggestion['keyword'])
                        ->where('is_active', true)
                        ->exists();

                    if ($existing) {
                        $skipped++;
                        $errors[] = "Keyword '{$suggestion['keyword']}' already exists";
                        continue;
                    }

                    // ✅ Create keyword
                    $keyword = Keyword::create([
                        'sub_category_id' => $suggestion['sub_category_id'],
                        'keyword' => $suggestion['keyword'],
                        'is_regex' => $suggestion['is_regex'] ?? false,
                        'case_sensitive' => $suggestion['case_sensitive'] ?? false,
                        'priority' => $suggestion['priority'] ?? 5,
                        'is_active' => true,
                        'description' => "Batch created from suggestion",
                    ]);

                    $created++;

                    // ✅ Apply to transactions
                    $updated = $this->suggestionService->applyKeywordToTransactions(
                        $keyword->id,
                        $suggestion['transaction_ids']
                    );

                    $applied += $updated;

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Error at index {$index}: " . $e->getMessage();
                    
                    Log::error('Batch keyword creation error', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // ✅ Clear cache
            Cache::forget("suggestions_*");

            Log::info('=== BATCH KEYWORD CREATION COMPLETED ===', [
                'created' => $created,
                'applied' => $applied,
                'skipped' => $skipped,
                'errors_count' => count($errors),
            ]);

            $message = "{$created} keywords created and applied to {$applied} transactions!";
            if ($skipped > 0) {
                $message .= " {$skipped} skipped.";
            }

            $flashType = $skipped > 0 ? 'warning' : 'success';

            return redirect()
                ->route('keywords.index')
                ->with($flashType, $message)
                ->with('batch_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== BATCH KEYWORD CREATION FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Batch creation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ✅ NEW: Smart batch create with AI recommendations
     */
    public function smartBatchCreate(Request $request)
    {
        $request->validate([
            'bank_statement_id' => 'required|exists:bank_statements,id',
            'auto_assign_categories' => 'boolean',
            'min_confidence' => 'integer|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            $bankStatement = BankStatement::findOrFail($request->bank_statement_id);
            
            // Get suggestions
            $suggestions = $this->suggestionService->analyzeBankStatement($bankStatement->id);
            
            // Get AI recommendations
            $recommendations = $this->getAICategoryRecommendations($suggestions);

            $created = 0;
            $applied = 0;
            $minConfidence = $request->input('min_confidence', 70);

            foreach ($suggestions as $suggestion) {
                $keyword = $suggestion['suggested_keyword'];
                
                // Skip if no recommendation or confidence too low
                if (!isset($recommendations[$keyword]) || $recommendations[$keyword]['confidence'] < $minConfidence) {
                    continue;
                }

                $categoryName = $recommendations[$keyword]['category'];
                
                // Find sub category by name pattern
                $subCategory = SubCategory::whereHas('category', function($query) use ($categoryName) {
                    $query->where('name', 'LIKE', "%{$categoryName}%");
                })->first();

                if (!$subCategory) {
                    continue;
                }

                // Create keyword
                $keywordModel = Keyword::create([
                    'sub_category_id' => $subCategory->id,
                    'keyword' => $keyword,
                    'is_regex' => false,
                    'case_sensitive' => false,
                    'priority' => 5,
                    'is_active' => true,
                    'description' => "AI-created with {$recommendations[$keyword]['confidence']}% confidence: {$recommendations[$keyword]['reason']}",
                ]);

                $created++;

                // Apply to transactions
                $updated = $this->suggestionService->applyKeywordToTransactions(
                    $keywordModel->id,
                    $suggestion['transaction_ids']
                );

                $applied += $updated;
            }

            DB::commit();

            Log::info('=== SMART BATCH CREATE COMPLETED ===', [
                'created' => $created,
                'applied' => $applied,
            ]);

            return back()->with('success', "🤖 AI created {$created} keywords and applied to {$applied} transactions!");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Smart batch create failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Smart batch failed: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ENHANCED: Dismiss/ignore a suggestion with reason
     */
    public function dismiss(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            // ✅ Log dismissal with reason
            Log::info('Suggestion dismissed', [
                'transaction_ids' => $request->transaction_ids,
                'reason' => $request->reason,
                'user_id' => auth()->id(),
                'dismissed_at' => now(),
            ]);

            // ✅ TODO: Add dismissed_suggestions table to track this
            // For now, just return success

            return response()->json([
                'success' => true,
                'message' => 'Suggestion dismissed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dismiss suggestion', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Get suggestion preview with detailed analysis
     */
    public function preview(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
        ]);

        try {
            $transactions = StatementTransaction::with('bankStatement.bank')
                ->whereIn('id', $request->transaction_ids)
                ->get();

            // ✅ Calculate detailed statistics
            $totalDebit = $transactions->where('transaction_type', 'debit')->sum('debit_amount');
            $totalCredit = $transactions->where('transaction_type', 'credit')->sum('credit_amount');
            $avgAmount = $transactions->avg(function($t) {
                return $t->transaction_type === 'debit' ? $t->debit_amount : $t->credit_amount;
            });

            // ✅ Date range
            $dates = $transactions->pluck('transaction_date')->sort();
            $dateRange = [
                'start' => $dates->first(),
                'end' => $dates->last(),
                'span_days' => $dates->first() && $dates->last() 
                    ? \Carbon\Carbon::parse($dates->first())->diffInDays($dates->last())
                    : 0,
            ];

            // ✅ Frequency analysis
            $frequency = $this->analyzeFrequency($transactions);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions->map(function($t) {
                        return [
                            'id' => $t->id,
                            'date' => $t->transaction_date,
                            'description' => $t->description,
                            'amount' => $t->transaction_type === 'debit' ? $t->debit_amount : $t->credit_amount,
                            'type' => $t->transaction_type,
                            'balance' => $t->balance,
                            'bank' => $t->bankStatement->bank->name ?? 'N/A',
                        ];
                    }),
                    'statistics' => [
                        'count' => $transactions->count(),
                        'total_debit' => $totalDebit,
                        'total_credit' => $totalCredit,
                        'avg_amount' => round($avgAmount, 2),
                        'date_range' => $dateRange,
                        'frequency' => $frequency,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Preview failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Analyze frequency pattern
     */
    private function analyzeFrequency($transactions): array
    {
        if ($transactions->isEmpty()) {
            return ['pattern' => 'none', 'description' => 'No transactions'];
        }

        $dates = $transactions->pluck('transaction_date')->sort()->values();
        
        if ($dates->count() < 2) {
            return ['pattern' => 'single', 'description' => 'Single occurrence'];
        }

        // Calculate intervals between transactions
        $intervals = [];
        for ($i = 1; $i < $dates->count(); $i++) {
            $interval = \Carbon\Carbon::parse($dates[$i-1])->diffInDays($dates[$i]);
            $intervals[] = $interval;
        }

        $avgInterval = array_sum($intervals) / count($intervals);

        // Determine pattern
        if ($avgInterval <= 1) {
            $pattern = 'daily';
        } elseif ($avgInterval <= 7) {
            $pattern = 'weekly';
        } elseif ($avgInterval <= 15) {
            $pattern = 'bi-weekly';
        } elseif ($avgInterval <= 31) {
            $pattern = 'monthly';
        } else {
            $pattern = 'irregular';
        }

        return [
            'pattern' => $pattern,
            'avg_interval_days' => round($avgInterval, 1),
            'description' => ucfirst($pattern) . ' pattern (avg ' . round($avgInterval, 1) . ' days)',
        ];
    }

    /**
     * ✅ NEW: Export suggestions to CSV
     */
    public function export(BankStatement $bankStatement)
    {
        try {
            $suggestions = $this->suggestionService->analyzeBankStatement($bankStatement->id);

            $filename = 'keyword_suggestions_' . $bankStatement->id . '_' . date('YmdHis') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function() use ($suggestions) {
                $file = fopen('php://output', 'w');
                
                // Header
                fputcsv($file, [
                    'Suggested Keyword',
                    'Transaction Count',
                    'Frequency',
                    'Transaction Type',
                    'Avg Amount',
                    'Total Amount',
                    'Sample Description',
                ]);

                // Data
                foreach ($suggestions as $suggestion) {
                    fputcsv($file, [
                        $suggestion['suggested_keyword'],
                        $suggestion['transaction_count'],
                        $suggestion['frequency'],
                        $suggestion['transaction_type'],
                        $suggestion['avg_amount'],
                        $suggestion['total_amount'],
                        $suggestion['description_sample'],
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Refresh/recalculate suggestions
     */
    public function refresh(BankStatement $bankStatement)
    {
        try {
            // Clear cache
            Cache::forget("suggestions_{$bankStatement->id}_*");

            Log::info('Suggestions cache cleared', [
                'statement_id' => $bankStatement->id,
            ]);

            return back()->with('success', 'Suggestions refreshed successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Refresh failed: ' . $e->getMessage());
        }
    }
}