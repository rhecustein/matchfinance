<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\Payment;
use App\Services\OcrService;
use App\Services\TransactionMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BankStatementController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private TransactionMatchingService $matchingService
    ) {}

    /**
     * Display a listing of bank statements
     */
    public function index(Request $request)
    {
        $query = BankStatement::with(['bank', 'user'])
            ->latest('uploaded_at');

        // Filter by bank
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('ocr_status', $request->status);
        }

        // Filter by period
        if ($request->filled('period_from')) {
            $query->where('period_from', '>=', $request->period_from);
        }

        if ($request->filled('period_to')) {
            $query->where('period_to', '<=', $request->period_to);
        }

        // Search by account number
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('account_number', 'like', "%{$request->search}%")
                  ->orWhere('original_filename', 'like', "%{$request->search}%");
            });
        }

        $statements = $query->paginate(15)->withQueryString();

        // Get banks for filter
        $banks = Bank::active()->orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => BankStatement::count(),
            'pending' => BankStatement::where('ocr_status', 'pending')->count(),
            'processing' => BankStatement::where('ocr_status', 'processing')->count(),
            'completed' => BankStatement::where('ocr_status', 'completed')->count(),
            'failed' => BankStatement::where('ocr_status', 'failed')->count(),
        ];

        return view('bank-statements.index', compact('statements', 'banks', 'stats'));
    }

    /**
     * Show the form for creating a new bank statement
     */
    public function create()
    {
        $banks = Bank::active()->orderBy('name')->get();
        
        return view('bank-statements.create', compact('banks'));
    }

    /**
     * Upload and preview bank statement
     * ✅ FIXED: Added mock mode + better error handling
     */
    public function uploadAndPreview(Request $request)
    {
        Log::info('=== UPLOAD START WITH DUPLICATE CHECK ===');

        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $bank = Bank::findOrFail($request->bank_id);
            $file = $request->file('file');

            // ✅ 1. CHECK FILE HASH DUPLICATE
            $fileHash = hash_file('sha256', $file->getRealPath());
            
            $existingByHash = BankStatement::where('file_hash', $fileHash)
                ->where('bank_id', $bank->id)
                ->first();
            
            if ($existingByHash) {
                return response()->json([
                    'success' => false,
                    'message' => 'File duplikat terdeteksi! File yang sama sudah pernah di-upload.',
                    'error_type' => 'duplicate_file',
                    'existing_statement' => [
                        'id' => $existingByHash->id,
                        'uploaded_at' => $existingByHash->created_at->format('d M Y H:i'),
                        'period' => $existingByHash->period_from . ' - ' . $existingByHash->period_to,
                    ]
                ], 422);
            }

            Log::info('Processing upload', [
                'bank' => $bank->name,
                'file' => $file->getClientOriginalName(),
                'hash' => $fileHash,
                'size' => $file->getSize(),
            ]);

            // ✅ Call OCR API (with mock mode support)
            $ocrResponse = $this->callOcrWithMockSupport($bank->code, $file);

            // Validate bank match
            $ocrBankName = strtoupper($ocrResponse['Bank'] ?? '');
            $selectedBankCode = strtoupper($bank->code);

            if ($ocrBankName !== $selectedBankCode) {
                return response()->json([
                    'success' => false,
                    'message' => "Bank tidak sesuai! OCR mendeteksi bank {$ocrBankName}, tapi Anda pilih {$selectedBankCode}.",
                    'error_type' => 'bank_mismatch',
                ], 422);
            }

            // Parse OCR data
            $parsedData = $this->parseOcrResponse($ocrResponse);

            // ✅ 2. CHECK PERIOD DUPLICATE
            $periodFrom = $parsedData['period_from'];
            $periodTo = $parsedData['period_to'];
            $accountNumber = $parsedData['account_number'];

            $existingByPeriod = BankStatement::where('bank_id', $bank->id)
                ->where('account_number', $accountNumber)
                ->where(function($query) use ($periodFrom, $periodTo) {
                    $query->whereBetween('period_from', [$periodFrom, $periodTo])
                        ->orWhereBetween('period_to', [$periodFrom, $periodTo])
                        ->orWhere(function($q) use ($periodFrom, $periodTo) {
                            $q->where('period_from', '<=', $periodFrom)
                                ->where('period_to', '>=', $periodTo);
                        });
                })
                ->first();

            if ($existingByPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Statement untuk periode ini sudah ada! Periode overlap terdeteksi.',
                    'error_type' => 'duplicate_period',
                    'existing_statement' => [
                        'id' => $existingByPeriod->id,
                        'period' => $existingByPeriod->period_from . ' - ' . $existingByPeriod->period_to,
                        'account' => $existingByPeriod->account_number,
                        'uploaded_at' => $existingByPeriod->created_at->format('d M Y H:i'),
                    ]
                ], 422);
            }

            // Save file permanently
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            
            $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nameWithoutExt);
            $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
            $sanitizedName = trim($sanitizedName, '_');
            
            $filename = time() . '_' . uniqid() . '_' . $sanitizedName . '.' . $extension;
            $filePath = $file->storeAs('bank-statements', $filename, 'private');
            
            Log::info('File saved permanently', [
                'path' => $filePath,
                'hash' => $fileHash,
            ]);

            // Store in session
            session()->put('pending_upload', [
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'original_filename' => $originalName,
                'file_size' => $file->getSize(),
                'bank_id' => $bank->id,
                'ocr_data' => $parsedData,
                'uploaded_at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OCR berhasil diproses. Tidak ada duplikasi terdeteksi.',
                'data' => [
                    'file_path' => $filePath,
                    'file_hash' => $fileHash,
                    'original_filename' => $originalName,
                    'file_size' => $file->getSize(),
                    'bank' => [
                        'id' => $bank->id,
                        'name' => $bank->name,
                        'code' => $bank->code
                    ],
                    'ocr_data' => $parsedData,
                    'summary' => [
                        'period' => $parsedData['period_from'] . ' s/d ' . $parsedData['period_to'],
                        'account_number' => $parsedData['account_number'],
                        'total_transactions' => count($parsedData['transactions']),
                        'opening_balance' => $parsedData['opening_balance'],
                        'closing_balance' => $parsedData['closing_balance'],
                        'total_credit' => $parsedData['total_credit_amount'],
                        'total_debit' => $parsedData['total_debit_amount'],
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('=== UPLOAD ERROR ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ✅ User-friendly error messages
            $userMessage = $this->getUserFriendlyErrorMessage($e);

            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'technical_error' => $e->getMessage(), // For debugging
            ], 500);
        }
    }

    /**
     * ✅ NEW: Call OCR with mock mode support
     */
    private function callOcrWithMockSupport(string $bankCode, $file): array
    {
        // Check if mock mode is enabled
        if (config('services.ocr.mock_mode', false)) {
            Log::warning('OCR Mock Mode is enabled - using sample data');
            return $this->getMockOcrResponse($bankCode);
        }

        // Call real OCR API
        return $this->callExternalOcrApi($bankCode, $file);
    }

    /**
     * ✅ NEW: Get mock OCR response for testing
     */
    private function getMockOcrResponse(string $bankCode): array
    {
        return [
            'Bank' => strtoupper($bankCode),
            'PeriodFrom' => '01/01/24',
            'PeriodTo' => '01/31/24',
            'AccountNo' => '1234567890 IDR TEST ACCOUNT',
            'Currency' => 'IDR',
            'Branch' => '00001',
            'OpeningBalance' => '10000000.00',
            'ClosingBalance' => '9500000.00',
            'CreditNo' => 5,
            'DebitNo' => 8,
            'TotalAmountCredited' => '2000000.00',
            'TotalAmountDebited' => '2500000.00',
            'TableData' => [
                [
                    'Date' => '01/05/24',
                    'Time' => '10:30:00',
                    'ValueDate' => '01/05/24',
                    'Branch' => '00001',
                    'Description' => 'APOTEK KIMIA FARMA - Pembelian obat',
                    'ReferenceNo' => 'TRX001',
                    'Debit' => '150000.00',
                    'Credit' => '0.00',
                    'Balance' => '9850000.00',
                ],
                [
                    'Date' => '01/10/24',
                    'Time' => '14:20:00',
                    'ValueDate' => '01/10/24',
                    'Branch' => '00001',
                    'Description' => 'Transfer masuk dari PT XYZ',
                    'ReferenceNo' => 'TRX002',
                    'Debit' => '0.00',
                    'Credit' => '1000000.00',
                    'Balance' => '10850000.00',
                ],
                [
                    'Date' => '01/15/24',
                    'Time' => '09:15:00',
                    'ValueDate' => '01/15/24',
                    'Branch' => '00001',
                    'Description' => 'LISTRIK PLN bulan Januari',
                    'ReferenceNo' => 'TRX003',
                    'Debit' => '500000.00',
                    'Credit' => '0.00',
                    'Balance' => '10350000.00',
                ],
            ],
        ];
    }

    /**
     * ✅ NEW: Get user-friendly error message
     */
    private function getUserFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Connection timeout
        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'Koneksi ke server OCR timeout. Silakan coba lagi atau hubungi administrator.';
        }

        // Connection refused
        if (str_contains($message, 'Connection refused') || str_contains($message, 'could not connect')) {
            return 'Tidak dapat terhubung ke server OCR. Silakan cek koneksi internet atau hubungi administrator.';
        }

        // Invalid response
        if (str_contains($message, 'Invalid OCR') || str_contains($message, 'missing')) {
            return 'Format response OCR tidak valid. Silakan hubungi administrator.';
        }

        // Default
        return 'Gagal memproses file: ' . $message;
    }

    /**
     * Store bank statement dengan hash tracking
     * ✅ FIXED: Better transaction validation
     */
    public function store(Request $request)
    {
        Log::info('=== STORE START ===');

        $pending = session('pending_upload');
        if (!$pending) {
            return back()->with('error', 'Session expired. Please upload again.');
        }

        DB::beginTransaction();
        try {
            // ✅ FINAL CHECK: Double-check duplikasi sebelum insert
            $fileHash = $pending['file_hash'];
            $ocrData = $pending['ocr_data'];
            
            $finalDuplicateCheck = BankStatement::where('file_hash', $fileHash)
                ->orWhere(function($query) use ($pending, $ocrData) {
                    $query->where('bank_id', $pending['bank_id'])
                        ->where('account_number', $ocrData['account_number'])
                        ->where('period_from', $ocrData['period_from'])
                        ->where('period_to', $ocrData['period_to']);
                })
                ->exists();

            if ($finalDuplicateCheck) {
                DB::rollBack();
                session()->forget('pending_upload');
                
                return back()->with('error', 'Duplikasi terdeteksi saat menyimpan! Data tidak disimpan.');
            }

            // Create bank statement
            $bankStatement = BankStatement::create([
                'bank_id' => $pending['bank_id'],
                'user_id' => auth()->id(),
                'file_path' => $pending['file_path'],
                'file_hash' => $fileHash,
                'original_filename' => $pending['original_filename'],
                'file_size' => $pending['file_size'],
                'period_from' => $ocrData['period_from'],
                'period_to' => $ocrData['period_to'],
                'account_number' => $ocrData['account_number'],
                'currency' => $ocrData['currency'] ?? 'IDR',
                'branch_code' => $ocrData['branch_code'],
                'opening_balance' => $ocrData['opening_balance'],
                'closing_balance' => $ocrData['closing_balance'],
                'total_credit_count' => $ocrData['total_credit_count'],
                'total_debit_count' => $ocrData['total_debit_count'],
                'total_credit_amount' => $ocrData['total_credit_amount'],
                'total_debit_amount' => $ocrData['total_debit_amount'],
                'ocr_status' => 'completed',
                'ocr_response' => $ocrData,
                'processed_at' => now(),
                'uploaded_at' => now(),
            ]);

            // ✅ Create transactions with validation
            $createdCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($ocrData['transactions'] as $index => $transaction) {
                try {
                    // ✅ Validate required fields
                    if (empty($transaction['date'])) {
                        $skippedCount++;
                        $errors[] = "Transaction #{$index}: Missing date";
                        Log::warning('Skipping transaction with missing date', [
                            'index' => $index,
                            'description' => $transaction['description'] ?? 'N/A',
                        ]);
                        continue;
                    }

                    // ✅ Validate description
                    if (empty($transaction['description'])) {
                        $transaction['description'] = 'No description';
                    }

                    // ✅ Create transaction
                    $bankStatement->transactions()->create([
                        'transaction_date' => $transaction['date'],
                        'transaction_time' => $transaction['time'],
                        'value_date' => $transaction['value_date'] ?? $transaction['date'], // Fallback to transaction date
                        'branch_code' => $transaction['branch'],
                        'description' => $transaction['description'],
                        'reference_no' => $transaction['reference_no'],
                        'debit_amount' => $transaction['debit_amount'] ?? 0,
                        'credit_amount' => $transaction['credit_amount'] ?? 0,
                        'balance' => $transaction['balance'] ?? 0,
                        'transaction_type' => $transaction['type'],
                    ]);

                    $createdCount++;

                } catch (\Exception $e) {
                    $skippedCount++;
                    $errors[] = "Transaction #{$index}: " . $e->getMessage();
                    
                    Log::error('Failed to create transaction', [
                        'index' => $index,
                        'transaction' => $transaction,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ✅ Check if we have at least some transactions
            if ($createdCount === 0) {
                DB::rollBack();
                
                Log::error('No transactions created', [
                    'statement_id' => $bankStatement->id,
                    'total_transactions' => count($ocrData['transactions']),
                    'skipped' => $skippedCount,
                    'errors' => $errors,
                ]);

                return back()->with('error', 'Gagal membuat transaksi. Tidak ada transaksi valid yang dapat disimpan.');
            }

            DB::commit();
            session()->forget('pending_upload');

            $message = "Bank statement berhasil disimpan! {$createdCount} transaksi dibuat";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} transaksi dilewati karena data tidak valid.";
            }

            Log::info('Bank statement saved successfully', [
                'id' => $bankStatement->id,
                'hash' => $fileHash,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);

            return redirect()
                ->route('bank-statements.show', $bankStatement)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
    /**
     * Cancel upload - DELETE FILE
     */
    public function cancelUpload(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        try {
            $filePath = $request->file_path;
            
            if (Storage::disk('private')->exists($filePath)) {
                Storage::disk('private')->delete($filePath);
                Log::info('Upload cancelled, file deleted', ['path' => $filePath]);
            }

            session()->forget('pending_upload');

            return response()->json([
                'success' => true,
                'message' => 'Upload dibatalkan.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel upload', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified bank statement
     */
    public function show(BankStatement $bankStatement)
    {
        $bankStatement->load(['bank', 'user', 'transactions.subCategory.category.type']);

        // Stats untuk view
        $stats = [
            'total' => $bankStatement->transactions()->count(),
            'matched' => $bankStatement->transactions()->whereNotNull('matched_keyword_id')->count(),
            'unmatched' => $bankStatement->transactions()->whereNull('matched_keyword_id')->count(),
            'verified' => $bankStatement->transactions()->where('is_verified', true)->count(),
            'low_confidence' => $bankStatement->transactions()->where('confidence_score', '<', 80)->whereNotNull('matched_keyword_id')->count(),
        ];

        // Matching stats untuk fitur baru
        $matchingStats = [
            'total_transactions' => $stats['total'],
            'matched_count' => $stats['matched'],
            'unmatched_count' => $stats['unmatched'],
            'manual_count' => $bankStatement->transactions()->where('is_manual_category', true)->count(),
            'match_percentage' => $stats['total'] > 0 ? ($stats['matched'] / $stats['total']) * 100 : 0,
        ];

        return view('bank-statements.show', compact('bankStatement', 'stats', 'matchingStats'));
    }

    /**
     * Show the form for editing the specified bank statement
     */
    public function edit(BankStatement $bankStatement)
    {
        $banks = Bank::active()->orderBy('name')->get();
        
        return view('bank-statements.edit', compact('bankStatement', 'banks'));
    }

    /**
     * Update the specified bank statement in storage
     */
    public function update(Request $request, BankStatement $bankStatement)
    {
        $request->validate([
            'account_number' => 'nullable|string|max:50',
            'branch_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        try {
            $bankStatement->update([
                'account_number' => $request->account_number,
                'branch_code' => $request->branch_code,
                'notes' => $request->notes,
            ]);

            return redirect()
                ->route('bank-statements.show', $bankStatement)
                ->with('success', 'Bank statement berhasil diperbarui');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified bank statement from storage
     */
    public function destroy(BankStatement $bankStatement)
    {
        DB::beginTransaction();

        try {
            // Delete file from storage
            if (Storage::disk('private')->exists($bankStatement->file_path)) {
                Storage::disk('private')->delete($bankStatement->file_path);
            }

            // Delete transactions first
            $bankStatement->transactions()->delete();

            // Delete bank statement
            $bankStatement->delete();

            Log::info('Bank statement deleted', [
                'statement_id' => $bankStatement->id,
                'deleted_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()
                ->route('bank-statements.index')
                ->with('success', 'Bank statement berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete bank statement', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    /**
     * Download the bank statement PDF file
     */
    public function download(BankStatement $bankStatement)
    {
        try {
            if (!Storage::disk('private')->exists($bankStatement->file_path)) {
                return back()->with('error', 'File tidak ditemukan');
            }

            return Storage::disk('private')->download(
                $bankStatement->file_path,
                $bankStatement->original_filename
            );

        } catch (\Exception $e) {
            Log::error('Failed to download bank statement', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal mengunduh file: ' . $e->getMessage());
        }
    }

    /**
     * Export bank statement transactions to Excel
     */
    public function export(BankStatement $bankStatement)
    {
        try {
            $transactions = $bankStatement->transactions()
                ->orderBy('transaction_date')
                ->orderBy('transaction_time')
                ->get();

            $filename = 'bank_statement_' . $bankStatement->id . '_' . date('YmdHis') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function() use ($transactions) {
                $file = fopen('php://output', 'w');
                
                // Header
                fputcsv($file, [
                    'Date',
                    'Time',
                    'Value Date',
                    'Description',
                    'Reference No',
                    'Debit',
                    'Credit',
                    'Balance',
                    'Type'
                ]);

                // Data
                foreach ($transactions as $transaction) {
                    fputcsv($file, [
                        $transaction->transaction_date,
                        $transaction->transaction_time,
                        $transaction->value_date,
                        $transaction->description,
                        $transaction->reference_no,
                        $transaction->debit_amount,
                        $transaction->credit_amount,
                        $transaction->balance,
                        $transaction->transaction_type,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to export bank statement', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal export: ' . $e->getMessage());
        }
    }

    /**
     * Reprocess OCR for a bank statement
     */
    public function reprocess(BankStatement $bankStatement)
    {
        DB::beginTransaction();

        try {
            // Update status to processing
            $bankStatement->update([
                'ocr_status' => 'processing',
                'ocr_error' => null,
            ]);

            // Check if file exists
            if (!Storage::disk('private')->exists($bankStatement->file_path)) {
                throw new \Exception('File tidak ditemukan');
            }

            // Get file path
            $filePath = Storage::disk('private')->path($bankStatement->file_path);

            // Call OCR API again
            $ocrResponse = $this->callExternalOcrApi(
                $bankStatement->bank->code,
                new \Illuminate\Http\File($filePath)
            );

            // Parse OCR data
            $parsedData = $this->parseOcrResponse($ocrResponse);

            // Delete old transactions
            $bankStatement->transactions()->delete();

            // Update bank statement
            $bankStatement->update([
                'ocr_status' => 'completed',
                'ocr_response' => $parsedData,
                'period_from' => $parsedData['period_from'],
                'period_to' => $parsedData['period_to'],
                'account_number' => $parsedData['account_number'],
                'currency' => $parsedData['currency'] ?? 'IDR',
                'branch_code' => $parsedData['branch_code'],
                'opening_balance' => $parsedData['opening_balance'],
                'closing_balance' => $parsedData['closing_balance'],
                'total_credit_count' => $parsedData['total_credit_count'],
                'total_debit_count' => $parsedData['total_debit_count'],
                'total_credit_amount' => $parsedData['total_credit_amount'],
                'total_debit_amount' => $parsedData['total_debit_amount'],
                'processed_at' => now(),
            ]);

            // Create new transactions
            foreach ($parsedData['transactions'] as $transaction) {
                $bankStatement->transactions()->create([
                    'transaction_date' => $transaction['date'],
                    'transaction_time' => $transaction['time'],
                    'value_date' => $transaction['value_date'],
                    'branch_code' => $transaction['branch'],
                    'description' => $transaction['description'],
                    'reference_no' => $transaction['reference_no'] === '-' ? null : $transaction['reference_no'],
                    'debit_amount' => $transaction['debit_amount'],
                    'credit_amount' => $transaction['credit_amount'],
                    'balance' => $transaction['balance'],
                    'transaction_type' => $transaction['type'],
                ]);
            }

            // Update statistics
            $bankStatement->updateMatchingStats();

            DB::commit();

            return redirect()
                ->route('bank-statements.show', $bankStatement)
                ->with('success', 'OCR berhasil diproses ulang');

        } catch (\Exception $e) {
            DB::rollBack();

            $bankStatement->update([
                'ocr_status' => 'failed',
                'ocr_error' => $e->getMessage(),
            ]);

            Log::error('Failed to reprocess OCR', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal memproses ulang: ' . $e->getMessage());
        }
    }

    /**
     * Match transactions with keywords
     */
    public function matchTransactions(BankStatement $bankStatement)
    {
        try {
            $this->matchingService->matchBankStatement($bankStatement);

            return redirect()
                ->route('bank-statements.show', $bankStatement)
                ->with('success', 'Proses matching transaksi berhasil');

        } catch (\Exception $e) {
            Log::error('Failed to match transactions', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal matching transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction detail (for modal/AJAX)
     */
    public function getTransaction(BankStatement $bankStatement, StatementTransaction $transaction)
    {
        try {
            if ($transaction->bank_statement_id !== $bankStatement->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan dalam bank statement ini'
                ], 404);
            }

            $transaction->load(['matchedKeyword.subCategory.category.type']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date,
                    'time' => $transaction->transaction_time,
                    'description' => $transaction->description,
                    'reference_no' => $transaction->reference_no,
                    'debit_amount' => $transaction->debit_amount,
                    'credit_amount' => $transaction->credit_amount,
                    'balance' => $transaction->balance,
                    'type' => $transaction->transaction_type,
                    'matched_keyword' => $transaction->matchedKeyword ? [
                        'id' => $transaction->matchedKeyword->id,
                        'keyword' => $transaction->matchedKeyword->keyword,
                        'sub_category' => $transaction->matchedKeyword->subCategory->name ?? null,
                    ] : null,
                    'confidence_score' => $transaction->confidence_score,
                    'is_verified' => $transaction->is_verified,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get transaction detail', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail transaksi'
            ], 500);
        }
    }

    /**
     * Process matching for all transactions in a bank statement
     */
    public function processMatching(BankStatement $bankStatement)
    {
        try {
            Log::info('=== PROCESS MATCHING START ===', [
                'statement_id' => $bankStatement->id,
            ]);

            $transactionsCount = $bankStatement->transactions()->count();
            
            if ($transactionsCount === 0) {
                return back()->with('warning', 'No transactions found to match.');
            }

            // Process matching using service
            $stats = $this->matchingService->processStatementTransactions($bankStatement->id);

            // Update bank statement statistics
            $bankStatement->updateMatchingStats();

            Log::info('=== PROCESS MATCHING SUCCESS ===', [
                'statement_id' => $bankStatement->id,
                'stats' => $stats,
            ]);

            $message = sprintf(
                'Matching completed! Matched: %d, Unmatched: %d, High Confidence: %d, Low Confidence: %d',
                $stats['matched'],
                $stats['unmatched'],
                $stats['high_confidence'],
                $stats['low_confidence']
            );

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('=== PROCESS MATCHING ERROR ===', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Matching failed: ' . $e->getMessage());
        }
    }

    /**
     * Rematch all transactions
     */
    public function rematchAll(BankStatement $bankStatement)
    {
        DB::beginTransaction();

        try {
            Log::info('=== REMATCH ALL START ===', [
                'statement_id' => $bankStatement->id,
            ]);

            // Reset all matches except verified ones
            $resetCount = $bankStatement->transactions()
                ->where('is_verified', false)
                ->update([
                    'matched_keyword_id' => null,
                    'sub_category_id' => null,
                    'category_id' => null,
                    'type_id' => null,
                    'confidence_score' => 0,
                ]);

            Log::info('Reset transactions', ['count' => $resetCount]);

            // Process matching
            $stats = $this->matchingService->processStatementTransactions($bankStatement->id);

            // Update statistics
            $bankStatement->updateMatchingStats();

            DB::commit();

            Log::info('=== REMATCH ALL SUCCESS ===', [
                'statement_id' => $bankStatement->id,
                'reset_count' => $resetCount,
                'stats' => $stats,
            ]);

            $message = sprintf(
                'Rematch completed! Reset: %d, Matched: %d, Unmatched: %d',
                $resetCount,
                $stats['matched'],
                $stats['unmatched']
            );

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== REMATCH ALL ERROR ===', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Rematch failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify all matched transactions
     */
    public function verifyAllMatched(BankStatement $bankStatement)
    {
        DB::beginTransaction();

        try {
            $updated = $bankStatement->transactions()
                ->where('is_verified', false)
                ->where(function($query) {
                    $query->whereNotNull('matched_keyword_id')
                          ->orWhere('is_manual_category', true);
                })
                ->update([
                    'is_verified' => true,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

            DB::commit();

            Log::info('All matched transactions verified', [
                'statement_id' => $bankStatement->id,
                'verified_count' => $updated,
            ]);

            if ($updated > 0) {
                return back()->with('success', "Successfully verified {$updated} matched transactions.");
            } else {
                return back()->with('info', 'No matched transactions to verify.');
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to verify all matched', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to verify: ' . $e->getMessage());
        }
    }

    /**
     * Verify single transaction
     */
    public function verifyTransaction(BankStatement $bankStatement, StatementTransaction $transaction)
    {
        try {
            if ($transaction->bank_statement_id !== $bankStatement->id) {
                return back()->with('error', 'Transaction not found in this statement.');
            }

            if (!$transaction->matched_keyword_id && !$transaction->is_manual_category) {
                return back()->with('error', 'Only matched or manually categorized transactions can be verified.');
            }

            $transaction->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            Log::info('Transaction verified', [
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Transaction verified successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to verify transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to verify: ' . $e->getMessage());
        }
    }

    /**
     * Call external OCR API with retry mechanism
     * ✅ FIXED: Better error handling + retry + timeout handling
     */
    private function callExternalOcrApi(string $bankCode, $file): array
    {
        $bankCode = strtolower($bankCode);
        $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bankCode}";
        
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        $lastException = null;

        Log::info('Calling OCR API', [
            'url' => $apiUrl,
            'bank_code' => $bankCode,
            'file_size' => $file->getSize(),
        ]);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("OCR API attempt {$attempt}/{$maxRetries}");

                $response = Http::timeout(180) // ✅ Increased timeout to 3 minutes
                    ->connectTimeout(30)
                    ->retry(2, 100) // ✅ Retry 2 times with 100ms delay for connection issues
                    ->withHeaders([
                        'Accept' => 'application/json',
                    ])
                    ->attach(
                        'file', 
                        file_get_contents($file->getRealPath()), 
                        $file->getClientOriginalName()
                    )
                    ->post($apiUrl);

                // ✅ Check if response is successful
                if (!$response->successful()) {
                    $statusCode = $response->status();
                    $responseBody = $response->body();
                    
                    Log::error('OCR API request failed', [
                        'attempt' => $attempt,
                        'status' => $statusCode,
                        'body' => $responseBody,
                        'headers' => $response->headers(),
                    ]);

                    // ✅ Don't retry for client errors (4xx)
                    if ($statusCode >= 400 && $statusCode < 500) {
                        throw new \Exception("OCR API Error ({$statusCode}): " . $responseBody);
                    }

                    // ✅ Retry for server errors (5xx)
                    if ($attempt < $maxRetries) {
                        Log::info("Retrying in {$retryDelay} seconds...");
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                        continue;
                    }

                    throw new \Exception("OCR API Error ({$statusCode}): " . $responseBody);
                }

                // ✅ Parse JSON response
                $data = $response->json();

                // ✅ Validate response structure
                if (!isset($data['status'])) {
                    throw new \Exception('Invalid OCR API response: missing status field');
                }

                if ($data['status'] !== 'OK') {
                    $message = $data['message'] ?? 'Unknown error';
                    throw new \Exception('OCR processing failed: ' . $message);
                }

                // ✅ Validate OCR data
                if (!isset($data['ocr']) || !is_array($data['ocr'])) {
                    throw new \Exception('Invalid OCR API response: missing or invalid ocr data');
                }

                Log::info('OCR API call successful', [
                    'attempt' => $attempt,
                    'response_size' => strlen(json_encode($data)),
                ]);

                return $data['ocr'];

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // ✅ Connection timeout or network error
                $lastException = $e;
                Log::error('OCR API connection failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'type' => 'connection_error',
                ]);

                if ($attempt < $maxRetries) {
                    Log::info("Connection failed, retrying in {$retryDelay} seconds...");
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }

            } catch (\Illuminate\Http\Client\RequestException $e) {
                // ✅ HTTP request exception
                $lastException = $e;
                Log::error('OCR API request exception', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'type' => 'request_exception',
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }

            } catch (\Exception $e) {
                // ✅ Other exceptions (don't retry)
                Log::error('OCR API processing error', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'type' => get_class($e),
                ]);

                throw $e;
            }
        }

        // ✅ All retries failed
        $errorMessage = $lastException 
            ? $lastException->getMessage() 
            : 'OCR API call failed after ' . $maxRetries . ' attempts';

        throw new \Exception('OCR API Error: ' . $errorMessage);
    }

    /**
     * Parse OCR response to standardized format
     * ✅ FIXED: Better date parsing with fallbacks
     */
    /**
     * Parse OCR response to standardized format with improved validation
     */
    private function parseOcrResponse(array $ocrData): array
    {
        $transactions = [];
        $skippedCount = 0;

        foreach ($ocrData['TableData'] as $index => $row) {
            // Validate required fields
            if (empty($row['Description'])) {
                Log::warning('Skipping transaction without description', ['row_index' => $index]);
                $skippedCount++;
                continue;
            }

            $transactionDate = $this->parseDate($row['Date'] ?? null);
            
            // Skip transactions without valid date
            if (empty($transactionDate)) {
                Log::warning('Skipping transaction without valid date', [
                    'row_index' => $index,
                    'date_value' => $row['Date'] ?? 'null',
                    'description' => substr($row['Description'], 0, 50)
                ]);
                $skippedCount++;
                continue;
            }

            $debitAmount = $this->parseAmount($row['Debit'] ?? '0.00');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0.00');

            $transactions[] = [
                'date' => $transactionDate,
                'time' => $row['Time'] ?? null,
                'value_date' => !empty($row['ValueDate']) ? $this->parseDate($row['ValueDate']) : null,
                'branch' => $row['Branch'] ?? null,
                'description' => $row['Description'],
                'reference_no' => $row['ReferenceNo'] ?? null,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'balance' => $this->parseAmount($row['Balance'] ?? '0.00'),
                'type' => $debitAmount > 0 ? 'debit' : 'credit',
            ];
        }

        if ($skippedCount > 0) {
            Log::info('OCR parsing summary', [
                'total_rows' => count($ocrData['TableData']),
                'parsed_transactions' => count($transactions),
                'skipped_transactions' => $skippedCount
            ]);
        }

        return [
            'period_from' => $this->parseDate($ocrData['PeriodFrom']),
            'period_to' => $this->parseDate($ocrData['PeriodTo']),
            'account_number' => $ocrData['AccountNo'] ?? null,
            'currency' => $ocrData['Currency'] ?? 'IDR',
            'branch_code' => $ocrData['Branch'] ?? null,
            'opening_balance' => $this->parseAmount($ocrData['OpeningBalance'] ?? '0.00'),
            'closing_balance' => $this->parseAmount($ocrData['ClosingBalance'] ?? '0.00'),
            'total_credit_count' => (int) ($ocrData['CreditNo'] ?? 0),
            'total_debit_count' => (int) ($ocrData['DebitNo'] ?? 0),
            'total_credit_amount' => $this->parseAmount($ocrData['TotalAmountCredited'] ?? '0.00'),
            'total_debit_amount' => $this->parseAmount($ocrData['TotalAmountDebited'] ?? '0.00'),
            'transactions' => $transactions,
        ];
    }
    /**
     * ✅ NEW: Parse transaction date with multiple fallbacks
     */
    private function parseTransactionDate($dateValue, $lastValidDate, $periodFrom, $periodTo): ?string
    {
        // Try to parse the provided date
        if (!empty($dateValue) && $dateValue !== null) {
            $parsed = $this->parseDate($dateValue);
            if ($parsed) {
                return $parsed;
            }
        }

        // ✅ Fallback 1: Use last valid transaction date
        if ($lastValidDate) {
            Log::debug('Using last valid date as fallback', [
                'fallback_date' => $lastValidDate,
            ]);
            return $lastValidDate;
        }

        // ✅ Fallback 2: Use period start date
        if ($periodFrom) {
            Log::debug('Using period start as fallback', [
                'fallback_date' => $periodFrom,
            ]);
            return $periodFrom;
        }

        // ✅ Fallback 3: Use period end date
        if ($periodTo) {
            Log::debug('Using period end as fallback', [
                'fallback_date' => $periodTo,
            ]);
            return $periodTo;
        }

        // ✅ All fallbacks failed
        Log::error('All date fallbacks failed', [
            'date_value' => $dateValue,
            'last_valid' => $lastValidDate,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
        ]);

        return null;
    }

    /**
     * Parse amount string to float
     * ✅ ENHANCED: Handle more formats
     */
    private function parseAmount(string $amount): float
    {
        if (empty($amount) || $amount === '-') {
            return 0.0;
        }

        // Remove any non-numeric characters except dot, comma, and minus
        $cleaned = preg_replace('/[^0-9.,\-]/', '', $amount);
        
        // Handle different decimal separators
        // Check if comma is used as decimal separator (European style)
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Both comma and dot present
            // Determine which is thousand separator
            $commaPos = strrpos($cleaned, ',');
            $dotPos = strrpos($cleaned, '.');
            
            if ($dotPos > $commaPos) {
                // Dot is decimal separator (US style: 1,234.56)
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                // Comma is decimal separator (EU style: 1.234,56)
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            }
        } elseif (strpos($cleaned, ',') !== false) {
            // Only comma present
            // Check if it's thousand separator or decimal
            $parts = explode(',', $cleaned);
            if (count($parts) == 2 && strlen($parts[1]) <= 2) {
                // Likely decimal separator
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // Likely thousand separator
                $cleaned = str_replace(',', '', $cleaned);
            }
        }
        
        return (float) $cleaned;
    }

    /**
     * Parse date string to Y-m-d format
     */
   private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        // Skip if it's only time format (contains only numbers and colon)
        if (preg_match('/^\d{1,2}:/', $date)) {
            Log::warning('Skipping time-only value as date', [
                'value' => $date
            ]);
            return null;
        }

        try {
            // Clean the date string
            $date = trim($date);
            
            // Handle different date formats
            // Format 1: "01/31/24" (MM/DD/YY)
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $date, $matches)) {
                $month = $matches[1];
                $day = $matches[2];
                $year = '20' . $matches[3];
                
                return sprintf('%s-%s-%s', $year, $month, $day);
            }
            
            // Format 2: "01/31/2024" (MM/DD/YYYY)
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
                $month = $matches[1];
                $day = $matches[2];
                $year = $matches[3];
                
                return sprintf('%s-%s-%s', $year, $month, $day);
            }
            
            // Format 3: "31-Jan-2024" or "01-JAN-24" (DD-Mon-YYYY or DD-MON-YY)
            if (preg_match('/^(\d{2})-([A-Za-z]{3})-(\d{2,4})$/', $date, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
                
                return \Carbon\Carbon::createFromFormat('d-M-Y', "$day-$month-$year")->format('Y-m-d');
            }
            
            // Format 4: "2024-01-31" (Already Y-m-d)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }
            
            // Format 5: "31 Jan 2024" (DD Mon YYYY)
            if (preg_match('/^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{4})$/', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = $matches[2];
                $year = $matches[3];
                
                return \Carbon\Carbon::createFromFormat('d M Y', "$day $month $year")->format('Y-m-d');
            }
            
            // Last resort: try Carbon parse
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
            
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics(Request $request)
    {
        try {
            $query = BankStatement::query();

            if ($request->filled('start_date')) {
                $query->where('period_from', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('period_to', '<=', $request->end_date);
            }

            if ($request->filled('bank_id')) {
                $query->where('bank_id', $request->bank_id);
            }

            $stats = [
                'total_statements' => $query->count(),
                'total_transactions' => StatementTransaction::whereIn(
                    'bank_statement_id',
                    $query->pluck('id')
                )->count(),
                'total_credit' => $query->sum('total_credit_amount'),
                'total_debit' => $query->sum('total_debit_amount'),
                'matched_percentage' => $query->avg('match_percentage') ?? 0,
                'by_status' => BankStatement::groupBy('ocr_status')
                    ->selectRaw('ocr_status, count(*) as count')
                    ->get()
                    ->pluck('count', 'ocr_status'),
                'by_bank' => BankStatement::with('bank')
                    ->groupBy('bank_id')
                    ->selectRaw('bank_id, count(*) as count')
                    ->get()
                    ->map(function($item) {
                        return [
                            'bank' => $item->bank->name,
                            'count' => $item->count
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }
}