<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Document Collections
            </h2>
            <a href="{{ route('document-collections.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                <i class="fas fa-plus mr-2"></i>Create Collection
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="{{ route('document-collections.index') }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition shadow-lg {{ !request('status') ? 'ring-2 ring-blue-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Collections</p>
                        <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-900/30 rounded-lg">
                        <i class="fas fa-folder text-blue-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('document-collections.index', ['status' => 'active']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-green-500 transition shadow-lg {{ request('status') == 'active' ? 'ring-2 ring-green-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Active</p>
                        <p class="text-3xl font-bold text-green-400">{{ $stats['active'] }}</p>
                    </div>
                    <div class="p-3 bg-green-900/30 rounded-lg">
                        <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('document-collections.index', ['status' => 'inactive']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-gray-500 transition shadow-lg {{ request('status') == 'inactive' ? 'ring-2 ring-gray-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Inactive</p>
                        <p class="text-3xl font-bold text-gray-400">{{ $stats['inactive'] }}</p>
                    </div>
                    <div class="p-3 bg-gray-900/30 rounded-lg">
                        <i class="fas fa-pause-circle text-gray-400 text-2xl"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <form method="GET" action="{{ route('document-collections.index') }}" class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[250px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Search Collections</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search by name or description..."
                           class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                </div>

                <!-- Company Filter (Super Admin Only) -->
                @if(auth()->user()->isSuperAdmin() && isset($companies))
                <div class="min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Company</label>
                    <select name="company_id" 
                            class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Status Filter -->
                <div class="min-w-[180px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
                    <select name="status" 
                            class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('document-collections.index') }}" 
                       class="px-6 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Collections Grid -->
        @if($collections->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($collections as $collection)
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden transform hover:scale-105 transition-all duration-300">
                <!-- Header -->
                <div class="p-6 bg-gradient-to-r from-purple-600 to-blue-600">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-1">{{ $collection->name }}</h3>
                            @if($collection->description)
                            <p class="text-blue-100 text-sm line-clamp-2">{{ $collection->description }}</p>
                            @endif
                        </div>
                        @if($collection->is_active)
                        <span class="px-3 py-1 bg-green-500 text-white rounded-full text-xs font-semibold flex-shrink-0 ml-2">
                            Active
                        </span>
                        @else
                        <span class="px-3 py-1 bg-gray-500 text-white rounded-full text-xs font-semibold flex-shrink-0 ml-2">
                            Inactive
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="text-center p-3 bg-slate-900/50 rounded-lg">
                            <p class="text-gray-400 text-xs mb-1">Documents</p>
                            <p class="text-white text-2xl font-bold">{{ $collection->items_count ?? 0 }}</p>
                        </div>
                        <div class="text-center p-3 bg-slate-900/50 rounded-lg">
                            <p class="text-gray-400 text-xs mb-1">Chat Sessions</p>
                            <p class="text-white text-2xl font-bold">{{ $collection->chat_sessions_count ?? 0 }}</p>
                        </div>
                    </div>

                    <!-- Meta Info -->
                    <div class="space-y-2 mb-6 text-sm">
                        @if(auth()->user()->isSuperAdmin())
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-building w-5 text-purple-400"></i>
                            <span class="ml-2">{{ $collection->company->name ?? 'Unknown Company' }}</span>
                        </div>
                        @endif
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-user w-5 text-blue-400"></i>
                            <span class="ml-2">Created by {{ $collection->user->name ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-clock w-5 text-purple-400"></i>
                            <span class="ml-2">{{ $collection->created_at->diffForHumans() }}</span>
                        </div>
                        @if($collection->last_used_at)
                        <div class="flex items-center text-gray-400">
                            <i class="fas fa-history w-5 text-green-400"></i>
                            <span class="ml-2">Used {{ $collection->last_used_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <a href="{{ route('document-collections.show', $collection) }}" 
                           class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center text-sm font-semibold">
                            <i class="fas fa-eye mr-1"></i>View
                        </a>
                        
                        <a href="{{ route('document-collections.edit', $collection) }}" 
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-semibold">
                            <i class="fas fa-edit"></i>
                        </a>

                        <form action="{{ route('document-collections.toggle-active', $collection) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="px-4 py-2 {{ $collection->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition text-sm font-semibold"
                                    title="{{ $collection->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="fas fa-{{ $collection->is_active ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>

                        @if($collection->chat_sessions_count == 0)
                        <form action="{{ route('document-collections.destroy', $collection) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('Delete this collection?')"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($collections->hasPages())
        <div class="mt-8">
            {{ $collections->links() }}
        </div>
        @endif

        @else
        <!-- Empty State -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 border border-slate-700 shadow-xl text-center">
            <i class="fas fa-folder-open text-gray-600 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-white mb-2">No Collections Found</h3>
            <p class="text-gray-400 mb-6">Create your first document collection to organize and chat with your bank statements.</p>
            <a href="{{ route('document-collections.create') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                <i class="fas fa-plus mr-2"></i>Create First Collection
            </a>
        </div>
        @endif

    </div>
</x-app-layout>