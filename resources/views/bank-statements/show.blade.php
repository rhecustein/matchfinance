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

            {{-- Header Info - Enhanced --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        @if($bankStatement->bank->logo)
                            <div class="w-16 h-16 bg-slate-700 rounded-xl flex items-center justify-center">
                                <img src="{{ asset('storage/' . $bankStatement->bank->logo) }}" 
                                     alt="{{ $bankStatement->bank->name }}" 
                                     class="w-14 h-14 object-contain">
                            </div>
                        @else
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-university text-white text-2xl"></i>
                            </div>
                        @endif
                        
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-1">
                                {{ $bankStatement->bank->name }}
                            </h3>
                            <p class="text-gray-400 text-sm flex items-center">
                                <i class="fas fa-file-pdf mr-2"></i>
                                {{ $bankStatement->original_filename }}
                            </p>
                            @if($bankStatement->account_holder_name)
                                <p class="text-gray-500 text-xs mt-1 flex items-center">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $bankStatement->account_holder_name }}
                                </p>
                            @endif
                            
                            {{-- Company Info (for Super Admin) --}}
                            @if(auth()->user()->isSuperAdmin() && $bankStatement->company)
                                <p class="text-purple-400 text-xs mt-1 flex items-center">
                                    <i class="fas fa-building mr-1"></i>
                                    Company: <span class="font-semibold ml-1">{{ $bankStatement->company->name }}</span>
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
                            <span>OCR: {{ ucfirst($bankStatement->ocr_status) }}</span>
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

                {{-- Statement Details Grid - Enhanced --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Period</p>
                        <p class="text-sm font-semibold text-white">
                            @if($bankStatement->period_from && $bankStatement->period_to)
                                {{ $bankStatement->period_from->format('d M Y') }} - {{ $bankStatement->period_to->format('d M Y') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Account Number</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->account_number ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Currency</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->currency ?? 'IDR' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">File Size</p>
                        <p class="text-sm font-semibold text-white">
                            {{ number_format($bankStatement->file_size / 1024 / 1024, 2) }} MB
                        </p>
                    </div>
                </div>

                {{-- User & Upload Info --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Uploaded By</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->user->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->uploaded_at->format('d M Y H:i') }}</p>
                    </div>
                    
                    @if($bankStatement->ocr_completed_at)
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">OCR Completed</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->ocr_completed_at->format('d M Y H:i') }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Duration: {{ $bankStatement->ocr_started_at ? $bankStatement->ocr_started_at->diffForHumans($bankStatement->ocr_completed_at, true) : 'N/A' }}
                        </p>
                    </div>
                    @endif
                    
                    @if($bankStatement->is_reconciled && $bankStatement->reconciledBy)
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-purple-700/50">
                        <p class="text-xs text-gray-400 mb-1">Reconciled By</p>
                        <p class="text-sm font-semibold text-purple-400">{{ $bankStatement->reconciledBy->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->reconciled_at->format('d M Y H:i') }}</p>
                    </div>
                    @endif
                </div>

                {{-- User Notes --}}
                @if($bankStatement->notes)
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <p class="text-xs text-gray-400 mb-2">Notes:</p>
                        <p class="text-sm text-gray-300">{{ $bankStatement->notes }}</p>
                    </div>
                @endif

                {{-- OCR Error --}}
                @if($bankStatement->ocr_status === 'failed' && $bankStatement->ocr_error)
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <div class="bg-red-600/20 border border-red-500/30 rounded-lg p-4">
                            <p class="text-xs text-red-400 font-semibold mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>OCR Error:
                            </p>
                            <p class="text-sm text-red-300">{{ $bankStatement->ocr_error }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Statistics Cards - Enhanced with more metrics --}}
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
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
                                {{ $statistics['total'] > 0 ? round(($statistics['categorized'] / $statistics['total']) * 100, 1) : 0 }}%
                            </p>
                        </div>
                        <i class="fas fa-tags text-3xl text-green-300/30"></i>
                    </div>
                </div>

                {{-- Uncategorized --}}
                <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl p-4 border border-orange-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-orange-200 mb-1">Uncategorized</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['uncategorized'] }}</p>
                        </div>
                        <i class="fas fa-exclamation text-3xl text-orange-300/30"></i>
                    </div>
                </div>

                {{-- With Account --}}
                <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-xl p-4 border border-cyan-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-cyan-200 mb-1">With Account</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['with_account'] }}</p>
                            <p class="text-xs text-cyan-200 mt-1">
                                {{ $statistics['total'] > 0 ? round(($statistics['with_account'] / $statistics['total']) * 100, 1) : 0 }}%
                            </p>
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
                                {{ $statistics['total'] > 0 ? round(($statistics['verified'] / $statistics['total']) * 100, 1) : 0 }}%
                            </p>
                        </div>
                        <i class="fas fa-shield-check text-3xl text-purple-300/30"></i>
                    </div>
                </div>

                {{-- High Confidence --}}
                <div class="bg-gradient-to-br from-teal-600 to-teal-700 rounded-xl p-4 border border-teal-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-teal-200 mb-1">High Conf.</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['high_confidence'] }}</p>
                            <p class="text-xs text-teal-200 mt-1">≥80%</p>
                        </div>
                        <i class="fas fa-chart-line text-3xl text-teal-300/30"></i>
                    </div>
                </div>
            </div>

            {{-- Balance Summary - Enhanced --}}
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
                        @php
                            $netChange = ($bankStatement->total_credit_amount ?? 0) - ($bankStatement->total_debit_amount ?? 0);
                        @endphp
                        <p class="text-lg font-semibold {{ $netChange >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $netChange >= 0 ? '+' : '' }}Rp {{ number_format($netChange, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Processing Statistics (Enhanced) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Matching Progress --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700">
                    <h4 class="text-sm font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-blue-400"></i>Category Matching Progress
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-400">Matched</span>
                                <span class="text-white font-semibold">{{ $statistics['categorized'] }}/{{ $statistics['total'] }}</span>
                            </div>
                            <div class="bg-slate-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" 
                                     style="width: {{ $statistics['total'] > 0 ? ($statistics['categorized'] / $statistics['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account Assignment --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700">
                    <h4 class="text-sm font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-book mr-2 text-cyan-400"></i>Account Assignment
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-400">Assigned</span>
                                <span class="text-white font-semibold">{{ $statistics['with_account'] }}/{{ $statistics['total'] }}</span>
                            </div>
                            <div class="bg-slate-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-cyan-500 to-cyan-600 h-2 rounded-full" 
                                     style="width: {{ $statistics['total'] > 0 ? ($statistics['with_account'] / $statistics['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Verification Progress --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700">
                    <h4 class="text-sm font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-shield-check mr-2 text-purple-400"></i>Verification Status
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-400">Verified</span>
                                <span class="text-white font-semibold">{{ $statistics['verified'] }}/{{ $statistics['total'] }}</span>
                            </div>
                            <div class="bg-slate-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full" 
                                     style="width: {{ $statistics['total'] > 0 ? ($statistics['verified'] / $statistics['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                    <span>Verify All Matched ({{ $statistics['categorized'] }})</span>
                                </button>
                            </form>
                        @endif

                        {{-- Verify High Confidence --}}
                        @if($statistics['high_confidence'] > 0)
                            <form action="{{ route('bank-statements.verify-high-confidence', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify all high confidence (80%+) transactions?')">
                                @csrf
                                <input type="hidden" name="threshold" value="80">
                                <button type="submit" class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                                    <i class="fas fa-check-double"></i>
                                    <span>Verify High Conf. ({{ $statistics['high_confidence'] }})</span>
                                </button>
                            </form>
                        @endif

                        {{-- Reconciliation --}}
                        @if(!$bankStatement->is_reconciled && $statistics['verified'] === $statistics['total'] && $statistics['total'] > 0)
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

            {{-- Transactions List with Advanced Filters --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-700">
                    <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                        <h4 class="text-lg font-bold text-white">
                            <i class="fas fa-list mr-2"></i>Transactions (<span id="visibleCount">{{ $bankStatement->transactions->count() }}</span>/{{ $bankStatement->transactions->count() }})
                        </h4>

                        {{-- Search Box --}}
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search description, reference..." 
                                       class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent pl-10">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>

                        {{-- Clear Filters --}}
                        <button onclick="clearAllFilters()" class="bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                            <i class="fas fa-times mr-2"></i>Clear Filters
                        </button>
                    </div>

                    {{-- Status Filters --}}
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 mb-2 font-semibold">Status Filters:</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="filterByStatus('all')" data-filter-status="all" class="status-filter-btn active bg-slate-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                All <span class="ml-1">({{ $statistics['total'] }})</span>
                            </button>
                            <button onclick="filterByStatus('categorized')" data-filter-status="categorized" class="status-filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Categorized <span class="ml-1">({{ $statistics['categorized'] }})</span>
                            </button>
                            <button onclick="filterByStatus('uncategorized')" data-filter-status="uncategorized" class="status-filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Uncategorized <span class="ml-1">({{ $statistics['uncategorized'] }})</span>
                            </button>
                            <button onclick="filterByStatus('with-account')" data-filter-status="with-account" class="status-filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                With Account <span class="ml-1">({{ $statistics['with_account'] }})</span>
                            </button>
                            <button onclick="filterByStatus('verified')" data-filter-status="verified" class="status-filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Verified <span class="ml-1">({{ $statistics['verified'] }})</span>
                            </button>
                            <button onclick="filterByStatus('low-confidence')" data-filter-status="low-confidence" class="status-filter-btn bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition">
                                Low Confidence <span class="ml-1">({{ $statistics['low_confidence'] }})</span>
                            </button>
                        </div>
                    </div>

                    {{-- Advanced Filters --}}
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 mb-2 font-semibold">Advanced Filters:</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            {{-- Amount Filters --}}
                            <button onclick="sortByAmount('highest')" class="bg-gradient-to-r from-red-600/20 to-red-700/20 text-red-400 hover:from-red-600 hover:to-red-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50">
                                <i class="fas fa-arrow-up mr-1"></i>Highest Amount
                            </button>
                            <button onclick="sortByAmount('lowest')" class="bg-gradient-to-r from-green-600/20 to-green-700/20 text-green-400 hover:from-green-600 hover:to-green-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-green-700/50">
                                <i class="fas fa-arrow-down mr-1"></i>Lowest Amount
                            </button>
                            
                            {{-- Date Filters --}}
                            <button onclick="sortByDate('newest')" class="bg-gradient-to-r from-blue-600/20 to-blue-700/20 text-blue-400 hover:from-blue-600 hover:to-blue-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-blue-700/50">
                                <i class="fas fa-calendar mr-1"></i>Newest First
                            </button>
                            <button onclick="sortByDate('oldest')" class="bg-gradient-to-r from-purple-600/20 to-purple-700/20 text-purple-400 hover:from-purple-600 hover:to-purple-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-purple-700/50">
                                <i class="fas fa-calendar mr-1"></i>Oldest First
                            </button>
                            
                            {{-- Type Filters --}}
                            <button onclick="filterByType('credit')" class="bg-gradient-to-r from-green-600/20 to-green-700/20 text-green-400 hover:from-green-600 hover:to-green-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-green-700/50">
                                <i class="fas fa-plus mr-1"></i>Credit Only
                            </button>
                            <button onclick="filterByType('debit')" class="bg-gradient-to-r from-red-600/20 to-red-700/20 text-red-400 hover:from-red-600 hover:to-red-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50">
                                <i class="fas fa-minus mr-1"></i>Debit Only
                            </button>
                            
                            {{-- Pattern Filters --}}
                            <button onclick="findDuplicateAmounts()" class="bg-gradient-to-r from-orange-600/20 to-orange-700/20 text-orange-400 hover:from-orange-600 hover:to-orange-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-orange-700/50">
                                <i class="fas fa-clone mr-1"></i>Duplicate Amounts
                            </button>
                            <button onclick="findRoundNumbers()" class="bg-gradient-to-r from-yellow-600/20 to-yellow-700/20 text-yellow-400 hover:from-yellow-600 hover:to-yellow-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-yellow-700/50">
                                <i class="fas fa-circle mr-1"></i>Round Numbers
                            </button>
                            
                            {{-- Amount Range Filters --}}
                            <button onclick="filterByAmountRange('large')" class="bg-gradient-to-r from-indigo-600/20 to-indigo-700/20 text-indigo-400 hover:from-indigo-600 hover:to-indigo-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-indigo-700/50">
                                <i class="fas fa-fire mr-1"></i>Large (>1M)
                            </button>
                            <button onclick="filterByAmountRange('medium')" class="bg-gradient-to-r from-cyan-600/20 to-cyan-700/20 text-cyan-400 hover:from-cyan-600 hover:to-cyan-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-cyan-700/50">
                                <i class="fas fa-dollar-sign mr-1"></i>Medium (100K-1M)
                            </button>
                            <button onclick="filterByAmountRange('small')" class="bg-gradient-to-r from-teal-600/20 to-teal-700/20 text-teal-400 hover:from-teal-600 hover:to-teal-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-teal-700/50">
                                <i class="fas fa-coins mr-1"></i>Small (<100K)
                            </button>
                            
                            {{-- Pattern Analysis --}}
                            <button onclick="findRecurringPatterns()" class="bg-gradient-to-r from-pink-600/20 to-pink-700/20 text-pink-400 hover:from-pink-600 hover:to-pink-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-pink-700/50">
                                <i class="fas fa-repeat mr-1"></i>Recurring Patterns
                            </button>
                        </div>
                    </div>

                    {{-- Active Filters Display --}}
                    <div id="activeFilters" class="hidden bg-blue-600/10 border border-blue-500/30 rounded-lg p-3 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-filter text-blue-400"></i>
                                <span class="text-sm text-blue-300 font-semibold">Active Filters:</span>
                                <div id="filterTags" class="flex flex-wrap gap-2"></div>
                            </div>
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
        // State management
        let currentStatusFilter = 'all';
        let currentTypeFilter = 'all';
        let currentSortBy = null;
        let currentAmountRange = null;
        let searchQuery = '';
        let duplicateMode = false;
        let roundNumberMode = false;
        let recurringMode = false;

        // Get all transactions data
        const allTransactions = Array.from(document.querySelectorAll('.transaction-item'));
        
        // Parse transaction data
        allTransactions.forEach(item => {
            const amountText = item.querySelector('.text-xl, .text-2xl').textContent;
            const amount = parseInt(amountText.replace(/[^0-9]/g, ''));
            const dateText = item.querySelector('.text-white.font-semibold.whitespace-nowrap').textContent;
            const description = item.querySelector('.text-white.font-semibold.break-words').textContent.toLowerCase();
            const type = item.querySelector('.text-red-400, .text-green-400') ? 
                        (item.querySelector('.text-red-400') ? 'debit' : 'credit') : 'unknown';
            
            item.dataset.amount = amount;
            item.dataset.date = dateText;
            item.dataset.description = description;
            item.dataset.type = type;
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            searchQuery = e.target.value.toLowerCase();
            applyAllFilters();
        });

        // Status filter
        function filterByStatus(status) {
            currentStatusFilter = status;
            updateStatusButtons(status);
            applyAllFilters();
        }

        function updateStatusButtons(activeStatus) {
            document.querySelectorAll('.status-filter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-slate-700', 'text-white');
                btn.classList.add('bg-slate-700/50', 'text-gray-400');
                
                if (btn.dataset.filterStatus === activeStatus) {
                    btn.classList.add('active', 'bg-slate-700', 'text-white');
                    btn.classList.remove('bg-slate-700/50', 'text-gray-400');
                }
            });
        }

        // Type filter (Credit/Debit)
        function filterByType(type) {
            currentTypeFilter = type;
            addFilterTag(type === 'credit' ? 'Credit Only' : 'Debit Only', 'type');
            applyAllFilters();
        }

        // Sort by amount
        function sortByAmount(direction) {
            currentSortBy = `amount-${direction}`;
            addFilterTag(direction === 'highest' ? 'Highest Amount' : 'Lowest Amount', 'sort');
            
            const container = document.querySelector('.space-y-3');
            const items = Array.from(allTransactions);
            
            items.sort((a, b) => {
                const amountA = parseInt(a.dataset.amount);
                const amountB = parseInt(b.dataset.amount);
                return direction === 'highest' ? amountB - amountA : amountA - amountB;
            });
            
            items.forEach(item => container.appendChild(item));
            applyAllFilters();
        }

        // Sort by date
        function sortByDate(direction) {
            currentSortBy = `date-${direction}`;
            addFilterTag(direction === 'newest' ? 'Newest First' : 'Oldest First', 'sort');
            
            const container = document.querySelector('.space-y-3');
            const items = Array.from(allTransactions);
            
            items.sort((a, b) => {
                const dateA = new Date(a.dataset.date);
                const dateB = new Date(b.dataset.date);
                return direction === 'newest' ? dateB - dateA : dateA - dateB;
            });
            
            items.forEach(item => container.appendChild(item));
            applyAllFilters();
        }

        // Filter by amount range
        function filterByAmountRange(range) {
            currentAmountRange = range;
            const rangeText = range === 'large' ? 'Large (>1M)' : 
                            range === 'medium' ? 'Medium (100K-1M)' : 'Small (<100K)';
            addFilterTag(rangeText, 'range');
            applyAllFilters();
        }

        // Find duplicate amounts
        function findDuplicateAmounts() {
            duplicateMode = !duplicateMode;
            roundNumberMode = false;
            recurringMode = false;
            
            if (duplicateMode) {
                addFilterTag('Duplicate Amounts', 'pattern');
                
                // Find amounts that appear more than once
                const amounts = {};
                allTransactions.forEach(item => {
                    const amount = item.dataset.amount;
                    amounts[amount] = (amounts[amount] || 0) + 1;
                });
                
                const duplicates = Object.keys(amounts).filter(amt => amounts[amt] > 1);
                
                allTransactions.forEach(item => {
                    if (duplicates.includes(item.dataset.amount)) {
                        item.classList.add('duplicate-highlight');
                        item.style.borderColor = '#f59e0b';
                    }
                });
                
                showNotification(`Found ${duplicates.length} duplicate amount(s)`, 'info');
            } else {
                removeFilterTag('pattern');
                allTransactions.forEach(item => {
                    item.classList.remove('duplicate-highlight');
                    item.style.borderColor = '';
                });
            }
            
            applyAllFilters();
        }

        // Find round numbers
        function findRoundNumbers() {
            roundNumberMode = !roundNumberMode;
            duplicateMode = false;
            recurringMode = false;
            
            if (roundNumberMode) {
                addFilterTag('Round Numbers', 'pattern');
                
                allTransactions.forEach(item => {
                    const amount = parseInt(item.dataset.amount);
                    // Check if divisible by 100000 or 1000000
                    if (amount % 1000000 === 0 || amount % 100000 === 0) {
                        item.classList.add('round-highlight');
                        item.style.borderColor = '#3b82f6';
                    }
                });
                
                const roundCount = allTransactions.filter(item => 
                    item.classList.contains('round-highlight')).length;
                showNotification(`Found ${roundCount} round number(s)`, 'info');
            } else {
                removeFilterTag('pattern');
                allTransactions.forEach(item => {
                    item.classList.remove('round-highlight');
                    item.style.borderColor = '';
                });
            }
            
            applyAllFilters();
        }

        // Find recurring patterns
        function findRecurringPatterns() {
            recurringMode = !recurringMode;
            duplicateMode = false;
            roundNumberMode = false;
            
            if (recurringMode) {
                addFilterTag('Recurring Patterns', 'pattern');
                
                // Group by description keywords
                const patterns = {};
                allTransactions.forEach(item => {
                    const desc = item.dataset.description;
                    const words = desc.split(' ').filter(w => w.length > 3);
                    words.forEach(word => {
                        patterns[word] = (patterns[word] || 0) + 1;
                    });
                });
                
                const recurring = Object.keys(patterns).filter(word => patterns[word] > 2);
                
                allTransactions.forEach(item => {
                    const desc = item.dataset.description;
                    if (recurring.some(word => desc.includes(word))) {
                        item.classList.add('recurring-highlight');
                        item.style.borderColor = '#8b5cf6';
                    }
                });
                
                const recurringCount = allTransactions.filter(item => 
                    item.classList.contains('recurring-highlight')).length;
                showNotification(`Found ${recurringCount} potential recurring transaction(s)`, 'info');
            } else {
                removeFilterTag('pattern');
                allTransactions.forEach(item => {
                    item.classList.remove('recurring-highlight');
                    item.style.borderColor = '';
                });
            }
            
            applyAllFilters();
        }

        // Apply all filters
        function applyAllFilters() {
            let visibleCount = 0;
            
            allTransactions.forEach(item => {
                const categorized = item.dataset.categorized;
                const hasAccount = item.dataset.account;
                const verified = item.dataset.verified;
                const confidence = parseInt(item.dataset.confidence);
                const amount = parseInt(item.dataset.amount);
                const type = item.dataset.type;
                const description = item.dataset.description;
                
                let show = true;
                
                // Status filter
                switch(currentStatusFilter) {
                    case 'categorized':
                        show = show && categorized === 'yes';
                        break;
                    case 'uncategorized':
                        show = show && categorized === 'no';
                        break;
                    case 'with-account':
                        show = show && hasAccount === 'yes';
                        break;
                    case 'verified':
                        show = show && verified === 'yes';
                        break;
                    case 'low-confidence':
                        show = show && confidence > 0 && confidence < 50;
                        break;
                }
                
                // Type filter
                if (currentTypeFilter !== 'all') {
                    show = show && type === currentTypeFilter;
                }
                
                // Amount range filter
                if (currentAmountRange) {
                    switch(currentAmountRange) {
                        case 'large':
                            show = show && amount > 1000000;
                            break;
                        case 'medium':
                            show = show && amount >= 100000 && amount <= 1000000;
                            break;
                        case 'small':
                            show = show && amount < 100000;
                            break;
                    }
                }
                
                // Pattern filters
                if (duplicateMode) {
                    show = show && item.classList.contains('duplicate-highlight');
                }
                if (roundNumberMode) {
                    show = show && item.classList.contains('round-highlight');
                }
                if (recurringMode) {
                    show = show && item.classList.contains('recurring-highlight');
                }
                
                // Search filter
                if (searchQuery) {
                    const refNo = item.querySelector('.fa-hashtag')?.parentElement?.textContent.toLowerCase() || '';
                    show = show && (description.includes(searchQuery) || refNo.includes(searchQuery));
                }
                
                item.style.display = show ? 'block' : 'none';
                if (show) visibleCount++;
            });
            
            // Update visible count
            document.getElementById('visibleCount').textContent = visibleCount;
        }

        // Clear all filters
        function clearAllFilters() {
            currentStatusFilter = 'all';
            currentTypeFilter = 'all';
            currentSortBy = null;
            currentAmountRange = null;
            searchQuery = '';
            duplicateMode = false;
            roundNumberMode = false;
            recurringMode = false;
            
            document.getElementById('searchInput').value = '';
            updateStatusButtons('all');
            
            // Clear highlights
            allTransactions.forEach(item => {
                item.classList.remove('duplicate-highlight', 'round-highlight', 'recurring-highlight');
                item.style.borderColor = '';
                item.style.display = 'block';
            });
            
            // Clear filter tags
            document.getElementById('activeFilters').classList.add('hidden');
            document.getElementById('filterTags').innerHTML = '';
            
            // Update count
            document.getElementById('visibleCount').textContent = allTransactions.length;
            
            showNotification('All filters cleared', 'success');
        }

        // Filter tags management
        function addFilterTag(text, type) {
            const activeFilters = document.getElementById('activeFilters');
            const filterTags = document.getElementById('filterTags');
            
            // Remove existing tag of same type
            const existing = filterTags.querySelector(`[data-filter-type="${type}"]`);
            if (existing) existing.remove();
            
            // Add new tag
            const tag = document.createElement('span');
            tag.className = 'px-2 py-1 bg-blue-600/30 text-blue-300 rounded text-xs font-semibold border border-blue-500/50';
            tag.dataset.filterType = type;
            tag.innerHTML = `${text} <i class="fas fa-times ml-1 cursor-pointer" onclick="removeFilterByType('${type}')"></i>`;
            filterTags.appendChild(tag);
            
            activeFilters.classList.remove('hidden');
        }

        function removeFilterTag(type) {
            const filterTags = document.getElementById('filterTags');
            const tag = filterTags.querySelector(`[data-filter-type="${type}"]`);
            if (tag) tag.remove();
            
            if (filterTags.children.length === 0) {
                document.getElementById('activeFilters').classList.add('hidden');
            }
        }

        function removeFilterByType(type) {
            switch(type) {
                case 'type':
                    currentTypeFilter = 'all';
                    break;
                case 'sort':
                    currentSortBy = null;
                    break;
                case 'range':
                    currentAmountRange = null;
                    break;
                case 'pattern':
                    duplicateMode = false;
                    roundNumberMode = false;
                    recurringMode = false;
                    allTransactions.forEach(item => {
                        item.classList.remove('duplicate-highlight', 'round-highlight', 'recurring-highlight');
                        item.style.borderColor = '';
                    });
                    break;
            }
            removeFilterTag(type);
            applyAllFilters();
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const colors = {
                info: 'bg-blue-600/20 text-blue-400 border-blue-500',
                success: 'bg-green-600/20 text-green-400 border-green-500',
                error: 'bg-red-600/20 text-red-400 border-red-500',
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} border px-6 py-3 rounded-lg shadow-xl flex items-center space-x-3 z-50 animate-fade-in`;
            notification.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span class="font-semibold">${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
    </script>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
        .duplicate-highlight {
            background: rgba(245, 158, 11, 0.1) !important;
        }
        .round-highlight {
            background: rgba(59, 130, 246, 0.1) !important;
        }
        .recurring-highlight {
            background: rgba(139, 92, 246, 0.1) !important;
        }
    </style>
</x-app-layout>