<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Plan Details: {{ $plan->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.plans.edit', $plan) }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Plan Header Card -->
        <div class="bg-gradient-to-r from-{{ $plan->billing_period === 'monthly' ? 'blue' : 'green' }}-600 to-{{ $plan->billing_period === 'monthly' ? 'purple' : 'teal' }}-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <h2 class="text-3xl font-bold text-white">
                            {{ $plan->name }}
                        </h2>
                        @if($plan->is_active)
                        <span class="px-4 py-2 bg-green-500 text-white rounded-full text-sm font-semibold">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                        @else
                        <span class="px-4 py-2 bg-gray-500 text-white rounded-full text-sm font-semibold">
                            <i class="fas fa-pause-circle mr-1"></i>Inactive
                        </span>
                        @endif
                    </div>
                    
                    <div class="text-white mb-2">
                        <span class="text-4xl font-bold">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                        <span class="text-blue-100 text-xl">/{{ $plan->billing_period === 'monthly' ? 'month' : 'year' }}</span>
                    </div>

                    @if($plan->billing_period === 'yearly')
                    <p class="text-blue-100">
                        <i class="fas fa-calculator mr-1"></i>
                        Rp {{ number_format($plan->price / 12, 0, ',', '.') }}/month
                    </p>
                    @endif
                </div>

                <div class="hidden lg:block text-right">
                    <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-6">
                        <p class="text-blue-100 text-sm mb-1">Active Subscribers</p>
                        <p class="text-white text-4xl font-bold">{{ $plan->active_subscriptions_count }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Subscriptions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Subscriptions</h3>
                <p class="text-white text-3xl font-bold">{{ $plan->subscriptions_count }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    {{ $plan->active_subscriptions_count }} active
                </p>
            </div>

            <!-- Monthly Revenue -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Monthly Revenue</h3>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    From active subscribers
                </p>
            </div>

            <!-- Annual Revenue -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Annual Revenue</h3>
                <p class="text-white text-3xl font-bold">Rp {{ number_format($monthlyRevenue * 12, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    Projected yearly
                </p>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            <!-- Plan Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Plan Information
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Plan Name</span>
                        <span class="text-white font-semibold">{{ $plan->name }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Slug</span>
                        <span class="text-white font-mono text-sm">{{ $plan->slug }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Price</span>
                        <span class="text-green-400 font-bold">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Billing Period</span>
                        <span class="text-white font-semibold capitalize">{{ $plan->billing_period }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Status</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $plan->is_active ? 'bg-green-900/30 text-green-400' : 'bg-gray-900/30 text-gray-400' }}">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Created</span>
                        <span class="text-white">{{ $plan->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="text-gray-400">Last Updated</span>
                        <span class="text-white">{{ $plan->updated_at->format('d M Y H:i') }}</span>
                    </div>
                </div>

                @if($plan->description)
                <div class="mt-6 p-4 bg-slate-900/50 rounded-lg">
                    <p class="text-sm font-semibold text-gray-300 mb-2">Description</p>
                    <p class="text-gray-400 text-sm">{{ $plan->description }}</p>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="mt-6 flex flex-wrap gap-2">
                    <form action="{{ route('admin.plans.toggle-active', $plan) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2 {{ $plan->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition text-sm font-semibold">
                            <i class="fas fa-{{ $plan->is_active ? 'pause' : 'play' }} mr-2"></i>
                            {{ $plan->is_active ? 'Deactivate' : 'Activate' }} Plan
                        </button>
                    </form>

                    @if($plan->active_subscriptions_count == 0)
                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                onclick="return confirm('Delete this plan? This action cannot be undone!')"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                            <i class="fas fa-trash mr-2"></i>Delete Plan
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Plan Limits & Features -->
            <div class="space-y-6">
                <!-- Limits -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-sliders-h text-purple-500 mr-2"></i>
                        Plan Limits
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                            <i class="fas fa-users text-blue-500 text-2xl mb-2"></i>
                            <p class="text-gray-400 text-xs mb-1">Max Users</p>
                            <p class="text-white text-xl font-bold">
                                {{ $plan->features['max_users'] == -1 ? '∞' : number_format($plan->features['max_users']) }}
                            </p>
                        </div>

                        <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                            <i class="fas fa-box text-purple-500 text-2xl mb-2"></i>
                            <p class="text-gray-400 text-xs mb-1">Max Products</p>
                            <p class="text-white text-xl font-bold">
                                {{ $plan->features['max_products'] == -1 ? '∞' : number_format($plan->features['max_products']) }}
                            </p>
                        </div>

                        <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                            <i class="fas fa-exchange-alt text-green-500 text-2xl mb-2"></i>
                            <p class="text-gray-400 text-xs mb-1">Transactions/Month</p>
                            <p class="text-white text-xl font-bold">
                                {{ $plan->features['max_transactions'] == -1 ? '∞' : number_format($plan->features['max_transactions']) }}
                            </p>
                        </div>

                        <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                            <i class="fas fa-database text-pink-500 text-2xl mb-2"></i>
                            <p class="text-gray-400 text-xs mb-1">Storage</p>
                            <p class="text-white text-xl font-bold">
                                {{ $plan->features['max_storage_mb'] == -1 ? '∞' : number_format($plan->features['max_storage_mb']) . ' MB' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        Features
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                {{ isset($plan->features['bank_statements']) && $plan->features['bank_statements'] ? 'bg-green-600' : 'bg-gray-600' }}">
                                <i class="fas fa-{{ isset($plan->features['bank_statements']) && $plan->features['bank_statements'] ? 'check' : 'times' }} text-white"></i>
                            </div>
                            <span class="text-gray-300">Bank Statements</span>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                {{ isset($plan->features['advanced_reports']) && $plan->features['advanced_reports'] ? 'bg-green-600' : 'bg-gray-600' }}">
                                <i class="fas fa-{{ isset($plan->features['advanced_reports']) && $plan->features['advanced_reports'] ? 'check' : 'times' }} text-white"></i>
                            </div>
                            <span class="text-gray-300">Advanced Reports</span>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                {{ isset($plan->features['api_access']) && $plan->features['api_access'] ? 'bg-green-600' : 'bg-gray-600' }}">
                                <i class="fas fa-{{ isset($plan->features['api_access']) && $plan->features['api_access'] ? 'check' : 'times' }} text-white"></i>
                            </div>
                            <span class="text-gray-300">API Access</span>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                {{ isset($plan->features['priority_support']) && $plan->features['priority_support'] ? 'bg-green-600' : 'bg-gray-600' }}">
                                <i class="fas fa-{{ isset($plan->features['priority_support']) && $plan->features['priority_support'] ? 'check' : 'times' }} text-white"></i>
                            </div>
                            <span class="text-gray-300">Priority Support</span>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-lg">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                {{ isset($plan->features['custom_branding']) && $plan->features['custom_branding'] ? 'bg-green-600' : 'bg-gray-600' }}">
                                <i class="fas fa-{{ isset($plan->features['custom_branding']) && $plan->features['custom_branding'] ? 'check' : 'times' }} text-white"></i>
                            </div>
                            <span class="text-gray-300">Custom Branding</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Active Subscribers -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">
                    <i class="fas fa-users text-blue-500 mr-2"></i>
                    Active Subscribers ({{ $subscribers->total() }})
                </h3>
                <a href="{{ route('admin.plans.subscribers', $plan) }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                    View All →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Company</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Started</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Ends</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($subscribers as $subscription)
                        <tr class="hover:bg-slate-900/30 transition">
                            <td class="px-4 py-3">
                                <p class="text-white font-semibold">{{ $subscription->company->name }}</p>
                                <p class="text-gray-400 text-xs">{{ $subscription->company->subdomain }}.matchfinance.app</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($subscription->status === 'active') bg-green-900/30 text-green-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-sm">
                                {{ $subscription->starts_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-sm">
                                @if($subscription->ends_at)
                                    {{ $subscription->ends_at->format('d M Y') }}
                                @else
                                    Never
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.companies.show', $subscription->company) }}" 
                                   class="text-blue-500 hover:text-blue-400 text-sm">
                                    View Company →
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                No active subscribers yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscribers->hasPages())
            <div class="mt-6">
                {{ $subscribers->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>