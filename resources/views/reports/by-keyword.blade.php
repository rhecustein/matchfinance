<x-app-layout>
    <x-slot name="header">By Keyword Report - {{ $year }}</x-slot>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="text-3xl font-bold text-white">By Keyword Report</h2>
                </div>
                <p class="text-gray-400">Year: {{ $year }} | Type: {{ ucfirst($transactionType) }}
                    @if($bankId) | Bank: {{ $banks->find($bankId)->name ?? 'All' }} @endif
                    @if($categoryId) | Category: {{ $categories->find($categoryId)->name ?? 'All' }} @endif
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export</span>
                </button>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <form action="{{ route('reports.by-keyword') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Year</label>
                    <select name="year" required class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-green-500">
                        @for($y = date('Y'); $y >= 2015; $y--)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Bank</label>
                    <select name="bank_id" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-green-500">
                        <option value="">All Banks</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ $bank->id == $bankId ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Category</label>
                    <select name="category_id" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-green-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $category->id == $categoryId ? 'selected' : '' }}>
                                {{ $category->type->name ?? '' }} - {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Transaction Type</label>
                    <select name="transaction_type" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-green-500">
                        <option value="all" {{ $transactionType == 'all' ? 'selected' : '' }}>All</option>
                        <option value="debit" {{ $transactionType == 'debit' ? 'selected' : '' }}>Debit</option>
                        <option value="credit" {{ $transactionType == 'credit' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Apply Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Amount</p>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($grandTotal['total'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Transactions</p>
                <p class="text-white text-3xl font-bold">{{ number_format($grandTotal['count']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Keywords</p>
                <p class="text-white text-3xl font-bold">{{ $keywords->count() }}</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Avg per Month</p>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($grandTotal['total'] / 12, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Report Table --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-white font-bold border-b border-slate-700 sticky left-0 bg-slate-900/50 z-10 min-w-[250px]">
                                Keyword (Category)
                            </th>
                            @foreach($months as $monthData)
                                <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 min-w-[180px]">
                                    {{ $monthData['month'] }}
                                </th>
                            @endforeach
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-green-900/30 min-w-[180px]">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($keywords as $keyword)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 sticky left-0 bg-slate-900/90 z-10">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-semibold">{{ $keyword->name }}</span>
                                        <span class="text-gray-400 text-xs">
                                            <i class="fas fa-folder mr-1"></i>
                                            {{ $keyword->subCategory->category->type->name ?? '' }} > 
                                            {{ $keyword->subCategory->category->name ?? '' }} > 
                                            {{ $keyword->subCategory->name ?? '' }}
                                        </span>
                                    </div>
                                </td>
                                @foreach($months as $monthData)
                                    @php
                                        $keywordData = $monthData['keywords'][$keyword->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-white font-bold">
                                                Rp {{ number_format($keywordData['total'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-gray-400 text-xs">
                                                {{ number_format($keywordData['count']) }} txn
                                            </span>
                                        </div>
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 text-center bg-green-900/20">
                                    @php
                                        $keywordTotal = $grandTotal['keywords'][$keyword->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-bold">
                                            Rp {{ number_format($keywordTotal['total'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-400 text-xs">
                                            {{ number_format($keywordTotal['count']) }} txn
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Grand Total Row --}}
                        <tr class="bg-green-900/30 border-t-2 border-green-500/50">
                            <td class="px-6 py-4 text-white font-bold sticky left-0 bg-green-900/50 z-10">
                                GRAND TOTAL
                            </td>
                            @foreach($months as $monthData)
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-bold text-base">
                                            Rp {{ number_format($monthData['total'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-300 text-xs">
                                            {{ number_format($monthData['count']) }} txn
                                        </span>
                                    </div>
                                </td>
                            @endforeach
                            <td class="px-6 py-4 text-center bg-green-900/40">
                                <div class="flex flex-col space-y-1">
                                    <span class="text-white font-bold text-base">
                                        Rp {{ number_format($grandTotal['total'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-gray-300 text-xs">
                                        {{ number_format($grandTotal['count']) }} txn
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Chart Visualization --}}
        <div class="mt-8 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
            <h3 class="text-white font-bold text-lg mb-4">
                <i class="fas fa-chart-bar mr-2"></i>Top 10 Keywords Trend
            </h3>
            <canvas id="keywordChart" height="80"></canvas>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('keywordChart').getContext('2d');
        
        const months = @json(array_column($months, 'month'));
        const datasets = [];
        
        // Get top 10 keywords by total
        const keywordsData = @json($grandTotal['keywords'] ?? []);
        const sortedKeywords = Object.entries(keywordsData)
            .sort((a, b) => b[1].total - a[1].total)
            .slice(0, 10);
        
        @foreach($keywords as $keyword)
            if (sortedKeywords.find(k => k[0] == {{ $keyword->id }})) {
                const data_{{ $keyword->id }} = @json(array_column(array_column($months, 'keywords'), $keyword->id));
                datasets.push({
                    label: '{{ $keyword->name }}',
                    data: data_{{ $keyword->id }}.map(d => d ? d.total : 0),
                    borderWidth: 2,
                    tension: 0.4
                });
            }
        @endforeach
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#9ca3af',
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>