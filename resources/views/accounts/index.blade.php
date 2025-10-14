<x-app-layout>
    <x-slot name="header">Accounts Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Account Management</h2>
                <p class="text-gray-400">Manage chart of accounts and keywords</p>
            </div>
            @if(auth()->user()->hasAdminAccess())
                <a href="{{ route('accounts.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Account</span>
                </a>
            @endif
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Accounts</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Active Accounts</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['active'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Inactive Accounts</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['inactive'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gray-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-times-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6 mb-8">
            <form method="GET" action="{{ route('accounts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Code, name, description..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:border-blue-500 focus:outline-none">
                </div>

                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Company</label>
                    <select name="company_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:border-blue-500 focus:outline-none">
                        <option value="">All Companies</option>
                        @foreach(\App\Models\Company::orderBy('name')->get() as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-gray-400 text-sm mb-2">Status</label>
                    <select name="status" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:border-blue-500 focus:outline-none">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-400 text-sm mb-2">Account Type</label>
                    <select name="account_type" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:border-blue-500 focus:outline-none">
                        <option value="">All Types</option>
                        <option value="asset" {{ request('account_type') === 'asset' ? 'selected' : '' }}>Asset</option>
                        <option value="liability" {{ request('account_type') === 'liability' ? 'selected' : '' }}>Liability</option>
                        <option value="equity" {{ request('account_type') === 'equity' ? 'selected' : '' }}>Equity</option>
                        <option value="revenue" {{ request('account_type') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="expense" {{ request('account_type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2 {{ auth()->user()->isSuperAdmin() ? '' : 'md:col-span-2' }}">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-semibold transition-all">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                    <a href="{{ route('accounts.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-xl transition-all">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Accounts List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">Accounts List</h3>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    @forelse($accounts as $account)
                        <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-3">
                                        {{-- Color Indicator --}}
                                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $account->color ?? '#3B82F6' }}"></div>
                                        
                                        {{-- Account Info --}}
                                        <div>
                                            <div class="flex items-center space-x-3 flex-wrap">
                                                <h3 class="text-xl font-bold text-white">{{ $account->name }}</h3>
                                                @if($account->code)
                                                    <span class="px-3 py-1 bg-slate-700 text-gray-300 rounded-full text-xs font-semibold">{{ $account->code }}</span>
                                                @endif
                                                @if($account->account_type)
                                                    <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-full text-xs font-semibold">
                                                        {{ ucfirst($account->account_type) }}
                                                    </span>
                                                @endif
                                                @if(auth()->user()->isSuperAdmin() && $account->company)
                                                    <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-xs font-semibold">
                                                        {{ $account->company->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($account->description)
                                                <p class="text-gray-400 text-sm mt-1">{{ $account->description }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Stats --}}
                                    <div class="flex items-center space-x-6 mt-4 flex-wrap gap-2">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-key text-blue-400"></i>
                                            <span class="text-gray-400 text-sm">{{ $account->keywords_count ?? 0 }} Keywords</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-exchange-alt text-green-400"></i>
                                            <span class="text-gray-400 text-sm">{{ $account->transactions_count ?? 0 }} Transactions</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-signal text-yellow-400"></i>
                                            <span class="text-gray-400 text-sm">Priority: {{ $account->priority }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            @if($account->is_active)
                                                <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-full text-xs font-semibold">Active</span>
                                            @else
                                                <span class="px-3 py-1 bg-gray-600/20 text-gray-400 rounded-full text-xs font-semibold">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Keywords Preview --}}
                                    @if($account->keywords && $account->keywords->isNotEmpty())
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach($account->keywords->take(5) as $keyword)
                                                <span class="px-3 py-1 bg-blue-600/10 text-blue-400 rounded-lg text-xs border border-blue-600/30">
                                                    {{ $keyword->keyword }}
                                                </span>
                                            @endforeach
                                            @if($account->keywords->count() > 5)
                                                <span class="px-3 py-1 bg-slate-700 text-gray-400 rounded-lg text-xs">
                                                    +{{ $account->keywords->count() - 5 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex items-center space-x-2 ml-4">
                                    <a href="{{ route('accounts.show', $account) }}" class="p-3 bg-teal-600/20 text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg transition-all" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if(auth()->user()->hasAdminAccess())
                                        <a href="{{ route('accounts.edit', $account) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        {{-- Toggle Status --}}
                                        <form action="{{ route('accounts.toggle-status', $account) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-3 bg-yellow-600/20 text-yellow-400 hover:bg-yellow-600 hover:text-white rounded-lg transition-all" title="Toggle Status">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>

                                        {{-- Rematch --}}
                                        <form action="{{ route('accounts.rematch', $account) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-3 bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded-lg transition-all" title="Rematch Transactions">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner())
                                            <button onclick="confirmDelete({{ $account->id }}, '{{ $account->name }}')" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $account->id }}" action="{{ route('accounts.destroy', $account) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-chart-pie text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg mb-2">No accounts found</p>
                            <p class="text-gray-500 text-sm">Try adjusting your filters or create a new account</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Pagination --}}
            @if($accounts->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $accounts->links() }}
                </div>
            @endif
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
                    <p class="text-gray-400 mb-4">Delete account <strong id="deleteAccountName" class="text-white"></strong>?</p>
                    <p class="text-yellow-400 text-sm"><i class="fas fa-exclamation-triangle mr-1"></i>This action cannot be undone</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">Cancel</button>
                    <button id="confirmDeleteBtn" onclick="submitDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let deleteFormId = null;

        function confirmDelete(id, name) {
            deleteFormId = id;
            document.getElementById('deleteAccountName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteFormId = null;
        }

        function submitDelete() {
            if (deleteFormId) {
                document.getElementById('delete-form-' + deleteFormId).submit();
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
    @endpush
</x-app-layout>