<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatMessageController extends Controller
{
    /**
     * Display message details (AJAX)
     */
    public function show(ChatMessage $chatMessage)
    {
        // Company ownership check via session
        $session = $chatMessage->chatSession;
        abort_unless($session->company_id === auth()->user()->company_id, 403);
        abort_unless($session->user_id === auth()->id(), 403);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $chatMessage->id,
                'uuid' => $chatMessage->uuid,
                'role' => $chatMessage->role,
                'content' => $chatMessage->content,
                'status' => $chatMessage->status,
                'model' => $chatMessage->model,
                'tokens' => [
                    'prompt' => $chatMessage->prompt_tokens,
                    'completion' => $chatMessage->completion_tokens,
                    'total' => $chatMessage->total_tokens,
                ],
                'cost' => $chatMessage->cost,
                'response_time_ms' => $chatMessage->response_time_ms,
                'rating' => $chatMessage->user_rating,
                'feedback' => $chatMessage->user_feedback,
                'created_at' => $chatMessage->created_at->toIso8601String(),
                'references' => [
                    'transactions' => $chatMessage->referenced_transactions,
                    'categories' => $chatMessage->referenced_categories,
                    'accounts' => $chatMessage->referenced_accounts,
                ],
            ],
        ]);
    }

    /**
     * Delete a message
     */
    public function destroy(ChatMessage $chatMessage)
    {
        $session = $chatMessage->chatSession;
        abort_unless($session->company_id === auth()->user()->company_id, 403);
        abort_unless($session->user_id === auth()->id(), 403);

        try {
            // Only allow deleting user messages
            if ($chatMessage->role !== 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only user messages can be deleted',
                ], 403);
            }

            $chatMessage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully!',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rate a message (thumbs up/down)
     */
    public function rate(Request $request, ChatMessage $chatMessage)
    {
        $session = $chatMessage->chatSession;
        abort_unless($session->company_id === auth()->user()->company_id, 403);
        abort_unless($session->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'rating' => 'required|in:up,down,neutral',
        ]);

        try {
            // Only allow rating assistant messages
            if ($chatMessage->role !== 'assistant') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only assistant messages can be rated',
                ], 403);
            }

            $chatMessage->update([
                'user_rating' => $validated['rating'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rating saved successfully!',
                'rating' => $chatMessage->user_rating,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to rate message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save rating: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit feedback for a message
     */
    public function feedback(Request $request, ChatMessage $chatMessage)
    {
        $session = $chatMessage->chatSession;
        abort_unless($session->company_id === auth()->user()->company_id, 403);
        abort_unless($session->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'feedback' => 'required|string|max:1000',
        ]);

        try {
            // Only allow feedback on assistant messages
            if ($chatMessage->role !== 'assistant') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only assistant messages can receive feedback',
                ], 403);
            }

            $chatMessage->update([
                'user_feedback' => $validated['feedback'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback submitted successfully!',
                'feedback' => $chatMessage->user_feedback,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit feedback: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate assistant response
     */
    public function regenerate(Request $request, ChatMessage $chatMessage)
    {
        $session = $chatMessage->chatSession;
        abort_unless($session->company_id === auth()->user()->company_id, 403);
        abort_unless($session->user_id === auth()->id(), 403);

        try {
            // Only allow regenerating assistant messages
            if ($chatMessage->role !== 'assistant') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only assistant messages can be regenerated',
                ], 403);
            }

            // Mark current message as regenerated (optional: soft delete or flag)
            $chatMessage->update([
                'status' => 'regenerated',
            ]);

            // TODO: Call AI API to regenerate response
            // For now, create placeholder
            $newMessage = ChatMessage::create([
                'uuid' => Str::uuid(),
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Regenerated response - AI integration coming soon',
                'status' => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Response regenerated successfully!',
                'new_message' => $newMessage,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate: ' . $e->getMessage(),
            ], 500);
        }
    }
}