<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('chat-sessions.index') }}" 
                   class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create AI Chat Session
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <form action="{{ route('chat-sessions.store') }}" method="POST" class="space-y-8" x-data="chatSessionForm()">
            @csrf

            <!-- Company Selection (Super Admin Only) -->
            @if(auth()->user()->isSuperAdmin())
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-building text-purple-500 mr-2"></i>
                    Select Company
                </h3>

                @if(isset($selectedCompany))
                    <div class="p-4 bg-purple-900/20 border border-purple-700 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white font-semibold">{{ $selectedCompany->name }}</p>
                                <p class="text-purple-300 text-sm">{{ $selectedCompany->subdomain }}.app</p>
                            </div>
                            <a href="{{ route('chat-sessions.create') }}" 
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
                            onchange="window.location.href='{{ route('chat-sessions.create') }}?company_id='+this.value"
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
                        Please select a company first to see available bank statements and collections
                    </p>
                @endif
            </div>
            @endif

            <!-- Show rest of form only if: regular user OR super admin with selected company -->
            @if(!auth()->user()->isSuperAdmin() || isset($selectedCompany))

            <!-- Mode Selection -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-layer-group text-blue-500 mr-2"></i>
                    Select Chat Mode
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Single Document Mode -->
                    <label class="relative cursor-pointer">
                        <input type="radio" 
                               name="mode" 
                               value="single" 
                               x-model="mode"
                               {{ old('mode', $mode ?? 'single') === 'single' ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="p-6 bg-slate-900/50 border-2 border-slate-700 rounded-xl peer-checked:border-blue-500 peer-checked:bg-blue-900/20 hover:border-slate-600 transition">
                            <div class="flex items-center justify-between mb-3">
                                <i class="fas fa-file text-3xl text-blue-400"></i>
                                <div class="w-5 h-5 rounded-full border-2 border-gray-500 peer-checked:border-blue-500 peer-checked:bg-blue-500 flex items-center justify-center transition">
                                    <i class="fas fa-check text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                                </div>
                            </div>
                            <h4 class="text-lg font-bold text-white mb-2">Single Document</h4>
                            <p class="text-gray-400 text-sm">Chat with one bank statement at a time</p>
                        </div>
                    </label>

                    <!-- Collection Mode -->
                    <label class="relative cursor-pointer">
                        <input type="radio" 
                               name="mode" 
                               value="collection" 
                               x-model="mode"
                               {{ old('mode', $mode ?? 'single') === 'collection' ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="p-6 bg-slate-900/50 border-2 border-slate-700 rounded-xl peer-checked:border-purple-500 peer-checked:bg-purple-900/20 hover:border-slate-600 transition">
                            <div class="flex items-center justify-between mb-3">
                                <i class="fas fa-folder text-3xl text-purple-400"></i>
                                <div class="w-5 h-5 rounded-full border-2 border-gray-500 peer-checked:border-purple-500 peer-checked:bg-purple-500 flex items-center justify-center transition">
                                    <i class="fas fa-check text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                                </div>
                            </div>
                            <h4 class="text-lg font-bold text-white mb-2">Document Collection</h4>
                            <p class="text-gray-400 text-sm">Chat with multiple bank statements</p>
                        </div>
                    </label>
                </div>

                @error('mode')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Source Selection -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-database text-green-500 mr-2"></i>
                    Select Data Source
                </h3>

                <!-- Single Document Selection -->
                <div x-show="mode === 'single'" x-transition>
                    <label class="block text-sm font-medium text-gray-400 mb-2">
                        Select Bank Statement <span class="text-red-400">*</span>
                    </label>

                    @if(isset($bankStatements) && $bankStatements->isNotEmpty())
                    <select name="bank_statement_id" 
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">-- Select Bank Statement --</option>
                        @foreach($bankStatements as $statement)
                            <option value="{{ $statement->id }}" {{ old('bank_statement_id', $bankStatementId ?? '') == $statement->id ? 'selected' : '' }}>
                                {{ $statement->bank->name ?? 'Unknown Bank' }} - 
                                {{ $statement->period_start->format('M Y') }} to {{ $statement->period_end->format('M Y') }} 
                                ({{ number_format($statement->total_transactions) }} transactions)
                            </option>
                        @endforeach
                    </select>
                    @error('bank_statement_id')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    @else
                    <div class="p-4 bg-red-900/20 border border-red-700 rounded-lg">
                        <p class="text-red-200 text-sm">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No completed bank statements available. Please upload and process a bank statement first.
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Collection Selection -->
                <div x-show="mode === 'collection'" x-transition>
                    <label class="block text-sm font-medium text-gray-400 mb-2">
                        Select Document Collection <span class="text-red-400">*</span>
                    </label>

                    @if(isset($documentCollections) && $documentCollections->isNotEmpty())
                    <select name="document_collection_id" 
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">-- Select Collection --</option>
                        @foreach($documentCollections as $collection)
                            <option value="{{ $collection->id }}" {{ old('document_collection_id', $collectionId ?? '') == $collection->id ? 'selected' : '' }}>
                                {{ $collection->name }} ({{ $collection->document_count }} documents)
                            </option>
                        @endforeach
                    </select>
                    @error('document_collection_id')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    @else
                    <div class="p-4 bg-yellow-900/20 border border-yellow-700 rounded-lg">
                        <p class="text-yellow-200 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            No active collections available. Create a document collection first.
                        </p>
                        <a href="{{ route('document-collections.create') }}{{ auth()->user()->isSuperAdmin() && isset($selectedCompany) ? '?company_id='.$selectedCompany->id : '' }}" 
                           class="inline-block mt-3 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-sm font-semibold">
                            <i class="fas fa-plus mr-2"></i>Create Collection
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Session Details -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-info-circle text-purple-500 mr-2"></i>
                    Session Details (Optional)
                </h3>

                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-400 mb-2">
                            Custom Title
                        </label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               value="{{ old('title') }}" 
                               placeholder="Leave blank for auto-generated title"
                               class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                        @error('title')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500">If empty, title will be generated automatically</p>
                    </div>

                    <!-- Context Description -->
                    <div>
                        <label for="context_description" class="block text-sm font-medium text-gray-400 mb-2">
                            Context/Notes
                        </label>
                        <textarea id="context_description" 
                                  name="context_description" 
                                  rows="4"
                                  placeholder="Add any context or notes about what you want to analyze..."
                                  class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">{{ old('context_description') }}</textarea>
                        @error('context_description')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-gradient-to-br from-blue-900/20 to-purple-900/20 rounded-2xl p-6 border border-blue-700 shadow-xl">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-400 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h4 class="text-white font-semibold mb-2">About AI Chat Sessions</h4>
                        <ul class="text-blue-200 text-sm space-y-2">
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Ask questions about your financial transactions</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Get insights and analysis from your bank statements</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Identify spending patterns and trends</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Chat history is saved for future reference</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('chat-sessions.index') }}" 
                   class="px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition font-semibold">
                    <i class="fas fa-rocket mr-2"></i>Start Chat Session
                </button>
            </div>

            @endif

            <!-- Empty State for Super Admin without company selection -->
            @if(auth()->user()->isSuperAdmin() && !isset($selectedCompany))
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 border border-slate-700 shadow-xl text-center">
                <i class="fas fa-building text-gray-600 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-white mb-2">Select a Company First</h3>
                <p class="text-gray-400 mb-6">Please select a company above to continue creating a chat session.</p>
            </div>
            @endif

        </form>

    </div>

    @push('scripts')
    <script>
        function chatSessionForm() {
            return {
                mode: '{{ old("mode", $mode ?? "single") }}',
            }
        }
    </script>
    @endpush
</x-app-layout>