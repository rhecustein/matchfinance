<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Plan Management
            </h2>
            <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                <i class="fas fa-plus mr-2"></i>Create Plan
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Monthly Plans -->
        @if(isset($plans['monthly']) && $plans['monthly']->isNotEmpty())
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                Monthly Plans
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($plans['monthly'] as $plan)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden transform hover:scale-105 transition-all duration-300">
                    <!-- Plan Header -->
                    <div class="p-6 bg-gradient-to-r from-blue-600 to-purple-600">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-2xl font-bold text-white">{{ $plan->name }}</h4>
                                <p class="text-blue-100 text-sm mt-1">Monthly billing</p>
                            </div>
                            @if($plan->is_active)
                            <span class="px-3 py-1 bg-green-500 text-white rounded-full text-xs font-semibold">
                                Active
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-500 text-white rounded-full text-xs font-semibold">
                                Inactive
                            </span>
                            @endif
                        </div>
                        
                        <div class="text-white">
                            <span class="text-4xl font-bold">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                            <span class="text-blue-100">/month</span>
                        </div>
                    </div>

                    <!-- Plan Body -->
                    <div class="p-6">
                        @if($plan->description)
                        <p class="text-gray-400 text-sm mb-4">{{ Str::limit($plan->description, 100) }}</p>
                        @endif

                        <!-- Subscriber Count -->
                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg mb-4">
                            <span class="text-gray-400 text-sm">Active Subscribers</span>
                            <span class="text-white font-bold text-lg">{{ $plan->active_subscriptions_count }}</span>
                        </div>

                        <!-- Key Features -->
                        <div class="space-y-2 mb-6">
                            @if(isset($plan->features['max_users']))
                            <div class="flex items-center text-sm">
                                <i class="fas fa-users text-blue-500 w-5"></i>
                                <span class="text-gray-300 ml-2">
                                    {{ $plan->features['max_users'] == -1 ? 'Unlimited' : $plan->features['max_users'] }} Users
                                </span>
                            </div>
                            @endif
                            
                            @if(isset($plan->features['max_storage_mb']))
                            <div class="flex items-center text-sm">
                                <i class="fas fa-database text-purple-500 w-5"></i>
                                <span class="text-gray-300 ml-2">
                                    {{ $plan->features['max_storage_mb'] == -1 ? 'Unlimited' : number_format($plan->features['max_storage_mb']) . ' MB' }} Storage
                                </span>
                            </div>
                            @endif

                            @if(isset($plan->features['bank_statements']) && $plan->features['bank_statements'])
                            <div class="flex items-center text-sm">
                                <i class="fas fa-check-circle text-green-500 w-5"></i>
                                <span class="text-gray-300 ml-2">Bank Statements</span>
                            </div>
                            @endif

                            @if(isset($plan->features['advanced_reports']) && $plan->features['advanced_reports'])
                            <div class="flex items-center text-sm">
                                <i class="fas fa-check-circle text-green-500 w-5"></i>
                                <span class="text-gray-300 ml-2">Advanced Reports</span>
                            </div>
                            @endif

                            @if(isset($plan->features['api_access']) && $plan->features['api_access'])
                            <div class="flex items-center text-sm">
                                <i class="fas fa-check-circle text-green-500 w-5"></i>
                                <span class="text-gray-300 ml-2">API Access</span>
                            </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <a href="{{ route('admin.plans.show', $plan) }}" 
                               class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center text-sm font-semibold">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            
                            <a href="{{ route('admin.plans.edit', $plan) }}" 
                               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-semibold">
                                <i class="fas fa-edit"></i>
                            </a>

                            <form action="{{ route('admin.plans.toggle-active', $plan) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2 {{ $plan->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition text-sm font-semibold">
                                    <i class="fas fa-{{ $plan->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>

                            @if($plan->active_subscriptions_count == 0)
                            <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        onclick="return confirm('Delete this plan?')"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Yearly Plans -->
        @if(isset($plans['yearly']) && $plans['yearly']->isNotEmpty())
        <div>
            <h3 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
                Yearly Plans
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($plans['yearly'] as $plan)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden transform hover:scale-105 transition-all duration-300">
                    <!-- Plan Header -->
                    <div class="p-6 bg-gradient-to-r from-green-600 to-teal-600">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-2xl font-bold text-white">{{ $plan->name }}</h4>
                                <p class="text-green-100 text-sm mt-1">Yearly billing</p>
                            </div>
                            @if($plan->is_active)
                            <span class="px-3 py-1 bg-green-500 text-white rounded-full text-xs font-semibold">
                                Active
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-500 text-white rounded-full text-xs font-semibold">
                                Inactive
                            </span>
                            @endif
                        </div>
                        
                        <div class="text-white">
                            <span class="text-4xl font-bold">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                            <span class="text-green-100">/year</span>
                        </div>
                        <p class="text-green-100 text-sm mt-2">
                            <i class="fas fa-tag mr-1"></i>
                            Rp {{ number_format($plan->price / 12, 0, ',', '.') }}/month
                        </p>
                    </div>

                    <!-- Plan Body -->
                    <div class="p-6">
                        @if($plan->description)
                        <p class="text-gray-400 text-sm mb-4">{{ Str::limit($plan->description, 100) }}</p>
                        @endif

                        <!-- Subscriber Count -->
                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg mb-4">
                            <span class="text-gray-400 text-sm">Active Subscribers</span>
                            <span class="text-white font-bold text-lg">{{ $plan->active_subscriptions_count }}</span>
                        </div>

                        <!-- Key Features -->
                        <div class="space-y-2 mb-6">
                            @if(isset($plan->features['max_users']))
                            <div class="flex items-center text-sm">
                                <i class="fas fa-users text-blue-500 w-5"></i>
                                <span class="text-gray-300 ml-2">
                                    {{ $plan->features['max_users'] == -1 ? 'Unlimited' : $plan->features['max_users'] }} Users
                                </span>
                            </div>
                            @endif
                            
                            @if(isset($plan->features['max_storage_mb']))
                            <div class="flex items-center text-sm">
                                <i class="fas fa-database text-purple-500 w-5"></i>
                                <span class="text-gray-300 ml-2">
                                    {{ $plan->features['max_storage_mb'] == -1 ? 'Unlimited' : number_format($plan->features['max_storage_mb']) . ' MB' }} Storage
                                </span>
                            </div>
                            @endif

                            @if(isset($plan->features['bank_statements']) && $plan->features['bank_statements'])
                            <div class="flex items-center text-sm">
                                <i class="fas fa-check-circle text-green-500 w-5"></i>
                                <span class="text-gray-300 ml-2">Bank Statements</span>
                            </div>
                            @endif

                            @if(isset($plan->features['advanced_reports']) && $plan->features['advanced_reports'])
                            <div class="flex items-center text-sm">
                                <i class="fas fa-check-circle text-green-500 w-5"></i>
                                <span class="text-gray-300 ml-2">Advanced Reports</span>
                            </div>
                            @endif

                            @if(isset($plan->features['api_access']) && $plan->features['api_access'])
                            <div class="flex items-center text-sm">
                                <i class="fas fa-check-circle text-green-500 w-5"></i>
                                <span class="text-gray-300 ml-2">API Access</span>
                            </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <a href="{{ route('admin.plans.show', $plan) }}" 
                               class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-center text-sm font-semibold">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            
                            <a href="{{ route('admin.plans.edit', $plan) }}" 
                               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-semibold">
                                <i class="fas fa-edit"></i>
                            </a>

                            <form action="{{ route('admin.plans.toggle-active', $plan) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2 {{ $plan->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition text-sm font-semibold">
                                    <i class="fas fa-{{ $plan->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>

                            @if($plan->active_subscriptions_count == 0)
                            <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        onclick="return confirm('Delete this plan?')"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- No Plans -->
        @if($plans->isEmpty())
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 border border-slate-700 shadow-xl text-center">
            <i class="fas fa-layer-group text-gray-600 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-white mb-2">No Plans Created Yet</h3>
            <p class="text-gray-400 mb-6">Create your first subscription plan to get started.</p>
            <a href="{{ route('admin.plans.create') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                <i class="fas fa-plus mr-2"></i>Create First Plan
            </a>
        </div>
        @endif

    </div>
</x-app-layout>