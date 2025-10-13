{{-- resources/views/bank-statements/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">Upload Bank Statement</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            {{-- Processing Queue Alert --}}
            @if(session('queued_count'))
                <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 border border-blue-500 rounded-xl p-6 shadow-lg">
                    <div class="flex items-start space-x-4 mb-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-white text-lg mb-2">
                                Upload Successful!
                            </h4>
                            <div class="space-y-2 text-sm">
                                @if(session('uploaded_count') > 0)
                                    <div class="flex items-center text-green-300">
                                        <i class="fas fa-plus-circle mr-2"></i>
                                        <span><strong>{{ session('uploaded_count') }}</strong> new file(s) uploaded and queued</span>
                                    </div>
                                @endif
                                
                                @if(session('replaced_count') > 0)
                                    <div class="flex items-center text-blue-300">
                                        <i class="fas fa-sync-alt mr-2"></i>
                                        <span><strong>{{ session('replaced_count') }}</strong> file(s) replaced (duplicate detected, old data cleared)</span>
                                    </div>
                                @endif
                                
                                @if(session('failed_files') && count(session('failed_files')) > 0)
                                    <div class="flex items-start text-red-300">
                                        <i class="fas fa-times-circle mr-2 mt-0.5"></i>
                                        <div>
                                            <span><strong>{{ count(session('failed_files')) }}</strong> file(s) failed:</span>
                                            <ul class="list-disc list-inside ml-4 mt-1 text-xs">
                                                @foreach(session('failed_files') as $failedFile)
                                                    <li>{{ $failedFile }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <div class="flex items-center justify-between text-sm mb-3">
                            <div class="flex items-center text-blue-200">
                                <i class="fas fa-cog fa-spin mr-2"></i>
                                <span>OCR processing will start automatically...</span>
                            </div>
                            <a href="{{ route('bank-statements.index') }}" class="text-blue-400 hover:text-blue-300 font-semibold transition">
                                View Progress <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                        <div class="bg-slate-800 rounded-full h-2.5 overflow-hidden">
                            <div class="processing-pulse bg-gradient-to-r from-blue-500 via-purple-500 to-blue-500 h-2.5 rounded-full" style="width: 100%; animation: pulse 2s ease-in-out infinite"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Processing time varies based on PDF size and complexity (usually 30s - 2min per file)
                        </p>
                        
                        @if(session('replaced_count') > 0)
                            <div class="mt-3 bg-yellow-600/20 border border-yellow-500/30 rounded-lg p-3">
                                <p class="text-xs text-yellow-300">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>Note:</strong> Replaced files will overwrite existing data. Previous transactions and OCR results have been cleared.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <style>
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.5; }
                    }
                </style>
            @endif
            
            {{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="bg-green-600/20 border border-green-600 text-green-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ERROR MESSAGE --}}
            @if(session('error'))
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            {{-- VALIDATION ERRORS --}}
            @if($errors->any())
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-6 py-4 rounded-lg">
                    <h4 class="font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>Validation Errors:</h4>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Header --}}
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-2">Upload Bank Statement</h2>
                    <p class="text-gray-400">Upload bank statement PDF files (Max 10 files at once)</p>
                </div>
                <a href="{{ route('bank-statements.index') }}" 
                   class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>

            {{-- Upload Form --}}
            <div id="uploadSection" class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-upload mr-2"></i>Upload Bank Statement File(s)
                </h3>
                
                <form action="{{ route('bank-statements.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="space-y-6">
                    @csrf
                    
                    {{-- Bank Selection --}}
                    <div>
                        <label for="bank_id" class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-university mr-1"></i>Select Bank <span class="text-red-400">*</span>
                        </label>
                        <select id="bank_id" name="bank_id" required
                            class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Select Bank --</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                    {{ $bank->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_id')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>Make sure the selected bank matches the statement file(s)
                        </p>
                    </div>

                    {{-- Multi-File Upload --}}
                    <div>
                        <label for="files" class="block text-sm font-semibold text-gray-300 mb-2">
                            <i class="fas fa-file-pdf mr-1"></i>PDF File(s) <span class="text-red-400">*</span>
                            <span class="text-gray-500 font-normal">(Max 10 files, 10MB each)</span>
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="files" class="flex flex-col items-center justify-center w-full h-56 border-2 border-slate-600 border-dashed rounded-xl cursor-pointer bg-slate-900/30 hover:bg-slate-900/50 hover:border-blue-500 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-6xl text-slate-500 mb-4"></i>
                                    <p class="mb-2 text-sm text-gray-300">
                                        <span class="font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PDF only (MAX. 10 files, 10MB each)</p>
                                </div>
                                <input id="files" name="files[]" type="file" class="hidden" accept=".pdf" multiple required />
                            </label>
                        </div>
                        @error('files')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        @error('files.*')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        
                        {{-- Selected Files List --}}
                        <div id="filesList" class="mt-4 space-y-2 hidden"></div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex gap-3">
                        <button type="submit" id="uploadBtn"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center">
                            <i class="fas fa-upload mr-2"></i>Upload & Process
                        </button>
                        <a href="{{ route('bank-statements.index') }}"
                            class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-6 rounded-lg text-center transition">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Multi-file upload handler
        const filesInput = document.getElementById('files');
        const filesList = document.getElementById('filesList');
        const uploadBtn = document.getElementById('uploadBtn');
        let selectedFiles = [];

        filesInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            
            // Validate max 10 files
            if (files.length > 10) {
                showAlert('Maximum 10 files allowed at once!', 'error');
                filesInput.value = '';
                return;
            }

            selectedFiles = files;
            displaySelectedFiles(files);
        });

        function displaySelectedFiles(files) {
            if (files.length === 0) {
                filesList.classList.add('hidden');
                filesList.innerHTML = '';
                return;
            }

            filesList.classList.remove('hidden');
            filesList.innerHTML = '';

            files.forEach((file, index) => {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const isValid = file.size <= 10 * 1024 * 1024; // 10MB
                
                const fileCard = document.createElement('div');
                fileCard.className = `bg-slate-900/50 rounded-lg p-4 border ${isValid ? 'border-slate-700' : 'border-red-600'} flex items-center justify-between`;
                fileCard.innerHTML = `
                    <div class="flex items-center space-x-3 flex-1">
                        <i class="fas fa-file-pdf text-2xl ${isValid ? 'text-red-400' : 'text-red-600'}"></i>
                        <div class="flex-1">
                            <p class="text-white font-semibold truncate">${file.name}</p>
                            <p class="text-sm ${isValid ? 'text-gray-400' : 'text-red-400'}">
                                ${fileSize} MB ${!isValid ? '- TOO LARGE!' : ''}
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="removeFile(${index})" class="text-red-400 hover:text-red-300 transition ml-3">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                filesList.appendChild(fileCard);
            });

            // Check if any file is too large
            const hasInvalidFile = files.some(f => f.size > 10 * 1024 * 1024);
            uploadBtn.disabled = hasInvalidFile;
            
            if (hasInvalidFile) {
                showAlert('One or more files exceed 10MB limit!', 'error');
            }
        }

        window.removeFile = function(index) {
            selectedFiles.splice(index, 1);
            
            // Update the file input
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            filesInput.files = dt.files;
            
            displaySelectedFiles(selectedFiles);
        };

        // Form validation before submit
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const bankId = document.getElementById('bank_id').value;
            const files = filesInput.files;

            if (!bankId) {
                e.preventDefault();
                showAlert('Please select a bank!', 'error');
                return;
            }

            if (files.length === 0) {
                e.preventDefault();
                showAlert('Please select at least one PDF file!', 'error');
                return;
            }

            if (files.length > 10) {
                e.preventDefault();
                showAlert('Maximum 10 files allowed!', 'error');
                return;
            }

            // Check file sizes
            for (let file of files) {
                if (file.size > 10 * 1024 * 1024) {
                    e.preventDefault();
                    showAlert(`File "${file.name}" exceeds 10MB limit!`, 'error');
                    return;
                }
            }

            // Show loading state
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading & Processing...';
        });

        // Show Alert Function
        function showAlert(message, type = 'info') {
            const colors = {
                error: 'bg-red-600/20 text-red-400 border-red-500',
                success: 'bg-green-600/20 text-green-400 border-green-500',
                warning: 'bg-yellow-600/20 text-yellow-400 border-yellow-500',
                info: 'bg-blue-600/20 text-blue-400 border-blue-500'
            };

            const icons = {
                error: 'fa-times-circle',
                success: 'fa-check-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            const alertDiv = document.createElement('div');
            alertDiv.className = `${colors[type]} border px-6 py-4 rounded-lg flex items-center space-x-3 mb-4`;
            alertDiv.innerHTML = `
                <i class="fas ${icons[type]} text-2xl"></i>
                <p class="font-semibold">${message}</p>
            `;
            
            const container = document.querySelector('.max-w-7xl');
            const firstChild = container.children[0];
            container.insertBefore(alertDiv, firstChild);

            setTimeout(() => alertDiv.remove(), 5000);
        }

        // Auto-hide success/error messages after 10 seconds
        setTimeout(() => {
            document.querySelectorAll('.bg-green-600\\/20, .bg-red-600\\/20').forEach(el => {
                if (el.textContent.includes('successfully') || el.textContent.includes('failed')) {
                    el.style.transition = 'opacity 0.5s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 500);
                }
            });
        }, 10000);
    </script>
    @endpush
</x-app-layout>