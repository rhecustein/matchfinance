<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Subscription Management
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Status Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <a href="{{ route('admin.subscriptions.index') }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition shadow-lg {{ !request('status') ? 'ring-2 ring-blue-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total</p>
                        <p class="text-3xl font-bold text-white">{{ $statusCounts['all'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-900/30 rounded-lg">
                        <i class="fas fa-layer-group text-blue-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.subscriptions.index', ['status' => 'active']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-green-500 transition shadow-lg {{ request('status') == 'active' ? 'ring-2 ring-green-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Active</p>
                        <p class="text-3xl font-bold text-green-400">{{ $statusCounts['active'] }}</p>
                    </div>
                    <div class="p-3 bg-green-900/30 rounded-lg">
                        <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.subscriptions.index', ['status' => 'cancelled']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-gray-500 transition shadow-lg {{ request('status') == 'cancelled' ? 'ring-2 ring-gray-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Cancelled</p>
                        <p class="text-3xl font-bold text-gray-400">{{ $statusCounts['cancelled'] }}</p>
                    </div>
                    <div class="p-3 bg-gray-900/30 rounded-lg">
                        <i class="fas fa-ban text-gray-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.subscriptions.index', ['status' => 'expired']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-red-500 transition shadow-lg {{ request('status') == 'expired' ? 'ring-2 ring-red-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Expired</p>
                        <p class="text-3xl font-bold text-red-400">{{ $statusCounts['expired'] }}</p>
                    </div>
                    <div class="p-3 bg-red-900/30 rounded-lg">
                        <i class="fas fa-times-circle text-red-400 text-2xl"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.subscriptions.index', ['status' => 'past_due']) }}" 
               class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 hover:border-yellow-500 transition shadow-lg {{ request('status') == 'past_due' ? 'ring-2 ring-yellow-500' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Past Due</p>
                        <p class="text-3xl font-bold text-yellow-400">{{ $statusCounts['past_due'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-900/30 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-2xl"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Search Company</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Company name..."
                           class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                </div>

                <!-- Plan Filter -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Plan</label>
                    <select name="plan_id" 
                            class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">All Plans</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
                    <select name="status" 
                            class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="past_due" {{ request('status') == 'past_due' ? 'selected' : '' }}>Past Due</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('admin.subscriptions.index') }}" 
                       class="px-6 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Subscriptions Table -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Company</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Plan</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Start Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">End Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Remaining</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($subscriptions as $subscription)
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $subscription->company->name }}</p>
                                    <p class="text-gray-400 text-sm">{{ $subscription->company->subdomain }}.app</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $subscription->plan->name }}</p>
                                    <p class="text-gray-400 text-sm">
                                        Rp {{ number_format($subscription->plan->price, 0, ',', '.') }} / {{ $subscription->plan->billing_period === 'monthly' ? 'month' : 'year' }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $subscription->statusBadgeClass }}">
                                    {{ $subscription->statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-white text-sm">{{ $subscription->starts_at->format('d M Y') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($subscription->ends_at)
                                    <p class="text-white text-sm">{{ $subscription->ends_at->format('d M Y') }}</p>
                                @else
                                    <p class="text-gray-400 text-sm">-</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($subscription->isActive() && $subscription->ends_at)
                                    @php
                                        $days = $subscription->daysRemaining;
                                    @endphp
                                    @if($days > 30)
                                        <span class="text-green-400 text-sm font-semibold">{{ $days }} days</span>
                                    @elseif($days > 7)
                                        <span class="text-yellow-400 text-sm font-semibold">{{ $days }} days</span>
                                    @else
                                        <span class="text-red-400 text-sm font-semibold">{{ $days }} days</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.subscriptions.show', $subscription) }}" 
                                       class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-semibold"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if($subscription->canBeCancelled())
                                        <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('Cancel this subscription?')"
                                                    class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-xs font-semibold"
                                                    title="Cancel">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($subscription->canBeRenewed())
                                        <form action="{{ route('admin.subscriptions.renew', $subscription) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('Renew this subscription?')"
                                                    class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-xs font-semibold"
                                                    title="Renew">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                                <p class="text-gray-400 text-lg">No subscriptions found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($subscriptions->hasPages())
            <div class="px-6 py-4 bg-slate-900/50 border-t border-slate-700">
                {{ $subscriptions->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>