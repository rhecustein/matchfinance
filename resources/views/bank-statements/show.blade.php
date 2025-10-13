<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Bank Statement Details') }}
            </h2>
            <a href="{{ route('bank-statements.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Messages --}}
            @if(session('success'))
                <div class="bg-green-600/20 border border-green-600 text-green-400 px-6 py-3 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p class="font-semibold">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-6 py-3 rounded-lg flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p class="font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Header Info --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 border border-slate-700 shadow-xl">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        @if($bankStatement->bank->logo)
                            <div class="w-12 h-12 bg-slate-700 rounded-lg flex items-center justify-center">
                                <img src="{{ asset('storage/' . $bankStatement->bank->logo) }}" 
                                     alt="{{ $bankStatement->bank->name }}" 
                                     class="w-10 h-10 object-contain">
                            </div>
                        @else
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-university text-white text-xl"></i>
                            </div>
                        @endif
                        
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $bankStatement->bank->name }}</h3>
                            <p class="text-gray-400 text-sm">{{ $bankStatement->original_filename }}</p>
                            @if($bankStatement->account_holder_name)
                                <p class="text-gray-500 text-xs mt-1">
                                    <i class="fas fa-user mr-1"></i>{{ $bankStatement->account_holder_name }}
                                </p>
                            @endif
                            
                            {{-- Company Info (Super Admin Only) --}}
                            @if(auth()->user()->isSuperAdmin() && $bankStatement->company)
                                <div class="mt-2 flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-1 bg-purple-600/20 text-purple-400 border border-purple-600/50 rounded text-xs font-semibold">
                                        <i class="fas fa-building mr-1.5"></i>
                                        {{ $bankStatement->company->name }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-user mr-1"></i>{{ $bankStatement->user->name }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="text-right space-y-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold
                            {{ $bankStatement->ocr_status === 'completed' ? 'bg-green-600/20 text-green-400 border border-green-600' : '' }}
                            {{ $bankStatement->ocr_status === 'processing' ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-600' : '' }}
                            {{ $bankStatement->ocr_status === 'failed' ? 'bg-red-600/20 text-red-400 border border-red-600' : '' }}
                            {{ $bankStatement->ocr_status === 'pending' ? 'bg-blue-600/20 text-blue-400 border border-blue-600' : '' }}">
                            <i class="fas fa-circle mr-1"></i>OCR: {{ ucfirst($bankStatement->ocr_status) }}
                        </span>
                        @if($bankStatement->is_reconciled)
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-purple-600/20 text-purple-400 border border-purple-600">
                                <i class="fas fa-check-double mr-1"></i>Reconciled
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Statement Details --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Period</p>
                        <p class="text-sm font-semibold text-white">
                            @if($bankStatement->period_from && $bankStatement->period_to)
                                {{ $bankStatement->period_from->format('d M Y') }} - {{ $bankStatement->period_to->format('d M Y') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Account Number</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->account_number ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Currency</p>
                        <p class="text-sm font-semibold text-white">{{ $bankStatement->currency ?? 'IDR' }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">File Size</p>
                        <p class="text-sm font-semibold text-white">{{ number_format($bankStatement->file_size / 1024 / 1024, 2) }} MB</p>
                    </div>
                </div>

                @if($bankStatement->notes)
                    <div class="mt-3 pt-3 border-t border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Notes:</p>
                        <p class="text-sm text-gray-300">{{ $bankStatement->notes }}</p>
                    </div>
                @endif

                @if($bankStatement->ocr_status === 'failed' && $bankStatement->ocr_error)
                    <div class="mt-3 pt-3 border-t border-slate-700">
                        <div class="bg-red-600/20 border border-red-500/30 rounded-lg p-3">
                            <p class="text-xs text-red-400 font-semibold mb-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>OCR Error:
                            </p>
                            <p class="text-sm text-red-300">{{ $bankStatement->ocr_error }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Statistics Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg p-3 border border-blue-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-200">Total</p>
                            <p class="text-xl font-bold text-white">{{ $statistics['total'] }}</p>
                        </div>
                        <i class="fas fa-receipt text-2xl text-blue-300/30"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-lg p-3 border border-green-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-green-200">Categorized</p>
                            <p class="text-xl font-bold text-white">{{ $statistics['categorized'] }}</p>
                            <p class="text-xs text-green-200">{{ $statistics['total'] > 0 ? round(($statistics['categorized'] / $statistics['total']) * 100, 1) : 0 }}%</p>
                        </div>
                        <i class="fas fa-tags text-2xl text-green-300/30"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-lg p-3 border border-orange-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-orange-200">Uncategorized</p>
                            <p class="text-xl font-bold text-white">{{ $statistics['uncategorized'] }}</p>
                        </div>
                        <i class="fas fa-exclamation text-2xl text-orange-300/30"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-lg p-3 border border-cyan-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-cyan-200">With Account</p>
                            <p class="text-xl font-bold text-white">{{ $statistics['with_account'] }}</p>
                            <p class="text-xs text-cyan-200">{{ $statistics['total'] > 0 ? round(($statistics['with_account'] / $statistics['total']) * 100, 1) : 0 }}%</p>
                        </div>
                        <i class="fas fa-book text-2xl text-cyan-300/30"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg p-3 border border-purple-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-200">Verified</p>
                            <p class="text-xl font-bold text-white">{{ $statistics['verified'] }}</p>
                            <p class="text-xs text-purple-200">{{ $statistics['total'] > 0 ? round(($statistics['verified'] / $statistics['total']) * 100, 1) : 0 }}%</p>
                        </div>
                        <i class="fas fa-shield-check text-2xl text-purple-300/30"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-teal-600 to-teal-700 rounded-lg p-3 border border-teal-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-teal-200">High Conf.</p>
                            <p class="text-xl font-bold text-white">{{ $statistics['high_confidence'] }}</p>
                            <p class="text-xs text-teal-200">≥80%</p>
                        </div>
                        <i class="fas fa-chart-line text-2xl text-teal-300/30"></i>
                    </div>
                </div>
            </div>

            {{-- Balance Summary --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 border border-slate-700 shadow-xl">
                <h4 class="text-base font-bold text-white mb-3 flex items-center">
                    <i class="fas fa-wallet mr-2"></i>Balance Summary
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Opening</p>
                        <p class="text-base font-semibold text-white">Rp {{ number_format($bankStatement->opening_balance ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-green-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Credit</p>
                        <p class="text-base font-semibold text-green-400">Rp {{ number_format($bankStatement->total_credit_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->total_credit_count }} trx</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-red-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Debit</p>
                        <p class="text-base font-semibold text-red-400">Rp {{ number_format($bankStatement->total_debit_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $bankStatement->total_debit_count }} trx</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-blue-700/50">
                        <p class="text-xs text-gray-400 mb-1">Closing</p>
                        <p class="text-base font-semibold text-blue-400">Rp {{ number_format($bankStatement->closing_balance ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3 border border-yellow-700/50">
                        <p class="text-xs text-gray-400 mb-1">Net Change</p>
                        @php
                            $netChange = ($bankStatement->total_credit_amount ?? 0) - ($bankStatement->total_debit_amount ?? 0);
                        @endphp
                        <p class="text-base font-semibold {{ $netChange >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $netChange >= 0 ? '+' : '' }}Rp {{ number_format($netChange, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            @if($transactions->total() > 0)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-4 border border-slate-700 shadow-xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <form action="{{ route('bank-statements.match-transactions', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                <i class="fas fa-tags mr-2"></i>Match Categories
                            </button>
                        </form>

                        <form action="{{ route('bank-statements.match-accounts', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                <i class="fas fa-book mr-2"></i>Match Accounts
                            </button>
                        </form>

                        <form action="{{ route('bank-statements.rematch-all', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Reset all categorization?')">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                <i class="fas fa-redo mr-2"></i>Rematch All
                            </button>
                        </form>

                        @if($statistics['categorized'] > 0)
                            <form action="{{ route('bank-statements.verify-all-matched', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify all categorized transactions?')">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                    <i class="fas fa-shield-check mr-2"></i>Verify Matched ({{ $statistics['categorized'] }})
                                </button>
                            </form>
                        @endif

                        @if($statistics['high_confidence'] > 0)
                            <form action="{{ route('bank-statements.verify-high-confidence', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify high confidence transactions?')">
                                @csrf
                                <input type="hidden" name="threshold" value="80">
                                <button type="submit" class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                    <i class="fas fa-check-double mr-2"></i>Verify High Conf. ({{ $statistics['high_confidence'] }})
                                </button>
                            </form>
                        @endif

                        @if(!$bankStatement->is_reconciled && $statistics['verified'] === $statistics['total'] && $statistics['total'] > 0)
                            <form action="{{ route('bank-statements.reconcile', $bankStatement) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                    <i class="fas fa-check-double mr-2"></i>Reconcile
                                </button>
                            </form>
                        @elseif($bankStatement->is_reconciled)
                            <form action="{{ route('bank-statements.unreconcile', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Unreconcile this statement?')">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                    <i class="fas fa-times mr-2"></i>Unreconcile
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('bank-statements.download', $bankStatement) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>

                        @if($bankStatement->ocr_status === 'failed')
                            <form action="{{ route('bank-statements.reprocess', $bankStatement) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center">
                                    <i class="fas fa-sync-alt mr-2"></i>Retry OCR
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Transactions List with Complete Filters --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="p-4 border-b border-slate-700">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                        <h4 class="text-base font-bold text-white flex items-center">
                            <i class="fas fa-list mr-2"></i>Transactions ({{ $transactions->total() }})
                        </h4>
                        <div class="text-sm text-gray-400">
                            Showing {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}
                        </div>
                    </div>

                    {{-- Status Filters --}}
                    <div class="mb-3">
                        <p class="text-xs text-gray-400 mb-2 font-semibold">Quick Filters:</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('bank-statements.show', $bankStatement) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ !request()->has('filter') ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                All ({{ $statistics['total'] }})
                            </a>
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'categorized']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('filter') === 'categorized' ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                Categorized ({{ $statistics['categorized'] }})
                            </a>
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'uncategorized']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('filter') === 'uncategorized' ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                Uncategorized ({{ $statistics['uncategorized'] }})
                            </a>
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'verified']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('filter') === 'verified' ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                Verified ({{ $statistics['verified'] }})
                            </a>
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'with-account']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('filter') === 'with-account' ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                With Account ({{ $statistics['with_account'] }})
                            </a>
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'high-confidence']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('filter') === 'high-confidence' ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                High Conf. ({{ $statistics['high_confidence'] }})
                            </a>
                        </div>
                    </div>

                    {{-- Advanced Filters --}}
                    <div>
                        <p class="text-xs text-gray-400 mb-2 font-semibold">Advanced Filters:</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                            {{-- Amount Sorting --}}
                            @php
                                $params = request()->all();
                                $params['sort'] = 'amount-desc';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-red-600/20 to-red-700/20 text-red-400 hover:from-red-600 hover:to-red-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50 text-center">
                                <i class="fas fa-arrow-up mr-1"></i>Highest
                            </a>
                            
                            @php
                                $params = request()->all();
                                $params['sort'] = 'amount-asc';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-green-600/20 to-green-700/20 text-green-400 hover:from-green-600 hover:to-green-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-green-700/50 text-center">
                                <i class="fas fa-arrow-down mr-1"></i>Lowest
                            </a>
                            
                            {{-- Date Sorting --}}
                            @php
                                $params = request()->all();
                                $params['sort'] = 'date-desc';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-blue-600/20 to-blue-700/20 text-blue-400 hover:from-blue-600 hover:to-blue-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-blue-700/50 text-center">
                                <i class="fas fa-calendar mr-1"></i>Newest
                            </a>
                            
                            @php
                                $params = request()->all();
                                $params['sort'] = 'date-asc';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-purple-600/20 to-purple-700/20 text-purple-400 hover:from-purple-600 hover:to-purple-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-purple-700/50 text-center">
                                <i class="fas fa-calendar mr-1"></i>Oldest
                            </a>
                            
                            {{-- Type Filters --}}
                            @php
                                $params = request()->all();
                                $params['type'] = 'credit';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-green-600/20 to-green-700/20 text-green-400 hover:from-green-600 hover:to-green-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-green-700/50 text-center">
                                <i class="fas fa-plus mr-1"></i>Credit Only
                            </a>
                            
                            @php
                                $params = request()->all();
                                $params['type'] = 'debit';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-red-600/20 to-red-700/20 text-red-400 hover:from-red-600 hover:to-red-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50 text-center">
                                <i class="fas fa-minus mr-1"></i>Debit Only
                            </a>
                            
                            {{-- Amount Range --}}
                            @php
                                $params = request()->all();
                                $params['amount_range'] = 'large';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-indigo-600/20 to-indigo-700/20 text-indigo-400 hover:from-indigo-600 hover:to-indigo-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-indigo-700/50 text-center">
                                <i class="fas fa-fire mr-1"></i>Large (>1M)
                            </a>
                            
                            @php
                                $params = request()->all();
                                $params['amount_range'] = 'medium';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-cyan-600/20 to-cyan-700/20 text-cyan-400 hover:from-cyan-600 hover:to-cyan-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-cyan-700/50 text-center">
                                <i class="fas fa-dollar-sign mr-1"></i>Medium (100K-1M)
                            </a>
                            
                            @php
                                $params = request()->all();
                                $params['amount_range'] = 'small';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-teal-600/20 to-teal-700/20 text-teal-400 hover:from-teal-600 hover:to-teal-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-teal-700/50 text-center">
                                <i class="fas fa-coins mr-1"></i>Small (<100K)
                            </a>
                            
                            {{-- Special Filters --}}
                            @php
                                $params = request()->all();
                                $params['special'] = 'round';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-yellow-600/20 to-yellow-700/20 text-yellow-400 hover:from-yellow-600 hover:to-yellow-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-yellow-700/50 text-center">
                                <i class="fas fa-circle mr-1"></i>Round Numbers
                            </a>
                            
                            @php
                                $params = request()->all();
                                $params['special'] = 'manual';
                            @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-pink-600/20 to-pink-700/20 text-pink-400 hover:from-pink-600 hover:to-pink-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-pink-700/50 text-center">
                                <i class="fas fa-hand-paper mr-1"></i>Manual Entry
                            </a>
                            
                            {{-- Clear Filters --}}
                            <a href="{{ route('bank-statements.show', $bankStatement) }}" 
                               class="bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50 text-center">
                                <i class="fas fa-times mr-1"></i>Clear All
                            </a>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    @if($transactions->count() > 0)
                        {{-- Bank Statement Style Table --}}
                        <table class="w-full text-xs">
                            <thead class="bg-slate-900/80 border-b-2 border-slate-600 sticky top-0">
                                <tr>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300 w-24">Date</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300">Description</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300 w-32">Category</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300 w-32">Account</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-300 w-28">Debit</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-300 w-28">Credit</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-300 w-32">Balance</th>
                                    <th class="px-2 py-2 text-center font-bold text-gray-300 w-16">Status</th>
                                    <th class="px-2 py-2 text-center font-bold text-gray-300 w-16">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/30">
                                @php $runningBalance = $bankStatement->opening_balance ?? 0; @endphp
                                @foreach($transactions as $transaction)
                                    @php
                                        // Calculate running balance
                                        if ($transaction->transaction_type === 'credit') {
                                            $runningBalance += $transaction->credit_amount;
                                        } else {
                                            $runningBalance -= $transaction->debit_amount;
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-800/40 transition {{ $transaction->is_verified ? 'bg-green-900/5' : '' }}">
                                        {{-- Date --}}
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <div class="font-medium text-white">{{ $transaction->transaction_date->format('d/m/Y') }}</div>
                                            @if($transaction->transaction_time)
                                                <div class="text-gray-500">{{ $transaction->transaction_time }}</div>
                                            @endif
                                        </td>
                                        
                                        {{-- Description --}}
                                        <td class="px-2 py-2">
                                            <div class="font-medium text-white max-w-md truncate" title="{{ $transaction->description }}">
                                                {{ $transaction->description }}
                                            </div>
                                            @if($transaction->reference_no)
                                                <div class="text-gray-500 text-xs">Ref: {{ $transaction->reference_no }}</div>
                                            @endif
                                        </td>
                                        
                                        {{-- Category --}}
                                        <td class="px-2 py-2">
                                            @if($transaction->subCategory)
                                                <div class="text-blue-400 font-medium">{{ $transaction->subCategory->name }}</div>
                                                @if($transaction->confidence_score > 0)
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold
                                                        {{ $transaction->confidence_score >= 80 ? 'bg-green-600/20 text-green-400' : 
                                                           ($transaction->confidence_score >= 50 ? 'bg-yellow-600/20 text-yellow-400' : 'bg-red-600/20 text-red-400') }}">
                                                        {{ $transaction->confidence_score }}%
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-orange-400 text-xs">Uncategorized</span>
                                            @endif
                                        </td>
                                        
                                        {{-- Account --}}
                                        <td class="px-2 py-2">
                                            @if($transaction->account)
                                                <div class="text-cyan-400 font-medium">{{ $transaction->account->code }}</div>
                                                <div class="text-gray-500 text-xs truncate max-w-[100px]" title="{{ $transaction->account->name }}">
                                                    {{ $transaction->account->name }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                        
                                        {{-- Debit --}}
                                        <td class="px-2 py-2 text-right whitespace-nowrap">
                                            @if($transaction->debit_amount > 0)
                                                <span class="font-bold text-red-400">
                                                    {{ number_format($transaction->debit_amount, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-gray-600">-</span>
                                            @endif
                                        </td>
                                        
                                        {{-- Credit --}}
                                        <td class="px-2 py-2 text-right whitespace-nowrap">
                                            @if($transaction->credit_amount > 0)
                                                <span class="font-bold text-green-400">
                                                    {{ number_format($transaction->credit_amount, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-gray-600">-</span>
                                            @endif
                                        </td>
                                        
                                        {{-- Balance --}}
                                        <td class="px-2 py-2 text-right whitespace-nowrap">
                                            <span class="font-bold {{ $runningBalance >= 0 ? 'text-blue-400' : 'text-red-400' }}">
                                                {{ number_format($runningBalance, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        
                                        {{-- Status --}}
                                        <td class="px-2 py-2">
                                            <div class="flex justify-center items-center gap-1">
                                                @if($transaction->is_verified)
                                                    <span class="px-1 py-0.5 bg-green-600/20 text-green-400 rounded" title="Verified">
                                                        <i class="fas fa-check text-xs"></i>
                                                    </span>
                                                @endif
                                                @if($transaction->is_manual_category || $transaction->is_manual_account)
                                                    <span class="px-1 py-0.5 bg-blue-600/20 text-blue-400 rounded" title="Manual">
                                                        <i class="fas fa-hand-paper text-xs"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        {{-- Action --}}
                                        <td class="px-2 py-2 text-center">
                                            <a href="{{ route('transactions.show', $transaction) }}" 
                                               class="inline-flex items-center justify-center w-7 h-7 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded transition">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            
                            {{-- Footer Totals --}}
                            <tfoot class="bg-slate-900/80 border-t-2 border-slate-600 font-bold">
                                <tr>
                                    <td colspan="4" class="px-2 py-2 text-right text-gray-300">
                                        Page Total:
                                    </td>
                                    <td class="px-2 py-2 text-right text-red-400 whitespace-nowrap">
                                        {{ number_format($transactions->sum('debit_amount'), 0, ',', '.') }}
                                    </td>
                                    <td class="px-2 py-2 text-right text-green-400 whitespace-nowrap">
                                        {{ number_format($transactions->sum('credit_amount'), 0, ',', '.') }}
                                    </td>
                                    <td class="px-2 py-2 text-right text-blue-400 whitespace-nowrap">
                                        {{ number_format($runningBalance, 0, ',', '.') }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-4xl mb-3"></i>
                            <p class="text-gray-400">No transactions found</p>
                            <p class="text-gray-500 text-sm mt-2">Try adjusting your filters</p>
                        </div>
                    @endif
                </div>

                {{-- Pagination --}}
                @if($transactions->hasPages())
                    <div class="p-3 border-t border-slate-700 bg-slate-900/30">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>