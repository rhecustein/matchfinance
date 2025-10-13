<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('document-collections.show', $documentCollection) }}" 
                   class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Collection
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <form action="{{ route('document-collections.update', $documentCollection) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Basic Information
                </h3>

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">
                            Collection Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $documentCollection->name) }}" 
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
                                  class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">{{ old('description', $documentCollection->description) }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', $documentCollection->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-3 text-sm font-medium text-gray-300">
                            Active (collection is available for AI chat)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Current Bank Statements -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-file-invoice text-green-500 mr-2"></i>
                    Current Bank Statements ({{ $documentCollection->items->count() }})
                </h3>

                @if($documentCollection->items->isNotEmpty())
                <div class="space-y-3 mb-6">
                    @foreach($documentCollection->items as $item)
                    <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-700">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h5 class="text-white font-semibold">
                                    {{ $item->bankStatement->bank->name ?? 'Unknown Bank' }}
                                </h5>
                                <p class="text-gray-400 text-sm">
                                    {{ $item->bankStatement->period_start->format('M Y') }} - 
                                    {{ $item->bankStatement->period_end->format('M Y') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($item->knowledge_status === 'ready') bg-green-900/30 text-green-400
                                    @elseif($item->knowledge_status === 'processing') bg-yellow-900/30 text-yellow-400
                                    @elseif($item->knowledge_status === 'failed') bg-red-900/30 text-red-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ ucfirst($item->knowledge_status) }}
                                </span>
                                <form action="{{ route('document-items.destroy', $item) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Remove this statement from collection?')"
                                            class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition text-xs font-semibold">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Transactions</p>
                                <p class="text-white font-semibold">
                                    {{ number_format($item->bankStatement->total_transactions) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Debit</p>
                                <p class="text-red-400 font-semibold">
                                    Rp {{ number_format($item->bankStatement->total_debit_amount, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Credit</p>
                                <p class="text-green-400 font-semibold">
                                    Rp {{ number_format($item->bankStatement->total_credit_amount, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 mb-6">
                    <i class="fas fa-inbox text-gray-600 text-4xl mb-3"></i>
                    <p class="text-gray-400">No bank statements in this collection yet</p>
                </div>
                @endif
            </div>

            <!-- Add New Bank Statements -->
            @if($availableStatements->isNotEmpty())
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-plus-circle text-blue-500 mr-2"></i>
                        Add Bank Statements
                    </h3>
                    <button type="button" 
                            id="toggle-add-section"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                        <i class="fas fa-chevron-down mr-2"></i>Show Available
                    </button>
                </div>

                <div id="add-statements-section" class="hidden">
                    <div class="mb-4 p-4 bg-blue-900/20 border border-blue-700 rounded-lg">
                        <p class="text-blue-200 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            Select bank statements below to add them to this collection. You can add multiple statements at once.
                        </p>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($availableStatements as $statement)
                        <label class="flex items-start p-4 bg-slate-900/50 rounded-lg hover:bg-slate-900 transition cursor-pointer border border-slate-700 hover:border-blue-500">
                            <input type="checkbox" 
                                   name="add_statement_ids[]" 
                                   value="{{ $statement->id }}"
                                   class="add-statement-checkbox mt-1 w-5 h-5 text-blue-600 bg-slate-900 border-slate-700 rounded focus:ring-blue-500">
                            
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
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('document-collections.show', $documentCollection) }}" 
                   class="px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Update Collection
                </button>
            </div>

        </form>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle add statements section
            const toggleBtn = document.getElementById('toggle-add-section');
            const addSection = document.getElementById('add-statements-section');
            
            if (toggleBtn && addSection) {
                toggleBtn.addEventListener('click', function() {
                    addSection.classList.toggle('hidden');
                    const icon = this.querySelector('i');
                    if (addSection.classList.contains('hidden')) {
                        icon.className = 'fas fa-chevron-down mr-2';
                        this.innerHTML = '<i class="fas fa-chevron-down mr-2"></i>Show Available';
                    } else {
                        icon.className = 'fas fa-chevron-up mr-2';
                        this.innerHTML = '<i class="fas fa-chevron-up mr-2"></i>Hide Available';
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>