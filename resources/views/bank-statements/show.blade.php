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
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
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

            {{-- Header Info Card --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 border border-slate-700 shadow-xl">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
                    <div class="flex items-center space-x-3">
                        @if($bankStatement->bank->logo)
                            <div class="w-12 h-12 bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                <img src="{{ asset('storage/' . $bankStatement->bank->logo) }}" 
                                     alt="{{ $bankStatement->bank->name }}" 
                                     class="w-10 h-10 object-contain">
                            </div>
                        @else
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-university text-white text-xl"></i>
                            </div>
                        @endif
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xl font-bold text-white truncate">{{ $bankStatement->bank->name }}</h3>
                            <p class="text-gray-400 text-sm truncate">{{ $bankStatement->original_filename }}</p>
                            @if($bankStatement->account_holder_name)
                                <p class="text-gray-500 text-xs mt-1">
                                    <i class="fas fa-user mr-1"></i>{{ $bankStatement->account_holder_name }}
                                </p>
                            @endif
                            
                            {{-- Company Info (Super Admin Only) --}}
                            @if(auth()->user()->isSuperAdmin() && $bankStatement->company)
                                <div class="mt-2 flex flex-wrap items-center gap-2">
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
                    
                    <div class="flex flex-wrap gap-2 justify-end">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold whitespace-nowrap
                            {{ $bankStatement->ocr_status === 'completed' ? 'bg-green-600/20 text-green-400 border border-green-600' : '' }}
                            {{ $bankStatement->ocr_status === 'processing' ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-600' : '' }}
                            {{ $bankStatement->ocr_status === 'failed' ? 'bg-red-600/20 text-red-400 border border-red-600' : '' }}
                            {{ $bankStatement->ocr_status === 'pending' ? 'bg-blue-600/20 text-blue-400 border border-blue-600' : '' }}">
                            <i class="fas fa-circle mr-1"></i>OCR: {{ ucfirst($bankStatement->ocr_status) }}
                        </span>
                        @if($bankStatement->is_reconciled)
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-purple-600/20 text-purple-400 border border-purple-600 whitespace-nowrap">
                                <i class="fas fa-check-double mr-1"></i>Reconciled
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Statement Details Grid --}}
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

            {{-- ✅ ENHANCED: Statistics Cards with Category + Account Info --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-3">
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

            {{-- ✅ NEW: Account Matching Status Section --}}
            @if(isset($accountMatchingStatus))
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-4 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-white flex items-center">
                        <i class="fas fa-book mr-2 text-cyan-400"></i>Account Matching Status
                    </h4>
                    
                    {{-- Status Badge --}}
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold
                        {{ $accountMatchingStatus['matching_status'] === 'completed' ? 'bg-green-600/20 text-green-400 border border-green-600' : '' }}
                        {{ $accountMatchingStatus['matching_status'] === 'processing' ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-600' : '' }}
                        {{ $accountMatchingStatus['matching_status'] === 'failed' ? 'bg-red-600/20 text-red-400 border border-red-600' : '' }}
                        {{ $accountMatchingStatus['matching_status'] === 'pending' ? 'bg-blue-600/20 text-blue-400 border border-blue-600' : '' }}">
                        <i class="fas fa-circle mr-1 text-xs"></i>{{ ucfirst($accountMatchingStatus['matching_status']) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2">
                    {{-- With Account --}}
                    <div class="bg-cyan-600/10 border border-cyan-600/50 rounded-lg p-2">
                        <p class="text-xs text-cyan-400 mb-0.5">With Account</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['with_account'] }}</p>
                        <p class="text-xs text-cyan-300">{{ $accountMatchingStatus['match_percentage'] }}%</p>
                    </div>

                    {{-- Without Account --}}
                    <div class="bg-gray-600/10 border border-gray-600/50 rounded-lg p-2">
                        <p class="text-xs text-gray-400 mb-0.5">Without Account</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['without_account'] }}</p>
                    </div>

                    {{-- Auto Matched --}}
                    <div class="bg-emerald-600/10 border border-emerald-600/50 rounded-lg p-2">
                        <p class="text-xs text-emerald-400 mb-0.5">Auto Matched</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['auto_account_matched'] }}</p>
                        <div class="flex items-center gap-1 mt-0.5">
                            <i class="fas fa-robot text-xs text-emerald-400"></i>
                            <span class="text-xs text-emerald-300">{{ $statistics['with_account'] > 0 ? round(($statistics['auto_account_matched'] / $statistics['with_account']) * 100, 1) : 0 }}%</span>
                        </div>
                    </div>

                    {{-- Manual Assigned --}}
                    <div class="bg-blue-600/10 border border-blue-600/50 rounded-lg p-2">
                        <p class="text-xs text-blue-400 mb-0.5">Manual</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['manual_account_assigned'] }}</p>
                        <div class="flex items-center gap-1 mt-0.5">
                            <i class="fas fa-hand-paper text-xs text-blue-400"></i>
                            <span class="text-xs text-blue-300">{{ $statistics['with_account'] > 0 ? round(($statistics['manual_account_assigned'] / $statistics['with_account']) * 100, 1) : 0 }}%</span>
                        </div>
                    </div>

                    {{-- High Confidence Account --}}
                    <div class="bg-teal-600/10 border border-teal-600/50 rounded-lg p-2">
                        <p class="text-xs text-teal-400 mb-0.5">High Conf</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['high_account_confidence'] }}</p>
                        <p class="text-xs text-teal-300">≥80%</p>
                    </div>

                    {{-- Medium Confidence Account --}}
                    <div class="bg-yellow-600/10 border border-yellow-600/50 rounded-lg p-2">
                        <p class="text-xs text-yellow-400 mb-0.5">Med Conf</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['medium_account_confidence'] }}</p>
                        <p class="text-xs text-yellow-300">50-79%</p>
                    </div>

                    {{-- Low Confidence Account --}}
                    <div class="bg-orange-600/10 border border-orange-600/50 rounded-lg p-2">
                        <p class="text-xs text-orange-400 mb-0.5">Low Conf</p>
                        <p class="text-lg font-bold text-white">{{ $statistics['low_account_confidence'] }}</p>
                        <p class="text-xs text-orange-300"><50%</p>
                    </div>

                    {{-- Total Processed --}}
                    <div class="bg-indigo-600/10 border border-indigo-600/50 rounded-lg p-2">
                        <p class="text-xs text-indigo-400 mb-0.5">Processed</p>
                        <p class="text-lg font-bold text-white">{{ $accountMatchingStatus['total_processed'] }}</p>
                    </div>
                </div>

                {{-- Processing Info --}}
                @if($accountMatchingStatus['matching_status'] === 'completed' && $accountMatchingStatus['matching_completed_at'])
                    <div class="mt-2 pt-2 border-t border-slate-700">
                        <p class="text-xs text-gray-400">
                            <i class="fas fa-clock mr-1"></i>
                            Completed {{ $accountMatchingStatus['matching_completed_at']->diffForHumans() }}
                            @if($accountMatchingStatus['matching_started_at'] && $accountMatchingStatus['matching_completed_at'])
                                <span class="text-gray-500">
                                    ({{ $accountMatchingStatus['matching_started_at']->diffInSeconds($accountMatchingStatus['matching_completed_at']) }}s)
                                </span>
                            @endif
                        </p>
                    </div>
                @endif

                @if($accountMatchingStatus['matching_status'] === 'processing')
                    <div class="mt-2 pt-2 border-t border-slate-700">
                        <div class="flex items-center text-yellow-400 text-xs">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Processing account matching...
                        </div>
                    </div>
                @endif

                @if($accountMatchingStatus['matching_status'] === 'failed' && $accountMatchingStatus['matching_notes'])
                    <div class="mt-2 pt-2 border-t border-slate-700">
                        <div class="bg-red-600/20 border border-red-500/30 rounded-lg p-2">
                            <p class="text-xs text-red-400 font-semibold mb-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Matching Error:
                            </p>
                            <p class="text-xs text-red-300">{{ $accountMatchingStatus['matching_notes'] }}</p>
                        </div>
                    </div>
                @endif
            </div>
            @endif

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

            {{-- ✅ ENHANCED: Action Buttons with Enhanced Confirm --}}
            @if($transactions->total() > 0)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-4 border border-slate-700 shadow-xl">
                    <div class="flex flex-wrap items-center gap-2">
                        
                        {{-- Match Categories --}}
                        <form action="{{ route('bank-statements.match-transactions', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                <i class="fas fa-tags mr-2"></i>Match Categories
                            </button>
                        </form>

                        {{-- Match Accounts --}}
                        <form action="{{ route('bank-statements.match-accounts', $bankStatement) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                <i class="fas fa-book mr-2"></i>Match Accounts
                            </button>
                        </form>

                        {{-- ✅ Rematch All - Enhanced Confirm --}}
                        <form action="{{ route('bank-statements.rematch-all', $bankStatement) }}" 
                              method="POST" 
                              class="inline"
                              data-confirm-rematch>
                            @csrf
                            <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                <i class="fas fa-redo mr-2"></i>Rematch All
                            </button>
                        </form>

                        {{-- ✅ Verify All Matched - Enhanced Confirm --}}
                        @if($statistics['categorized'] > 0)
                            <form action="{{ route('bank-statements.verify-all-matched', $bankStatement) }}" 
                                  method="POST" 
                                  class="inline"
                                  data-confirm-verify-matched
                                  data-count="{{ $statistics['categorized'] }}">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                    <i class="fas fa-shield-check mr-2"></i>Verify Matched ({{ $statistics['categorized'] }})
                                </button>
                            </form>
                        @endif

                        {{-- ✅ Verify High Confidence - Enhanced Confirm --}}
                        @if($statistics['high_confidence'] > 0)
                            <form action="{{ route('bank-statements.verify-high-confidence', $bankStatement) }}" 
                                  method="POST" 
                                  class="inline"
                                  data-confirm-verify-confidence
                                  data-count="{{ $statistics['high_confidence'] }}">
                                @csrf
                                <input type="hidden" name="threshold" value="80">
                                <button type="submit" class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                    <i class="fas fa-check-double mr-2"></i>Verify High Conf. ({{ $statistics['high_confidence'] }})
                                </button>
                            </form>
                        @endif

                        {{-- Reconcile --}}
                        @if(!$bankStatement->is_reconciled && $statistics['verified'] === $statistics['total'] && $statistics['total'] > 0)
                            <form action="{{ route('bank-statements.reconcile', $bankStatement) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                    <i class="fas fa-check-double mr-2"></i>Reconcile
                                </button>
                            </form>
                        
                        {{-- ✅ Unreconcile - Enhanced Confirm --}}
                        @elseif($bankStatement->is_reconciled)
                            <form action="{{ route('bank-statements.unreconcile', $bankStatement) }}" 
                                  method="POST" 
                                  class="inline"
                                  data-confirm-unreconcile>
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                    <i class="fas fa-times mr-2"></i>Unreconcile
                                </button>
                            </form>
                        @endif

                        {{-- Download --}}
                        <a href="{{ route('bank-statements.download', $bankStatement) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>

                        {{-- Retry OCR --}}
                        @if($bankStatement->ocr_status === 'failed')
                            <form action="{{ route('bank-statements.reprocess', $bankStatement) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center shadow-lg hover:shadow-xl">
                                    <i class="fas fa-sync-alt mr-2"></i>Retry OCR
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Transactions List --}}
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

                    {{-- ✅ ENHANCED: Quick Filters with Account Options --}}
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
                            
                            {{-- ✅ Account Filters --}}
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'with-account']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1
                               {{ request('filter') === 'with-account' ? 'bg-cyan-700 text-white border border-cyan-600' : 'bg-cyan-700/20 text-cyan-400 border border-cyan-700/50 hover:bg-cyan-700 hover:text-white' }}">
                                <i class="fas fa-book"></i>
                                With Account ({{ $statistics['with_account'] }})
                            </a>
                            
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'without-account']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1
                               {{ request('filter') === 'without-account' ? 'bg-gray-700 text-white border border-gray-600' : 'bg-gray-700/20 text-gray-400 border border-gray-700/50 hover:bg-gray-700 hover:text-white' }}">
                                <i class="fas fa-minus-circle"></i>
                                No Account ({{ $statistics['without_account'] }})
                            </a>
                            
                            <a href="{{ route('bank-statements.show', [$bankStatement, 'filter' => 'high-confidence']) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('filter') === 'high-confidence' ? 'bg-slate-700 text-white' : 'bg-slate-700/50 text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                                High Conf. ({{ $statistics['high_confidence'] }})
                            </a>
                        </div>
                    </div>

                    {{-- ✅ ENHANCED: Advanced Filters --}}
                    <div x-data="{ showAdvanced: false }" class="mt-3">
                        <button @click="showAdvanced = !showAdvanced" class="text-xs text-gray-400 hover:text-white font-semibold mb-2 flex items-center">
                            <i :class="showAdvanced ? 'fas fa-chevron-down' : 'fas fa-chevron-right'" class="mr-1"></i>
                            Advanced Filters
                        </button>
                        
                        <div x-show="showAdvanced" x-transition class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                            {{-- Amount Sorting --}}
                            @php $params = request()->all(); $params['sort'] = 'amount-desc'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-red-600/20 to-red-700/20 text-red-400 hover:from-red-600 hover:to-red-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50 text-center">
                                <i class="fas fa-arrow-up mr-1"></i>Highest
                            </a>
                            
                            @php $params = request()->all(); $params['sort'] = 'amount-asc'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-green-600/20 to-green-700/20 text-green-400 hover:from-green-600 hover:to-green-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-green-700/50 text-center">
                                <i class="fas fa-arrow-down mr-1"></i>Lowest
                            </a>
                            
                            {{-- Type Filters --}}
                            @php $params = request()->all(); $params['type'] = 'credit'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-green-600/20 to-green-700/20 text-green-400 hover:from-green-600 hover:to-green-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-green-700/50 text-center">
                                <i class="fas fa-plus mr-1"></i>Credit Only
                            </a>
                            
                            @php $params = request()->all(); $params['type'] = 'debit'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-red-600/20 to-red-700/20 text-red-400 hover:from-red-600 hover:to-red-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-red-700/50 text-center">
                                <i class="fas fa-minus mr-1"></i>Debit Only
                            </a>
                            
                            {{-- ✅ Account Confidence Filters --}}
                            @php $params = request()->all(); $params['filter'] = 'auto-account'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-emerald-600/20 to-emerald-700/20 text-emerald-400 hover:from-emerald-600 hover:to-emerald-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-emerald-700/50 text-center">
                                <i class="fas fa-robot mr-1"></i>Auto Acct ({{ $statistics['auto_account_matched'] }})
                            </a>
                            
                            @php $params = request()->all(); $params['filter'] = 'high-account-confidence'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-teal-600/20 to-teal-700/20 text-teal-400 hover:from-teal-600 hover:to-teal-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-teal-700/50 text-center">
                                <i class="fas fa-shield-alt mr-1"></i>High Acct ({{ $statistics['high_account_confidence'] }})
                            </a>
                            
                            {{-- Amount Range --}}
                            @php $params = request()->all(); $params['amount_range'] = 'large'; @endphp
                            <a href="{{ route('bank-statements.show', $bankStatement) }}?{{ http_build_query($params) }}" 
                               class="bg-gradient-to-r from-indigo-600/20 to-indigo-700/20 text-indigo-400 hover:from-indigo-600 hover:to-indigo-700 hover:text-white px-3 py-2 rounded-lg text-xs font-semibold transition border border-indigo-700/50 text-center">
                                <i class="fas fa-fire mr-1"></i>Large (>1M)
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
                        <table class="w-full text-xs">
                            <thead class="bg-slate-900/80 border-b-2 border-slate-600 sticky top-0 z-10">
                                <tr>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300 w-24">Date</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300">Description</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300 w-32">Category</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-300 w-40">Account</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-300 w-28">Debit</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-300 w-28">Credit</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-300 w-32">Balance</th>
                                    <th class="px-2 py-2 text-center font-bold text-gray-300 w-20">Status</th>
                                    <th class="px-2 py-2 text-center font-bold text-gray-300 w-16">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/30">
                                @php $runningBalance = $bankStatement->opening_balance ?? 0; @endphp
                                @foreach($transactions as $transaction)
                                    @php
                                        if ($transaction->transaction_type === 'credit') {
                                            $runningBalance += $transaction->credit_amount;
                                        } else {
                                            $runningBalance -= $transaction->debit_amount;
                                        }
                                    @endphp
                                    {{-- ✅ ENHANCED: Transaction Row with Account Info --}}
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
                                        
                                        {{-- ✅ ENHANCED: Category with Badges --}}
                                        <td class="px-2 py-2">
                                            @if($transaction->subCategory)
                                                <div class="text-blue-400 font-medium text-xs">{{ $transaction->subCategory->name }}</div>
                                                @if($transaction->confidence_score > 0)
                                                    <div class="flex items-center gap-1 mt-0.5">
                                                        <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold
                                                            {{ $transaction->confidence_score >= 80 ? 'bg-green-600/20 text-green-400' : 
                                                               ($transaction->confidence_score >= 50 ? 'bg-yellow-600/20 text-yellow-400' : 'bg-red-600/20 text-red-400') }}">
                                                            {{ $transaction->confidence_score }}%
                                                        </span>
                                                        @if($transaction->is_manual_category)
                                                            <span class="inline-block px-1 py-0.5 bg-blue-600/20 text-blue-400 rounded text-xs" title="Manual Category">
                                                                <i class="fas fa-hand-paper"></i>
                                                            </span>
                                                        @else
                                                            <span class="inline-block px-1 py-0.5 bg-emerald-600/20 text-emerald-400 rounded text-xs" title="Auto Matched">
                                                                <i class="fas fa-robot"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-orange-400 text-xs">Uncategorized</span>
                                            @endif
                                        </td>
                                        
                                        {{-- ✅ ENHANCED: Account Column with Full Details --}}
                                        <td class="px-2 py-2">
                                            @if($transaction->account)
                                                <div class="space-y-0.5">
                                                    {{-- Account Code & Badge --}}
                                                    <div class="flex items-start gap-1">
                                                        <span class="font-mono text-cyan-400 font-bold text-xs">{{ $transaction->account->code }}</span>
                                                        @if($transaction->is_manual_account)
                                                            <span class="inline-block px-1 py-0.5 bg-blue-600/20 text-blue-400 rounded text-xs" title="Manual Account">
                                                                <i class="fas fa-hand-paper"></i>
                                                            </span>
                                                        @else
                                                            <span class="inline-block px-1 py-0.5 bg-emerald-600/20 text-emerald-400 rounded text-xs" title="Auto Matched">
                                                                <i class="fas fa-robot"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- Account Name --}}
                                                    <div class="text-gray-400 text-xs truncate max-w-[140px]" title="{{ $transaction->account->name }}">
                                                        {{ $transaction->account->name }}
                                                    </div>
                                                    
                                                    {{-- Account Confidence Score --}}
                                                    @if($transaction->account_confidence_score > 0)
                                                        <div class="flex items-center gap-1">
                                                            <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold
                                                                {{ $transaction->account_confidence_score >= 80 ? 'bg-teal-600/20 text-teal-400 border border-teal-600/50' : 
                                                                   ($transaction->account_confidence_score >= 50 ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-600/50' : 
                                                                   'bg-orange-600/20 text-orange-400 border border-orange-600/50') }}">
                                                                <i class="fas fa-shield-alt mr-0.5"></i>{{ $transaction->account_confidence_score }}%
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-center">
                                                    <span class="text-gray-500 text-xs">No Account</span>
                                                    <div class="mt-0.5">
                                                        <i class="fas fa-minus-circle text-gray-600 text-xs"></i>
                                                    </div>
                                                </div>
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
                                        
                                        {{-- ✅ ENHANCED: Status Column --}}
                                        <td class="px-2 py-2">
                                            <div class="flex justify-center items-center gap-1 flex-wrap">
                                                @if($transaction->is_verified)
                                                    <span class="px-1.5 py-0.5 bg-green-600/20 text-green-400 rounded border border-green-600/50" title="Verified">
                                                        <i class="fas fa-check text-xs"></i>
                                                    </span>
                                                @endif
                                                
                                                @if($transaction->is_manual_category)
                                                    <span class="px-1.5 py-0.5 bg-blue-600/20 text-blue-400 rounded border border-blue-600/50" title="Manual Category">
                                                        <i class="fas fa-tag text-xs"></i>
                                                    </span>
                                                @endif
                                                
                                                @if($transaction->is_manual_account)
                                                    <span class="px-1.5 py-0.5 bg-cyan-600/20 text-cyan-400 rounded border border-cyan-600/50" title="Manual Account">
                                                        <i class="fas fa-book text-xs"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        {{-- Action --}}
                                        <td class="px-2 py-2 text-center">
                                            <a href="{{ route('transactions.show', $transaction) }}" 
                                               class="inline-flex items-center justify-center w-7 h-7 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded transition"
                                               title="View Details">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            
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

                @if($transactions->hasPages())
                    <div class="p-3 border-t border-slate-700 bg-slate-900/30">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('[class*="bg-green-600"], [class*="bg-red-600"]');
            flashMessages.forEach(msg => {
                if (msg.parentElement.classList.contains('space-y-6')) {
                    msg.style.transition = 'opacity 0.5s';
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 500);
                }
            });
        }, 5000);

        // ============================================
        // ✅ Enhanced Confirm Dialog Component
        // ============================================
        
        document.addEventListener('alpine:init', () => {
            Alpine.data('confirmDialog', () => ({
                show: false,
                title: '',
                message: '',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                type: 'warning',
                confirmCallback: null,
                
                open(options) {
                    this.title = options.title || 'Are you sure?';
                    this.message = options.message || '';
                    this.confirmText = options.confirmText || 'Confirm';
                    this.cancelText = options.cancelText || 'Cancel';
                    this.type = options.type || 'warning';
                    this.confirmCallback = options.onConfirm || null;
                    this.show = true;
                },
                
                close() {
                    this.show = false;
                },
                
                confirm() {
                    if (this.confirmCallback) {
                        this.confirmCallback();
                    }
                    this.close();
                },
                
                getIconClass() {
                    const icons = {
                        warning: 'fas fa-exclamation-triangle text-yellow-400',
                        danger: 'fas fa-exclamation-circle text-red-400',
                        success: 'fas fa-check-circle text-green-400',
                        info: 'fas fa-info-circle text-blue-400'
                    };
                    return icons[this.type] || icons.warning;
                },
                
                getConfirmButtonClass() {
                    const classes = {
                        warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
                        danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
                        success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
                        info: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                    };
                    return classes[this.type] || classes.warning;
                }
            }));
        });

        // Global confirm function
        window.showConfirm = function(options) {
            const event = new CustomEvent('open-confirm', { detail: options });
            window.dispatchEvent(event);
        };

        // Setup form confirmations
        function setupFormConfirmations() {
            // Rematch All
            document.querySelectorAll('[data-confirm-rematch]')?.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showConfirm({
                        title: 'Reset All Categorization?',
                        message: 'This will clear all existing category and account matches. All transactions will be rematched using current keywords. This action cannot be undone.',
                        confirmText: 'Yes, Rematch All',
                        cancelText: 'Cancel',
                        type: 'danger',
                        onConfirm: () => this.submit()
                    });
                });
            });
            
            // Verify All Matched
            document.querySelectorAll('[data-confirm-verify-matched]')?.forEach(form => {
                const count = form.dataset.count || '0';
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showConfirm({
                        title: 'Verify All Categorized?',
                        message: `This will mark ${count} categorized transactions as verified. You can unverify them later if needed.`,
                        confirmText: `Verify ${count} Transactions`,
                        cancelText: 'Cancel',
                        type: 'success',
                        onConfirm: () => this.submit()
                    });
                });
            });
            
            // Verify High Confidence
            document.querySelectorAll('[data-confirm-verify-confidence]')?.forEach(form => {
                const count = form.dataset.count || '0';
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showConfirm({
                        title: 'Verify High Confidence?',
                        message: `This will mark ${count} transactions with confidence ≥80% as verified. These are likely to be accurate matches.`,
                        confirmText: `Verify ${count} Transactions`,
                        cancelText: 'Cancel',
                        type: 'success',
                        onConfirm: () => this.submit()
                    });
                });
            });
            
            // Unreconcile
            document.querySelectorAll('[data-confirm-unreconcile]')?.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showConfirm({
                        title: 'Unreconcile Statement?',
                        message: 'This will mark the statement as not reconciled. You can reconcile it again later.',
                        confirmText: 'Yes, Unreconcile',
                        cancelText: 'Cancel',
                        type: 'warning',
                        onConfirm: () => this.submit()
                    });
                });
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', setupFormConfirmations);
    </script>

    {{-- ✅ Confirm Dialog Modal Component --}}
    <div x-data="confirmDialog()" 
         @open-confirm.window="open($event.detail)"
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        {{-- Backdrop --}}
        <div x-show="show" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="close()"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity">
        </div>

        {{-- Dialog --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.away="close()"
                 class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 shadow-2xl transition-all">
                
                {{-- Content --}}
                <div class="p-6">
                    {{-- Icon --}}
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full mb-4"
                         :class="{
                             'bg-yellow-600/20': type === 'warning',
                             'bg-red-600/20': type === 'danger',
                             'bg-green-600/20': type === 'success',
                             'bg-blue-600/20': type === 'info'
                         }">
                        <i :class="getIconClass()" class="text-4xl"></i>
                    </div>

                    {{-- Title --}}
                    <h3 class="text-xl font-bold text-white text-center mb-2" x-text="title"></h3>

                    {{-- Message --}}
                    <p class="text-sm text-gray-400 text-center mb-6" x-text="message"></p>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        {{-- Cancel Button --}}
                        <button @click="close()"
                                type="button"
                                class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                            <span x-text="cancelText"></span>
                        </button>

                        {{-- Confirm Button --}}
                        <button @click="confirm()"
                                type="button"
                                :class="getConfirmButtonClass()"
                                class="flex-1 px-4 py-3 text-white font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900">
                            <span x-text="confirmText"></span>
                        </button>
                    </div>
                </div>

                {{-- Close button --}}
                <button @click="close()"
                        type="button"
                        class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
    </div>
    @endpush
</x-app-layout