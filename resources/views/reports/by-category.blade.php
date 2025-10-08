<x-app-layout>
    <x-slot name="header">By Category Report - {{ $year }}</x-slot>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="text-3xl font-bold text-white">By Category Report</h2>
                </div>
                <p class="text-gray-400">Year: {{ $year }} | Type: {{ ucfirst($transactionType) }}
                    @if($bankId) | Bank: {{ $banks->find($bankId)->name ?? 'All' }} @endif
                    @if($typeId) | Type: {{ $types->find($typeId)->name ?? 'All' }} @endif
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
            <form action="{{ route('reports.by-category') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Year</label>
                    <select name="year" required class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-purple-500">
                        @for($y = date('Y'); $y >= 2015; $y--)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Bank</label>
                    <select name="bank_id" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-purple-500">
                        <option value="">All Banks</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ $bank->id == $bankId ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Type</label>
                    <select name="type_id" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-purple-500">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ $type->id == $typeId ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Transaction Type</label>
                    <select name="transaction_type" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-purple-500">
                        <option value="all" {{ $transactionType == 'all' ? 'selected' : '' }}>All</option>
                        <option value="debit" {{ $transactionType == 'debit' ? 'selected' : '' }}>Debit</option>
                        <option value="credit" {{ $transactionType == 'credit' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Apply Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Amount</p>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($grandTotal['total'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Transactions</p>
                <p class="text-white text-3xl font-bold">{{ number_format($grandTotal['count']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Categories</p>
                <p class="text-white text-3xl font-bold">{{ $categories->count() }}</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Avg per Category</p>
                <p class="text-white text-3xl font-bold">Rp {{ $categories->count() > 0 ? number_format($grandTotal['total'] / $categories->count(), 0, ',', '.') : 0 }}</p>
            </div>
        </div>

        {{-- Report Table --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-white font-bold border-b border-slate-700 sticky left-0 bg-slate-900/50 z-10 min-w-[250px]">
                                Category (Type)
                            </th>
                            @foreach($months as $monthData)
                                <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 min-w-[180px]">
                                    {{ $monthData['month'] }}
                                </th>
                            @endforeach
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-purple-900/30 min-w-[180px]">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 sticky left-0 bg-slate-900/90 z-10">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-semibold">{{ $category->name }}</span>
                                        <span class="text-gray-400 text-xs">
                                            <i class="fas fa-tag mr-1"></i>{{ $category->type->name ?? 'No Type' }}
                                        </span>
                                    </div>
                                </td>
                                @foreach($months as $monthData)
                                    @php
                                        $categoryData = $monthData['categories'][$category->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-white font-bold">
                                                Rp {{ number_format($categoryData['total'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-gray-400 text-xs">
                                                {{ number_format($categoryData['count']) }} txn
                                            </span>
                                        </div>
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 text-center bg-purple-900/20">
                                    @php
                                        $categoryTotal = $grandTotal['categories'][$category->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-bold">
                                            Rp {{ number_format($categoryTotal['total'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-400 text-xs">
                                            {{ number_format($categoryTotal['count']) }} txn
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Grand Total Row --}}
                        <tr class="bg-purple-900/30 border-t-2 border-purple-500/50">
                            <td class="px-6 py-4 text-white font-bold sticky left-0 bg-purple-900/50 z-10">
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
                            <td class="px-6 py-4 text-center bg-purple-900/40">
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

        {{-- Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            {{-- Trend Chart --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-white font-bold text-lg mb-4">
                    <i class="fas fa-chart-line mr-2"></i>Monthly Trend
                </h3>
                <canvas id="categoryTrendChart" height="100"></canvas>
            </div>

            {{-- Pie Chart --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-white font-bold text-lg mb-4">
                    <i class="fas fa-chart-pie mr-2"></i>Category Distribution
                </h3>
                <canvas id="categoryPieChart" height="100"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Trend Chart
        const trendCtx = document.getElementById('categoryTrendChart').getContext('2d');
        const months = @json(array_column($months, 'month'));
        const datasets = [];
        
        @foreach($categories as $category)
            const data_{{ $category->id }} = @json(array_column(array_column($months, 'categories'), $category->id));
            datasets.push({
                label: '{{ $category->name }}',
                data: data_{{ $category->id }}.map(d => d ? d.total : 0),
                borderWidth: 2,
                tension: 0.4
            });
        @endforeach
        
        new Chart(trendCtx, {
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

        // Pie Chart
        const pieCtx = document.getElementById('categoryPieChart').getContext('2d');
        const categoryLabels = @json($categories->pluck('name'));
        const categoryTotals = @json(array_values(array_map(function($cat) { return $cat['total']; }, $grandTotal['categories'] ?? [])));
        
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryTotals,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': Rp ' + context.parsed.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>