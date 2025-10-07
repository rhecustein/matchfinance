<?php

namespace App\Http\Controllers;

use App\Models\StatementTransaction;
use App\Models\SubCategory;
use App\Services\TransactionMatchingService;
use Illuminate\Http\Request;

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

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Show transaction detail
     */
    public function show(StatementTransaction $transaction)
    {
        $transaction->load([
            'bankStatement.bank',
            'subCategory.category.type',
            'matchedKeyword',
            'matchingLogs.keyword.subCategory'
        ]);

        return view('transactions.show', compact('transaction'));
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
                $data['is_verified'] = false; // Reset verification on manual change
            }

            $transaction->update($data);

            return back()->with('success', 'Transaction updated successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    /**
     * Verify a transaction
     */
    public function verify(StatementTransaction $transaction)
    {
        $transaction->verify(auth()->id());

        return back()->with('success', 'Transaction verified successfully.');
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

        try {
            StatementTransaction::whereIn('id', $request->transaction_ids)
                ->update([
                    'is_verified' => true,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

            return back()->with('success', count($request->transaction_ids) . ' transactions verified.');

        } catch (\Exception $e) {
            return back()->with('error', 'Bulk verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Re-match a transaction
     */
    public function rematch(StatementTransaction $transaction)
    {
        try {
            $success = $this->matchingService->rematchTransaction($transaction);

            if ($success) {
                return back()->with('success', 'Transaction re-matched successfully.');
            }

            return back()->with('warning', 'No matching keyword found for this transaction.');

        } catch (\Exception $e) {
            return back()->with('error', 'Re-matching failed: ' . $e->getMessage());
        }
    }
}