<?php

namespace App\Http\Controllers;

use App\Models\StatementTransaction;
use App\Models\SubCategory;
use App\Models\Category;
use App\Services\TransactionMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionMatchingService $matchingService
    ) {}

    /**
     * Display transactions with filters
     */
    public function index(Request $request)
    {
        $query = StatementTransaction::with([
            'bankStatement.bank',
            'subCategory.category.type',
            'matchedKeyword'
        ])->latest('transaction_date');

        // Apply filters
        if ($request->filled('status')) {
            match($request->status) {
                'matched' => $query->matched(),
                'unmatched' => $query->unmatched(),
                'verified' => $query->verified(),
                'unverified' => $query->unverified(),
                'low_confidence' => $query->lowConfidence(),
                default => null,
            };
        }

        if ($request->filled('bank_statement_id')) {
            $query->where('bank_statement_id', $request->bank_statement_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20)->withQueryString();

        // Get categories for filter
        $categories = Category::with('type')->orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'categories'));
    }

    /**
     * Show transaction detail
     */
    public function show(StatementTransaction $transaction)
    {
        // Load all necessary relationships
        $transaction->load([
            'bankStatement.bank',
            'subCategory.category.type',
            'matchedKeyword.subCategory.category.type',
            'matchingLogs.keyword.subCategory.category.type',
            'verifiedBy'
        ]);

        // Get sub categories for manual assignment - grouped for better UX
        $subCategories = SubCategory::with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy(function($item) {
                return $item->category->type->name;
            });

        return view('transactions.show', compact('transaction', 'subCategories'));
    }

    /**
     * Show edit form
     */
    public function edit(StatementTransaction $transaction)
    {
        $transaction->load('bankStatement.bank', 'subCategory.category.type');

        // Get sub categories grouped by type and category
        $subCategories = SubCategory::with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy(function($item) {
                return $item->category->type->name;
            });

        return view('transactions.edit', compact('transaction', 'subCategories'));
    }

    /**
     * Update transaction category manually
     */
    public function update(Request $request, StatementTransaction $transaction)
    {
        $request->validate([
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $data = [
                'notes' => $request->notes,
            ];

            if ($request->filled('sub_category_id')) {
                $subCategory = SubCategory::with('category.type')->find($request->sub_category_id);
                
                $data['sub_category_id'] = $subCategory->id;
                $data['category_id'] = $subCategory->category_id;
                $data['type_id'] = $subCategory->category->type_id;
                $data['confidence_score'] = 100; // Manual assignment = 100% confidence
                $data['is_manual_category'] = true;
                $data['matched_keyword_id'] = null; // Clear keyword match
                $data['is_verified'] = false; // Reset verification on manual change
            }

            $transaction->update($data);

            // Update bank statement stats
            if ($transaction->bankStatement && method_exists($transaction->bankStatement, 'updateMatchingStats')) {
                $transaction->bankStatement->updateMatchingStats();
            }

            DB::commit();

            Log::info('Transaction updated manually', [
                'transaction_id' => $transaction->id,
                'sub_category_id' => $data['sub_category_id'] ?? null,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    /**
     * Verify a transaction
     */
    public function verify(StatementTransaction $transaction)
    {
        try {
            if (!$transaction->matched_keyword_id && !$transaction->is_manual_category) {
                return back()->with('error', 'Only matched or manually categorized transactions can be verified.');
            }

            $transaction->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            Log::info('Transaction verified', [
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction verified successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to verify transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to verify: ' . $e->getMessage());
        }
    }

    /**
     * Unverify a transaction
     */
    public function unverify(StatementTransaction $transaction)
    {
        try {
            $transaction->update([
                'is_verified' => false,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            Log::info('Transaction unverified', [
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction verification removed.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to unverify: ' . $e->getMessage());
        }
    }

    /**
     * Bulk verify transactions
     */
    public function bulkVerify(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
        ]);

        DB::beginTransaction();

        try {
            $updated = StatementTransaction::whereIn('id', $request->transaction_ids)
                ->where(function($query) {
                    $query->whereNotNull('matched_keyword_id')
                          ->orWhere('is_manual_category', true);
                })
                ->update([
                    'is_verified' => true,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

            DB::commit();

            Log::info('Bulk verification completed', [
                'count' => $updated,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', "{$updated} transactions verified.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk verification failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Bulk verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Re-match a transaction
     */
    public function rematch(StatementTransaction $transaction)
    {
        DB::beginTransaction();

        try {
            Log::info('Rematching transaction', [
                'transaction_id' => $transaction->id,
                'description' => $transaction->description,
            ]);

            // Clear existing match
            $transaction->update([
                'matched_keyword_id' => null,
                'sub_category_id' => null,
                'category_id' => null,
                'type_id' => null,
                'confidence_score' => 0,
                'is_verified' => false,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            // Try to find new match
            $success = $this->matchingService->rematchTransaction($transaction);

            // Update bank statement stats
            if ($transaction->bankStatement && method_exists($transaction->bankStatement, 'updateMatchingStats')) {
                $transaction->bankStatement->updateMatchingStats();
            }

            DB::commit();

            if ($success) {
                Log::info('Transaction rematched successfully', [
                    'transaction_id' => $transaction->id,
                    'new_category' => $transaction->fresh()->subCategory?->name,
                ]);

                return back()->with('success', 'Transaction re-matched successfully.');
            }

            Log::info('No match found for transaction', [
                'transaction_id' => $transaction->id,
            ]);

            return back()->with('warning', 'No matching keyword found for this transaction.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Rematch failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Re-matching failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear match from a transaction
     */
    public function unmatch(StatementTransaction $transaction)
    {
        DB::beginTransaction();

        try {
            $transaction->update([
                'matched_keyword_id' => null,
                'sub_category_id' => null,
                'category_id' => null,
                'type_id' => null,
                'confidence_score' => 0,
                'is_manual_category' => false,
                'is_verified' => false,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            // Update bank statement stats
            if ($transaction->bankStatement && method_exists($transaction->bankStatement, 'updateMatchingStats')) {
                $transaction->bankStatement->updateMatchingStats();
            }

            DB::commit();

            Log::info('Transaction unmatched', [
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction match cleared.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Unmatch failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to clear match: ' . $e->getMessage());
        }
    }

    /**
     * Delete a transaction
     */
    public function destroy(StatementTransaction $transaction)
    {
        DB::beginTransaction();

        try {
            $bankStatement = $transaction->bankStatement;
            
            $transaction->delete();

            // Update bank statement stats
            if ($bankStatement && method_exists($bankStatement, 'updateMatchingStats')) {
                $bankStatement->updateMatchingStats();
            }

            DB::commit();

            Log::info('Transaction deleted', [
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('bank-statements.show', $bankStatement)
                ->with('success', 'Transaction deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}