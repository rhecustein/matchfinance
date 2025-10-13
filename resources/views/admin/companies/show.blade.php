<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Company Details: {{ $company->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.companies.edit', $company) }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('admin.companies.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Company Header Card -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">
                        {{ $company->name }}
                    </h2>
                    <div class="flex items-center gap-4 text-blue-100">
                        <span>
                            <i class="fas fa-link mr-1"></i>
                            {{ $company->subdomain }}.matchfinance.app
                        </span>
                        @if($company->domain)
                        <span>
                            <i class="fas fa-globe mr-1"></i>
                            {{ $company->domain }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="hidden lg:block">
                    <span class="px-6 py-3 rounded-full text-lg font-bold
                        @if($company->status === 'active') bg-green-500 text-white
                        @elseif($company->status === 'trial') bg-yellow-500 text-white
                        @elseif($company->status === 'suspended') bg-red-500 text-white
                        @else bg-gray-500 text-white
                        @endif">
                        {{ ucfirst($company->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <span class="text-green-500 text-sm font-semibold">
                        {{ $stats['active_users'] }} active
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Total Users</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_users']) }}</p>
            </div>

            <!-- Total Statements -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-pdf text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Bank Statements</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_statements']) }}</p>
            </div>

            <!-- Total Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Transactions</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['total_transactions']) }}</p>
            </div>

            <!-- Verified Transactions -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-400 text-sm mb-1">Verified</h3>
                <p class="text-white text-3xl font-bold">{{ number_format($stats['verified_transactions']) }}</p>
                <div class="mt-2">
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['total_transactions'] > 0 ? ($stats['verified_transactions'] / $stats['total_transactions']) * 100 : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            <!-- Company Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-building text-blue-500 mr-2"></i>
                    Company Information
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Company Name</span>
                        <span class="text-white font-semibold">{{ $company->name }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Slug</span>
                        <span class="text-white font-mono">{{ $company->slug }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Subdomain</span>
                        <span class="text-blue-400">{{ $company->subdomain }}.matchfinance.app</span>
                    </div>
                    @if($company->domain)
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Custom Domain</span>
                        <span class="text-blue-400">{{ $company->domain }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Status</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($company->status === 'active') bg-green-900/30 text-green-400
                            @elseif($company->status === 'trial') bg-yellow-900/30 text-yellow-400
                            @elseif($company->status === 'suspended') bg-red-900/30 text-red-400
                            @else bg-gray-900/30 text-gray-400
                            @endif">
                            {{ ucfirst($company->status) }}
                        </span>
                    </div>
                    @if($company->status === 'trial' && $company->trial_ends_at)
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Trial Ends</span>
                        <span class="text-white">{{ $company->trial_ends_at->format('d M Y') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Created</span>
                        <span class="text-white">{{ $company->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="text-gray-400">Last Updated</span>
                        <span class="text-white">{{ $company->updated_at->format('d M Y H:i') }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex flex-wrap gap-2">
                    @if($company->status === 'active')
                    <form action="{{ route('admin.companies.suspend', $company) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Suspend this company?')"
                                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-sm font-semibold">
                            <i class="fas fa-pause mr-2"></i>Suspend Company
                        </button>
                    </form>
                    @elseif($company->status === 'suspended')
                    <form action="{{ route('admin.companies.activate', $company) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-semibold">
                            <i class="fas fa-check mr-2"></i>Activate Company
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('admin.companies.cancel', $company) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Cancel this company subscription?')"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                            <i class="fas fa-times mr-2"></i>Cancel Company
                        </button>
                    </form>
                </div>
            </div>

            <!-- Owner Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-user-crown text-purple-500 mr-2"></i>
                    Owner Information
                </h3>

                @if($company->owner)
                <div class="space-y-4">
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Name</span>
                        <span class="text-white font-semibold">{{ $company->owner->name }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Email</span>
                        <span class="text-blue-400">{{ $company->owner->email }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Role</span>
                        <span class="px-3 py-1 bg-purple-900/30 text-purple-400 rounded-full text-xs font-semibold">
                            Owner
                        </span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Status</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($company->owner->is_active) bg-green-900/30 text-green-400
                            @else bg-red-900/30 text-red-400
                            @endif">
                            {{ $company->owner->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-700">
                        <span class="text-gray-400">Joined</span>
                        <span class="text-white">{{ $company->owner->created_at->format('d M Y') }}</span>
                    </div>
                    @if($company->owner->last_login_at)
                    <div class="flex justify-between py-3">
                        <span class="text-gray-400">Last Login</span>
                        <span class="text-white">{{ $company->owner->last_login_at->diffForHumans() }}</span>
                    </div>
                    @endif
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-user-slash text-gray-600 text-4xl mb-4"></i>
                    <p class="text-gray-400">No owner assigned</p>
                </div>
                @endif
            </div>

        </div>

        <!-- Current Subscription -->
        @if($company->subscription)
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-credit-card text-green-500 mr-2"></i>
                Current Subscription
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Plan</p>
                    <p class="text-white text-xl font-bold">{{ $company->subscription->plan->name }}</p>
                </div>
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Price</p>
                    <p class="text-green-400 text-xl font-bold">
                        Rp {{ number_format($company->subscription->plan->price, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">per {{ $company->subscription->plan->billing_period === 'monthly' ? 'month' : 'year' }}</p>
                </div>
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Status</p>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                        @if($company->subscription->status === 'active') bg-green-900/30 text-green-400
                        @else bg-red-900/30 text-red-400
                        @endif">
                        {{ ucfirst($company->subscription->status) }}
                    </span>
                </div>
                <div class="text-center p-4 bg-slate-900/50 rounded-xl">
                    <p class="text-gray-400 text-sm mb-2">Expires</p>
                    <p class="text-white text-sm font-bold">
                        @if($company->subscription->ends_at)
                            {{ $company->subscription->ends_at->format('d M Y') }}
                        @else
                            Never
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Users List -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-8">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-users text-blue-500 mr-2"></i>
                Users ({{ $company->users->count() }})
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($company->users as $user)
                        <tr class="hover:bg-slate-900/30 transition">
                            <td class="px-4 py-3 text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-gray-400 text-sm">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($user->role === 'owner') bg-purple-900/30 text-purple-400
                                    @elseif($user->role === 'admin') bg-blue-900/30 text-blue-400
                                    @else bg-gray-900/30 text-gray-400
                                    @endif">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($user->is_active) bg-green-900/30 text-green-400
                                    @else bg-red-900/30 text-red-400
                                    @endif">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-sm">{{ $user->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                No users found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bank Statements -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                Recent Bank Statements ({{ $company->bankStatements->count() }})
            </h3>

            <div class="space-y-3">
                @forelse($company->bankStatements as $statement)
                <div class="p-4 bg-slate-900/50 rounded-xl hover:bg-slate-900 transition border border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-600/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500"></i>
                            </div>
                            <div>
                                <p class="text-white font-semibold text-sm">{{ $statement->bank->name }}</p>
                                <p class="text-gray-400 text-xs">{{ $statement->original_filename }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                @if($statement->ocr_status === 'completed') bg-green-900/30 text-green-400
                                @elseif($statement->ocr_status === 'processing') bg-blue-900/30 text-blue-400
                                @elseif($statement->ocr_status === 'failed') bg-red-900/30 text-red-400
                                @else bg-yellow-900/30 text-yellow-400
                                @endif">
                                {{ ucfirst($statement->ocr_status) }}
                            </span>
                            <p class="text-gray-400 text-xs mt-1">
                                {{ $statement->total_transactions ?? 0 }} transactions
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-file-pdf text-gray-600 text-4xl mb-4"></i>
                    <p class="text-gray-400">No bank statements uploaded yet</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</x-app-layout>