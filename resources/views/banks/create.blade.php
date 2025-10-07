<x-app-layout>
    <x-slot name="header">
        Add New Bank
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('banks.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-university mr-1"></i>Banks
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Add New</span>
            </nav>
        </div>

        <!-- Form Card -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-university text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Add New Bank</h2>
                        <p class="text-gray-400">Fill in the bank details</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('banks.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Bank Code -->
                <div>
                    <label for="code" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-barcode mr-2"></i>Bank Code
                        <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        value="{{ old('code') }}" 
                        required 
                        maxlength="10"
                        autofocus
                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all uppercase"
                        placeholder="e.g., MANDIRI, BCA, BNI">
                    @error('code')
                        <p class="text-red-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Unique identifier for the bank (max 10 characters)
                    </p>
                </div>

                <!-- Bank Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-building mr-2"></i>Bank Name
                        <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}" 
                        required 
                        maxlength="100"
                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="e.g., Bank Mandiri">
                    @error('name')
                        <p class="text-red-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Bank Logo -->
                <div>
                    <label for="logo" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-image mr-2"></i>Bank Logo
                        <span class="text-gray-500 text-xs">(Optional)</span>
                    </label>
                    <div class="flex items-center space-x-4">
                        <div id="logoPreview" class="hidden w-24 h-24 bg-white rounded-xl p-2 flex items-center justify-center">
                            <img id="previewImage" src="" alt="Logo Preview" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-1">
                            <input 
                                type="file" 
                                id="logo" 
                                name="logo" 
                                accept="image/png,image/jpg,image/jpeg,image/svg+xml"
                                onchange="previewLogo(event)"
                                class="block w-full text-sm text-gray-400
                                    file:mr-4 file:py-3 file:px-6
                                    file:rounded-xl file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-600 file:text-white
                                    hover:file:bg-blue-700
                                    file:cursor-pointer file:transition-all">
                        </div>
                    </div>
                    @error('logo')
                        <p class="text-red-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Supported formats: PNG, JPG, JPEG, SVG (Max: 2MB)
                    </p>
                </div>

                <!-- Is Active -->
                <div class="flex items-center space-x-3 p-4 bg-slate-900/50 rounded-xl">
                    <input 
                        type="checkbox" 
                        id="is_active" 
                        name="is_active" 
                        value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 rounded border-slate-700 bg-slate-900/50 focus:ring-blue-500 focus:ring-offset-0 transition">
                    <label for="is_active" class="text-white font-semibold cursor-pointer select-none flex-1">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        Active Bank
                    </label>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                    <div class="flex space-x-3">
                        <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                        <div>
                            <p class="text-blue-300 font-semibold mb-1">Bank Activation</p>
                            <p class="text-blue-200 text-sm">Active banks will be available for bank statement uploads. You can change this status later.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button 
                        type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center justify-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Create Bank</span>
                    </button>
                    <a 
                        href="{{ route('banks.index') }}"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center justify-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>

        </div>

    </div>

    <script>
        function previewLogo(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('logoPreview').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</x-app-layout>