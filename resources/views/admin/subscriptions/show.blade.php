<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.subscriptions.index') }}" 
                   class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Subscription Details
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Main Info Card -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden mb-8">
            <!-- Header with Status -->
            <div class="p-6 bg-gradient-to-r from-blue-600 to-purple-600">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-3xl font-bold text-white mb-2">{{ $subscription->company->name }}</h3>
                        <p class="text-blue-100">{{ $subscription->company->subdomain }}.app</p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-bold {{ $subscription->statusBadgeClass }}">
                        {{ $subscription->statusLabel }}
                    </span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Current Plan</p>
                    <p class="text-white text-xl font-bold">{{ $subscription->plan->name }}</p>
                    <p class="text-gray-400 text-xs mt-1">{{ ucfirst($subscription->plan->billing_period) }}</p>
                </div>
                
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Amount</p>
                    <p class="text-white text-xl font-bold">Rp {{ number_format($subscription->plan->price, 0, ',', '.') }}</p>
                    <p class="text-gray-400 text-xs mt-1">per {{ $subscription->plan->billing_period === 'monthly' ? 'month' : 'year' }}</p>
                </div>

                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Start Date</p>
                    <p class="text-white text-xl font-bold">{{ $subscription->starts_at->format('d M Y') }}</p>
                </div>

                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">End Date</p>
                    @if($subscription->ends_at)
                        <p class="text-white text-xl font-bold">{{ $subscription->ends_at->format('d M Y') }}</p>
                        @if($subscription->isActive())
                            <p class="text-{{ $subscription->daysRemaining > 7 ? 'green' : 'red' }}-400 text-xs mt-1">
                                {{ $subscription->daysRemaining }} days remaining
                            </p>
                        @endif
                    @else
                        <p class="text-gray-400 text-xl">-</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Company Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-building text-blue-500 mr-2"></i>
                        Company Information
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Company Name</label>
                            <p class="text-white font-semibold">{{ $subscription->company->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Subdomain</label>
                            <p class="text-white font-semibold">{{ $subscription->company->subdomain }}.app</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Owner</label>
                            @if($subscription->company->owner)
                                <p class="text-white font-semibold">{{ $subscription->company->owner->name }}</p>
                                <p class="text-gray-400 text-sm">{{ $subscription->company->owner->email }}</p>
                            @else
                                <p class="text-gray-400">No owner assigned</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Company Status</label>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                @if($subscription->company->status === 'active') bg-green-100 text-green-800
                                @elseif($subscription->company->status === 'trial') bg-blue-100 text-blue-800
                                @elseif($subscription->company->status === 'suspended') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($subscription->company->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Plan Features -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        Plan Features
                    </h4>

                    @if($subscription->plan->description)
                        <p class="text-gray-400 mb-6">{{ $subscription->plan->description }}</p>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(isset($subscription->plan->features['max_users']))
                        <div class="flex items-center p-4 bg-slate-900/50 rounded-lg">
                            <div class="p-3 bg-blue-900/30 rounded-lg mr-4">
                                <i class="fas fa-users text-blue-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Max Users</p>
                                <p class="text-white font-bold">
                                    {{ $subscription->plan->features['max_users'] == -1 ? 'Unlimited' : $subscription->plan->features['max_users'] }}
                                </p>
                            </div>
                        </div>
                        @endif

                        @if(isset($subscription->plan->features['max_storage_mb']))
                        <div class="flex items-center p-4 bg-slate-900/50 rounded-lg">
                            <div class="p-3 bg-purple-900/30 rounded-lg mr-4">
                                <i class="fas fa-database text-purple-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Storage</p>
                                <p class="text-white font-bold">
                                    {{ $subscription->plan->features['max_storage_mb'] == -1 ? 'Unlimited' : number_format($subscription->plan->features['max_storage_mb']) . ' MB' }}
                                </p>
                            </div>
                        </div>
                        @endif

                        @if(isset($subscription->plan->features['bank_statements']))
                        <div class="flex items-center p-4 bg-slate-900/50 rounded-lg">
                            <div class="p-3 bg-{{ $subscription->plan->features['bank_statements'] ? 'green' : 'red' }}-900/30 rounded-lg mr-4">
                                <i class="fas fa-{{ $subscription->plan->features['bank_statements'] ? 'check' : 'times' }}-circle text-{{ $subscription->plan->features['bank_statements'] ? 'green' : 'red' }}-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Bank Statements</p>
                                <p class="text-white font-bold">{{ $subscription->plan->features['bank_statements'] ? 'Enabled' : 'Disabled' }}</p>
                            </div>
                        </div>
                        @endif

                        @if(isset($subscription->plan->features['advanced_reports']))
                        <div class="flex items-center p-4 bg-slate-900/50 rounded-lg">
                            <div class="p-3 bg-{{ $subscription->plan->features['advanced_reports'] ? 'green' : 'red' }}-900/30 rounded-lg mr-4">
                                <i class="fas fa-{{ $subscription->plan->features['advanced_reports'] ? 'check' : 'times' }}-circle text-{{ $subscription->plan->features['advanced_reports'] ? 'green' : 'red' }}-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Advanced Reports</p>
                                <p class="text-white font-bold">{{ $subscription->plan->features['advanced_reports'] ? 'Enabled' : 'Disabled' }}</p>
                            </div>
                        </div>
                        @endif

                        @if(isset($subscription->plan->features['api_access']))
                        <div class="flex items-center p-4 bg-slate-900/50 rounded-lg">
                            <div class="p-3 bg-{{ $subscription->plan->features['api_access'] ? 'green' : 'red' }}-900/30 rounded-lg mr-4">
                                <i class="fas fa-{{ $subscription->plan->features['api_access'] ? 'check' : 'times' }}-circle text-{{ $subscription->plan->features['api_access'] ? 'green' : 'red' }}-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">API Access</p>
                                <p class="text-white font-bold">{{ $subscription->plan->features['api_access'] ? 'Enabled' : 'Disabled' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Subscription History -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-history text-purple-500 mr-2"></i>
                        Subscription History
                    </h4>

                    <div class="space-y-4">
                        @forelse($history as $item)
                        <div class="flex items-start p-4 bg-slate-900/50 rounded-lg {{ $item->id === $subscription->id ? 'ring-2 ring-blue-500' : '' }}">
                            <div class="flex-shrink-0 mr-4">
                                <div class="w-12 h-12 rounded-full bg-blue-900/30 flex items-center justify-center">
                                    <i class="fas fa-layer-group text-blue-400"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="text-white font-semibold">{{ $item->plan->name }}</p>
                                        <p class="text-gray-400 text-sm">
                                            {{ $item->starts_at->format('d M Y') }} - 
                                            {{ $item->ends_at ? $item->ends_at->format('d M Y') : 'Ongoing' }}
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $item->statusBadgeClass }}">
                                        {{ $item->statusLabel }}
                                    </span>
                                </div>
                                @if($item->id === $subscription->id)
                                    <span class="inline-block px-2 py-1 bg-blue-900/30 text-blue-400 rounded text-xs font-semibold">
                                        Current
                                    </span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-400 text-center py-8">No history available</p>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Right Column - Actions -->
            <div class="space-y-8">
                
                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Quick Actions
                    </h4>

                    <div class="space-y-3">
                        @if($subscription->canBeCancelled())
                        <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('Are you sure you want to cancel this subscription?')"
                                    class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold text-left">
                                <i class="fas fa-ban mr-2"></i>Cancel Subscription
                            </button>
                        </form>
                        @endif

                        @if($subscription->canBeRenewed())
                        <form action="{{ route('admin.subscriptions.renew', $subscription) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('Renew this subscription for another period?')"
                                    class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold text-left">
                                <i class="fas fa-redo mr-2"></i>Renew Subscription
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('admin.companies.show', $subscription->company) }}" 
                           class="block w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-center">
                            <i class="fas fa-building mr-2"></i>View Company
                        </a>
                    </div>
                </div>

                <!-- Change Plan -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-exchange-alt text-green-500 mr-2"></i>
                        Change Plan
                    </h4>

                    <form action="{{ route('admin.subscriptions.change-plan', $subscription) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Select New Plan</label>
                            <select name="plan_id" 
                                    required
                                    class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
                                <option value="">Choose a plan...</option>
                                @foreach(\App\Models\Plan::where('is_active', true)->get() as $plan)
                                    @if($plan->id !== $subscription->plan_id)
                                        <option value="{{ $plan->id }}">
                                            {{ $plan->name }} - Rp {{ number_format($plan->price, 0, ',', '.') }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" 
                                onclick="return confirm('Change to the selected plan?')"
                                class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold">
                            <i class="fas fa-save mr-2"></i>Change Plan
                        </button>
                    </form>
                </div>

                <!-- Subscription Timeline -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h4 class="text-xl font-bold text-white mb-6">
                        <i class="fas fa-clock text-blue-500 mr-2"></i>
                        Timeline
                    </h4>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 rounded-full bg-green-500 mt-2 mr-3"></div>
                            <div>
                                <p class="text-gray-400 text-sm">Started</p>
                                <p class="text-white font-semibold">{{ $subscription->starts_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>

                        @if($subscription->ends_at)
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2 mr-3"></div>
                            <div>
                                <p class="text-gray-400 text-sm">Ends</p>
                                <p class="text-white font-semibold">{{ $subscription->ends_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($subscription->cancelled_at)
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-2 h-2 rounded-full bg-red-500 mt-2 mr-3"></div>
                            <div>
                                <p class="text-gray-400 text-sm">Cancelled</p>
                                <p class="text-white font-semibold">{{ $subscription->cancelled_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>