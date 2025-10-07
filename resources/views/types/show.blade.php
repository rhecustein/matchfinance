<x-app-layout>
    <x-slot name="header">Type Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('types.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-tags mr-1"></i>Types
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">{{ $type->name }}</span>
            </nav>
        </div>

        {{-- Header Card --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <div class="w-24 h-24 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-tags text-white text-4xl"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-white mb-2">{{ $type->name }}</h2>
                    @if($type->description)
                        <p class="text-blue-100 mb-4">{{ $type->description }}</p>
                    @endif
                    <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                        <i class="fas fa-sort-numeric-up"></i>
                        <span>Order: {{ $type->sort_order }}</span>
                    </span>
                </div>
                <a href="{{ route('types.edit', $type) }}" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </a>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="mb-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-folder text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Categories</p>
                <p class="text-white text-3xl font-bold">{{ $stats['total_categories'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="mb-3">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Transactions</p>
                <p class="text-white text-3xl font-bold">{{ $stats['total_transactions'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="mb-3">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Verified</p>
                <p class="text-white text-3xl font-bold">{{ $stats['verified_transactions'] }}</p>
            </div>
        </div>

        {{-- Categories List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-folder mr-3 text-blue-500"></i>
                    Categories in this Type
                </h3>
                @if($type->categories->count() > 0)
                    <a href="{{ route('categories.index') }}?type_id={{ $type->id }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                        View All →
                    </a>
                @endif
            </div>
            
            @if($type->categories->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($type->categories as $category)
                        <a href="{{ route('categories.show', $category) }}" class="bg-slate-900/50 rounded-xl p-6 hover:bg-slate-800 transition group block">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $category->color }}20;">
                                    <i class="fas fa-folder" style="color: {{ $category->color }};"></i>
                                </div>
                                <span class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs font-semibold">
                                    {{ $category->sub_categories_count }} subs
                                </span>
                            </div>
                            <h4 class="text-white font-semibold mb-2 group-hover:text-blue-400 transition">
                                {{ $category->name }}
                            </h4>
                            @if($category->description)
                                <p class="text-gray-400 text-xs line-clamp-2">{{ $category->description }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-folder text-gray-600 text-5xl mb-4"></i>
                    <p class="text-gray-400 text-lg mb-4">No categories yet</p>
                    <a href="{{ route('categories.create') }}?type_id={{ $type->id }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        <i class="fas fa-plus"></i>
                        <span>Add Category</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>