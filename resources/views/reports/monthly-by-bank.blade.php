<x-app-layout>
    <x-slot name="header">Monthly by Bank Report - {{ $year }}</x-slot>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="text-3xl font-bold text-white">Monthly by Bank Report</h2>
                </div>
                <p class="text-gray-400">Year: {{ $year }} | Type: {{ ucfirst($transactionType) }}</p>
            </div>
            <div class="flex items-center space-x-3">
                {{-- Export Button --}}
                <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export</span>
                </button>
                {{-- Print Button --}}
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Amount</p>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($grandTotal['total'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Transactions</p>
                <p class="text-white text-3xl font-bold">{{ number_format($grandTotal['count']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Banks</p>
                <p class="text-white text-3xl font-bold">{{ $banks->count() }}</p>
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
                            <th class="px-6 py-4 text-left text-white font-bold border-b border-slate-700 sticky left-0 bg-slate-900/50 z-10">
                                Month
                            </th>
                            @foreach($banks as $bank)
                                <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 min-w-[200px]">
                                    <div class="flex flex-col items-center space-y-1">
                                        @if($bank->logo)
                                            <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="h-8 object-contain">
                                        @endif
                                        <span>{{ $bank->name }}</span>
                                    </div>
                                </th>
                            @endforeach
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-blue-900/30 min-w-[200px]">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $monthData)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 text-white font-semibold sticky left-0 bg-slate-900/90 z-10">
                                    {{ $monthData['month'] }}
                                </td>
                                @foreach($banks as $bank)
                                    @php
                                        $bankData = $monthData['banks'][$bank->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <td class="px-6 py-4 text-center group relative">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-white font-bold">
                                                Rp {{ number_format($bankData['total'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-gray-400 text-xs">
                                                {{ number_format($bankData['count']) }} txn
                                            </span>
                                        </div>
                                        
                                        {{-- Detail Link (muncul on hover) --}}
                                        @if($bankData['count'] > 0)
                                            <a href="{{ route('reports.monthly-detail', [
                                                'year' => $year,
                                                'month' => $monthData['month_number'],
                                                'bank_id' => $bank->id,
                                                'transaction_type' => $transactionType,
                                                'company_id' => $selectedCompanyId ?? null
                                            ]) }}"
                                                class="absolute inset-0 bg-blue-600/0 hover:bg-blue-600/20 transition opacity-0 group-hover:opacity-100 flex items-center justify-center"
                                                title="View transaction details">
                                                <span class="bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-semibold shadow-lg">
                                                    <i class="fas fa-eye mr-1"></i> View Details
                                                </span>
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 text-center bg-blue-900/20">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-bold">
                                            Rp {{ number_format($monthData['total'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-400 text-xs">
                                            {{ number_format($monthData['count']) }} txn
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Grand Total Row --}}
                        <tr class="bg-blue-900/30 border-t-2 border-blue-500/50">
                            <td class="px-6 py-4 text-white font-bold sticky left-0 bg-blue-900/50 z-10">
                                GRAND TOTAL
                            </td>
                            @foreach($banks as $bank)
                                @php
                                    $bankTotal = $grandTotal['banks'][$bank->id] ?? ['total' => 0, 'count' => 0];
                                @endphp
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-bold text-base">
                                            Rp {{ number_format($bankTotal['total'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-300 text-xs">
                                            {{ number_format($bankTotal['count']) }} txn
                                        </span>
                                    </div>
                                </td>
                            @endforeach
                            <td class="px-6 py-4 text-center bg-blue-900/40">
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

        {{-- Chart Visualization (Optional) --}}
        <div class="mt-8 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
            <h3 class="text-white font-bold text-lg mb-4">
                <i class="fas fa-chart-area mr-2"></i>Monthly Trend
            </h3>
            <canvas id="monthlyChart" height="80"></canvas>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        
        const months = @json(array_column($months, 'month'));
        const datasets = [];
        
        @foreach($banks as $bank)
            const data_{{ $bank->id }} = @json(array_column(array_column($months, 'banks'), $bank->id));
            datasets.push({
                label: '{{ $bank->name }}',
                data: data_{{ $bank->id }}.map(d => d ? d.total : 0),
                borderWidth: 2,
                tension: 0.4
            });
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