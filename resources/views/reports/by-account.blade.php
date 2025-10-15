<x-app-layout>
    <x-slot name="header">By Account Report - {{ $dateRange['label'] }}</x-slot>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('reports.index') }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="text-3xl font-bold text-white">By Account Report</h2>
                </div>
                <p class="text-gray-400">
                    Period: {{ $dateRange['label'] }} | 
                    Type: {{ ucfirst($transactionType) }}
                    @if($accountType)
                        | Account Type: {{ ucfirst($accountType) }}
                    @endif
                    @if($bankId)
                        @php $selectedBank = $banks->firstWhere('id', $bankId); @endphp
                        | Bank: {{ $selectedBank->name ?? 'Unknown' }}
                    @endif
                </p>
            </div>
            <div class="flex items-center space-x-3">
                {{-- Filter Button --}}
                <button onclick="showFilterModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                    <i class="fas fa-filter"></i>
                    <span>Filters</span>
                </button>
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
            <div class="bg-gradient-to-br from-cyan-900/20 to-slate-900 rounded-2xl p-6 border border-cyan-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Amount</p>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($grandTotal['total'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Total Transactions</p>
                <p class="text-white text-3xl font-bold">{{ number_format($grandTotal['count']) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Accounts</p>
                <p class="text-white text-3xl font-bold">{{ $accounts->count() }}</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl">
                <p class="text-gray-400 text-sm mb-1">Avg per Month</p>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($grandTotal['total'] / count($months), 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Report Table --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-white font-bold border-b border-slate-700 sticky left-0 bg-slate-900/50 z-10 min-w-[250px]">
                                Account (Code - Name)
                            </th>
                            @foreach($months as $monthData)
                                <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 min-w-[180px]">
                                    {{ $monthData['month'] }}
                                </th>
                            @endforeach
                            <th class="px-6 py-4 text-center text-white font-bold border-b border-slate-700 bg-cyan-900/30 min-w-[180px]">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 sticky left-0 bg-slate-900/90 z-10">
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-cyan-400 font-mono text-xs font-bold">{{ $account->code }}</span>
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $account->accountTypeBadgeClass }}">
                                                {{ $account->accountTypeLabel }}
                                            </span>
                                        </div>
                                        <span class="text-white font-semibold">{{ $account->name }}</span>
                                        @if($account->description)
                                            <span class="text-gray-400 text-xs">{{ Str::limit($account->description, 50) }}</span>
                                        @endif
                                    </div>
                                </td>
                                @foreach($months as $monthData)
                                    @php
                                        $accountData = $monthData['items'][$account->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <td class="px-6 py-4 text-center group relative">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-white font-bold">
                                                Rp {{ number_format($accountData['total'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-gray-400 text-xs">
                                                {{ number_format($accountData['count']) }} txn
                                            </span>
                                        </div>
                                        
                                        {{-- Detail Link (muncul on hover) --}}
                                        @if($accountData['count'] > 0)
                                            <a href="{{ route('reports.account-detail', [
                                                'year' => $dateRange['type'] === 'year' ? $dateRange['year'] : $monthData['start_date']->year,
                                                'month' => $monthData['month_number'],
                                                'account_id' => $account->id,
                                                'transaction_type' => $transactionType,
                                                'company_id' => $selectedCompanyId ?? null
                                            ]) }}"
                                                class="absolute inset-0 bg-cyan-600/0 hover:bg-cyan-600/20 transition opacity-0 group-hover:opacity-100 flex items-center justify-center"
                                                title="View transaction details">
                                                <span class="bg-cyan-600 text-white px-3 py-1 rounded-lg text-xs font-semibold shadow-lg">
                                                    <i class="fas fa-eye mr-1"></i> View Details
                                                </span>
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 text-center bg-cyan-900/20">
                                    @php
                                        $accountTotal = $grandTotal['items'][$account->id] ?? ['total' => 0, 'count' => 0];
                                    @endphp
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-white font-bold">
                                            Rp {{ number_format($accountTotal['total'], 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-400 text-xs">
                                            {{ number_format($accountTotal['count']) }} txn
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($months) + 2 }}" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center space-y-3">
                                        <i class="fas fa-inbox text-gray-600 text-4xl"></i>
                                        <p class="text-gray-400">No accounts found with the selected filters</p>
                                        <button onclick="showFilterModal()" class="text-cyan-400 hover:text-cyan-300 transition">
                                            <i class="fas fa-filter mr-1"></i>Adjust Filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        
                        {{-- Grand Total Row --}}
                        @if($accounts->count() > 0)
                            <tr class="bg-cyan-900/30 border-t-2 border-cyan-500/50">
                                <td class="px-6 py-4 text-white font-bold sticky left-0 bg-cyan-900/50 z-10">
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
                                <td class="px-6 py-4 text-center bg-cyan-900/40">
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
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Chart Visualization --}}
        @if($accounts->count() > 0)
            <div class="mt-8 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-white font-bold text-lg mb-4">
                    <i class="fas fa-chart-area mr-2"></i>Monthly Trend by Account
                </h3>
                <canvas id="accountChart" height="80"></canvas>
            </div>

            {{-- Account Type Breakdown --}}
            @if(!$accountType)
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-white font-bold text-lg mb-4">
                            <i class="fas fa-chart-pie mr-2"></i>By Account Type
                        </h3>
                        <canvas id="accountTypeChart"></canvas>
                    </div>
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-white font-bold text-lg mb-4">
                            <i class="fas fa-list mr-2"></i>Account Type Summary
                        </h3>
                        <div class="space-y-3">
                            @php
                                $typeBreakdown = [];
                                foreach($accounts as $account) {
                                    $type = $account->account_type;
                                    $accountTotal = $grandTotal['items'][$account->id] ?? ['total' => 0, 'count' => 0];
                                    if (!isset($typeBreakdown[$type])) {
                                        $typeBreakdown[$type] = ['total' => 0, 'count' => 0];
                                    }
                                    $typeBreakdown[$type]['total'] += $accountTotal['total'];
                                    $typeBreakdown[$type]['count'] += $accountTotal['count'];
                                }
                            @endphp
                            @foreach($typeBreakdown as $type => $data)
                                <div class="flex items-center justify-between p-4 bg-slate-900/50 rounded-lg border border-slate-700">
                                    <div class="flex items-center space-x-3">
                                        <span class="px-3 py-1 rounded text-xs font-semibold 
                                            @if($type === 'asset') bg-blue-100 text-blue-800
                                            @elseif($type === 'liability') bg-red-100 text-red-800
                                            @elseif($type === 'equity') bg-purple-100 text-purple-800
                                            @elseif($type === 'revenue') bg-green-100 text-green-800
                                            @elseif($type === 'expense') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($type) }}
                                        </span>
                                        <span class="text-gray-400 text-sm">{{ $data['count'] }} transactions</span>
                                    </div>
                                    <span class="text-white font-bold">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Filter Modal --}}
    <div id="filterModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-white font-bold text-xl">
                    <i class="fas fa-filter mr-2"></i>Report Filters
                </h3>
                <button onclick="hideFilterModal()" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('reports.by-account') }}">
                <div class="space-y-4">
                    {{-- Period Type --}}
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <label class="block text-sm font-semibold text-gray-300 mb-3">
                            <i class="fas fa-calendar-alt mr-1"></i>Period Type
                        </label>
                        <div class="flex gap-3">
                            <button type="button" onclick="setPeriodType('year')" id="yearTypeBtn" 
                                class="flex-1 px-4 py-2 {{ $dateRange['type'] === 'year' ? 'bg-cyan-600 text-white' : 'bg-slate-700 text-gray-300' }} rounded-lg font-semibold transition">
                                <i class="fas fa-calendar mr-2"></i>By Year
                            </button>
                            <button type="button" onclick="setPeriodType('custom')" id="customTypeBtn" 
                                class="flex-1 px-4 py-2 {{ $dateRange['type'] === 'custom' ? 'bg-cyan-600 text-white' : 'bg-slate-700 text-gray-300' }} rounded-lg font-semibold transition">
                                <i class="fas fa-calendar-week mr-2"></i>Custom Range
                            </button>
                        </div>
                    </div>

                    {{-- Year Filter --}}
                    <div id="yearFilter" class="{{ $dateRange['type'] === 'year' ? '' : 'hidden' }}">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Year
                        </label>
                        <select name="year" id="yearSelect" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                            @for($y = date('Y'); $y >= 2015; $y--)
                                <option value="{{ $y }}" {{ (isset($dateRange['year']) && $dateRange['year'] == $y) ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Custom Date Range --}}
                    <div id="customFilter" class="{{ $dateRange['type'] === 'custom' ? '' : 'hidden' }} space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">
                                    <i class="fas fa-calendar-day mr-1"></i>Start Date
                                </label>
                                <input type="date" name="start_date" id="startDate" 
                                    value="{{ $dateRange['type'] === 'custom' ? $dateRange['start']->format('Y-m-d') : '' }}"
                                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">
                                    <i class="fas fa-calendar-day mr-1"></i>End Date
                                </label>
                                <input type="date" name="end_date" id="endDate" 
                                    value="{{ $dateRange['type'] === 'custom' ? $dateRange['end']->format('Y-m-d') : '' }}"
                                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                            </div>
                        </div>
                    </div>

                    {{-- Account Type Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-tags mr-1"></i>Account Type
                        </label>
                        <select name="account_type" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                            <option value="">All Account Types</option>
                            @foreach($accountTypes as $key => $label)
                                <option value="{{ $key }}" {{ $accountType === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Bank Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-university mr-1"></i>Bank
                        </label>
                        <select name="bank_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                            <option value="">All Banks</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" {{ $bankId == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Transaction Type --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-exchange-alt mr-1"></i>Transaction Type
                        </label>
                        <select name="transaction_type" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                            <option value="all" {{ $transactionType === 'all' ? 'selected' : '' }}>All Transactions</option>
                            <option value="debit" {{ $transactionType === 'debit' ? 'selected' : '' }}>Debit Only</option>
                            <option value="credit" {{ $transactionType === 'credit' ? 'selected' : '' }}>Credit Only</option>
                        </select>
                    </div>

                    {{-- Super Admin Company Filter --}}
                    @if($companies->count() > 0)
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-building mr-1"></i>Company (Super Admin)
                            </label>
                            <select name="company_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-cyan-500">
                                <option value="">All Companies</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Buttons --}}
                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit" class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-check mr-2"></i>Apply Filters
                        </button>
                        <button type="button" onclick="hideFilterModal()" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-semibold transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Modal Functions
        function showFilterModal() {
            document.getElementById('filterModal').classList.remove('hidden');
            document.getElementById('filterModal').classList.add('flex');
        }
        
        function hideFilterModal() {
            document.getElementById('filterModal').classList.add('hidden');
            document.getElementById('filterModal').classList.remove('flex');
        }

        function setPeriodType(type) {
            const yearFilter = document.getElementById('yearFilter');
            const customFilter = document.getElementById('customFilter');
            const yearSelect = document.getElementById('yearSelect');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const yearTypeBtn = document.getElementById('yearTypeBtn');
            const customTypeBtn = document.getElementById('customTypeBtn');

            if (type === 'year') {
                yearFilter.classList.remove('hidden');
                customFilter.classList.add('hidden');
                yearSelect.required = true;
                startDate.required = false;
                endDate.required = false;
                
                yearTypeBtn.classList.remove('bg-slate-700', 'text-gray-300');
                yearTypeBtn.classList.add('bg-cyan-600', 'text-white');
                customTypeBtn.classList.remove('bg-cyan-600', 'text-white');
                customTypeBtn.classList.add('bg-slate-700', 'text-gray-300');
            } else {
                yearFilter.classList.add('hidden');
                customFilter.classList.remove('hidden');
                yearSelect.required = false;
                startDate.required = true;
                endDate.required = true;
                
                customTypeBtn.classList.remove('bg-slate-700', 'text-gray-300');
                customTypeBtn.classList.add('bg-cyan-600', 'text-white');
                yearTypeBtn.classList.remove('bg-cyan-600', 'text-white');
                yearTypeBtn.classList.add('bg-slate-700', 'text-gray-300');
            }
        }

        // Close modal on ESC or outside click
        document.getElementById('filterModal').addEventListener('click', function(e) {
            if (e.target === this) hideFilterModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideFilterModal();
        });

        @if($accounts->count() > 0)
        // Monthly Trend Chart
        const ctx = document.getElementById('accountChart').getContext('2d');
        const months = @json(array_column($months, 'month'));
        const datasets = [];
        
        // Limit to top 10 accounts by total
        const topAccounts = @json($accounts->take(10));
        
        topAccounts.forEach(account => {
            const accountData = @json($months).map(m => m.items[account.id] ? m.items[account.id].total : 0);
            datasets.push({
                label: account.code + ' - ' + account.name,
                data: accountData,
                borderWidth: 2,
                tension: 0.4
            });
        });
        
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

        @if(!$accountType)
        // Account Type Pie Chart
        const typeCtx = document.getElementById('accountTypeChart').getContext('2d');
        const typeData = @json($typeBreakdown ?? []);
        
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(typeData).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
                datasets: [{
                    data: Object.values(typeData).map(v => v.total),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',   // blue - asset
                        'rgba(239, 68, 68, 0.8)',    // red - liability
                        'rgba(168, 85, 247, 0.8)',   // purple - equity
                        'rgba(34, 197, 94, 0.8)',    // green - revenue
                        'rgba(249, 115, 22, 0.8)',   // orange - expense
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
        @endif
        @endif
    </script>
    @endpush
</x-app-layout>