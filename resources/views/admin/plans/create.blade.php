<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Plan
            </h2>
            <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <form action="{{ route('admin.plans.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Basic Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Basic Information
                </h3>

                <div class="space-y-4">
                    <!-- Plan Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                            Plan Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Professional Plan">
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-semibold text-gray-300 mb-2">
                            Slug (Optional)
                        </label>
                        <input type="text" 
                               name="slug" 
                               id="slug" 
                               value="{{ old('slug') }}"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="professional-plan">
                        <p class="text-xs text-gray-400 mt-1">Will be auto-generated if left empty</p>
                        @error('slug')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="3"
                                  class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe this plan...">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Price -->
                        <div class="md:col-span-2">
                            <label for="price" class="block text-sm font-semibold text-gray-300 mb-2">
                                Price (Rp) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   value="{{ old('price', 0) }}"
                                   min="0"
                                   step="1000"
                                   required
                                   class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="100000">
                            @error('price')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Billing Period -->
                        <div>
                            <label for="billing_period" class="block text-sm font-semibold text-gray-300 mb-2">
                                Billing Period <span class="text-red-500">*</span>
                            </label>
                            <select name="billing_period" 
                                    id="billing_period" 
                                    required
                                    class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="monthly" {{ old('billing_period') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ old('billing_period') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                            @error('billing_period')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center gap-3">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-5 h-5 bg-slate-700 border-slate-600 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <label for="is_active" class="text-gray-300 font-semibold">
                            Active (available for subscription)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Plan Limits -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-sliders-h text-purple-500 mr-2"></i>
                    Plan Limits
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Max Users -->
                    <div>
                        <label for="max_users" class="block text-sm font-semibold text-gray-300 mb-2">
                            Max Users <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="max_users" 
                               id="max_users" 
                               value="{{ old('max_users', -1) }}"
                               min="-1"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="-1">
                        <p class="text-xs text-gray-400 mt-1">Use -1 for unlimited</p>
                        @error('max_users')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Max Products -->
                    <div>
                        <label for="max_products" class="block text-sm font-semibold text-gray-300 mb-2">
                            Max Products <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="max_products" 
                               id="max_products" 
                               value="{{ old('max_products', -1) }}"
                               min="-1"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="-1">
                        <p class="text-xs text-gray-400 mt-1">Use -1 for unlimited</p>
                        @error('max_products')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Max Transactions -->
                    <div>
                        <label for="max_transactions" class="block text-sm font-semibold text-gray-300 mb-2">
                            Max Transactions/Month <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="max_transactions" 
                               id="max_transactions" 
                               value="{{ old('max_transactions', -1) }}"
                               min="-1"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="-1">
                        <p class="text-xs text-gray-400 mt-1">Use -1 for unlimited</p>
                        @error('max_transactions')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Max Storage -->
                    <div>
                        <label for="max_storage_mb" class="block text-sm font-semibold text-gray-300 mb-2">
                            Max Storage (MB) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="max_storage_mb" 
                               id="max_storage_mb" 
                               value="{{ old('max_storage_mb', -1) }}"
                               min="-1"
                               required
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="-1">
                        <p class="text-xs text-gray-400 mt-1">Use -1 for unlimited</p>
                        @error('max_storage_mb')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Plan Features -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>
                    Features
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Bank Statements -->
                    <div class="flex items-start gap-3 p-4 bg-slate-900/50 rounded-lg">
                        <input type="checkbox" 
                               name="bank_statements" 
                               id="bank_statements" 
                               value="1"
                               {{ old('bank_statements') ? 'checked' : '' }}
                               class="w-5 h-5 mt-0.5 bg-slate-700 border-slate-600 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <div>
                            <label for="bank_statements" class="text-white font-semibold block">
                                Bank Statements
                            </label>
                            <p class="text-gray-400 text-xs mt-1">Upload and process bank statements</p>
                        </div>
                    </div>

                    <!-- Advanced Reports -->
                    <div class="flex items-start gap-3 p-4 bg-slate-900/50 rounded-lg">
                        <input type="checkbox" 
                               name="advanced_reports" 
                               id="advanced_reports" 
                               value="1"
                               {{ old('advanced_reports') ? 'checked' : '' }}
                               class="w-5 h-5 mt-0.5 bg-slate-700 border-slate-600 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <div>
                            <label for="advanced_reports" class="text-white font-semibold block">
                                Advanced Reports
                            </label>
                            <p class="text-gray-400 text-xs mt-1">Access to advanced analytics and reports</p>
                        </div>
                    </div>

                    <!-- API Access -->
                    <div class="flex items-start gap-3 p-4 bg-slate-900/50 rounded-lg">
                        <input type="checkbox" 
                               name="api_access" 
                               id="api_access" 
                               value="1"
                               {{ old('api_access') ? 'checked' : '' }}
                               class="w-5 h-5 mt-0.5 bg-slate-700 border-slate-600 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <div>
                            <label for="api_access" class="text-white font-semibold block">
                                API Access
                            </label>
                            <p class="text-gray-400 text-xs mt-1">Full API access for integrations</p>
                        </div>
                    </div>

                    <!-- Priority Support -->
                    <div class="flex items-start gap-3 p-4 bg-slate-900/50 rounded-lg">
                        <input type="checkbox" 
                               name="priority_support" 
                               id="priority_support" 
                               value="1"
                               {{ old('priority_support') ? 'checked' : '' }}
                               class="w-5 h-5 mt-0.5 bg-slate-700 border-slate-600 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <div>
                            <label for="priority_support" class="text-white font-semibold block">
                                Priority Support
                            </label>
                            <p class="text-gray-400 text-xs mt-1">Get faster response times</p>
                        </div>
                    </div>

                    <!-- Custom Branding -->
                    <div class="flex items-start gap-3 p-4 bg-slate-900/50 rounded-lg md:col-span-2">
                        <input type="checkbox" 
                               name="custom_branding" 
                               id="custom_branding" 
                               value="1"
                               {{ old('custom_branding') ? 'checked' : '' }}
                               class="w-5 h-5 mt-0.5 bg-slate-700 border-slate-600 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <div>
                            <label for="custom_branding" class="text-white font-semibold block">
                                Custom Branding
                            </label>
                            <p class="text-gray-400 text-xs mt-1">Custom logo, colors, and branding options</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.plans.index') }}" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Create Plan
                </button>
            </div>
        </form>

    </div>

    @push('scripts')
    <script>
        // Auto-generate slug from plan name
        document.getElementById('name').addEventListener('input', function(e) {
            const name = e.target.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            
            const slugInput = document.getElementById('slug');
            if (!slugInput.dataset.manuallyEdited) {
                slugInput.value = slug;
            }
        });

        // Track manual edits to slug
        document.getElementById('slug').addEventListener('input', function() {
            this.dataset.manuallyEdited = 'true';
        });
    </script>
    @endpush
</x-app-layout>