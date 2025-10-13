{{-- resources/views/bank-statements/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Bank Statement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Success Message --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700 font-medium">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Error Message --}}
            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-medium">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">{{ __('There were some errors with your submission') }}</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Upload Form --}}
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                                <i class="fas fa-cloud-upload-alt text-blue-600 mr-2"></i>
                                Upload Bank Statement Files
                            </h3>

                            <form action="{{ route('bank-statements.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                                @csrf

                                {{-- Bank Selection --}}
                                <div class="mb-6">
                                    <label for="bank_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-university text-blue-600 mr-1"></i>
                                        Select Bank <span class="text-red-500">*</span>
                                    </label>
                                    <select id="bank_id" name="bank_id" required
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="">-- Choose Bank --</option>
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                                {{ $bank->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Select the bank that matches your statement
                                    </p>
                                </div>

                                {{-- File Upload Area --}}
                                <div class="mb-6">
                                    <label for="files" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-file-pdf text-red-600 mr-1"></i>
                                        PDF Files <span class="text-red-500">*</span>
                                    </label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-400 transition cursor-pointer" id="dropZone">
                                        <div class="space-y-1 text-center">
                                            <i class="fas fa-cloud-upload-alt text-gray-400 text-5xl mb-3"></i>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="files" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                                    <span>Upload files</span>
                                                    <input id="files" name="files[]" type="file" class="sr-only" accept=".pdf" multiple required>
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                PDF up to 10MB per file (Max 10 files)
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Selected Files Preview --}}
                                    <div id="filesList" class="mt-4 space-y-2 hidden"></div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('bank-statements.index') }}" 
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Cancel
                                    </a>
                                    <button type="submit" id="submitBtn"
                                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-upload mr-2"></i>
                                        Upload & Process
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Information Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-900 mb-4">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                Upload Instructions
                            </h4>
                            <div class="space-y-3 text-sm text-gray-600">
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                                    <span>Select the correct bank</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                                    <span>Upload PDF files only</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                                    <span>Max 10 files at once</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                                    <span>Each file max 10MB</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                                    <span>Files will be processed automatically</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Processing Info --}}
                    <div class="bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-semibold text-blue-900 mb-3">
                                <i class="fas fa-robot text-blue-600 mr-2"></i>
                                Auto Processing
                            </h4>
                            <p class="text-sm text-blue-700 mb-3">
                                After upload, your files will be automatically:
                            </p>
                            <div class="space-y-2 text-sm text-blue-600">
                                <div class="flex items-center">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    <span>Processed by OCR</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    <span>Transactions extracted</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    <span>Keywords matched</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    <span>Ready for review</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Supported Banks --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">
                                <i class="fas fa-university text-blue-600 mr-2"></i>
                                Supported Banks
                            </h4>
                            <div class="space-y-2">
                                @foreach($banks as $bank)
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        <span>{{ $bank->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const fileInput = document.getElementById('files');
        const filesList = document.getElementById('filesList');
        const dropZone = document.getElementById('dropZone');
        const submitBtn = document.getElementById('submitBtn');
        let selectedFiles = [];

        // File input change handler
        fileInput.addEventListener('change', function(e) {
            handleFiles(Array.from(e.target.files));
        });

        // Drag and drop handlers
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            
            const files = Array.from(e.dataTransfer.files).filter(file => file.type === 'application/pdf');
            handleFiles(files);
        });

        // Handle selected files
        function handleFiles(files) {
            if (files.length === 0) return;

            // Limit to 10 files
            if (selectedFiles.length + files.length > 10) {
                alert('Maximum 10 files allowed');
                return;
            }

            // Validate file size and type
            const validFiles = files.filter(file => {
                if (file.type !== 'application/pdf') {
                    alert(`${file.name} is not a PDF file`);
                    return false;
                }
                if (file.size > 10 * 1024 * 1024) {
                    alert(`${file.name} exceeds 10MB limit`);
                    return false;
                }
                return true;
            });

            // Add to selected files
            validFiles.forEach(file => {
                if (!selectedFiles.find(f => f.name === file.name)) {
                    selectedFiles.push(file);
                }
            });

            displayFiles();
            updateFileInput();
        }

        // Display selected files
        function displayFiles() {
            if (selectedFiles.length === 0) {
                filesList.classList.add('hidden');
                return;
            }

            filesList.classList.remove('hidden');
            filesList.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-md';
                fileItem.innerHTML = `
                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                        <i class="fas fa-file-pdf text-red-500 text-xl flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                            <p class="text-xs text-gray-500">${fileSize} MB</p>
                        </div>
                    </div>
                    <button type="button" onclick="removeFile(${index})" 
                        class="ml-3 flex-shrink-0 text-red-600 hover:text-red-800 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                filesList.appendChild(fileItem);
            });
        }

        // Remove file
        window.removeFile = function(index) {
            selectedFiles.splice(index, 1);
            displayFiles();
            updateFileInput();
        };

        // Update actual file input
        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        // Form submit handler
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            if (!document.getElementById('bank_id').value) {
                e.preventDefault();
                alert('Please select a bank first');
                return;
            }

            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('Please select at least one PDF file');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...';
        });
    </script>
    @endpush
</x-app-layout>