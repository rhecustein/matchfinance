<x-app-layout>
    <x-slot name="header">Account Transaction Details</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="javascript:history.back()" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="text-3xl font-bold text-white">
                        {{ $account->code }} - {{ $account->name }}
                    </h2>
                </div>
                <div class="flex items-center space-x-4 text-gray-400">
                    <span class="flex items-center space-x-2">
                        <i class="fas fa-calendar mr-1"></i>
                        <span>{{ $monthName }} {{ $year }}</span>
                    </span>
                    <span class="flex items-center space-x-2">
                        <span class="px-3 py-1 rounded text-xs font-semibold {{ $account->accountTypeBadgeClass }}">
                            {{ $account->accountTypeLabel }}
                        </span>
                    </span>
                    @if($transactionType !== 'all')
                        <span class="flex items-center space-x-2">
                            <i class="fas fa-exchange-alt mr-1"></i>
                            <span>{{ ucfirst($transactionType) }}</span>
                        </span>
                    @endif
                </div>
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

        {{-- Account Info Card --}}
        @if($account->description)
            <div class="bg-gradient-to-br from-cyan-900/20 to-slate-900 rounded-2xl p-6 border border-cyan-500/30 shadow-xl mb-8">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-cyan-400 text-xl mt-1"></i>
                    <div>
                        <h3 class="text-white font-semibold mb-1">Account Description</h3>
                        <p class="text-gray-400">{{ $account->description }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-gradient-to-br from-cyan-900/20 to-slate-900 rounded-2xl p-6 border border-cyan-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Amount</p>
                <p class="text-white text-2xl font-bold">Rp {{ number_format($summary['total'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Transactions</p>
                <p class="text-white text-2xl font-bold">{{ number_format($summary['count']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Debit</p>
                <p class="text-green-400 text-2xl font-bold">Rp {{ number_format($summary['debit'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Credit</p>
                <p class="text-red-400 text-2xl font-bold">Rp {{ number_format($summary['credit'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Verified</p>
                <p class="text-purple-400 text-2xl font-bold">{{ number_format($summary['verified']) }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $summary['count'] > 0 ? number_format(($summary['verified'] / $summary['count']) * 100, 1) : 0 }}%</p>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-700">
                <h3 class="text-white font-bold text-lg">
                    <i class="fas fa-list mr-2"></i>Transaction List
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-white font-semibold border-b border-slate-700">Date & Time</th>
                            <th class="px-6 py-4 text-left text-white font-semibold border-b border-slate-700">Description</th>
                            <th class="px-6 py-4 text-left text-white font-semibold border-b border-slate-700">Bank</th>
                            <th class="px-6 py-4 text-left text-white font-semibold border-b border-slate-700">Category</th>
                            <th class="px-6 py-4 text-center text-white font-semibold border-b border-slate-700">Type</th>
                            <th class="px-6 py-4 text-right text-white font-semibold border-b border-slate-700">Amount</th>
                            <th class="px-6 py-4 text-center text-white font-semibold border-b border-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                                {{-- Date & Time --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-white font-semibold">
                                            {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}
                                        </span>
                                        @if($transaction->transaction_time)
                                            <span class="text-gray-400 text-xs">{{ $transaction->transaction_time }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Description --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col max-w-md">
                                        <span class="text-white">{{ $transaction->description }}</span>
                                        @if($transaction->reference_no)
                                            <span class="text-gray-400 text-xs mt-1">Ref: {{ $transaction->reference_no }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Bank --}}
                                <td class="px-6 py-4">
                                    @if($transaction->bankStatement && $transaction->bankStatement->bank)
                                        <div class="flex items-center space-x-2">
                                            @if($transaction->bankStatement->bank->logo)
                                                <img src="{{ Storage::url($transaction->bankStatement->bank->logo) }}" 
                                                     alt="{{ $transaction->bankStatement->bank->name }}" 
                                                     class="h-8 w-8 object-contain rounded">
                                            @endif
                                            <div class="flex flex-col">
                                                <span class="text-white text-sm font-semibold">{{ $transaction->bankStatement->bank->name }}</span>
                                                @if($transaction->bankStatement->account_number)
                                                    <span class="text-gray-400 text-xs">{{ $transaction->bankStatement->account_number }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Category --}}
                                <td class="px-6 py-4">
                                    @if($transaction->category)
                                        <div class="flex flex-col">
                                            <span class="text-white text-sm font-semibold">{{ $transaction->category->name }}</span>
                                            @if($transaction->subCategory)
                                                <span class="text-gray-400 text-xs">{{ $transaction->subCategory->name }}</span>
                                            @endif
                                            @if($transaction->matchedKeyword)
                                                <span class="text-cyan-400 text-xs mt-1">
                                                    <i class="fas fa-key mr-1"></i>{{ $transaction->matchedKeyword->keyword }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-sm">Uncategorized</span>
                                    @endif
                                </td>

                                {{-- Transaction Type --}}
                                <td class="px-6 py-4 text-center">
                                    @if($transaction->transaction_type === 'debit')
                                        <span class="px-3 py-1 bg-green-900/30 text-green-400 rounded-full text-xs font-semibold border border-green-500/30">
                                            <i class="fas fa-arrow-down mr-1"></i>DEBIT
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-red-900/30 text-red-400 rounded-full text-xs font-semibold border border-red-500/30">
                                            <i class="fas fa-arrow-up mr-1"></i>CREDIT
                                        </span>
                                    @endif
                                </td>

                                {{-- Amount --}}
                                <td class="px-6 py-4 text-right">
                                    <span class="text-white font-bold text-base">
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center space-y-1">
                                        @if($transaction->is_verified)
                                            <span class="px-3 py-1 bg-green-900/30 text-green-400 rounded-full text-xs font-semibold border border-green-500/30">
                                                <i class="fas fa-check-circle mr-1"></i>Verified
                                            </span>
                                        @elseif($transaction->matched_keyword_id)
                                            <span class="px-3 py-1 bg-blue-900/30 text-blue-400 rounded-full text-xs font-semibold border border-blue-500/30">
                                                <i class="fas fa-link mr-1"></i>Matched
                                            </span>
                                        @else
                                            <span class="px-3 py-1 bg-gray-900/30 text-gray-400 rounded-full text-xs font-semibold border border-gray-500/30">
                                                <i class="fas fa-circle mr-1"></i>Pending
                                            </span>
                                        @endif

                                        @if($transaction->account_confidence_score)
                                            <span class="text-xs text-gray-500">
                                                {{ $transaction->account_confidence_score }}%
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center space-y-3">
                                        <i class="fas fa-inbox text-gray-600 text-4xl"></i>
                                        <p class="text-gray-400">No transactions found for this account in {{ $monthName }} {{ $year }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if($transactions->count() > 0)
                        <tfoot class="bg-cyan-900/30 border-t-2 border-cyan-500/50">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-white font-bold">
                                    TOTAL (This Page)
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-white font-bold text-base">
                                        Rp {{ number_format($transactions->sum('amount'), 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white font-bold">
                                        {{ $transactions->count() }} txn
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
                <div class="p-6 border-t border-slate-700">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        {{-- Chart Visualization --}}
        @if($transactions->count() > 0)
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Daily Breakdown --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-white font-bold text-lg mb-4">
                        <i class="fas fa-chart-bar mr-2"></i>Daily Transaction Amount
                    </h3>
                    <canvas id="dailyChart"></canvas>
                </div>

                {{-- Transaction Type Distribution --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-white font-bold text-lg mb-4">
                        <i class="fas fa-chart-pie mr-2"></i>Transaction Type
                    </h3>
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            {{-- Category Breakdown --}}
            <div class="mt-6 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-white font-bold text-lg mb-4">
                    <i class="fas fa-tags mr-2"></i>Category Breakdown
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                        $categoryBreakdown = $transactions->groupBy('category_id')->map(function($items) {
                            return [
                                'category' => $items->first()->category,
                                'total' => $items->sum('amount'),
                                'count' => $items->count(),
                            ];
                        })->sortByDesc('total');
                    @endphp

                    @foreach($categoryBreakdown as $data)
                        @if($data['category'])
                            <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-white font-semibold">{{ $data['category']->name }}</span>
                                    <span class="text-gray-400 text-xs">{{ $data['count'] }} txn</span>
                                </div>
                                <p class="text-cyan-400 font-bold">Rp {{ number_format($data['total'], 0, ',', '.') }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        @if($transactions->count() > 0)
        // Daily Transaction Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = @json(
            $transactions->groupBy(function($txn) {
                return \Carbon\Carbon::parse($txn->transaction_date)->format('d M');
            })->map(function($items) {
                return $items->sum('amount');
            })
        );

        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(dailyData),
                datasets: [{
                    label: 'Amount',
                    data: Object.values(dailyData),
                    backgroundColor: 'rgba(34, 211, 238, 0.8)',
                    borderColor: 'rgba(34, 211, 238, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
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

        // Transaction Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const debitCount = {{ $transactions->where('transaction_type', 'debit')->count() }};
        const creditCount = {{ $transactions->where('transaction_type', 'credit')->count() }};

        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Debit', 'Credit'],
                datasets: [{
                    data: [debitCount, creditCount],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
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
                    }
                }
            }
        });
        @endif
    </script>
    @endpush
</x-app-layout>