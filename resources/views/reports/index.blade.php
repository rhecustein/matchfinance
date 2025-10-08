<x-app-layout>
    <x-slot name="header">Financial Reports</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-white mb-2">
                <i class="fas fa-chart-line mr-2"></i>Financial Reports
            </h2>
            <p class="text-gray-400">Generate comprehensive financial reports and analytics</p>
        </div>

        {{-- Report Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            
            {{-- 1. Monthly by Bank Report --}}
            <div class="bg-gradient-to-br from-blue-900/20 to-slate-900 rounded-2xl p-6 border border-blue-500/30 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-xs font-semibold">
                        Monthly
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Monthly by Bank</h3>
                <p class="text-gray-400 text-sm mb-4">View monthly transactions grouped by bank accounts</p>
                <button onclick="showReportModal('monthly')" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-eye mr-2"></i>Generate Report
                </button>
            </div>

            {{-- 2. By Keyword Report --}}
            <div class="bg-gradient-to-br from-purple-900/20 to-slate-900 rounded-2xl p-6 border border-purple-500/30 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-full text-xs font-semibold">
                        Keywords
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">By Keyword</h3>
                <p class="text-gray-400 text-sm mb-4">Analyze transactions by matched keywords</p>
                <button onclick="showReportModal('keyword')" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-eye mr-2"></i>Generate Report
                </button>
            </div>

            {{-- 3. By Category Report --}}
            <div class="bg-gradient-to-br from-green-900/20 to-slate-900 rounded-2xl p-6 border border-green-500/30 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-folder text-white text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-full text-xs font-semibold">
                        Categories
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">By Category</h3>
                <p class="text-gray-400 text-sm mb-4">Group transactions by main categories</p>
                <button onclick="showReportModal('category')" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-eye mr-2"></i>Generate Report
                </button>
            </div>

            {{-- 4. By Sub Category Report --}}
            <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-yellow-600/20 text-yellow-400 rounded-full text-xs font-semibold">
                        Sub Categories
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">By Sub Category</h3>
                <p class="text-gray-400 text-sm mb-4">Detailed breakdown by sub categories</p>
                <button onclick="showReportModal('subcategory')" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-eye mr-2"></i>Generate Report
                </button>
            </div>

            {{-- 5. Comparison Report --}}
            <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-balance-scale text-white text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-red-600/20 text-red-400 rounded-full text-xs font-semibold">
                        Compare
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Bank Comparison</h3>
                <p class="text-gray-400 text-sm mb-4">Compare two banks side by side</p>
                <button onclick="showReportModal('comparison')" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-eye mr-2"></i>Generate Report
                </button>
            </div>

            {{-- 6. Custom Report --}}
            <div class="bg-gradient-to-br from-indigo-900/20 to-slate-900 rounded-2xl p-6 border border-indigo-500/30 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-cog text-white text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-indigo-600/20 text-indigo-400 rounded-full text-xs font-semibold">
                        Custom
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Custom Report</h3>
                <p class="text-gray-400 text-sm mb-4">Build your own custom report</p>
                <button onclick="alert('Coming Soon!')" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-wrench mr-2"></i>Build Report
                </button>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
            <h3 class="text-white font-bold text-lg mb-4">
                <i class="fas fa-info-circle mr-2"></i>Available Data
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="text-center">
                    <p class="text-gray-400 text-sm mb-1">Banks</p>
                    <p class="text-white text-2xl font-bold">{{ $availableBanks->count() }}</p>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 text-sm mb-1">Years with Data</p>
                    <p class="text-white text-2xl font-bold">{{ $transactionYears->count() }}</p>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 text-sm mb-1">Earliest</p>
                    <p class="text-white text-2xl font-bold">{{ $transactionYears->last() ?? '-' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 text-sm mb-1">Latest</p>
                    <p class="text-white text-2xl font-bold">{{ $transactionYears->first() ?? '-' }}</p>
                </div>
            </div>

            {{-- Year Timeline --}}
            @if($transactionYears->count() > 0)
                <div class="pt-4 border-t border-slate-700">
                    <p class="text-sm text-gray-400 mb-3">Years with Transaction Data:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($transactionYears as $year)
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-lg text-sm font-semibold border border-blue-500/30">
                                {{ $year }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal untuk Filter --}}
    <div id="reportModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-2xl max-w-lg w-full mx-4">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-white font-bold text-xl" id="modalTitle">Generate Report</h3>
                <button onclick="hideReportModal()" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="reportForm" method="GET">
                <div class="space-y-4">
                    {{-- Year --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Year
                        </label>
                        <select name="year" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                            <optgroup label="Available Years (with data)">
                                @foreach($availableYears as $year)
                                    @if($transactionYears->contains($year))
                                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                            <optgroup label="All Years (2015-2027)">
                                @foreach($availableYears as $year)
                                    @if(!$transactionYears->contains($year))
                                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                            {{ $year }} (no data)
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>Years are grouped by data availability
                        </p>
                    </div>

                    {{-- Transaction Type --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-exchange-alt mr-1"></i>Transaction Type
                        </label>
                        <select name="transaction_type" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Transactions</option>
                            <option value="debit">Debit Only</option>
                            <option value="credit">Credit Only</option>
                        </select>
                    </div>

                    {{-- Additional filters akan ditambah via JS --}}
                    <div id="additionalFilters"></div>

                    {{-- Buttons --}}
                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-chart-bar mr-2"></i>Generate
                        </button>
                        <button type="button" onclick="hideReportModal()" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-semibold transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showReportModal(type) {
            const modal = document.getElementById('reportModal');
            const form = document.getElementById('reportForm');
            const modalTitle = document.getElementById('modalTitle');
            const additionalFilters = document.getElementById('additionalFilters');
            
            // Reset
            additionalFilters.innerHTML = '';
            
            // Set action dan title berdasarkan type
            switch(type) {
                case 'monthly':
                    form.action = '{{ route("reports.monthly-by-bank") }}';
                    modalTitle.textContent = 'Monthly by Bank Report';
                    break;
                case 'keyword':
                    form.action = '{{ route("reports.by-keyword") }}';
                    modalTitle.textContent = 'By Keyword Report';
                    additionalFilters.innerHTML = `
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-university mr-1"></i>Bank (Optional)
                            </label>
                            <select name="bank_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">All Banks</option>
                                @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    `;
                    break;
                case 'category':
                    form.action = '{{ route("reports.by-category") }}';
                    modalTitle.textContent = 'By Category Report';
                    additionalFilters.innerHTML = `
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-university mr-1"></i>Bank (Optional)
                            </label>
                            <select name="bank_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">All Banks</option>
                                @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    `;
                    break;
                case 'subcategory':
                    form.action = '{{ route("reports.by-sub-category") }}';
                    modalTitle.textContent = 'By Sub Category Report';
                    additionalFilters.innerHTML = `
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-university mr-1"></i>Bank (Optional)
                            </label>
                            <select name="bank_id" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">All Banks</option>
                                @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    `;
                    break;
                case 'comparison':
                    form.action = '{{ route("reports.comparison") }}';
                    modalTitle.textContent = 'Bank Comparison Report';
                    additionalFilters.innerHTML = `
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-university mr-1"></i>Bank 1
                            </label>
                            <select name="bank_1" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Bank</option>
                                @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-university mr-1"></i>Bank 2
                            </label>
                            <select name="bank_2" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Bank</option>
                                @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    `;
                    break;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function hideReportModal() {
            const modal = document.getElementById('reportModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Close on outside click
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideReportModal();
            }
        });
    </script>
</x-app-layout>