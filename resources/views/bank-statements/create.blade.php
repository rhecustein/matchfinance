{{-- resources/views/bank-statements/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">Upload Bank Statement</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Upload Bank Statement</h2>
                <p class="text-gray-400">Upload and process your bank statement PDF</p>
            </div>
            <a href="{{ route('bank-statements.index') }}" 
               class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        {{-- Upload Form --}}
        <div id="uploadSection" class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
            <h3 class="text-xl font-bold text-white mb-6">
                <i class="fas fa-upload mr-2"></i>Upload Bank Statement File
            </h3>
            
            <form id="uploadForm" class="space-y-6">
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
                            <option value="{{ $bank->id }}" data-code="{{ $bank->code }}">
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>Make sure the selected bank matches the statement file
                    </p>
                </div>

                {{-- File Upload --}}
                <div>
                    <label for="file" class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-file-pdf mr-1"></i>PDF File <span class="text-red-400">*</span>
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label for="file" class="flex flex-col items-center justify-center w-full h-56 border-2 border-slate-600 border-dashed rounded-xl cursor-pointer bg-slate-900/30 hover:bg-slate-900/50 hover:border-blue-500 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-6xl text-slate-500 mb-4"></i>
                                <p class="mb-2 text-sm text-gray-300">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500">PDF (MAX. 10MB)</p>
                            </div>
                            <input id="file" name="file" type="file" class="hidden" accept=".pdf" required />
                        </label>
                    </div>
                    <div id="fileInfo" class="mt-3 text-sm text-gray-300 hidden flex items-center space-x-2">
                        <i class="fas fa-file-pdf text-red-400"></i>
                        <span id="fileName"></span>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex gap-3">
                    <button type="submit" id="uploadBtn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i>Upload & Preview
                    </button>
                    <a href="{{ route('bank-statements.index') }}"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-6 rounded-lg text-center transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Loading Spinner --}}
        <div id="loadingSection" class="hidden bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
            <div class="flex flex-col items-center justify-center py-12">
                <div class="relative">
                    <div class="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-blue-500"></div>
                    <i class="fas fa-file-pdf absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-2xl text-blue-400"></i>
                </div>
                <h3 class="text-xl font-bold text-white mt-6 mb-2">Processing OCR...</h3>
                <p class="text-sm text-gray-400 mb-6">Please wait, processing your bank statement</p>
                <div class="w-full max-w-md">
                    <div class="bg-slate-900/50 rounded-full h-3 overflow-hidden">
                        <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p class="text-center text-xs text-gray-500 mt-2">
                        <i class="fas fa-spinner fa-spin mr-1"></i>Processing...
                    </p>
                </div>
            </div>
        </div>

        {{-- Preview Section --}}
        <div id="previewSection" class="hidden space-y-6">
            {{-- Header with Close Button --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">
                            <i class="fas fa-eye mr-2"></i>OCR Data Preview
                        </h3>
                        <p class="text-gray-400">Review the processed data before saving</p>
                    </div>
                    <button id="closePreview" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-4 border border-blue-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-200 mb-1">Period</p>
                            <p id="preview_period" class="text-lg font-bold text-white">-</p>
                        </div>
                        <i class="fas fa-calendar-alt text-3xl text-blue-300/50"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-4 border border-green-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-green-200 mb-1">Total Transactions</p>
                            <p id="preview_total_transactions" class="text-lg font-bold text-white">-</p>
                        </div>
                        <i class="fas fa-receipt text-3xl text-green-300/50"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-4 border border-purple-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-200 mb-1">Account Number</p>
                            <p id="preview_account_number" class="text-sm font-bold text-white truncate">-</p>
                        </div>
                        <i class="fas fa-credit-card text-3xl text-purple-300/50"></i>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl p-4 border border-orange-500/50 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-orange-200 mb-1">Keywords Detected</p>
                            <p id="preview_keywords_count" class="text-lg font-bold text-white">-</p>
                        </div>
                        <i class="fas fa-tags text-3xl text-orange-300/50"></i>
                    </div>
                </div>
            </div>

            {{-- Balance Info --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h4 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-wallet mr-2"></i>Balance Summary
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                        <p class="text-xs text-gray-400 mb-1">Opening Balance</p>
                        <p id="preview_opening_balance" class="text-base font-semibold text-white">-</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-green-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Credit</p>
                        <p id="preview_total_credit" class="text-base font-semibold text-green-400">-</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-red-700/50">
                        <p class="text-xs text-gray-400 mb-1">Total Debit</p>
                        <p id="preview_total_debit" class="text-base font-semibold text-red-400">-</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-blue-700/50">
                        <p class="text-xs text-gray-400 mb-1">Closing Balance</p>
                        <p id="preview_closing_balance" class="text-base font-semibold text-blue-400">-</p>
                    </div>
                </div>
            </div>

            {{-- Keywords Detected Section --}}
            <div id="keywordsSection" class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h4 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-tags mr-2"></i>Keywords Detected in Transactions
                </h4>
                <div id="keywordsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            {{-- Transactions Preview --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-700">
                    <h4 class="text-lg font-bold text-white">
                        <i class="fas fa-list mr-2"></i>Transactions Preview (First 10)
                    </h4>
                </div>
                <div class="p-6">
                    <div id="previewTransactionsList" class="space-y-3">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <div class="flex gap-3">
                    <button id="confirmSave" type="button"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2"></i>Save to Database
                    </button>
                    <button id="cancelSave" type="button"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-times-circle mr-2"></i>Cancel
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- ✅ REPLACE MODAL --}}
    <div id="replaceModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 p-6 border-b border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-exclamation-triangle text-3xl text-white"></i>
                        <div>
                            <h3 class="text-xl font-bold text-white">Duplicate Detected</h3>
                            <p class="text-sm text-yellow-100">A similar bank statement already exists</p>
                        </div>
                    </div>
                    <button id="closeReplaceModal" class="text-white hover:text-yellow-200 transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-6">
                {{-- Error Message --}}
                <div id="duplicateMessage" class="bg-yellow-600/20 border border-yellow-500/50 rounded-lg p-4">
                    <p class="text-yellow-300 text-sm font-medium"></p>
                </div>

                {{-- Existing Data Info --}}
                <div class="bg-slate-900/50 rounded-xl border border-slate-700 overflow-hidden">
                    <div class="bg-slate-800/50 px-4 py-3 border-b border-slate-700">
                        <h4 class="text-white font-semibold">
                            <i class="fas fa-database mr-2"></i>Existing Data Information
                        </h4>
                    </div>
                    <div class="p-4 space-y-3" id="existingDataInfo">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                {{-- Options Info --}}
                <div class="bg-blue-600/20 border border-blue-500/50 rounded-lg p-4">
                    <h4 class="text-blue-300 font-semibold mb-3">
                        <i class="fas fa-info-circle mr-2"></i>Your Options
                    </h4>
                    <div class="space-y-2 text-sm text-blue-200">
                        <div class="flex items-start space-x-2">
                            <i class="fas fa-times text-red-400 mt-1"></i>
                            <div>
                                <span class="font-semibold">Cancel:</span> Keep existing data, discard new upload
                            </div>
                        </div>
                        <div class="flex items-start space-x-2">
                            <i class="fas fa-sync-alt text-green-400 mt-1"></i>
                            <div>
                                <span class="font-semibold">Replace:</span> Delete existing data and save new upload
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-6 border-t border-slate-700 bg-slate-800/30">
                <div class="flex gap-3">
                    <button id="cancelReplace" type="button"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button id="confirmReplace" type="button"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-sync-alt mr-2"></i>Replace Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let previewData = null;
        let duplicateData = null;

        document.getElementById('file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                document.getElementById('fileName').textContent = `${file.name} (${fileSize} MB)`;
                document.getElementById('fileInfo').classList.remove('hidden');
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const bankId = document.getElementById('bank_id').value;
            const fileInput = document.getElementById('file');
            
            if (!bankId) {
                showAlert('Please select a bank first!', 'error');
                return;
            }

            if (!fileInput.files.length) {
                showAlert('Please select a PDF file first!', 'error');
                return;
            }

            document.getElementById('uploadSection').classList.add('hidden');
            document.getElementById('loadingSection').classList.remove('hidden');
            document.getElementById('previewSection').classList.add('hidden');

            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    document.getElementById('progressBar').style.width = progress + '%';
                }
            }, 200);

            const formData = new FormData();
            formData.append('bank_id', bankId);
            formData.append('file', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route('bank-statements.upload.preview') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                clearInterval(progressInterval);
                document.getElementById('progressBar').style.width = '100%';

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const htmlText = await response.text();
                    console.error('Server returned HTML:', htmlText.substring(0, 500));
                    throw new Error('Server error. Check console or Laravel logs for details.');
                }

                const result = await response.json();

                if (result.success) {
                    previewData = result.data;
                    setTimeout(() => showPreview(result.data), 500);
                } else {
                    // ✅ Handle duplicate errors
                    if (result.error_type === 'duplicate_file' || result.error_type === 'duplicate_period') {
                        if (result.allow_replace) {
                            duplicateData = result.duplicate_data;
                            showReplaceModal(result);
                        } else {
                            showError(result.message, result.error_type);
                        }
                    } else {
                        showError(result.message || 'Upload failed', result.error_type);
                    }
                }

            } catch (error) {
                clearInterval(progressInterval);
                console.error('Upload error:', error);
                
                let errorMessage = 'Upload failed: ';
                if (error.message.includes('JSON') || error.message.includes('Server error')) {
                    errorMessage += 'Server returned an error. Check Laravel logs at storage/logs/laravel.log';
                } else {
                    errorMessage += error.message;
                }
                
                showError(errorMessage);
            }
        });

        // ✅ Show Replace Modal
        function showReplaceModal(errorData) {
            const modal = document.getElementById('replaceModal');
            const messageEl = modal.querySelector('#duplicateMessage p');
            const infoEl = modal.querySelector('#existingDataInfo');

            // Set message
            messageEl.textContent = errorData.message;

            // Set existing data info
            const data = errorData.duplicate_data;
            infoEl.innerHTML = `
                <div class="flex justify-between items-center py-2 border-b border-slate-700">
                    <span class="text-gray-400 text-sm">Period:</span>
                    <span class="text-white font-semibold">${data.period || 'N/A'}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-700">
                    <span class="text-gray-400 text-sm">Account:</span>
                    <span class="text-white font-semibold">${data.account || 'N/A'}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-700">
                    <span class="text-gray-400 text-sm">Uploaded:</span>
                    <span class="text-white font-semibold">${data.uploaded_at || 'N/A'}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-400 text-sm">Transactions:</span>
                    <span class="text-white font-semibold">${data.transactions || 0} transactions</span>
                </div>
            `;

            // Show modal
            modal.classList.remove('hidden');
        }

        // ✅ Handle Replace Confirm
        document.getElementById('confirmReplace')?.addEventListener('click', async function() {
            if (!duplicateData || !duplicateData.id) {
                showAlert('Invalid duplicate data', 'error');
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Replacing...';
            this.disabled = true;

            try {
                const response = await fetch('{{ route('bank-statements.replace') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        existing_id: duplicateData.id,
                        confirm: true
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showAlert(result.message, 'success');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect_url;
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Replace failed');
                }
            } catch (error) {
                console.error('Replace error:', error);
                showAlert('Failed to replace: ' + error.message, 'error');
                
                document.getElementById('replaceModal').classList.add('hidden');
                document.getElementById('uploadSection').classList.remove('hidden');
                document.getElementById('loadingSection').classList.add('hidden');
            } finally {
                this.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Replace Data';
                this.disabled = false;
            }
        });

        // ✅ Handle Replace Cancel
        document.getElementById('cancelReplace')?.addEventListener('click', function() {
            document.getElementById('replaceModal').classList.add('hidden');
            document.getElementById('uploadSection').classList.remove('hidden');
            document.getElementById('loadingSection').classList.add('hidden');
            document.getElementById('uploadForm').reset();
            document.getElementById('fileInfo').classList.add('hidden');
            duplicateData = null;
        });

        document.getElementById('closeReplaceModal')?.addEventListener('click', function() {
            document.getElementById('cancelReplace').click();
        });

        function showPreview(data) {
            document.getElementById('loadingSection').classList.add('hidden');
            document.getElementById('previewSection').classList.remove('hidden');

            document.getElementById('preview_period').textContent = data.summary.period;
            document.getElementById('preview_total_transactions').textContent = data.summary.total_transactions;
            document.getElementById('preview_account_number').textContent = data.summary.account_number;
            document.getElementById('preview_opening_balance').textContent = formatCurrency(data.summary.opening_balance);
            document.getElementById('preview_total_credit').textContent = formatCurrency(data.summary.total_credit);
            document.getElementById('preview_total_debit').textContent = formatCurrency(data.summary.total_debit);
            document.getElementById('preview_closing_balance').textContent = formatCurrency(data.summary.closing_balance);

            const keywords = extractKeywords(data.ocr_data.transactions);
            displayKeywords(keywords);

            const transactionsList = document.getElementById('previewTransactionsList');
            transactionsList.innerHTML = '';
            
            const transactions = data.ocr_data.transactions.slice(0, 10);
            transactions.forEach(transaction => {
                const card = createTransactionCard(transaction);
                transactionsList.innerHTML += card;
            });
        }

        function extractKeywords(transactions) {
            const keywordMap = new Map();
            
            transactions.forEach(transaction => {
                const desc = transaction.description.toUpperCase();
                
                const patterns = [
                    { regex: /INDOMARET|ALFAMART|SUPERINDO/i, category: 'Minimarket', color: 'blue' },
                    { regex: /KIMIA FARMA|GUARDIAN|APOTEK/i, category: 'Pharmacy', color: 'green' },
                    { regex: /GOPAY|OVO|DANA|SHOPEEPAY/i, category: 'E-Wallet', color: 'purple' },
                    { regex: /GRAB|GOJEK|UBER/i, category: 'Transportation', color: 'orange' },
                    { regex: /TOKOPEDIA|SHOPEE|LAZADA|BUKALAPAK/i, category: 'E-Commerce', color: 'pink' },
                    { regex: /RESTORAN|RESTAURANT|CAFE|WARUNG/i, category: 'Restaurant', color: 'red' },
                    { regex: /TRANSFER|TRF|OVERBOOKING/i, category: 'Transfer', color: 'indigo' },
                ];

                patterns.forEach(pattern => {
                    if (pattern.regex.test(desc)) {
                        const match = desc.match(pattern.regex)[0];
                        if (!keywordMap.has(match)) {
                            keywordMap.set(match, { keyword: match, category: pattern.category, color: pattern.color, count: 1 });
                        } else {
                            keywordMap.get(match).count++;
                        }
                    }
                });
            });

            return Array.from(keywordMap.values());
        }

        function displayKeywords(keywords) {
            const keywordsList = document.getElementById('keywordsList');
            document.getElementById('preview_keywords_count').textContent = keywords.length;

            if (keywords.length === 0) {
                keywordsList.innerHTML = `
                    <div class="col-span-full text-center py-8">
                        <i class="fas fa-tags text-gray-600 text-4xl mb-3"></i>
                        <p class="text-gray-400">No keywords detected</p>
                    </div>
                `;
                return;
            }

            keywordsList.innerHTML = '';
            keywords.forEach(kw => {
                keywordsList.innerHTML += `
                    <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700 hover:border-${kw.color}-500 transition">
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-3 py-1 bg-${kw.color}-600/20 text-${kw.color}-400 border border-${kw.color}-500/50 rounded-lg text-xs font-semibold">
                                <i class="fas fa-tag mr-1"></i>${kw.keyword}
                            </span>
                            <span class="px-2 py-1 bg-slate-800 text-gray-400 rounded text-xs">${kw.count}x</span>
                        </div>
                        <p class="text-xs text-gray-500"><i class="fas fa-folder mr-1"></i>${kw.category}</p>
                    </div>
                `;
            });
        }

        function createTransactionCard(transaction) {
            const isDebit = transaction.debit_amount > 0;
            const amount = isDebit ? transaction.debit_amount : transaction.credit_amount;
            
            return `
                <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700 hover:border-blue-500 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="px-2 py-1 bg-slate-800 rounded text-xs text-gray-400">${transaction.date}</span>
                                ${isDebit ? 
                                    '<span class="px-2 py-1 bg-red-600/20 text-red-400 rounded text-xs font-semibold"><i class="fas fa-arrow-down mr-1"></i>Debit</span>' :
                                    '<span class="px-2 py-1 bg-green-600/20 text-green-400 rounded text-xs font-semibold"><i class="fas fa-arrow-up mr-1"></i>Credit</span>'
                                }
                            </div>
                            <p class="text-white font-semibold mb-2">${truncate(transaction.description, 80)}</p>
                            <div class="flex items-center space-x-2 text-xs text-gray-500">
                                <span><i class="fas fa-wallet mr-1"></i>Balance: ${formatCurrency(transaction.balance)}</span>
                            </div>
                        </div>
                        <div class="ml-4 text-right">
                            <div class="text-2xl font-bold ${isDebit ? 'text-red-400' : 'text-green-400'}">
                                ${isDebit ? '-' : '+'}${formatCurrency(amount)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function showError(message, errorType = null) {
            document.getElementById('loadingSection').classList.add('hidden');
            document.getElementById('uploadSection').classList.remove('hidden');
            showAlert(message, 'error');
        }

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
            alertDiv.className = `${colors[type]} border-l-4 p-4 mb-4 rounded-r-lg`;
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icons[type]} text-2xl mr-3"></i>
                    <p class="font-medium">${message}</p>
                </div>
            `;
            
            const uploadSection = document.getElementById('uploadSection');
            uploadSection.insertBefore(alertDiv, uploadSection.firstChild);

            setTimeout(() => alertDiv.remove(), 10000);
        }

        document.getElementById('confirmSave').addEventListener('click', async function() {
            if (!previewData) return;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

            try {
                const response = await fetch('{{ route('bank-statements.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        _token: '{{ csrf_token() }}'
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success || result.redirect_url) {
                        window.location.href = result.redirect_url || '{{ route('bank-statements.index') }}';
                    } else {
                        throw new Error(result.message || 'Save failed');
                    }
                } else {
                    throw new Error('Server error');
                }

            } catch (error) {
                console.error('Save error:', error);
                showAlert('Failed to save: ' + error.message, 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Save to Database';
            }
        });

        document.getElementById('cancelSave').addEventListener('click', async function() {
            if (!previewData) return;

            if (confirm('Are you sure you want to cancel? All processed data will be lost.')) {
                try {
                    await fetch('{{ route('bank-statements.cancel') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ file_path: previewData.file_path })
                    });
                } catch (error) {
                    console.error('Error canceling:', error);
                }

                document.getElementById('uploadForm').reset();
                document.getElementById('fileInfo').classList.add('hidden');
                document.getElementById('previewSection').classList.add('hidden');
                document.getElementById('uploadSection').classList.remove('hidden');
                previewData = null;
            }
        });

        document.getElementById('closePreview').addEventListener('click', function() {
            document.getElementById('cancelSave').click();
        });

        function formatCurrency(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        function truncate(str, length) {
            return str.length > length ? str.substring(0, length) + '...' : str;
        }
    </script>
    @endpush
</x-app-layout>