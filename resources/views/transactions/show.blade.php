<!-- ============================================================ -->
<!-- INDEX VIEW: resources/views/transactions/index.blade.php -->
<!-- ============================================================ -->

<x-app-layout>
    <x-slot name="header">Transactions</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">All Transactions</h2>
                <p class="text-gray-400">View and manage transaction categorization</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
            <form method="GET" action="{{ route('transactions.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    {{-- Status Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-filter mr-1"></i>Status
                        </label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="matched" {{ request('status') == 'matched' ? 'selected' : '' }}>Matched</option>
                            <option value="unmatched" {{ request('status') == 'unmatched' ? 'selected' : '' }}>Unmatched</option>
                            <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>Unverified</option>
                            <option value="low_confidence" {{ request('status') == 'low_confidence' ? 'selected' : '' }}>Low Confidence</option>
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Date From
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                               class="w-full px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Date To
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                               class="w-full px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Category Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-folder mr-1"></i>Category
                        </label>
                        <select name="category_id" class="w-full px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            @foreach(\App\Models\Category::with('type')->orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }} ({{ $cat->type->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition text-sm">
                            <i class="fas fa-search mr-1"></i>Filter
                        </button>
                        @if(request()->hasAny(['status', 'date_from', 'date_to', 'category_id', 'bank_statement_id']))
                            <a href="{{ route('transactions.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-semibold transition text-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Transactions List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($transactions as $transaction)
                        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500 transition">
                            <div class="flex items-start justify-between">
                                {{-- Main Info --}}
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        {{-- Date --}}
                                        <span class="px-2 py-1 bg-slate-800 rounded text-xs text-gray-400">
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                        </span>
                                        
                                        {{-- Type Badge --}}
                                        @if($transaction->transaction_type == 'debit')
                                            <span class="px-2 py-1 bg-red-600/20 text-red-400 rounded text-xs font-semibold">
                                                <i class="fas fa-arrow-down mr-1"></i>Debit
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold">
                                                <i class="fas fa-arrow-up mr-1"></i>Credit
                                            </span>
                                        @endif

                                        {{-- Status Badges --}}
                                        @if($transaction->is_verified)
                                            <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs">
                                                <i class="fas fa-check-circle mr-1"></i>Verified
                                            </span>
                                        @endif

                                        @if($transaction->matched_keyword_id)
                                            <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs">
                                                <i class="fas fa-link mr-1"></i>Matched
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-yellow-600/20 text-yellow-400 rounded text-xs">
                                                <i class="fas fa-unlink mr-1"></i>Unmatched
                                            </span>
                                        @endif

                                        @if($transaction->confidence_score < 60)
                                            <span class="px-2 py-1 bg-orange-600/20 text-orange-400 rounded text-xs">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>Low Conf: {{ $transaction->confidence_score }}%
                                            </span>
                                        @elseif($transaction->confidence_score)
                                            <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs">
                                                Conf: {{ $transaction->confidence_score }}%
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Description --}}
                                    <p class="text-white font-semibold mb-2">{{ $transaction->description }}</p>

                                    {{-- Category Info --}}
                                    @if($transaction->subCategory)
                                        <div class="flex items-center space-x-2 text-xs text-gray-400">
                                            <i class="fas fa-folder"></i>
                                            <span>{{ $transaction->subCategory->category->type->name }}</span>
                                            <i class="fas fa-chevron-right text-xs"></i>
                                            <span>{{ $transaction->subCategory->category->name }}</span>
                                            <i class="fas fa-chevron-right text-xs"></i>
                                            <span class="text-blue-400">{{ $transaction->subCategory->name }}</span>
                                        </div>
                                    @endif

                                    {{-- Bank Info --}}
                                    <div class="flex items-center space-x-3 mt-2 text-xs text-gray-500">
                                        <span>
                                            <i class="fas fa-university mr-1"></i>{{ $transaction->bankStatement->bank->name }}
                                        </span>
                                        <span>
                                            <i class="fas fa-file-alt mr-1"></i>Statement #{{ $transaction->bankStatement->id }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Amount & Actions --}}
                                <div class="ml-4 text-right">
                                    <div class="text-2xl font-bold mb-3 {{ $transaction->transaction_type == 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                        {{ $transaction->transaction_type == 'debit' ? '-' : '+' }}
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    </div>

                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('transactions.show', $transaction) }}" 
                                           class="p-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition text-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if(!$transaction->is_verified && $transaction->matched_keyword_id)
                                            <form action="{{ route('transactions.verify', $transaction) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white rounded-lg transition text-sm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if(!$transaction->matched_keyword_id || $transaction->confidence_score < 80)
                                            <form action="{{ route('transactions.rematch', $transaction) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded-lg transition text-sm">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-receipt text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No transactions found</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($transactions->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>


<!-- ============================================================ -->
<!-- SHOW VIEW: resources/views/transactions/show.blade.php -->
<!-- ============================================================ -->

<x-app-layout>
    <x-slot name="header">Transaction Detail</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
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
                            <h2 class="text-2xl font-bold text-white mb-2">{{ $transaction->description }}</h2>
                            <p class="text-gray-400">{{ $transaction->transaction_date->format('l, d F Y') }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold {{ $transaction->transaction_type == 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                {{ $transaction->transaction_type == 'debit' ? '-' : '+' }}
                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </div>
                            <p class="text-gray-400 text-sm mt-1">
                                Balance: Rp {{ number_format($transaction->balance, 0, ',', '.') }}
                            </p>
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
                        @else
                            <span class="px-3 py-1 bg-orange-600/20 text-orange-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-unlink mr-1"></i>Unmatched
                            </span>
                        @endif

                        @if($transaction->confidence_score)
                            @php
                                $confColor = $transaction->confidence_score >= 80 ? 'green' : ($transaction->confidence_score >= 60 ? 'yellow' : 'red');
                            @endphp
                            <span class="px-3 py-1 bg-{{ $confColor }}-600/20 text-{{ $confColor }}-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-tachometer-alt mr-1"></i>Confidence: {{ $transaction->confidence_score }}%
                            </span>
                        @endif
                    </div>

                    {{-- Details Grid --}}
                    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-slate-700">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Bank</p>
                            <p class="text-white font-semibold">{{ $transaction->bankStatement->bank->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Statement</p>
                            <a href="{{ route('bank-statements.show', $transaction->bankStatement) }}" class="text-blue-400 hover:text-blue-300 font-semibold">
                                #{{ $transaction->bankStatement->id }} - {{ $transaction->bankStatement->period_start->format('M Y') }}
                            </a>
                        </div>
                        @if($transaction->matched_keyword_id)
                            <div>
                                <p class="text-gray-400 text-sm mb-1">Matched Keyword</p>
                                <code class="text-purple-400">{{ $transaction->matchedKeyword->keyword }}</code>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm mb-1">Keyword Priority</p>
                                <p class="text-white font-semibold">{{ $transaction->matchedKeyword->priority }}</p>
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
                                <span class="text-white font-semibold">{{ $transaction->subCategory->category->type->name }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                                <span class="text-gray-400">Category</span>
                                <span class="text-white font-semibold">{{ $transaction->subCategory->category->name }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                                <span class="text-gray-400">Sub Category</span>
                                <span class="text-white font-semibold">{{ $transaction->subCategory->name }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Matching History --}}
                @if($transaction->matchingLogs->count() > 0)
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-4">
                            <i class="fas fa-history mr-2"></i>Matching History
                        </h3>
                        <div class="space-y-2">
                            @foreach($transaction->matchingLogs as $log)
                                <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg text-sm">
                                    <div class="flex-1">
                                        <code class="text-purple-400">{{ $log->keyword->keyword }}</code>
                                        <p class="text-gray-500 text-xs mt-1">
                                            {{ $log->keyword->subCategory->name }} - {{ $log->matched_at->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs font-semibold">
                                        {{ $log->confidence_score }}%
                                    </span>
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
                        @if(!$transaction->is_verified)
                            <form action="{{ route('transactions.verify', $transaction) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Verify Transaction</span>
                                </button>
                            </form>
                        @else
                            <div class="w-full bg-green-600/20 border border-green-600/30 text-green-400 px-4 py-3 rounded-xl font-semibold flex items-center justify-center space-x-2">
                                <i class="fas fa-check-circle"></i>
                                <span>Verified</span>
                            </div>
                        @endif

                        <form action="{{ route('transactions.rematch', $transaction) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center space-x-2">
                                <i class="fas fa-sync"></i>
                                <span>Re-match</span>
                            </button>
                        </form>

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
                                <option value="">-- Select --</option>
                                @foreach(\App\Models\SubCategory::with('category.type')->orderBy('name')->get() as $sub)
                                    <option value="{{ $sub->id }}" {{ $transaction->sub_category_id == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->category->type->name }} - {{ $sub->category->name }} - {{ $sub->name }}
                                    </option>
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
                @if($transaction->is_verified)
                    <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-green-400 mb-4">
                            <i class="fas fa-check-circle mr-2"></i>Verification Info
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Verified By</span>
                                <span class="text-white font-semibold">{{ $transaction->verifiedBy->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Verified At</span>
                                <span class="text-white font-semibold">{{ $transaction->verified_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>