<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Company
            </h2>
            <a href="{{ route('admin.companies.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <form action="{{ route('admin.companies.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Company Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-building text-blue-500 mr-2"></i>
                    Company Information
                </h3>

                <div class="space-y-4">
                    <!-- Company Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                            Company Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Acme Corporation">
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Slug -->
                        <div>
                            <label for="slug" class="block text-sm font-semibold text-gray-300 mb-2">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="slug" 
                                   id="slug" 
                                   value="{{ old('slug') }}"
                                   required
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="acme-corporation">
                            @error('slug')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subdomain -->
                        <div>
                            <label for="subdomain" class="block text-sm font-semibold text-gray-300 mb-2">
                                Subdomain <span class="text-red-500">*</span>
                            </label>
                            <div class="flex">
                                <input type="text" 
                                       name="subdomain" 
                                       id="subdomain" 
                                       value="{{ old('subdomain') }}"
                                       required
                                       class="flex-1 px-4 py-3 bg-slate-700 border border-slate-600 rounded-l-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="acme">
                                <span class="px-4 py-3 bg-slate-600 border border-slate-600 rounded-r-lg text-gray-400 text-sm">
                                    .matchfinance.app
                                </span>
                            </div>
                            @error('subdomain')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-semibold text-gray-300 mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status" 
                                    id="status" 
                                    required
                                    onchange="toggleTrialDays()"
                                    class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="trial" {{ old('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Trial Days -->
                        <div id="trial-days-wrapper">
                            <label for="trial_days" class="block text-sm font-semibold text-gray-300 mb-2">
                                Trial Days
                            </label>
                            <input type="number" 
                                   name="trial_days" 
                                   id="trial_days" 
                                   value="{{ old('trial_days', 14) }}"
                                   min="0"
                                   max="365"
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="14">
                            <p class="text-xs text-gray-400 mt-1">Only applicable if status is Trial</p>
                            @error('trial_days')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Plan Selection -->
                    <div>
                        <label for="plan_id" class="block text-sm font-semibold text-gray-300 mb-2">
                            Subscription Plan
                        </label>
                        <select name="plan_id" 
                                id="plan_id" 
                                class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">No subscription (Trial only)</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} - Rp {{ number_format($plan->price, 0, ',', '.') }}/{{ $plan->billing_period === 'monthly' ? 'month' : 'year' }}
                            </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">If plan is selected, subscription will be created automatically</p>
                        @error('plan_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Owner Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-user-crown text-purple-500 mr-2"></i>
                    Owner Information
                </h3>

                <div class="space-y-4">
                    <!-- Owner Name -->
                    <div>
                        <label for="owner_name" class="block text-sm font-semibold text-gray-300 mb-2">
                            Owner Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="owner_name" 
                               id="owner_name" 
                               value="{{ old('owner_name') }}"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="John Doe">
                        @error('owner_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Owner Email -->
                    <div>
                        <label for="owner_email" class="block text-sm font-semibold text-gray-300 mb-2">
                            Owner Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               name="owner_email" 
                               id="owner_email" 
                               value="{{ old('owner_email') }}"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="john@acme.com">
                        @error('owner_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Owner Password -->
                        <div>
                            <label for="owner_password" class="block text-sm font-semibold text-gray-300 mb-2">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   name="owner_password" 
                                   id="owner_password" 
                                   required
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                            @error('owner_password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="owner_password_confirmation" class="block text-sm font-semibold text-gray-300 mb-2">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   name="owner_password_confirmation" 
                                   id="owner_password_confirmation" 
                                   required
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.companies.index') }}" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Create Company
                </button>
            </div>
        </form>

    </div>

    @push('scripts')
    <script>
        // Auto-generate slug and subdomain from company name
        document.getElementById('name').addEventListener('input', function(e) {
            const name = e.target.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            
            if (!document.getElementById('slug').value) {
                document.getElementById('slug').value = slug;
            }
            if (!document.getElementById('subdomain').value) {
                document.getElementById('subdomain').value = slug;
            }
        });

        // Toggle trial days visibility
        function toggleTrialDays() {
            const status = document.getElementById('status').value;
            const trialDaysWrapper = document.getElementById('trial-days-wrapper');
            
            if (status === 'trial') {
                trialDaysWrapper.style.display = 'block';
            } else {
                trialDaysWrapper.style.display = 'none';
            }
        }

        // Initialize on page load
        toggleTrialDays();
    </script>
    @endpush
</x-app-layout>