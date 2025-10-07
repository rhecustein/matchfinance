<!-- =================================================================== -->
<!-- EDIT VIEW: resources/views/banks/edit.blade.php -->
<!-- =================================================================== -->

<x-app-layout>
    <x-slot name="header">
        Edit Bank
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('banks.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-university mr-1"></i>Banks
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Edit {{ $bank->name }}</span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex items-center space-x-4 mb-4">
                            @if($bank->logo)
                                <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="w-14 h-14 object-contain rounded-xl bg-white p-2">
                            @else
                                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-university text-white text-2xl"></i>
                                </div>
                            @endif
                            <div>
                                <h2 class="text-2xl font-bold text-white">Edit Bank</h2>
                                <p class="text-gray-400">Update bank information</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('banks.update', $bank) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PATCH')

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
                                value="{{ old('code', $bank->code) }}" 
                                required 
                                maxlength="10"
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all uppercase"
                                placeholder="e.g., MANDIRI, BCA, BNI">
                            @error('code')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
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
                                value="{{ old('name', $bank->name) }}" 
                                required 
                                maxlength="100"
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
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
                            </label>
                            <div class="flex items-center space-x-4">
                                <div id="logoPreview" class="w-24 h-24 bg-white rounded-xl p-2 flex items-center justify-center {{ $bank->logo ? '' : 'hidden' }}">
                                    <img id="previewImage" src="{{ $bank->logo ? Storage::url($bank->logo) : '' }}" alt="Logo Preview" class="max-w-full max-h-full object-contain">
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
                                            file:bg-purple-600 file:text-white
                                            hover:file:bg-purple-700
                                            file:cursor-pointer file:transition-all">
                                    @if($bank->logo)
                                        <p class="text-gray-500 text-xs mt-2">Current logo will be replaced if you upload a new one</p>
                                    @endif
                                </div>
                            </div>
                            @error('logo')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center space-x-3 p-4 bg-slate-900/50 rounded-xl">
                            <input 
                                type="checkbox" 
                                id="is_active" 
                                name="is_active" 
                                value="1"
                                {{ old('is_active', $bank->is_active) ? 'checked' : '' }}
                                class="w-5 h-5 text-purple-600 rounded border-slate-700 bg-slate-900/50 focus:ring-purple-500 focus:ring-offset-0 transition">
                            <label for="is_active" class="text-white font-semibold cursor-pointer select-none flex-1">
                                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                Active Bank
                            </label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6">
                            <button 
                                type="submit"
                                class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg flex items-center justify-center space-x-2">
                                <i class="fas fa-save"></i>
                                <span>Update Bank</span>
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

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Bank Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Bank Information</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Status</span>
                            @if($bank->is_active)
                                <span class="text-green-500 font-semibold">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            @else
                                <span class="text-red-500 font-semibold">
                                    <i class="fas fa-times-circle"></i> Inactive
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Statements</span>
                            <span class="text-white font-semibold">{{ $bank->bankStatements()->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Created</span>
                            <span class="text-white font-semibold">{{ $bank->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                @if($bank->bankStatements()->count() == 0)
                    <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-red-400 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                        </h3>
                        <p class="text-gray-400 text-sm mb-4">
                            Permanently delete this bank. This action cannot be undone.
                        </p>
                        <button 
                            type="button"
                            onclick="confirmDelete()"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl font-semibold transition-all shadow-lg">
                            <i class="fas fa-trash mr-2"></i>Delete Bank
                        </button>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-yellow-900/20 to-slate-900 rounded-2xl p-6 border border-yellow-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-yellow-400 mb-4">
                            <i class="fas fa-info-circle mr-2"></i>Cannot Delete
                        </h3>
                        <p class="text-gray-400 text-sm">
                            This bank has {{ $bank->bankStatements()->count() }} statement(s) and cannot be deleted. Remove all statements first.
                        </p>
                    </div>
                @endif

            </div>

        </div>

    </div>

    <!-- Delete Confirmation Modal -->
    @if($bank->bankStatements()->count() == 0)
        <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
                
                <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Delete Bank?</h3>
                        <p class="text-gray-400">Are you sure you want to delete <strong class="text-white">{{ $bank->name }}</strong>? This action cannot be undone.</p>
                    </div>

                    <form method="POST" action="{{ route('banks.destroy', $bank) }}">
                        @csrf
                        @method('DELETE')
                        <div class="flex space-x-3">
                            <button 
                                type="button"
                                onclick="closeDeleteModal()"
                                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Delete Bank
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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

        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</x-app-layout>


<!-- =================================================================== -->
<!-- SHOW VIEW: resources/views/banks/show.blade.php -->
<!-- =================================================================== -->

<x-app-layout>
    <x-slot name="header">
        Bank Details
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('banks.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-university mr-1"></i>Banks
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">{{ $bank->name }}</span>
            </nav>
        </div>

        <!-- Bank Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                @if($bank->logo)
                    <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="w-24 h-24 object-contain rounded-xl bg-white p-4">
                @else
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-university text-white text-4xl"></i>
                    </div>
                @endif
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-white mb-2">{{ $bank->name }}</h2>
                    <p class="text-blue-100 mb-4">{{ $bank->code }}</p>
                    <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                        @if($bank->is_active)
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-green-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-check-circle"></i>
                                <span>Active</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-red-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-times-circle"></i>
                                <span>Inactive</span>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('banks.edit', $bank) }}" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                        <i class="fas fa-edit"></i>
                        <span>Edit Bank</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Total Statements</p>
                <p class="text-white text-2xl font-bold">{{ $stats['total_statements'] }}</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Completed</p>
                <p class="text-white text-2xl font-bold">{{ $stats['completed'] }}</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-spinner text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Processing</p>
                <p class="text-white text-2xl font-bold">{{ $stats['processing'] + $stats['pending'] }}</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Failed</p>
                <p class="text-white text-2xl font-bold">{{ $stats['failed'] }}</p>
            </div>

        </div>

        <!-- Recent Statements -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-history mr-3 text-blue-500"></i>
                    Recent Bank Statements
                </h3>
                <a href="{{ route('bank-statements.index', ['bank' => $bank->id]) }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                    View All →
                </a>
            </div>
            
            @if($bank->bankStatements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">File</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Period</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            @foreach($bank->bankStatements as $statement)
                                <tr class="hover:bg-slate-800/50 transition">
                                    <td class="px-6 py-4">
                                        <p class="text-white font-semibold text-sm">{{ $statement->original_filename }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-300 text-sm">
                                            {{ $statement->statement_period_start?->format('M Y') ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg text-xs font-semibold
                                            {{ $statement->ocr_status === 'completed' ? 'bg-green-600/20 text-green-400' : '' }}
                                            {{ $statement->ocr_status === 'processing' ? 'bg-yellow-600/20 text-yellow-400' : '' }}
                                            {{ $statement->ocr_status === 'failed' ? 'bg-red-600/20 text-red-400' : '' }}
                                            {{ $statement->ocr_status === 'pending' ? 'bg-blue-600/20 text-blue-400' : '' }}">
                                            <i class="fas fa-circle"></i>
                                            <span>{{ ucfirst($statement->ocr_status) }}</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-300 text-sm">{{ $statement->uploaded_at->diffForHumans() }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                    <p class="text-gray-400 text-lg">No bank statements uploaded yet</p>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>