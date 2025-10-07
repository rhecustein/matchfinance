<x-app-layout>
    <x-slot name="header">Add New Type</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('types.index') }}" class="text-gray-400 hover:text-white transition"><i class="fas fa-tags mr-1"></i>Types</a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Add New</span>
            </nav>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center"><i class="fas fa-tags text-white text-2xl"></i></div>
                    <div><h2 class="text-2xl font-bold text-white">Create New Type</h2><p class="text-gray-400">Add a new transaction type</p></div>
                </div>
            </div>

            <form method="POST" action="{{ route('types.store') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-300 mb-2"><i class="fas fa-tag mr-2"></i>Type Name<span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="e.g., Outlet, Transfer, Payment">
                    @error('name')<p class="text-red-500 text-sm mt-2 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-300 mb-2"><i class="fas fa-align-left mr-2"></i>Description</label>
                    <textarea id="description" name="description" rows="4" maxlength="1000" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Brief description of this type...">{{ old('description') }}</textarea>
                    @error('description')<p class="text-red-500 text-sm mt-2 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-semibold text-gray-300 mb-2"><i class="fas fa-sort-numeric-up mr-2"></i>Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="0">
                    @error('sort_order')<p class="text-red-500 text-sm mt-2 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                    <p class="text-gray-500 text-sm mt-2"><i class="fas fa-info-circle mr-1"></i>Lower numbers appear first (you can also drag to reorder)</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center justify-center space-x-2"><i class="fas fa-save"></i><span>Create Type</span></button>
                    <a href="{{ route('types.index') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center justify-center space-x-2"><i class="fas fa-times"></i><span>Cancel</span></a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>