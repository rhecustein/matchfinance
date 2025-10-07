<x-app-layout>
    <x-slot name="header">Upload Bank Statement</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex items-center space-x-3 mb-3">
                <a href="{{ route('bank-statements.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="text-2xl font-bold text-white">Upload New Bank Statement</h2>
            </div>
            <p class="text-gray-400">Upload your bank statement PDF for automatic transaction processing</p>
        </div>

        {{-- Upload Form --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-8">
            <form action="{{ route('bank-statements.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf

                {{-- Bank Selection --}}
                <div class="mb-8">
                    <label class="block text-white font-semibold mb-3 flex items-center space-x-2">
                        <i class="fas fa-university text-blue-400"></i>
                        <span>Select Bank</span>
                        <span class="text-red-400">*</span>
                    </label>
                    <select name="bank_id" required class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-all @error('bank_id') border-red-500 @enderror">
                        <option value="">Choose a bank...</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_id')
                        <p class="text-red-400 text-sm mt-2">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- File Upload --}}
                <div class="mb-8">
                    <label class="block text-white font-semibold mb-3 flex items-center space-x-2">
                        <i class="fas fa-file-pdf text-blue-400"></i>
                        <span>Upload PDF File</span>
                        <span class="text-red-400">*</span>
                    </label>
                    
                    <div class="relative">
                        <input type="file" name="file" id="fileInput" accept=".pdf" required class="hidden" onchange="handleFileSelect(event)">
                        
                        <div id="dropZone" class="border-2 border-dashed border-slate-600 rounded-xl p-12 text-center hover:border-blue-500 transition-all cursor-pointer bg-slate-900/50 @error('file') border-red-500 @enderror" onclick="document.getElementById('fileInput').click()">
                            <div id="dropZoneContent">
                                <i class="fas fa-cloud-upload-alt text-6xl text-gray-500 mb-4"></i>
                                <p class="text-white font-semibold mb-2">Click to upload or drag and drop</p>
                                <p class="text-gray-400 text-sm">PDF files only (Max 10MB)</p>
                            </div>
                            
                            <div id="filePreview" class="hidden">
                                <i class="fas fa-file-pdf text-6xl text-red-400 mb-4"></i>
                                <p class="text-white font-semibold mb-1" id="fileName"></p>
                                <p class="text-gray-400 text-sm" id="fileSize"></p>
                                <button type="button" onclick="clearFile(event)" class="mt-4 text-red-400 hover:text-red-300 text-sm">
                                    <i class="fas fa-times-circle mr-1"></i>Remove file
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    @error('file')
                        <p class="text-red-400 text-sm mt-2">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Info Box --}}
                <div class="bg-blue-600/10 border border-blue-500/30 rounded-xl p-6 mb-8">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-info-circle text-blue-400 text-xl mt-1"></i>
                        <div>
                            <h4 class="text-white font-semibold mb-2">What happens after upload?</h4>
                            <ul class="text-gray-300 text-sm space-y-1">
                                <li><i class="fas fa-check text-blue-400 mr-2"></i>Your PDF will be processed using OCR technology</li>
                                <li><i class="fas fa-check text-blue-400 mr-2"></i>Transactions will be extracted automatically</li>
                                <li><i class="fas fa-check text-blue-400 mr-2"></i>Smart matching will categorize transactions</li>
                                <li><i class="fas fa-check text-blue-400 mr-2"></i>You can review and verify the results</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between pt-6 border-t border-slate-700">
                    <a href="{{ route('bank-statements.index') }}" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-arrow-left mr-2"></i>Cancel
                    </a>
                    <button type="submit" id="submitBtn" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                        <i class="fas fa-upload"></i>
                        <span>Upload Statement</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const dropZoneContent = document.getElementById('dropZoneContent');
        const filePreview = document.getElementById('filePreview');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when dragging
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-500', 'bg-blue-500/5');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-500', 'bg-blue-500/5');
            }, false);
        });

        // Handle dropped files
        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect({ target: { files: files } });
            }
        }, false);

        function handleFileSelect(event) {
            const file = event.target.files[0];
            
            if (file) {
                // Validate file type
                if (file.type !== 'application/pdf') {
                    alert('Please upload a PDF file only');
                    fileInput.value = '';
                    return;
                }
                
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must not exceed 10MB');
                    fileInput.value = '';
                    return;
                }
                
                // Show file preview
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = formatFileSize(file.size);
                dropZoneContent.classList.add('hidden');
                filePreview.classList.remove('hidden');
            }
        }

        function clearFile(event) {
            event.stopPropagation();
            fileInput.value = '';
            dropZoneContent.classList.remove('hidden');
            filePreview.classList.add('hidden');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Form submission loading state
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Uploading...</span>';
        });
    </script>
</x-app-layout>