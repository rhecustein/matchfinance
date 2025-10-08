<x-app-layout>
    <x-slot name="header">Transaction Detail</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('transactions.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-receipt mr-1"></i>Transactions
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Transaction #{{ $transaction->id }}</span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Transaction Info Card --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2">{{ $transaction->description ?? '-' }}</h2>
                            <p class="text-gray-400">
                                {{ $transaction->transaction_date ? $transaction->transaction_date->format('l, d F Y') : '-' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold {{ $transaction->transaction_type == 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                {{ $transaction->transaction_type == 'debit' ? '-' : '+' }}
                                Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}
                            </div>
                            @if($transaction->balance)
                                <p class="text-gray-400 text-sm mt-1">
                                    Balance: Rp {{ number_format($transaction->balance, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Status Badges --}}
                    <div class="flex flex-wrap gap-2 mb-6">
                        @if($transaction->transaction_type == 'debit')
                            <span class="px-3 py-1 bg-red-600/20 text-red-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-arrow-down mr-1"></i>Debit
                            </span>
                        @else
                            <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-arrow-up mr-1"></i>Credit
                            </span>
                        @endif

                        @if($transaction->is_verified)
                            <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>Verified
                            </span>
                        @else
                            <span class="px-3 py-1 bg-yellow-600/20 text-yellow-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-clock mr-1"></i>Unverified
                            </span>
                        @endif

                        @if($transaction->matched_keyword_id)
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-link mr-1"></i>Auto Matched
                            </span>
                        @elseif($transaction->is_manual_category)
                            <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-hand-pointer mr-1"></i>Manual
                            </span>
                        @else
                            <span class="px-3 py-1 bg-orange-600/20 text-orange-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-unlink mr-1"></i>Unmatched
                            </span>
                        @endif

                        @if($transaction->confidence_score)
                            @php
                                $confColorBg = $transaction->confidence_score >= 80 ? 'bg-green-600/20' : ($transaction->confidence_score >= 60 ? 'bg-yellow-600/20' : 'bg-red-600/20');
                                $confColorText = $transaction->confidence_score >= 80 ? 'text-green-400' : ($transaction->confidence_score >= 60 ? 'text-yellow-400' : 'text-red-400');
                            @endphp
                            <span class="px-3 py-1 {{ $confColorBg }} {{ $confColorText }} rounded-full text-sm font-semibold">
                                <i class="fas fa-tachometer-alt mr-1"></i>Confidence: {{ $transaction->confidence_score }}%
                            </span>
                        @endif
                    </div>

                    {{-- Details Grid --}}
                    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-slate-700">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Bank</p>
                            <p class="text-white font-semibold">
                                {{ $transaction->bankStatement->bank->name ?? 'Unknown Bank' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Statement</p>
                            @if($transaction->bankStatement)
                                <a href="{{ route('bank-statements.show', $transaction->bankStatement) }}" class="text-blue-400 hover:text-blue-300 font-semibold">
                                    #{{ $transaction->bankStatement->id }}
                                    @if($transaction->bankStatement->period_start)
                                        - {{ $transaction->bankStatement->period_start->format('M Y') }}
                                    @endif
                                </a>
                            @else
                                <p class="text-gray-500">-</p>
                            @endif
                        </div>
                        @if($transaction->matchedKeyword)
                            <div>
                                <p class="text-gray-400 text-sm mb-1">Matched Keyword</p>
                                <code class="text-purple-400">{{ $transaction->matchedKeyword->keyword }}</code>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm mb-1">Keyword Priority</p>
                                <p class="text-white font-semibold">{{ $transaction->matchedKeyword->priority ?? '-' }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Notes --}}
                    @if($transaction->notes)
                        <div class="mt-6 p-4 bg-blue-600/10 border border-blue-600/30 rounded-xl">
                            <p class="text-blue-400 font-semibold text-sm mb-1">
                                <i class="fas fa-sticky-note mr-1"></i>Notes
                            </p>
                            <p class="text-gray-300 text-sm">{{ $transaction->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Categorization Info --}}
                @if($transaction->subCategory)
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-4">
                            <i class="fas fa-sitemap mr-2"></i>Categorization
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                                <span class="text-gray-400">Type</span>
                                <span class="text-white font-semibold">
                                    {{ $transaction->subCategory->category->type->name ?? '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                                <span class="text-gray-400">Category</span>
                                <span class="text-white font-semibold">
                                    {{ $transaction->subCategory->category->name ?? '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                                <span class="text-gray-400">Sub Category</span>
                                <span class="text-white font-semibold">
                                    {{ $transaction->subCategory->name ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Matching History --}}
                @if($transaction->matchingLogs && $transaction->matchingLogs->count() > 0)
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-4">
                            <i class="fas fa-history mr-2"></i>Matching History
                        </h3>
                        <div class="space-y-2">
                            @foreach($transaction->matchingLogs as $log)
                                <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg text-sm">
                                    <div class="flex-1">
                                        <code class="text-purple-400">{{ $log->keyword->keyword ?? '-' }}</code>
                                        <p class="text-gray-500 text-xs mt-1">
                                            {{ $log->keyword->subCategory->name ?? '-' }}
                                            @if($log->matched_at)
                                                - {{ $log->matched_at->format('d M Y H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                    @if($log->confidence_score)
                                        <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs font-semibold">
                                            {{ $log->confidence_score }}%
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar Actions --}}
            <div class="space-y-6">
                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        @if(!$transaction->is_verified && ($transaction->matched_keyword_id || $transaction->is_manual_category))
                            <form action="{{ route('transactions.verify', $transaction) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Verify Transaction</span>
                                </button>
                            </form>
                        @elseif($transaction->is_verified)
                            <div class="w-full bg-green-600/20 border border-green-600/30 text-green-400 px-4 py-3 rounded-xl font-semibold flex items-center justify-center space-x-2">
                                <i class="fas fa-check-circle"></i>
                                <span>Verified</span>
                            </div>
                            
                            <form action="{{ route('transactions.unverify', $transaction) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Unverify</span>
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('transactions.rematch', $transaction) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                                <i class="fas fa-sync"></i>
                                <span>Re-match</span>
                            </button>
                        </form>

                        @if($transaction->matched_keyword_id || $transaction->is_manual_category)
                            <form action="{{ route('transactions.unmatch', $transaction) }}" method="POST" onsubmit="return confirm('Clear this transaction match?')">
                                @csrf
                                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                                    <i class="fas fa-unlink"></i>
                                    <span>Clear Match</span>
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('transactions.index') }}" class="block w-full bg-slate-700 hover:bg-slate-600 text-white px-4 py-3 rounded-xl font-semibold transition text-center">
                            <i class="fas fa-arrow-left mr-2"></i>Back to List
                        </a>
                    </div>
                </div>

                {{-- Manual Categorization --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-edit mr-2"></i>Manual Update
                    </h3>
                    <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Sub Category</label>
                            <select name="sub_category_id" class="w-full px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Select Sub Category --</option>
                                @foreach($subCategories as $typeName => $subs)
                                    <optgroup label="{{ $typeName }}">
                                        @foreach($subs as $sub)
                                            <option value="{{ $sub->id }}" {{ $transaction->sub_category_id == $sub->id ? 'selected' : '' }}>
                                                {{ $sub->category->name }} - {{ $sub->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Notes</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500" placeholder="Add notes...">{{ $transaction->notes }}</textarea>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                            <i class="fas fa-save mr-2"></i>Update
                        </button>
                    </form>
                </div>

                {{-- Verification Info --}}
                @if($transaction->is_verified && $transaction->verifiedBy)
                    <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-green-400 mb-4">
                            <i class="fas fa-check-circle mr-2"></i>Verification Info
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Verified By</span>
                                <span class="text-white font-semibold">{{ $transaction->verifiedBy->name }}</span>
                            </div>
                            @if($transaction->verified_at)
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Verified At</span>
                                    <span class="text-white font-semibold">{{ $transaction->verified_at->format('d M Y H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Danger Zone --}}
                <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl">
                    <h3 class="text-lg font-bold text-red-400 mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                    </h3>
                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                            <i class="fas fa-trash"></i>
                            <span>Delete Transaction</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>