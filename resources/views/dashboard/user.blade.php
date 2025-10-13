<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
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
                        View your transactions and financial reports here.
                    </p>
                </div>
                <div class="hidden lg:block">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-chart-bar text-white text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Statements -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Statements</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_bank_statements'] ?? 0) }}</p>
            </div>

            <!-- Total Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Transactions</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_transactions'] ?? 0) }}</p>
            </div>

            <!-- Verified -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
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

            <!-- Matched -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-tags text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Matched</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['matched_transactions'] ?? 0) }}</p>
                <div class="mt-3 text-xs text-gray-400">
                    of {{ number_format($stats['total_transactions'] ?? 0) }} total
                </div>
            </div>

        </div>

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
                                        {{ Str::limit($transaction->description, 30) }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-gray-400 text-xs">
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                        </span>
                                        @if($transaction->subCategory)
                                            <span class="text-xs px-2 py-0.5 bg-blue-900/30 text-blue-400 rounded">
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
                                @if($transaction->is_verified)
                                    <span class="text-xs text-green-500">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                @else
                                    <span class="text-xs text-yellow-500">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                @endif
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

            <!-- Quick Actions & Recent Statements -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Quick Actions
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('transactions.index') }}" class="bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-list text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold text-sm">View Transactions</p>
                        </a>
                        
                        <a href="{{ route('profile.edit') }}" class="bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-user text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold text-sm">Edit Profile</p>
                        </a>
                    </div>
                </div>

                <!-- Recent Statements -->
                @if(isset($recentStatements))
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">
                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                        Recent Statements
                    </h3>
                    <div class="space-y-2">
                        @forelse($recentStatements as $statement)
                            <div class="p-3 bg-slate-900/50 rounded-lg flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @if($statement->bank->logo)
                                        <img src="{{ asset('storage/' . $statement->bank->logo) }}" alt="{{ $statement->bank->name }}" class="w-6 h-6 rounded">
                                    @else
                                        <div class="w-6 h-6 bg-blue-600 rounded flex items-center justify-center">
                                            <i class="fas fa-university text-white text-xs"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-white text-xs font-medium">{{ $statement->bank->name }}</p>
                                        <p class="text-gray-400 text-xs">{{ $statement->total_transactions ?? 0 }} trans.</p>
                                    </div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded
                                    @if($statement->ocr_status === 'completed') bg-green-900/30 text-green-400
                                    @elseif($statement->ocr_status === 'processing') bg-blue-900/30 text-blue-400
                                    @else bg-yellow-900/30 text-yellow-400
                                    @endif">
                                    {{ ucfirst($statement->ocr_status) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm text-center py-4">No statements yet</p>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

        </div>

        <!-- Charts Row -->
        @if(isset($transactionsByType) && $transactionsByType->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Transactions by Type -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
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
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Transactions by Category -->
            @if(isset($transactionsByCategory) && $transactionsByCategory->isNotEmpty())
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-chart-pie text-purple-500 mr-2"></i>
                    Top Categories
                </h3>
                <div class="space-y-3">
                    @foreach($transactionsByCategory as $category)
                    <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-{{ $category->color ?? 'blue' }}-500"></div>
                            <span class="text-gray-300 text-sm">{{ $category->name }}</span>
                        </div>
                        <span class="text-white font-bold">{{ number_format($category->count) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
        @endif

    </div>
</x-app-layout>