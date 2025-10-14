<x-app-layout>
    <x-slot name="header">Edit Keyword</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('accounts.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-chart-pie mr-1"></i>Accounts
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <a href="{{ route('accounts.show', $accountKeyword->account) }}" class="text-gray-400 hover:text-white transition">
                    {{ $accountKeyword->account->name }}
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Edit Keyword</span>
            </nav>
        </div>

        {{-- Form Card --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-700 bg-gradient-to-r from-blue-600/10 to-purple-600/10">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Edit Keyword</h3>
                        <p class="text-gray-400 text-sm">Update keyword pattern configuration</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('account-keywords.update', $accountKeyword) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    
                    {{-- Account Info Display --}}
                    <div class="bg-blue-600/10 border border-blue-600/30 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $accountKeyword->account->color ?? '#3B82F6' }}"></div>
                                <div>
                                    <p class="text-white font-semibold">{{ $accountKeyword->account->name }}</p>
                                    <p class="text-gray-400 text-sm">{{ $accountKeyword->account->code }}</p>
                                </div>
                            </div>
                            @if(auth()->user()->isSuperAdmin() && $accountKeyword->account->company)
                                <span class="px-3 py-1 bg-white/10 text-white rounded-full text-xs">
                                    {{ $accountKeyword->account->company->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Keyword --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Keyword Pattern <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="keyword" value="{{ old('keyword', $accountKeyword->keyword) }}" required placeholder="e.g., KF 0264, NAROGONG, A_01" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('keyword') border-red-500 @enderror">
                        <p class="mt-2 text-sm text-gray-400">The text pattern to match in transaction descriptions</p>
                        @error('keyword')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Match Type --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Match Type <span class="text-red-400">*</span>
                        </label>
                        <select name="match_type" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('match_type') border-red-500 @enderror">
                            <option value="contains" {{ old('match_type', $accountKeyword->match_type) == 'contains' ? 'selected' : '' }}>Contains - Match if keyword appears anywhere</option>
                            <option value="exact" {{ old('match_type', $accountKeyword->match_type) == 'exact' ? 'selected' : '' }}>Exact - Must match exactly</option>
                            <option value="starts_with" {{ old('match_type', $accountKeyword->match_type) == 'starts_with' ? 'selected' : '' }}>Starts With - Match if text starts with keyword</option>
                            <option value="ends_with" {{ old('match_type', $accountKeyword->match_type) == 'ends_with' ? 'selected' : '' }}>Ends With - Match if text ends with keyword</option>
                            <option value="regex" {{ old('match_type', $accountKeyword->match_type) == 'regex' ? 'selected' : '' }}>Regex - Advanced pattern matching</option>
                        </select>
                        @error('match_type')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Pattern Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Pattern Description
                        </label>
                        <input type="text" name="pattern_description" value="{{ old('pattern_description', $accountKeyword->pattern_description) }}" placeholder="e.g., Outlet code match, Location name" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('pattern_description') border-red-500 @enderror">
                        <p class="mt-2 text-sm text-gray-400">Optional description to explain this matching rule</p>
                        @error('pattern_description')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Priority <span class="text-red-400">*</span>
                        </label>
                        <input type="number" name="priority" value="{{ old('priority', $accountKeyword->priority) }}" min="1" max="10" required class="w-32 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition">
                        <p class="mt-2 text-sm text-gray-400">1-10, higher number = matched first</p>
                    </div>

                    {{-- Advanced Options --}}
                    <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700">
                        <h4 class="text-white font-semibold mb-4 flex items-center">
                            <i class="fas fa-cog text-blue-400 mr-2"></i>
                            Advanced Options
                        </h4>
                        
                        <div class="space-y-4">
                            {{-- Case Sensitive --}}
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="case_sensitive" value="1" {{ old('case_sensitive', $accountKeyword->case_sensitive) ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                                </div>
                                <div class="ml-3">
                                    <label class="text-sm font-semibold text-gray-300">Case Sensitive</label>
                                    <p class="text-sm text-gray-400">Match exact uppercase/lowercase (e.g., "KF" ≠ "kf")</p>
                                </div>
                            </div>

                            {{-- Is Regex --}}
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_regex" value="1" {{ old('is_regex', $accountKeyword->is_regex) ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                                </div>
                                <div class="ml-3">
                                    <label class="text-sm font-semibold text-gray-300">Use Regular Expression</label>
                                    <p class="text-sm text-gray-400">Treat keyword as regex pattern (advanced users only)</p>
                                </div>
                            </div>

                            {{-- Active Status --}}
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $accountKeyword->is_active) ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                                </div>
                                <div class="ml-3">
                                    <label class="text-sm font-semibold text-gray-300">Active</label>
                                    <p class="text-sm text-gray-400">Keyword is active and will be used for matching</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Statistics --}}
                    <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Match Count:</span>
                                <span class="text-white ml-2 font-semibold">{{ $accountKeyword->match_count ?? 0 }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Last Matched:</span>
                                <span class="text-white ml-2">
                                    {{ $accountKeyword->last_matched_at ? $accountKeyword->last_matched_at->format('d M Y') : 'Never' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400">Created:</span>
                                <span class="text-white ml-2">{{ $accountKeyword->created_at->format('d M Y') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Updated:</span>
                                <span class="text-white ml-2">{{ $accountKeyword->updated_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-700">
                    <a href="{{ route('accounts.show', $accountKeyword->account) }}" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Update Keyword
                    </button>
                </div>
            </form>
        </div>

        {{-- Danger Zone --}}
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isOwner())
            <div class="mt-8 bg-red-600/10 border border-red-600/30 rounded-xl p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-red-400 font-semibold mb-2">Danger Zone</h4>
                        <p class="text-gray-400 text-sm mb-4">
                            Once you delete this keyword, all matching history will be lost. This action cannot be undone.
                        </p>
                        <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-all">
                            <i class="fas fa-trash mr-2"></i>Delete This Keyword
                        </button>
                        <form id="delete-form" action="{{ route('account-keywords.destroy', $accountKeyword) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this keyword?\n\nKeyword: "{{ $accountKeyword->keyword }}"\n\nThis action cannot be undone.')) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
    @endpush
</x-app-layout>