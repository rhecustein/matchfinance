<x-app-layout>
    <x-slot name="header">Types Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Transaction Types</h2>
                <p class="text-gray-400">Manage transaction type categories</p>
            </div>
            <a href="{{ route('types.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i><span>Add Type</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div><p class="text-gray-400 text-sm mb-1">Total Types</p><p class="text-white text-3xl font-bold">{{ $types->total() }}</p></div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center"><i class="fas fa-tags text-white text-xl"></i></div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div><p class="text-gray-400 text-sm mb-1">Total Categories</p><p class="text-white text-3xl font-bold">{{ $types->sum('categories_count') }}</p></div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center"><i class="fas fa-folder text-white text-xl"></i></div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div><p class="text-gray-400 text-sm mb-1">Avg per Type</p><p class="text-white text-3xl font-bold">{{ $types->total() > 0 ? round($types->sum('categories_count') / $types->total(), 1) : 0 }}</p></div>
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center"><i class="fas fa-chart-pie text-white text-xl"></i></div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden" id="typesContainer">
            <div class="px-6 py-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">All Types (Drag to reorder)</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4" id="typesList">
                    @forelse($types as $index => $type)
                        <div class="type-item bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition cursor-move" data-id="{{ $type->id }}" data-order="{{ $type->sort_order }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center cursor-grab active:cursor-grabbing">
                                        <i class="fas fa-grip-vertical text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-xl font-bold text-white">{{ $type->name }}</h3>
                                            <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-xs font-semibold">Order: {{ $type->sort_order }}</span>
                                        </div>
                                        @if($type->description)
                                            <p class="text-gray-400 text-sm">{{ $type->description }}</p>
                                        @endif
                                        <div class="flex items-center space-x-4 mt-3">
                                            <span class="text-gray-500 text-xs"><i class="fas fa-folder mr-1"></i>{{ $type->categories_count }} Categories</span>
                                            <span class="text-gray-500 text-xs"><i class="fas fa-clock mr-1"></i>{{ $type->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('types.show', $type) }}" class="p-3 bg-teal-600/20 text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg transition-all"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('types.edit', $type) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all"><i class="fas fa-edit"></i></a>
                                    <button onclick="confirmDelete({{ $type->id }}, '{{ $type->name }}', {{ $type->categories_count }})" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all"><i class="fas fa-trash"></i></button>
                                    <form id="delete-form-{{ $type->id }}" action="{{ route('types.destroy', $type) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-tags text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No types found</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @if($types->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">{{ $types->links() }}</div>
            @endif
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i></div>
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Type?</h3>
                    <p class="text-gray-400 mb-4">Delete <strong id="deleteTypeName" class="text-white"></strong>?</p>
                    <p id="categoryWarning" class="text-yellow-400 text-sm hidden"><i class="fas fa-exclamation-triangle mr-1"></i>Has <span id="categoryCount"></span> categories.</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">Cancel</button>
                    <button id="confirmDeleteBtn" onclick="submitDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        let deleteFormId = null;
        const typesList = document.getElementById('typesList');
        
        if (typesList) {
            new Sortable(typesList, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    const types = [];
                    document.querySelectorAll('.type-item').forEach((el, index) => {
                        types.push({ id: parseInt(el.dataset.id), sort_order: index });
                    });
                    
                    fetch('{{ route("types.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ types })
                    }).then(response => response.json())
                      .then(data => {
                          if (data.success) {
                              document.querySelectorAll('.type-item').forEach((el, index) => {
                                  el.querySelector('.px-3').textContent = 'Order: ' + index;
                              });
                          }
                      });
                }
            });
        }

        function confirmDelete(id, name, count) {
            deleteFormId = id;
            document.getElementById('deleteTypeName').textContent = name;
            if (count > 0) {
                document.getElementById('categoryCount').textContent = count;
                document.getElementById('categoryWarning').classList.remove('hidden');
                document.getElementById('confirmDeleteBtn').disabled = true;
                document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('categoryWarning').classList.add('hidden');
                document.getElementById('confirmDeleteBtn').disabled = false;
                document.getElementById('confirmDeleteBtn').classList.remove('opacity-50', 'cursor-not-allowed');
            }
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteFormId = null;
        }

        function submitDelete() {
            if (deleteFormId && !document.getElementById('confirmDeleteBtn').disabled) {
                document.getElementById('delete-form-' + deleteFormId).submit();
            }
        }
    </script>
</x-app-layout>