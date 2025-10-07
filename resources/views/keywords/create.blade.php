<x-app-layout>
    <x-slot name="header">Add New Keyword</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('keywords.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-key mr-1"></i>Keywords
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Add New</span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Form --}}
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="mb-8">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-key text-white text-2xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">Create New Keyword</h2>
                                <p class="text-gray-400">Add a keyword for transaction matching</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('keywords.store') }}" class="space-y-6">
                        @csrf
                        
                        {{-- Quick Filters --}}
                        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700">
                            <label class="block text-sm font-semibold text-gray-300 mb-3">
                                <i class="fas fa-filter mr-2"></i>Filter by Type
                            </label>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" onclick="filterByType('all', this)" class="type-filter-btn active px-3 py-2 rounded-lg text-xs font-semibold transition-all bg-blue-600 text-white">
                                    <i class="fas fa-list mr-1"></i>All
                                </button>
                                @foreach(\App\Models\Type::orderBy('sort_order')->orderBy('name')->get() as $type)
                                    <button type="button" onclick="filterByType('{{ $type->id }}', this)" class="type-filter-btn px-3 py-2 rounded-lg text-xs font-semibold transition-all bg-slate-700 text-gray-300 hover:bg-slate-600">
                                        {{ $type->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Live Search Sub Categories --}}
                        <div>
                            <label for="sub_category_search" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-search mr-2"></i>Search Sub Category<span class="text-red-500">*</span>
                            </label>
                            
                            {{-- Search Input --}}
                            <div class="relative mb-3">
                                <input type="text" id="sub_category_search" 
                                       class="w-full pl-10 pr-10 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                       placeholder="Type to search sub categories..."
                                       autocomplete="off"
                                       oninput="searchSubCategories()">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                                <button type="button" onclick="clearSearch()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-white transition hidden" id="clearSearchBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            {{-- Results Info --}}
                            <div class="flex items-center justify-between mb-2 text-xs">
                                <span id="searchInfo" class="text-gray-400">
                                    <i class="fas fa-info-circle mr-1"></i>All sub categories shown
                                </span>
                                <span id="searchCount" class="text-blue-400 font-semibold"></span>
                            </div>

                            {{-- Hidden Select for Form Submission --}}
                            <input type="hidden" name="sub_category_id" id="sub_category_id" value="{{ old('sub_category_id', $selectedSubCategoryId ?? '') }}" required>
                            
                            {{-- Results List (Scrollable) --}}
                            <div class="border border-slate-700 rounded-xl bg-slate-900/50 max-h-80 overflow-y-auto" id="subCategoryList">
                                @foreach($subCategories as $categoryName => $subs)
                                    <div class="sub-category-group" data-category="{{ $categoryName }}">
                                        <div class="px-4 py-2 bg-slate-800/50 border-b border-slate-700 sticky top-0">
                                            <span class="text-xs font-semibold text-gray-400">
                                                <i class="fas fa-folder mr-2"></i>{{ $categoryName }}
                                            </span>
                                        </div>
                                        @foreach($subs as $subCategory)
                                            <div class="sub-category-item px-4 py-3 hover:bg-slate-800 cursor-pointer transition border-b border-slate-700/50 last:border-0"
                                                 data-id="{{ $subCategory->id }}"
                                                 data-type="{{ $subCategory->category->type_id }}"
                                                 data-priority="{{ $subCategory->priority }}"
                                                 data-name="{{ strtolower($subCategory->name) }}"
                                                 data-category="{{ strtolower($categoryName) }}"
                                                 data-type-name="{{ strtolower($subCategory->category->type->name) }}"
                                                 onclick="selectSubCategory({{ $subCategory->id }}, '{{ $subCategory->name }}', '{{ $categoryName }}', {{ $subCategory->priority }}, this)">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center space-x-2 mb-1">
                                                            @php
                                                                $priorityIcon = $subCategory->priority >= 8 ? '🔴' : ($subCategory->priority >= 5 ? '🟡' : '🔵');
                                                            @endphp
                                                            <span class="text-lg">{{ $priorityIcon }}</span>
                                                            <span class="text-white font-semibold">{{ $subCategory->name }}</span>
                                                            <span class="px-2 py-0.5 bg-blue-600/20 text-blue-400 rounded text-xs">
                                                                P:{{ $subCategory->priority }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-400">
                                                            <i class="fas fa-tag mr-1"></i>{{ $subCategory->category->type->name }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-2">
                                                        <i class="fas fa-check-circle text-green-500 hidden selected-icon"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>

                            {{-- Selected Info Panel --}}
                            <div id="selectedPanel" class="hidden mt-3 p-4 bg-blue-600/10 border border-blue-600/30 rounded-xl">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <i class="fas fa-check-circle text-blue-400 text-xl mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-blue-400 font-semibold text-sm mb-1">Selected:</p>
                                            <p class="text-white font-semibold" id="selectedName"></p>
                                            <p class="text-gray-400 text-xs mt-1" id="selectedPath"></p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="clearSelection()" class="text-gray-400 hover:text-white transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            @error('sub_category_id')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Keyword Input --}}
                        <div>
                            <label for="keyword" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-font mr-2"></i>Keyword Pattern<span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="keyword" name="keyword" value="{{ old('keyword') }}" required maxlength="255" 
                                   class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono text-lg" 
                                   placeholder="e.g., INDOMARET, GOPAY, .*QRIS.*"
                                   oninput="updateLiveTest()">
                            @error('keyword')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Priority Selection (Visual Buttons) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-3">
                                <i class="fas fa-exclamation-circle mr-2"></i>Priority Level<span class="text-red-500">*</span>
                            </label>
                            
                            <div class="grid grid-cols-5 gap-3">
                                @for($i = 10; $i >= 6; $i--)
                                    <button type="button" onclick="setPriority({{ $i }}, this)" 
                                            class="priority-btn p-4 rounded-xl border-2 transition-all {{ $i >= 8 ? 'border-red-500/30 hover:border-red-500 hover:bg-red-500/20' : 'border-yellow-500/30 hover:border-yellow-500 hover:bg-yellow-500/20' }}"
                                            data-priority="{{ $i }}">
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-white">{{ $i }}</div>
                                            <div class="text-xs text-gray-400 mt-1">{{ $i >= 8 ? 'High' : 'Medium' }}</div>
                                        </div>
                                    </button>
                                @endfor
                            </div>
                            
                            <div class="grid grid-cols-5 gap-3 mt-3">
                                @for($i = 5; $i >= 1; $i--)
                                    <button type="button" onclick="setPriority({{ $i }}, this)" 
                                            class="priority-btn p-4 rounded-xl border-2 transition-all {{ $i >= 5 ? 'border-yellow-500/30 hover:border-yellow-500 hover:bg-yellow-500/20' : 'border-blue-500/30 hover:border-blue-500 hover:bg-blue-500/20' }}"
                                            data-priority="{{ $i }}">
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-white">{{ $i }}</div>
                                            <div class="text-xs text-gray-400 mt-1">{{ $i >= 5 ? 'Medium' : 'Low' }}</div>
                                        </div>
                                    </button>
                                @endfor
                            </div>
                            
                            <input type="hidden" name="priority" id="priority" value="{{ old('priority', 5) }}">
                            <p class="text-gray-500 text-sm mt-3">
                                <i class="fas fa-info-circle mr-1"></i>Higher priority = checked first in matching
                            </p>
                        </div>

                        {{-- Options (Clean 3-column) --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-purple-600/10 border border-purple-600/30 rounded-xl hover:bg-purple-600/20 transition cursor-pointer" onclick="document.getElementById('is_regex').click()">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" id="is_regex" name="is_regex" value="1" {{ old('is_regex') ? 'checked' : '' }} 
                                           class="w-5 h-5 rounded border-purple-700 text-purple-600 focus:ring-2 focus:ring-purple-500"
                                           onchange="updateLiveTest()">
                                    <div>
                                        <label for="is_regex" class="text-white font-semibold cursor-pointer block">
                                            <i class="fas fa-code mr-2 text-purple-400"></i>Regex
                                        </label>
                                        <p class="text-gray-400 text-xs">Pattern matching</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-orange-600/10 border border-orange-600/30 rounded-xl hover:bg-orange-600/20 transition cursor-pointer" onclick="document.getElementById('case_sensitive').click()">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" id="case_sensitive" name="case_sensitive" value="1" {{ old('case_sensitive') ? 'checked' : '' }} 
                                           class="w-5 h-5 rounded border-orange-700 text-orange-600 focus:ring-2 focus:ring-orange-500"
                                           onchange="updateLiveTest()">
                                    <div>
                                        <label for="case_sensitive" class="text-white font-semibold cursor-pointer block">
                                            <i class="fas fa-font mr-2 text-orange-400"></i>Case Sensitive
                                        </label>
                                        <p class="text-gray-400 text-xs">Exact case match</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-green-600/10 border border-green-600/30 rounded-xl hover:bg-green-600/20 transition cursor-pointer" onclick="document.getElementById('is_active').click()">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                                           class="w-5 h-5 rounded border-green-700 text-green-600 focus:ring-2 focus:ring-green-500">
                                    <div>
                                        <label for="is_active" class="text-white font-semibold cursor-pointer block">
                                            <i class="fas fa-check-circle mr-2 text-green-400"></i>Active
                                        </label>
                                        <p class="text-gray-400 text-xs">Enable matching</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-slate-700">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center justify-center space-x-2">
                                <i class="fas fa-save"></i>
                                <span>Create Keyword</span>
                            </button>
                            <a href="{{ route('keywords.index') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-4 rounded-xl font-semibold transition-all flex items-center justify-center space-x-2">
                                <i class="fas fa-times"></i>
                                <span>Cancel</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Live Tester Sidebar (Sticky) --}}
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl sticky top-4">
                    <h3 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-flask mr-2 text-purple-500"></i>Live Pattern Test
                    </h3>
                    
                    <div class="space-y-4">
                        {{-- Current Pattern Display --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Pattern</label>
                            <div class="p-3 bg-slate-900/50 border border-slate-700 rounded-lg">
                                <code id="displayPattern" class="text-purple-400 text-sm break-all">Type keyword...</code>
                            </div>
                        </div>

                        {{-- Test Input --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Test String</label>
                            <input type="text" id="testString" 
                                   class="w-full px-3 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white text-sm font-mono" 
                                   placeholder="APOTEK KIMIA FARMA"
                                   oninput="testMatch()">
                        </div>

                        {{-- Quick Test Examples --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Examples</label>
                            <div class="space-y-1">
                                <button type="button" onclick="quickTest('INDOMARET GALAXY MALL')" class="w-full text-left px-3 py-2 bg-slate-900/50 hover:bg-slate-800 rounded text-xs text-gray-300 transition">
                                    INDOMARET GALAXY MALL
                                </button>
                                <button type="button" onclick="quickTest('GOPAY PAYMENT QR')" class="w-full text-left px-3 py-2 bg-slate-900/50 hover:bg-slate-800 rounded text-xs text-gray-300 transition">
                                    GOPAY PAYMENT QR
                                </button>
                                <button type="button" onclick="quickTest('Transfer Bank BCA')" class="w-full text-left px-3 py-2 bg-slate-900/50 hover:bg-slate-800 rounded text-xs text-gray-300 transition">
                                    Transfer Bank BCA
                                </button>
                            </div>
                        </div>

                        {{-- Test Result --}}
                        <div id="testResult" class="hidden p-4 rounded-lg"></div>
                    </div>
                </div>

                {{-- Regex Helper --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-book mr-2 text-yellow-500"></i>Regex Patterns
                    </h3>
                    <div class="space-y-2 text-xs">
                        <button type="button" onclick="insertPattern('.*TEXT.*')" class="w-full text-left p-2 bg-slate-900/50 rounded hover:bg-slate-800 transition">
                            <code class="text-purple-400">.*TEXT.*</code>
                            <p class="text-gray-400 mt-1">Contains TEXT</p>
                        </button>
                        <button type="button" onclick="insertPattern('^TEXT')" class="w-full text-left p-2 bg-slate-900/50 rounded hover:bg-slate-800 transition">
                            <code class="text-purple-400">^TEXT</code>
                            <p class="text-gray-400 mt-1">Starts with</p>
                        </button>
                        <button type="button" onclick="insertPattern('TEXT$')" class="w-full text-left p-2 bg-slate-900/50 rounded hover:bg-slate-800 transition">
                            <code class="text-purple-400">TEXT$</code>
                            <p class="text-gray-400 mt-1">Ends with</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTypeFilter = 'all';
        let currentSelectedId = '{{ old("sub_category_id", $selectedSubCategoryId ?? "") }}';

        // Search sub categories
        function searchSubCategories() {
            const searchTerm = document.getElementById('sub_category_search').value.toLowerCase().trim();
            const items = document.querySelectorAll('.sub-category-item');
            const groups = document.querySelectorAll('.sub-category-group');
            let visibleCount = 0;
            let totalCount = 0;

            // Show/hide clear button
            const clearBtn = document.getElementById('clearSearchBtn');
            if (searchTerm) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }

            // Filter items
            items.forEach(item => {
                const name = item.dataset.name || '';
                const category = item.dataset.category || '';
                const typeName = item.dataset.typeName || '';
                const typeId = item.dataset.type || '';
                
                totalCount++;
                
                // Check type filter
                let matchType = (currentTypeFilter === 'all' || typeId === currentTypeFilter);
                
                // Check search filter
                let matchSearch = !searchTerm || 
                                 name.includes(searchTerm) || 
                                 category.includes(searchTerm) ||
                                 typeName.includes(searchTerm);
                
                if (matchType && matchSearch) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Hide empty groups
            groups.forEach(group => {
                const visibleItems = group.querySelectorAll('.sub-category-item:not([style*="display: none"])');
                if (visibleItems.length > 0) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });

            // Update info
            if (searchTerm) {
                document.getElementById('searchInfo').innerHTML = `<i class="fas fa-search mr-1"></i>Search: "${searchTerm}"`;
                document.getElementById('searchCount').textContent = `${visibleCount} of ${totalCount} found`;
            } else if (currentTypeFilter !== 'all') {
                document.getElementById('searchInfo').innerHTML = `<i class="fas fa-filter mr-1"></i>Filtered by type`;
                document.getElementById('searchCount').textContent = `${visibleCount} shown`;
            } else {
                document.getElementById('searchInfo').innerHTML = `<i class="fas fa-info-circle mr-1"></i>All sub categories shown`;
                document.getElementById('searchCount').textContent = '';
            }
        }

        // Clear search
        function clearSearch() {
            document.getElementById('sub_category_search').value = '';
            searchSubCategories();
            document.getElementById('sub_category_search').focus();
        }

        // Filter by type
        function filterByType(typeId, buttonElement) {
            currentTypeFilter = typeId;
            
            // Update button states
            document.querySelectorAll('.type-filter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-600', 'text-white');
                btn.classList.add('bg-slate-700', 'text-gray-300');
            });
            
            if (buttonElement) {
                buttonElement.classList.remove('bg-slate-700', 'text-gray-300');
                buttonElement.classList.add('active', 'bg-blue-600', 'text-white');
            }
            
            searchSubCategories();
        }

        // Select sub category
        function selectSubCategory(id, name, category, priority, element) {
            currentSelectedId = id;
            
            // Update hidden input
            document.getElementById('sub_category_id').value = id;
            
            // Update visual selection
            document.querySelectorAll('.sub-category-item').forEach(item => {
                item.classList.remove('bg-blue-600/20', 'border-l-4', 'border-blue-500');
                item.querySelector('.selected-icon').classList.add('hidden');
            });
            
            element.classList.add('bg-blue-600/20', 'border-l-4', 'border-blue-500');
            element.querySelector('.selected-icon').classList.remove('hidden');
            
            // Show selected panel
            const priorityIcon = priority >= 8 ? '🔴' : (priority >= 5 ? '🟡' : '🔵');
            document.getElementById('selectedName').textContent = priorityIcon + ' ' + name;
            document.getElementById('selectedPath').textContent = category + ' (Priority: ' + priority + ')';
            document.getElementById('selectedPanel').classList.remove('hidden');
            
            // Scroll to selected
            element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Clear selection
        function clearSelection() {
            currentSelectedId = '';
            document.getElementById('sub_category_id').value = '';
            document.getElementById('selectedPanel').classList.add('hidden');
            
            document.querySelectorAll('.sub-category-item').forEach(item => {
                item.classList.remove('bg-blue-600/20', 'border-l-4', 'border-blue-500');
                item.querySelector('.selected-icon').classList.add('hidden');
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setPriority({{ old('priority', 5) }});
            
            // Pre-select if old value exists
            if (currentSelectedId) {
                const item = document.querySelector(`[data-id="${currentSelectedId}"]`);
                if (item) {
                    item.click();
                }
            }
            
            // Keyboard navigation for search
            document.getElementById('sub_category_search').addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    clearSearch();
                }
            });
        });

        // Priority selection
        function setPriority(value, buttonElement) {
            document.getElementById('priority').value = value;
            document.querySelectorAll('.priority-btn').forEach(btn => {
                btn.classList.remove('ring-4', 'ring-white', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
            });
            
            if (buttonElement) {
                buttonElement.classList.add('ring-4', 'ring-white', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
            } else {
                // If called without button (initialization), find button by priority
                const btn = document.querySelector(`.priority-btn[data-priority="${value}"]`);
                if (btn) {
                    btn.classList.add('ring-4', 'ring-white', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
                }
            }
            updateLiveTest();
        }

        // Live test update
        function updateLiveTest() {
            const keyword = document.getElementById('keyword').value;
            const isRegex = document.getElementById('is_regex').checked;
            const caseSensitive = document.getElementById('case_sensitive').checked;

            let display = keyword || 'Type keyword...';
            if (keyword && isRegex) {
                display = '/' + keyword + '/' + (caseSensitive ? '' : 'i');
            }

            document.getElementById('displayPattern').textContent = display;
            testMatch();
        }

        // Test matching
        function testMatch() {
            const keyword = document.getElementById('keyword').value;
            const testString = document.getElementById('testString').value;
            const isRegex = document.getElementById('is_regex').checked;
            const caseSensitive = document.getElementById('case_sensitive').checked;
            const resultDiv = document.getElementById('testResult');

            if (!keyword || !testString) {
                resultDiv.classList.add('hidden');
                return;
            }

            try {
                let matched = false;

                if (isRegex) {
                    const flags = caseSensitive ? '' : 'i';
                    const regex = new RegExp(keyword, flags);
                    matched = regex.test(testString);
                } else {
                    const kw = caseSensitive ? keyword : keyword.toUpperCase();
                    const str = caseSensitive ? testString : testString.toUpperCase();
                    matched = str.includes(kw);
                }

                if (matched) {
                    resultDiv.className = 'p-4 rounded-lg bg-green-600/20 border border-green-600/30';
                    resultDiv.innerHTML = '<div class="flex items-center space-x-2"><i class="fas fa-check-circle text-green-400 text-xl"></i><div><p class="text-green-400 font-semibold">Match Found!</p><p class="text-xs text-gray-400 mt-1">This pattern will match</p></div></div>';
                } else {
                    resultDiv.className = 'p-4 rounded-lg bg-red-600/20 border border-red-600/30';
                    resultDiv.innerHTML = '<div class="flex items-center space-x-2"><i class="fas fa-times-circle text-red-400 text-xl"></i><div><p class="text-red-400 font-semibold">No Match</p><p class="text-xs text-gray-400 mt-1">Pattern will not match</p></div></div>';
                }
                resultDiv.classList.remove('hidden');
            } catch (error) {
                resultDiv.className = 'p-4 rounded-lg bg-red-600/20 border border-red-600/30';
                resultDiv.innerHTML = '<div class="flex items-center space-x-2"><i class="fas fa-exclamation-triangle text-red-400 text-xl"></i><div><p class="text-red-400 font-semibold">Invalid Regex</p><p class="text-xs text-gray-400 mt-1">' + error.message + '</p></div></div>';
                resultDiv.classList.remove('hidden');
            }
        }

        // Quick test
        function quickTest(str) {
            document.getElementById('testString').value = str;
            testMatch();
        }

        // Insert pattern
        function insertPattern(pattern) {
            document.getElementById('keyword').value = pattern;
            document.getElementById('is_regex').checked = true;
            updateLiveTest();
        }

        // Initialize
        updateLiveTest();
    </script>

    <style>
        /* Hide filtered options properly */
        select option[style*="display: none"] {
            display: none !important;
        }
        select optgroup[style*="display: none"] {
            display: none !important;
        }

        /* Make select look better */
        #sub_category_id {
            scrollbar-width: thin;
            scrollbar-color: #475569 #1e293b;
        }
        #sub_category_id::-webkit-scrollbar {
            width: 8px;
        }
        #sub_category_id::-webkit-scrollbar-track {
            background: #1e293b;
        }
        #sub_category_id::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        /* Priority button animation */
        .priority-btn {
            transition: all 0.2s ease;
        }
        .priority-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</x-app-layout>