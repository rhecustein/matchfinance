<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Super Admin Dashboard
            </h2>
            <div class="flex gap-2">
                <button onclick="refreshStats()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-red-600 to-pink-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">
                        Super Admin Panel 🔐
                    </h2>
                    <p class="text-red-100">
                        Welcome back, {{ auth()->user()->name }}! You have full system access.
                    </p>
                </div>
                <div class="hidden lg:block">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-crown text-white text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- MRR Card -->
            <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <span class="text-green-100 text-sm font-semibold">Monthly</span>
                </div>
                <h3 class="text-green-100 text-sm mb-2">Monthly Recurring Revenue</h3>
                <p class="text-white text-4xl font-bold">Rp {{ number_format($mrr, 0, ',', '.') }}</p>
                <p class="text-green-100 text-sm mt-2">
                    <i class="fas fa-chart-line mr-1"></i>
                    From {{ $subscriptionStats['active'] }} active subscriptions
                </p>
            </div>

            <!-- ARR Card -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <span class="text-blue-100 text-sm font-semibold">Annual</span>
                </div>
                <h3 class="text-blue-100 text-sm mb-2">Annual Recurring Revenue</h3>
                <p class="text-white text-4xl font-bold">Rp {{ number_format($arr, 0, ',', '.') }}</p>
                <p class="text-blue-100 text-sm mt-2">
                    <i class="fas fa-calendar mr-1"></i>
                    Projected yearly revenue
                </p>
            </div>
        </div>

        <!-- Main Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Companies -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-building text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-sm font-semibold">
                        {{ $companyStats['active'] }} active
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Companies</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($companyStats['total']) }}</p>
                <div class="mt-3 flex gap-2 text-xs">
                    <span class="text-yellow-500">
                        <i class="fas fa-clock"></i> {{ $companyStats['trial'] }} trial
                    </span>
                    <span class="text-red-500">
                        <i class="fas fa-pause-circle"></i> {{ $companyStats['suspended'] }} suspended
                    </span>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-sm font-semibold">
                        {{ $userStats['active'] }} active
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Users</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($userStats['total']) }}</p>
                <div class="mt-3 text-xs text-gray-400">
                    {{ $userStats['super_admins'] }} super admins / 
                    {{ $userStats['company_users'] }} company users
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <span class="text-yellow-500 text-sm font-semibold">
                        {{ $subscriptionStats['expiring_soon'] }} expiring
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Subscriptions</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($subscriptionStats['active']) }}</p>
                <div class="mt-3">
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $subscriptionStats['total'] > 0 ? ($subscriptionStats['active'] / $subscriptionStats['total']) * 100 : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:transform hover:scale-105 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                    <span class="text-blue-500 text-sm font-semibold">
                        {{ number_format($transactionStats['this_month']) }} this month
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Transactions</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($transactionStats['total']) }}</p>
                <div class="mt-3 text-xs text-gray-400">
                    {{ number_format($transactionStats['verified']) }} verified / 
                    {{ number_format($transactionStats['matched']) }} matched
                </div>
            </div>

        </div>

        <!-- Company Status Breakdown -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                Company Status Breakdown
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-green-900/20 border border-green-600 rounded-xl p-4 text-center">
                    <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $companyStats['active'] }}</p>
                    <p class="text-green-500 text-sm">Active</p>
                </div>
                <div class="bg-yellow-900/20 border border-yellow-600 rounded-xl p-4 text-center">
                    <i class="fas fa-clock text-yellow-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $companyStats['trial'] }}</p>
                    <p class="text-yellow-500 text-sm">Trial</p>
                </div>
                <div class="bg-orange-900/20 border border-orange-600 rounded-xl p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-orange-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $companyStats['trial_expiring_soon'] }}</p>
                    <p class="text-orange-500 text-sm">Expiring Soon</p>
                </div>
                <div class="bg-red-900/20 border border-red-600 rounded-xl p-4 text-center">
                    <i class="fas fa-pause-circle text-red-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $companyStats['suspended'] }}</p>
                    <p class="text-red-500 text-sm">Suspended</p>
                </div>
                <div class="bg-gray-900/20 border border-gray-600 rounded-xl p-4 text-center">
                    <i class="fas fa-times-circle text-gray-500 text-2xl mb-2"></i>
                    <p class="text-white text-2xl font-bold">{{ $companyStats['cancelled'] }}</p>
                    <p class="text-gray-500 text-sm">Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Recent Companies -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-building text-blue-500 mr-2"></i>
                        Recent Companies
                    </h3>
                    <a href="{{ route('admin.companies.index') }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                        View All →
                    </a>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($recentCompanies as $company)
                        <div class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-900 transition border border-slate-700/50">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-white font-semibold">{{ $company->name }}</p>
                                    <p class="text-gray-400 text-xs">{{ $company->subdomain }}.matchfinance.app</p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($company->status === 'active') bg-green-900/30 text-green-400
                                    @elseif($company->status === 'trial') bg-yellow-900/30 text-yellow-400
                                    @elseif($company->status === 'suspended') bg-red-900/30 text-red-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ ucfirst($company->status) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $company->owner?->name ?? 'No owner' }}
                                </span>
                                <span class="text-gray-400">
                                    <i class="fas fa-calendar mr-1"></i>
                                    {{ $company->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-building text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400">No companies yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Subscriptions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-credit-card text-green-500 mr-2"></i>
                        Recent Subscriptions
                    </h3>
                    <a href="{{ route('admin.subscriptions.index') }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                        View All →
                    </a>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($recentSubscriptions as $subscription)
                        <div class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-900 transition border border-slate-700/50">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-white font-semibold">{{ $subscription->company->name }}</p>
                                    <p class="text-gray-400 text-xs">{{ $subscription->plan->name }}</p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($subscription->status === 'active') bg-green-900/30 text-green-400
                                    @elseif($subscription->status === 'cancelled') bg-red-900/30 text-red-400
                                    @elseif($subscription->status === 'expired') bg-gray-900/30 text-gray-400
                                    @else bg-yellow-900/30 text-yellow-400
                                    @endif">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">
                                    <i class="fas fa-dollar-sign mr-1"></i>
                                    Rp {{ number_format($subscription->plan->price, 0, ',', '.') }}/{{ $subscription->plan->billing_period === 'monthly' ? 'mo' : 'yr' }}
                                </span>
                                <span class="text-gray-400">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Started {{ $subscription->starts_at->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-credit-card text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-400">No subscriptions yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Plan Statistics -->
        @if($planStats->isNotEmpty())
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-layer-group text-purple-500 mr-2"></i>
                Plan Distribution
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($planStats as $plan)
                <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700/50">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-white font-bold text-lg">{{ $plan['name'] }}</h4>
                        <span class="text-2xl">
                            @if($loop->index === 0) 🥉
                            @elseif($loop->index === 1) 🥈
                            @elseif($loop->index === 2) 🥇
                            @else 📦
                            @endif
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 text-sm">Subscribers</span>
                            <span class="text-white font-bold text-xl">{{ $plan['subscribers'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 text-sm">Price</span>
                            <span class="text-green-400 font-semibold">
                                Rp {{ number_format($plan['price'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 text-center mt-2">
                            {{ ucfirst($plan['billing_period']) }} billing
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Monthly Growth Chart -->
        @if($monthlyGrowth)
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-chart-line text-green-500 mr-2"></i>
                Growth Trend (Last 6 Months)
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($monthlyGrowth as $month)
                <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700/50">
                    <p class="text-gray-400 text-xs mb-3">{{ $month['month'] }}</p>
                    <div class="space-y-2">
                        <div>
                            <p class="text-xs text-gray-400">Companies</p>
                            <p class="text-white text-lg font-bold">{{ $month['companies'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Subscriptions</p>
                            <p class="text-green-400 text-lg font-bold">{{ $month['subscriptions'] }}</p>
                        </div>
                    </div>
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
                <a href="{{ route('admin.companies.index') }}" class="bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-building text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">Manage Companies</p>
                </a>
                
                <a href="{{ route('admin.plans.index') }}" class="bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-layer-group text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">Manage Plans</p>
                </a>
                
                <a href="{{ route('admin.subscriptions.index') }}" class="bg-gradient-to-br from-pink-600 to-pink-700 hover:from-pink-700 hover:to-pink-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-credit-card text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">Subscriptions</p>
                </a>
                
                <a href="{{ route('admin.system-users.index') }}" class="bg-gradient-to-br from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 rounded-xl p-6 text-center transition-all transform hover:scale-105">
                    <i class="fas fa-users text-white text-3xl mb-3"></i>
                    <p class="text-white font-semibold">System Users</p>
                </a>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function refreshStats() {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // Auto-refresh stats every 5 minutes
        setInterval(() => {
            fetch('{{ route('admin.dashboard.stats') }}')
                .then(response => response.json())
                .then(data => {
                    console.log('Stats updated:', data);
                    // You can update specific elements here if needed
                })
                .catch(error => console.error('Error fetching stats:', error));
        }, 300000); // 5 minutes
    </script>
    @endpush
</x-app-layout>