<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                    {{ __('Validate Transactions') }}
                </h2>
                <p class="text-sm text-gray-400 mt-1">{{ $bankStatement->bank->name }} - {{ $bankStatement->original_filename }}</p>
            </div>
            <a href="{{ route('bank-statements.show', $bankStatement) }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            {{-- Progress Summary Card --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-tasks mr-2"></i>Validation Progress
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Review and approve transaction categorizations</p>
                    </div>
                    
                    {{-- Approve All Button --}}
                    @if($stats['pending'] > 0)
                        <form action="{{ route('bank-statements.verify-all-matched', $bankStatement) }}" method="POST" class="inline" id="approveAllForm">
                            @csrf
                            <button type="button" onclick="confirmApproveAll()" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg transition-all flex items-center gap-2 animate-pulse hover:animate-none">
                                <i class="fas fa-check-double"></i>
                                <span>Approve All ({{ $stats['high_confidence'] }})</span>
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Statistics Cards --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Total</p>
                                <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
                            </div>
                            <i class="fas fa-list text-2xl text-gray-600"></i>
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 border border-orange-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Pending</p>
                                <p class="text-2xl font-bold text-orange-400">{{ $stats['pending'] }}</p>
                            </div>
                            <i class="fas fa-clock text-2xl text-orange-600/30"></i>
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 border border-green-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Verified</p>
                                <p class="text-2xl font-bold text-green-400">{{ $stats['verified'] }}</p>
                                <p class="text-xs text-green-200">{{ $stats['progress'] }}%</p>
                            </div>
                            <i class="fas fa-check-circle text-2xl text-green-600/30"></i>
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 border border-teal-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">High Conf.</p>
                                <p class="text-2xl font-bold text-teal-400">{{ $stats['high_confidence'] }}</p>
                                <p class="text-xs text-teal-200">≥80%</p>
                            </div>
                            <i class="fas fa-star text-2xl text-teal-600/30"></i>
                        </div>
                    </div>

                    <div class="bg-slate-900/50 rounded-lg p-4 border border-red-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">No Match</p>
                                <p class="text-2xl font-bold text-red-400">{{ $stats['no_match'] }}</p>
                            </div>
                            <i class="fas fa-times-circle text-2xl text-red-600/30"></i>
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-400 mb-2">
                        <span>Validation Progress</span>
                        <span>{{ $stats['verified'] }} / {{ $stats['total'] }} verified</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-emerald-600 h-3 rounded-full transition-all duration-500" 
                             style="width: {{ $stats['progress'] }}%">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs Navigation --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="border-b border-slate-700">
                    <div class="flex flex-wrap gap-2 p-4">
                        {{-- Tab 1: With Suggestions (Default) --}}
                        <button onclick="switchTab('with-suggestions')" 
                                id="tab-with-suggestions" 
                                class="tab-button active px-6 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-lightbulb mr-2"></i>
                            <span>With Suggestions</span>
                            <span class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs">
                                {{ $transactions->where('matched_keyword_id', '!=', null)->count() }}
                            </span>
                        </button>

                        {{-- Tab 2: No Suggestions --}}
                        <button onclick="switchTab('no-suggestions')" 
                                id="tab-no-suggestions" 
                                class="tab-button px-6 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span>No Suggestions</span>
                            <span class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs">
                                {{ $transactions->where('matched_keyword_id', null)->count() }}
                            </span>
                        </button>

                        {{-- Tab 3: Verified --}}
                        <button onclick="switchTab('verified')" 
                                id="tab-verified" 
                                class="tab-button px-6 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-check-double mr-2"></i>
                            <span>Verified</span>
                            <span class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs">
                                {{ $stats['verified'] }}
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Tab Content: With Suggestions --}}
                <div id="content-with-suggestions" class="tab-content p-6">
                    <div class="mb-4 bg-blue-900/20 border border-blue-700 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                            <div class="text-sm text-blue-200">
                                <p class="font-semibold mb-2">These transactions have AI-powered suggestions:</p>
                                <ul class="list-disc list-inside space-y-1 text-blue-300">
                                    <li>Review the suggested category for each transaction</li>
                                    <li>Click <strong class="text-green-400">✓ Approve</strong> if correct</li>
                                    <li>Click <strong class="text-red-400">✗ Reject</strong> to choose another category</li>
                                    <li>High confidence matches (≥80%) are more accurate</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @php
                            $withSuggestions = $transactions->filter(function($t) {
                                return $t->matched_keyword_id != null && !$t->is_verified;
                            });
                        @endphp

                        @forelse($withSuggestions as $transaction)
                        <div class="bg-slate-900/50 rounded-lg border border-slate-700 hover:border-slate-600 transition-all p-5" 
                             id="transaction-{{ $transaction->id }}">
                            <div class="flex flex-col lg:flex-row gap-4">
                                {{-- Left: Transaction Details --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Header: Date, Amount, Type --}}
                                    <div class="flex flex-wrap items-center gap-3 mb-3">
                                        <span class="text-xs text-gray-400 font-mono">
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                            @if($transaction->transaction_time)
                                                {{ \Carbon\Carbon::parse($transaction->transaction_time)->format('H:i') }}
                                            @endif
                                        </span>
                                        
                                        <span class="px-2 py-1 rounded text-xs font-semibold 
                                            {{ $transaction->transaction_type === 'debit' ? 'bg-red-600/20 text-red-400 border border-red-600' : 'bg-green-600/20 text-green-400 border border-green-600' }}">
                                            {{ strtoupper($transaction->transaction_type) }}
                                        </span>
                                        
                                        <span class="text-lg font-bold {{ $transaction->transaction_type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>

                                        {{-- Confidence Badge --}}
                                        @if($transaction->confidence_score > 0)
                                            <span class="px-3 py-1 rounded-lg text-xs font-bold
                                                {{ $transaction->confidence_score >= 80 ? 'bg-green-600/20 text-green-400 border border-green-600' : 
                                                   ($transaction->confidence_score >= 50 ? 'bg-yellow-600/20 text-yellow-400 border border-yellow-600' : 'bg-red-600/20 text-red-400 border border-red-600') }}">
                                                <i class="fas fa-chart-line mr-1"></i>{{ $transaction->confidence_score }}% Match
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Description --}}
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-400 mb-1">Description:</p>
                                        <p class="text-white font-medium">{{ $transaction->description }}</p>
                                        @if($transaction->reference_no)
                                            <p class="text-xs text-gray-500 mt-1">Ref: {{ $transaction->reference_no }}</p>
                                        @endif
                                    </div>

                                    {{-- Suggested Category (Primary Match) --}}
                                    @if($transaction->matchedKeyword)
                                        <div class="bg-slate-800/50 rounded-lg p-4 border border-blue-700/50">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-lightbulb text-yellow-400"></i>
                                                    <p class="text-sm font-semibold text-blue-400">AI Suggested Category:</p>
                                                </div>
                                                <span class="text-xs text-gray-400">
                                                    Keyword: <span class="text-white font-mono">{{ $transaction->matchedKeyword->keyword }}</span>
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2 text-sm">
                                                @if($transaction->type)
                                                    <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs">
                                                        {{ $transaction->type->name }}
                                                    </span>
                                                @endif
                                                <i class="fas fa-arrow-right text-gray-600 text-xs"></i>
                                                @if($transaction->category)
                                                    <span class="px-2 py-1 rounded text-xs" style="background-color: {{ $transaction->category->color }}20; color: {{ $transaction->category->color }};">
                                                        {{ $transaction->category->name }}
                                                    </span>
                                                @endif
                                                <i class="fas fa-arrow-right text-gray-600 text-xs"></i>
                                                @if($transaction->subCategory)
                                                    <span class="px-2 py-1 bg-cyan-600/20 text-cyan-400 rounded text-xs font-semibold">
                                                        {{ $transaction->subCategory->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Right: Action Buttons --}}
                                <div class="flex-shrink-0 flex flex-col gap-2 min-w-[250px]">
                                    <button type="button" 
                                            class="approve-btn px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-bold transition-all shadow-lg flex items-center justify-center gap-2"
                                            data-transaction-id="{{ $transaction->id }}"
                                            data-keyword-id="{{ $transaction->matched_keyword_id }}">
                                        <i class="fas fa-check-circle text-xl"></i>
                                        <span>Approve Suggestion</span>
                                    </button>
                                    
                                    <button type="button" 
                                            class="reject-btn px-6 py-4 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-lg font-bold transition-all shadow-lg flex items-center justify-center gap-2"
                                            data-transaction-id="{{ $transaction->id }}">
                                        <i class="fas fa-times-circle text-xl"></i>
                                        <span>Reject & Choose</span>
                                    </button>

                                    {{-- Manual Assignment Form (Hidden by default) --}}
                                    <div class="manual-assignment-form hidden mt-2 p-4 bg-slate-800 rounded-lg border border-slate-600">
                                        <p class="text-xs text-gray-400 mb-2 font-semibold">Choose correct category:</p>
                                        <select class="keyword-search-select w-full bg-slate-900 border border-slate-600 rounded-lg text-white"
                                                data-transaction-id="{{ $transaction->id }}"
                                                data-placeholder="Search keywords...">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <i class="fas fa-check-circle text-green-400 text-5xl mb-4"></i>
                            <p class="text-gray-300 text-lg font-semibold">All suggestions have been processed!</p>
                            <p class="text-gray-500 text-sm mt-2">Check the "Verified" tab to review approved transactions</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                {{-- Tab Content: No Suggestions --}}
                <div id="content-no-suggestions" class="tab-content hidden p-6">
                    <div class="mb-4 bg-orange-900/20 border border-orange-700 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-orange-400 mt-1 mr-3"></i>
                            <div class="text-sm text-orange-200">
                                <p class="font-semibold mb-2">These transactions have NO AI suggestions:</p>
                                <ul class="list-disc list-inside space-y-1 text-orange-300">
                                    <li>The AI couldn't find a matching category based on keywords</li>
                                    <li>You need to <strong>manually assign</strong> a category for each transaction</li>
                                    <li>Use the search box to find and assign the correct category</li>
                                    <li>This helps the AI learn and improve future suggestions</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @php
                            $noSuggestions = $transactions->filter(function($t) {
                                return $t->matched_keyword_id == null && !$t->is_verified;
                            });
                        @endphp

                        @forelse($noSuggestions as $transaction)
                        <div class="bg-slate-900/50 rounded-lg border border-orange-700/50 hover:border-orange-600 transition-all p-5" 
                             id="transaction-{{ $transaction->id }}">
                            <div class="flex flex-col lg:flex-row gap-4">
                                {{-- Left: Transaction Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-3 mb-3">
                                        <span class="text-xs text-gray-400 font-mono">
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                            @if($transaction->transaction_time)
                                                {{ \Carbon\Carbon::parse($transaction->transaction_time)->format('H:i') }}
                                            @endif
                                        </span>
                                        
                                        <span class="px-2 py-1 rounded text-xs font-semibold 
                                            {{ $transaction->transaction_type === 'debit' ? 'bg-red-600/20 text-red-400 border border-red-600' : 'bg-green-600/20 text-green-400 border border-green-600' }}">
                                            {{ strtoupper($transaction->transaction_type) }}
                                        </span>
                                        
                                        <span class="text-lg font-bold {{ $transaction->transaction_type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>

                                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-orange-600/20 text-orange-400 border border-orange-600">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>No Match Found
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <p class="text-sm text-gray-400 mb-1">Description:</p>
                                        <p class="text-white font-medium">{{ $transaction->description }}</p>
                                        @if($transaction->reference_no)
                                            <p class="text-xs text-gray-500 mt-1">Ref: {{ $transaction->reference_no }}</p>
                                        @endif
                                    </div>

                                    <div class="bg-orange-900/20 border border-orange-700/50 rounded-lg p-3">
                                        <div class="flex items-center gap-2 text-sm text-orange-300">
                                            <i class="fas fa-robot"></i>
                                            <span>AI couldn't find a matching keyword pattern for this transaction</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right: Manual Assignment --}}
                                <div class="flex-shrink-0 min-w-[300px]">
                                    <div class="bg-slate-800 rounded-lg p-4 border border-slate-600">
                                        <p class="text-sm text-gray-300 font-semibold mb-3 flex items-center gap-2">
                                            <i class="fas fa-hand-pointer text-blue-400"></i>
                                            Manual Category Assignment Required:
                                        </p>
                                        <select class="keyword-search-select w-full bg-slate-900 border border-slate-600 rounded-lg text-white"
                                                data-transaction-id="{{ $transaction->id }}"
                                                data-placeholder="Search and select category...">
                                            <option></option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Search by keyword, category, or sub-category name
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <i class="fas fa-check-circle text-green-400 text-5xl mb-4"></i>
                            <p class="text-gray-300 text-lg font-semibold">All transactions have suggestions!</p>
                            <p class="text-gray-500 text-sm mt-2">Great! The AI found matches for all transactions</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                {{-- Tab Content: Verified --}}
                <div id="content-verified" class="tab-content hidden p-6">
                    <div class="mb-4 bg-green-900/20 border border-green-700 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
                            <div class="text-sm text-green-200">
                                <p class="font-semibold mb-1">These transactions have been verified and approved</p>
                                <p class="text-green-300">They are ready for reconciliation and accounting</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @php
                            $verified = $transactions->where('is_verified', true);
                        @endphp

                        @forelse($verified as $transaction)
                        <div class="bg-green-900/10 rounded-lg border border-green-700/50 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3 mb-2">
                                        <span class="text-xs text-gray-400 font-mono">
                                            {{ $transaction->transaction_date->format('d M Y H:i') }}
                                        </span>
                                        
                                        <span class="px-2 py-1 rounded text-xs font-semibold 
                                            {{ $transaction->transaction_type === 'debit' ? 'bg-red-600/20 text-red-400' : 'bg-green-600/20 text-green-400' }}">
                                            {{ strtoupper($transaction->transaction_type) }}
                                        </span>
                                        
                                        <span class="text-base font-bold {{ $transaction->transaction_type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    </div>

                                    <p class="text-white font-medium mb-2">{{ $transaction->description }}</p>

                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        @if($transaction->type)
                                            <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded">
                                                {{ $transaction->type->name }}
                                            </span>
                                        @endif
                                        @if($transaction->category)
                                            <span class="px-2 py-1 rounded" style="background-color: {{ $transaction->category->color }}20; color: {{ $transaction->category->color }};">
                                                {{ $transaction->category->name }}
                                            </span>
                                        @endif
                                        @if($transaction->subCategory)
                                            <span class="px-2 py-1 bg-cyan-600/20 text-cyan-400 rounded">
                                                {{ $transaction->subCategory->name }}
                                            </span>
                                        @endif

                                        @if($transaction->is_manual_category)
                                            <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded">
                                                <i class="fas fa-hand-pointer mr-1"></i>Manual
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex-shrink-0 text-right">
                                    <div class="px-4 py-2 bg-green-600/20 border border-green-600 rounded-lg">
                                        <i class="fas fa-check-circle text-green-400 text-xl mb-1"></i>
                                        <p class="text-xs text-green-300 font-semibold">Verified</p>
                                        @if($transaction->verifiedBy)
                                            <p class="text-xs text-gray-400 mt-1">{{ $transaction->verifiedBy->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No verified transactions yet</p>
                            <p class="text-gray-500 text-sm mt-2">Start approving transactions in the other tabs</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Tab Buttons */
        .tab-button {
            background: rgba(51, 65, 85, 0.5);
            color: rgb(148, 163, 184);
        }
        .tab-button:hover {
            background: rgba(71, 85, 105, 0.7);
            color: rgb(226, 232, 240);
        }
        .tab-button.active {
            background: linear-gradient(to right, rgb(59, 130, 246), rgb(37, 99, 235));
            color: white;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Select2 Dark Theme Styling */
        .select2-container--default .select2-selection--single {
            background-color: rgb(15, 23, 42) !important;
            border: 1px solid rgb(51, 65, 85) !important;
            border-radius: 0.5rem !important;
            height: 42px !important;
            padding: 6px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white !important;
            line-height: 28px !important;
            padding-left: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgb(148, 163, 184) !important;
        }
        
        /* Dropdown */
        .select2-dropdown {
            background-color: rgb(15, 23, 42) !important;
            border: 1px solid rgb(51, 65, 85) !important;
            border-radius: 0.5rem !important;
            margin-top: 4px !important;
        }
        .select2-search--dropdown .select2-search__field {
            background-color: rgb(30, 41, 59) !important;
            border: 1px solid rgb(51, 65, 85) !important;
            color: white !important;
            border-radius: 0.5rem !important;
            padding: 8px !important;
        }
        .select2-search--dropdown .select2-search__field:focus {
            border-color: rgb(59, 130, 246) !important;
            outline: none !important;
        }
        
        /* Results */
        .select2-results__option {
            color: white !important;
            padding: 10px 12px !important;
        }
        .select2-results__option--highlighted {
            background-color: rgb(59, 130, 246) !important;
        }
        .select2-results__option--selected {
            background-color: rgb(37, 99, 235) !important;
        }
        
        /* Custom result styling */
        .select2-result-keyword {
            padding: 8px 0;
        }
        
        /* Loading */
        .select2-results__option.loading-results {
            color: rgb(148, 163, 184) !important;
        }
        
        /* Messages */
        .select2-results__message {
            color: rgb(148, 163, 184) !important;
        }
        
        /* Clear button */
        .select2-selection__clear {
            color: rgb(239, 68, 68) !important;
            font-size: 1.2em !important;
            margin-right: 8px !important;
        }
        .select2-selection__clear:hover {
            color: rgb(220, 38, 38) !important;
        }
        
        /* Transaction Cards Hover */
        [id^="transaction-"] {
            transition: all 0.2s ease;
        }
        [id^="transaction-"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }
    </style>
    @endpush

    @push('scripts')
    <!-- jQuery HARUS di-load PERTAMA -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Select2 di-load SETELAH jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // Tab Switching
        function switchTab(tabName) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active class to selected button
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        // Approve All Confirmation
        function confirmApproveAll() {
            if (confirm('Are you sure you want to approve all high-confidence matches?\n\nThis will verify {{ $stats["high_confidence"] }} transactions.')) {
                document.getElementById('approveAllForm').submit();
            }
        }

        // Initialize Select2 for keyword search
        $(document).ready(function() {
            initializeSelect2();
        });

        function initializeSelect2() {
            $('.keyword-search-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        ajax: {
                            url: '{{ route("statement-transactions.search-keywords") }}',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term,
                                    @if(auth()->user()->isSuperAdmin())
                                    company_id: {{ $bankStatement->company_id }},
                                    @endif
                                };
                            },
                            processResults: function (data) {
                                console.log('Search results:', data);
                                return {
                                    results: data.results || []
                                };
                            },
                            cache: true
                        },
                        placeholder: 'Type to search keywords...',
                        allowClear: true,
                        minimumInputLength: 2,
                        width: '100%',
                        templateResult: formatKeywordResult,
                        templateSelection: formatKeywordSelection,
                        language: {
                            inputTooShort: function() {
                                return 'Please enter 2 or more characters';
                            },
                            searching: function() {
                                return 'Searching...';
                            },
                            noResults: function() {
                                return 'No keywords found';
                            }
                        }
                    });

                    // On keyword selection
                    $(this).on('select2:select', function (e) {
                        const data = e.params.data;
                        const transactionId = $(this).data('transaction-id');
                        
                        console.log('Selected keyword:', data);
                        assignKeyword(transactionId, data.id, data);
                    });
                }
            });
        }

        function formatKeywordResult(keyword) {
            if (!keyword.id) {
                return keyword.text;
            }
            
            // Build detailed result HTML
            var $result = $(`
                <div class="select2-result-keyword py-2">
                    <div class="flex items-center justify-between mb-1">
                        <div class="font-semibold text-white text-base">${escapeHtml(keyword.keyword)}</div>
                        <div class="flex items-center gap-2">
                            ${keyword.priority ? `<span class="px-2 py-0.5 bg-yellow-600/20 text-yellow-400 rounded text-xs">Priority: ${keyword.priority}</span>` : ''}
                            ${keyword.match_count ? `<span class="px-2 py-0.5 bg-blue-600/20 text-blue-400 rounded text-xs">${keyword.match_count} matches</span>` : ''}
                        </div>
                    </div>
                    ${keyword.category_path ? `
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <i class="fas fa-sitemap text-gray-500"></i>
                            <span>${escapeHtml(keyword.category_path)}</span>
                        </div>
                    ` : ''}
                </div>
            `);
            
            return $result;
        }

        function formatKeywordSelection(keyword) {
            if (keyword.keyword) {
                return keyword.keyword + ' → ' + (keyword.sub_category || '');
            }
            return keyword.text;
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Approve Button Handler
        $(document).on('click', '.approve-btn', function() {
            const btn = $(this);
            const transactionId = btn.data('transaction-id');
            const originalHtml = btn.html();
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Approving...');
            
            $.ajax({
                url: `/statement-transactions/${transactionId}/approve`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.message);
                        $(`#transaction-${transactionId}`).fadeOut(300, function() {
                            $(this).remove();
                            updateStats();
                        });
                    }
                },
                error: function(xhr) {
                    showNotification('error', xhr.responseJSON?.message || 'Failed to approve');
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Reject Button Handler
        $(document).on('click', '.reject-btn', function() {
            const transactionId = $(this).data('transaction-id');
            const $parent = $(`#transaction-${transactionId}`);
            const $form = $parent.find('.manual-assignment-form');
            
            // Toggle form visibility
            if ($form.hasClass('hidden')) {
                $form.removeClass('hidden');
                
                // Re-initialize Select2 if needed
                const $select = $form.find('.keyword-search-select');
                if (!$select.hasClass('select2-hidden-accessible')) {
                    initializeSelect2();
                }
                
                // Open Select2
                setTimeout(() => {
                    $select.select2('open');
                }, 100);
            } else {
                $form.addClass('hidden');
            }
        });

        // Assign keyword manually
        function assignKeyword(transactionId, keywordId, keywordData) {
            // Show loading state
            const $transaction = $(`#transaction-${transactionId}`);
            const $select = $transaction.find('.keyword-search-select');
            
            // Disable select
            $select.prop('disabled', true);
            
            // Show loading overlay
            const $loadingOverlay = $(`
                <div class="absolute inset-0 bg-slate-900/80 rounded-lg flex items-center justify-center z-50">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-blue-400 text-3xl mb-2"></i>
                        <p class="text-white text-sm">Assigning keyword...</p>
                    </div>
                </div>
            `);
            $transaction.css('position', 'relative').append($loadingOverlay);
            
            $.ajax({
                url: `/statement-transactions/${transactionId}/set-keyword`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    keyword_id: keywordId
                },
                success: function(response) {
                    console.log('Assign response:', response);
                    
                    if (response.success) {
                        showNotification('success', response.message);
                        
                        // Fade out and remove transaction
                        $transaction.fadeOut(400, function() {
                            $(this).remove();
                            updateStats();
                            
                            // Check if no more transactions in current tab
                            checkEmptyTab();
                        });
                    } else {
                        showNotification('error', response.message || 'Failed to assign keyword');
                        $loadingOverlay.remove();
                        $select.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error('Assign error:', xhr);
                    
                    let errorMessage = 'Failed to assign keyword';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 403) {
                        errorMessage = 'You do not have permission to assign keywords';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Transaction or keyword not found';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later';
                    }
                    
                    showNotification('error', errorMessage);
                    $loadingOverlay.remove();
                    $select.prop('disabled', false);
                }
            });
        }

        // Show notification
        function showNotification(type, message) {
            const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const notification = $(`
                <div class="${bgColor}/20 border border-${bgColor.replace('bg-', '')} text-${bgColor.replace('bg-', '').replace('600', '400')} px-6 py-3 rounded-lg flex items-center mb-4" style="animation: slideIn 0.3s ease-out;">
                    <i class="fas ${icon} mr-2"></i>
                    <p class="font-semibold">${message}</p>
                </div>
            `);
            
            $('.max-w-7xl > .space-y-6').prepend(notification);
            
            setTimeout(() => {
                notification.fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        }

        // Update statistics (reload page)
        function updateStats() {
            const remaining = $('.tab-content:not(.hidden) .bg-slate-900\\/50, .tab-content:not(.hidden) .bg-green-900\\/10').length;
            
            console.log('Remaining transactions:', remaining);
            
            if (remaining === 0) {
                setTimeout(() => location.reload(), 1500);
            }
        }
        
        // Check if current tab is empty
        function checkEmptyTab() {
            const $activeTab = $('.tab-content:not(.hidden)');
            const $transactions = $activeTab.find('[id^="transaction-"]');
            
            if ($transactions.length === 0) {
                const emptyMessage = $(`
                    <div class="text-center py-12 fade-in">
                        <i class="fas fa-check-circle text-green-400 text-5xl mb-4"></i>
                        <p class="text-gray-300 text-lg font-semibold">All transactions processed!</p>
                        <p class="text-gray-500 text-sm mt-2">Switching to next tab...</p>
                    </div>
                `);
                
                $activeTab.find('.space-y-3').html(emptyMessage);
                
                // Auto switch to next tab if available
                setTimeout(() => {
                    const $nextTabBtn = $('.tab-button.active').next('.tab-button');
                    if ($nextTabBtn.length > 0) {
                        $nextTabBtn.click();
                    } else {
                        // All tabs done, reload
                        location.reload();
                    }
                }, 2000);
            }
        }
        
        // Auto-hide success notifications
        setTimeout(() => {
            $('.bg-green-600\\/20').fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    </script>
    @endpush
</x-app-layout>