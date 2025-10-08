<x-app-layout>
    <x-slot name="header">Account Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <a href="{{ route('accounts.index') }}" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 rounded-xl flex items-center justify-center border border-slate-700 transition-all">
                    <i class="fas fa-arrow-left text-white"></i>
                </a>
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $account->color ?? '#3B82F6' }}"></div>
                        <h2 class="text-2xl font-bold text-white">{{ $account->name }}</h2>
                        @if($account->code)
                            <span class="px-3 py-1 bg-slate-700 text-gray-300 rounded-full text-sm font-semibold">{{ $account->code }}</span>
                        @endif
                    </div>
                    <p class="text-gray-400">{{ $account->description ?? 'No description' }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <form action="{{ route('accounts.toggle-status', $account) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="bg-yellow-600/20 text-yellow-400 hover:bg-yellow-600 hover:text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        <i class="fas fa-power-off mr-2"></i>
                        {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>

                <a href="{{ route('accounts.edit', $account) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
            </div>
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Total Keywords</p>
                    <i class="fas fa-key text-blue-400"></i>
                </div>
                <p class="text-white text-3xl font-bold">{{ $statistics['total_keywords'] ?? 0 }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $statistics['active_keywords'] ?? 0 }} active</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Transactions</p>
                    <i class="fas fa-exchange-alt text-purple-400"></i>
                </div>
                <p class="text-white text-3xl font-bold">{{ $statistics['total_transactions'] ?? 0 }}</p>
                <p class="text-gray-500 text-xs mt-1">Total matched</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Match Rate</p>
                    <i class="fas fa-chart-line text-green-400"></i>
                </div>
                <p class="text-white text-3xl font-bold">
                    {{ $statistics['total_transactions'] > 0 ? number_format(($statistics['total_transactions'] / max($statistics['total_transactions'], 1)) * 100, 1) : 0 }}%
                </p>
                <p class="text-gray-500 text-xs mt-1">Success rate</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Priority</p>
                    <i class="fas fa-signal text-yellow-400"></i>
                </div>
                <p class="text-white text-3xl font-bold">{{ $account->priority }}</p>
                <p class="text-gray-500 text-xs mt-1">
                    @if($account->is_active)
                        <span class="text-green-400">Active</span>
                    @else
                        <span class="text-gray-400">Inactive</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column: Account Info & Keywords --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Account Information --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-info-circle mr-3 text-blue-400"></i>
                        Account Information
                    </h3>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Account Code</p>
                            <p class="text-white font-semibold">{{ $account->code ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-gray-400 text-sm mb-1">Account Type</p>
                            <p class="text-white font-semibold">{{ $account->account_type ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-gray-400 text-sm mb-1">Priority</p>
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-slate-700 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: {{ $account->priority * 10 }}%"></div>
                                </div>
                                <span class="text-white font-semibold">{{ $account->priority }}/10</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-gray-400 text-sm mb-1">Status</p>
                            @if($account->is_active)
                                <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-full text-sm font-semibold inline-block">Active</span>
                            @else
                                <span class="px-3 py-1 bg-gray-600/20 text-gray-400 rounded-full text-sm font-semibold inline-block">Inactive</span>
                            @endif
                        </div>

                        <div class="col-span-2">
                            <p class="text-gray-400 text-sm mb-1">Created</p>
                            <p class="text-white">{{ $account->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Keywords List --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-key mr-3 text-purple-400"></i>
                            Keywords ({{ $account->keywords->count() }})
                        </h3>
                        <a href="{{ route('accounts.edit', $account) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all text-sm">
                            <i class="fas fa-plus mr-2"></i>Add Keyword
                        </a>
                    </div>

                    @if($account->keywords->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($account->keywords as $keyword)
                                <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500/50 transition-all">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <code class="text-blue-400 font-mono bg-blue-600/10 px-3 py-1 rounded-lg border border-blue-600/30">
                                                    {{ $keyword->keyword }}
                                                </code>
                                                <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs font-semibold">
                                                    {{ ucfirst(str_replace('_', ' ', $keyword->match_type)) }}
                                                </span>
                                                @if($keyword->is_active)
                                                    <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold">Active</span>
                                                @else
                                                    <span class="px-2 py-1 bg-gray-600/20 text-gray-400 rounded text-xs font-semibold">Inactive</span>
                                                @endif
                                            </div>

                                            <div class="flex items-center space-x-4 text-sm text-gray-400">
                                                <span><i class="fas fa-signal mr-1"></i>Priority: {{ $keyword->priority }}</span>
                                                @if($keyword->case_sensitive)
                                                    <span><i class="fas fa-font mr-1"></i>Case Sensitive</span>
                                                @endif
                                                @if($keyword->is_regex)
                                                    <span><i class="fas fa-code mr-1"></i>Regex</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-white font-bold text-lg">{{ $keyword->match_count ?? 0 }}</p>
                                            <p class="text-gray-500 text-xs">matches</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-key text-gray-600 text-4xl mb-3"></i>
                            <p class="text-gray-400">No keywords added yet</p>
                            <a href="{{ route('accounts.edit', $account) }}" class="text-blue-400 hover:text-blue-300 text-sm mt-2 inline-block">
                                Add your first keyword →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column: Actions & Recent Transactions --}}
            <div class="space-y-6">
                
                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-bolt mr-3 text-yellow-400"></i>
                        Quick Actions
                    </h3>

                    <div class="space-y-3">
                        <form action="{{ route('accounts.rematch', $account) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-purple-600/20 hover:bg-purple-600 text-purple-400 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all text-left flex items-center justify-between">
                                <span><i class="fas fa-sync-alt mr-2"></i>Rematch Transactions</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>

                        <a href="{{ route('accounts.statistics', $account) }}" class="w-full bg-teal-600/20 hover:bg-teal-600 text-teal-400 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all text-left flex items-center justify-between block">
                            <span><i class="fas fa-chart-bar mr-2"></i>View Statistics</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>

                        <button onclick="confirmDelete()" class="w-full bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all text-left flex items-center justify-between">
                            <span><i class="fas fa-trash mr-2"></i>Delete Account</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Recent Transactions --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-history mr-3 text-green-400"></i>
                        Recent Transactions
                    </h3>

                    @if($account->transactions->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($account->transactions as $transaction)
                                <a href="{{ route('transactions.show', $transaction) }}" class="block bg-slate-900/50 rounded-lg p-3 border border-slate-700 hover:border-blue-500/50 transition-all">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-white font-semibold text-sm truncate">{{ Str::limit($transaction->description, 30) }}</p>
                                        <span class="px-2 py-1 {{ $transaction->transaction_type === 'debit' ? 'bg-red-600/20 text-red-400' : 'bg-green-600/20 text-green-400' }} rounded text-xs font-semibold">
                                            {{ strtoupper($transaction->transaction_type) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <p class="text-gray-400 text-xs">{{ $transaction->transaction_date->format('d M Y') }}</p>
                                        <p class="text-white font-bold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <a href="{{ route('transactions.index', ['account_id' => $account->id]) }}" class="block text-center text-blue-400 hover:text-blue-300 text-sm mt-4">
                            View all transactions →
                        </a>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-exchange-alt text-gray-600 text-3xl mb-2"></i>
                            <p class="text-gray-400 text-sm">No transactions yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Account?</h3>
                    <p class="text-gray-400 mb-4">Are you sure you want to delete <strong class="text-white">"{{ $account->name }}"</strong>?</p>
                    @if($account->transactions()->count() > 0)
                        <p class="text-yellow-400 text-sm">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            This account has {{ $account->transactions()->count() }} associated transactions and cannot be deleted.
                        </p>
                    @else
                        <p class="text-red-400 text-sm">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            This action cannot be undone.
                        </p>
                    @endif
                </div>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Cancel
                    </button>
                    @if($account->transactions()->count() === 0)
                        <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Delete
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</x-app-layout>