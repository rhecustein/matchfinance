<x-app-layout>
    <x-slot name="header">Sub Category Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('types.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-tags mr-1"></i>Types
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <a href="{{ route('types.show', $subCategory->category->type) }}" class="text-gray-400 hover:text-white transition">
                    {{ $subCategory->category->type->name }}
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <a href="{{ route('categories.show', $subCategory->category) }}" class="text-gray-400 hover:text-white transition">
                    {{ $subCategory->category->name }}
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">{{ $subCategory->name }}</span>
            </nav>
        </div>

        {{-- Header Card --}}
        <div class="rounded-2xl p-8 mb-8 shadow-2xl" style="background: linear-gradient(135deg, {{ $subCategory->category->color }}, {{ $subCategory->category->color }}cc);">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <div class="w-24 h-24 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-layer-group text-white text-4xl"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start space-x-3 mb-2 flex-wrap gap-2">
                        <h2 class="text-3xl font-bold text-white">{{ $subCategory->name }}</h2>
                        @php
                            $priorityColor = $subCategory->priority >= 8 ? 'bg-red-500' : ($subCategory->priority >= 5 ? 'bg-yellow-500' : 'bg-blue-500');
                        @endphp
                        <span class="px-3 py-1 {{ $priorityColor }} rounded-full text-white text-sm font-semibold backdrop-blur-sm">
                            Priority: {{ $subCategory->priority }}
                        </span>
                    </div>
                    @if($subCategory->description)
                        <p class="text-white/90 mb-4">{{ $subCategory->description }}</p>
                    @endif
                    <div class="flex items-center justify-center md:justify-start space-x-3 flex-wrap gap-2">
                        <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                            <i class="fas fa-folder"></i>
                            <span>{{ $subCategory->category->name }}</span>
                        </span>
                        <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                            <i class="fas fa-tag"></i>
                            <span>{{ $subCategory->category->type->name }}</span>
                        </span>
                    </div>
                </div>
                <a href="{{ route('sub-categories.edit', $subCategory) }}" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </a>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="mb-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Total Keywords</p>
                <p class="text-white text-3xl font-bold">{{ $stats['total_keywords'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="mb-3">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Active Keywords</p>
                <p class="text-white text-3xl font-bold">{{ $stats['active_keywords'] }}</p>
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
                    <div class="w-12 h-12 bg-teal-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-double text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Verified</p>
                <p class="text-white text-3xl font-bold">{{ $stats['verified_transactions'] }}</p>
            </div>
        </div>

        {{-- Keywords List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-key mr-3 text-blue-500"></i>
                    Keywords for Matching
                </h3>
                @if($subCategory->keywords->count() > 0)
                    <a href="{{ route('keywords.index') }}?sub_category={{ $subCategory->id }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                        View All →
                    </a>
                @endif
            </div>
            
            @if($subCategory->keywords->count() > 0)
                <div class="space-y-3">
                    @foreach($subCategory->keywords as $keyword)
                        <div class="bg-slate-900/50 rounded-xl p-4 hover:bg-slate-800 transition group flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="flex flex-col items-center">
                                    <span class="text-xs text-gray-500 mb-1">Priority</span>
                                    @php
                                        $keywordPriorityColor = $keyword->priority >= 8 ? 'red' : ($keyword->priority >= 5 ? 'yellow' : 'blue');
                                    @endphp
                                    <span class="w-10 h-10 flex items-center justify-center rounded-lg bg-{{ $keywordPriorityColor }}-600/20 text-{{ $keywordPriorityColor }}-400 font-bold">
                                        {{ $keyword->priority }}
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <code class="text-white font-mono bg-slate-700/50 px-3 py-1 rounded">{{ $keyword->keyword }}</code>
                                        @if($keyword->is_regex)
                                            <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs font-semibold">
                                                Regex
                                            </span>
                                        @endif
                                        @if($keyword->case_sensitive)
                                            <span class="px-2 py-1 bg-orange-600/20 text-orange-400 rounded text-xs font-semibold">
                                                Case Sensitive
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-3 text-xs text-gray-500">
                                        <span>
                                            <i class="fas fa-circle mr-1 {{ $keyword->is_active ? 'text-green-500' : 'text-red-500' }}"></i>
                                            {{ $keyword->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span>
                                            <i class="fas fa-clock mr-1"></i>{{ $keyword->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('keywords.edit', $keyword) }}" class="p-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-key text-gray-600 text-5xl mb-4"></i>
                    <p class="text-gray-400 text-lg mb-4">No keywords yet</p>
                    <a href="{{ route('keywords.create') }}?sub_category_id={{ $subCategory->id }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        <i class="fas fa-plus"></i>
                        <span>Add Keyword</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>