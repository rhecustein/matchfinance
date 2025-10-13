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

                <!-- Quick Prompts -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-4 border border-slate-700 shadow-xl">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-white font-semibold flex items-center gap-2">
                            <i class="fas fa-lightbulb text-yellow-500"></i>
                            Quick Prompts
                        </h4>
                        <div id="quick-prompts-status" class="hidden">
                            <span class="text-xs text-blue-400 flex items-center gap-1">
                                <i class="fas fa-spinner fa-spin"></i>
                                Processing...
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <button onclick="sendQuickPrompt('Berikan daftar 10 transaksi tertinggi')" 
                                class="p-3 bg-gradient-to-r from-blue-900/50 to-blue-800/50 hover:from-blue-800/70 hover:to-blue-700/70 border border-blue-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-chart-line text-blue-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Top Transactions</p>
                                    <p class="text-gray-400 text-xs">10 transaksi tertinggi</p>
                                </div>
                            </div>
                        </button>
                        
                        <button onclick="sendQuickPrompt('Analisis transaksi yang mencurigakan atau tidak wajar')" 
                                class="p-3 bg-gradient-to-r from-red-900/50 to-red-800/50 hover:from-red-800/70 hover:to-red-700/70 border border-red-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-red-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Suspicious Activity</p>
                                    <p class="text-gray-400 text-xs">Deteksi anomali</p>
                                </div>
                            </div>
                        </button>
                        
                        <button onclick="sendQuickPrompt('Tampilkan transaksi yang berulang atau duplikat')" 
                                class="p-3 bg-gradient-to-r from-yellow-900/50 to-yellow-800/50 hover:from-yellow-800/70 hover:to-yellow-700/70 border border-yellow-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-copy text-yellow-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Recurring Payments</p>
                                    <p class="text-gray-400 text-xs">Pola berulang</p>
                                </div>
                            </div>
                        </button>
                        
                        <button onclick="sendQuickPrompt('Berikan ringkasan dan insight dari data transaksi ini')" 
                                class="p-3 bg-gradient-to-r from-purple-900/50 to-purple-800/50 hover:from-purple-800/70 hover:to-purple-700/70 border border-purple-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-brain text-purple-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Smart Insights</p>
                                    <p class="text-gray-400 text-xs">Analisis mendalam</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="sendQuickPrompt('Tampilkan tren pengeluaran bulanan dalam grafik')" 
                                class="p-3 bg-gradient-to-r from-green-900/50 to-green-800/50 hover:from-green-800/70 hover:to-green-700/70 border border-green-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-chart-area text-green-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Monthly Trends</p>
                                    <p class="text-gray-400 text-xs">Grafik tren</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="sendQuickPrompt('Analisis kategori pengeluaran terbesar')" 
                                class="p-3 bg-gradient-to-r from-orange-900/50 to-orange-800/50 hover:from-orange-800/70 hover:to-orange-700/70 border border-orange-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-pie-chart text-orange-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Category Analysis</p>
                                    <p class="text-gray-400 text-xs">Breakdown kategori</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="sendQuickPrompt('Prediksi cashflow 3 bulan ke depan')" 
                                class="p-3 bg-gradient-to-r from-cyan-900/50 to-cyan-800/50 hover:from-cyan-800/70 hover:to-cyan-700/70 border border-cyan-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-crystal-ball text-cyan-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Cashflow Prediction</p>
                                    <p class="text-gray-400 text-xs">Proyeksi keuangan</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="sendQuickPrompt('Bandingkan pengeluaran bulan ini vs bulan lalu')" 
                                class="p-3 bg-gradient-to-r from-pink-900/50 to-pink-800/50 hover:from-pink-800/70 hover:to-pink-700/70 border border-pink-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-balance-scale text-pink-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Compare Periods</p>
                                    <p class="text-gray-400 text-xs">Perbandingan bulan</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="sendQuickPrompt('Saran penghematan berdasarkan pola transaksi')" 
                                class="p-3 bg-gradient-to-r from-indigo-900/50 to-indigo-800/50 hover:from-indigo-800/70 hover:to-indigo-700/70 border border-indigo-700/50 rounded-lg text-left transition-all duration-300 group transform hover:scale-105 hover:shadow-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-piggy-bank text-indigo-400 mt-1 group-hover:scale-110 transition-transform"></i>
                                <div>
                                    <p class="text-white text-sm font-semibold">Saving Tips</p>
                                    <p class="text-gray-400 text-xs">Rekomendasi hemat</p>
                                </div>
                            </div>
                        </button>
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
                                <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl rounded-tr-sm p-4 shadow-lg">
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
                                <div class="inline-block p-6 bg-gradient-to-br from-purple-900/30 to-blue-900/30 rounded-3xl border border-purple-700/50 mb-4">
                                    <i class="fas fa-comment-dots text-purple-400 text-5xl"></i>
                                </div>
                                <h3 class="text-white text-lg font-semibold mb-2">Ready to analyze your transactions!</h3>
                                <p class="text-gray-400 mb-4">Try one of the quick prompts above or ask your own question</p>
                                <div class="flex items-center justify-center gap-2 text-xs text-gray-500">
                                    <i class="fas fa-magic"></i>
                                    <span>Powered by AI Analysis</span>
                                </div>
                            </div>
                        </div>
                        @endforelse
                        
                        <!-- Typing Indicator (hidden by default) -->
                        <div id="typing-indicator" class="hidden flex justify-start">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 flex items-center justify-center">
                                    <i class="fas fa-robot text-white text-sm"></i>
                                </div>
                                <div class="bg-slate-900/50 border border-slate-700 rounded-2xl rounded-tl-sm px-6 py-4">
                                    <div class="flex gap-2">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 bg-slate-900/50 border-t border-slate-700">
                        <form id="chat-form" class="flex gap-3">
                            <input type="text" 
                                   id="message-input"
                                   placeholder="Ask a question about your transactions..."
                                   class="flex-1 px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                            <button type="submit" 
                                    id="send-button"
                                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition font-semibold shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i class="fas fa-paper-plane mr-2"></i>Send
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 mt-2 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            <span>AI-powered transaction analysis • Smart Demo Mode</span>
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
                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400 text-sm">Messages</span>
                            <span class="text-white font-bold" id="message-count">{{ $chatSession->message_count }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400 text-sm">Tokens Used</span>
                            <span class="text-white font-bold" id="token-count">{{ number_format($chatSession->total_tokens) }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400 text-sm">Cost</span>
                            <span class="text-green-400 font-bold" id="cost-display">${{ number_format($chatSession->total_cost, 4) }}</span>
                        </div>

                        <div class="p-3 bg-gradient-to-r from-blue-900/30 to-purple-900/30 border border-blue-700/50 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs text-gray-400 mb-1">Response Time</p>
                                <p class="text-2xl font-bold text-white" id="response-time">0.8s</p>
                                <p class="text-xs text-green-400">Fast</p>
                            </div>
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
                            <label class="block text-xs font-medium text-gray-400 mb-1">AI Model</label>
                            <span class="inline-block px-3 py-1 bg-green-900/30 text-green-400 rounded-full text-xs font-semibold">
                                <i class="fas fa-brain mr-1"></i>GPT-4o Mini
                            </span>
                        </div>

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
                        <button onclick="exportChat()" 
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-semibold text-left">
                            <i class="fas fa-download mr-2"></i>Export Chat
                        </button>

                        <button onclick="editTitle()" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold text-left">
                            <i class="fas fa-edit mr-2"></i>Edit Title
                        </button>

                        <button onclick="clearChat()" 
                                class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-sm font-semibold text-left">
                            <i class="fas fa-broom mr-2"></i>Clear Messages
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let messageCount = {{ $chatSession->message_count }};
        let currentChart = null;
        let chartCounter = 0;

        // Dummy data generator
        const dummyTransactions = [
            {date: '2024-01-15', desc: 'Transfer ke PT Maju Jaya', amount: 15750000, type: 'debit', category: 'Supplier'},
            {date: '2024-01-18', desc: 'Pembayaran Gaji Karyawan', amount: 12500000, type: 'debit', category: 'Payroll'},
            {date: '2024-01-20', desc: 'Penerimaan dari Customer A', amount: 25000000, type: 'credit', category: 'Sales'},
            {date: '2024-01-22', desc: 'Bayar Listrik & Internet', amount: 3250000, type: 'debit', category: 'Utilities'},
            {date: '2024-01-25', desc: 'Transfer Mencurigakan', amount: 18900000, type: 'debit', category: 'Unknown', suspicious: true},
            {date: '2024-01-28', desc: 'Subscription Software', amount: 2500000, type: 'debit', category: 'IT', recurring: true},
            {date: '2024-02-01', desc: 'Penerimaan dari Customer B', amount: 32000000, type: 'credit', category: 'Sales'},
            {date: '2024-02-05', desc: 'Pembayaran Sewa Kantor', amount: 8500000, type: 'debit', category: 'Rent', recurring: true},
            {date: '2024-02-10', desc: 'Transfer ke Vendor XYZ', amount: 11200000, type: 'debit', category: 'Supplier'},
            {date: '2024-02-15', desc: 'Pembayaran Gaji Karyawan', amount: 12500000, type: 'debit', category: 'Payroll', recurring: true}
        ];

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            setTimeout(() => container.scrollTop = container.scrollHeight, 100);
        }

        function updateStats() {
            messageCount++;
            document.getElementById('message-count').textContent = messageCount;
            
            const currentTokens = parseInt(document.getElementById('token-count').textContent.replace(/,/g, ''));
            const newTokens = currentTokens + Math.floor(Math.random() * 800) + 500;
            document.getElementById('token-count').textContent = newTokens.toLocaleString();
            
            const newCost = (newTokens * 0.000002).toFixed(4);
            document.getElementById('cost-display').textContent = '$' + newCost;

            const responseTime = (Math.random() * 1.5 + 0.5).toFixed(1);
            document.getElementById('response-time').textContent = responseTime + 's';
        }

        function addUserMessage(content) {
            const container = document.getElementById('messages-container');
            const typingIndicator = document.getElementById('typing-indicator');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex justify-end';
            messageDiv.innerHTML = `
                <div class="max-w-[80%]">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl rounded-tr-sm p-4 shadow-lg">
                        <p class="text-white whitespace-pre-wrap">${escapeHtml(content)}</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-right">
                        ${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}
                    </p>
                </div>
            `;
            container.insertBefore(messageDiv, typingIndicator);
            messageCount++;
            scrollToBottom();
        }

        function showTypingIndicator() {
            document.getElementById('typing-indicator').classList.remove('hidden');
            scrollToBottom();
        }

        function hideTypingIndicator() {
            document.getElementById('typing-indicator').classList.add('hidden');
        }

        function createTable(data) {
            let html = `
                <div class="overflow-x-auto my-3">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-600">
                                <th class="text-left py-2 px-3 text-gray-400 font-semibold">#</th>
                                <th class="text-left py-2 px-3 text-gray-400 font-semibold">Tanggal</th>
                                <th class="text-left py-2 px-3 text-gray-400 font-semibold">Deskripsi</th>
                                <th class="text-right py-2 px-3 text-gray-400 font-semibold">Nominal</th>
                                <th class="text-center py-2 px-3 text-gray-400 font-semibold">Kategori</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            data.forEach((item, idx) => {
                const amountClass = item.type === 'credit' ? 'text-green-400' : 'text-red-400';
                const badge = item.suspicious ? '<span class="ml-2 px-2 py-0.5 bg-red-900/50 text-red-400 rounded text-xs">⚠️ Suspicious</span>' : '';
                const recurringBadge = item.recurring ? '<span class="ml-2 px-2 py-0.5 bg-yellow-900/50 text-yellow-400 rounded text-xs">🔄 Recurring</span>' : '';
                
                html += `
                    <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                        <td class="py-3 px-3 text-gray-300">${idx + 1}</td>
                        <td class="py-3 px-3 text-gray-300">${item.date}</td>
                        <td class="py-3 px-3 text-white">${item.desc}${badge}${recurringBadge}</td>
                        <td class="py-3 px-3 text-right font-semibold ${amountClass}">Rp ${item.amount.toLocaleString('id-ID')}</td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-1 bg-blue-900/30 text-blue-400 rounded text-xs">${item.category}</span>
                        </td>
                    </tr>`;
            });
            
            html += `</tbody></table></div>`;
            return html;
        }

        function createChart(type, labels, datasets) {
            chartCounter++;
            const chartId = `chart-${chartCounter}`;
            const html = `
                <div class="my-4 p-4 bg-slate-800/50 rounded-lg border border-slate-700">
                    <canvas id="${chartId}" class="w-full" style="max-height: 300px;"></canvas>
                </div>
            `;
            
            setTimeout(() => {
                const ctx = document.getElementById(chartId);
                if (ctx) {
                    if (currentChart) currentChart.destroy();
                    currentChart = new Chart(ctx, {
                        type: type,
                        data: {labels: labels, datasets: datasets},
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {labels: {color: '#fff'}},
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) label += ': ';
                                            if (context.parsed.y !== null) {
                                                label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    ticks: {color: '#9ca3af'},
                                    grid: {color: 'rgba(148, 163, 184, 0.1)'}
                                },
                                x: {
                                    ticks: {color: '#9ca3af'},
                                    grid: {color: 'rgba(148, 163, 184, 0.1)'}
                                }
                            }
                        }
                    });
                }
            }, 200);
            
            return html;
        }

        function generateAIResponse(prompt) {
            const lowerPrompt = prompt.toLowerCase();
            let response = '';

            if (lowerPrompt.includes('tertinggi') || lowerPrompt.includes('top')) {
                const topTransactions = [...dummyTransactions].sort((a, b) => b.amount - a.amount).slice(0, 10);
                const totalAmount = topTransactions.reduce((sum, t) => sum + t.amount, 0);
                
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-blue-900/30 rounded-lg">
                                <i class="fas fa-chart-line text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Top 10 Transaksi Tertinggi</p>
                                <p class="text-gray-400 text-sm">Total: Rp ${totalAmount.toLocaleString('id-ID')}</p>
                            </div>
                        </div>
                        ${createTable(topTransactions)}
                        <div class="mt-3 p-3 bg-blue-900/20 border border-blue-700/50 rounded-lg">
                            <p class="text-blue-400 text-sm"><i class="fas fa-lightbulb mr-2"></i><strong>Insight:</strong> Transaksi tertinggi adalah penerimaan dari Customer B sebesar Rp 32 juta. Ada 1 transaksi yang perlu diverifikasi lebih lanjut.</p>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('mencurigakan') || lowerPrompt.includes('suspicious')) {
                const suspicious = dummyTransactions.filter(t => t.suspicious);
                
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-red-900/30 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-red-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Analisis Transaksi Mencurigakan</p>
                                <p class="text-gray-400 text-sm">Ditemukan ${suspicious.length} transaksi mencurigakan</p>
                            </div>
                        </div>
                        ${createTable(suspicious)}
                        <div class="grid grid-cols-2 gap-3 mt-3">
                            <div class="p-3 bg-red-900/20 border border-red-700/50 rounded-lg">
                                <p class="text-red-400 text-xs font-semibold mb-1">🚨 Alert Level</p>
                                <p class="text-white text-xl font-bold">High</p>
                            </div>
                            <div class="p-3 bg-orange-900/20 border border-orange-700/50 rounded-lg">
                                <p class="text-orange-400 text-xs font-semibold mb-1">📊 Risk Score</p>
                                <p class="text-white text-xl font-bold">7.5/10</p>
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-red-900/20 border border-red-700/50 rounded-lg">
                            <p class="text-red-400 text-sm"><i class="fas fa-shield-alt mr-2"></i><strong>Rekomendasi:</strong> Transfer sebesar Rp 18.9 juta ke rekening tidak dikenal memerlukan verifikasi segera. Kategori "Unknown" dan nominal besar menjadi red flag.</p>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('berulang') || lowerPrompt.includes('recurring')) {
                const recurring = dummyTransactions.filter(t => t.recurring);
                const totalRecurring = recurring.reduce((sum, t) => sum + t.amount, 0);
                
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-yellow-900/30 rounded-lg">
                                <i class="fas fa-copy text-yellow-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Transaksi Berulang / Recurring</p>
                                <p class="text-gray-400 text-sm">Total monthly: Rp ${totalRecurring.toLocaleString('id-ID')}</p>
                            </div>
                        </div>
                        ${createTable(recurring)}
                        <div class="grid grid-cols-3 gap-3 mt-3">
                            <div class="p-3 bg-purple-900/20 border border-purple-700/50 rounded-lg text-center">
                                <p class="text-purple-400 text-xs font-semibold mb-1">Payroll</p>
                                <p class="text-white text-lg font-bold">Rp 12.5M</p>
                            </div>
                            <div class="p-3 bg-blue-900/20 border border-blue-700/50 rounded-lg text-center">
                                <p class="text-blue-400 text-xs font-semibold mb-1">Rent</p>
                                <p class="text-white text-lg font-bold">Rp 8.5M</p>
                            </div>
                            <div class="p-3 bg-green-900/20 border border-green-700/50 rounded-lg text-center">
                                <p class="text-green-400 text-xs font-semibold mb-1">Software</p>
                                <p class="text-white text-lg font-bold">Rp 2.5M</p>
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-yellow-900/20 border border-yellow-700/50 rounded-lg">
                            <p class="text-yellow-400 text-sm"><i class="fas fa-calendar-check mr-2"></i><strong>Pattern Detected:</strong> Pembayaran gaji konsisten setiap tanggal 15, sewa kantor setiap awal bulan. Total fixed cost: Rp 23.5M/bulan.</p>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('ringkasan') || lowerPrompt.includes('insight')) {
                const totalDebit = dummyTransactions.filter(t => t.type === 'debit').reduce((sum, t) => sum + t.amount, 0);
                const totalCredit = dummyTransactions.filter(t => t.type === 'credit').reduce((sum, t) => sum + t.amount, 0);
                const balance = totalCredit - totalDebit;
                
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-purple-900/30 rounded-lg">
                                <i class="fas fa-brain text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Smart Financial Insights</p>
                                <p class="text-gray-400 text-sm">AI-powered analysis</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-4 bg-green-900/20 border border-green-700/50 rounded-lg text-center">
                                <p class="text-green-400 text-xs font-semibold mb-1">💰 Total Pemasukan</p>
                                <p class="text-white text-lg font-bold">Rp ${totalCredit.toLocaleString('id-ID')}</p>
                            </div>
                            <div class="p-4 bg-red-900/20 border border-red-700/50 rounded-lg text-center">
                                <p class="text-red-400 text-xs font-semibold mb-1">💸 Total Pengeluaran</p>
                                <p class="text-white text-lg font-bold">Rp ${totalDebit.toLocaleString('id-ID')}</p>
                            </div>
                            <div class="p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg text-center">
                                <p class="text-blue-400 text-xs font-semibold mb-1">📊 Net Balance</p>
                                <p class="text-white text-lg font-bold">Rp ${balance.toLocaleString('id-ID')}</p>
                            </div>
                        </div>

                        <div class="p-4 bg-gradient-to-r from-purple-900/30 to-blue-900/30 border border-purple-700/50 rounded-lg">
                            <p class="text-purple-400 font-semibold mb-2"><i class="fas fa-chart-pie mr-2"></i>Breakdown Pengeluaran:</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-gray-300">Payroll (Gaji)</span><span class="text-white font-semibold">40.2%</span></div>
                                <div class="flex justify-between"><span class="text-gray-300">Supplier & Vendor</span><span class="text-white font-semibold">28.7%</span></div>
                                <div class="flex justify-between"><span class="text-gray-300">Rent (Sewa)</span><span class="text-white font-semibold">13.5%</span></div>
                                <div class="flex justify-between"><span class="text-gray-300">Others</span><span class="text-white font-semibold">17.6%</span></div>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg">
                            <p class="text-blue-400 font-semibold mb-2"><i class="fas fa-lightbulb mr-2"></i>Key Insights:</p>
                            <ul class="space-y-1 text-sm text-gray-300">
                                <li>✅ Cashflow positif dengan net balance Rp ${balance.toLocaleString('id-ID')}</li>
                                <li>⚠️ 1 transaksi mencurigakan terdeteksi (Rp 18.9M)</li>
                                <li>📅 Fixed cost bulanan: Rp 23.5M (Payroll + Rent + Software)</li>
                                <li>💡 Revenue dari 2 customer utama (Customer A & B)</li>
                            </ul>
                        </div>

                        <div class="p-4 bg-green-900/20 border border-green-700/50 rounded-lg">
                            <p class="text-green-400 font-semibold mb-2"><i class="fas fa-check-circle mr-2"></i>Rekomendasi:</p>
                            <ul class="space-y-1 text-sm text-gray-300">
                                <li>1. Verifikasi transfer Rp 18.9M ke rekening tidak dikenal</li>
                                <li>2. Diversifikasi customer base (saat ini tergantung 2 customer)</li>
                                <li>3. Review kontrak vendor untuk optimasi biaya</li>
                                <li>4. Setup alert otomatis untuk transaksi > Rp 15M</li>
                            </ul>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('tren') || lowerPrompt.includes('trend') || lowerPrompt.includes('grafik')) {
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-green-900/30 rounded-lg">
                                <i class="fas fa-chart-area text-green-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Tren Pengeluaran Bulanan</p>
                                <p class="text-gray-400 text-sm">6 bulan terakhir</p>
                            </div>
                        </div>
                        ${createChart('line', 
                            ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb'],
                            [{
                                label: 'Pengeluaran',
                                data: [45000000, 52000000, 48000000, 58000000, 62000000, 55000000],
                                borderColor: 'rgb(239, 68, 68)',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4
                            }, {
                                label: 'Pemasukan',
                                data: [55000000, 58000000, 62000000, 65000000, 70000000, 68000000],
                                borderColor: 'rgb(34, 197, 94)',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4
                            }]
                        )}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-green-900/20 border border-green-700/50 rounded-lg">
                                <p class="text-green-400 text-xs font-semibold mb-1">📈 Growth Rate</p>
                                <p class="text-white text-xl font-bold">+12.5%</p>
                            </div>
                            <div class="p-3 bg-blue-900/20 border border-blue-700/50 rounded-lg">
                                <p class="text-blue-400 text-xs font-semibold mb-1">💹 Avg Monthly</p>
                                <p class="text-white text-xl font-bold">Rp 65M</p>
                            </div>
                        </div>
                        <div class="p-3 bg-green-900/20 border border-green-700/50 rounded-lg">
                            <p class="text-green-400 text-sm"><i class="fas fa-arrow-trend-up mr-2"></i><strong>Analisis:</strong> Tren positif dengan pertumbuhan revenue 12.5%. Pengeluaran terkontrol dengan rata-rata Rp 55M/bulan.</p>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('kategori') || lowerPrompt.includes('category')) {
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-orange-900/30 rounded-lg">
                                <i class="fas fa-pie-chart text-orange-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Analisis Kategori Pengeluaran</p>
                                <p class="text-gray-400 text-sm">Breakdown by category</p>
                            </div>
                        </div>
                        ${createChart('doughnut',
                            ['Payroll', 'Supplier', 'Rent', 'Utilities', 'IT', 'Unknown'],
                            [{
                                data: [12500000, 11200000, 8500000, 3250000, 2500000, 18900000],
                                backgroundColor: [
                                    'rgba(59, 130, 246, 0.8)',
                                    'rgba(168, 85, 247, 0.8)',
                                    'rgba(34, 197, 94, 0.8)',
                                    'rgba(251, 146, 60, 0.8)',
                                    'rgba(14, 165, 233, 0.8)',
                                    'rgba(239, 68, 68, 0.8)'
                                ],
                                borderColor: '#1e293b',
                                borderWidth: 2
                            }]
                        )}
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="p-2 bg-blue-900/20 border border-blue-700/50 rounded">
                                <span class="text-blue-400">●</span> <span class="text-gray-300">Payroll: Rp 12.5M</span>
                            </div>
                            <div class="p-2 bg-purple-900/20 border border-purple-700/50 rounded">
                                <span class="text-purple-400">●</span> <span class="text-gray-300">Supplier: Rp 11.2M</span>
                            </div>
                            <div class="p-2 bg-green-900/20 border border-green-700/50 rounded">
                                <span class="text-green-400">●</span> <span class="text-gray-300">Rent: Rp 8.5M</span>
                            </div>
                            <div class="p-2 bg-orange-900/20 border border-orange-700/50 rounded">
                                <span class="text-orange-400">●</span> <span class="text-gray-300">Utilities: Rp 3.2M</span>
                            </div>
                            <div class="p-2 bg-cyan-900/20 border border-cyan-700/50 rounded">
                                <span class="text-cyan-400">●</span> <span class="text-gray-300">IT: Rp 2.5M</span>
                            </div>
                            <div class="p-2 bg-red-900/20 border border-red-700/50 rounded">
                                <span class="text-red-400">●</span> <span class="text-gray-300">Unknown: Rp 18.9M ⚠️</span>
                            </div>
                        </div>
                        <div class="p-3 bg-orange-900/20 border border-orange-700/50 rounded-lg">
                            <p class="text-orange-400 text-sm"><i class="fas fa-info-circle mr-2"></i><strong>Insight:</strong> Kategori "Unknown" mencakup 33% total pengeluaran. Perlu kategorisasi lebih detail untuk analisis yang akurat.</p>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('prediksi') || lowerPrompt.includes('cashflow') || lowerPrompt.includes('forecast')) {
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-cyan-900/30 rounded-lg">
                                <i class="fas fa-crystal-ball text-cyan-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Prediksi Cashflow 3 Bulan</p>
                                <p class="text-gray-400 text-sm">AI-based forecasting</p>
                            </div>
                        </div>
                        ${createChart('bar',
                            ['Mar (P)', 'Apr (P)', 'May (P)'],
                            [{
                                label: 'Predicted Income',
                                data: [72000000, 75000000, 78000000],
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1
                            }, {
                                label: 'Predicted Expense',
                                data: [58000000, 60000000, 62000000],
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1
                            }]
                        )}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-cyan-900/20 border border-cyan-700/50 rounded-lg text-center">
                                <p class="text-cyan-400 text-xs font-semibold mb-1">March</p>
                                <p class="text-green-400 text-lg font-bold">+Rp 14M</p>
                            </div>
                            <div class="p-3 bg-cyan-900/20 border border-cyan-700/50 rounded-lg text-center">
                                <p class="text-cyan-400 text-xs font-semibold mb-1">April</p>
                                <p class="text-green-400 text-lg font-bold">+Rp 15M</p>
                            </div>
                            <div class="p-3 bg-cyan-900/20 border border-cyan-700/50 rounded-lg text-center">
                                <p class="text-cyan-400 text-xs font-semibold mb-1">May</p>
                                <p class="text-green-400 text-lg font-bold">+Rp 16M</p>
                            </div>
                        </div>
                        <div class="p-4 bg-cyan-900/20 border border-cyan-700/50 rounded-lg">
                            <p class="text-cyan-400 font-semibold mb-2"><i class="fas fa-robot mr-2"></i>AI Prediction Summary:</p>
                            <ul class="space-y-1 text-sm text-gray-300">
                                <li>📊 Confidence Score: 87%</li>
                                <li>📈 Expected growth: 8% per month</li>
                                <li>💰 Projected net profit (3 months): Rp 45M</li>
                                <li>⚡ Best case scenario: +Rp 52M</li>
                                <li>⚠️ Worst case scenario: +Rp 38M</li>
                            </ul>
                        </div>
                        <div class="p-3 bg-green-900/20 border border-green-700/50 rounded-lg">
                            <p class="text-green-400 text-sm"><i class="fas fa-thumbs-up mr-2"></i><strong>Outlook:</strong> Cashflow stabil dengan tren positif. Prediksi surplus Rp 45M dalam 3 bulan ke depan.</p>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('bandingkan') || lowerPrompt.includes('compare')) {
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-pink-900/30 rounded-lg">
                                <i class="fas fa-balance-scale text-pink-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Perbandingan Periode</p>
                                <p class="text-gray-400 text-sm">This month vs Last month</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-800/50 border border-slate-700 rounded-lg">
                                <p class="text-gray-400 text-sm mb-2">Bulan Lalu (Jan)</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-300 text-sm">Income</span>
                                        <span class="text-green-400 font-semibold">Rp 70M</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-300 text-sm">Expense</span>
                                        <span class="text-red-400 font-semibold">Rp 62M</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-slate-600">
                                        <span class="text-white font-semibold text-sm">Net</span>
                                        <span class="text-blue-400 font-bold">+Rp 8M</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 bg-slate-800/50 border border-blue-700 rounded-lg">
                                <p class="text-blue-400 text-sm mb-2">Bulan Ini (Feb)</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-300 text-sm">Income</span>
                                        <span class="text-green-400 font-semibold">Rp 68M</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-300 text-sm">Expense</span>
                                        <span class="text-red-400 font-semibold">Rp 55M</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-slate-600">
                                        <span class="text-white font-semibold text-sm">Net</span>
                                        <span class="text-green-400 font-bold">+Rp 13M</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-red-900/20 border border-red-700/50 rounded-lg text-center">
                                <p class="text-red-400 text-xs font-semibold mb-1">Income Change</p>
                                <p class="text-white text-lg font-bold">-2.9%</p>
                            </div>
                            <div class="p-3 bg-green-900/20 border border-green-700/50 rounded-lg text-center">
                                <p class="text-green-400 text-xs font-semibold mb-1">Expense Change</p>
                                <p class="text-white text-lg font-bold">-11.3%</p>
                            </div>
                            <div class="p-3 bg-blue-900/20 border border-blue-700/50 rounded-lg text-center">
                                <p class="text-blue-400 text-xs font-semibold mb-1">Net Improvement</p>
                                <p class="text-white text-lg font-bold">+62.5%</p>
                            </div>
                        </div>

                        <div class="p-4 bg-pink-900/20 border border-pink-700/50 rounded-lg">
                            <p class="text-pink-400 font-semibold mb-2"><i class="fas fa-chart-line mr-2"></i>Analisis Perbandingan:</p>
                            <ul class="space-y-1 text-sm text-gray-300">
                                <li>📉 Revenue turun 2.9% (Rp 2M) - masih dalam batas normal</li>
                                <li>✅ Pengeluaran turun signifikan 11.3% (Rp 7M) - efisiensi meningkat</li>
                                <li>🎯 Net profit naik 62.5% - performa keuangan membaik</li>
                                <li>💡 Cost optimization berhasil, pertahankan strategi ini</li>
                            </ul>
                        </div>
                    </div>
                `;

            } else if (lowerPrompt.includes('saran') || lowerPrompt.includes('hemat') || lowerPrompt.includes('saving')) {
                response = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-indigo-900/30 rounded-lg">
                                <i class="fas fa-piggy-bank text-indigo-400"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold">Rekomendasi Penghematan</p>
                                <p class="text-gray-400 text-sm">Based on your spending patterns</p>
                            </div>
                        </div>

                        <div class="p-4 bg-gradient-to-r from-indigo-900/30 to-purple-900/30 border border-indigo-700/50 rounded-lg">
                            <p class="text-indigo-400 font-semibold mb-3"><i class="fas fa-trophy mr-2"></i>Potensi Penghematan Total: <span class="text-white text-xl">Rp 8.5M/bulan</span></p>
                        </div>

                        <div class="space-y-3">
                            <div class="p-4 bg-slate-800/50 border-l-4 border-green-500 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <p class="text-white font-semibold">1. Negosiasi Kontrak Vendor</p>
                                    <span class="px-2 py-1 bg-green-900/30 text-green-400 rounded text-xs font-bold">High Impact</span>
                                </div>
                                <p class="text-gray-300 text-sm mb-2">Vendor XYZ dan PT Maju Jaya memiliki harga di atas market rate 15-20%</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400 text-xs">Potential saving:</span>
                                    <span class="text-green-400 font-bold">Rp 4.2M/bulan</span>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-800/50 border-l-4 border-yellow-500 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <p class="text-white font-semibold">2. Optimize Software Subscriptions</p>
                                    <span class="px-2 py-1 bg-yellow-900/30 text-yellow-400 rounded text-xs font-bold">Medium Impact</span>
                                </div>
                                <p class="text-gray-300 text-sm mb-2">Beberapa software subscription overlap fiturnya. Konsolidasi bisa hemat biaya.</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400 text-xs">Potential saving:</span>
                                    <span class="text-yellow-400 font-bold">Rp 1.8M/bulan</span>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-800/50 border-l-4 border-blue-500 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <p class="text-white font-semibold">3. Audit Utilities & Overhead</p>
                                    <span class="px-2 py-1 bg-blue-900/30 text-blue-400 rounded text-xs font-bold">Quick Win</span>
                                </div>
                                <p class="text-gray-300 text-sm mb-2">Listrik & internet bisa dikurangi dengan switch ke provider yang lebih kompetitif</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400 text-xs">Potential saving:</span>
                                    <span class="text-blue-400 font-bold">Rp 1.2M/bulan</span>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-800/50 border-l-4 border-purple-500 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <p class="text-white font-semibold">4. Payment Terms Optimization</p>
                                    <span class="px-2 py-1 bg-purple-900/30 text-purple-400 rounded text-xs font-bold">Strategic</span>
                                </div>
                                <p class="text-gray-300 text-sm mb-2">Negosiasi payment terms 45-60 hari untuk improve working capital</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400 text-xs">Cash flow improvement:</span>
                                    <span class="text-purple-400 font-bold">Rp 1.3M buffer</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-green-900/20 border border-green-700/50 rounded-lg">
                            <p class="text-green-400 font-semibold mb-2"><i class="fas fa-check-circle mr-2"></i>Action Plan (Priority Order):</p>
                            <ol class="space-y-1 text-sm text-gray-300 list-decimal list-inside">
                                <li>Week 1: Audit dan bandingkan harga vendor dengan kompetitor</li>
                                <li>Week 2: Negosiasi kontrak vendor (target: diskon 15%)</li>
                                <li>Week 3: Review semua software subscriptions dan cancel yang tidak terpakai</li>
                                <li>Week 4: Switch utilities provider dan review payment terms</li>
                            </ol>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-indigo-900/20 border border-indigo-700/50 rounded-lg text-center">
                                <p class="text-indigo-400 text-xs font-semibold mb-1">Monthly Saving</p>
                                <p class="text-white text-xl font-bold">Rp 8.5M</p>
                            </div>
                            <div class="p-3 bg-purple-900/20 border border-purple-700/50 rounded-lg text-center">
                                <p class="text-purple-400 text-xs font-semibold mb-1">Yearly Impact</p>
                                <p class="text-white text-xl font-bold">Rp 102M</p>
                            </div>
                        </div>
                    </div>
                `;

            } else {
                response = `
                    <div class="space-y-3">
                        <p class="text-gray-300">Saya telah menerima pertanyaan Anda: "<strong class="text-white">${escapeHtml(prompt)}</strong>"</p>
                        <div class="p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg">
                            <p class="text-blue-400 font-semibold mb-2"><i class="fas fa-magic mr-2"></i>Coba gunakan Quick Prompts untuk:</p>
                            <ul class="space-y-1 text-sm text-gray-300 list-disc list-inside">
                                <li>Melihat top transaksi dengan tabel detail</li>
                                <li>Analisis transaksi mencurigakan dengan AI</li>
                                <li>Visualisasi data dengan grafik interaktif</li>
                                <li>Prediksi cashflow masa depan</li>
                                <li>Rekomendasi penghematan cerdas</li>
                            </ul>
                        </div>
                    </div>
                `;
            }

            addAIMessage(response);
        }

        function addAIMessage(content) {
            const container = document.getElementById('messages-container');
            const typingIndicator = document.getElementById('typing-indicator');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex justify-start';
            messageDiv.innerHTML = `
                <div class="max-w-[85%]">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-robot text-white text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="bg-slate-900/50 border border-slate-700 rounded-2xl rounded-tl-sm p-4">
                                ${content}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                ${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}
                            </p>
                        </div>
                    </div>
                </div>
            `;
            container.insertBefore(messageDiv, typingIndicator);
            messageCount++;
            scrollToBottom();
        }

        function sendQuickPrompt(prompt) {
            const statusEl = document.getElementById('quick-prompts-status');
            if (statusEl) statusEl.classList.remove('hidden');
            
            document.querySelectorAll('button[onclick^="sendQuickPrompt"]').forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            });
            
            const input = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            if (input) input.disabled = true;
            if (sendButton) sendButton.disabled = true;
            
            addUserMessage(prompt);
            showTypingIndicator();
            
            const responseTime = Math.random() * 1000 + 1500;
            
            setTimeout(() => {
                hideTypingIndicator();
                generateAIResponse(prompt);
                updateStats();
                
                if (statusEl) statusEl.classList.add('hidden');
                if (input) {
                    input.disabled = false;
                    input.focus();
                }
                if (sendButton) sendButton.disabled = false;
                
                document.querySelectorAll('button[onclick^="sendQuickPrompt"]').forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
            }, responseTime);
        }

        function clearChat() {
            if (confirm('Clear all messages in this session?')) {
                const container = document.getElementById('messages-container');
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="inline-block p-6 bg-gradient-to-br from-purple-900/30 to-blue-900/30 rounded-3xl border border-purple-700/50 mb-4">
                                <i class="fas fa-comment-dots text-purple-400 text-5xl"></i>
                            </div>
                            <h3 class="text-white text-lg font-semibold mb-2">Chat cleared!</h3>
                            <p class="text-gray-400 mb-4">Start a new conversation with AI</p>
                        </div>
                    </div>
                    <div id="typing-indicator" class="hidden flex justify-start">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 flex items-center justify-center">
                                <i class="fas fa-robot text-white text-sm"></i>
                            </div>
                            <div class="bg-slate-900/50 border border-slate-700 rounded-2xl rounded-tl-sm px-6 py-4">
                                <div class="flex gap-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                messageCount = 0;
                document.getElementById('message-count').textContent = '0';
                chartCounter = 0;
            }
        }

        function editTitle() {
            const newTitle = prompt('Enter new title:', '{{ $chatSession->title }}');
            if (newTitle && newTitle.trim()) {
                document.querySelector('h2.font-semibold').textContent = newTitle;
                
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
                notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Title updated successfully!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        }

        function exportChat() {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.innerHTML = '<i class="fas fa-download mr-2"></i>Exporting chat... (Demo Mode)';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Chat exported successfully!';
                notification.classList.remove('bg-blue-600');
                notification.classList.add('bg-green-600');
                setTimeout(() => notification.remove(), 2000);
            }, 1500);
        }

        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            const chatForm = document.getElementById('chat-form');
            if (chatForm) {
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const input = document.getElementById('message-input');
                    const message = input.value.trim();
                    
                    if (!message) return;

                    addUserMessage(message);
                    input.value = '';
                    showTypingIndicator();
                    
                    const responseTime = Math.random() * 1000 + 1500;
                    
                    setTimeout(() => {
                        hideTypingIndicator();
                        generateAIResponse(message);
                        updateStats();
                    }, responseTime);
                });
            }

            const input = document.getElementById('message-input');
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        chatForm.dispatchEvent(new Event('submit'));
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>