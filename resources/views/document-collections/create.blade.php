<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('document-collections.index') }}" 
                   class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create Document Collection
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <form action="{{ route('document-collections.store') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Basic Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Basic Information
                </h3>

                <div class="space-y-6">
                    <!-- Company Selection (Super Admin Only) -->
                    @if(auth()->user()->isSuperAdmin())
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-400 mb-2">
                            Select Company <span class="text-red-400">*</span>
                        </label>
                        @if(isset($selectedCompany))
                            <div class="p-4 bg-blue-900/20 border border-blue-700 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-white font-semibold">{{ $selectedCompany->name }}</p>
                                        <p class="text-blue-300 text-sm">{{ $selectedCompany->subdomain }}.app</p>
                                    </div>
                                    <a href="{{ route('document-collections.create') }}" 
                                       class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition text-sm">
                                        Change Company
                                    </a>
                                </div>
                            </div>
                            <input type="hidden" name="company_id" value="{{ $selectedCompany->id }}">
                        @else
                            <select id="company_id" 
                                    name="company_id" 
                                    required
                                    onchange="window.location.href='{{ route('document-collections.create') }}?company_id='+this.value"
                                    class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                                <option value="">-- Select Company First --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-yellow-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                Please select a company first to see available bank statements
                            </p>
                        @endif
                    </div>
                    @endif

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">
                            Collection Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required
                               placeholder="e.g., Q1 2024 Statements, Tax Documents 2023"
                               class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-400 mb-2">
                            Description
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="4"
                                  placeholder="Describe the purpose of this collection..."
                                  class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500">Optional: Add context about what this collection contains</p>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-3 text-sm font-medium text-gray-300">
                            Set as Active (collection will be available for AI chat immediately)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Select Bank Statements -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-file-invoice text-green-500 mr-2"></i>
                        Select Bank Statements
                    </h3>
                    <span class="text-sm text-gray-400">
                        <span id="selected-count">0</span> selected
                    </span>
                </div>

                @if(auth()->user()->isSuperAdmin() && !isset($selectedCompany))
                <div class="text-center py-12">
                    <i class="fas fa-building text-gray-600 text-5xl mb-4"></i>
                    <h4 class="text-white text-lg font-semibold mb-2">Select a Company First</h4>
                    <p class="text-gray-400 mb-6">Please select a company above to view available bank statements.</p>
                </div>
                @elseif(isset($bankStatements) && $bankStatements->isNotEmpty())
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($bankStatements as $statement)
                    <label class="flex items-start p-4 bg-slate-900/50 rounded-lg hover:bg-slate-900 transition cursor-pointer border border-slate-700 hover:border-blue-500">
                        <input type="checkbox" 
                               name="bank_statement_ids[]" 
                               value="{{ $statement->id }}"
                               class="statement-checkbox mt-1 w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                        
                        <div class="ml-4 flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h4 class="text-white font-semibold">
                                        {{ $statement->bank->name ?? 'Unknown Bank' }}
                                    </h4>
                                    <p class="text-gray-400 text-sm">
                                        {{ $statement->period_start->format('M Y') }} - {{ $statement->period_end->format('M Y') }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-green-900/30 text-green-400 rounded-full text-xs font-semibold">
                                    {{ ucfirst($statement->ocr_status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Transactions</p>
                                    <p class="text-white font-semibold">{{ number_format($statement->total_transactions) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Debit</p>
                                    <p class="text-red-400 font-semibold">Rp {{ number_format($statement->total_debit_amount, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Credit</p>
                                    <p class="text-green-400 font-semibold">Rp {{ number_format($statement->total_credit_amount, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div class="mt-6 p-4 bg-blue-900/20 border border-blue-700 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-200">
                            <p class="font-semibold mb-1">About Bank Statements Selection:</p>
                            <ul class="list-disc list-inside space-y-1 text-blue-300">
                                <li>Only completed OCR statements are available</li>
                                <li>Statements already in other collections are hidden</li>
                                <li>You can add more statements later</li>
                            </ul>
                        </div>
                    </div>
                </div>

                @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                    <h4 class="text-white text-lg font-semibold mb-2">No Bank Statements Available</h4>
                    <p class="text-gray-400 mb-6">Upload and process bank statements first before creating collections.</p>
                    <a href="{{ route('bank-statements.index') }}" 
                       class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        <i class="fas fa-upload mr-2"></i>Go to Bank Statements
                    </a>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('document-collections.index') }}" 
                   class="px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Create Collection
                </button>
            </div>

        </form>

    </div>

    @push('scripts')
    <script>
        // Count selected checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.statement-checkbox');
            const counter = document.getElementById('selected-count');

            function updateCount() {
                const count = document.querySelectorAll('.statement-checkbox:checked').length;
                counter.textContent = count;
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCount);
            });

            updateCount();
        });
    </script>
    @endpush
</x-app-layout>