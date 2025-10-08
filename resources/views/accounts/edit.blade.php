<x-app-layout>
    <x-slot name="header">Edit Account</x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Edit Account</h2>
                <p class="text-gray-400">Update account information and manage keywords</p>
            </div>
            <a href="{{ route('accounts.show', $account) }}" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <form action="{{ route('accounts.update', $account) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

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
                        <input type="text" name="name" value="{{ old('name', $account->name) }}" required
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
                        <input type="text" name="code" value="{{ old('code', $account->code) }}"
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
                            <option value="Asset" {{ old('account_type', $account->account_type) === 'Asset' ? 'selected' : '' }}>Asset</option>
                            <option value="Liability" {{ old('account_type', $account->account_type) === 'Liability' ? 'selected' : '' }}>Liability</option>
                            <option value="Equity" {{ old('account_type', $account->account_type) === 'Equity' ? 'selected' : '' }}>Equity</option>
                            <option value="Revenue" {{ old('account_type', $account->account_type) === 'Revenue' ? 'selected' : '' }}>Revenue</option>
                            <option value="Expense" {{ old('account_type', $account->account_type) === 'Expense' ? 'selected' : '' }}>Expense</option>
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
                                <option value="{{ $i }}" {{ old('priority', $account->priority) == $i ? 'selected' : '' }}>{{ $i }} {{ $i === 10 ? '(Highest)' : ($i === 1 ? '(Lowest)' : '') }}</option>
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
                        <input type="color" name="color" value="{{ old('color', $account->color ?? '#3B82F6') }}"
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
                            placeholder="Optional description...">{{ old('description', $account->description) }}</textarea>
                        @error('description')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Active --}}
                    <div class="md:col-span-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}
                                class="w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500 focus:ring-2">
                            <span class="text-gray-300 font-semibold">Account is active</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex justify-end space-x-4">
                <a href="{{ route('accounts.show', $account) }}" class="bg-slate-700 hover:bg-slate-600 text-white px-8 py-3 rounded-xl font-semibold transition-all">
                    Cancel
                </a>
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition-all shadow-lg">
                    <i class="fas fa-save mr-2"></i>Update Account
                </button>
            </div>
        </form>

        {{-- Keywords Management Section --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6 mt-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-key mr-3 text-purple-400"></i>
                    Manage Keywords ({{ $account->keywords->count() }})
                </h3>
                <button type="button" onclick="openAddKeywordModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Keyword</span>
                </button>
            </div>

            @if($account->keywords->isNotEmpty())
                <div class="space-y-3">
                    @foreach($account->keywords as $keyword)
                        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500/50 transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <code class="text-blue-400 font-mono bg-blue-600/10 px-3 py-1 rounded-lg border border-blue-600/30 text-sm">
                                            {{ $keyword->keyword }}
                                        </code>
                                        <span class="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs font-semibold">
                                            {{ ucfirst(str_replace('_', ' ', $keyword->match_type)) }}
                                        </span>
                                        @if($keyword->is_active)
                                            <span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold">Active</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-600/20 text-gray-400 rounded text-xs font-semibold">Inactive</span>
                                        @endif
                                        <span class="px-2 py-1 bg-yellow-600/20 text-yellow-400 rounded text-xs font-semibold">
                                            Priority: {{ $keyword->priority }}
                                        </span>
                                    </div>

                                    <div class="flex items-center space-x-4 text-sm text-gray-400">
                                        @if($keyword->case_sensitive)
                                            <span><i class="fas fa-font mr-1"></i>Case Sensitive</span>
                                        @endif
                                        @if($keyword->is_regex)
                                            <span><i class="fas fa-code mr-1"></i>Regex</span>
                                        @endif
                                        <span><i class="fas fa-check-circle mr-1"></i>{{ $keyword->match_count ?? 0 }} matches</span>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2 ml-4">
                                    <button onclick="editKeyword({{ $keyword->id }})" class="p-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form action="{{ route('account-keywords.toggle-status', $keyword) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="p-2 bg-yellow-600/20 text-yellow-400 hover:bg-yellow-600 hover:text-white rounded-lg transition-all">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>

                                    <button onclick="confirmDeleteKeyword({{ $keyword->id }}, '{{ $keyword->keyword }}')" class="p-2 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-keyword-form-{{ $keyword->id }}" action="{{ route('account-keywords.destroy', $keyword) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-key text-gray-600 text-4xl mb-3"></i>
                    <p class="text-gray-400 mb-2">No keywords added yet</p>
                    <button onclick="openAddKeywordModal()" class="text-blue-400 hover:text-blue-300 text-sm">
                        Add your first keyword →
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Add/Edit Keyword Modal --}}
    <div id="keywordModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeKeywordModal()"></div>
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-8 border border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-white" id="keywordModalTitle">Add Keyword</h3>
                    <button onclick="closeKeywordModal()" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form id="keywordForm" action="{{ route('account-keywords.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                    <input type="hidden" name="_method" value="POST" id="keywordFormMethod">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-gray-400 text-sm font-semibold mb-2">
                                Keyword Text <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="keyword" id="keyword_input" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none"
                                placeholder="e.g., SALARY, GAJI, ATM">
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-semibold mb-2">
                                Match Type <span class="text-red-400">*</span>
                            </label>
                            <select name="match_type" id="match_type_input" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none">
                                <option value="contains">Contains</option>
                                <option value="exact">Exact Match</option>
                                <option value="starts_with">Starts With</option>
                                <option value="ends_with">Ends With</option>
                                <option value="regex">Regular Expression</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-semibold mb-2">
                                Priority <span class="text-red-400">*</span>
                            </label>
                            <select name="priority" id="priority_input" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none">
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ $i === 5 ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="md:col-span-2 space-y-3">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="case_sensitive" id="case_sensitive_input" value="1"
                                    class="w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500 focus:ring-2">
                                <span class="text-gray-300">Case Sensitive</span>
                            </label>

                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="is_regex" id="is_regex_input" value="1"
                                    class="w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500 focus:ring-2">
                                <span class="text-gray-300">Is Regular Expression</span>
                            </label>

                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="is_active" id="is_active_input" value="1" checked
                                    class="w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500 focus:ring-2">
                                <span class="text-gray-300">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4 border-t border-slate-700">
                        <button type="button" onclick="closeKeywordModal()" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg">
                            <i class="fas fa-save mr-2"></i><span id="keywordSubmitBtn">Add Keyword</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Keyword Modal --}}
    <div id="deleteKeywordModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteKeywordModal()"></div>
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Keyword?</h3>
                    <p class="text-gray-400 mb-4">Delete keyword <strong id="deleteKeywordName" class="text-white"></strong>?</p>
                    <p class="text-red-400 text-sm"><i class="fas fa-exclamation-triangle mr-1"></i>This action cannot be undone</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteKeywordModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">Cancel</button>
                    <button id="confirmDeleteKeywordBtn" onclick="submitDeleteKeyword()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteKeywordFormId = null;

        function openAddKeywordModal() {
            document.getElementById('keywordModalTitle').textContent = 'Add Keyword';
            document.getElementById('keywordSubmitBtn').textContent = 'Add Keyword';
            document.getElementById('keywordFormMethod').value = 'POST';
            document.getElementById('keywordForm').action = '{{ route("account-keywords.store") }}';
            
            // Reset form
            document.getElementById('keywordForm').reset();
            document.getElementById('is_active_input').checked = true;
            
            document.getElementById('keywordModal').classList.remove('hidden');
        }

        function editKeyword(id) {
            // In a real implementation, you would fetch keyword data via AJAX
            // For now, this is a placeholder
            alert('Edit functionality would be implemented here. Keyword ID: ' + id);
        }

        function closeKeywordModal() {
            document.getElementById('keywordModal').classList.add('hidden');
        }

        function confirmDeleteKeyword(id, name) {
            deleteKeywordFormId = id;
            document.getElementById('deleteKeywordName').textContent = name;
            document.getElementById('deleteKeywordModal').classList.remove('hidden');
        }

        function closeDeleteKeywordModal() {
            document.getElementById('deleteKeywordModal').classList.add('hidden');
            deleteKeywordFormId = null;
        }

        function submitDeleteKeyword() {
            if (deleteKeywordFormId) {
                document.getElementById('delete-keyword-form-' + deleteKeywordFormId).submit();
            }
        }
    </script>
</x-app-layout>