<?php

namespace App\Http\Controllers;

use App\Models\DocumentItem;
use App\Models\DocumentCollection;
use App\Models\BankStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentItemController extends Controller
{
    /**
     * Store new item in collection
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'document_collection_id' => 'required|exists:document_collections,id',
            'bank_statement_id' => 'required|exists:bank_statements,id',
        ]);

        try {
            // Verify collection ownership
            $collection = DocumentCollection::where('id', $validated['document_collection_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();

            // Verify statement ownership
            $statement = BankStatement::where('id', $validated['bank_statement_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();

            // Check if statement already in a collection
            if ($statement->documentItem()->exists()) {
                return back()->with('error', 'Bank statement already in a collection.');
            }

            DB::beginTransaction();

            // Get next sort order
            $maxSort = $collection->items()->max('sort_order') ?? -1;

            $item = DocumentItem::create([
                'uuid' => Str::uuid(),
                'company_id' => $user->company_id,
                'document_collection_id' => $collection->id,
                'bank_statement_id' => $statement->id,
                'document_type' => 'bank_statement',
                'sort_order' => $maxSort + 1,
                'knowledge_status' => 'ready',
            ]);

            // Update collection document count
            $collection->increment('document_count');

            DB::commit();

            return back()->with('success', 'Document added to collection!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add document item: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to add document: ' . $e->getMessage());
        }
    }

    /**
     * Display item details
     */
    public function show(DocumentItem $documentItem)
    {
        abort_unless($documentItem->company_id === auth()->user()->company_id, 403);

        $documentItem->load([
            'collection',
            'bankStatement.bank'
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $documentItem->id,
                'uuid' => $documentItem->uuid,
                'type' => $documentItem->document_type,
                'status' => $documentItem->knowledge_status,
                'sort_order' => $documentItem->sort_order,
                'collection' => [
                    'id' => $documentItem->collection->id,
                    'name' => $documentItem->collection->name,
                ],
                'statement' => $documentItem->bankStatement ? [
                    'id' => $documentItem->bankStatement->id,
                    'bank' => $documentItem->bankStatement->bank->name,
                    'period' => $documentItem->bankStatement->period_from->format('M Y'),
                    'filename' => $documentItem->bankStatement->original_filename,
                ] : null,
            ],
        ]);
    }

    /**
     * Update item
     */
    public function update(Request $request, DocumentItem $documentItem)
    {
        abort_unless($documentItem->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'sort_order' => 'nullable|integer|min:0',
            'knowledge_status' => 'nullable|in:pending,processing,ready,failed',
        ]);

        try {
            $documentItem->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Document item updated!',
                'item' => $documentItem,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update document item: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove item from collection
     */
    public function destroy(DocumentItem $documentItem)
    {
        abort_unless($documentItem->company_id === auth()->user()->company_id, 403);

        try {
            DB::beginTransaction();

            $collection = $documentItem->collection;
            
            $documentItem->delete();

            // Update collection document count
            $collection->decrement('document_count');

            // Reorder remaining items
            $collection->items()
                ->where('sort_order', '>', $documentItem->sort_order)
                ->decrement('sort_order');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document removed from collection!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove document item: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder items in collection (AJAX)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:document_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['items'] as $itemData) {
                $item = DocumentItem::find($itemData['id']);
                
                // Verify ownership
                if ($item && $item->company_id === auth()->user()->company_id) {
                    $item->update(['sort_order' => $itemData['sort_order']]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items reordered successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder items: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry processing for failed item
     */
    public function retry(DocumentItem $documentItem)
    {
        abort_unless($documentItem->company_id === auth()->user()->company_id, 403);

        try {
            // Only retry failed items
            if ($documentItem->knowledge_status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed items can be retried',
                ], 400);
            }

            $documentItem->update([
                'knowledge_status' => 'pending',
                'error_message' => null,
            ]);

            // TODO: Dispatch processing job
            // dispatch(new ProcessDocumentItem($documentItem));

            return response()->json([
                'success' => true,
                'message' => 'Processing retry started!',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retry item: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk add items to collection
     */
    public function bulkStore(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'document_collection_id' => 'required|exists:document_collections,id',
            'bank_statement_ids' => 'required|array',
            'bank_statement_ids.*' => 'exists:bank_statements,id',
        ]);

        try {
            // Verify collection ownership
            $collection = DocumentCollection::where('id', $validated['document_collection_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();

            DB::beginTransaction();

            $addedCount = 0;
            $maxSort = $collection->items()->max('sort_order') ?? -1;

            foreach ($validated['bank_statement_ids'] as $statementId) {
                $statement = BankStatement::where('id', $statementId)
                    ->where('company_id', $user->company_id)
                    ->first();

                // Skip if statement already in a collection
                if (!$statement || $statement->documentItem()->exists()) {
                    continue;
                }

                DocumentItem::create([
                    'uuid' => Str::uuid(),
                    'company_id' => $user->company_id,
                    'document_collection_id' => $collection->id,
                    'bank_statement_id' => $statementId,
                    'document_type' => 'bank_statement',
                    'sort_order' => ++$maxSort,
                    'knowledge_status' => 'ready',
                ]);

                $addedCount++;
            }

            // Update collection document count
            $collection->update([
                'document_count' => $collection->items()->count()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$addedCount} document(s) added to collection!",
                'added_count' => $addedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk add items: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add documents: ' . $e->getMessage(),
            ], 500);
        }
    }
}