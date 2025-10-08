<x-app-layout>
    <x-slot name="header">Bank Statement Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
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
                        @if($bankStatement->bank && $bankStatement->bank->logo_url)
                            <img src="{{ $bankStatement->bank->logo_url }}" alt="{{ $bankStatement->bank->name }}" class="w-16 h-16 object-contain">
                        @else
                            <i class="fas fa-university text-gray-400 text-3xl"></i>
                        @endif
                    </div>

                    {{-- Bank Info --}}
                    <div>
                        <h3 class="text-white font-bold text-xl mb-1">
                            {{ $bankStatement->bank ? $bankStatement->bank->name : 'Unknown Bank' }}
                        </h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-400">
                            <span>
                                <i class="fas fa-user mr-1"></i>{{ $bankStatement->user->name ?? 'Unknown User' }}
                            </span>
                            <span>
                                <i class="fas fa-calendar mr-1"></i>{{ $bankStatement->uploaded_at ? $bankStatement->uploaded_at->format('d M Y H:i') : '-' }}
                            </span>
                            @if($bankStatement->account_number)
                                <span>
                                    <i class="fas fa-credit-card mr-1"></i>{{ $bankStatement->account_number }}
                                </span>
                            @endif
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
                        $currentStatus = $bankStatement->ocr_status ?? 'pending';
                        $status = $statusConfig[$currentStatus] ?? $statusConfig['pending'];
                    @endphp
                    
                    <span class="px-4 py-2 rounded-xl text-sm font-semibold
                        @if($status['color'] === 'blue') bg-blue-600/20 text-blue-400
                        @elseif($status['color'] === 'yellow') bg-yellow-600/20 text-yellow-400
                        @elseif($status['color'] === 'green') bg-green-600/20 text-green-400
                        @elseif($status['color'] === 'red') bg-red-600/20 text-red-400
                        @endif">
                        <i class="fas fa-{{ $status['icon'] }} mr-1 {{ $currentStatus === 'processing' ? 'fa-spin' : '' }}"></i>
                        {{ $status['text'] }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Action Buttons Section --}}
        @if($bankStatement->ocr_status === 'completed' && $bankStatement->transactions->count() > 0)
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Suggest Keywords (NEW) --}}
                    @if($matchingStats['unmatched_count'] > 0)
                        <a href="{{ route('keyword-suggestions.analyze', $bankStatement) }}" 
                           class="bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-lightbulb"></i>
                            <span>Suggest Keywords</span>
                            <span class="px-2 py-1 bg-white/20 rounded text-xs">
                                {{ $matchingStats['unmatched_count'] }}
                            </span>
                        </a>
                    @endif

                    {{-- Process Matching --}}
                    <form action="{{ route('bank-statements.process-matching', $bankStatement) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-sync-alt"></i>
                            <span>Process Matching</span>
                        </button>
                    </form>

                    {{-- Rematch All --}}
                    <form action="{{ route('bank-statements.rematch-all', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('This will reset all unverified matches. Continue?')">
                        @csrf
                        <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-redo"></i>
                            <span>Rematch All</span>
                        </button>
                    </form>

                    {{-- Verify All Matched --}}
                    @if($matchingStats['matched_count'] > 0)
                        <form action="{{ route('bank-statements.verify-all-matched', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify all matched transactions?')">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                <i class="fas fa-check-double"></i>
                                <span>Verify All</span>
                                <span class="px-2 py-1 bg-white/20 rounded text-xs">
                                    {{ $matchingStats['matched_count'] }}
                                </span>
                            </button>
                        </form>
                    @endif

                    {{-- Export CSV --}}
                    <a href="{{ route('bank-statements.export', $bankStatement) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-file-csv"></i>
                        <span>Export</span>
                    </a>

                    {{-- Download PDF --}}
                    <a href="{{ route('bank-statements.download', $bankStatement) }}" class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-download"></i>
                        <span>PDF</span>
                    </a>

                    {{-- Delete --}}
                    <form action="{{ route('bank-statements.destroy', $bankStatement) }}" method="POST" class="inline ml-auto" onsubmit="return confirm('Are you sure? This will delete all transactions!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-trash"></i>
                            <span>Delete</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total</p>
                        <p class="text-white text-3xl font-bold">{{ $matchingStats['total_transactions'] }}</p>
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
                        <p class="text-white text-3xl font-bold">{{ $matchingStats['matched_count'] }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($matchingStats['match_percentage'], 1) }}%</p>
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
                        <p class="text-white text-3xl font-bold">{{ $matchingStats['unmatched_count'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-times text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Manual</p>
                        <p class="text-white text-3xl font-bold">{{ $matchingStats['manual_count'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-hand-pointer text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Low Conf.</p>
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
                            Low Conf.
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($bankStatement->transactions as $transaction)
                            @php
                                $isMatched = !is_null($transaction->matched_keyword_id);
                                $matchStatus = $isMatched ? 'matched' : 'unmatched';
                                $isLowConf = $transaction->confidence_score && $transaction->confidence_score < 80;
                            @endphp
                            <div class="transaction-item bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition" 
                                data-status="{{ $matchStatus }}" 
                                data-confidence="{{ $isLowConf ? 'low' : 'high' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-3">
                                            {{-- Transaction Type Badge --}}
                                            @if($transaction->transaction_type === 'debit')
                                                <span class="px-3 py-1 bg-red-600/20 text-red-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-arrow-down mr-1"></i>Debit
                                                </span>
                                            @else
                                                <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-arrow-up mr-1"></i>Credit
                                                </span>
                                            @endif
                                            
                                            {{-- Matching Status --}}
                                            @if($isMatched)
                                                <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-check mr-1"></i>Matched
                                                </span>
                                            @else
                                                <span class="px-3 py-1 bg-red-600/20 text-red-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-times mr-1"></i>Unmatched
                                                </span>
                                            @endif

                                            {{-- Verification Status --}}
                                            @if($transaction->is_verified)
                                                <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-check-double mr-1"></i>Verified
                                                </span>
                                            @endif

                                            {{-- Low Confidence Warning --}}
                                            @if($isLowConf)
                                                <span class="px-3 py-1 bg-yellow-600/20 text-yellow-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Low Conf: {{ $transaction->confidence_score }}%
                                                </span>
                                            @endif

                                            {{-- Manual Category --}}
                                            @if($transaction->is_manual_category)
                                                <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-lg text-xs font-semibold">
                                                    <i class="fas fa-hand-pointer mr-1"></i>Manual
                                                </span>
                                            @endif
                                        </div>

                                        <h4 class="text-white font-semibold text-lg mb-3">{{ $transaction->description ?? '-' }}</h4>

                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-400 block mb-1">Date</span>
                                                <span class="text-white font-semibold">
                                                    <i class="fas fa-calendar mr-1 text-blue-400"></i>
                                                    {{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '-' }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-400 block mb-1">Amount</span>
                                                <span class="font-semibold {{ $transaction->transaction_type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                                    <i class="fas fa-{{ $transaction->transaction_type === 'debit' ? 'minus' : 'plus' }}-circle mr-1"></i>
                                                    Rp {{ number_format($transaction->transaction_type === 'debit' ? ($transaction->debit_amount ?? 0) : ($transaction->credit_amount ?? 0), 0, ',', '.') }}
                                                </span>
                                            </div>
                                            @if($transaction->subCategory)
                                                <div>
                                                    <span class="text-gray-400 block mb-1">Category</span>
                                                    <span class="text-white font-semibold">
                                                        <i class="fas fa-folder mr-1 text-blue-400"></i>
                                                        {{ $transaction->subCategory->category->name ?? '-' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-400 block mb-1">Sub Category</span>
                                                    <span class="text-white font-semibold">
                                                        <i class="fas fa-layer-group mr-1 text-purple-400"></i>
                                                        {{ $transaction->subCategory->name ?? '-' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Confidence Score Bar --}}
                                        @if($transaction->confidence_score)
                                            <div class="mt-3">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-gray-400 text-xs">Confidence:</span>
                                                    <div class="flex-1 max-w-xs bg-slate-700 rounded-full h-2">
                                                        <div class="h-2 rounded-full transition-all {{ $transaction->confidence_score >= 80 ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-yellow-500 to-red-600' }}" 
                                                             style="width: {{ $transaction->confidence_score }}%"></div>
                                                    </div>
                                                    <span class="text-white text-xs font-semibold">{{ $transaction->confidence_score }}%</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Amount Display (Right Side) --}}
                                    <div class="ml-6 text-right">
                                        <div class="text-2xl font-bold mb-2 {{ $transaction->transaction_type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                            {{ $transaction->transaction_type === 'debit' ? '-' : '+' }}Rp {{ number_format($transaction->transaction_type === 'debit' ? ($transaction->debit_amount ?? 0) : ($transaction->credit_amount ?? 0), 0, ',', '.') }}
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('transactions.show', $transaction) }}" 
                                               class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if(!$transaction->is_verified && $isMatched)
                                                <form action="{{ route('bank-statements.verify-transaction', [$bankStatement, $transaction]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="p-3 bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white rounded-lg transition-all"
                                                            title="Verify">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if(!$isMatched || $isLowConf)
                                                <form action="{{ route('transactions.rematch', $transaction) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="p-3 bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded-lg transition-all"
                                                            title="Rematch">
                                                        <i class="fas fa-sync"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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
                    <div class="mt-4">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                    </div>
                @elseif($bankStatement->ocr_status === 'failed')
                    <p class="text-red-400 text-sm mb-4">OCR processing failed. Please try uploading again.</p>
                    @if($bankStatement->ocr_error)
                        <p class="text-xs text-gray-500 mb-4">Error: {{ $bankStatement->ocr_error }}</p>
                    @endif
                    <form action="{{ route('bank-statements.reprocess', $bankStatement) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-redo mr-2"></i>Reprocess OCR
                        </button>
                    </form>
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