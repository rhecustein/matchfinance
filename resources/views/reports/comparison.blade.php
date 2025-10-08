<x-app-layout>
    <x-slot name="header">Bank Comparison Report - {{ $year }}</x-slot>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="text-3xl font-bold text-white">Bank Comparison Report</h2>
                </div>
                <p class="text-gray-400">
                    Year: {{ $year }} | Type: {{ ucfirst($transactionType) }} | 
                    Comparing: <span class="text-white font-semibold">{{ $bank1->name }}</span> vs <span class="text-white font-semibold">{{ $bank2->name }}</span>
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
            <form action="{{ route('reports.comparison') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Year</label>
                    <select name="year" required class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-pink-500">
                        @for($y = date('Y'); $y >= 2015; $y--)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Bank 1</label>
                    <select name="bank_1" required class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-pink-500">
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ $bank->id == $bank1->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Bank 2</label>
                    <select name="bank_2" required class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-pink-500">
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ $bank->id == $bank2->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Transaction Type</label>
                    <select name="transaction_type" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-pink-500">
                        <option value="all" {{ $transactionType == 'all' ? 'selected' : '' }}>All</option>
                        <option value="debit" {{ $transactionType == 'debit' ? 'selected' : '' }}>Debit</option>
                        <option value="credit" {{ $transactionType == 'credit' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="w-full bg-pink-600 hover:bg-pink-700 text-white font-semibold py-2 rounded-lg transition">
                        <i class="fas fa-sync-alt mr-2"></i>Compare Banks
                    </button>
                </div>
            </form>
        </div>

        {{-- Comparison Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Bank 1 Card --}}
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <div class="flex items-center space-x-3 mb-4">
                    @if($bank1->logo_url)
                        <img src="{{ $bank1->logo_url }}" alt="{{ $bank1->name }}" class="h-10 object-contain">
                    @else
                        <div class="bg-blue-500/20 rounded-xl p-3">
                            <i class="fas fa-university text-blue-400 text-xl"></i>
                        </div>
                    @endif
                    <div>
                        <p class="text-gray-400 text-xs">Bank 1</p>
                        <p class="text-white font-bold text-lg">{{ $bank1->name }}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-400 text-xs mb-1">Total Amount</p>
                        <p class="text-white text-2xl font-bold">Rp {{ number_format($grandTotal['bank_1']['total'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs mb-1">Total Transactions</p>
                        <p class="text-white text-xl font-semibold">{{ number_format($grandTotal['bank_1']['count']) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs mb-1">Average per Month</p>
                        <p class="text-white text-lg">Rp {{ number_format($grandTotal['bank_1']['total'] / 12, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Difference Card --}}
            <div class="bg-gradient-to-br from-pink-900/20 to-slate-900 rounded-2xl p-6 border border-pink-500/30 shadow-xl">
                <div class="text-center mb-4">
                    <div class="bg-pink-500/20 rounded-xl p-3 inline-block">
                        <i class="fas fa-balance-scale text-pink-400 text-2xl"></i>
                    </div>
                    <p class="text-white font-bold text-lg mt-2">Difference</p>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-400 text-xs mb-1 text-center">Amount Difference</p>
                        <p class="text-white text-2xl font-bold text-center {{ $grandTotal['difference']['total'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $grandTotal['difference']['total'] >= 0 ? '+' : '' }}Rp {{ number_format($grandTotal['difference']['total'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs mb-1 text-center">Transaction Difference</p>
                        <p class="text-white text-xl font-semibold text-center {{ $grandTotal['difference']['count'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $grandTotal['difference']['count'] >= 0 ? '+' : '' }}{{ number_format($grandTotal['difference']['count']) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs mb-1 text-center">Percentage</p>
                        <p class="text-white text-lg text-center">
                            @php
                                $percentage = $grandTotal['bank_2']['total'] > 0 ? (($grandTotal['bank_1']['total'] - $grandTotal['bank_2']['total']) / $grandTotal['bank_2']['total']) * 100 : 0;
                            @endphp
                            {{ number_format($percentage, 2) }}%
                        </p>
                    </div>
                </div>
            </div>

            {{-- Bank 2 Card --}}
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <div class="flex items-center space-x-3 mb-4">
                    @if($bank2->logo_url)
                        <img src="{{ $bank2->logo_url }}" alt="{{ $bank2->name }}" class="h-10 object-contain">
                    @else
                        <div class="bg-purple-500/20 rounded-xl p-3">
                            <i class="fas fa-university text-purple-400 text-xl"></i>
                        </div>
                    @endif
                    <div>
                        <p class="text-gray-400 text-xs">Bank 2</p>
                        <p class="text-white font-bold text-lg">{{ $bank2->name }}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-400 text-xs mb-1">Total Amount</p>
                        <p class="text-white text-2xl font-bold">Rp {{ number_format($grandTotal['bank_2']['total'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs mb-1">Total Transactions</p>
                        <p class="text-white text-xl font-semibold">{{ number_format($grandTotal['bank_2']['count']) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs mb-1">Average per Month</p>
                        <p class="text-white text-lg">Rp {{ number_format($grandTotal['bank_2']['total'] / 12, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Comparison Table --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-white font-bold border-b border-slate-700 sticky left-0 bg-slate-900/50 z-10">
                                Month
                            </th>
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-blue-900/20" colspan="2">
                                {{ $bank1->name }}
                            </th>
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-purple-900/20" colspan="2">
                                {{ $bank2->name }}
                            </th>
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-pink-900/20" colspan="2">
                                Difference
                            </th>
                        </tr>
                        <tr>
                            <th class="px-6 py-3 text-left text-gray-400 text-xs border-b border-slate-700 sticky left-0 bg-slate-900/50 z-10">
                                Period
                            </th>
                            <th class="px-6 py-3 text-center text-gray-400 text-xs border-b border-slate-700">Amount</th>
                            <th class="px-6 py-3 text-center text-gray-400 text-xs border-b border-slate-700">Txn</th>
                            <th class="px-6 py-3 text-center text-gray-400 text-xs border-b border-slate-700">Amount</th>
                            <th class="px-6 py-3 text-center text-gray-400 text-xs border-b border-slate-700">Txn</th>
                            <th class="px-6 py-3 text-center text-gray-400 text-xs border-b border-slate-700">Amount</th>
                            <th class="px-6 py-3 text-center text-gray-400 text-xs border-b border-slate-700">Txn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $monthData)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 text-white font-semibold sticky left-0 bg-slate-900/90 z-10">
                                    {{ $monthData['month'] }}
                                </td>
                                {{-- Bank 1 --}}
                                <td class="px-6 py-4 text-center bg-blue-900/10">
                                    <span class="text-white font-bold">
                                        Rp {{ number_format($monthData['bank_1']['total'], 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center bg-blue-900/10">
                                    <span class="text-gray-300">
                                        {{ number_format($monthData['bank_1']['count']) }}
                                    </span>
                                </td>
                                {{-- Bank 2 --}}
                                <td class="px-6 py-4 text-center bg-purple-900/10">
                                    <span class="text-white font-bold">
                                        Rp {{ number_format($monthData['bank_2']['total'], 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center bg-purple-900/10">
                                    <span class="text-gray-300">
                                        {{ number_format($monthData['bank_2']['count']) }}
                                    </span>
                                </td>
                                {{-- Difference --}}
                                <td class="px-6 py-4 text-center bg-pink-900/10">
                                    <span class="font-bold {{ $monthData['difference']['total'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $monthData['difference']['total'] >= 0 ? '+' : '' }}Rp {{ number_format($monthData['difference']['total'], 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center bg-pink-900/10">
                                    <span class="{{ $monthData['difference']['count'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $monthData['difference']['count'] >= 0 ? '+' : '' }}{{ number_format($monthData['difference']['count']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Grand Total Row --}}
                        <tr class="bg-pink-900/30 border-t-2 border-pink-500/50">
                            <td class="px-6 py-4 text-white font-bold sticky left-0 bg-pink-900/50 z-10">
                                GRAND TOTAL
                            </td>
                            <td class="px-6 py-4 text-center bg-blue-900/20">
                                <span class="text-white font-bold text-base">
                                    Rp {{ number_format($grandTotal['bank_1']['total'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center bg-blue-900/20">
                                <span class="text-gray-300">
                                    {{ number_format($grandTotal['bank_1']['count']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center bg-purple-900/20">
                                <span class="text-white font-bold text-base">
                                    Rp {{ number_format($grandTotal['bank_2']['total'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center bg-purple-900/20">
                                <span class="text-gray-300">
                                    {{ number_format($grandTotal['bank_2']['count']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center bg-pink-900/30">
                                <span class="font-bold text-base {{ $grandTotal['difference']['total'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $grandTotal['difference']['total'] >= 0 ? '+' : '' }}Rp {{ number_format($grandTotal['difference']['total'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center bg-pink-900/30">
                                <span class="text-base {{ $grandTotal['difference']['count'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $grandTotal['difference']['count'] >= 0 ? '+' : '' }}{{ number_format($grandTotal['difference']['count']) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            {{-- Comparison Line Chart --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-white font-bold text-lg mb-4">
                    <i class="fas fa-chart-line mr-2"></i>Monthly Comparison Trend
                </h3>
                <canvas id="comparisonLineChart" height="100"></canvas>
            </div>

            {{-- Difference Bar Chart --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-white font-bold text-lg mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>Monthly Difference
                </h3>
                <canvas id="differenceBarChart" height="100"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const months = @json(array_column($months, 'month'));
        
        // Line Chart - Comparison
        const lineCtx = document.getElementById('comparisonLineChart').getContext('2d');
        
        const bank1Data = @json(array_column(array_column($months, 'bank_1'), 'total'));
        const bank2Data = @json(array_column(array_column($months, 'bank_2'), 'total'));
        
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: '{{ $bank1->name }}',
                        data: bank1Data,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '{{ $bank2->name }}',
                        data: bank2Data,
                        borderColor: 'rgba(168, 85, 247, 1)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#9ca3af',
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
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

        // Bar Chart - Difference
        const barCtx = document.getElementById('differenceBarChart').getContext('2d');
        
        const differenceData = @json(array_column(array_column($months, 'difference'), 'total'));
        const backgroundColors = differenceData.map(value => 
            value >= 0 ? 'rgba(16, 185, 129, 0.8)' : 'rgba(239, 68, 68, 0.8)'
        );
        const borderColors = differenceData.map(value => 
            value >= 0 ? 'rgba(16, 185, 129, 1)' : 'rgba(239, 68, 68, 1)'
        );
        
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Difference (Bank 1 - Bank 2)',
                    data: differenceData,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                return (value >= 0 ? '+' : '') + 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#9ca3af',
                            callback: function(value) {
                                return (value >= 0 ? '+' : '') + 'Rp ' + (value / 1000000).toFixed(1) + 'M';
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