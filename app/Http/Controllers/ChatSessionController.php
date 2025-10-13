<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\BankStatement;
use App\Models\DocumentCollection;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatSessionController extends Controller
{
    /**
     * Display listing of chat sessions (Company Scoped or Super Admin)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Super Admin: Can see all sessions from all companies
        if ($user->isSuperAdmin()) {
            $query = ChatSession::with(['user', 'company', 'bankStatement.bank', 'documentCollection']);

            // Filter by company
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            // Regular users: Only their own sessions
            $query = ChatSession::where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->with(['bankStatement.bank', 'documentCollection']);
        }

        // Filter by mode
        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }

        // Filter by archived status
        if ($request->filled('archived')) {
            $isArchived = $request->boolean('archived');
            $query->where('is_archived', $isArchived);
        } else {
            // Default: show only active sessions
            $query->where('is_archived', false);
        }

        // Search by title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('context_description', 'like', "%{$search}%");
            });
        }

        // Sort: pinned first, then by last activity
        $sessions = $query->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->paginate(20);

        // Stats
        if ($user->isSuperAdmin()) {
            $statsQuery = ChatSession::query();
            if ($request->filled('company_id')) {
                $statsQuery->where('company_id', $request->company_id);
            }
            
            $stats = [
                'total' => $statsQuery->count(),
                'active' => (clone $statsQuery)->where('is_archived', false)->count(),
                'archived' => (clone $statsQuery)->where('is_archived', true)->count(),
            ];

            $companies = Company::orderBy('name')->get(['id', 'name']);
        } else {
            $stats = [
                'total' => ChatSession::where('company_id', $user->company_id)
                    ->where('user_id', $user->id)
                    ->count(),
                'active' => ChatSession::where('company_id', $user->company_id)
                    ->where('user_id', $user->id)
                    ->where('is_archived', false)
                    ->count(),
                'archived' => ChatSession::where('company_id', $user->company_id)
                    ->where('user_id', $user->id)
                    ->where('is_archived', true)
                    ->count(),
            ];
            $companies = null;
        }

        return view('chat-sessions.index', compact('sessions', 'stats', 'companies'));
    }

    /**
     * Show form for creating new chat session
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
                
                // Get available resources for selected company
                $bankStatements = BankStatement::where('company_id', $selectedCompanyId)
                    ->where('ocr_status', 'completed')
                    ->with('bank')
                    ->latest()
                    ->get();

                $documentCollections = DocumentCollection::where('company_id', $selectedCompanyId)
                    ->where('is_active', true)
                    ->latest()
                    ->get();
                
                // Pre-select mode and source from query params
                $mode = $request->get('mode', 'single');
                $bankStatementId = $request->get('bank_statement_id');
                $collectionId = $request->get('collection_id');

                return view('chat-sessions.create', compact(
                    'bankStatements',
                    'documentCollections',
                    'mode',
                    'bankStatementId',
                    'collectionId',
                    'companies',
                    'selectedCompany'
                ));
            }
            
            // Show company selection first
            return view('chat-sessions.create', compact('companies'));
        }

        // Regular user: use their company
        $bankStatements = BankStatement::where('company_id', $user->company_id)
            ->where('ocr_status', 'completed')
            ->with('bank')
            ->latest()
            ->get();

        $documentCollections = DocumentCollection::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->latest()
            ->get();

        $mode = $request->get('mode', 'single');
        $bankStatementId = $request->get('bank_statement_id');
        $collectionId = $request->get('collection_id');

        return view('chat-sessions.create', compact(
            'bankStatements',
            'documentCollections',
            'mode',
            'bankStatementId',
            'collectionId'
        ));
    }

    /**
     * Store a new chat session
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'company_id' => $user->isSuperAdmin() ? 'required|exists:companies,id' : 'nullable',
            'mode' => 'required|in:single,collection',
            'bank_statement_id' => 'required_if:mode,single|exists:bank_statements,id',
            'document_collection_id' => 'required_if:mode,collection|exists:document_collections,id',
            'title' => 'nullable|string|max:255',
            'context_description' => 'nullable|string|max:1000',
        ]);

        // Determine company_id
        $companyId = $user->isSuperAdmin() 
            ? $validated['company_id'] 
            : $user->company_id;

        // Verify ownership of selected resources
        if (isset($validated['bank_statement_id'])) {
            $statement = BankStatement::findOrFail($validated['bank_statement_id']);
            if ($statement->company_id !== $companyId) {
                return back()->with('error', 'Invalid bank statement selection.');
            }
        }

        if (isset($validated['document_collection_id'])) {
            $collection = DocumentCollection::findOrFail($validated['document_collection_id']);
            if ($collection->company_id !== $companyId) {
                return back()->with('error', 'Invalid collection selection.');
            }
        }

        try {
            DB::beginTransaction();

            $session = ChatSession::create([
                'uuid' => Str::uuid(),
                'company_id' => $companyId,
                'user_id' => $user->id,
                'mode' => $validated['mode'],
                'bank_statement_id' => $validated['bank_statement_id'] ?? null,
                'document_collection_id' => $validated['document_collection_id'] ?? null,
                'title' => $validated['title'] ?? $this->generateDefaultTitle($validated),
                'context_description' => $validated['context_description'] ?? null,
                'is_pinned' => false,
                'is_archived' => false,
            ]);

            DB::commit();

            return redirect()->route('chat-sessions.show', $session)
                ->with('success', 'Chat session created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create chat session: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to create chat session: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified chat session
     */
    public function show(ChatSession $chatSession)
    {
        $user = auth()->user();
        
        // Authorization: super admin or owner
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        $chatSession->load([
            'messages' => function($q) {
                $q->orderBy('created_at', 'asc');
            },
            'bankStatement.bank',
            'documentCollection',
            'company',
            'user'
        ]);

        // Update activity
        $chatSession->updateActivity();

        return view('chat-sessions.show', compact('chatSession'));
    }

    /**
     * Update chat session
     */
    public function update(Request $request, ChatSession $chatSession)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'context_description' => 'nullable|string|max:1000',
        ]);

        try {
            $chatSession->update($validated);

            return back()->with('success', 'Chat session updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update chat session: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update session: ' . $e->getMessage());
        }
    }

    /**
     * Delete chat session
     */
    public function destroy(ChatSession $chatSession)
    {
        $user = auth()->user();
        
        // Authorization
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        try {
            $chatSession->delete();

            return redirect()->route('chat-sessions.index')
                ->with('success', 'Chat session deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete chat session: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete session: ' . $e->getMessage());
        }
    }

    /**
     * Archive chat session
     */
    public function archive(ChatSession $chatSession)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        $chatSession->archive();

        return back()->with('success', 'Chat session archived!');
    }

    /**
     * Unarchive chat session
     */
    public function unarchive(ChatSession $chatSession)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        $chatSession->unarchive();

        return back()->with('success', 'Chat session restored!');
    }

    /**
     * Pin chat session
     */
    public function pin(ChatSession $chatSession)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        $chatSession->pin();

        return back()->with('success', 'Chat session pinned!');
    }

    /**
     * Unpin chat session
     */
    public function unpin(ChatSession $chatSession)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && 
            ($chatSession->company_id !== $user->company_id || $chatSession->user_id !== $user->id)) {
            abort(403);
        }

        $chatSession->unpin();

        return back()->with('success', 'Chat session unpinned!');
    }

    // ... rest of the methods (updateTitle, messages, sendMessage, statistics, etc.)
    // Keep them the same but add authorization checks

    /**
     * Generate default title based on context
     */
    private function generateDefaultTitle(array $validated): string
    {
        if ($validated['mode'] === 'single' && isset($validated['bank_statement_id'])) {
            $statement = BankStatement::find($validated['bank_statement_id']);
            return $statement ? "Chat: {$statement->bank->name} - {$statement->period_start->format('M Y')}" : 'New Chat';
        }

        if ($validated['mode'] === 'collection' && isset($validated['document_collection_id'])) {
            $collection = DocumentCollection::find($validated['document_collection_id']);
            return $collection ? "Chat: {$collection->name}" : 'New Chat';
        }

        return 'New Chat Session';
    }
}