<x-app-layout>
    <x-slot name="header">Sub Categories Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Sub Categories</h2>
                <p class="text-gray-400">Manage sub categories with priority levels</p>
            </div>
            <a href="{{ route('sub-categories.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add Sub Category</span>
            </a>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Sub Categories</p>
                        <p class="text-white text-3xl font-bold">{{ $subCategories->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Keywords</p>
                        <p class="text-white text-3xl font-bold">{{ $subCategories->sum('keywords_count') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">High Priority</p>
                        <p class="text-white text-3xl font-bold">{{ $subCategories->where('priority', '>=', 8)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Avg Keywords</p>
                        <p class="text-white text-3xl font-bold">{{ $subCategories->total() > 0 ? round($subCategories->sum('keywords_count') / $subCategories->total(), 1) : 0 }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Bulk Actions --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
            <form method="GET" action="{{ route('sub-categories.index') }}" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="category_id" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-filter mr-2"></i>Filter by Category
                    </label>
                    <select name="category_id" id="category_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->type->name }} - {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                        <i class="fas fa-search"></i>
                        <span>Filter</span>
                    </button>
                    @if(request('category_id'))
                        <a href="{{ route('sub-categories.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </form>

            {{-- Bulk Actions --}}
            <div id="bulkActions" class="hidden mt-4 pt-4 border-t border-slate-700">
                <form method="POST" action="{{ route('sub-categories.bulk-update-priority') }}" class="flex flex-col md:flex-row gap-4 items-end">
                    @csrf
                    <input type="hidden" name="sub_category_ids" id="selectedIds">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-tasks mr-2"></i>Bulk Update Priority
                        </label>
                        <select name="priority" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-purple-500">
                            @for($i = 10; $i >= 1; $i--)
                                <option value="{{ $i }}">Priority {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                        <i class="fas fa-sync"></i>
                        <span>Update Selected</span>
                    </button>
                    <button type="button" onclick="clearSelection()" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Cancel
                    </button>
                </form>
            </div>
        </div>

        {{-- Sub Categories List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">All Sub Categories (Drag to reorder)</h3>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="selectAll" class="w-5 h-5 rounded border-slate-700 text-blue-600 focus:ring-2 focus:ring-blue-500">
                    <label for="selectAll" class="text-gray-300 text-sm font-semibold">Select All</label>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4" id="subCategoriesList">
                    @forelse($subCategories as $subCategory)
                        <div class="subcategory-item bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition cursor-move" 
                             data-id="{{ $subCategory->id }}" 
                             data-order="{{ $subCategory->sort_order }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    <input type="checkbox" class="item-checkbox w-5 h-5 rounded border-slate-700 text-blue-600 focus:ring-2 focus:ring-blue-500" value="{{ $subCategory->id }}">
                                    
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center cursor-grab active:cursor-grabbing" style="background-color: {{ $subCategory->category->color }}20;">
                                        <i class="fas fa-grip-vertical" style="color: {{ $subCategory->category->color }};"></i>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2 flex-wrap gap-2">
                                            <h3 class="text-xl font-bold text-white">{{ $subCategory->name }}</h3>
                                            
                                            @php
                                                $priorityColor = $subCategory->priority >= 8 ? 'red' : ($subCategory->priority >= 5 ? 'yellow' : 'blue');
                                            @endphp
                                            <span class="px-3 py-1 bg-{{ $priorityColor }}-600/20 text-{{ $priorityColor }}-400 rounded-full text-xs font-semibold">
                                                Priority: {{ $subCategory->priority }}
                                            </span>
                                            
                                            <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-full text-xs font-semibold">
                                                Order: {{ $subCategory->sort_order }}
                                            </span>
                                        </div>
                                        
                                        @if($subCategory->description)
                                            <p class="text-gray-400 text-sm mb-2">{{ $subCategory->description }}</p>
                                        @endif
                                        
                                        <div class="flex items-center space-x-4 flex-wrap gap-2">
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-folder mr-1" style="color: {{ $subCategory->category->color }};"></i>
                                                {{ $subCategory->category->name }}
                                            </span>
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-tag mr-1"></i>{{ $subCategory->category->type->name }}
                                            </span>
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-key mr-1"></i>{{ $subCategory->keywords_count }} Keywords
                                            </span>
                                            <span class="text-gray-500 text-xs">
                                                <i class="fas fa-clock mr-1"></i>{{ $subCategory->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('sub-categories.show', $subCategory) }}" class="p-3 bg-teal-600/20 text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sub-categories.edit', $subCategory) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete({{ $subCategory->id }}, '{{ $subCategory->name }}', {{ $subCategory->keywords_count }})" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $subCategory->id }}" action="{{ route('sub-categories.destroy', $subCategory) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-layer-group text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg">No sub categories found</p>
                            <a href="{{ route('sub-categories.create') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all mt-4">
                                <i class="fas fa-plus"></i>
                                <span>Add First Sub Category</span>
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
            @if($subCategories->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $subCategories->links() }}
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
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Sub Category?</h3>
                    <p class="text-gray-400 mb-4">Delete <strong id="deleteSubCategoryName" class="text-white"></strong>?</p>
                    <p id="keywordWarning" class="text-yellow-400 text-sm hidden">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Has <span id="keywordCount"></span> keywords.
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
        const subCategoriesList = document.getElementById('subCategoriesList');
        
        // Sortable for drag & drop reordering
        if (subCategoriesList) {
            new Sortable(subCategoriesList, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    const subCategories = [];
                    document.querySelectorAll('.subcategory-item').forEach((el, index) => {
                        subCategories.push({
                            id: parseInt(el.dataset.id),
                            sort_order: index
                        });
                    });
                    
                    fetch('{{ route("sub-categories.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ sub_categories: subCategories })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelectorAll('.subcategory-item').forEach((el, index) => {
                                const orderBadge = el.querySelector('.bg-purple-600\\/20');
                                if (orderBadge) orderBadge.textContent = 'Order: ' + index;
                            });
                        }
                    });
                }
            });
        }

        // Bulk selection
        const selectAllCheckbox = document.getElementById('selectAll');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedIdsInput = document.getElementById('selectedIds');

        selectAllCheckbox?.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });

        function updateBulkActions() {
            const selected = Array.from(itemCheckboxes).filter(cb => cb.checked);
            if (selected.length > 0) {
                bulkActions.classList.remove('hidden');
                selectedIdsInput.value = JSON.stringify(selected.map(cb => cb.value));
            } else {
                bulkActions.classList.add('hidden');
            }
        }

        function clearSelection() {
            selectAllCheckbox.checked = false;
            itemCheckboxes.forEach(cb => cb.checked = false);
            updateBulkActions();
        }

        // Delete functions
        function confirmDelete(id, name, count) {
            deleteFormId = id;
            document.getElementById('deleteSubCategoryName').textContent = name;
            
            if (count > 0) {
                document.getElementById('keywordCount').textContent = count;
                document.getElementById('keywordWarning').classList.remove('hidden');
                document.getElementById('confirmDeleteBtn').disabled = true;
                document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('keywordWarning').classList.add('hidden');
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