<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Subscribers: {{ $plan->name }}
            </h2>
            <a href="{{ route('admin.plans.show', $plan) }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back to Plan
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Plan Summary Card -->
        <div class="bg-gradient-to-r from-{{ $plan->billing_period === 'monthly' ? 'blue' : 'green' }}-600 to-{{ $plan->billing_period === 'monthly' ? 'purple' : 'teal' }}-600 rounded-2xl p-6 mb-8 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-2">{{ $plan->name }}</h3>
                    <p class="text-blue-100">
                        Rp {{ number_format($plan->price, 0, ',', '.') }}/{{ $plan->billing_period === 'monthly' ? 'month' : 'year' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-blue-100 text-sm">Total Subscribers</p>
                    <p class="text-white text-4xl font-bold">{{ $subscribers->total() }}</p>
                </div>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-700">
                <h3 class="text-xl font-bold text-white">
                    <i class="fas fa-users text-blue-500 mr-2"></i>
                    All Subscribers
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Started</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ends</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($subscribers as $subscription)
                        <tr class="hover:bg-slate-900/30 transition">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $subscription->company->name }}</p>
                                    <p class="text-gray-400 text-xs">{{ $subscription->company->subdomain }}.matchfinance.app</p>
                                    @if($subscription->company->domain)
                                    <p class="text-blue-400 text-xs">
                                        <i class="fas fa-globe mr-1"></i>{{ $subscription->company->domain }}
                                    </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($subscription->company->owner)
                                <div>
                                    <p class="text-white text-sm">{{ $subscription->company->owner->name }}</p>
                                    <p class="text-gray-400 text-xs">{{ $subscription->company->owner->email }}</p>
                                </div>
                                @else
                                <span class="text-gray-500 text-sm">No owner</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($subscription->status === 'active') bg-green-900/30 text-green-400
                                    @elseif($subscription->status === 'cancelled') bg-red-900/30 text-red-400
                                    @elseif($subscription->status === 'expired') bg-gray-900/30 text-gray-400
                                    @else bg-yellow-900/30 text-yellow-400
                                    @endif">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-white text-sm">{{ $subscription->starts_at->format('d M Y') }}</p>
                                <p class="text-gray-400 text-xs">{{ $subscription->starts_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($subscription->ends_at)
                                <p class="text-white text-sm">{{ $subscription->ends_at->format('d M Y') }}</p>
                                <p class="text-gray-400 text-xs">
                                    @if($subscription->ends_at->isFuture())
                                    {{ $subscription->ends_at->diffForHumans() }}
                                    @else
                                    Expired
                                    @endif
                                </p>
                                @else
                                <span class="text-gray-400 text-sm">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-white font-semibold">{{ $subscription->company->users_count ?? 0 }}</span>
                                <span class="text-gray-400 text-xs">users</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.companies.show', $subscription->company) }}" 
                                       class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-semibold">
                                        <i class="fas fa-building mr-1"></i>View Company
                                    </a>
                                    <a href="{{ route('admin.subscriptions.show', $subscription) }}" 
                                       class="px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-xs font-semibold">
                                        <i class="fas fa-receipt mr-1"></i>Subscription
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-users text-gray-600 text-4xl mb-4"></i>
                                <p class="text-gray-400">No subscribers found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscribers->hasPages())
            <div class="px-6 py-4 border-t border-slate-700">
                {{ $subscribers->links() }}
            </div>
            @endif
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    <span class="text-green-400 text-xs font-semibold">Active</span>
                </div>
                <p class="text-white text-3xl font-bold">
                    {{ $subscribers->where('status', 'active')->count() }}
                </p>
                <p class="text-gray-400 text-xs mt-1">Active subscriptions</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                    <span class="text-red-400 text-xs font-semibold">Cancelled</span>
                </div>
                <p class="text-white text-3xl font-bold">
                    {{ $subscribers->where('status', 'cancelled')->count() }}
                </p>
                <p class="text-gray-400 text-xs mt-1">Cancelled subscriptions</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-clock text-gray-500 text-2xl"></i>
                    <span class="text-gray-400 text-xs font-semibold">Expired</span>
                </div>
                <p class="text-white text-3xl font-bold">
                    {{ $subscribers->where('status', 'expired')->count() }}
                </p>
                <p class="text-gray-400 text-xs mt-1">Expired subscriptions</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-dollar-sign text-blue-500 text-2xl"></i>
                    <span class="text-blue-400 text-xs font-semibold">Revenue</span>
                </div>
                <p class="text-white text-2xl font-bold">
                    Rp {{ number_format($plan->price * $subscribers->where('status', 'active')->count(), 0, ',', '.') }}
                </p>
                <p class="text-gray-400 text-xs mt-1">
                    Per {{ $plan->billing_period === 'monthly' ? 'month' : 'year' }}
                </p>
            </div>
        </div>

    </div>
</x-app-layout>