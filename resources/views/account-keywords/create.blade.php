<x-app-layout>
    <x-slot name="header">Add Account Keyword</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('accounts.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-chart-pie mr-1"></i>Accounts
                </a>
                @if(isset($account))
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                    <a href="{{ route('accounts.show', $account) }}" class="text-gray-400 hover:text-white transition">
                        {{ $account->name }}
                    </a>
                @endif
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Add Keyword</span>
            </nav>
        </div>

        {{-- Form Card --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-700 bg-gradient-to-r from-blue-600/10 to-purple-600/10">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Add New Keyword</h3>
                        <p class="text-gray-400 text-sm">Create keyword pattern for automatic transaction matching</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('account-keywords.store') }}" method="POST" class="p-8">
                @csrf

                <div class="space-y-6">
                    
                    {{-- Account Selection (if not set) --}}
                    @if(!isset($account))
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                Account <span class="text-red-400">*</span>
                            </label>
                            <select name="account_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('account_id') border-red-500 @enderror">
                                <option value="">Select Account</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} - {{ $acc->name }}
                                        @if(auth()->user()->isSuperAdmin() && $acc->company)
                                            ({{ $acc->company->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                        
                        {{-- Account Info Display --}}
                        <div class="bg-blue-600/10 border border-blue-600/30 rounded-xl p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $account->color ?? '#3B82F6' }}"></div>
                                <div>
                                    <p class="text-white font-semibold">{{ $account->name }}</p>
                                    <p class="text-gray-400 text-sm">{{ $account->code }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Keyword --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Keyword Pattern <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="keyword" value="{{ old('keyword') }}" required placeholder="e.g., KF 0264, NAROGONG, A_01" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('keyword') border-red-500 @enderror">
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
                            <option value="contains" {{ old('match_type', 'contains') == 'contains' ? 'selected' : '' }}>Contains - Match if keyword appears anywhere</option>
                            <option value="exact" {{ old('match_type') == 'exact' ? 'selected' : '' }}>Exact - Must match exactly</option>
                            <option value="starts_with" {{ old('match_type') == 'starts_with' ? 'selected' : '' }}>Starts With - Match if text starts with keyword</option>
                            <option value="ends_with" {{ old('match_type') == 'ends_with' ? 'selected' : '' }}>Ends With - Match if text ends with keyword</option>
                            <option value="regex" {{ old('match_type') == 'regex' ? 'selected' : '' }}>Regex - Advanced pattern matching</option>
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
                        <input type="text" name="pattern_description" value="{{ old('pattern_description') }}" placeholder="e.g., Outlet code match, Location name" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('pattern_description') border-red-500 @enderror">
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
                        <input type="number" name="priority" value="{{ old('priority', 5) }}" min="1" max="10" required class="w-32 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition">
                        <p class="mt-2 text-sm text-gray-400">1-10, higher number = matched first (default: 5)</p>
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
                                    <input type="checkbox" name="case_sensitive" value="1" {{ old('case_sensitive') ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                                </div>
                                <div class="ml-3">
                                    <label class="text-sm font-semibold text-gray-300">Case Sensitive</label>
                                    <p class="text-sm text-gray-400">Match exact uppercase/lowercase (e.g., "KF" ≠ "kf")</p>
                                </div>
                            </div>

                            {{-- Is Regex --}}
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_regex" value="1" {{ old('is_regex') ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                                </div>
                                <div class="ml-3">
                                    <label class="text-sm font-semibold text-gray-300">Use Regular Expression</label>
                                    <p class="text-sm text-gray-400">Treat keyword as regex pattern (advanced users only)</p>
                                </div>
                            </div>

                            {{-- Active Status --}}
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                                </div>
                                <div class="ml-3">
                                    <label class="text-sm font-semibold text-gray-300">Active</label>
                                    <p class="text-sm text-gray-400">Keyword is active and will be used for matching</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-slate-700">
                    <a href="{{ isset($account) ? route('accounts.show', $account) : route('accounts.index') }}" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Create Keyword
                    </button>
                </div>
            </form>
        </div>

        {{-- Help Card --}}
        <div class="mt-6 bg-blue-600/10 border border-blue-600/30 rounded-xl p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-blue-400 text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-blue-400 font-semibold mb-3">Keyword Matching Examples</h4>
                    <div class="space-y-3 text-gray-400 text-sm">
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            <div>
                                <strong class="text-white">Code Match:</strong> Use "A_01" or "KF 0264" to match outlet codes
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            <div>
                                <strong class="text-white">Location Match:</strong> Use "NAROGONG" or "CIKARANG" for location names
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            <div>
                                <strong class="text-white">Flexible Match:</strong> "Contains" type works for most cases (recommended)
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            <div>
                                <strong class="text-white">Priority:</strong> Higher priority keywords are checked first. Set important patterns to 8-10
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>