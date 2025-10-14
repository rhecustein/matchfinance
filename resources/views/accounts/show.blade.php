<x-app-layout>
    <x-slot name="header">Account Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('accounts.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-chart-pie mr-1"></i>Accounts
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">{{ $account->name }}</span>
            </nav>
        </div>

        {{-- Header Card --}}
        <div class="rounded-2xl p-8 mb-8 shadow-2xl" style="background: linear-gradient(135deg, {{ $account->color ?? '#3B82F6' }}, {{ $account->color ?? '#3B82F6' }}cc);">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <div class="w-24 h-24 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-chart-pie text-white text-4xl"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start space-x-3 mb-2 flex-wrap">
                        <h2 class="text-3xl font-bold text-white">{{ $account->name }}</h2>
                        @if($account->code)
                            <span class="px-3 py-1 bg-white/20 rounded-full text-white text-sm font-semibold backdrop-blur-sm">
                                {{ $account->code }}
                            </span>
                        @endif
                        @if($account->account_type)
                            <span class="px-3 py-1 bg-white/20 rounded-full text-white text-sm font-semibold backdrop-blur-sm">
                                {{ ucfirst($account->account_type) }}
                            </span>
                        @endif
                    </div>
                    @if($account->description)
                        <p class="text-white/90 mb-4">{{ $account->description }}</p>
                    @endif
                    <div class="flex items-center justify-center md:justify-start space-x-4 flex-wrap gap-2">
                        <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                            <i class="fas fa-signal"></i>
                            <span>Priority: {{ $account->priority }}</span>
                        </span>
                        @if($account->is_active)
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-green-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-check-circle"></i>
                                <span>Active</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-gray-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-times-circle"></i>
                                <span>Inactive</span>
                            </span>
                        @endif
                        @if(auth()->user()->isSuperAdmin() && $account->company)
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-building"></i>
                                <span>{{ $account->company->name }}</span>
                            </span>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->hasAdminAccess())
                    <a href="{{ route('accounts.edit', $account) }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg flex items-center space-x-2">
                        <i class="fas fa-edit"></i>
                        <span>Edit Account</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Keywords</p>
                        <p class="text-white text-3xl font-bold">{{ $keywords->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Active Keywords</p>
                        <p class="text-white text-3xl font-bold">{{ $keywords->where('is_active', true)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Transactions</p>
                        <p class="text-white text-3xl font-bold">{{ $account->transactions_count ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Match Rate</p>
                        <p class="text-white text-3xl font-bold">
                            {{ $keywords->sum('match_count') }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            {{-- Keywords Section --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white flex items-center">
                        <i class="fas fa-key text-blue-400 mr-2"></i>
                        Keywords ({{ $keywords->count() }})
                    </h3>
                    @if(auth()->user()->hasAdminAccess())
                        <a href="{{ route('account-keywords.create', ['account' => $account->uuid]) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-all">
                            <i class="fas fa-plus mr-1"></i>Add Keyword
                        </a>
                    @endif
                </div>

                <div class="p-6">
                    @forelse($keywords as $keyword)
                        <div class="mb-4 last:mb-0">
                            <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500 transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-lg font-mono text-sm border border-blue-600/30">
                                                {{ $keyword->keyword }}
                                            </span>
                                            @if($keyword->is_active)
                                                <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs">Active</span>
                                            @else
                                                <span class="px-2 py-1 bg-gray-600/20 text-gray-400 rounded text-xs">Inactive</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-4 text-xs text-gray-400">
                                            <span><i class="fas fa-signal mr-1"></i>Priority: {{ $keyword->priority }}</span>
                                            <span><i class="fas fa-check-double mr-1"></i>{{ $keyword->match_count }} matches</span>
                                            <span><i class="fas fa-cog mr-1"></i>{{ ucfirst($keyword->match_type) }}</span>
                                        </div>
                                        @if($keyword->pattern_description)
                                            <p class="text-gray-500 text-xs mt-2">{{ $keyword->pattern_description }}</p>
                                        @endif
                                    </div>
                                    @if(auth()->user()->hasAdminAccess())
                                        <div class="flex items-center space-x-2 ml-4">
                                            <a href="{{ route('account-keywords.edit', $keyword) }}" class="p-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all text-xs">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('account-keywords.destroy', $keyword) }}" method="POST" class="inline" onsubmit="return confirm('Delete this keyword?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all text-xs">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-key text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400 text-sm mb-4">No keywords yet</p>
                            @if(auth()->user()->hasAdminAccess())
                                <a href="{{ route('account-keywords.create', ['account' => $account->uuid]) }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-all">
                                    <i class="fas fa-plus"></i>
                                    <span>Add First Keyword</span>
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Transactions Section --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white flex items-center">
                        <i class="fas fa-exchange-alt text-purple-400 mr-2"></i>
                        Recent Transactions
                    </h3>
                    @if($recentTransactions->isNotEmpty())
                        <a href="{{ route('transactions.index', ['account_id' => $account->id]) }}" class="text-blue-400 hover:text-blue-300 text-sm font-semibold transition-all">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    @endif
                </div>

                <div class="p-6">
                    @forelse($recentTransactions as $transaction)
                        <div class="mb-4 last:mb-0">
                            <a href="{{ route('transactions.show', $transaction) }}" class="block bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-purple-500 transition-all">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <p class="text-white font-semibold mb-1">{{ Str::limit($transaction->description, 50) }}</p>
                                        <p class="text-gray-400 text-xs">
                                            <i class="fas fa-calendar mr-1"></i>
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        @if($transaction->transaction_type === 'credit')
                                            <span class="text-green-400 font-bold">
                                                +Rp {{ number_format($transaction->credit_amount, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-red-400 font-bold">
                                                -Rp {{ number_format($transaction->debit_amount, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if($transaction->bankStatement)
                                    <div class="flex items-center space-x-2 text-xs text-gray-500">
                                        <i class="fas fa-university"></i>
                                        <span>{{ $transaction->bankStatement->bank->name ?? 'Unknown Bank' }}</span>
                                    </div>
                                @endif
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-exchange-alt text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400 text-sm">No transactions found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        @if(auth()->user()->hasAdminAccess())
            <div class="mt-8 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <form action="{{ route('accounts.toggle-status', $account) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-xl font-semibold transition-all shadow-lg flex items-center space-x-2">
                            <i class="fas fa-power-off"></i>
                            <span>{{ $account->is_active ? 'Deactivate' : 'Activate' }}</span>
                        </button>
                    </form>

                    <form action="{{ route('accounts.rematch', $account) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition-all shadow-lg flex items-center space-x-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>Rematch Transactions</span>
                        </button>
                    </form>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner())
                    <button onclick="confirmDelete()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all shadow-lg flex items-center space-x-2">
                        <i class="fas fa-trash"></i>
                        <span>Delete Account</span>
                    </button>
                    <form id="delete-form" action="{{ route('accounts.destroy', $account) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
    @endpush
</x-app-layout>