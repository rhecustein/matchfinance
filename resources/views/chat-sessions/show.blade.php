<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('chat-sessions.index') }}" 
                   class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $chatSession->title }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $chatSession->message_count }} messages · Last activity {{ $chatSession->last_activity_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if(!$chatSession->is_pinned)
                <form action="{{ route('chat-sessions.pin', $chatSession) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-sm">
                        <i class="fas fa-thumbtack mr-2"></i>Pin
                    </button>
                </form>
                @else
                <form action="{{ route('chat-sessions.unpin', $chatSession) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                        <i class="fas fa-thumbtack mr-2"></i>Unpin
                    </button>
                </form>
                @endif

                @if(!$chatSession->is_archived)
                <form action="{{ route('chat-sessions.archive', $chatSession) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm">
                        <i class="fas fa-archive mr-2"></i>Archive
                    </button>
                </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Main Chat Area -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- Context Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-4 border border-slate-700 shadow-xl">
                    <div class="flex items-center gap-4">
                        @if($chatSession->mode === 'single')
                        <div class="p-3 bg-blue-900/30 rounded-lg">
                            <i class="fas fa-file text-blue-400 text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-400 text-sm">Chatting with</p>
                            <h3 class="text-white font-semibold">
                                {{ $chatSession->bankStatement->bank->name ?? 'Unknown Bank' }}
                            </h3>
                            <p class="text-gray-400 text-sm">
                                {{ $chatSession->bankStatement->period_start->format('M Y') }} - 
                                {{ $chatSession->bankStatement->period_end->format('M Y') }}
                                ({{ number_format($chatSession->bankStatement->total_transactions) }} transactions)
                            </p>
                        </div>
                        <a href="{{ route('bank-statements.show', $chatSession->bankStatement) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-2"></i>View Statement
                        </a>
                        @else
                        <div class="p-3 bg-purple-900/30 rounded-lg">
                            <i class="fas fa-folder text-purple-400 text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-400 text-sm">Chatting with collection</p>
                            <h3 class="text-white font-semibold">{{ $chatSession->documentCollection->name }}</h3>
                            <p class="text-gray-400 text-sm">
                                {{ $chatSession->documentCollection->document_count }} documents · 
                                {{ number_format($chatSession->documentCollection->total_transactions ?? 0) }} total transactions
                            </p>
                        </div>
                        <a href="{{ route('document-collections.show', $chatSession->documentCollection) }}" 
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-2"></i>View Collection
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                    <!-- Messages Container -->
                    <div id="messages-container" class="h-[600px] overflow-y-auto p-6 space-y-4">
                        @forelse($chatSession->messages as $message)
                        
                        @if($message->role === 'user')
                        <!-- User Message -->
                        <div class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="bg-blue-600 rounded-2xl rounded-tr-sm p-4">
                                    <p class="text-white whitespace-pre-wrap">{{ $message->content }}</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 text-right">
                                    {{ $message->created_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                        @else
                        <!-- Assistant Message -->
                        <div class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 flex items-center justify-center">
                                        <i class="fas fa-robot text-white text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="bg-slate-900/50 border border-slate-700 rounded-2xl rounded-tl-sm p-4">
                                            <p class="text-gray-200 whitespace-pre-wrap">{{ $message->content }}</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $message->created_at->format('H:i') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @empty
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <i class="fas fa-comment-dots text-gray-600 text-5xl mb-4"></i>
                                <h3 class="text-white text-lg font-semibold mb-2">No messages yet</h3>
                                <p class="text-gray-400">Start the conversation by sending a message below</p>
                            </div>
                        </div>
                        @endforelse
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 bg-slate-900/50 border-t border-slate-700">
                        <form id="chat-form" class="flex gap-3">
                            <input type="text" 
                                   id="message-input"
                                   placeholder="Ask a question about your transactions..."
                                   class="flex-1 px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                            <button type="submit" 
                                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition font-semibold">
                                <i class="fas fa-paper-plane mr-2"></i>Send
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            AI integration coming soon. Messages will be saved.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Session Stats -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                        Session Stats
                    </h4>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Messages</span>
                            <span class="text-white font-bold">{{ $chatSession->message_count }}</span>
                        </div>

                        @if($chatSession->total_tokens > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Tokens Used</span>
                            <span class="text-white font-bold">{{ number_format($chatSession->total_tokens) }}</span>
                        </div>
                        @endif

                        @if($chatSession->total_cost > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Cost</span>
                            <span class="text-white font-bold">${{ number_format($chatSession->total_cost, 4) }}</span>
                        </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Created</span>
                            <span class="text-white font-bold text-xs">{{ $chatSession->created_at->format('M d, Y') }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Last Active</span>
                            <span class="text-white font-bold text-xs">{{ $chatSession->last_activity_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Session Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Session Info
                    </h4>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Mode</label>
                            @if($chatSession->mode === 'single')
                            <span class="inline-block px-3 py-1 bg-blue-900/30 text-blue-400 rounded-full text-xs font-semibold">
                                <i class="fas fa-file mr-1"></i>Single Document
                            </span>
                            @else
                            <span class="inline-block px-3 py-1 bg-purple-900/30 text-purple-400 rounded-full text-xs font-semibold">
                                <i class="fas fa-folder mr-1"></i>Collection
                            </span>
                            @endif
                        </div>

                        @if($chatSession->context_description)
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Context</label>
                            <p class="text-white text-sm">{{ $chatSession->context_description }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Status</label>
                            @if($chatSession->is_archived)
                            <span class="inline-block px-3 py-1 bg-gray-900/30 text-gray-400 rounded-full text-xs font-semibold">
                                <i class="fas fa-archive mr-1"></i>Archived
                            </span>
                            @else
                            <span class="inline-block px-3 py-1 bg-green-900/30 text-green-400 rounded-full text-xs font-semibold">
                                <i class="fas fa-check mr-1"></i>Active
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-cog text-purple-500 mr-2"></i>
                        Actions
                    </h4>

                    <div class="space-y-2">
                        <button onclick="editTitle()" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold text-left">
                            <i class="fas fa-edit mr-2"></i>Edit Title
                        </button>

                        <form action="{{ route('chat-sessions.destroy', $chatSession) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('Delete this chat session? All messages will be lost.')"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold text-left">
                                <i class="fas fa-trash mr-2"></i>Delete Session
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        // Auto-scroll to bottom on load
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });

        // Handle chat form submission
        document.getElementById('chat-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message) return;

            // Disable input while sending
            input.disabled = true;
            
            try {
                const response = await fetch('{{ route("chat-sessions.send-message", $chatSession) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ content: message })
                });

                if (response.ok) {
                    // Reload page to show new messages
                    window.location.reload();
                } else {
                    alert('Failed to send message. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                input.disabled = false;
            }
        });

        // Edit title function
        function editTitle() {
            const newTitle = prompt('Enter new title:', '{{ $chatSession->title }}');
            if (newTitle && newTitle.trim()) {
                fetch('{{ route("chat-sessions.update-title", $chatSession) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ title: newTitle.trim() })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Failed to update title');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
    </script>
    @endpush
</x-app-layout>