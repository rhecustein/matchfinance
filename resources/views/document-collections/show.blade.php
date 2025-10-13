<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('document-collections.index') }}" 
                   class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Collection Details
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Main Info Header -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden mb-8">
            <!-- Header -->
            <div class="p-6 bg-gradient-to-r from-purple-600 to-blue-600">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-3xl font-bold text-white mb-2">{{ $documentCollection->name }}</h3>
                        @if($documentCollection->description)
                        <p class="text-blue-100 max-w-2xl">{{ $documentCollection->description }}</p>
                        @endif
                    </div>
                    @if($documentCollection->is_active)
                    <span class="px-4 py-2 bg-green-500 text-white rounded-full text-sm font-bold">
                        Active
                    </span>
                    @else
                    <span class="px-4 py-2 bg-gray-500 text-white rounded-full text-sm font-bold">
                        Inactive
                    </span>
                    @endif
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Documents</p>
                    <p class="text-white text-3xl font-bold">{{ $documentCollection->document_count }}</p>
                </div>
                
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Total Transactions</p>
                    <p class="text-white text-3xl font-bold">{{ number_format($documentCollection->total_transactions ?? 0) }}</p>
                </div>

                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Chat Sessions</p>
                    <p class="text-white text-3xl font-bold">{{ $documentCollection->chat_count }}</p>
                </div>

                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Last Used</p>
                    <p class="text-white text-lg font-bold">
                        {{ $documentCollection->last_used_at ? $documentCollection->last_used_at->diffForHumans() : 'Never' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Bank Statements/Documents -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-xl font-bold text-white">
                            <i class="fas fa-file-invoice text-green-500 mr-2"></i>
                            Bank Statements ({{ $documentCollection->items->count() }})
                        </h4>
                        <a href="{{ route('document-collections.edit', $documentCollection) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                            <i class="fas fa-plus mr-2"></i>Add More
                        </a>
                    </div>

                    @if($documentCollection->items->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($documentCollection->items as $item)
                        <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-700 hover:border-blue-500 transition">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h5 class="text-white font-semibold">
                                        {{ $item->bankStatement->bank->name ?? 'Unknown Bank' }}
                                    </h5>
                                    <p class="text-gray-400 text-sm">
                                        {{ $item->bankStatement->period_start->format('M Y') }} - 
                                        {{ $item->bankStatement->period_end->format('M Y') }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($item->knowledge_status === 'ready') bg-green-900/30 text-green-400
                                    @elseif($item->knowledge_status === 'processing') bg-yellow-900/30 text-yellow-400
                                    @elseif($item->knowledge_status === 'failed') bg-red-900/30 text-red-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ ucfirst($item->knowledge_status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Transactions</p>
                                    <p class="text-white font-semibold">
                                        {{ number_format($item->bankStatement->total_transactions) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Debit</p>
                                    <p class="text-red-400 font-semibold">
                                        Rp {{ number_format($item->bankStatement->total_debit_amount, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Credit</p>
                                    <p class="text-green-400 font-semibold">
                                        Rp {{ number_format($item->bankStatement->total_credit_amount, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center gap-2">
                                <a href="{{ route('bank-statements.show', $item->bankStatement) }}" 
                                   class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition font-semibold">
                                    <i class="fas fa-eye mr-1"></i>View Statement
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-600 text-4xl mb-3"></i>
                        <p class="text-gray-400">No bank statements in this collection</p>
                    </div>
                    @endif
                </div>

                <!-- Recent Chat Sessions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-xl font-bold text-white">
                            <i class="fas fa-comments text-blue-500 mr-2"></i>
                            Recent Chat Sessions
                        </h4>
                    </div>

                    @if($documentCollection->chatSessions->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($documentCollection->chatSessions as $chat)
                        <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-700">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h5 class="text-white font-semibold">{{ $chat->title }}</h5>
                                    <p class="text-gray-400 text-sm">by {{ $chat->user->name }}</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($chat->is_active) bg-green-900/30 text-green-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ $chat->is_active ? 'Active' : 'Ended' }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-400">
                                    <i class="fas fa-message mr-1"></i>
                                    {{ $chat->message_count }} messages
                                </span>
                                <span class="text-gray-400">
                                    {{ $chat->last_activity_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-comments text-gray-600 text-4xl mb-3"></i>
                        <p class="text-gray-400 mb-4">No chat sessions yet</p>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                            <i class="fas fa-plus mr-2"></i>Start Chat Session
                        </button>
                    </div>
                    @endif
                </div>

            </div>

            <!-- Right Column - Actions & Info -->
            <div class="space-y-8">
                
                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Quick Actions
                    </h4>

                    <div class="space-y-3">
                        <a href="{{ route('document-collections.edit', $documentCollection) }}" 
                           class="block w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold text-center">
                            <i class="fas fa-edit mr-2"></i>Edit Collection
                        </a>

                        <form action="{{ route('document-collections.toggle-active', $documentCollection) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full px-4 py-3 {{ $documentCollection->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition font-semibold">
                                <i class="fas fa-{{ $documentCollection->is_active ? 'pause' : 'play' }} mr-2"></i>
                                {{ $documentCollection->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        <form action="{{ route('document-collections.process', $documentCollection) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                <i class="fas fa-robot mr-2"></i>Process for AI
                            </button>
                        </form>

                        @if($documentCollection->chatSessions->where('is_active', true)->isEmpty())
                        <form action="{{ route('document-collections.destroy', $documentCollection) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('Are you sure you want to delete this collection?')"
                                    class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                                <i class="fas fa-trash mr-2"></i>Delete Collection
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Collection Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Information
                    </h4>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Created By</label>
                            <p class="text-white font-semibold">{{ $documentCollection->user->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Created At</label>
                            <p class="text-white">{{ $documentCollection->created_at->format('d M Y, H:i') }}</p>
                            <p class="text-gray-400 text-sm">{{ $documentCollection->created_at->diffForHumans() }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Last Updated</label>
                            <p class="text-white">{{ $documentCollection->updated_at->format('d M Y, H:i') }}</p>
                            <p class="text-gray-400 text-sm">{{ $documentCollection->updated_at->diffForHumans() }}</p>
                        </div>

                        @if($documentCollection->last_used_at)
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Last Used</label>
                            <p class="text-white">{{ $documentCollection->last_used_at->format('d M Y, H:i') }}</p>
                            <p class="text-gray-400 text-sm">{{ $documentCollection->last_used_at->diffForHumans() }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">UUID</label>
                            <p class="text-white text-xs font-mono bg-slate-900/50 p-2 rounded">{{ $documentCollection->uuid }}</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                        Statistics
                    </h4>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400">Ready Items</span>
                            <span class="text-green-400 font-bold">
                                {{ $documentCollection->items->where('knowledge_status', 'ready')->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400">Processing Items</span>
                            <span class="text-yellow-400 font-bold">
                                {{ $documentCollection->items->where('knowledge_status', 'processing')->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400">Failed Items</span>
                            <span class="text-red-400 font-bold">
                                {{ $documentCollection->items->where('knowledge_status', 'failed')->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-gray-400">Active Chats</span>
                            <span class="text-blue-400 font-bold">
                                {{ $documentCollection->chatSessions->where('is_active', true)->count() }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>