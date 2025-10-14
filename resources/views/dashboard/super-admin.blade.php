<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
            <div class="flex gap-2">
                <button onclick="refreshStats()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
                <form action="{{ route('dashboard.clear-cache') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                        <i class="fas fa-trash mr-2"></i>Clear Cache
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">
                        Welcome back, {{ auth()->user()->name }}! 👋
                    </h2>
                    <p class="text-blue-100">
                        You have admin access to manage all system features.
                    </p>
                </div>
                <div class="hidden lg:block">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-chart-line text-white text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Statements -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-gray-400">
                        {{ $stats['completed_ocr'] ?? 0 }} completed
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Statements</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_bank_statements'] ?? 0) }}</p>
                <div class="mt-3 flex gap-2 text-xs">
                    <span class="text-yellow-500">
                        <i class="fas fa-clock"></i> {{ $stats['pending_ocr'] ?? 0 }} pending
                    </span>
                    <span class="text-red-500">
                        <i class="fas fa-times-circle"></i> {{ $stats['failed_ocr'] ?? 0 }} failed
                    </span>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-sm font-semibold">
                        {{ number_format($matchingStats['matching_percentage'] ?? 0, 1) }}% matched
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Transactions</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_transactions'] ?? 0) }}</p>
                <div class="mt-3 text-xs text-gray-400">
                    {{ number_format($matchingStats['matched_count'] ?? 0) }} matched / 
                    {{ number_format($matchingStats['unmatched_count'] ?? 0) }} unmatched
                </div>
            </div>

            <!-- Verified Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <span class="text-gray-400 text-sm font-semibold">
                        {{ $stats['verified_transactions'] ?? 0 }}/{{ $stats['total_transactions'] ?? 0 }}
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Verified</h3>
                <p class="text-white text-3xl font-bold">
                    {{ $stats['total_transactions'] > 0 ? round(($stats['verified_transactions'] / $stats['total_transactions']) * 100, 1) : 0 }}%
                </p>
                <div class="mt-3">
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['total_transactions'] > 0 ? ($stats['verified_transactions'] / $stats['total_transactions']) * 100 : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Master Data -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-database text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-xs font-semibold">
                        {{ $stats['active_keywords'] ?? 0 }} active
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Master Data</h3>
                <p class="text-white text-3xl font-bold">{{ $stats['total_keywords'] ?? 0 }}</p>
                <div class="mt-3 text-xs text-gray-400">
                    {{ $stats['total_types'] ?? 0 }} types, 
                    {{ $stats['total_categories'] ?? 0 }} categories
                </div>
            </div>

        </div>

        <!-- OCR Processing Status -->
        @if(isset($ocrStatus) && !empty($ocrStatus))
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-robot text-blue-500 mr-2"></i>
                OCR Processing Status
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-yellow-900/20 border border-yellow-600 rounded-xl p-4 text-center">
                    <i class="fas fa-clock text-yellow-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $ocrStatus['pending'] ?? 0 }}</p>
                    <p class="text-yellow-500 text-sm">Pending</p>
                </div>
                <div class="bg-blue-900/20 border border-blue-600 rounded-xl p-4 text-center">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $ocrStatus['processing'] ?? 0 }}</p>
                    <p class="text-blue-500 text-sm">Processing</p>
                </div>
                <div class="bg-green-900/20 border border-green-600 rounded-xl p-4 text-center">
                    <i class="fas fa-check text-green-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $ocrStatus['completed'] ?? 0 }}</p>
                    <p class="text-green-500 text-sm">Completed</p>
                </div>
                <div class="bg-red-900/20 border border-red-600 rounded-xl p-4 text-center">
                    <i class="fas fa-times text-red-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $ocrStatus['failed'] ?? 0 }}</p>
                    <p class="text-red-500 text-sm">Failed</p>
                </div>
            </div>
            @if(isset($avgProcessingTime) && $avgProcessingTime)
            <div class="mt-4 text-center">
                <p class="text-gray-400 text-sm">
                    <i class="fas fa-stopwatch mr-2"></i>
                    Average Processing Time: <span class="text-white font-semibold">{{ round($avgProcessingTime, 2) }}s</span>
                </p>
            </div>
            @endif
        </div>
        @endif

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Recent Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-receipt text-blue-500 mr-2"></i>
                        Recent Transactions
                    </h3>
                    <a href="{{ route('transactions.index') }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                        View All →
                    </a>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($recentTransactions ?? [] as $transaction)
                        <div class="flex items-center justify-between p-4 bg-slate-900/50 rounded-xl hover:bg-slate-900 transition border border-slate-700/50">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <div class="w-10 h-10 {{ $transaction->transaction_type === 'credit' ? 'bg-green-600/20' : 'bg-red-600/20' }} rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-{{ $transaction->transaction_type === 'credit' ? 'arrow-down' : 'arrow-up' }} text-{{ $transaction->transaction_type === 'credit' ? 'green' : 'red' }}-500"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-semibold text-sm truncate">
                                        {{ Str::limit($transaction->description, 40) }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-gray-400 text-xs">
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                        </span>
                                        @if($transaction->subCategory)
                                            <span class="text-xs px-2 py-0.5 bg-{{ $transaction->category->color ?? 'blue' }}-900/30 text-{{ $transaction->category->color ?? 'blue' }}-400 rounded">
                                                {{ $transaction->subCategory->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <p class="text-white font-bold text-sm">
                                    Rp {{ number_format($transaction->transaction_type === 'credit' ? $transaction->credit_amount : $transaction->debit_amount, 0, ',', '.') }}
                                </p>
                                <div class="flex items-center gap-2 justify-end mt-1">
                                    @if($transaction->is_verified)
                                        <span class="text-xs text-green-500">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    @else
                                        <span class="text-xs text-yellow-500">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                    @endif
                                    @if($transaction->confidence_score)
                                        <span class="text-xs text-gray-400">
                                            {{ $transaction->confidence_score }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400">No transactions yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Bank Statements -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                        Recent Statements
                    </h3>
                    <a href="{{ route('bank-statements.index') }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                        View All →
                    </a>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($recentStatements ?? [] as $statement)
                        <div class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-900 transition border border-slate-700/50">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    @if($statement->bank->logo)
                                        <img src="{{ asset('storage/' . $statement->bank->logo) }}" alt="{{ $statement->bank->name }}" class="w-8 h-8 rounded">
                                    @else
                                        <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                                            <i class="fas fa-university text-white text-xs"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-white font-semibold text-sm">
                                            {{ $statement->bank->name }}
                                        </p>
                                        <p class="text-gray-400 text-xs">
                                            {{ $statement->original_filename }}
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($statement->ocr_status === 'completed') bg-green-900/30 text-green-400
                                    @elseif($statement->ocr_status === 'processing') bg-blue-900/30 text-blue-400
                                    @elseif($statement->ocr_status === 'failed') bg-red-900/30 text-red-400
                                    @else bg-yellow-900/30 text-yellow-400
                                    @endif">
                                    {{ ucfirst($statement->ocr_status) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">
                                    <i class="fas fa-calendar mr-1"></i>
                                    {{ $statement->period_from?->format('M Y') }} - {{ $statement->period_to?->format('M Y') }}
                                </span>
                                <span class="text-gray-400">
                                    {{ $statement->total_transactions ?? 0 }} transactions
                                </span>
                            </div>
                            @if($statement->matched_transactions > 0)
                            <div class="mt-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-400">Matching Progress</span>
                                    <span class="text-white">{{ round(($statement->matched_transactions / max($statement->total_transactions, 1)) * 100) }}%</span>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-1.5">
                                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ ($statement->matched_transactions / max($statement->total_transactions, 1)) * 100 }}%"></div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-file-pdf text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400">No statements uploaded yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Transactions by Type -->
            @if(isset($transactionsByType) && $transactionsByType->isNotEmpty())
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-chart-pie text-purple-500 mr-2"></i>
                    Transactions by Type
                </h3>
                <div class="space-y-4">
                    @foreach($transactionsByType as $type)
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-300 text-sm">{{ $type->name }}</span>
                            <span class="text-white font-bold">{{ number_format($type->count) }}</span>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-3 rounded-full" 
                                 style="width: {{ ($type->count / $transactionsByType->sum('count')) * 100 }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>Credit: Rp {{ number_format($type->total_credit, 0, ',', '.') }}</span>
                            <span>Debit: Rp {{ number_format($type->total_debit, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Top Keywords -->
            @if(isset($topKeywords) && $topKeywords->isNotEmpty())
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-fire text-orange-500 mr-2"></i>
                    Top Matched Keywords
                </h3>
                <div class="space-y-3">
                    @foreach($topKeywords as $index => $keyword)
                    <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-600 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-sm">{{ $index + 1 }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-semibold text-sm truncate">{{ $keyword->keyword }}</p>
                            <p class="text-gray-400 text-xs">{{ $keyword->sub_category_name }}</p>
                        </div>
                        <span class="text-white font-bold">{{ $keyword->match_count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        <!-- Bank Distribution -->
        @if(isset($bankDistribution) && $bankDistribution->isNotEmpty())
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-university text-blue-500 mr-2"></i>
                Bank Distribution
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($bankDistribution as $bank)
                <div class="bg-slate-900/50 rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="w-12 h-12 bg-blue-600/20 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <i class="fas fa-university text-blue-500"></i>
                    </div>
                    <p class="text-white font-bold text-lg">{{ $bank->statement_count }}</p>
                    <p class="text-gray-400 text-xs mb-1">{{ $bank->name }}</p>
                    <p class="text-gray-500 text-xs">{{ number_format($bank->transaction_count) }} trans.</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                Quick Actions
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('bank-statements.create') }}" class="bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-upload text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">Upload Statement</p>
                </a>
                
                <a href="{{ route('transactions.index') }}" class="bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-list text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">View Transactions</p>
                </a>
                
                <a href="{{ route('banks.index') }}" class="bg-gradient-to-br from-pink-600 to-pink-700 hover:from-pink-700 hover:to-pink-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-university text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">Manage Banks</p>
                </a>
                
                <a href="" class="bg-gradient-to-br from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-users text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">User Management</p>
                </a>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function refreshStats() {
            // Show loading state
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            
            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    </script>
    @endpush
</x-app-layout>