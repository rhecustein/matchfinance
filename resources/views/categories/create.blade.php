<x-app-layout>
    <x-slot name="header">Categories Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Transaction Categories</h2>
                <p class="text-gray-400">Manage transaction categories and their types</p>
            </div>
            <a href="{{ route('categories.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add Category</span>
            </a>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Categories</p>
                        <p class="text-white text-3xl font-bold">{{ $categories->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-folder text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Sub Categories</p>
                        <p class="text-white text-3xl font-bold">{{ $categories->sum('sub_categories_count') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Avg per Category</p>
                        <p class="text-white text-3xl font-bold">{{ $categories->total() > 0 ? round($categories->sum('sub_categories_count') / $categories->total(), 1) : 0 }}</p>
                    </div>
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-bar text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
            <form method="GET" action="{{ route('categories.index') }}" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="type_id" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-filter mr-2"></i>Filter by Type
                    </label>
                    <select name="type_id" id="type_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                        <i class="fas fa-search"></i>
                        <span>Filter</span>
                    </button>
                    @if(request('type_id'))
                        <a href="{{ route('categories.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Categories List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">All Categories (Drag to reorder)</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4" id="categoriesList">
                    @forelse($categories as $category)
                        <div class="category-item bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition cursor-move" 
                             data-id="{{ $category->id }}" 
                             data-order="{{ $category->sort_order }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center cursor-grab active:cursor-grabbing" style="background-color: {{ $category->color }}20;">
                                        <i class="fas fa-grip-vertical" style="color: {{ $category->color }};"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color }};"></div>
                                            <h3 class="text-xl font-bold text-white">{{ $category->name }}</h3>
                                            <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-xs font-semibold">
                                                Order: {{ $category->sort_order }}
                                            </span>
                                        </div>
                                        @if($category->description)
                                            <p class="text-gray-400 text-sm mb-2">{{ $category->description }}</p>
                                        @endif
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-tag mr-1"></i>{{ $category->type->name }}
                                            </span>
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-layer-group mr-1"></i>{{ $category->sub_categories_count }} Sub Categories
                                            </span>
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-clock mr-1"></i>{{ $category->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('categories.show', $category) }}" class="p-3 bg-teal-600/20 text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('categories.edit', $category) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete({{ $category->id }}, '{{ $category->name }}', {{ $category->sub_categories_count }})" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $category->id }}" action="{{ route('categories.destroy', $category) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-folder text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No categories found</p>
                            <a href="{{ route('categories.create') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all mt-4">
                                <i class="fas fa-plus"></i>
                                <span>Add First Category</span>
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
            @if($categories->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $categories->links() }}
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
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Category?</h3>
                    <p class="text-gray-400 mb-4">Delete <strong id="deleteCategoryName" class="text-white"></strong>?</p>
                    <p id="subCategoryWarning" class="text-yellow-400 text-sm hidden">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Has <span id="subCategoryCount"></span> sub categories.
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

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        let deleteFormId = null;
        const categoriesList = document.getElementById('categoriesList');
        
        if (categoriesList) {
            new Sortable(categoriesList, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    const categories = [];
                    document.querySelectorAll('.category-item').forEach((el, index) => {
                        categories.push({
                            id: parseInt(el.dataset.id),
                            sort_order: index
                        });
                    });
                    
                    fetch('{{ route("categories.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ categories })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelectorAll('.category-item').forEach((el, index) => {
                                el.querySelector('.px-3').textContent = 'Order: ' + index;
                            });
                        }
                    });
                }
            });
        }

        function confirmDelete(id, name, count) {
            deleteFormId = id;
            document.getElementById('deleteCategoryName').textContent = name;
            
            if (count > 0) {
                document.getElementById('subCategoryCount').textContent = count;
                document.getElementById('subCategoryWarning').classList.remove('hidden');
                document.getElementById('confirmDeleteBtn').disabled = true;
                document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('subCategoryWarning').classList.add('hidden');
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