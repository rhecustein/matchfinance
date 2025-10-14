<x-app-layout>
    <x-slot name="header">Edit Account</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('accounts.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-chart-pie mr-1"></i>Accounts
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <a href="{{ route('accounts.show', $account) }}" class="text-gray-400 hover:text-white transition">
                    {{ $account->name }}
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Edit</span>
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
                        <h3 class="text-2xl font-bold text-white">Edit Account</h3>
                        <p class="text-gray-400 text-sm">Update account information</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('accounts.update', $account) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    
                    {{-- Company Info (Display only) --}}
                    @if($account->company)
                        <div class="bg-blue-600/10 border border-blue-600/30 rounded-xl p-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-building text-blue-400"></i>
                                <span class="text-gray-300 font-semibold">Company:</span>
                                <span class="text-white">{{ $account->company->name }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Account Code --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Account Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code', $account->code) }}" required placeholder="e.g., A_01, 1-1000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('code') border-red-500 @enderror">
                        <p class="mt-2 text-sm text-gray-400">Unique code to identify this account</p>
                        @error('code')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Account Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $account->name) }}" required placeholder="e.g., KF 0264 NAROGONG" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Type --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Account Type <span class="text-red-400">*</span>
                        </label>
                        <select name="account_type" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('account_type') border-red-500 @enderror">
                            <option value="">Select Account Type</option>
                            <option value="asset" {{ old('account_type', $account->account_type) == 'asset' ? 'selected' : '' }}>Asset</option>
                            <option value="liability" {{ old('account_type', $account->account_type) == 'liability' ? 'selected' : '' }}>Liability</option>
                            <option value="equity" {{ old('account_type', $account->account_type) == 'equity' ? 'selected' : '' }}>Equity</option>
                            <option value="revenue" {{ old('account_type', $account->account_type) == 'revenue' ? 'selected' : '' }}>Revenue</option>
                            <option value="expense" {{ old('account_type', $account->account_type) == 'expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                        @error('account_type')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea name="description" rows="3" placeholder="Optional description for this account..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition resize-none @error('description') border-red-500 @enderror">{{ old('description', $account->description) }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Color --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Color
                        </label>
                        <div class="flex items-center space-x-4">
                            <input type="color" name="color" value="{{ old('color', $account->color ?? '#3B82F6') }}" class="w-16 h-12 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer">
                            <span class="text-gray-400 text-sm">Choose a color for visual identification</span>
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Priority
                        </label>
                        <input type="number" name="priority" value="{{ old('priority', $account->priority) }}" min="1" max="10" class="w-32 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition">
                        <p class="mt-2 text-sm text-gray-400">1-10, higher number = matched first</p>
                    </div>

                    {{-- Status --}}
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                        </div>
                        <div class="ml-3">
                            <label class="text-sm font-semibold text-gray-300">Active</label>
                            <p class="text-sm text-gray-400">Account is active and available for transaction matching</p>
                        </div>
                    </div>

                    {{-- Metadata --}}
                    <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Created:</span>
                                <span class="text-white ml-2">{{ $account->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Last Updated:</span>
                                <span class="text-white ml-2">{{ $account->updated_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-700">
                    <a href="{{ route('accounts.show', $account) }}" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Update Account
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
                            Once you delete this account, there is no going back. Please be certain.
                        </p>
                        <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-all">
                            <i class="fas fa-trash mr-2"></i>Delete This Account
                        </button>
                        <form id="delete-form" action="{{ route('accounts.destroy', $account) }}" method="POST" class="hidden">
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
            if (confirm('Are you sure you want to delete this account?\n\nThis action cannot be undone.')) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
    @endpush
</x-app-layout>