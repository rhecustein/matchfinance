<x-app-layout>
    <x-slot name="header">Transaction Details - {{ $monthName }} {{ $year }}</x-slot>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header with Back Button --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ url()->previous() }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="flex items-center space-x-3">
                        @if($bank->logo)
                            <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="h-10 object-contain">
                        @else
                            <div class="h-10 w-10 bg-slate-700 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ substr($bank->code, 0, 3) }}</span>
                            </div>
                        @endif
                        <h2 class="text-3xl font-bold text-white">{{ $bank->name }}</h2>
                    </div>
                </div>
                <p class="text-gray-400">
                    {{ $monthName }} {{ $year }} | 
                    Type: {{ ucfirst($transactionType) }} |
                    {{ number_format($summary['count']) }} transactions
                </p>
            </div>
            <div class="flex items-center space-x-3">
                {{-- Export Button --}}
                <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export Excel</span>
                </button>
                {{-- Print Button --}}
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Amount</p>
                <p class="text-white text-2xl font-bold">Rp {{ number_format($summary['total'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Transactions</p>
                <p class="text-white text-2xl font-bold">{{ number_format($summary['count']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Matched</p>
                <p class="text-green-400 text-2xl font-bold">{{ number_format($summary['matched']) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $summary['count'] > 0 ? number_format(($summary['matched'] / $summary['count']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Unmatched</p>
                <p class="text-red-400 text-2xl font-bold">{{ number_format($summary['unmatched']) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $summary['count'] > 0 ? number_format(($summary['unmatched'] / $summary['count']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="bg-gradient-to-br from-cyan-900/20 to-slate-900 rounded-2xl p-6 border border-cyan-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Verified</p>
                <p class="text-cyan-400 text-2xl font-bold">{{ number_format($summary['verified']) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $summary['count'] > 0 ? number_format(($summary['verified'] / $summary['count']) * 100, 1) : 0 }}%</p>
            </div>
        </div>

        {{-- Suggested Keywords Section --}}
        @if(count($suggestedKeywords) > 0)
            <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl mb-8">
                <div class="flex items-start space-x-3 mb-4">
                    <i class="fas fa-lightbulb text-yellow-400 text-2xl mt-1"></i>
                    <div class="flex-1">
                        <h3 class="text-white font-bold text-lg mb-1">💡 Suggested Keywords</h3>
                        <p class="text-gray-400 text-sm mb-4">
                            Common words found in {{ $summary['unmatched'] }} unmatched transactions. 
                            These could be useful as keywords for auto-categorization.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($suggestedKeywords as $keyword)
                                <div class="bg-yellow-900/30 border border-yellow-700 rounded-xl px-4 py-3 hover:bg-yellow-900/50 transition group cursor-pointer" 
                                     title="Sample: {{ $keyword['sample_descriptions'][0] ?? '' }}">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-1">
                                            <span class="text-yellow-400 font-bold text-base">{{ strtoupper($keyword['word']) }}</span>
                                            <div class="flex items-center space-x-3 mt-1">
                                                <span class="text-gray-400 text-xs">
                                                    <i class="fas fa-chart-bar mr-1"></i>{{ $keyword['count'] }}x
                                                </span>
                                                <span class="text-gray-400 text-xs">
                                                    <i class="fas fa-money-bill-wave mr-1"></i>Avg: Rp {{ number_format($keyword['total_amount'] / $keyword['count'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                        <button class="opacity-0 group-hover:opacity-100 bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded-lg text-xs font-semibold transition"
                                                onclick="createKeyword('{{ $keyword['word'] }}')">
                                            <i class="fas fa-plus mr-1"></i>Create
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-bold text-lg">
                    <i class="fas fa-filter mr-2"></i>Filters & Search
                </h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                {{-- Search --}}
                <div>
                    <label class="text-gray-400 text-sm mb-1 block">Search</label>
                    <input type="text" id="searchInput" placeholder="Search description, reference..."
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500">
                </div>
                {{-- Category Filter --}}
                <div>
                    <label class="text-gray-400 text-sm mb-1 block">Category</label>
                    <select id="categoryFilter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <option value="matched">Matched Only</option>
                        <option value="unmatched">Unmatched Only</option>
                    </select>
                </div>
                {{-- Status Filter --}}
                <div>
                    <label class="text-gray-400 text-sm mb-1 block">Verification</label>
                    <select id="statusFilter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="verified">Verified</option>
                        <option value="unverified">Unverified</option>
                    </select>
                </div>
                {{-- Sort --}}
                <div>
                    <label class="text-gray-400 text-sm mb-1 block">Sort By</label>
                    <select id="sortBy" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500">
                        <option value="date-desc">Date (Newest)</option>
                        <option value="date-asc">Date (Oldest)</option>
                        <option value="amount-desc">Amount (Highest)</option>
                        <option value="amount-asc">Amount (Lowest)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="transactionsTable">
                    <thead class="bg-slate-900/80 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-4 text-left text-white font-semibold border-b border-slate-700">
                                <input type="checkbox" id="selectAll" class="rounded border-slate-600">
                            </th>
                            <th class="px-4 py-4 text-left text-white font-semibold border-b border-slate-700">Date</th>
                            <th class="px-4 py-4 text-left text-white font-semibold border-b border-slate-700">Description</th>
                            <th class="px-4 py-4 text-right text-white font-semibold border-b border-slate-700">Amount</th>
                            <th class="px-4 py-4 text-center text-white font-semibold border-b border-slate-700">Type</th>
                            <th class="px-4 py-4 text-left text-white font-semibold border-b border-slate-700">Category</th>
                            <th class="px-4 py-4 text-center text-white font-semibold border-b border-slate-700">Status</th>
                            <th class="px-4 py-4 text-center text-white font-semibold border-b border-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsBody">
                        @forelse($transactions as $transaction)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition transaction-row"
                                data-description="{{ strtolower($transaction->description) }}"
                                data-reference="{{ strtolower($transaction->reference_no ?? '') }}"
                                data-amount="{{ $transaction->amount }}"
                                data-date="{{ $transaction->transaction_date }}"
                                data-matched="{{ $transaction->matched_keyword_id ? 'matched' : 'unmatched' }}"
                                data-verified="{{ $transaction->is_verified ? 'verified' : 'unverified' }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="transaction-checkbox rounded border-slate-600" value="{{ $transaction->id }}">
                                </td>
                                <td class="px-4 py-3 text-gray-300 whitespace-nowrap">
                                    {{ $transaction->transaction_date->format('d M Y') }}
                                    @if($transaction->transaction_time)
                                        <br><span class="text-gray-500 text-xs">{{ $transaction->transaction_time }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="max-w-md">
                                        <p class="text-white text-sm font-medium">{{ $transaction->description }}</p>
                                        @if($transaction->reference_no)
                                            <p class="text-gray-500 text-xs mt-1">
                                                <i class="fas fa-hashtag"></i> {{ $transaction->reference_no }}
                                            </p>
                                        @endif
                                        @if($transaction->matchedKeyword)
                                            <p class="text-blue-400 text-xs mt-1">
                                                <i class="fas fa-tag"></i> Keyword: {{ $transaction->matchedKeyword->keyword }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="text-white font-bold text-base">
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($transaction->transaction_type === 'credit')
                                        <span class="inline-block bg-green-900/30 text-green-400 px-3 py-1 rounded-lg text-xs font-semibold">
                                            <i class="fas fa-arrow-down mr-1"></i>Credit
                                        </span>
                                    @else
                                        <span class="inline-block bg-red-900/30 text-red-400 px-3 py-1 rounded-lg text-xs font-semibold">
                                            <i class="fas fa-arrow-up mr-1"></i>Debit
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($transaction->subCategory)
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-white text-sm font-medium">{{ $transaction->subCategory->name }}</span>
                                            <span class="text-gray-400 text-xs">{{ $transaction->category->name ?? '' }}</span>
                                            @if($transaction->type)
                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold w-fit
                                                    {{ $transaction->type->name === 'Income' ? 'bg-green-900/30 text-green-400' : 
                                                       ($transaction->type->name === 'Expense' ? 'bg-red-900/30 text-red-400' : 'bg-blue-900/30 text-blue-400') }}">
                                                    {{ $transaction->type->name }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-sm">
                                            <i class="fas fa-question-circle"></i> Uncategorized
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center space-y-1">
                                        @if($transaction->is_verified)
                                            <span class="inline-block bg-green-900/30 text-green-400 text-xs px-2 py-1 rounded font-semibold">
                                                <i class="fas fa-check-circle mr-1"></i>Verified
                                            </span>
                                        @else
                                            <span class="inline-block bg-gray-900/30 text-gray-400 text-xs px-2 py-1 rounded">
                                                <i class="fas fa-clock mr-1"></i>Pending
                                            </span>
                                        @endif
                                        
                                        @if($transaction->matched_keyword_id)
                                            <span class="inline-block bg-blue-900/30 text-blue-400 text-xs px-2 py-1 rounded">
                                                <i class="fas fa-tag mr-1"></i>Auto
                                            </span>
                                        @elseif($transaction->is_manual_category)
                                            <span class="inline-block bg-purple-900/30 text-purple-400 text-xs px-2 py-1 rounded">
                                                <i class="fas fa-hand-pointer mr-1"></i>Manual
                                            </span>
                                        @else
                                            <span class="inline-block bg-yellow-900/30 text-yellow-400 text-xs px-2 py-1 rounded">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>Unmatched
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('transactions.show', $transaction->id) }}" 
                                       target="_blank"
                                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                                    <p class="text-gray-400">No transactions found for this period</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Filter & Search functionality
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');
        const sortBy = document.getElementById('sortBy');
        const transactionsBody = document.getElementById('transactionsBody');
        const rows = Array.from(document.querySelectorAll('.transaction-row'));

        function filterAndSort() {
            const searchTerm = searchInput.value.toLowerCase();
            const category = categoryFilter.value;
            const status = statusFilter.value;
            const sort = sortBy.value;

            // Filter rows
            let filteredRows = rows.filter(row => {
                const description = row.dataset.description;
                const reference = row.dataset.reference;
                const matched = row.dataset.matched;
                const verified = row.dataset.verified;

                // Search filter
                const matchesSearch = !searchTerm || 
                    description.includes(searchTerm) || 
                    reference.includes(searchTerm);

                // Category filter
                const matchesCategory = !category || matched === category;

                // Status filter
                const matchesStatus = !status || verified === status;

                return matchesSearch && matchesCategory && matchesStatus;
            });

            // Sort rows
            filteredRows.sort((a, b) => {
                switch(sort) {
                    case 'date-asc':
                        return new Date(a.dataset.date) - new Date(b.dataset.date);
                    case 'date-desc':
                        return new Date(b.dataset.date) - new Date(a.dataset.date);
                    case 'amount-asc':
                        return parseFloat(a.dataset.amount) - parseFloat(b.dataset.amount);
                    case 'amount-desc':
                        return parseFloat(b.dataset.amount) - parseFloat(a.dataset.amount);
                    default:
                        return 0;
                }
            });

            // Hide all rows
            rows.forEach(row => row.style.display = 'none');

            // Show filtered & sorted rows
            filteredRows.forEach(row => {
                row.style.display = '';
                transactionsBody.appendChild(row);
            });

            // Show empty state if no results
            if (filteredRows.length === 0 && rows.length > 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="8" class="px-4 py-12 text-center">
                        <i class="fas fa-search text-gray-600 text-5xl mb-4"></i>
                        <p class="text-gray-400">No transactions match your filters</p>
                    </td>
                `;
                emptyRow.id = 'emptyState';
                transactionsBody.appendChild(emptyRow);
            } else {
                document.getElementById('emptyState')?.remove();
            }
        }

        // Event listeners
        searchInput?.addEventListener('input', filterAndSort);
        categoryFilter?.addEventListener('change', filterAndSort);
        statusFilter?.addEventListener('change', filterAndSort);
        sortBy?.addEventListener('change', filterAndSort);

        // Select all checkbox
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Create keyword function
        function createKeyword(word) {
            // Redirect to keyword create page with pre-filled keyword
            window.location.href = `/keywords/create?keyword=${encodeURIComponent(word)}`;
        }

        // Export to Excel (placeholder)
        function exportToExcel() {
            alert('Export functionality coming soon!');
            // TODO: Implement export
        }
    </script>
    @endpush
</x-app-layout>