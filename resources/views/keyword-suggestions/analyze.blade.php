<x-app-layout>
    <x-slot name="header">Keyword Suggestions</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Keyword Suggestions</h2>
                <p class="text-gray-400">Review and create keywords from unmatched transactions</p>
                <p class="text-sm text-gray-500 mt-1">
                    Bank Statement: {{ $bankStatement->original_filename }} | 
                    {{ $bankStatement->period_from }} - {{ $bankStatement->period_to }}
                </p>
            </div>
            <a href="{{ route('bank-statements.show', $bankStatement) }}" 
               class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Statement
            </a>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-4 border border-blue-500/50">
                <p class="text-xs text-blue-200 mb-1">Suggestions Found</p>
                <p class="text-3xl font-bold text-white">{{ count($suggestions) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-4 border border-green-500/50">
                <p class="text-xs text-green-200 mb-1">Total Transactions</p>
                <p class="text-3xl font-bold text-white">{{ array_sum(array_column($suggestions, 'transaction_count')) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-4 border border-purple-500/50">
                <p class="text-xs text-purple-200 mb-1">Potential Coverage</p>
                <p class="text-3xl font-bold text-white">
                    {{ $bankStatement->transactions()->count() > 0 ? number_format((array_sum(array_column($suggestions, 'transaction_count')) / $bankStatement->transactions()->count()) * 100, 1) : 0 }}%
                </p>
            </div>
        </div>

        {{-- Suggestions List --}}
        <div class="space-y-4">
            @forelse($suggestions as $index => $suggestion)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <form action="{{ route('keyword-suggestions.create') }}" method="POST" class="suggestion-form">
                        @csrf
                        <input type="hidden" name="suggestion_index" value="{{ $index }}">
                        <input type="hidden" name="transaction_ids" value="{{ json_encode($suggestion['transaction_ids']) }}">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {{-- Left: Suggestion Info --}}
                            <div class="lg:col-span-2">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-white mb-2">
                                            <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
                                            Suggested Keyword: 
                                            <span class="text-blue-400">{{ $suggestion['suggested_keyword'] }}</span>
                                        </h3>
                                        <p class="text-sm text-gray-400 mb-3">
                                            Found in {{ $suggestion['transaction_count'] }} transactions
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 {{ $suggestion['transaction_type'] === 'debit' ? 'bg-red-600/20 text-red-400' : 'bg-green-600/20 text-green-400' }} rounded-lg text-sm font-semibold">
                                        <i class="fas fa-{{ $suggestion['transaction_type'] === 'debit' ? 'arrow-down' : 'arrow-up' }} mr-1"></i>
                                        {{ ucfirst($suggestion['transaction_type']) }}
                                    </span>
                                </div>

                                {{-- Sample Description --}}
                                <div class="bg-slate-900/50 rounded-lg p-4 mb-4">
                                    <p class="text-xs text-gray-400 mb-2">Sample Description:</p>
                                    <p class="text-white font-mono text-sm">{{ $suggestion['description_sample'] }}</p>
                                </div>

                                {{-- Alternative Keywords --}}
                                @if(count($suggestion['alternative_keywords']) > 1)
                                    <div class="mb-4">
                                        <p class="text-xs text-gray-400 mb-2">Alternative Keywords:</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($suggestion['alternative_keywords'] as $altKey)
                                                <button type="button" 
                                                        onclick="selectKeyword(this, '{{ $altKey }}', {{ $index }})"
                                                        class="px-3 py-1 bg-slate-700 hover:bg-blue-600 text-gray-300 hover:text-white rounded text-sm transition">
                                                    {{ $altKey }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Stats --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-slate-900/30 rounded-lg p-3">
                                        <p class="text-xs text-gray-400">Avg Amount</p>
                                        <p class="text-lg font-bold text-white">Rp {{ number_format($suggestion['avg_amount'], 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-slate-900/30 rounded-lg p-3">
                                        <p class="text-xs text-gray-400">Frequency</p>
                                        <p class="text-lg font-bold text-white">{{ $suggestion['frequency'] }}</p>
                                    </div>
                                </div>

                                {{-- Preview Transactions Button --}}
                                <button type="button" 
                                        onclick="previewTransactions({{ json_encode($suggestion['transaction_ids']) }})"
                                        class="mt-4 text-sm text-blue-400 hover:text-blue-300 transition">
                                    <i class="fas fa-eye mr-1"></i>
                                    View all {{ $suggestion['transaction_count'] }} transactions
                                </button>
                            </div>

                            {{-- Right: Category Selection --}}
                            <div class="bg-slate-900/50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-white mb-4">Assign Category</h4>
                                
                                {{-- Keyword Input --}}
                                <div class="mb-4">
                                    <label class="block text-xs text-gray-400 mb-2">Keyword</label>
                                    <input type="text" 
                                           name="keyword" 
                                           id="keyword_{{ $index }}"
                                           value="{{ $suggestion['suggested_keyword'] }}"
                                           class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                {{-- Sub Category Selection --}}
                                <div class="mb-4">
                                    <label class="block text-xs text-gray-400 mb-2">
                                        Sub Category <span class="text-red-400">*</span>
                                    </label>
                                    <select name="sub_category_id" 
                                            class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500"
                                            required>
                                        <option value="">-- Select Sub Category --</option>
                                        @foreach($subCategories as $typeName => $categories)
                                            <optgroup label="{{ $typeName }}">
                                                @foreach($categories->groupBy('category.name') as $categoryName => $subs)
                                                    <optgroup label="  {{ $categoryName }}">
                                                        @foreach($subs as $sub)
                                                            <option value="{{ $sub->id }}">
                                                                {{ $sub->name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Priority --}}
                                <div class="mb-4">
                                    <label class="block text-xs text-gray-400 mb-2">Priority (1-10)</label>
                                    <input type="number" 
                                           name="priority" 
                                           value="5" 
                                           min="1" 
                                           max="10"
                                           class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Higher = checked first</p>
                                </div>

                                {{-- Options --}}
                                <div class="space-y-2 mb-4">
                                    <label class="flex items-center text-sm text-gray-300">
                                        <input type="checkbox" name="case_sensitive" class="mr-2 rounded">
                                        Case Sensitive
                                    </label>
                                    <label class="flex items-center text-sm text-gray-300">
                                        <input type="checkbox" name="is_regex" class="mr-2 rounded">
                                        Use Regex
                                    </label>
                                    <label class="flex items-center text-sm text-gray-300">
                                        <input type="checkbox" name="apply_immediately" value="1" checked class="mr-2 rounded">
                                        Apply to transactions now
                                    </label>
                                </div>

                                {{-- Actions --}}
                                <div class="flex gap-2">
                                    <button type="submit" 
                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm font-semibold transition">
                                        <i class="fas fa-check mr-1"></i>Create
                                    </button>
                                    <button type="button" 
                                            onclick="dismissSuggestion({{ json_encode($suggestion['transaction_ids']) }})"
                                            class="px-3 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @empty
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 border border-slate-700 text-center">
                    <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">No Suggestions Found</h3>
                    <p class="text-gray-400">All transactions are already matched or don't have clear patterns.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Preview Modal --}}
    <div id="previewModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-4xl w-full p-8 border border-slate-700">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-white">Transaction Preview</h3>
                    <button onclick="closePreviewModal()" class="text-gray-400 hover:text-white">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                <div id="previewContent" class="space-y-3 max-h-96 overflow-y-auto">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function selectKeyword(button, keyword, index) {
            document.getElementById('keyword_' + index).value = keyword;
            
            // Highlight selected button
            button.parentElement.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-slate-700', 'text-gray-300');
            });
            button.classList.remove('bg-slate-700', 'text-gray-300');
            button.classList.add('bg-blue-600', 'text-white');
        }

        async function previewTransactions(transactionIds) {
            try {
                const response = await fetch('{{ route('keyword-suggestions.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ transaction_ids: transactionIds })
                });

                const result = await response.json();

                if (result.success) {
                    displayPreview(result.data);
                }
            } catch (error) {
                console.error('Preview error:', error);
                alert('Failed to load preview');
            }
        }

        function displayPreview(data) {
            const content = document.getElementById('previewContent');
            content.innerHTML = '';

            data.transactions.forEach(transaction => {
                const isDebit = transaction.transaction_type === 'debit';
                const amount = isDebit ? transaction.debit_amount : transaction.credit_amount;
                
                content.innerHTML += `
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-white font-semibold mb-1">${transaction.description}</p>
                                <p class="text-xs text-gray-400">${transaction.transaction_date} | ${transaction.bank_statement.bank.name}</p>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-lg font-bold ${isDebit ? 'text-red-400' : 'text-green-400'}">
                                    ${isDebit ? '-' : '+'}Rp ${new Intl.NumberFormat('id-ID').format(amount)}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            });

            document.getElementById('previewModal').classList.remove('hidden');
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
        }

        async function dismissSuggestion(transactionIds) {
            if (!confirm('Dismiss this suggestion?')) return;

            try {
                const response = await fetch('{{ route('keyword-suggestions.dismiss') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ transaction_ids: transactionIds })
                });

                const result = await response.json();

                if (result.success) {
                    event.target.closest('.bg-gradient-to-br').remove();
                }
            } catch (error) {
                console.error('Dismiss error:', error);
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePreviewModal();
            }
        });
    </script>
    @endpush
</x-app-layout>