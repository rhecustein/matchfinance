<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Company Management
            </h2>
            <a href="{{ route('admin.companies.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-2"></i>Create Company
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Status Filter Tabs -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.companies.index') }}" 
                   class="px-6 py-3 rounded-lg font-semibold transition {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' }}">
                    All Companies
                    <span class="ml-2 px-2 py-1 bg-white/20 rounded text-xs">{{ $statusCounts['all'] }}</span>
                </a>
                <a href="{{ route('admin.companies.index', ['status' => 'active']) }}" 
                   class="px-6 py-3 rounded-lg font-semibold transition {{ request('status') === 'active' ? 'bg-green-600 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' }}">
                    Active
                    <span class="ml-2 px-2 py-1 bg-white/20 rounded text-xs">{{ $statusCounts['active'] }}</span>
                </a>
                <a href="{{ route('admin.companies.index', ['status' => 'trial']) }}" 
                   class="px-6 py-3 rounded-lg font-semibold transition {{ request('status') === 'trial' ? 'bg-yellow-600 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' }}">
                    Trial
                    <span class="ml-2 px-2 py-1 bg-white/20 rounded text-xs">{{ $statusCounts['trial'] }}</span>
                </a>
                <a href="{{ route('admin.companies.index', ['status' => 'suspended']) }}" 
                   class="px-6 py-3 rounded-lg font-semibold transition {{ request('status') === 'suspended' ? 'bg-red-600 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' }}">
                    Suspended
                    <span class="ml-2 px-2 py-1 bg-white/20 rounded text-xs">{{ $statusCounts['suspended'] }}</span>
                </a>
                <a href="{{ route('admin.companies.index', ['status' => 'cancelled']) }}" 
                   class="px-6 py-3 rounded-lg font-semibold transition {{ request('status') === 'cancelled' ? 'bg-gray-600 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' }}">
                    Cancelled
                    <span class="ml-2 px-2 py-1 bg-white/20 rounded text-xs">{{ $statusCounts['cancelled'] }}</span>
                </a>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <form method="GET" action="{{ route('admin.companies.index') }}" class="flex gap-4">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search by name, slug, or subdomain..." 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                @if(request('search'))
                <a href="{{ route('admin.companies.index', ['status' => request('status')]) }}" 
                   class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
                @endif
            </form>
        </div>

        <!-- Companies Table -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Subscription</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Statistics</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($companies as $company)
                        <tr class="hover:bg-slate-900/30 transition">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $company->name }}</p>
                                    <p class="text-gray-400 text-xs">{{ $company->subdomain }}.matchfinance.app</p>
                                    @if($company->domain)
                                    <p class="text-blue-400 text-xs">
                                        <i class="fas fa-globe mr-1"></i>{{ $company->domain }}
                                    </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($company->owner)
                                <div>
                                    <p class="text-white text-sm">{{ $company->owner->name }}</p>
                                    <p class="text-gray-400 text-xs">{{ $company->owner->email }}</p>
                                </div>
                                @else
                                <span class="text-gray-500 text-sm">No owner</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($company->status === 'active') bg-green-900/30 text-green-400
                                    @elseif($company->status === 'trial') bg-yellow-900/30 text-yellow-400
                                    @elseif($company->status === 'suspended') bg-red-900/30 text-red-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ ucfirst($company->status) }}
                                </span>
                                @if($company->status === 'trial' && $company->trial_ends_at)
                                <p class="text-xs text-gray-400 mt-1">
                                    Ends: {{ $company->trial_ends_at->format('d M Y') }}
                                </p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($company->subscription)
                                <div>
                                    <p class="text-white text-sm font-semibold">{{ $company->subscription->plan->name }}</p>
                                    <p class="text-gray-400 text-xs">
                                        Rp {{ number_format($company->subscription->plan->price, 0, ',', '.') }}/{{ $company->subscription->plan->billing_period === 'monthly' ? 'mo' : 'yr' }}
                                    </p>
                                </div>
                                @else
                                <span class="text-gray-500 text-sm">No subscription</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs space-y-1">
                                    <p class="text-gray-400">
                                        <i class="fas fa-users text-blue-500 mr-1"></i>
                                        {{ $company->users_count }} users
                                    </p>
                                    <p class="text-gray-400">
                                        <i class="fas fa-file-pdf text-red-500 mr-1"></i>
                                        {{ $company->bank_statements_count }} statements
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-400 text-xs">{{ $company->created_at->format('d M Y') }}</p>
                                <p class="text-gray-500 text-xs">{{ $company->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.companies.show', $company) }}" 
                                       class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-semibold">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($company->status === 'active')
                                    <form action="{{ route('admin.companies.suspend', $company) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('Suspend this company?')"
                                                class="px-3 py-1.5 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-xs font-semibold">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                    </form>
                                    @elseif($company->status === 'suspended')
                                    <form action="{{ route('admin.companies.activate', $company) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-xs font-semibold">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    @endif

                                    <a href="{{ route('admin.companies.edit', $company) }}" 
                                       class="px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-xs font-semibold">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                onclick="return confirm('Delete this company? This action cannot be undone!')"
                                                class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-xs font-semibold">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-building text-gray-600 text-4xl mb-4"></i>
                                <p class="text-gray-400">No companies found</p>
                                @if(request('search'))
                                <a href="{{ route('admin.companies.index') }}" class="text-blue-500 hover:text-blue-400 text-sm mt-2 inline-block">
                                    Clear search
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($companies->hasPages())
            <div class="px-6 py-4 border-t border-slate-700">
                {{ $companies->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>