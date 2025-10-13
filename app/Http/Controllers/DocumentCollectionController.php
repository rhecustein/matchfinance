<?php

namespace App\Http\Controllers;

use App\Models\DocumentCollection;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentCollectionController extends Controller
{
    /**
     * Display listing of document collections (Company Scoped or Super Admin)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Super Admin: Can see all collections from all companies
        if ($user->isSuperAdmin()) {
            $query = DocumentCollection::with(['company', 'user'])
                ->withCount(['items', 'chatSessions']);

            // Filter by company
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            // Regular users: Only their company
            $query = DocumentCollection::where('company_id', $user->company_id)
                ->with('user')
                ->withCount(['items', 'chatSessions']);
        }

        // Filter by active status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $collections = $query->latest()->paginate(20);

        // Stats
        if ($user->isSuperAdmin()) {
            $statsQuery = DocumentCollection::query();
            if ($request->filled('company_id')) {
                $statsQuery->where('company_id', $request->company_id);
            }
            
            $stats = [
                'total' => $statsQuery->count(),
                'active' => (clone $statsQuery)->where('is_active', true)->count(),
                'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            ];

            // Get companies list for filter
            $companies = Company::orderBy('name')->get(['id', 'name']);
        } else {
            $stats = [
                'total' => DocumentCollection::where('company_id', $user->company_id)->count(),
                'active' => DocumentCollection::where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->count(),
                'inactive' => DocumentCollection::where('company_id', $user->company_id)
                    ->where('is_active', false)
                    ->count(),
            ];
            $companies = null;
        }

        return view('document-collections.index', compact('collections', 'stats', 'companies'));
    }

    /**
     * Show form for creating new collection
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // Super Admin must select a company first
        if ($user->isSuperAdmin()) {
            $companies = Company::orderBy('name')->get(['id', 'name']);
            
            // If company_id provided in query string
            $selectedCompanyId = $request->input('company_id');
            
            if ($selectedCompanyId) {
                $selectedCompany = Company::findOrFail($selectedCompanyId);
                
                // Get available bank statements for selected company
                $bankStatements = BankStatement::where('company_id', $selectedCompanyId)
                    ->where('ocr_status', 'completed')
                    ->whereDoesntHave('documentItem')
                    ->with('bank')
                    ->latest()
                    ->get();
                
                return view('document-collections.create', compact('bankStatements', 'companies', 'selectedCompany'));
            }
            
            // Show company selection first
            return view('document-collections.create', compact('companies'));
        }

        // Regular user: use their company
        $bankStatements = BankStatement::where('company_id', $user->company_id)
            ->where('ocr_status', 'completed')
            ->whereDoesntHave('documentItem')
            ->with('bank')
            ->latest()
            ->get();

        return view('document-collections.create', compact('bankStatements'));
    }

    /**
     * Store a new collection
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'company_id' => $user->isSuperAdmin() ? 'required|exists:companies,id' : 'nullable',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'bank_statement_ids' => 'nullable|array',
            'bank_statement_ids.*' => 'exists:bank_statements,id',
            'is_active' => 'boolean',
        ]);

        // Determine company_id
        $companyId = $user->isSuperAdmin() 
            ? $validated['company_id'] 
            : $user->company_id;

        try {
            DB::beginTransaction();

            $collection = DocumentCollection::create([
                'uuid' => Str::uuid(),
                'company_id' => $companyId,
                'user_id' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'document_count' => 0,
            ]);

            // Add bank statements as document items
            if (!empty($validated['bank_statement_ids'])) {
                $sortOrder = 0;
                foreach ($validated['bank_statement_ids'] as $statementId) {
                    $statement = BankStatement::find($statementId);
                    
                    // Verify statement belongs to the same company
                    if ($statement && $statement->company_id === $companyId) {
                        $collection->items()->create([
                            'uuid' => Str::uuid(),
                            'company_id' => $companyId,
                            'bank_statement_id' => $statementId,
                            'document_type' => 'bank_statement',
                            'sort_order' => $sortOrder++,
                            'knowledge_status' => 'ready',
                        ]);
                    }
                }

                $collection->update(['document_count' => $sortOrder]);
            }

            DB::commit();

            return redirect()->route('document-collections.show', $collection)
                ->with('success', 'Collection created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create collection: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to create collection: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified collection (FIXED: Proper authorization & null checks)
     */
    public function show(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // ===================================
        // AUTHORIZATION
        // ===================================
        if ($user->isSuperAdmin()) {
            // Super Admin: Full access with company info
            $documentCollection->load([
                'company:id,name',
                'user:id,name,email',
                'items.bankStatement' => function($q) {
                    // ✅ TAMBAH 'uuid' untuk route model binding!
                    $q->select('id', 'uuid', 'bank_id', 'period_from', 'period_to', 
                            'total_transactions', 'total_debit_amount', 'total_credit_amount');
                },
                'items.bankStatement.bank:id,name,code,logo', // Specify columns
                'chatSessions' => function($q) {
                    $q->latest('last_activity_at')->limit(10);
                }
            ]);
        } else {
            // Regular User: Company-scoped only
            if ($documentCollection->company_id !== $user->company_id) {
                abort(403, 'You do not have permission to view this collection.');
            }
            
            $documentCollection->load([
                'user:id,name,email',
                'items.bankStatement' => function($q) {
                    // ✅ TAMBAH 'uuid' untuk route model binding!
                    $q->select('id', 'uuid', 'bank_id', 'period_from', 'period_to', 
                            'total_transactions', 'total_debit_amount', 'total_credit_amount');
                },
                'items.bankStatement.bank:id,name,code,logo', // Specify columns
                'chatSessions' => function($q) {
                    $q->latest('last_activity_at')->limit(10);
                }
            ]);
        }

        return view('document-collections.show', compact('documentCollection'));
    }

    // Add this method to DocumentCollectionController.php (after the 'items' method)

    /**
     * Start a new chat session directly from this collection
     */
    public function startChat(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            abort(403, 'You do not have permission to use this collection.');
        }

        // Validation: Collection must be active
        if (!$documentCollection->is_active) {
            return back()->with('error', 'Cannot start chat with inactive collection. Please activate it first.');
        }

        // Validation: Collection must have documents
        if ($documentCollection->document_count === 0) {
            return back()->with('error', 'Cannot start chat with empty collection. Please add documents first.');
        }

        // Validation: Collection must have ready items
        if ($documentCollection->readyItems()->count() === 0) {
            return back()->with('error', 'Collection has no ready documents. Please process the collection first.');
        }

        try {
            DB::beginTransaction();

            // Create new chat session
            $session = ChatSession::create([
                'uuid' => Str::uuid(),
                'company_id' => $documentCollection->company_id,
                'user_id' => $user->id,
                'mode' => 'collection',
                'document_collection_id' => $documentCollection->id,
                'bank_statement_id' => null,
                'title' => "Chat: {$documentCollection->name}",
                'context_description' => $documentCollection->description,
                'is_pinned' => false,
                'is_archived' => false,
            ]);

            DB::commit();

            // Log activity
            Log::info("Chat session started from collection", [
                'user_id' => $user->id,
                'collection_id' => $documentCollection->id,
                'session_id' => $session->id,
            ]);

            return redirect()->route('chat-sessions.show', $session)
                ->with('success', 'Chat session started! You can now ask questions about your documents.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start chat from collection: ' . $e->getMessage(), [
                'collection_id' => $documentCollection->id,
                'user_id' => $user->id,
            ]);
            
            return back()->with('error', 'Failed to start chat session: ' . $e->getMessage());
        }
    }

    /**
     * Show form for editing collection
     */
    public function edit(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            abort(403, 'You do not have permission to edit this collection.');
        }

        $documentCollection->load('items.bankStatement');

        $availableStatements = BankStatement::where('company_id', $documentCollection->company_id)
            ->where('ocr_status', 'completed')
            ->whereDoesntHave('documentItem')
            ->with('bank')
            ->latest()
            ->get();

        return view('document-collections.edit', compact('documentCollection', 'availableStatements'));
    }

    /**
     * Update collection
     */
    public function update(Request $request, DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            abort(403, 'You do not have permission to update this collection.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        try {
            $documentCollection->update($validated);

            return redirect()->route('document-collections.show', $documentCollection)
                ->with('success', 'Collection updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update collection: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to update collection: ' . $e->getMessage());
        }
    }

    /**
     * Delete collection
     */
    public function destroy(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            abort(403, 'You do not have permission to delete this collection.');
        }

        try {
            // Check if collection has active chat sessions
            if ($documentCollection->activeChatSessions()->exists()) {
                return back()->with('error', 'Cannot delete collection with active chat sessions.');
            }

            $documentCollection->delete();

            return redirect()->route('document-collections.index')
                ->with('success', 'Collection deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete collection: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete collection: ' . $e->getMessage());
        }
    }

    /**
     * Toggle collection active status
     */
    public function toggleActive(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            abort(403, 'You do not have permission to modify this collection.');
        }

        try {
            $documentCollection->update([
                'is_active' => !$documentCollection->is_active
            ]);

            $status = $documentCollection->is_active ? 'activated' : 'deactivated';

            return back()->with('success', "Collection {$status} successfully!");

        } catch (\Exception $e) {
            Log::error('Failed to toggle status: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Process collection (prepare for AI)
     */
    public function process(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            abort(403, 'You do not have permission to process this collection.');
        }

        try {
            // TODO: Implement AI knowledge processing
            // - Extract and index transaction data
            // - Build searchable knowledge base
            // - Update item statuses

            $documentCollection->items()->update([
                'knowledge_status' => 'processing'
            ]);

            // Simulate processing (replace with actual AI processing)
            $documentCollection->items()->update([
                'knowledge_status' => 'ready'
            ]);

            return back()->with('success', 'Collection processing started!');

        } catch (\Exception $e) {
            Log::error('Failed to process collection: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to process: ' . $e->getMessage());
        }
    }

    /**
     * Get collection statistics (AJAX)
     */
    public function statistics(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = [
            'document_count' => $documentCollection->document_count,
            'chat_count' => $documentCollection->chat_count,
            'total_chats' => $documentCollection->chatSessions()->count(),
            'active_chats' => $documentCollection->activeChatSessions()->count(),
            'ready_items' => $documentCollection->readyItems()->count(),
            'processing_items' => $documentCollection->processingItems()->count(),
            'failed_items' => $documentCollection->failedItems()->count(),
            'is_active' => $documentCollection->is_active,
            'created_at' => $documentCollection->created_at->diffForHumans(),
            'updated_at' => $documentCollection->updated_at->diffForHumans(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Get items for collection (AJAX)
     */
    public function items(DocumentCollection $documentCollection)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && $documentCollection->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $items = $documentCollection->items()
            ->with('bankStatement.bank')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }
}