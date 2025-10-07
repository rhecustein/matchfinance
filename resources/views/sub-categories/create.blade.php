<x-app-layout>
    <x-slot name="header">Add New Sub Category</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('sub-categories.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-layer-group mr-1"></i>Sub Categories
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Add New</span>
            </nav>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Create New Sub Category</h2>
                        <p class="text-gray-400">Add a new sub category with priority level</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('sub-categories.store') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="category_id" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-folder mr-2"></i>Category<span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" id="category_id" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">Select Category</option>
                        @foreach($categories as $typeName => $cats)
                            <optgroup label="{{ $typeName }}">
                                @foreach($cats as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-layer-group mr-2"></i>Sub Category Name<span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" 
                           class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           placeholder="e.g., Kimia Farma, Indomaret, GoPay">
                    @error('name')
                        <p class="text-red-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-align-left mr-2"></i>Description
                    </label>
                    <textarea id="description" name="description" rows="4" maxlength="1000" 
                              class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                              placeholder="Brief description of this sub category...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="priority" class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-exclamation-circle mr-2"></i>Priority Level<span class="text-red-500">*</span>
                        </label>
                        <select name="priority" id="priority" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @for($i = 10; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ old('priority', 5) == $i ? 'selected' : '' }}>
                                    Priority {{ $i }} - {{ $i >= 8 ? 'High' : ($i >= 5 ? 'Medium' : 'Low') }}
                                </option>
                            @endfor
                        </select>
                        @error('priority')
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                        <p class="text-gray-500 text-sm mt-2">
                            <i class="fas fa-info-circle mr-1"></i>Higher priority = matched first
                        </p>
                    </div>

                    <div>
                        <label for="sort_order" class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-sort-numeric-up mr-2"></i>Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" 
                               class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               placeholder="0">
                        @error('sort_order')
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                        <p class="text-gray-500 text-sm mt-2">
                            <i class="fas fa-info-circle mr-1"></i>Display order (drag to reorder)
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center justify-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Create Sub Category</span>
                    </button>
                    <a href="{{ route('sub-categories.index') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center justify-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>

        {{-- Priority Guide --}}
        <div class="mt-6 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
            <h3 class="text-lg font-bold text-white mb-4">
                <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>Priority Guide
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-red-600/10 border border-red-600/30 rounded-xl p-4">
                    <h4 class="text-red-400 font-semibold mb-2">High Priority (8-10)</h4>
                    <p class="text-gray-400 text-sm">Very specific matches, e.g., specific store names</p>
                </div>
                <div class="bg-yellow-600/10 border border-yellow-600/30 rounded-xl p-4">
                    <h4 class="text-yellow-400 font-semibold mb-2">Medium Priority (5-7)</h4>
                    <p class="text-gray-400 text-sm">Common patterns, e.g., payment types</p>
                </div>
                <div class="bg-blue-600/10 border border-blue-600/30 rounded-xl p-4">
                    <h4 class="text-blue-400 font-semibold mb-2">Low Priority (1-4)</h4>
                    <p class="text-gray-400 text-sm">Generic matches, e.g., general categories</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>