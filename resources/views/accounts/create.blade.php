<x-app-layout>
    <x-slot name="header">Create Account</x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Create New Account</h2>
                <p class="text-gray-400">Add a new account with keywords for transaction matching</p>
            </div>
            <a href="{{ route('accounts.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <form action="{{ route('accounts.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Account Information --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-chart-pie mr-3 text-blue-400"></i>
                    Account Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Name --}}
                    <div class="md:col-span-2">
                        <label class="block text-gray-400 text-sm font-semibold mb-2">
                            Account Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none @error('name') border-red-500 @enderror"
                            placeholder="e.g., Cash in Bank, Accounts Receivable">
                        @error('name')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Code --}}
                    <div>
                        <label class="block text-gray-400 text-sm font-semibold mb-2">
                            Account Code
                        </label>
                        <input type="text" name="code" value="{{ old('code') }}"
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none @error('code') border-red-500 @enderror"
                            placeholder="e.g., 1-1000">
                        @error('code')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Type --}}
                    <div>
                        <label class="block text-gray-400 text-sm font-semibold mb-2">
                            Account Type
                        </label>
                        <select name="account_type" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none @error('account_type') border-red-500 @enderror">
                            <option value="">Select Type</option>
                            <option value="Asset" {{ old('account_type') === 'Asset' ? 'selected' : '' }}>Asset</option>
                            <option value="Liability" {{ old('account_type') === 'Liability' ? 'selected' : '' }}>Liability</option>
                            <option value="Equity" {{ old('account_type') === 'Equity' ? 'selected' : '' }}>Equity</option>
                            <option value="Revenue" {{ old('account_type') === 'Revenue' ? 'selected' : '' }}>Revenue</option>
                            <option value="Expense" {{ old('account_type') === 'Expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                        @error('account_type')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-gray-400 text-sm font-semibold mb-2">
                            Priority <span class="text-red-400">*</span>
                        </label>
                        <select name="priority" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none @error('priority') border-red-500 @enderror">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('priority', 5) == $i ? 'selected' : '' }}>{{ $i }} {{ $i === 10 ? '(Highest)' : ($i === 1 ? '(Lowest)' : '') }}</option>
                            @endfor
                        </select>
                        @error('priority')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-xs mt-1">Higher priority accounts are matched first</p>
                    </div>

                    {{-- Color --}}
                    <div>
                        <label class="block text-gray-400 text-sm font-semibold mb-2">
                            Display Color
                        </label>
                        <input type="color" name="color" value="{{ old('color', '#3B82F6') }}"
                            class="w-full h-12 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer focus:border-blue-500 focus:outline-none @error('color') border-red-500 @enderror">
                        @error('color')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="block text-gray-400 text-sm font-semibold mb-2">
                            Description
                        </label>
                        <textarea name="description" rows="3"
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none @error('description') border-red-500 @enderror"
                            placeholder="Optional description...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Active --}}
                    <div class="md:col-span-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500 focus:ring-2">
                            <span class="text-gray-300 font-semibold">Account is active</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Keywords Section --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-key mr-3 text-purple-400"></i>
                        Keywords
                    </h3>
                    <button type="button" onclick="addKeyword()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Add Keyword</span>
                    </button>
                </div>

                <div id="keywordsContainer" class="space-y-4">
                    {{-- Keywords will be added here dynamically --}}
                </div>

                <div class="mt-4 text-center text-gray-500 text-sm" id="emptyKeywordsMessage">
                    <i class="fas fa-info-circle mr-2"></i>No keywords added yet. Click "Add Keyword" to start.
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex justify-end space-x-4">
                <a href="{{ route('accounts.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-8 py-3 rounded-xl font-semibold transition-all">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Create Account
                </button>
            </div>
        </form>
    </div>

    <script>
        let keywordIndex = 0;

        function addKeyword() {
            const container = document.getElementById('keywordsContainer');
            const emptyMessage = document.getElementById('emptyKeywordsMessage');
            
            const keywordHtml = `
                <div class="keyword-item bg-slate-900/50 rounded-xl p-4 border border-slate-700" data-index="${keywordIndex}">
                    <div class="flex items-start justify-between mb-4">
                        <h4 class="text-white font-semibold">Keyword #${keywordIndex + 1}</h4>
                        <button type="button" onclick="removeKeyword(${keywordIndex})" class="text-red-400 hover:text-red-300 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-gray-400 text-sm font-semibold mb-2">Keyword Text</label>
                            <input type="text" name="keywords[${keywordIndex}][keyword]" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                                placeholder="e.g., SALARY, GAJI, ATM">
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-semibold mb-2">Match Type</label>
                            <select name="keywords[${keywordIndex}][match_type]" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:outline-none">
                                <option value="contains">Contains</option>
                                <option value="exact">Exact Match</option>
                                <option value="starts_with">Starts With</option>
                                <option value="ends_with">Ends With</option>
                                <option value="regex">Regular Expression</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-semibold mb-2">Priority</label>
                            <select name="keywords[${keywordIndex}][priority]" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:outline-none">
                                ${Array.from({length: 10}, (_, i) => i + 1).map(i => 
                                    `<option value="${i}" ${i === 5 ? 'selected' : ''}>${i}</option>`
                                ).join('')}
                            </select>
                        </div>

                        <div class="md:col-span-2 flex items-center space-x-6">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="keywords[${keywordIndex}][case_sensitive]" value="1"
                                    class="w-4 h-4 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                                <span class="text-gray-300 text-sm">Case Sensitive</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="keywords[${keywordIndex}][is_regex]" value="1"
                                    class="w-4 h-4 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                                <span class="text-gray-300 text-sm">Is Regex</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="keywords[${keywordIndex}][is_active]" value="1" checked
                                    class="w-4 h-4 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                                <span class="text-gray-300 text-sm">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', keywordHtml);
            emptyMessage.style.display = 'none';
            keywordIndex++;
        }

        function removeKeyword(index) {
            const keyword = document.querySelector(`.keyword-item[data-index="${index}"]`);
            if (keyword) {
                keyword.remove();
                
                // Check if container is empty
                const container = document.getElementById('keywordsContainer');
                const emptyMessage = document.getElementById('emptyKeywordsMessage');
                if (container.children.length === 0) {
                    emptyMessage.style.display = 'block';
                }

                // Renumber remaining keywords
                updateKeywordNumbers();
            }
        }

        function updateKeywordNumbers() {
            const keywords = document.querySelectorAll('.keyword-item');
            keywords.forEach((keyword, index) => {
                keyword.querySelector('h4').textContent = `Keyword #${index + 1}`;
            });
        }
    </script>
</x-app-layout>