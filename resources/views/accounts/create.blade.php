<x-app-layout>
    <x-slot name="header">Create New Account</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('accounts.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-chart-pie mr-1"></i>Accounts
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Create New</span>
            </nav>
        </div>

        {{-- Form Card --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-700 bg-gradient-to-r from-blue-600/10 to-purple-600/10">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Create New Account</h3>
                        <p class="text-gray-400 text-sm">Add a new account to your chart of accounts</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('accounts.store') }}" method="POST" class="p-8">
                @csrf

                <div class="space-y-6">
                    
                    {{-- Super Admin: Company Selection --}}
                    @if(auth()->user()->isSuperAdmin())
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                Company <span class="text-red-400">*</span>
                            </label>
                            <select name="company_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('company_id') border-red-500 @enderror">
                                <option value="">Select Company</option>
                                @foreach(\App\Models\Company::orderBy('name')->get() as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Account Code --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Account Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g., A_01, 1-1000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('code') border-red-500 @enderror">
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
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., KF 0264 NAROGONG" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition @error('name') border-red-500 @enderror">
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
                            <option value="asset" {{ old('account_type') == 'asset' ? 'selected' : '' }}>Asset</option>
                            <option value="liability" {{ old('account_type') == 'liability' ? 'selected' : '' }}>Liability</option>
                            <option value="equity" {{ old('account_type') == 'equity' ? 'selected' : '' }}>Equity</option>
                            <option value="revenue" {{ old('account_type') == 'revenue' ? 'selected' : '' }}>Revenue</option>
                            <option value="expense" {{ old('account_type') == 'expense' ? 'selected' : '' }}>Expense</option>
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
                        <textarea name="description" rows="3" placeholder="Optional description for this account..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition resize-none @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
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
                            <input type="color" name="color" value="{{ old('color', '#3B82F6') }}" class="w-16 h-12 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer">
                            <span class="text-gray-400 text-sm">Choose a color for visual identification</span>
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Priority
                        </label>
                        <input type="number" name="priority" value="{{ old('priority', 5) }}" min="1" max="10" class="w-32 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition">
                        <p class="mt-2 text-sm text-gray-400">1-10, higher number = matched first (default: 5)</p>
                    </div>

                    {{-- Status --}}
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-5 h-5 bg-slate-900 border border-slate-700 rounded focus:ring-2 focus:ring-blue-500/20 text-blue-600">
                        </div>
                        <div class="ml-3">
                            <label class="text-sm font-semibold text-gray-300">Active</label>
                            <p class="text-sm text-gray-400">Account is active and available for transaction matching</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-slate-700">
                    <a href="{{ route('accounts.index') }}" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-semibold transition-all">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Create Account
                    </button>
                </div>
            </form>
        </div>

        {{-- Help Card --}}
        <div class="mt-6 bg-blue-600/10 border border-blue-600/30 rounded-xl p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400 text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-blue-400 font-semibold mb-2">Tips for Creating Accounts</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-400 mr-2 mt-1"></i>
                            <span>Use clear, descriptive names for easy identification</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-400 mr-2 mt-1"></i>
                            <span>Account codes should be unique within your company</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-400 mr-2 mt-1"></i>
                            <span>Higher priority accounts will be matched first in the algorithm</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-400 mr-2 mt-1"></i>
                            <span>After creating, add keywords to enable automatic transaction matching</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>