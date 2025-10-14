<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Billing History
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('subscription.index') }}" 
                   class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <!-- Total Subscriptions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Subscriptions</p>
                        <p class="text-3xl font-bold text-white">{{ $subscriptions->total() }}</p>
                    </div>
                    <div class="p-3 bg-blue-900/30 rounded-lg">
                        <i class="fas fa-receipt text-blue-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Paid -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Paid</p>
                        <p class="text-3xl font-bold text-green-400">
                            Rp {{ number_format($totalPaid, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-900/30 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Company Info -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-6 border border-slate-700 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Company</p>
                        <p class="text-xl font-bold text-white truncate">{{ $company->name }}</p>
                        <p class="text-gray-400 text-xs">{{ $company->subdomain }}.app</p>
                    </div>
                    <div class="p-3 bg-purple-900/30 rounded-lg">
                        <i class="fas fa-building text-purple-400 text-2xl"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- Billing History Table -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-slate-700">
                <h3 class="text-xl font-bold text-white">Subscription History</h3>
                <p class="text-gray-400 text-sm mt-1">Complete history of all your subscriptions</p>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Plan</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Start Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">End Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Duration</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($subscriptions as $subscription)
                        <tr class="hover:bg-slate-800/50 transition">
                            
                            <!-- Plan -->
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $subscription->plan->name }}</p>
                                    <p class="text-gray-400 text-xs">
                                        {{ $subscription->plan->billing_period === 'monthly' ? 'Monthly' : 'Yearly' }}
                                    </p>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $subscription->statusBadgeClass }}">
                                    {{ $subscription->statusLabel }}
                                </span>
                            </td>

                            <!-- Start Date -->
                            <td class="px-6 py-4">
                                <p class="text-white text-sm">{{ $subscription->starts_at->format('d M Y') }}</p>
                                <p class="text-gray-400 text-xs">{{ $subscription->starts_at->format('H:i') }}</p>
                            </td>

                            <!-- End Date -->
                            <td class="px-6 py-4">
                                @if($subscription->ends_at)
                                    <p class="text-white text-sm">{{ $subscription->ends_at->format('d M Y') }}</p>
                                    <p class="text-gray-400 text-xs">{{ $subscription->ends_at->format('H:i') }}</p>
                                @else
                                    <p class="text-gray-400 text-sm">-</p>
                                @endif
                            </td>

                            <!-- Duration -->
                            <td class="px-6 py-4">
                                @if($subscription->ends_at)
                                    <p class="text-white text-sm font-semibold">
                                        {{ $subscription->getDurationInDays() }} days
                                    </p>
                                    <p class="text-gray-400 text-xs">
                                        {{ $subscription->getDurationInMonths() }} 
                                        {{ $subscription->getDurationInMonths() > 1 ? 'months' : 'month' }}
                                    </p>
                                @else
                                    <p class="text-gray-400 text-sm">-</p>
                                @endif
                            </td>

                            <!-- Amount -->
                            <td class="px-6 py-4">
                                <p class="text-white font-semibold">
                                    Rp {{ number_format($subscription->plan->price, 0, ',', '.') }}
                                </p>
                                @if($subscription->plan->billing_period === 'yearly')
                                <p class="text-gray-400 text-xs">
                                    Rp {{ number_format($subscription->plan->getMonthlyPrice(), 0, ',', '.') }}/mo
                                </p>
                                @endif
                            </td>

                            <!-- Created Date -->
                            <td class="px-6 py-4">
                                <p class="text-white text-sm">{{ $subscription->created_at->format('d M Y') }}</p>
                                <p class="text-gray-400 text-xs">{{ $subscription->created_at->diffForHumans() }}</p>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                                <p class="text-gray-400 text-lg">No billing history found</p>
                                <p class="text-gray-500 text-sm mt-2">Your subscription history will appear here</p>
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

        <!-- Additional Info Section -->
        @if($subscriptions->count() > 0)
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Quick Stats -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-chart-bar text-blue-400 mr-2"></i>
                    Quick Statistics
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">Active Subscriptions</span>
                        <span class="text-white font-semibold">
                            {{ $subscriptions->where('status', 'active')->count() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">Cancelled Subscriptions</span>
                        <span class="text-white font-semibold">
                            {{ $subscriptions->where('status', 'cancelled')->count() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">Expired Subscriptions</span>
                        <span class="text-white font-semibold">
                            {{ $subscriptions->where('status', 'expired')->count() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-slate-700">
                        <span class="text-gray-400 text-sm">Average Monthly Cost</span>
                        <span class="text-green-400 font-semibold">
                            @php
                                $monthlyTotal = 0;
                                $count = 0;
                                foreach($subscriptions as $sub) {
                                    if($sub->plan) {
                                        $monthlyTotal += $sub->plan->getMonthlyPrice();
                                        $count++;
                                    }
                                }
                                $average = $count > 0 ? $monthlyTotal / $count : 0;
                            @endphp
                            Rp {{ number_format($average, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Info -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                    Billing Information
                </h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-gray-400 text-sm mb-2">Company Name</p>
                        <p class="text-white font-semibold">{{ $company->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm mb-2">Subdomain</p>
                        <p class="text-white font-semibold">{{ $company->subdomain }}.app</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm mb-2">Owner</p>
                        <p class="text-white font-semibold">{{ $company->owner->name }}</p>
                        <p class="text-gray-500 text-xs">{{ $company->owner->email }}</p>
                    </div>
                    <div class="pt-4 border-t border-slate-700">
                        <p class="text-gray-400 text-sm mb-2">Need help with billing?</p>
                        <a href="mailto:support@example.com" 
                           class="text-blue-400 hover:text-blue-300 text-sm font-semibold">
                            <i class="fas fa-envelope mr-1"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>

        </div>
        @endif

        <!-- Export Option (Optional) -->
        @if($subscriptions->count() > 0)
        <div class="mt-6 text-center">
            <p class="text-gray-400 text-sm mb-3">Need a detailed report?</p>
            <button onclick="alert('Export feature coming soon!')" 
                    class="px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold">
                <i class="fas fa-download mr-2"></i>Export Billing History
            </button>
        </div>
        @endif

    </div>
</x-app-layout>