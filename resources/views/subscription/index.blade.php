<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Subscription Dashboard
            </h2>
            @if($isOwner)
            <div class="flex gap-2">
                <a href="{{ route('subscription.plans') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-sm">
                    <i class="fas fa-th mr-2"></i>View Plans
                </a>
                <a href="{{ route('subscription.billing-history') }}" 
                   class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold text-sm">
                    <i class="fas fa-history mr-2"></i>Billing History
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Alert Messages -->
        @if(session('success'))
        <div class="mb-6 bg-green-900/30 border border-green-700 rounded-xl p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-400 text-xl mr-3"></i>
                <p class="text-green-400 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-900/30 border border-red-700 rounded-xl p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-400 text-xl mr-3"></i>
                <p class="text-red-400 font-semibold">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="mb-6 bg-yellow-900/30 border border-yellow-700 rounded-xl p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl mr-3"></i>
                <p class="text-yellow-400 font-semibold">{{ session('warning') }}</p>
            </div>
        </div>
        @endif

        <!-- Trial Warning -->
        @if($isTrial && !$trialExpired && $trialDaysRemaining !== null && $trialDaysRemaining <= 7)
        <div class="mb-6 bg-yellow-900/30 border border-yellow-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-400 text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-yellow-400 font-bold text-lg">Trial Period Ending Soon!</h3>
                        <p class="text-gray-300 mt-1">
                            Your trial will expire in <span class="font-bold text-yellow-400">{{ $trialDaysRemaining }} days</span>. 
                            Subscribe to continue using our services.
                        </p>
                    </div>
                </div>
                @if($isOwner)
                <a href="{{ route('subscription.plans') }}" 
                   class="px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition font-semibold whitespace-nowrap">
                    Choose a Plan
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Trial Expired Warning -->
        @if($trialExpired && !$hasActiveSubscription)
        <div class="mb-6 bg-red-900/30 border border-red-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-times-circle text-red-400 text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-red-400 font-bold text-lg">Trial Period Expired</h3>
                        <p class="text-gray-300 mt-1">
                            Your trial has ended. Please subscribe to a plan to continue using our services.
                        </p>
                    </div>
                </div>
                @if($isOwner)
                <a href="{{ route('subscription.plans') }}" 
                   class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold whitespace-nowrap">
                    Subscribe Now
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Expiring Soon Warning -->
        @if($hasActiveSubscription && $isExpiringSoon && $daysUntilExpiry > 0)
        <div class="mb-6 bg-orange-900/30 border border-orange-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-orange-400 text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-orange-400 font-bold text-lg">Subscription Expiring Soon!</h3>
                        <p class="text-gray-300 mt-1">
                            Your subscription will expire in <span class="font-bold text-orange-400">{{ $daysUntilExpiry }} days</span>. 
                            Renew now to avoid service interruption.
                        </p>
                    </div>
                </div>
                @if($isOwner)
                <form action="{{ route('subscription.renew', $subscription) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Renew subscription for {{ $currentPlan->name }}?')"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold whitespace-nowrap">
                        <i class="fas fa-redo mr-2"></i>Renew Now
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Current Subscription Card -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-white">Current Subscription</h3>
                        @if($subscription)
                        <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $subscription->statusBadgeClass }}">
                            {{ $subscription->statusLabel }}
                        </span>
                        @endif
                    </div>

                    @if($hasActiveSubscription && $currentPlan)
                    <div class="space-y-6">
                        <!-- Plan Info -->
                        <div class="flex items-start justify-between pb-6 border-b border-slate-700">
                            <div class="flex-1">
                                <p class="text-gray-400 text-sm mb-2">Current Plan</p>
                                <h4 class="text-3xl font-bold text-white mb-2">{{ $currentPlan->name }}</h4>
                                @if($currentPlan->description)
                                <p class="text-gray-400 text-sm">{{ $currentPlan->description }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-gray-400 text-sm mb-2">Price</p>
                                <p class="text-2xl font-bold text-blue-400">
                                    Rp {{ number_format($currentPlan->price, 0, ',', '.') }}
                                </p>
                                <p class="text-gray-400 text-sm">
                                    / {{ $currentPlan->billing_period === 'monthly' ? 'month' : 'year' }}
                                </p>
                            </div>
                        </div>

                        <!-- Subscription Period -->
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-gray-400 text-sm mb-2">Start Date</p>
                                <p class="text-white font-semibold text-lg">
                                    {{ $subscription->starts_at->format('d M Y') }}
                                </p>
                                <p class="text-gray-500 text-xs">
                                    {{ $subscription->starts_at->diffForHumans() }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm mb-2">End Date</p>
                                @if($subscription->ends_at)
                                <p class="text-white font-semibold text-lg">
                                    {{ $subscription->ends_at->format('d M Y') }}
                                </p>
                                <p class="text-gray-500 text-xs">
                                    {{ $subscription->ends_at->diffForHumans() }}
                                </p>
                                @else
                                <p class="text-gray-400 text-sm">No end date</p>
                                @endif
                            </div>
                        </div>

                        <!-- Days Remaining -->
                        @if($daysUntilExpiry !== null && $daysUntilExpiry > 0)
                        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm mb-1">Days Remaining</p>
                                    <p class="text-2xl font-bold
                                        {{ $daysUntilExpiry > 30 ? 'text-green-400' : ($daysUntilExpiry > 7 ? 'text-yellow-400' : 'text-red-400') }}">
                                        {{ $daysUntilExpiry }} days
                                    </p>
                                </div>
                                <div class="p-3 bg-blue-900/30 rounded-lg">
                                    <i class="fas fa-calendar-alt text-blue-400 text-2xl"></i>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Owner Actions -->
                        @if($isOwner)
                        <div class="flex flex-wrap gap-3 pt-6 border-t border-slate-700">
                            <a href="{{ route('subscription.plans') }}" 
                               class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-center">
                                <i class="fas fa-exchange-alt mr-2"></i>Change Plan
                            </a>
                            
                            @if($subscription->canBeCancelled())
                            <form action="{{ route('subscription.cancel') }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Are you sure you want to cancel your subscription? You will still have access until {{ $subscription->ends_at->format('d M Y') }}.')"
                                        class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                                    <i class="fas fa-ban mr-2"></i>Cancel Subscription
                                </button>
                            </form>
                            @endif

                            @if($subscription->canBeReactivated())
                            <form action="{{ route('subscription.resume') }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Reactivate your subscription?')"
                                        class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                                    <i class="fas fa-play mr-2"></i>Resume Subscription
                                </button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </div>

                    @elseif($isTrial && !$trialExpired)
                    <!-- Trial Period -->
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-900/30 rounded-full mb-4">
                            <i class="fas fa-gift text-blue-400 text-3xl"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-white mb-2">Trial Period</h4>
                        <p class="text-gray-400 mb-6">
                            You are currently on a trial period
                        </p>
                        @if($trialDaysRemaining !== null)
                        <div class="inline-block bg-slate-900/50 rounded-xl px-6 py-4 border border-slate-700 mb-6">
                            <p class="text-gray-400 text-sm mb-1">Days Remaining</p>
                            <p class="text-3xl font-bold text-blue-400">{{ $trialDaysRemaining }} days</p>
                        </div>
                        @endif
                        @if($isOwner)
                        <div>
                            <a href="{{ route('subscription.plans') }}" 
                               class="inline-flex items-center px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-lg">
                                <i class="fas fa-star mr-3"></i>Choose Your Plan
                            </a>
                        </div>
                        @endif
                    </div>

                    @else
                    <!-- No Subscription -->
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-red-900/30 rounded-full mb-4">
                            <i class="fas fa-times-circle text-red-400 text-3xl"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-white mb-2">No Active Subscription</h4>
                        <p class="text-gray-400 mb-6">
                            Subscribe to a plan to access all features
                        </p>
                        @if($isOwner)
                        <a href="{{ route('subscription.plans') }}" 
                           class="inline-flex items-center px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-lg">
                            <i class="fas fa-rocket mr-3"></i>Get Started
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Company & Usage Stats -->
            <div class="space-y-6">
                
                <!-- Company Info Card -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Company Information</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Company Name</p>
                            <p class="text-white font-semibold">{{ $company->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Subdomain</p>
                            <p class="text-white font-semibold">{{ $company->subdomain }}.app</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Owner</p>
                            <p class="text-white font-semibold">{{ $company->owner->name }}</p>
                            <p class="text-gray-500 text-xs">{{ $company->owner->email }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Status</p>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                {{ $company->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($company->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Usage Stats Card -->
                @if($currentPlan)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Usage Statistics</h3>
                    <div class="space-y-4">
                        
                        <!-- Users -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-gray-400 text-sm">Users</p>
                                <p class="text-white font-semibold">
                                    {{ $usageStats['users_count'] }} / 
                                    @if($usageStats['max_users'] === 0 || $usageStats['max_users'] === -1)
                                        <span class="text-green-400">Unlimited</span>
                                    @else
                                        {{ $usageStats['max_users'] }}
                                    @endif
                                </p>
                            </div>
                            @if($usageStats['max_users'] > 0)
                            <div class="w-full bg-slate-900 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" 
                                     style="width: {{ min(100, ($usageStats['users_count'] / $usageStats['max_users']) * 100) }}%">
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Bank Statements -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-gray-400 text-sm">Bank Statements</p>
                                <p class="text-white font-semibold">
                                    {{ $usageStats['statements_count'] }} / 
                                    @if($usageStats['max_statements'] === 0 || $usageStats['max_statements'] === -1)
                                        <span class="text-green-400">Unlimited</span>
                                    @else
                                        {{ $usageStats['max_statements'] }}
                                    @endif
                                </p>
                            </div>
                            @if($usageStats['max_statements'] > 0)
                            <div class="w-full bg-slate-900 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" 
                                     style="width: {{ min(100, ($usageStats['statements_count'] / $usageStats['max_statements']) * 100) }}%">
                                </div>
                            </div>
                            @endif
                        </div>

                    </div>
                </div>
                @endif

            </div>
        </div>

        <!-- Plan Features -->
        @if($currentPlan && $currentPlan->features)
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <h3 class="text-2xl font-bold text-white mb-6">Plan Features</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($currentPlan->features as $feature => $value)
                <div class="flex items-start p-4 bg-slate-900/50 rounded-lg border border-slate-700">
                    <i class="fas fa-check-circle text-green-400 text-lg mr-3 mt-1"></i>
                    <div>
                        <p class="text-white font-semibold capitalize">{{ str_replace('_', ' ', $feature) }}</p>
                        <p class="text-gray-400 text-sm">
                            @if($value === true)
                                Enabled
                            @elseif($value === false)
                                Disabled
                            @elseif($value === -1)
                                Unlimited
                            @else
                                {{ $value }}
                            @endif
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-app-layout>