<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                AI Chat Sessions
            </h2>
            <a href="{{ route('chat-sessions.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                <i class="fas fa-plus mr-2"></i>New Chat Session
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="{{ route('chat-sessions.index') }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition shadow-lg {{ !request('archived') ? 'ring-2 ring-blue-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Sessions</p>
                        <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-900/30 rounded-lg">
                        <i class="fas fa-comments text-blue-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('chat-sessions.index', ['archived' => 'false']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-green-500 transition shadow-lg {{ request('archived') === 'false' ? 'ring-2 ring-green-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Active</p>
                        <p class="text-3xl font-bold text-green-400">{{ $stats['active'] }}</p>
                    </div>
                    <div class="p-3 bg-green-900/30 rounded-lg">
                        <i class="fas fa-message text-green-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('chat-sessions.index', ['archived' => 'true']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-gray-500 transition shadow-lg {{ request('archived') === 'true' ? 'ring-2 ring-gray-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Archived</p>
                        <p class="text-3xl font-bold text-gray-400">{{ $stats['archived'] }}</p>
                    </div>
                    <div class="p-3 bg-gray-900/30 rounded-lg">
                        <i class="fas fa-archive text-gray-400 text-2xl"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <form method="GET" action="{{ route('chat-sessions.index') }}" class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[250px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Search Sessions</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search by title or description..."
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

                <!-- Mode Filter -->
                <div class="min-w-[180px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Mode</label>
                    <select name="mode" 
                            class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">All Modes</option>
                        <option value="single" {{ request('mode') == 'single' ? 'selected' : '' }}>Single Document</option>
                        <option value="collection" {{ request('mode') == 'collection' ? 'selected' : '' }}>Collection</option>
                    </select>
                </div>

                <!-- Archived Filter -->
                <div class="min-w-[180px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
                    <select name="archived" 
                            class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="false" {{ request('archived') === 'false' ? 'selected' : '' }}>Active</option>
                        <option value="true" {{ request('archived') === 'true' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('chat-sessions.index') }}" 
                       class="px-6 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Chat Sessions List -->
        @if($sessions->isNotEmpty())
        <div class="space-y-4">
            @foreach($sessions as $session)
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden hover:border-blue-500 transition">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                @if($session->is_pinned)
                                <i class="fas fa-thumbtack text-yellow-400"></i>
                                @endif
                                
                                <h3 class="text-xl font-bold text-white">{{ $session->title }}</h3>
                                
                                @if($session->mode === 'single')
                                <span class="px-3 py-1 bg-blue-900/30 text-blue-400 rounded-full text-xs font-semibold">
                                    <i class="fas fa-file mr-1"></i>Single
                                </span>
                                @else
                                <span class="px-3 py-1 bg-purple-900/30 text-purple-400 rounded-full text-xs font-semibold">
                                    <i class="fas fa-folder mr-1"></i>Collection
                                </span>
                                @endif
                            </div>

                            <!-- Context Info -->
                            <div class="flex items-center gap-4 text-sm text-gray-400 mb-3 flex-wrap">
                                @if(auth()->user()->isSuperAdmin())
                                <div class="flex items-center">
                                    <i class="fas fa-building mr-2 text-purple-400"></i>
                                    <span>{{ $session->company->name ?? 'Unknown Company' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-2 text-blue-400"></i>
                                    <span>{{ $session->user->name ?? 'Unknown User' }}</span>
                                </div>
                                @endif
                                
                                @if($session->mode === 'single' && $session->bankStatement)
                                <div class="flex items-center">
                                    <i class="fas fa-university mr-2 text-green-400"></i>
                                    <span>{{ $session->bankStatement->bank->name ?? 'Unknown Bank' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2 text-blue-400"></i>
                                    <span>{{ $session->bankStatement->period_start->format('M Y') }}</span>
                                </div>
                                @elseif($session->mode === 'collection' && $session->documentCollection)
                                <div class="flex items-center">
                                    <i class="fas fa-folder mr-2 text-purple-400"></i>
                                    <span>{{ $session->documentCollection->name }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-file mr-2 text-blue-400"></i>
                                    <span>{{ $session->documentCollection->document_count }} docs</span>
                                </div>
                                @endif
                            </div>

                            @if($session->context_description)
                            <p class="text-gray-400 text-sm mb-3">{{ Str::limit($session->context_description, 100) }}</p>
                            @endif

                            <!-- Stats -->
                            <div class="flex items-center gap-6 text-sm">
                                <div class="flex items-center text-gray-400">
                                    <i class="fas fa-message mr-2"></i>
                                    <span>{{ $session->message_count }} messages</span>
                                </div>
                                <div class="flex items-center text-gray-400">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span>{{ $session->last_activity_at->diffForHumans() }}</span>
                                </div>
                                @if($session->total_tokens > 0)
                                <div class="flex items-center text-gray-400">
                                    <i class="fas fa-microchip mr-2"></i>
                                    <span>{{ number_format($session->total_tokens) }} tokens</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-start gap-2 ml-4">
                            <a href="{{ route('chat-sessions.show', $session) }}" 
                               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                <i class="fas fa-eye mr-1"></i>Open
                            </a>

                            @if(!$session->is_pinned)
                            <form action="{{ route('chat-sessions.pin', $session) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-sm"
                                        title="Pin">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </form>
                            @else
                            <form action="{{ route('chat-sessions.unpin', $session) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm"
                                        title="Unpin">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </form>
                            @endif

                            @if(!$session->is_archived)
                            <form action="{{ route('chat-sessions.archive', $session) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm"
                                        title="Archive">
                                    <i class="fas fa-archive"></i>
                                </button>
                            </form>
                            @else
                            <form action="{{ route('chat-sessions.unarchive', $session) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm"
                                        title="Restore">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            @endif

                            <form action="{{ route('chat-sessions.destroy', $session) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        onclick="return confirm('Delete this chat session?')"
                                        class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($sessions->hasPages())
        <div class="mt-8">
            {{ $sessions->links() }}
        </div>
        @endif

        @else
        <!-- Empty State -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 border border-slate-700 shadow-xl text-center">
            <i class="fas fa-comments text-gray-600 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-white mb-2">No Chat Sessions Yet</h3>
            <p class="text-gray-400 mb-6">Start your first AI chat session with your bank statements.</p>
            <a href="{{ route('chat-sessions.create') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                <i class="fas fa-plus mr-2"></i>Create First Chat Session
            </a>
        </div>
        @endif

    </div>
</x-app-layout>