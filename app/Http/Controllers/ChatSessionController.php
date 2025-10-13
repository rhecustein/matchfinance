<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\BankStatement;
use App\Models\DocumentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatSessionController extends Controller
{
    /**
     * Display listing of chat sessions (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ChatSession::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->with(['bankStatement.bank', 'documentCollection']);

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

        return view('chat-sessions.index', compact('sessions', 'stats'));
    }

    /**
     * Show form for creating new chat session
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // Get available bank statements
        $bankStatements = BankStatement::where('company_id', $user->company_id)
            ->where('ocr_status', 'completed')
            ->with('bank')
            ->latest()
            ->get();

        // Get available document collections
        $documentCollections = DocumentCollection::where('company_id', $user->company_id)
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
            'mode' => 'required|in:single,collection',
            'bank_statement_id' => 'required_if:mode,single|exists:bank_statements,id',
            'document_collection_id' => 'required_if:mode,collection|exists:document_collections,id',
            'title' => 'nullable|string|max:255',
            'context_description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $session = ChatSession::create([
                'uuid' => Str::uuid(),
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'mode' => $validated['mode'],
                'bank_statement_id' => $validated['bank_statement_id'] ?? null,
                'document_collection_id' => $validated['document_collection_id'] ?? null,
                'title' => $validated['title'] ?? $this->generateDefaultTitle($validated),
                'context_description' => $validated['context_description'],
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
        // Company ownership check
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $chatSession->load([
            'messages' => function($q) {
                $q->orderBy('created_at', 'asc');
            },
            'bankStatement.bank',
            'documentCollection',
            'knowledgeSnapshot'
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
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

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
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

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
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $chatSession->archive();

        return back()->with('success', 'Chat session archived!');
    }

    /**
     * Unarchive chat session
     */
    public function unarchive(ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $chatSession->unarchive();

        return back()->with('success', 'Chat session restored!');
    }

    /**
     * Pin chat session
     */
    public function pin(ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $chatSession->pin();

        return back()->with('success', 'Chat session pinned!');
    }

    /**
     * Unpin chat session
     */
    public function unpin(ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $chatSession->unpin();

        return back()->with('success', 'Chat session unpinned!');
    }

    /**
     * Update session title (AJAX)
     */
    public function updateTitle(Request $request, ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        try {
            $chatSession->update(['title' => $validated['title']]);

            return response()->json([
                'success' => true,
                'message' => 'Title updated successfully!',
                'title' => $chatSession->title,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update title: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get messages for session (AJAX)
     */
    public function messages(ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $messages = $chatSession->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Send message (AJAX) - For future AI integration
     */
    public function sendMessage(Request $request, ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        try {
            DB::beginTransaction();

            // Store user message
            $userMessage = ChatMessage::create([
                'uuid' => Str::uuid(),
                'chat_session_id' => $chatSession->id,
                'role' => 'user',
                'content' => $validated['content'],
                'status' => 'completed',
            ]);

            // TODO: Call AI API here and get response
            // For now, create a placeholder assistant response
            $assistantMessage = ChatMessage::create([
                'uuid' => Str::uuid(),
                'chat_session_id' => $chatSession->id,
                'role' => 'assistant',
                'content' => 'AI integration coming soon. Your message: ' . $validated['content'],
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get session statistics (AJAX)
     */
    public function statistics(ChatSession $chatSession)
    {
        abort_unless($chatSession->company_id === auth()->user()->company_id, 403);
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $stats = [
            'message_count' => $chatSession->message_count,
            'total_tokens' => $chatSession->total_tokens,
            'total_cost' => number_format($chatSession->total_cost, 4),
            'created_at' => $chatSession->created_at->diffForHumans(),
            'last_activity' => $chatSession->last_activity_at->diffForHumans(),
            'mode' => $chatSession->mode,
            'context' => $chatSession->getContextInfo(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Generate default title based on context
     */
    private function generateDefaultTitle(array $validated): string
    {
        if ($validated['mode'] === 'single' && isset($validated['bank_statement_id'])) {
            $statement = BankStatement::find($validated['bank_statement_id']);
            return $statement ? "Chat: {$statement->bank->name} - {$statement->period_from->format('M Y')}" : 'New Chat';
        }

        if ($validated['mode'] === 'collection' && isset($validated['document_collection_id'])) {
            $collection = DocumentCollection::find($validated['document_collection_id']);
            return $collection ? "Chat: {$collection->name}" : 'New Chat';
        }

        return 'New Chat Session';
    }

    /**
     * API: Stream message response (for future AI streaming)
     */
    public function apiStreamMessage(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:chat_sessions,id',
            'message' => 'required|string|max:10000',
        ]);

        // TODO: Implement streaming response with AI API
        return response()->json([
            'success' => false,
            'message' => 'Streaming not implemented yet',
        ], 501);
    }
}