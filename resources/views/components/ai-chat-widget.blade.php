{{-- AI Chat Widget Bubble - resources/views/components/ai-chat-widget.blade.php --}}
<div x-data="chatWidget()" 
     x-init="init()"
     class="fixed bottom-6 right-6 z-50"
     @keydown.escape="isOpen = false">
    
    {{-- Chat Window --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 transform translate-y-4 scale-95"
         class="mb-4 w-96 h-[600px] bg-slate-800 rounded-2xl shadow-2xl border border-slate-700 flex flex-col overflow-hidden">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-robot text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-sm">AI Assistant</h3>
                    <p class="text-blue-100 text-xs flex items-center">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5 animate-pulse"></span>
                        Online
                    </p>
                </div>
            </div>
            <button @click="isOpen = false" 
                    class="text-white/80 hover:text-white transition p-2 hover:bg-white/10 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-slate-900/50 p-3 border-b border-slate-700">
            <div class="flex items-center space-x-2 overflow-x-auto">
                <button @click="sendQuickMessage('Help me analyze my transactions')"
                        class="flex-shrink-0 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-full transition">
                    <i class="fas fa-chart-line mr-1"></i>Analyze Transactions
                </button>
                <button @click="sendQuickMessage('Show me spending summary')"
                        class="flex-shrink-0 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-full transition">
                    <i class="fas fa-wallet mr-1"></i>Spending Summary
                </button>
                <button @click="sendQuickMessage('Create expense report')"
                        class="flex-shrink-0 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-full transition">
                    <i class="fas fa-file-alt mr-1"></i>Create Report
                </button>
            </div>
        </div>

        {{-- Messages Container --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messagesContainer">
            {{-- Welcome Message --}}
            <div x-show="messages.length === 0" class="text-center py-8">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <h4 class="text-white font-semibold mb-2">Welcome to AI Assistant!</h4>
                <p class="text-gray-400 text-sm">Ask me anything about your finances, transactions, or reports.</p>
            </div>

            {{-- Messages --}}
            <template x-for="(message, index) in messages" :key="index">
                <div :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="message.role === 'user' ? 'bg-blue-600 text-white' : 'bg-slate-700 text-gray-200'"
                         class="max-w-[80%] rounded-2xl px-4 py-2.5 shadow-lg">
                        <p class="text-sm whitespace-pre-wrap" x-text="message.content"></p>
                        <p class="text-xs mt-1 opacity-60" x-text="message.time"></p>
                    </div>
                </div>
            </template>

            {{-- Typing Indicator --}}
            <div x-show="isTyping" class="flex justify-start">
                <div class="bg-slate-700 rounded-2xl px-4 py-3">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="p-4 bg-slate-900/50 border-t border-slate-700">
            <form @submit.prevent="sendMessage()" class="flex items-end space-x-2">
                <div class="flex-1">
                    <textarea x-model="newMessage"
                              @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                              placeholder="Type your message..."
                              rows="1"
                              class="w-full bg-slate-700 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none max-h-32"></textarea>
                </div>
                <button type="submit"
                        :disabled="!newMessage.trim() || isSending"
                        :class="newMessage.trim() && !isSending ? 'bg-purple-600 hover:bg-purple-700' : 'bg-slate-600 cursor-not-allowed'"
                        class="p-3 rounded-xl text-white transition shadow-lg">
                    <i class="fas" :class="isSending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                </button>
            </form>
            <p class="text-xs text-gray-500 mt-2 text-center">
                <i class="fas fa-shield-alt mr-1"></i>Your conversations are encrypted
            </p>
        </div>
    </div>

    {{-- Chat Bubble Button --}}
    <button @click="toggleChat()"
            class="w-16 h-16 bg-gradient-to-br from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 rounded-full shadow-2xl flex items-center justify-center transition transform hover:scale-110 relative">
        <i class="fas text-white text-xl" :class="isOpen ? 'fa-times' : 'fa-comments'"></i>
        
        {{-- Notification Badge --}}
        <span x-show="unreadCount > 0"
              x-transition
              class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center border-2 border-slate-900"
              x-text="unreadCount"></span>
        
        {{-- Pulse Animation --}}
        <span class="absolute inset-0 rounded-full bg-purple-600 animate-ping opacity-20"></span>
    </button>
</div>

<script>
function chatWidget() {
    return {
        isOpen: false,
        messages: [],
        newMessage: '',
        isTyping: false,
        isSending: false,
        unreadCount: 0,
        sessionId: null,

        init() {
            // Load chat history from localStorage
            const saved = localStorage.getItem('chatWidget');
            if (saved) {
                const data = JSON.parse(saved);
                this.messages = data.messages || [];
                this.sessionId = data.sessionId;
            }

            // Auto-open on first visit
            if (!localStorage.getItem('chatWidget_visited')) {
                setTimeout(() => {
                    this.isOpen = true;
                    this.addSystemMessage('👋 Hi! I\'m your AI financial assistant. How can I help you today?');
                    localStorage.setItem('chatWidget_visited', 'true');
                }, 2000);
            }
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.unreadCount = 0;
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },

        async sendMessage() {
            if (!this.newMessage.trim() || this.isSending) return;

            const userMessage = this.newMessage.trim();
            this.newMessage = '';

            // Add user message
            this.addMessage({
                role: 'user',
                content: userMessage,
                time: this.getCurrentTime()
            });

            this.isSending = true;
            this.isTyping = true;

            try {
                // Send to backend API
                const response = await fetch('{{ route("chat-sessions.send-message", ":sessionId") }}'.replace(':sessionId', this.getOrCreateSession()), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: userMessage
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.addMessage({
                        role: 'assistant',
                        content: data.reply,
                        time: this.getCurrentTime()
                    });
                } else {
                    throw new Error(data.message || 'Failed to send message');
                }
            } catch (error) {
                console.error('Chat error:', error);
                this.addMessage({
                    role: 'assistant',
                    content: 'Sorry, I encountered an error. Please try again or contact support.',
                    time: this.getCurrentTime()
                });
            } finally {
                this.isTyping = false;
                this.isSending = false;
                this.scrollToBottom();
            }
        },

        async sendQuickMessage(message) {
            this.newMessage = message;
            await this.sendMessage();
        },

        addMessage(message) {
            this.messages.push(message);
            this.saveToStorage();
            
            if (!this.isOpen && message.role === 'assistant') {
                this.unreadCount++;
            }

            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        addSystemMessage(content) {
            this.addMessage({
                role: 'assistant',
                content: content,
                time: this.getCurrentTime()
            });
        },

        getOrCreateSession() {
            if (!this.sessionId) {
                // Create new session ID
                this.sessionId = 'widget_' + Date.now();
                this.saveToStorage();
            }
            return this.sessionId;
        },

        getCurrentTime() {
            return new Date().toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        },

        scrollToBottom() {
            if (this.$refs.messagesContainer) {
                this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
            }
        },

        saveToStorage() {
            localStorage.setItem('chatWidget', JSON.stringify({
                messages: this.messages,
                sessionId: this.sessionId
            }));
        },

        clearChat() {
            if (confirm('Clear chat history?')) {
                this.messages = [];
                this.sessionId = null;
                localStorage.removeItem('chatWidget');
                this.addSystemMessage('Chat history cleared. How can I help you?');
            }
        }
    }
}
</script>

<style>
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>