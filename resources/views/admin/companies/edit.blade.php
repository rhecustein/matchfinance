<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Company: {{ $company->name }}
            </h2>
            <a href="{{ route('admin.companies.show', $company) }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <form action="{{ route('admin.companies.update', $company) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

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
                               value="{{ old('name', $company->name) }}"
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
                                   value="{{ old('slug', $company->slug) }}"
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
                                       value="{{ old('subdomain', $company->subdomain) }}"
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

                    <!-- Custom Domain -->
                    <div>
                        <label for="domain" class="block text-sm font-semibold text-gray-300 mb-2">
                            Custom Domain (Optional)
                        </label>
                        <input type="text" 
                               name="domain" 
                               id="domain" 
                               value="{{ old('domain', $company->domain) }}"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="acme.com">
                        <p class="text-xs text-gray-400 mt-1">Enter custom domain without http:// or https://</p>
                        @error('domain')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Logo Upload -->
                    <div>
                        <label for="logo" class="block text-sm font-semibold text-gray-300 mb-2">
                            Company Logo
                        </label>
                        
                        @if($company->logo)
                        <div class="mb-3">
                            <img src="{{ asset($company->logo) }}" alt="Current Logo" class="h-20 rounded-lg border border-slate-600">
                            <p class="text-xs text-gray-400 mt-1">Current logo</p>
                        </div>
                        @endif

                        <div class="flex items-center gap-4">
                            <label for="logo" class="flex-1 cursor-pointer">
                                <div class="flex items-center justify-center px-4 py-3 bg-slate-700 border-2 border-dashed border-slate-600 rounded-lg hover:border-blue-500 transition">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-gray-400 text-sm">Click to upload new logo</p>
                                        <p class="text-gray-500 text-xs mt-1">PNG, JPG up to 2MB</p>
                                    </div>
                                </div>
                                <input type="file" 
                                       name="logo" 
                                       id="logo" 
                                       accept="image/*"
                                       class="hidden"
                                       onchange="previewLogo(event)">
                            </label>

                            <div id="logo-preview" class="hidden">
                                <img id="preview-image" src="" alt="Preview" class="h-20 rounded-lg border border-slate-600">
                            </div>
                        </div>

                        @error('logo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Current Status Info (Read-only) -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                    Current Status
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-900/50 rounded-xl p-4">
                        <p class="text-gray-400 text-sm mb-2">Status</p>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            @if($company->status === 'active') bg-green-900/30 text-green-400
                            @elseif($company->status === 'trial') bg-yellow-900/30 text-yellow-400
                            @elseif($company->status === 'suspended') bg-red-900/30 text-red-400
                            @else bg-gray-900/30 text-gray-400
                            @endif">
                            {{ ucfirst($company->status) }}
                        </span>
                    </div>

                    @if($company->status === 'trial' && $company->trial_ends_at)
                    <div class="bg-slate-900/50 rounded-xl p-4">
                        <p class="text-gray-400 text-sm mb-2">Trial Ends</p>
                        <p class="text-white font-semibold">{{ $company->trial_ends_at->format('d M Y') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $company->trial_ends_at->diffForHumans() }}</p>
                    </div>
                    @endif

                    <div class="bg-slate-900/50 rounded-xl p-4">
                        <p class="text-gray-400 text-sm mb-2">Created</p>
                        <p class="text-white font-semibold">{{ $company->created_at->format('d M Y') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $company->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-yellow-900/20 border border-yellow-600 rounded-lg">
                    <p class="text-yellow-400 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        To change company status, use the action buttons on the company detail page.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.companies.show', $company) }}" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Update Company
                </button>
            </div>
        </form>

    </div>

    @push('scripts')
    <script>
        // Preview logo before upload
        function previewLogo(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('logo-preview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        // Auto-generate slug from company name
        document.getElementById('name').addEventListener('input', function(e) {
            const name = e.target.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            
            // Only update if user hasn't manually changed it
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