<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Subscription Plans
            </h2>
            <a href="{{ route('subscription.index') }}" 
               class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition font-semibold text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Choose Your Perfect Plan</h1>
            <p class="text-xl text-gray-600">Select a plan that fits your business needs</p>
        </div>

        <!-- Billing Period Toggle -->
        <div class="flex justify-center mb-8">
            <div class="inline-flex bg-slate-800 rounded-xl p-1 border border-slate-700">
                <button onclick="showPlans('monthly')" 
                        id="monthly-tab"
                        class="billing-tab px-6 py-3 rounded-lg font-semibold transition text-white bg-blue-600">
                    Monthly
                </button>
                <button onclick="showPlans('yearly')" 
                        id="yearly-tab"
                        class="billing-tab px-6 py-3 rounded-lg font-semibold transition text-gray-400">
                    Yearly
                    <span class="ml-2 px-2 py-1 bg-green-900/30 text-green-400 rounded-full text-xs">Save up to 20%</span>
                </button>
            </div>
        </div>

        <!-- Current Plan Info -->
        @if($currentPlan)
        <div class="mb-8 bg-blue-900/30 border border-blue-700 rounded-xl p-4">
            <div class="flex items-center justify-center">
                <i class="fas fa-info-circle text-blue-400 text-xl mr-3"></i>
                <p class="text-blue-300">
                    You are currently on the <span class="font-bold">{{ $currentPlan->name }}</span> plan 
                    ({{ $currentPlan->billing_period === 'monthly' ? 'Monthly' : 'Yearly' }})
                </p>
            </div>
        </div>
        @endif

        <!-- Monthly Plans -->
        <div id="monthly-plans" class="plans-container">
            @if($monthlyPlans->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min(4, $monthlyPlans->count()) }} gap-6">
                @foreach($monthlyPlans as $plan)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border 
                    {{ $currentPlanId === $plan->id ? 'border-blue-500 ring-2 ring-blue-500' : 'border-slate-700' }} 
                    shadow-xl hover:border-blue-500 transition transform hover:-translate-y-1">
                    
                    <!-- Current Plan Badge -->
                    @if($currentPlanId === $plan->id)
                    <div class="mb-4">
                        <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-semibold">
                            Current Plan
                        </span>
                    </div>
                    @endif

                    <!-- Plan Name -->
                    <h3 class="text-2xl font-bold text-white mb-2">{{ $plan->name }}</h3>
                    
                    <!-- Plan Description -->
                    @if($plan->description)
                    <p class="text-gray-400 text-sm mb-6">{{ $plan->description }}</p>
                    @endif

                    <!-- Price -->
                    <div class="mb-6">
                        <div class="flex items-baseline">
                            <span class="text-4xl font-bold text-white">
                                Rp {{ number_format($plan->price, 0, ',', '.') }}
                            </span>
                            <span class="text-gray-400 ml-2">/month</span>
                        </div>
                    </div>

                    <!-- Features List -->
                    @if($plan->features)
                    <div class="space-y-3 mb-8">
                        @foreach($plan->features as $feature => $value)
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-400 mt-1 mr-3"></i>
                            <span class="text-gray-300 text-sm">
                                <span class="capitalize">{{ str_replace('_', ' ', $feature) }}:</span>
                                @if($value === true)
                                    <span class="text-green-400 font-semibold">Enabled</span>
                                @elseif($value === false)
                                    <span class="text-red-400 font-semibold">Disabled</span>
                                @elseif($value === -1)
                                    <span class="text-blue-400 font-semibold">Unlimited</span>
                                @else
                                    <span class="text-white font-semibold">{{ $value }}</span>
                                @endif
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Action Button -->
                    @if($isOwner)
                        @if($currentPlanId === $plan->id)
                        <button disabled 
                                class="w-full px-6 py-3 bg-slate-700 text-gray-400 rounded-lg cursor-not-allowed font-semibold">
                            <i class="fas fa-check mr-2"></i>Current Plan
                        </button>
                        @else
                        <form action="{{ route('subscription.change-plan') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" 
                                    onclick="return confirm('Change to {{ $plan->name }} plan?')"
                                    class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                @if($currentPlan && $plan->price > $currentPlan->price)
                                    <i class="fas fa-arrow-up mr-2"></i>Upgrade
                                @elseif($currentPlan && $plan->price < $currentPlan->price)
                                    <i class="fas fa-arrow-down mr-2"></i>Downgrade
                                @else
                                    <i class="fas fa-exchange-alt mr-2"></i>Switch Plan
                                @endif
                            </button>
                        </form>
                        @endif
                    @else
                    <button disabled 
                            class="w-full px-6 py-3 bg-slate-700 text-gray-400 rounded-lg cursor-not-allowed font-semibold">
                        <i class="fas fa-lock mr-2"></i>Owner Only
                    </button>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                <p class="text-gray-400 text-lg">No monthly plans available</p>
            </div>
            @endif
        </div>

        <!-- Yearly Plans -->
        <div id="yearly-plans" class="plans-container hidden">
            @if($yearlyPlans->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min(4, $yearlyPlans->count()) }} gap-6">
                @foreach($yearlyPlans as $plan)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border 
                    {{ $currentPlanId === $plan->id ? 'border-blue-500 ring-2 ring-blue-500' : 'border-slate-700' }} 
                    shadow-xl hover:border-blue-500 transition transform hover:-translate-y-1">
                    
                    <!-- Savings Badge -->
                    @if($plan->savings_percentage > 0)
                    <div class="mb-4">
                        <span class="px-3 py-1 bg-green-900/50 text-green-400 rounded-full text-xs font-semibold">
                            <i class="fas fa-tag mr-1"></i>Save {{ $plan->savings_percentage }}%
                        </span>
                    </div>
                    @endif

                    <!-- Current Plan Badge -->
                    @if($currentPlanId === $plan->id)
                    <div class="mb-4">
                        <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-semibold">
                            Current Plan
                        </span>
                    </div>
                    @endif

                    <!-- Plan Name -->
                    <h3 class="text-2xl font-bold text-white mb-2">{{ $plan->name }}</h3>
                    
                    <!-- Plan Description -->
                    @if($plan->description)
                    <p class="text-gray-400 text-sm mb-6">{{ $plan->description }}</p>
                    @endif

                    <!-- Price -->
                    <div class="mb-6">
                        <div class="flex items-baseline mb-2">
                            <span class="text-4xl font-bold text-white">
                                Rp {{ number_format($plan->price, 0, ',', '.') }}
                            </span>
                            <span class="text-gray-400 ml-2">/year</span>
                        </div>
                        <p class="text-sm text-gray-400">
                            Rp {{ number_format($plan->getMonthlyPrice(), 0, ',', '.') }}/month billed annually
                        </p>
                        @if($plan->savings_amount > 0)
                        <p class="text-sm text-green-400 font-semibold mt-1">
                            Save Rp {{ number_format($plan->savings_amount, 0, ',', '.') }}/year
                        </p>
                        @endif
                    </div>

                    <!-- Features List -->
                    @if($plan->features)
                    <div class="space-y-3 mb-8">
                        @foreach($plan->features as $feature => $value)
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-400 mt-1 mr-3"></i>
                            <span class="text-gray-300 text-sm">
                                <span class="capitalize">{{ str_replace('_', ' ', $feature) }}:</span>
                                @if($value === true)
                                    <span class="text-green-400 font-semibold">Enabled</span>
                                @elseif($value === false)
                                    <span class="text-red-400 font-semibold">Disabled</span>
                                @elseif($value === -1)
                                    <span class="text-blue-400 font-semibold">Unlimited</span>
                                @else
                                    <span class="text-white font-semibold">{{ $value }}</span>
                                @endif
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Action Button -->
                    @if($isOwner)
                        @if($currentPlanId === $plan->id)
                        <button disabled 
                                class="w-full px-6 py-3 bg-slate-700 text-gray-400 rounded-lg cursor-not-allowed font-semibold">
                            <i class="fas fa-check mr-2"></i>Current Plan
                        </button>
                        @else
                        <form action="{{ route('subscription.change-plan') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" 
                                    onclick="return confirm('Change to {{ $plan->name }} plan (Yearly)?')"
                                    class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                @if($currentPlan && $plan->getMonthlyPrice() > $currentPlan->getMonthlyPrice())
                                    <i class="fas fa-arrow-up mr-2"></i>Upgrade to Yearly
                                @elseif($currentPlan && $plan->getMonthlyPrice() < $currentPlan->getMonthlyPrice())
                                    <i class="fas fa-arrow-down mr-2"></i>Downgrade to Yearly
                                @else
                                    <i class="fas fa-exchange-alt mr-2"></i>Switch to Yearly
                                @endif
                            </button>
                        </form>
                        @endif
                    @else
                    <button disabled 
                            class="w-full px-6 py-3 bg-slate-700 text-gray-400 rounded-lg cursor-not-allowed font-semibold">
                        <i class="fas fa-lock mr-2"></i>Owner Only
                    </button>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                <p class="text-gray-400 text-lg">No yearly plans available</p>
            </div>
            @endif
        </div>

        <!-- FAQ or Additional Info Section -->
        <div class="mt-16 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <h3 class="text-2xl font-bold text-white mb-6 text-center">Frequently Asked Questions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-white font-semibold mb-2">
                        <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                        Can I change my plan anytime?
                    </h4>
                    <p class="text-gray-400 text-sm">
                        Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">
                        <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                        What happens when I cancel?
                    </h4>
                    <p class="text-gray-400 text-sm">
                        Your subscription remains active until the end of your billing period. You can reactivate anytime.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">
                        <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                        How much do I save with yearly billing?
                    </h4>
                    <p class="text-gray-400 text-sm">
                        Yearly plans save you up to 20% compared to monthly billing. It's the best value for your business.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">
                        <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                        Do you offer refunds?
                    </h4>
                    <p class="text-gray-400 text-sm">
                        Please contact our support team to discuss refund options based on your specific situation.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript for Tab Switching -->
    <script>
        function showPlans(period) {
            // Hide all plan containers
            document.querySelectorAll('.plans-container').forEach(container => {
                container.classList.add('hidden');
            });

            // Show selected plan container
            document.getElementById(period + '-plans').classList.remove('hidden');

            // Update tab styles
            document.querySelectorAll('.billing-tab').forEach(tab => {
                tab.classList.remove('bg-blue-600', 'text-white');
                tab.classList.add('text-gray-400');
            });

            // Highlight active tab
            const activeTab = document.getElementById(period + '-tab');
            activeTab.classList.add('bg-blue-600', 'text-white');
            activeTab.classList.remove('text-gray-400');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if current plan is yearly, show yearly plans by default
            @if($currentPlan && $currentPlan->billing_period === 'yearly')
                showPlans('yearly');
            @endif
        });
    </script>
</x-app-layout>