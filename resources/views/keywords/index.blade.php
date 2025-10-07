<x-app-layout>
    <x-slot name="header">Keywords Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Matching Keywords</h2>
                <p class="text-gray-400">Manage keywords for automatic transaction matching</p>
            </div>
            <a href="{{ route('keywords.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add Keyword</span>
            </a>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Keywords</p>
                        <p class="text-white text-3xl font-bold">{{ $keywords->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
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
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Regex Patterns</p>
                        <p class="text-white text-3xl font-bold">{{ $keywords->where('is_regex', true)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-code text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">High Priority</p>
                        <p class="text-white text-3xl font-bold">{{ $keywords->where('priority', '>=', 8)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Keywords List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">All Keywords</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($keywords as $keyword)
                        <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    {{-- Priority Badge --}}
                                    <div class="flex flex-col items-center">
                                        <span class="text-xs text-gray-500 mb-1">Priority</span>
                                        @php
                                            $priorityColor = $keyword->priority >= 8 ? 'red' : ($keyword->priority >= 5 ? 'yellow' : 'blue');
                                        @endphp
                                        <span class="w-12 h-12 flex items-center justify-center rounded-lg bg-{{ $priorityColor }}-600/20 text-{{ $priorityColor }}-400 font-bold text-lg">
                                            {{ $keyword->priority }}
                                        </span>
                                    </div>

                                    {{-- Keyword Info --}}
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2 flex-wrap gap-2">
                                            <code class="text-white font-mono text-lg bg-slate-700/50 px-4 py-1 rounded">
                                                {{ $keyword->keyword }}
                                            </code>
                                            
                                            @if($keyword->is_regex)
                                                <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs font-semibold">
                                                    <i class="fas fa-code mr-1"></i>Regex
                                                </span>
                                            @endif
                                            
                                            @if($keyword->case_sensitive)
                                                <span class="px-2 py-1 bg-orange-600/20 text-orange-400 rounded text-xs font-semibold">
                                                    <i class="fas fa-font mr-1"></i>Case Sensitive
                                                </span>
                                            @endif
                                            
                                            @if($keyword->is_active)
                                                <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold">
                                                    <i class="fas fa-check-circle mr-1"></i>Active
                                                </span>
                                            @else
                                                <span class="px-2 py-1 bg-red-600/20 text-red-400 rounded text-xs font-semibold">
                                                    <i class="fas fa-times-circle mr-1"></i>Inactive
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-400">
                                            <span>
                                                <i class="fas fa-layer-group mr-1" style="color: {{ $keyword->subCategory->category->color }};"></i>
                                                {{ $keyword->subCategory->name }}
                                            </span>
                                            <span>
                                                <i class="fas fa-folder mr-1"></i>{{ $keyword->subCategory->category->name }}
                                            </span>
                                            <span>
                                                <i class="fas fa-tag mr-1"></i>{{ $keyword->subCategory->category->type->name }}
                                            </span>
                                            <span>
                                                <i class="fas fa-clock mr-1"></i>{{ $keyword->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('keywords.edit', $keyword) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete({{ $keyword->id }}, '{{ $keyword->keyword }}')" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $keyword->id }}" action="{{ route('keywords.destroy', $keyword) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-key text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No keywords found</p>
                            <a href="{{ route('keywords.create') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all mt-4">
                                <i class="fas fa-plus"></i>
                                <span>Add First Keyword</span>
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
            @if($keywords->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $keywords->links() }}
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
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Keyword?</h3>
                    <p class="text-gray-400 mb-4">Delete keyword <code id="deleteKeywordName" class="text-white bg-slate-700 px-2 py-1 rounded"></code>?</p>
                    <p class="text-yellow-400 text-sm">
                        <i class="fas fa-info-circle mr-1"></i>This will clear the matching cache
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Cancel
                    </button>
                    <button id="confirmDeleteBtn" onclick="submitDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteFormId = null;

        function confirmDelete(id, keyword) {
            deleteFormId = id;
            document.getElementById('deleteKeywordName').textContent = keyword;
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
    </script>
</x-app-layout>