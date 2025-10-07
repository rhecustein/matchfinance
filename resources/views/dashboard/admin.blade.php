<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">
                        Welcome back, {{ auth()->user()->name }}! 👋
                    </h2>
                    <p class="text-blue-100">
                        @if(auth()->user()->isAdmin())
                            You have admin access to manage all system features.
                        @else
                            View your transactions and financial reports here.
                        @endif
                    </p>
                </div>
                <div class="hidden lg:block">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-chart-line text-white text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Stat Card 1 -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-sm font-semibold">+12%</span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Statements</h3>
                <p class="text-white text-3xl font-bold">{{ $stats['total_bank_statements'] ?? 0 }}</p>
            </div>

            <!-- Stat Card 2 -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-sm font-semibold">+8%</span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Transactions</h3>
                <p class="text-white text-3xl font-bold">{{ $stats['total_transactions'] ?? 0 }}</p>
            </div>

            <!-- Stat Card 3 -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <span class="text-gray-400 text-sm font-semibold">{{ $stats['verified_transactions'] ?? 0 }}/{{ $stats['total_transactions'] ?? 0 }}</span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Verified</h3>
                <p class="text-white text-3xl font-bold">{{ round((($stats['verified_transactions'] ?? 0) / max($stats['total_transactions'] ?? 1, 1)) * 100) }}%</p>
            </div>

            <!-- Stat Card 4 -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-university text-white text-xl"></i>
                    </div>
                    <span class="text-gray-400 text-sm font-semibold">Active</span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Banks</h3>
                <p class="text-white text-3xl font-bold">{{ $stats['total_banks'] ?? 0 }}</p>
            </div>

        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Recent Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">Recent Transactions</h3>
                    <a href="{{ route('transactions.index') }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">View All →</a>
                </div>
                
                <div class="space-y-4">
                    @forelse($recentTransactions ?? [] as $transaction)
                        <div class="flex items-center justify-between p-4 bg-slate-900/50 rounded-xl hover:bg-slate-900 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-receipt text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="text-white font-semibold text-sm">{{ Str::limit($transaction->description, 30) }}</p>
                                    <p class="text-gray-400 text-xs">{{ $transaction->transaction_date->format('d M Y') }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-white font-bold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
                                @if($transaction->is_verified)
                                    <span class="text-xs text-green-500"><i class="fas fa-check-circle"></i> Verified</span>
                                @else
                                    <span class="text-xs text-yellow-500"><i class="fas fa-clock"></i> Pending</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400">No transactions yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">Quick Actions</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('bank-statements.create') }}" class="bg-blue-600 hover:bg-blue-700 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-upload text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold">Upload Statement</p>
                        </a>
                        
                        <a href="{{ route('transactions.index') }}" class="bg-purple-600 hover:bg-purple-700 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-list text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold">View Transactions</p>
                        </a>
                        
                        <a href="{{ route('banks.index') }}" class="bg-pink-600 hover:bg-pink-700 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-university text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold">Manage Banks</p>
                        </a>
                        
                        <a href="{{ route('admin.users.index') }}" class="bg-teal-600 hover:bg-teal-700 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-users text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold">User Management</p>
                        </a>
                    @else
                        <a href="{{ route('transactions.index') }}" class="bg-blue-600 hover:bg-blue-700 rounded-xl p-6 text-center transition-all transform hover:scale-105 col-span-2">
                            <i class="fas fa-list text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold">View All Transactions</p>
                        </a>
                        
                        <a href="{{ route('profile.edit') }}" class="bg-purple-600 hover:bg-purple-700 rounded-xl p-6 text-center transition-all transform hover:scale-105 col-span-2">
                            <i class="fas fa-user text-white text-3xl mb-3"></i>
                            <p class="text-white font-semibold">Edit Profile</p>
                        </a>
                    @endif
                </div>
            </div>

        </div>

        <!-- Chart Section (Optional) -->
        @if(auth()->user()->isAdmin())
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">Transaction Overview</h3>
                <div class="h-64 flex items-center justify-center">
                    <p class="text-gray-400">Chart will be displayed here</p>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>