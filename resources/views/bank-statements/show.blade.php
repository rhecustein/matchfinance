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

            {{-- Success/Error Messages --}}
            @if(session('success'))
                <div class="bg-green-600/20 border border-green-600 text-green-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Header Info --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        @if($bankStatement->bank->logo)
                            <img src="{{ asset('storage/' . $bankStatement->bank->logo) }}" alt="{{ $bankStatement->bank->name }}" class="w-12 h-12 object-contain">
                        @endif
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-1">
                                {{ $bankStatement->bank->name }}
                            </h3>
                            <p class="text-gray-400 text-sm">
                                <i class="fas fa-file-pdf mr-2"></i>{{ $bankStatement->original_filename }}
                            </p>
                            @if($bankStatement->account_holder_name)
                                <p class="text-gray-500 text-xs mt-1">
                                    <i class="fas fa-user mr-1"></i>{{ $bankStatement->account_holder_name }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="text-right space-y-2">
                        {{-- OCR Status --}}
                        <span class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-semibold
                            {{ $bankStatement->ocr_status === 'completed' ? 'bg-green-600/20 text-green-400 border border-green-600' : '' }}
                            {{ $bankStatement->ocr_status === 'processing' ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-600' : '' }}
                            {{ $bankStatement->ocr_status === 'failed' ? 'bg-red-600/20 text-red-400 border border-red-600' : '' }}
                            {{ $bankStatement->ocr_status === 'pending' ? 'bg-blue-600/20 text-blue-400 border border-blue-600' : '' }}">
                            <i class="fas fa-circle"></i>
                            <span>{{ $bankStatement->ocr_status_label }}</span>
                        </span>

                        {{-- Reconciliation Status --}}
                        @if($bankStatement->is_reconciled)
                            <span class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-semibold bg-purple-600/20 text-purple-400 border border-purple-600">
                                <i class="fas fa-check-double"></i>
                                <span>Reconciled</span>
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Statement Details Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Period</p>
                        <p class="text-sm font-semibold text-white">
                            {{ $bankStatement->period_label }}
                        </p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Account Number</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->account_number ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Currency</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->currency }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Uploaded By</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->user->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->uploaded_at->diffForHumans() }}</p>
                    </div>
                </div>

                {{-- Processing Info --}}
                @if($bankStatement->ocr_completed_at)
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">
                                <i class="fas fa-clock mr-2"></i>Processing Time: 
                                <span class="text-white font-semibold">{{ $bankStatement->formatted_processing_duration }}</span>
                            </span>
                            <span class="text-gray-400">
                                <i class="fas fa-file-alt mr-2"></i>File Size: 
                                <span class="text-white font-semibold">{{ $bankStatement->formatted_file_size }}</span>
                            </span>
                        </div>
                    </div>
                @endif

                {{-- User Notes --}}
                @if($bankStatement->notes)
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <p class="text-xs text-gray-400 mb-2">Notes:</p>
                        <p class="text-sm text-gray-300">{{ $bankStatement->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- Statistics Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                {{-- Total --}}
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-4 border border-blue-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-200 mb-1">Total</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['total'] }}</p>
                        </div>
                        <i class="fas fa-receipt text-3xl text-blue-300/30"></i>
                    </div>
                </div>

                {{-- Categorized --}}
                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-4 border border-green-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-green-200 mb-1">Categorized</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['categorized'] }}</p>
                            <p class="text-xs text-green-200 mt-1">
                                {{ $bankStatement->matching_percentage }}%
                            </p>
                        </div>
                        <i class="fas fa-tags text-3xl text-green-300/30"></i>
                    </div>
                </div>

                {{-- With Account --}}
                <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-xl p-4 border border-cyan-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-cyan-200 mb-1">With Account</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['with_account'] }}</p>
                        </div>
                        <i class="fas fa-book text-3xl text-cyan-300/30"></i>
                    </div>
                </div>

                {{-- Verified --}}
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-4 border border-purple-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-200 mb-1">Verified</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['verified'] }}</p>
                            <p class="text-xs text-purple-200 mt-1">
                                {{ $bankStatement->verification_percentage }}%
                            </p>
                        </div>
                        <i class="fas fa-shield-check text-3xl text-purple-300/30"></i>
                    </div>
                </div>

                {{-- Low Confidence --}}
                <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-xl p-4 border border-red-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-red-200 mb-1">Low Confidence</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['low_confidence'] }}</p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-3xl text-red-300/30"></i>
                    </div>
                </div>
            </div>

            {{-- Balance Summary --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h4 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-wallet mr-2"></i>Balance Summary
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Opening</p>
                        <p class="text-lg font-semibold text-white">
                            Rp {{ number_format($bankStatement->opening_balance ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-green-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Credit</p>
                        <p class="text-lg font-semibold text-green-400">
                            Rp {{ number_format($bankStatement->total_credit_amount, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->total_credit_count }} transactions</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-red-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Debit</p>
                        <p class="text-lg font-semibold text-red-400">
                            Rp {{ number_format($bankStatement->total_debit_amount, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->total_debit_count }} transactions</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-blue-700/50">
                        <p class="text-xs text-gray-400 mb-1">Closing</p>
                        <p class="text-lg font-semibold text-blue-400">
                            Rp {{ number_format($bankStatement->closing_balance ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-yellow-700/50">
                        <p class="text-xs text-gray-400 mb-1">Net Change</p>
                        <p class="text-lg font-semibold text-yellow-400">
                            {{ $bankStatement->formatted_net_change }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Keyword Suggestions (if unmatched exists) --}}
            @if($statistics['uncategorized'] > 0)
                <div class="bg-gradient-to-br from-yellow-800/50 to-yellow-900/50 rounded-2xl p-6 border border-yellow-600 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-bold text-white flex items-center">
                                <i class="fas fa-lightbulb mr-2 text-yellow-400"></i>
                                AI-Powered Keyword Suggestions
                            </h4>
                            <p class="text-sm text-gray-300 mt-1">
                                Analyze {{ $statistics['uncategorized'] }} uncategorized transactions and get smart keyword recommendations
                            </p>
                        </div>
                        <a href="{{ route('keyword-suggestions.analyze', $bankStatement) }}" 
                           class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-magic"></i>
                            <span>Analyze & Suggest</span>
                            <span class="ml-2 px-2 py-1 bg-white/20 rounded text-xs">
                                {{ $statistics['uncategorized'] }}
                            </span>
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-brain text-blue-400"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">AI Analysis</p>
                                    <p class="text-sm font-semibold text-white">Pattern Detection</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-600/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-tags text-green-400"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Smart Grouping</p>
                                    <p class="text-sm font-semibold text-white">Auto-Categorize</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-600/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-line text-purple-400"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Statistics</p>
                                    <p class="text-sm font-semibold text-white">Coverage Report</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            @if($bankStatement->transactions()->count() > 0)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-cog mr-2"></i>Actions
                    </h4>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Category Matching --}}
                        <form action="{{ route('bank-statements.match-transactions', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                <i class="fas fa-tags"></i>
                                <span>Match Categories</span>
                            </button>
                        </form>

                        {{-- Account Matching --}}
                        <form action="{{ route('bank-statements.match-accounts', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                <i class="fas fa-book"></i>
                                <span>Match Accounts</span>
                            </button>
                        </form>

                        {{-- Rematch All --}}
                        <form action="{{ route('bank-statements.rematch-all', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('This will reset all categorization. Continue?')">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                <i class="fas fa-redo"></i>
                                <span>Rematch All</span>
                            </button>
                        </form>

                        {{-- Verify All Matched --}}
                        @if($statistics['categorized'] > 0)
                            <form action="{{ route('bank-statements.verify-all-matched', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify all categorized transactions?')">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                    <i class="fas fa-shield-check"></i>
                                    <span>Verify All</span>
                                </button>
                            </form>
                        @endif

                        {{-- Verify High Confidence --}}
                        @if($statistics['high_confidence'] > 0)
                            <form action="{{ route('bank-statements.verify-high-confidence', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify all high confidence (80%+) transactions?')">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                    <i class="fas fa-check-double"></i>
                                    <span>Verify High Confidence</span>
                                </button>
                            </form>
                        @endif

                        {{-- Reconciliation --}}
                        @if(!$bankStatement->is_reconciled && $bankStatement->is_fully_verified)
                            <form action="{{ route('bank-statements.reconcile', $bankStatement) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                    <i class="fas fa-check-double"></i>
                                    <span>Reconcile</span>
                                </button>
                            </form>
                        @elseif($bankStatement->is_reconciled)
                            <form action="{{ route('bank-statements.unreconcile', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Unreconcile this statement?')">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                    <i class="fas fa-times"></i>
                                    <span>Unreconcile</span>
                                </button>
                            </form>
                        @endif

                        {{-- Download PDF --}}
                        <a href="{{ route('bank-statements.download', $bankStatement) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-download"></i>
                            <span>Download PDF</span>
                        </a>

                        {{-- Reprocess OCR --}}
                        @if($bankStatement->ocr_status === 'failed')
                            <form action="{{ route('bank-statements.reprocess', $bankStatement) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                    <i class="fas fa-sync-alt"></i>
                                    <span>Retry OCR</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Transactions List --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-700">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <h4 class="text-lg font-bold text-white">
                            <i class="fas fa-list mr-2"></i>Transactions ({{ $bankStatement->transactions->count() }})
                        </h4>

                        {{-- Filter Buttons --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="filterTransactions('all')" data-filter="all" class="filter-btn active bg-slate-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                All <span class="ml-1">({{ $statistics['total'] }})</span>
                            </button>
                            <button onclick="filterTransactions('categorized')" data-filter="categorized" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Categorized <span class="ml-1">({{ $statistics['categorized'] }})</span>
                            </button>
                            <button onclick="filterTransactions('uncategorized')" data-filter="uncategorized" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Uncategorized <span class="ml-1">({{ $statistics['uncategorized'] }})</span>
                            </button>
                            <button onclick="filterTransactions('with-account')" data-filter="with-account" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                With Account <span class="ml-1">({{ $statistics['with_account'] }})</span>
                            </button>
                            <button onclick="filterTransactions('verified')" data-filter="verified" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Verified <span class="ml-1">({{ $statistics['verified'] }})</span>
                            </button>
                            <button onclick="filterTransactions('low-confidence')" data-filter="low-confidence" class="filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Low Confidence <span class="ml-1">({{ $statistics['low_confidence'] }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if($bankStatement->transactions->count() > 0)
                        <div class="space-y-3">
                            @foreach($bankStatement->transactions as $transaction)
                                <div class="transaction-item bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500 transition duration-200"
                                     data-categorized="{{ $transaction->sub_category_id ? 'yes' : 'no' }}"
                                     data-account="{{ $transaction->account_id ? 'yes' : 'no' }}"
                                     data-verified="{{ $transaction->is_verified ? 'yes' : 'no' }}"
                                     data-confidence="{{ $transaction->confidence_score }}">
                                    
                                    <div class="flex items-start justify-between gap-4">
                                        {{-- Main Info --}}
                                        <div class="flex-1 min-w-0">
                                            {{-- Date & Status Badges --}}
                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                <span class="text-white font-semibold whitespace-nowrap">
                                                    {{ $transaction->transaction_date->format('d M Y') }}
                                                </span>
                                                @if($transaction->transaction_time)
                                                    <span class="text-gray-400 text-sm">{{ $transaction->transaction_time }}</span>
                                                @endif
                                                
                                                {{-- Verified Badge --}}
                                                @if($transaction->is_verified)
                                                    <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold whitespace-nowrap">
                                                        <i class="fas fa-check-circle mr-1"></i>Verified
                                                    </span>
                                                @endif

                                                {{-- Category Badge --}}
                                                @if($transaction->sub_category_id)
                                                    <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs font-semibold whitespace-nowrap">
                                                        <i class="fas fa-tags mr-1"></i>
                                                        {{ $transaction->is_manual_category ? 'Manual' : 'Auto' }}
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 bg-orange-600/20 text-orange-400 rounded text-xs font-semibold whitespace-nowrap">
                                                        <i class="fas fa-exclamation mr-1"></i>Uncategorized
                                                    </span>
                                                @endif

                                                {{-- Account Badge --}}
                                                @if($transaction->account_id)
                                                    <span class="px-2 py-1 bg-cyan-600/20 text-cyan-400 rounded text-xs font-semibold whitespace-nowrap">
                                                        <i class="fas fa-book mr-1"></i>
                                                        {{ $transaction->is_manual_account ? 'Manual' : 'Auto' }}
                                                    </span>
                                                @endif

                                                {{-- Confidence Score --}}
                                                @if($transaction->confidence_score > 0)
                                                    <span class="px-2 py-1 rounded text-xs font-semibold whitespace-nowrap
                                                        {{ $transaction->confidence_score >= 80 ? 'bg-green-600/20 text-green-400' : 
                                                           ($transaction->confidence_score >= 50 ? 'bg-yellow-600/20 text-yellow-400' : 'bg-red-600/20 text-red-400') }}">
                                                        {{ $transaction->confidence_score }}%
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Description --}}
                                            <p class="text-white font-semibold mb-2 break-words">{{ $transaction->description }}</p>

                                            {{-- Category Path --}}
                                            @if($transaction->subCategory)
                                                <div class="flex flex-wrap items-center gap-1 text-xs text-gray-400 mb-2">
                                                    <i class="fas fa-folder"></i>
                                                    <span>{{ $transaction->type->name ?? 'N/A' }}</span>
                                                    <i class="fas fa-chevron-right text-xs"></i>
                                                    <span>{{ $transaction->category->name ?? 'N/A' }}</span>
                                                    <i class="fas fa-chevron-right text-xs"></i>
                                                    <span class="text-blue-400 font-semibold">{{ $transaction->subCategory->name }}</span>
                                                </div>
                                            @endif

                                            {{-- Account Info --}}
                                            @if($transaction->account)
                                                <div class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                                                    <i class="fas fa-book"></i>
                                                    <span class="text-cyan-400 font-semibold">{{ $transaction->account->name }}</span>
                                                    @if($transaction->account->code)
                                                        <span class="text-gray-500">({{ $transaction->account->code }})</span>
                                                    @endif
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
                                        <div class="text-right flex-shrink-0">
                                            <div class="text-xl md:text-2xl font-bold mb-3 whitespace-nowrap {{ $transaction->transaction_type == 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                                {{ $transaction->transaction_type == 'debit' ? '-' : '+' }}
                                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                            </div>
                                            <a href="{{ route('transactions.show', $transaction) }}" 
                                               class="inline-flex items-center space-x-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg transition text-sm font-semibold">
                                                <i class="fas fa-eye"></i>
                                                <span class="hidden sm:inline">View</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Pagination if needed --}}
                        {{-- {{ $bankStatement->transactions->links() }} --}}
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No transactions found</p>
                            <p class="text-gray-500 text-sm mt-2">This statement hasn't been processed yet</p>
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
                const categorized = item.dataset.categorized;
                const hasAccount = item.dataset.account;
                const verified = item.dataset.verified;
                const confidence = parseInt(item.dataset.confidence);
                
                let shouldShow = false;
                
                switch(filter) {
                    case 'all':
                        shouldShow = true;
                        break;
                    case 'categorized':
                        shouldShow = categorized === 'yes';
                        break;
                    case 'uncategorized':
                        shouldShow = categorized === 'no';
                        break;
                    case 'with-account':
                        shouldShow = hasAccount === 'yes';
                        break;
                    case 'verified':
                        shouldShow = verified === 'yes';
                        break;
                    case 'low-confidence':
                        shouldShow = confidence > 0 && confidence < 50;
                        break;
                }
                
                item.style.display = shouldShow ? 'block' : 'none';
            });
        }
    </script>
</x-app-layout>