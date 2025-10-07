<x-app-layout>
    <x-slot name="header">Edit Category</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('categories.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-folder mr-1"></i>Categories
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Edit {{ $category->name }}</span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="mb-8">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, {{ $category->color }}, {{ $category->color }}cc);">
                                <i class="fas fa-folder text-white text-2xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">Edit Category</h2>
                                <p class="text-gray-400">Update category information</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        
                        <div>
                            <label for="type_id" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-tag mr-2"></i>Type<span class="text-red-500">*</span>
                            </label>
                            <select name="type_id" id="type_id" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ old('type_id', $category->type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type_id')
                                <p class="text-red-500 text-sm mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-folder mr-2"></i>Category Name<span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="100" 
                                   class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            @error('name')
                                <p class="text-red-500 text-sm mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-align-left mr-2"></i>Description
                            </label>
                            <textarea id="description" name="description" rows="4" maxlength="1000" 
                                      class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-sm mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="color" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-palette mr-2"></i>Color
                            </label>
                            <div class="flex items-center space-x-4">
                                <input type="color" id="color" name="color" value="{{ old('color', $category->color) }}" 
                                       class="w-16 h-12 rounded-lg cursor-pointer border-2 border-slate-700">
                                <input type="text" id="color_text" value="{{ old('color', $category->color) }}" 
                                       class="flex-1 px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" readonly>
                            </div>
                            @error('color')
                                <p class="text-red-500 text-sm mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="sort_order" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-sort-numeric-up mr-2"></i>Sort Order
                            </label>
                            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" 
                                   class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            @error('sort_order')
                                <p class="text-red-500 text-sm mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 pt-6">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg flex items-center justify-center space-x-2">
                                <i class="fas fa-save"></i>
                                <span>Update Category</span>
                            </button>
                            <a href="{{ route('categories.index') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center justify-center space-x-2">
                                <i class="fas fa-times"></i>
                                <span>Cancel</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Category Info</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm py-3 border-b border-slate-700">
                            <span class="text-gray-400">Type</span>
                            <span class="text-white font-semibold">{{ $category->type->name }}</span>
                        </div>
                        <div class="flex justify-between text-sm py-3 border-b border-slate-700">
                            <span class="text-gray-400">Sub Categories</span>
                            <span class="text-white font-semibold">{{ $category->subCategories()->count() }}</span>
                        </div>
                        <div class="flex justify-between text-sm py-3 border-b border-slate-700">
                            <span class="text-gray-400">Created</span>
                            <span class="text-white font-semibold">{{ $category->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between text-sm py-3">
                            <span class="text-gray-400">Updated</span>
                            <span class="text-white font-semibold">{{ $category->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                @if($category->subCategories()->count() == 0)
                    <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-red-400 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                        </h3>
                        <p class="text-gray-400 text-sm mb-4">Delete this category permanently.</p>
                        <button onclick="confirmDelete()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl font-semibold transition-all">
                            <i class="fas fa-trash mr-2"></i>Delete Category
                        </button>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-yellow-400 mb-4">
                            <i class="fas fa-info-circle mr-2"></i>Cannot Delete
                        </h3>
                        <p class="text-gray-400 text-sm">Has {{ $category->subCategories()->count() }} sub categories.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($category->subCategories()->count() == 0)
        <div id="deleteModal" class="hidden fixed inset-0 z-50">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
                <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Delete Category?</h3>
                        <p class="text-gray-400">Delete <strong class="text-white">{{ $category->name }}</strong>?</p>
                    </div>
                    <form method="POST" action="{{ route('categories.destroy', $category) }}">
                        @csrf
                        @method('DELETE')
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        // Color picker sync
        const colorPicker = document.getElementById('color');
        const colorText = document.getElementById('color_text');
        
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
        });

        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</x-app-layout>