<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Bank Statement Details') }}
            </h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('bank-statements.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ✅ SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="bg-green-600/20 border border-green-600 text-green-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ✅ ERROR MESSAGE --}}
            @if(session('error'))
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Header Info --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">
                            {{ $bankStatement->bank->name ?? 'N/A' }}
                        </h3>
                        <p class="text-gray-400">
                            <i class="fas fa-file-pdf mr-2"></i>{{ $bankStatement->original_filename }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-semibold
                            {{ $bankStatement->ocr_status === 'completed' ? 'bg-green-600/20 text-green-400' : '' }}
                            {{ $bankStatement->ocr_status === 'processing' ? 'bg-yellow-600/20 text-yellow-400' : '' }}
                            {{ $bankStatement->ocr_status === 'failed' ? 'bg-red-600/20 text-red-400' : '' }}
                            {{ $bankStatement->ocr_status === 'pending' ? 'bg-blue-600/20 text-blue-400' : '' }}">
                            <i class="fas fa-circle"></i>
                            <span>{{ ucfirst($bankStatement->ocr_status) }}</span>
                        </span>
                    </div>
                </div>

                {{-- Details Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Period</p>
                        <p class="text-sm font-semibold text-white">
                            {{ $bankStatement->period_from }} s/d {{ $bankStatement->period_to }}
                        </p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Account Number</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->account_number }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Currency</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->currency }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Uploaded</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->uploaded_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-4 border border-blue-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-200 mb-1">Total Transactions</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
                        </div>
                        <i class="fas fa-receipt text-3xl text-blue-300/50"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-4 border border-green-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-green-200 mb-1">Matched</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['matched'] }}</p>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-green-300/50"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl p-4 border border-orange-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-orange-200 mb-1">Unmatched</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['unmatched'] }}</p>
                        </div>
                        <i class="fas fa-question-circle text-3xl text-orange-300/50"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-4 border border-purple-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-200 mb-1">Verified</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['verified'] }}</p>
                        </div>
                        <i class="fas fa-shield-check text-3xl text-purple-300/50"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-xl p-4 border border-red-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-red-200 mb-1">Low Confidence</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['low_confidence'] }}</p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-3xl text-red-300/50"></i>
                    </div>
                </div>
            </div>

            {{-- Balance Info --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h4 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-wallet mr-2"></i>Balance Summary
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Opening Balance</p>
                        <p class="text-lg font-semibold text-white">Rp {{ number_format($bankStatement->opening_balance, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-green-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Credit</p>
                        <p class="text-lg font-semibold text-green-400">Rp {{ number_format($bankStatement->total_credit_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->total_credit_count }} transactions</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-red-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Debit</p>
                        <p class="text-lg font-semibold text-red-400">Rp {{ number_format($bankStatement->total_debit_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->total_debit_count }} transactions</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-blue-700/50">
                        <p class="text-xs text-gray-400 mb-1">Closing Balance</p>
                        <p class="text-lg font-semibold text-blue-400">Rp {{ number_format($bankStatement->closing_balance, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            @if($bankStatement->transactions()->count() > 0)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Suggest Keywords --}}
                        @if($matchingStats['unmatched_count'] > 0)
                            <a href="{{ route('keyword-suggestions.analyze', $bankStatement) }}" 
                               class="bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                <i class="fas fa-lightbulb"></i>
                                <span>Suggest Keywords</span>
                                <span class="px-2 py-1 bg-white/20 rounded text-xs">
                                    {{ $matchingStats['unmatched_count'] }} unmatched
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
                                    <i class="fas fa-shield-check"></i>
                                    <span>Verify All Matched</span>
                                </button>
                            </form>
                        @endif

                        {{-- Export --}}
                        <a href="{{ route('bank-statements.export', $bankStatement) }}" class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-file-excel"></i>
                            <span>Export Excel</span>
                        </a>

                        {{-- Download PDF --}}
                        <a href="{{ route('bank-statements.download', $bankStatement) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-download"></i>
                            <span>Download PDF</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Transactions List --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-700">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-bold text-white">
                            <i class="fas fa-list mr-2"></i>Transactions ({{ $bankStatement->transactions->count() }})
                        </h4>

                        {{-- Filter Buttons --}}
                        <div class="flex items-center space-x-2">
                            <button onclick="filterTransactions('all')" data-filter="all" class="filter-btn active bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                All ({{ $stats['total'] }})
                            </button>
                            <button onclick="filterTransactions('matched')" data-filter="matched" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                Matched ({{ $stats['matched'] }})
                            </button>
                            <button onclick="filterTransactions('unmatched')" data-filter="unmatched" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                Unmatched ({{ $stats['unmatched'] }})
                            </button>
                            <button onclick="filterTransactions('low-confidence')" data-filter="low-confidence" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                Low Confidence ({{ $stats['low_confidence'] }})
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if($bankStatement->transactions->count() > 0)
                        <div class="space-y-3">
                            @foreach($bankStatement->transactions as $transaction)
                                <div class="transaction-item bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500 transition"
                                     data-status="{{ $transaction->matched_keyword_id ? 'matched' : 'unmatched' }}"
                                     data-confidence="{{ $transaction->confidence_score < 80 && $transaction->matched_keyword_id ? 'low' : 'high' }}">
                                    
                                    <div class="flex items-start justify-between">
                                        {{-- Main Info --}}
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <span class="text-white font-semibold">
                                                    {{ $transaction->transaction_date->format('d M Y') }}
                                                </span>
                                                @if($transaction->transaction_time)
                                                    <span class="text-gray-400 text-sm">{{ $transaction->transaction_time }}</span>
                                                @endif
                                                
                                                {{-- Status Badge --}}
                                                @if($transaction->is_verified)
                                                    <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold">
                                                        <i class="fas fa-check-circle mr-1"></i>Verified
                                                    </span>
                                                @elseif($transaction->matched_keyword_id)
                                                    <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs font-semibold">
                                                        <i class="fas fa-link mr-1"></i>Matched
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 bg-orange-600/20 text-orange-400 rounded text-xs font-semibold">
                                                        <i class="fas fa-exclamation mr-1"></i>Unmatched
                                                    </span>
                                                @endif

                                                {{-- Confidence Score --}}
                                                @if($transaction->confidence_score && $transaction->matched_keyword_id)
                                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                                        {{ $transaction->confidence_score >= 80 ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400' }}">
                                                        {{ $transaction->confidence_score }}% confidence
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Description --}}
                                            <p class="text-white font-semibold mb-2">{{ $transaction->description }}</p>

                                            {{-- Category Path --}}
                                            @if($transaction->subCategory)
                                                <div class="flex items-center space-x-2 text-xs text-gray-400 mb-2">
                                                    <i class="fas fa-folder"></i>
                                                    <span>{{ $transaction->subCategory->category->type->name ?? 'N/A' }}</span>
                                                    <i class="fas fa-chevron-right text-xs"></i>
                                                    <span>{{ $transaction->subCategory->category->name ?? 'N/A' }}</span>
                                                    <i class="fas fa-chevron-right text-xs"></i>
                                                    <span class="text-blue-400">{{ $transaction->subCategory->name }}</span>
                                                </div>
                                            @endif

                                            {{-- Reference Number --}}
                                            @if($transaction->reference_no)
                                                <p class="text-xs text-gray-500">
                                                    <i class="fas fa-hashtag mr-1"></i>{{ $transaction->reference_no }}
                                                </p>
                                            @endif
                                        </div>

                                        {{-- Amount & Actions --}}
                                        <div class="ml-4 text-right">
                                            <div class="text-2xl font-bold mb-3 {{ $transaction->transaction_type == 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                                {{ $transaction->transaction_type == 'debit' ? '-' : '+' }}
                                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                            </div>
                                            <a href="{{ route('bank-statements.transactions.show', [$bankStatement, $transaction]) }}" 
                                               class="inline-flex items-center space-x-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg transition text-sm">
                                                <i class="fas fa-eye"></i>
                                                <span>View Details</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No transactions found</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
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
    </script>
</x-app-layout>