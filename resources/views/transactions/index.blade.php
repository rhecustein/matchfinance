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