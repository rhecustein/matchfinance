<?php

namespace App\Http\Controllers;

use App\Models\StatementTransaction;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use App\Models\Account;
use App\Models\BankStatement;
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
     * Display transactions with filters (COMPANY SCOPED)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // COMPANY SCOPED QUERY
        $query = StatementTransaction::where('company_id', $user->company_id)
            ->with([
                'bankStatement.bank',
                'subCategory.category.type',
                'account',
                'matchedKeyword',
                'verifiedBy'
            ])
            ->latest('transaction_date');

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

        // Filter by bank statement (verify belongs to company)
        if ($request->filled('bank_statement_id')) {
            $query->where('bank_statement_id', $request->bank_statement_id)
                  ->whereHas('bankStatement', function($q) use ($user) {
                      $q->where('company_id', $user->company_id);
                  });
        }

        // Filter by type (verify belongs to company)
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id)
                  ->whereHas('type', function($q) use ($user) {
                      $q->where('company_id', $user->company_id);
                  });
        }

        // Filter by category (verify belongs to company)
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id)
                  ->whereHas('category', function($q) use ($user) {
                      $q->where('company_id', $user->company_id);
                  });
        }

        // Filter by sub category (verify belongs to company)
        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id)
                  ->whereHas('subCategory', function($q) use ($user) {
                      $q->where('company_id', $user->company_id);
                  });
        }

        // Filter by account (verify belongs to company)
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id)
                  ->whereHas('account', function($q) use ($user) {
                      $q->where('company_id', $user->company_id);
                  });
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Search by description or reference
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        // Get filter options (COMPANY SCOPED)
        $types = Type::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $categories = Category::where('company_id', $user->company_id)
            ->with('type')
            ->orderBy('name')
            ->get();

        $subCategories = SubCategory::where('company_id', $user->company_id)
            ->with('category')
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $bankStatements = BankStatement::where('company_id', $user->company_id)
            ->with('bank')
            ->latest()
            ->limit(50)
            ->get(['id', 'bank_id', 'original_filename', 'period_from', 'period_to']);

        return view('transactions.index', compact(
            'transactions',
            'types',
            'categories',
            'subCategories',
            'accounts',
            'bankStatements'
        ));
    }

    /**
     * Show transaction detail (COMPANY SCOPED)
     */
    public function show(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        $user = auth()->user();

        // Load all necessary relationships
        $transaction->load([
            'bankStatement.bank',
            'subCategory.category.type',
            'account',
            'matchedKeyword.subCategory.category.type',
            'matchedAccountKeyword',
            'matchingLogs.keyword.subCategory.category.type',
            'accountMatchingLogs.account',
            'verifiedBy',
            'transactionCategories.subCategory.category.type'
        ]);

        // Get sub categories for manual assignment (COMPANY SCOPED) - grouped for better UX
        $subCategories = SubCategory::where('company_id', $user->company_id)
            ->with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy(function($item) {
                return $item->category->type->name;
            });

        // Get accounts for manual assignment (COMPANY SCOPED)
        $accounts = Account::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('transactions.show', compact('transaction', 'subCategories', 'accounts'));
    }

    /**
     * Show edit form (COMPANY SCOPED)
     */
    public function edit(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        $user = auth()->user();

        $transaction->load([
            'bankStatement.bank',
            'subCategory.category.type',
            'account'
        ]);

        // Get sub categories grouped by type and category (COMPANY SCOPED)
        $subCategories = SubCategory::where('company_id', $user->company_id)
            ->with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy(function($item) {
                return $item->category->type->name;
            });

        // Get accounts (COMPANY SCOPED)
        $accounts = Account::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('transactions.edit', compact('transaction', 'subCategories', 'accounts'));
    }

    /**
     * Update transaction category/account manually (COMPANY SCOPED)
     */
    public function update(Request $request, StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        $user = auth()->user();

        $request->validate([
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $data = [
                'notes' => $request->notes,
            ];

            // Update category if provided
            if ($request->filled('sub_category_id')) {
                // VERIFY SUB CATEGORY BELONGS TO COMPANY
                $subCategory = SubCategory::where('id', $request->sub_category_id)
                    ->where('company_id', $user->company_id)
                    ->with('category.type')
                    ->firstOrFail();
                
                $data['sub_category_id'] = $subCategory->id;
                $data['category_id'] = $subCategory->category_id;
                $data['type_id'] = $subCategory->category->type_id;
                $data['confidence_score'] = 100; // Manual assignment = 100% confidence
                $data['is_manual_category'] = true;
                $data['matched_keyword_id'] = null; // Clear keyword match
                $data['is_verified'] = false; // Reset verification on manual change
            }

            // Update account if provided
            if ($request->filled('account_id')) {
                // VERIFY ACCOUNT BELONGS TO COMPANY
                $account = Account::where('id', $request->account_id)
                    ->where('company_id', $user->company_id)
                    ->firstOrFail();
                
                $data['account_id'] = $account->id;
                $data['account_confidence_score'] = 100;
                $data['is_manual_account'] = true;
                $data['matched_account_keyword_id'] = null;
            }

            $transaction->update($data);

            // Update bank statement stats
            if ($transaction->bankStatement && method_exists($transaction->bankStatement, 'updateMatchingStats')) {
                $transaction->bankStatement->updateMatchingStats();
            }

            DB::commit();

            Log::info('Transaction updated manually', [
                'transaction_id' => $transaction->id,
                'company_id' => $user->company_id,
                'sub_category_id' => $data['sub_category_id'] ?? null,
                'account_id' => $data['account_id'] ?? null,
                'user_id' => $user->id,
            ]);

            return back()->with('success', 'Transaction updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update transaction', [
                'transaction_id' => $transaction->id,
                'company_id' => $user->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    /**
     * Verify a transaction (COMPANY SCOPED)
     */
    public function verify(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        try {
            if (!$transaction->matched_keyword_id && !$transaction->is_manual_category) {
                return back()->with('error', 'Only matched or manually categorized transactions can be verified.');
            }

            $transaction->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            // Update bank statement stats
            if ($transaction->bankStatement && method_exists($transaction->bankStatement, 'updateStatistics')) {
                $transaction->bankStatement->updateStatistics();
            }

            Log::info('Transaction verified', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction verified successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to verify transaction', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to verify: ' . $e->getMessage());
        }
    }

    /**
     * Unverify a transaction (COMPANY SCOPED)
     */
    public function unverify(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        try {
            $transaction->update([
                'is_verified' => false,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            // Update bank statement stats
            if ($transaction->bankStatement && method_exists($transaction->bankStatement, 'updateStatistics')) {
                $transaction->bankStatement->updateStatistics();
            }

            Log::info('Transaction unverified', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction verification removed.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to unverify: ' . $e->getMessage());
        }
    }

    /**
     * Bulk verify transactions (COMPANY SCOPED)
     */
    public function bulkVerify(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
        ]);

        DB::beginTransaction();

        try {
            // COMPANY SCOPED BULK UPDATE
            $updated = StatementTransaction::where('company_id', $user->company_id)
                ->whereIn('id', $request->transaction_ids)
                ->where(function($query) {
                    $query->whereNotNull('matched_keyword_id')
                          ->orWhere('is_manual_category', true);
                })
                ->update([
                    'is_verified' => true,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                ]);

            // Update bank statement statistics for affected statements
            $statementIds = StatementTransaction::where('company_id', $user->company_id)
                ->whereIn('id', $request->transaction_ids)
                ->pluck('bank_statement_id')
                ->unique();

            foreach ($statementIds as $statementId) {
                $statement = BankStatement::find($statementId);
                if ($statement && method_exists($statement, 'updateStatistics')) {
                    $statement->updateStatistics();
                }
            }

            DB::commit();

            Log::info('Bulk verification completed', [
                'count' => $updated,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
            ]);

            return back()->with('success', "{$updated} transactions verified.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk verification failed', [
                'company_id' => $user->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Bulk verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update category (COMPANY SCOPED)
     */
    public function bulkUpdateCategory(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:statement_transactions,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
        ]);

        DB::beginTransaction();

        try {
            // VERIFY SUB CATEGORY BELONGS TO COMPANY
            $subCategory = SubCategory::where('id', $request->sub_category_id)
                ->where('company_id', $user->company_id)
                ->with('category.type')
                ->firstOrFail();

            // COMPANY SCOPED BULK UPDATE
            $updated = StatementTransaction::where('company_id', $user->company_id)
                ->whereIn('id', $request->transaction_ids)
                ->update([
                    'sub_category_id' => $subCategory->id,
                    'category_id' => $subCategory->category_id,
                    'type_id' => $subCategory->category->type_id,
                    'confidence_score' => 100,
                    'is_manual_category' => true,
                    'matched_keyword_id' => null,
                    'is_verified' => false,
                ]);

            // Update bank statement statistics
            $statementIds = StatementTransaction::where('company_id', $user->company_id)
                ->whereIn('id', $request->transaction_ids)
                ->pluck('bank_statement_id')
                ->unique();

            foreach ($statementIds as $statementId) {
                $statement = BankStatement::find($statementId);
                if ($statement && method_exists($statement, 'updateStatistics')) {
                    $statement->updateStatistics();
                }
            }

            DB::commit();

            Log::info('Bulk category update completed', [
                'count' => $updated,
                'sub_category_id' => $subCategory->id,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
            ]);

            return back()->with('success', "{$updated} transactions updated with category '{$subCategory->name}'.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk category update failed', [
                'company_id' => $user->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Bulk update failed: ' . $e->getMessage());
        }
    }

    /**
     * Re-match a transaction (COMPANY SCOPED)
     */
    public function rematch(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        DB::beginTransaction();

        try {
            Log::info('Rematching transaction', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'description' => $transaction->description,
            ]);

            // Clear existing match
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
                    'company_id' => auth()->user()->company_id,
                    'new_category' => $transaction->fresh()->subCategory?->name,
                ]);

                return back()->with('success', 'Transaction re-matched successfully.');
            }

            Log::info('No match found for transaction', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
            ]);

            return back()->with('warning', 'No matching keyword found for this transaction.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Rematch failed', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Re-matching failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear match from a transaction (COMPANY SCOPED)
     */
    public function unmatch(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

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
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction match cleared.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Unmatch failed', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to clear match: ' . $e->getMessage());
        }
    }

    /**
     * Delete a transaction (COMPANY SCOPED)
     */
    public function destroy(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

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
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('bank-statements.show', $bankStatement)
                ->with('success', 'Transaction deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete transaction', [
                'transaction_id' => $transaction->id,
                'company_id' => auth()->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /**
     * Export transactions to CSV (COMPANY SCOPED)
     */
    public function export(Request $request)
    {
        $user = auth()->user();

        // Same filters as index
        $query = StatementTransaction::where('company_id', $user->company_id)
            ->with([
                'bankStatement.bank',
                'subCategory.category.type',
                'account'
            ]);

        // Apply same filters as index
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

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date')->get();

        // Generate CSV
        $filename = 'transactions_' . $user->company->slug . '_' . now()->format('Ymd_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // Headers
        fputcsv($handle, [
            'Date',
            'Bank',
            'Description',
            'Type',
            'Debit',
            'Credit',
            'Balance',
            'Category',
            'Account',
            'Confidence',
            'Verified',
            'Notes'
        ]);

        // Data
        foreach ($transactions as $transaction) {
            fputcsv($handle, [
                $transaction->transaction_date->format('Y-m-d'),
                $transaction->bankStatement->bank->name ?? '',
                $transaction->description,
                $transaction->transaction_type,
                $transaction->debit_amount,
                $transaction->credit_amount,
                $transaction->balance,
                $transaction->subCategory?->name ?? 'Uncategorized',
                $transaction->account?->name ?? 'Unassigned',
                $transaction->confidence_score,
                $transaction->is_verified ? 'Yes' : 'No',
                $transaction->notes ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('Transactions exported', [
            'company_id' => $user->company_id,
            'count' => $transactions->count(),
            'user_id' => $user->id,
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * API: Search transactions (COMPANY SCOPED)
     */
    public function apiSearch(Request $request)
    {
        $user = auth()->user();
        $search = $request->get('q', '');
        
        $transactions = StatementTransaction::where('company_id', $user->company_id)
            ->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            })
            ->with(['bankStatement.bank', 'subCategory'])
            ->orderBy('transaction_date', 'desc')
            ->limit(20)
            ->get(['id', 'uuid', 'description', 'transaction_date', 'amount', 'transaction_type', 'bank_statement_id', 'sub_category_id']);

        return response()->json($transactions);
    }

    /**
     * API: Get matching logs for transaction (COMPANY SCOPED)
     */
    public function apiGetMatchingLogs(StatementTransaction $transaction)
    {
        // COMPANY OWNERSHIP CHECK
        abort_unless($transaction->company_id === auth()->user()->company_id, 403);

        $logs = $transaction->matchingLogs()
            ->with('keyword.subCategory.category.type')
            ->orderBy('confidence_score', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}