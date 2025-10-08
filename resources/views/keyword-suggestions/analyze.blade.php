<x-app-layout>
    <x-slot name="header">Keyword Suggestions</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        {{-- Header with Actions --}}
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">🤖 AI Keyword Suggestions</h2>
                <p class="text-gray-400">Intelligent keyword extraction and categorization</p>
                <p class="text-sm text-gray-500 mt-1">
                    <i class="fas fa-file-alt mr-1"></i>{{ $bankStatement->original_filename }} | 
                    <i class="fas fa-calendar mr-1"></i>{{ $bankStatement->period_from->format('d M Y') }} - {{ $bankStatement->period_to->format('d M Y') }}
                </p>
            </div>
            <div class="flex gap-2">
                <button onclick="showFiltersModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-filter mr-2"></i>Filters
                </button>
                <a href="{{ route('keyword-suggestions.export', $bankStatement) }}" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-download mr-2"></i>Export
                </a>
                <form action="{{ route('keyword-suggestions.refresh', $bankStatement) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </form>
                <a href="{{ route('bank-statements.show', $bankStatement) }}" 
                   class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>

        {{-- Enhanced Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-4 border border-blue-500/50 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-blue-200 mb-1">Suggestions</p>
                        <p class="text-3xl font-bold text-white">{{ $stats['total_suggestions'] }}</p>
                    </div>
                    <i class="fas fa-lightbulb text-4xl text-blue-300/30"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-4 border border-green-500/50 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-green-200 mb-1">Transactions</p>
                        <p class="text-3xl font-bold text-white">{{ $stats['total_transactions'] }}</p>
                    </div>
                    <i class="fas fa-receipt text-4xl text-green-300/30"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-4 border border-purple-500/50 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-purple-200 mb-1">Coverage</p>
                        <p class="text-3xl font-bold text-white">{{ $stats['coverage_percentage'] }}%</p>
                    </div>
                    <i class="fas fa-chart-pie text-4xl text-purple-300/30"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl p-4 border border-orange-500/50 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-orange-200 mb-1">Avg Frequency</p>
                        <p class="text-3xl font-bold text-white">{{ $stats['avg_frequency'] }}x</p>
                    </div>
                    <i class="fas fa-sync-alt text-4xl text-orange-300/30"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-pink-600 to-pink-700 rounded-xl p-4 border border-pink-500/50 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-pink-200 mb-1">Total Amount</p>
                        <p class="text-xl font-bold text-white">Rp {{ number_format($stats['total_amount'], 0, ',', '.') }}</p>
                    </div>
                    <i class="fas fa-wallet text-4xl text-pink-300/30"></i>
                </div>
            </div>
        </div>

        {{-- Debit/Credit Distribution --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gradient-to-br from-red-600/20 to-red-700/20 rounded-xl p-4 border border-red-500/50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-red-300">💸 Debit Transactions</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['debit_count'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400">{{ $stats['total_transactions'] > 0 ? round(($stats['debit_count'] / $stats['total_transactions']) * 100, 1) : 0 }}%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-600/20 to-green-700/20 rounded-xl p-4 border border-green-500/50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-300">💰 Credit Transactions</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['credit_count'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400">{{ $stats['total_transactions'] > 0 ? round(($stats['credit_count'] / $stats['total_transactions']) * 100, 1) : 0 }}%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions Bar --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-4 border border-slate-700 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="selectAllSuggestions()" 
                            class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-check-double mr-1"></i>Select All
                    </button>
                    <button onclick="deselectAllSuggestions()" 
                            class="text-sm bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-times mr-1"></i>Deselect All
                    </button>
                    <span id="selectedCount" class="text-sm text-gray-400">0 selected</span>
                </div>
                <button onclick="batchCreateSelected()" 
                        id="batchCreateBtn"
                        disabled
                        class="bg-green-600 hover:bg-green-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-magic mr-2"></i>Batch Create Selected
                </button>
            </div>
        </div>

        {{-- Suggestions List --}}
        <div id="suggestionsList" class="space-y-4">
            @forelse($suggestions as $index => $suggestion)
                <div class="suggestion-card bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl {{ $suggestion['has_duplicates'] ? 'border-yellow-500/50' : '' }}" 
                     data-index="{{ $index }}">
                    
                    {{-- Selection Checkbox --}}
                    <div class="flex items-start mb-4">
                        <input type="checkbox" 
                               class="suggestion-checkbox mr-4 mt-1 w-5 h-5 rounded"
                               data-index="{{ $index }}"
                               onchange="updateSelectedCount()">
                        
                        <div class="flex-1">
                            <form action="{{ route('keyword-suggestions.create') }}" 
                                  method="POST" 
                                  class="suggestion-form"
                                  data-index="{{ $index }}">
                                @csrf
                                <input type="hidden" name="suggestion_index" value="{{ $index }}">
                                <input type="hidden" name="transaction_ids" value="{{ json_encode($suggestion['transaction_ids']) }}">
                                
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    {{-- Left: Suggestion Info --}}
                                    <div class="lg:col-span-2">
                                        {{-- Header --}}
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-white mb-2 flex items-center">
                                                    <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
                                                    <span class="text-blue-400">{{ $suggestion['suggested_keyword'] }}</span>
                                                    
                                                    {{-- Duplicate Warning --}}
                                                    @if($suggestion['has_duplicates'])
                                                        <span class="ml-3 px-3 py-1 bg-yellow-600/20 text-yellow-400 text-xs rounded-lg border border-yellow-500/50">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>Potential Duplicate
                                                        </span>
                                                    @endif

                                                    {{-- AI Recommendation --}}
                                                    @if(isset($categoryRecommendations[$suggestion['suggested_keyword']]))
                                                        <span class="ml-3 px-3 py-1 bg-purple-600/20 text-purple-400 text-xs rounded-lg border border-purple-500/50">
                                                            <i class="fas fa-robot mr-1"></i>AI: {{ $categoryRecommendations[$suggestion['suggested_keyword']]['confidence'] }}%
                                                        </span>
                                                    @endif
                                                </h3>
                                                
                                                <div class="flex items-center space-x-4 text-sm text-gray-400">
                                                    <span><i class="fas fa-hashtag mr-1"></i>{{ $suggestion['transaction_count'] }} transactions</span>
                                                    <span><i class="fas fa-sync-alt mr-1"></i>{{ $suggestion['frequency'] }}</span>
                                                </div>
                                            </div>
                                            
                                            <span class="px-3 py-1 {{ $suggestion['transaction_type'] === 'debit' ? 'bg-red-600/20 text-red-400' : 'bg-green-600/20 text-green-400' }} rounded-lg text-sm font-semibold">
                                                <i class="fas fa-{{ $suggestion['transaction_type'] === 'debit' ? 'arrow-down' : 'arrow-up' }} mr-1"></i>
                                                {{ ucfirst($suggestion['transaction_type']) }}
                                            </span>
                                        </div>

                                        {{-- Sample Description --}}
                                        <div class="bg-slate-900/50 rounded-lg p-4 mb-4 border border-slate-700">
                                            <p class="text-xs text-gray-400 mb-2">📝 Sample Description:</p>
                                            <p class="text-white font-mono text-sm">{{ $suggestion['description_sample'] }}</p>
                                        </div>

                                        {{-- Duplicate Warnings --}}
                                        @if($suggestion['has_duplicates'])
                                            <div class="bg-yellow-600/10 border border-yellow-500/30 rounded-lg p-4 mb-4">
                                                <p class="text-xs text-yellow-400 mb-2">⚠️ Similar Keywords Exist:</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($suggestion['potential_duplicates'] as $dup)
                                                        <span class="px-2 py-1 bg-yellow-600/20 text-yellow-300 rounded text-xs border border-yellow-500/50">
                                                            {{ $dup['keyword'] }} 
                                                            <span class="text-yellow-500">({{ $dup['similarity'] }}% {{ $dup['match_type'] }})</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- AI Recommendation Details --}}
                                        @if(isset($categoryRecommendations[$suggestion['suggested_keyword']]))
                                            <div class="bg-purple-600/10 border border-purple-500/30 rounded-lg p-4 mb-4">
                                                <p class="text-xs text-purple-400 mb-2">🤖 AI Recommendation:</p>
                                                <p class="text-sm text-white">
                                                    Category: <span class="font-semibold">{{ ucfirst($categoryRecommendations[$suggestion['suggested_keyword']]['category']) }}</span>
                                                </p>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    {{ $categoryRecommendations[$suggestion['suggested_keyword']]['reason'] }}
                                                </p>
                                            </div>
                                        @endif

                                        {{-- Alternative Keywords --}}
                                        @if(count($suggestion['alternative_keywords']) > 1)
                                            <div class="mb-4">
                                                <p class="text-xs text-gray-400 mb-2">💡 Alternative Keywords:</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($suggestion['alternative_keywords'] as $altKey)
                                                        <button type="button" 
                                                                onclick="selectKeyword(this, '{{ $altKey }}', {{ $index }})"
                                                                class="px-3 py-1 bg-slate-700 hover:bg-blue-600 text-gray-300 hover:text-white rounded-lg text-sm transition border border-slate-600 hover:border-blue-500">
                                                            {{ $altKey }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Stats Grid --}}
                                        <div class="grid grid-cols-3 gap-4 mb-4">
                                            <div class="bg-slate-900/30 rounded-lg p-3 border border-slate-700">
                                                <p class="text-xs text-gray-400 mb-1">💵 Avg Amount</p>
                                                <p class="text-lg font-bold text-white">Rp {{ number_format($suggestion['avg_amount'], 0, ',', '.') }}</p>
                                            </div>
                                            <div class="bg-slate-900/30 rounded-lg p-3 border border-slate-700">
                                                <p class="text-xs text-gray-400 mb-1">💰 Total Amount</p>
                                                <p class="text-lg font-bold text-white">Rp {{ number_format($suggestion['total_amount'], 0, ',', '.') }}</p>
                                            </div>
                                            <div class="bg-slate-900/30 rounded-lg p-3 border border-slate-700">
                                                <p class="text-xs text-gray-400 mb-1">📊 Frequency</p>
                                                <p class="text-lg font-bold text-white">{{ $suggestion['frequency'] }}</p>
                                            </div>
                                        </div>

                                        {{-- Preview Button --}}
                                        <button type="button" 
                                                onclick="previewTransactions({{ json_encode($suggestion['transaction_ids']) }})"
                                                class="text-sm text-blue-400 hover:text-blue-300 transition">
                                                <i class="fas fa-eye mr-1"></i>
                                                View all {{ $suggestion['transaction_count'] }} transactions
                                        </button>
                                    </div>

                                    {{-- Right: Category Selection --}}
                                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                                        <h4 class="text-sm font-semibold text-white mb-4">
                                            <i class="fas fa-tag mr-1"></i>Assign Category
                                        </h4>
                                        
                                        {{-- Keyword Input --}}
                                        <div class="mb-4">
                                            <label class="block text-xs text-gray-400 mb-2">Keyword</label>
                                            <input type="text" 
                                                   name="keyword" 
                                                   id="keyword_{{ $index }}"
                                                   value="{{ $suggestion['suggested_keyword'] }}"
                                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   required>
                                        </div>

                                        {{-- Sub Category Selection (Hierarchical) --}}
                                        <div class="mb-4">
                                            <label class="block text-xs text-gray-400 mb-2">
                                                Sub Category <span class="text-red-400">*</span>
                                            </label>
                                            <select name="sub_category_id" 
                                                    class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    required>
                                                <option value="">-- Select Sub Category --</option>
                                                @foreach($subCategories as $type)
                                                    <optgroup label="📁 {{ $type['name'] }}">
                                                        @foreach($type['categories'] as $category)
                                                            <optgroup label="  📂 {{ $category['name'] }}">
                                                                @foreach($category['sub_categories'] as $sub)
                                                                    <option value="{{ $sub->id }}">
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;📄 {{ $sub->name }}
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Priority --}}
                                        <div class="mb-4">
                                            <label class="block text-xs text-gray-400 mb-2">
                                                Priority (1-10)
                                                <i class="fas fa-info-circle text-gray-500 ml-1" title="Higher priority = checked first"></i>
                                            </label>
                                            <input type="number" 
                                                   name="priority" 
                                                   value="5" 
                                                   min="1" 
                                                   max="10"
                                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <div class="flex justify-between mt-1">
                                                <span class="text-xs text-gray-500">Low</span>
                                                <span class="text-xs text-gray-500">High</span>
                                            </div>
                                        </div>

                                        {{-- Options --}}
                                        <div class="space-y-2 mb-4">
                                            <label class="flex items-center text-sm text-gray-300 hover:text-white transition cursor-pointer">
                                                <input type="checkbox" name="case_sensitive" class="mr-2 rounded text-blue-600 focus:ring-blue-500">
                                                <span>Case Sensitive</span>
                                            </label>
                                            <label class="flex items-center text-sm text-gray-300 hover:text-white transition cursor-pointer">
                                                <input type="checkbox" name="is_regex" class="mr-2 rounded text-blue-600 focus:ring-blue-500">
                                                <span>Use Regex Pattern</span>
                                            </label>
                                            <label class="flex items-center text-sm text-gray-300 hover:text-white transition cursor-pointer">
                                                <input type="checkbox" name="apply_immediately" value="1" checked class="mr-2 rounded text-blue-600 focus:ring-blue-500">
                                                <span>Apply immediately</span>
                                            </label>
                                        </div>

                                        {{-- Description --}}
                                        <div class="mb-4">
                                            <label class="block text-xs text-gray-400 mb-2">Description (Optional)</label>
                                            <textarea name="description" 
                                                      rows="2"
                                                      class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                      placeholder="Add notes about this keyword..."></textarea>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex gap-2">
                                            <button type="submit" 
                                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm font-semibold transition">
                                                <i class="fas fa-check mr-1"></i>Create
                                            </button>
                                            <button type="button" 
                                                    onclick="dismissSuggestion({{ json_encode($suggestion['transaction_ids']) }}, {{ $index }})"
                                                    class="px-3 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm transition"
                                                    title="Dismiss">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 border border-slate-700 text-center">
                    <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
                    <h3 class="text-2xl font-bold text-white mb-2">🎉 No Suggestions Found</h3>
                    <p class="text-gray-400 mb-4">All transactions are already matched or don't have clear patterns.</p>
                    <a href="{{ route('bank-statements.show', $bankStatement) }}" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                        Back to Statement
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Preview Modal (Enhanced) --}}
    <div id="previewModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="relative bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-2xl max-w-5xl w-full border border-slate-700">
                {{-- Modal Header --}}
                <div class="flex justify-between items-center p-6 border-b border-slate-700">
                    <div>
                        <h3 class="text-xl font-bold text-white">📊 Transaction Preview</h3>
                        <p class="text-sm text-gray-400 mt-1" id="previewStats"></p>
                    </div>
                    <button onclick="closePreviewModal()" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                {{-- Modal Body --}}
                <div class="p-6">
                    <div id="previewContent" class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Modal --}}
    <div id="filtersModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-700">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-filter mr-2"></i>Filter Suggestions
                    </h3>
                    
                    <form action="{{ route('keyword-suggestions.analyze', $bankStatement) }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-2">Min Frequency</label>
                                <input type="number" name="min_frequency" value="{{ $filters['min_frequency'] ?? 2 }}" min="1"
                                       class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-2">Min Amount</label>
                                <input type="number" name="min_amount" value="{{ $filters['min_amount'] ?? 0 }}" min="0"
                                       class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Transaction Type</label>
                            <select name="transaction_type" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                                <option value="">All</option>
                                <option value="debit" {{ ($filters['transaction_type'] ?? '') === 'debit' ? 'selected' : '' }}>Debit Only</option>
                                <option value="credit" {{ ($filters['transaction_type'] ?? '') === 'credit' ? 'selected' : '' }}>Credit Only</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Sort By</label>
                            <select name="sort_by" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white">
                                <option value="frequency" {{ ($filters['sort_by'] ?? 'frequency') === 'frequency' ? 'selected' : '' }}>Frequency</option>
                                <option value="amount" {{ ($filters['sort_by'] ?? '') === 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="count" {{ ($filters['sort_by'] ?? '') === 'count' ? 'selected' : '' }}>Count</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="include_similar" value="1" {{ ($filters['include_similar'] ?? true) ? 'checked' : '' }} class="mr-2 rounded">
                            <label class="text-sm text-gray-300">Include similar patterns</label>
                        </div>
                        
                        <div class="flex gap-2 pt-4">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-semibold">
                                Apply Filters
                            </button>
                            <button type="button" onclick="closeFiltersModal()" class="px-6 bg-slate-700 hover:bg-slate-600 text-white py-2 rounded-lg">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.7);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        let selectedSuggestions = new Set();

        function selectKeyword(button, keyword, index) {
            document.getElementById('keyword_' + index).value = keyword;
            
            // Highlight selected button
            button.parentElement.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-500');
                btn.classList.add('bg-slate-700', 'text-gray-300', 'border-slate-600');
            });
            button.classList.remove('bg-slate-700', 'text-gray-300', 'border-slate-600');
            button.classList.add('bg-blue-600', 'text-white', 'border-blue-500');
        }

        function updateSelectedCount() {
            selectedSuggestions.clear();
            document.querySelectorAll('.suggestion-checkbox:checked').forEach(cb => {
                selectedSuggestions.add(parseInt(cb.dataset.index));
            });
            
            const count = selectedSuggestions.size;
            document.getElementById('selectedCount').textContent = `${count} selected`;
            document.getElementById('batchCreateBtn').disabled = count === 0;
        }

        function selectAllSuggestions() {
            document.querySelectorAll('.suggestion-checkbox').forEach(cb => {
                cb.checked = true;
            });
            updateSelectedCount();
        }

        function deselectAllSuggestions() {
            document.querySelectorAll('.suggestion-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateSelectedCount();
        }

        async function batchCreateSelected() {
            if (selectedSuggestions.size === 0) {
                alert('Please select at least one suggestion');
                return;
            }

            if (!confirm(`Create ${selectedSuggestions.size} keywords?`)) return;

            const suggestions = [];
            selectedSuggestions.forEach(index => {
                const form = document.querySelector(`.suggestion-form[data-index="${index}"]`);
                const formData = new FormData(form);
                
                suggestions.push({
                    keyword: formData.get('keyword'),
                    sub_category_id: formData.get('sub_category_id'),
                    priority: formData.get('priority') || 5,
                    transaction_ids: JSON.parse(formData.get('transaction_ids')),
                    is_regex: formData.get('is_regex') === 'on',
                    case_sensitive: formData.get('case_sensitive') === 'on',
                });
            });

            try {
                const response = await fetch('{{ route('keyword-suggestions.batch-create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ suggestions })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ ${result.created} keywords created successfully!`);
                    window.location.reload();
                } else {
                    alert('❌ Batch creation failed: ' + result.message);
                }
            } catch (error) {
                console.error('Batch create error:', error);
                alert('Error creating keywords: ' + error.message);
            }
        }

        async function dismissSuggestion(transactionIds, index) {
            if (!confirm('Are you sure you want to dismiss this suggestion?')) return;

            try {
                const response = await fetch('{{ route('keyword-suggestions.dismiss') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ 
                        transaction_ids: transactionIds,
                        reason: 'User dismissed from UI'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Remove the card with animation
                    const card = document.querySelector(`.suggestion-card[data-index="${index}"]`);
                    card.style.transition = 'all 0.3s ease-out';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(-100px)';
                    
                    setTimeout(() => {
                        card.remove();
                        updateSelectedCount();
                        
                        // Check if no more suggestions
                        if (document.querySelectorAll('.suggestion-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    alert('Failed to dismiss: ' + result.message);
                }
            } catch (error) {
                console.error('Dismiss error:', error);
                alert('Error dismissing suggestion: ' + error.message);
            }
        }

        async function previewTransactions(transactionIds) {
            try {
                const response = await fetch('{{ route('keyword-suggestions.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ transaction_ids: transactionIds })
                });

                const result = await response.json();

                if (result.success) {
                    const stats = result.data.statistics;
                    
                    // Update stats
                    document.getElementById('previewStats').innerHTML = `
                        ${stats.count} transactions | 
                        ${stats.frequency.description} | 
                        Avg: Rp ${new Intl.NumberFormat('id-ID').format(stats.avg_amount)}
                    `;

                    // Populate content
                    const content = document.getElementById('previewContent');
                    content.innerHTML = result.data.transactions.map(t => `
                        <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <p class="text-white font-medium">${t.description}</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        ${new Date(t.date).toLocaleDateString('id-ID')} | ${t.bank}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold ${t.type === 'debit' ? 'text-red-400' : 'text-green-400'}">
                                        ${t.type === 'debit' ? '-' : '+'} Rp ${new Intl.NumberFormat('id-ID').format(t.amount)}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Balance: Rp ${new Intl.NumberFormat('id-ID').format(t.balance)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `).join('');

                    // Show modal
                    document.getElementById('previewModal').classList.remove('hidden');
                } else {
                    alert('Failed to load preview: ' + result.message);
                }
            } catch (error) {
                console.error('Preview error:', error);
                alert('Error loading preview: ' + error.message);
            }
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
        }

        function showFiltersModal() {
            document.getElementById('filtersModal').classList.remove('hidden');
        }

        function closeFiltersModal() {
            document.getElementById('filtersModal').classList.add('hidden');
        }

        // Close modals on outside click
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreviewModal();
            }
        });

        document.getElementById('filtersModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFiltersModal();
            }
        });

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreviewModal();
                closeFiltersModal();
            }
        });
    </script>
    @endpush
</x-app-layout>