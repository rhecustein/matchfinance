<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * ChatController - Legacy/Wrapper Controller
 * 
 * This controller serves as a legacy wrapper or redirect layer.
 * Main chat functionality is handled by ChatSessionController and ChatMessageController.
 */
class ChatController extends Controller
{
    /**
     * Redirect to chat sessions index
     */
    public function index()
    {
        return redirect()->route('chat-sessions.index');
    }

    /**
     * Redirect to create new chat session
     */
    public function create()
    {
        return redirect()->route('chat-sessions.create');
    }

    /**
     * Show chat interface (redirect to session)
     */
    public function show($id)
    {
        return redirect()->route('chat-sessions.show', $id);
    }
}