<x-app-layout>
    <x-slot name="header">Bank Statement Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex items-center space-x-3 mb-3">
                <a href="{{ route('bank-statements.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="text-2xl font-bold text-white">{{ $bankStatement->original_filename }}</h2>
            </div>
            <p class="text-gray-400">Review and manage extracted transactions</p>
        </div>

        {{-- Statement Info Card --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6 mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-6">
                    {{-- Bank Logo --}}
                    <div class="w-20 h-20 bg-slate-700 rounded-xl flex items-center justify-center">
                        @if($bankStatement->bank->logo_url)
                            <img src="{{ $bankStatement->bank->logo_url }}" alt="{{ $bankStatement->bank->name }}" class="w-16 h-16 object-contain">
                        @else
                            <i class="fas fa-university text-gray-400 text-3xl"></i>
                        @endif
                    </div>

                    {{-- Bank Info --}}
                    <div>
                        <h3 class="text-white font-bold text-xl mb-1">{{ $bankStatement->bank->name }}</h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-400">
                            <span>
                                <i class="fas fa-user mr-1"></i>{{ $bankStatement->user->name }}
                            </span>
                            <span>
                                <i class="fas fa-calendar mr-1"></i>{{ $bankStatement->uploaded_at->format('d M Y H:i') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- OCR Status --}}
                <div class="flex items-center space-x-3">
                    @php
                        $statusConfig = [
                            'pending' => ['color' => 'blue', 'icon' => 'clock', 'text' => 'Pending OCR'],
                            'processing' => ['color' => 'yellow', 'icon' => 'spinner', 'text' => 'Processing OCR'],
                            'completed' => ['color' => 'green', 'icon' => 'check-circle', 'text' => 'OCR Completed'],
                            'failed' => ['color' => 'red', 'icon' => 'exclamation-circle', 'text' => 'OCR Failed'],
                        ];
                        $status = $statusConfig[$bankStatement->ocr_status] ?? $statusConfig['pending'];
                    @endphp
                    
                    <span class="px-4 py-2 bg-{{ $status['color'] }}-600/20 text-{{ $status['color'] }}-400 rounded-xl text-sm font-semibold">
                        <i class="fas fa-{{ $status['icon'] }} mr-1 {{ $bankStatement->ocr_status === 'processing' ? 'fa-spin' : '' }}"></i>
                        {{ $status['text'] }}
                    </span>

                    {{-- Actions --}}
                    @if($bankStatement->ocr_status === 'completed')
                        <form action="{{ route('bank-statements.process-matching', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition-all">
                                <i class="fas fa-sync-alt mr-2"></i>Run Matching
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('bank-statements.download', $bankStatement) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-all">
                        <i class="fas fa-download mr-2"></i>Download PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-list text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Matched</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['matched'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Unmatched</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['unmatched'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-times text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Verified</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['verified'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-double text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Low Confidence</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['low_confidence'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transactions List --}}
        @if($bankStatement->transactions->count() > 0)
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Extracted Transactions</h3>
                    <div class="flex items-center space-x-2">
                        <button onclick="filterTransactions('all')" class="filter-btn active px-4 py-2 bg-slate-700 text-white rounded-lg text-sm font-semibold transition-all" data-filter="all">
                            All
                        </button>
                        <button onclick="filterTransactions('matched')" class="filter-btn px-4 py-2 bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white rounded-lg text-sm font-semibold transition-all" data-filter="matched">
                            Matched
                        </button>
                        <button onclick="filterTransactions('unmatched')" class="filter-btn px-4 py-2 bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white rounded-lg text-sm font-semibold transition-all" data-filter="unmatched">
                            Unmatched
                        </button>
                        <button onclick="filterTransactions('low-confidence')" class="filter-btn px-4 py-2 bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white rounded-lg text-sm font-semibold transition-all" data-filter="low-confidence">
                            Low Confidence
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($bankStatement->transactions as $transaction)
                            <div class="transaction-item bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition" 
                                data-status="{{ $transaction->matching_status }}" 
                                data-confidence="{{ $transaction->confidence_score < 0.7 ? 'low' : 'high' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <h4 class="text-white font-semibold text-lg">{{ $transaction->description }}</h4>
                                            
                                            @if($transaction->matching_status === 'matched')
                                                <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-check mr-1"></i>Matched
                                                </span>
                                            @elseif($transaction->matching_status === 'unmatched')
                                                <span class="px-3 py-1 bg-red-600/20 text-red-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-times mr-1"></i>Unmatched
                                                </span>
                                            @endif

                                            @if($transaction->is_verified)
                                                <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-check-double mr-1"></i>Verified
                                                </span>
                                            @endif

                                            @if($transaction->confidence_score < 0.7)
                                                <span class="px-3 py-1 bg-yellow-600/20 text-yellow-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Low Confidence
                                                </span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-400 block mb-1">Date</span>
                                                <span class="text-white font-semibold">
                                                    <i class="fas fa-calendar mr-1 text-blue-400"></i>
                                                    {{ $transaction->transaction_date->format('d M Y') }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-400 block mb-1">Amount</span>
                                                <span class="text-white font-semibold {{ $transaction->type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                                    <i class="fas fa-{{ $transaction->type === 'debit' ? 'minus' : 'plus' }}-circle mr-1"></i>
                                                    Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            @if($transaction->subCategory)
                                                <div>
                                                    <span class="text-gray-400 block mb-1">Category</span>
                                                    <span class="text-white font-semibold">
                                                        <i class="fas fa-folder mr-1" style="color: {{ $transaction->subCategory->category->color }}"></i>
                                                        {{ $transaction->subCategory->category->name }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-400 block mb-1">Sub Category</span>
                                                    <span class="text-white font-semibold">
                                                        <i class="fas fa-layer-group mr-1" style="color: {{ $transaction->subCategory->category->color }}"></i>
                                                        {{ $transaction->subCategory->name }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        @if($transaction->confidence_score)
                                            <div class="mt-3">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-gray-400 text-xs">Confidence:</span>
                                                    <div class="flex-1 max-w-xs bg-slate-700 rounded-full h-2">
                                                        <div class="bg-gradient-to-r from-{{ $transaction->confidence_score >= 0.7 ? 'green' : 'yellow' }}-500 to-{{ $transaction->confidence_score >= 0.7 ? 'green' : 'red' }}-600 h-2 rounded-full transition-all" style="width: {{ $transaction->confidence_score * 100 }}%"></div>
                                                    </div>
                                                    <span class="text-white text-xs font-semibold">{{ round($transaction->confidence_score * 100) }}%</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex items-center space-x-2 ml-4">
                                        <a href="{{ route('transactions.edit', $transaction) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-12 text-center">
                <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                <p class="text-gray-400 text-lg mb-2">No transactions found</p>
                @if($bankStatement->ocr_status === 'processing')
                    <p class="text-gray-500 text-sm">OCR processing in progress. Transactions will appear once completed.</p>
                @elseif($bankStatement->ocr_status === 'failed')
                    <p class="text-red-400 text-sm">OCR processing failed. Please try uploading again.</p>
                @endif
            </div>
        @endif
    </div>

    <script>
        function filterTransactions(filter) {
            const items = document.querySelectorAll('.transaction-item');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update button states
            buttons.forEach(btn => {
                btn.classList.remove('active', 'bg-slate-700', 'text-white');
                btn.classList.add('bg-slate-700/50', 'text-gray-400');
                
                if (btn.dataset.filter === filter) {
                    btn.classList.add('active', 'bg-slate-700', 'text-white');
                    btn.classList.remove('bg-slate-700/50', 'text-gray-400');
                }
            });
            
            // Filter items
            items.forEach(item => {
                const status = item.dataset.status;
                const confidence = item.dataset.confidence;
                
                if (filter === 'all') {
                    item.style.display = 'block';
                } else if (filter === 'low-confidence') {
                    item.style.display = confidence === 'low' ? 'block' : 'none';
                } else {
                    item.style.display = status === filter ? 'block' : 'none';
                }
            });
        }

        // Auto refresh if OCR is processing
        @if($bankStatement->ocr_status === 'processing')
            setTimeout(() => {
                window.location.reload();
            }, 10000); // Refresh every 10 seconds
        @endif
    </script>
</x-app-layout>