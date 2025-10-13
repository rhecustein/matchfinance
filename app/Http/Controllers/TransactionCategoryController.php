<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use App\Models\StatementTransaction;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionCategoryController extends Controller
{
    /**
     * Get all categories for a transaction (AJAX)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'transaction_id' => 'required|exists:statement_transactions,id',
        ]);

        try {
            $transaction = StatementTransaction::where('id', $validated['transaction_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();

            $categories = $transaction->transactionCategories()
                ->with(['subCategory.category.type', 'matchedKeyword'])
                ->orderBy('confidence_score', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'categories' => $categories->map(function($tc) {
                    return [
                        'id' => $tc->id,
                        'uuid' => $tc->uuid,
                        'is_primary' => $tc->is_primary,
                        'is_manual' => $tc->is_manual,
                        'confidence_score' => $tc->confidence_score,
                        'reason' => $tc->reason,
                        'sub_category' => [
                            'id' => $tc->subCategory->id,
                            'name' => $tc->subCategory->name,
                            'category' => $tc->subCategory->category->name,
                            'type' => $tc->subCategory->category->type->name,
                        ],
                        'keyword' => $tc->matchedKeyword ? [
                            'id' => $tc->matchedKeyword->id,
                            'keyword' => $tc->matchedKeyword->keyword,
                            'priority' => $tc->matchedKeyword->priority,
                        ] : null,
                        'assigned_at' => $tc->assigned_at->toIso8601String(),
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get transaction categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add new category to transaction
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'transaction_id' => 'required|exists:statement_transactions,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'is_primary' => 'boolean',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            // Verify transaction ownership
            $transaction = StatementTransaction::where('id', $validated['transaction_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();

            // Verify sub category ownership
            $subCategory = SubCategory::where('id', $validated['sub_category_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();

            DB::beginTransaction();

            // If setting as primary, unset other primary categories
            if ($validated['is_primary'] ?? false) {
                $transaction->transactionCategories()
                    ->update(['is_primary' => false]);
            }

            // Create transaction category
            $transactionCategory = TransactionCategory::create([
                'uuid' => Str::uuid(),
                'company_id' => $user->company_id,
                'statement_transaction_id' => $transaction->id,
                'sub_category_id' => $subCategory->id,
                'category_id' => $subCategory->category_id,
                'type_id' => $subCategory->category->type_id,
                'confidence_score' => 100, // Manual assignment = 100% confidence
                'is_primary' => $validated['is_primary'] ?? false,
                'is_manual' => true,
                'reason' => $validated['reason'] ?? 'Manually assigned by user',
                'assigned_by' => $user->id,
                'assigned_at' => now(),
            ]);

            // Update denormalized fields on transaction if primary
            if ($transactionCategory->is_primary) {
                $transaction->update([
                    'sub_category_id' => $subCategory->id,
                    'category_id' => $subCategory->category_id,
                    'type_id' => $subCategory->category->type_id,
                    'confidence_score' => 100,
                    'is_manual_category' => true,
                    'matching_reason' => $validated['reason'] ?? 'Manually assigned',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category assigned successfully!',
                'transaction_category' => $transactionCategory->load('subCategory.category.type'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update transaction category
     */
    public function update(Request $request, TransactionCategory $transactionCategory)
    {
        abort_unless($transactionCategory->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'is_primary' => 'boolean',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // If setting as primary, unset others
            if ($validated['is_primary'] ?? false) {
                $transactionCategory->statementTransaction
                    ->transactionCategories()
                    ->where('id', '!=', $transactionCategory->id)
                    ->update(['is_primary' => false]);

                // Update denormalized fields
                $transactionCategory->statementTransaction->update([
                    'sub_category_id' => $transactionCategory->sub_category_id,
                    'category_id' => $transactionCategory->category_id,
                    'type_id' => $transactionCategory->type_id,
                    'confidence_score' => $transactionCategory->confidence_score,
                    'matching_reason' => $validated['reason'] ?? $transactionCategory->reason,
                ]);
            }

            $transactionCategory->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully!',
                'transaction_category' => $transactionCategory,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove category from transaction
     */
    public function destroy(TransactionCategory $transactionCategory)
    {
        abort_unless($transactionCategory->company_id === auth()->user()->company_id, 403);

        try {
            DB::beginTransaction();

            $transaction = $transactionCategory->statementTransaction;
            $wasPrimary = $transactionCategory->is_primary;

            $transactionCategory->delete();

            // If deleted category was primary, set next highest confidence as primary
            if ($wasPrimary) {
                $nextPrimary = $transaction->transactionCategories()
                    ->orderBy('confidence_score', 'desc')
                    ->first();

                if ($nextPrimary) {
                    $nextPrimary->update(['is_primary' => true]);

                    // Update denormalized fields
                    $transaction->update([
                        'sub_category_id' => $nextPrimary->sub_category_id,
                        'category_id' => $nextPrimary->category_id,
                        'type_id' => $nextPrimary->type_id,
                        'confidence_score' => $nextPrimary->confidence_score,
                        'matching_reason' => $nextPrimary->reason,
                    ]);
                } else {
                    // No categories left, clear denormalized fields
                    $transaction->update([
                        'sub_category_id' => null,
                        'category_id' => null,
                        'type_id' => null,
                        'confidence_score' => 0,
                        'is_manual_category' => false,
                        'matching_reason' => null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category removed successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set category as primary
     */
    public function setPrimary(TransactionCategory $transactionCategory)
    {
        abort_unless($transactionCategory->company_id === auth()->user()->company_id, 403);

        try {
            DB::beginTransaction();

            // Unset other primary categories
            $transactionCategory->statementTransaction
                ->transactionCategories()
                ->where('id', '!=', $transactionCategory->id)
                ->update(['is_primary' => false]);

            // Set this as primary
            $transactionCategory->update(['is_primary' => true]);

            // Update denormalized fields
            $transactionCategory->statementTransaction->update([
                'sub_category_id' => $transactionCategory->sub_category_id,
                'category_id' => $transactionCategory->category_id,
                'type_id' => $transactionCategory->type_id,
                'confidence_score' => $transactionCategory->confidence_score,
                'matching_reason' => $transactionCategory->reason,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Primary category set successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to set primary: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary: ' . $e->getMessage(),
            ], 500);
        }
    }
}