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

            $userMessage = $this->getUserFriendlyErrorMessage($e);

            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'technical_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Call OCR with mock mode support
     */
    private function callOcrWithMockSupport(string $bankCode, $file): array
    {
        if (config('services.ocr.mock_mode', false)) {
            Log::warning('OCR Mock Mode is enabled - using sample data');
            return $this->getMockOcrResponse($bankCode);
        }

        return $this->callExternalOcrApi($bankCode, $file);
    }

    /**
     * Get mock OCR response for testing
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
     * Get user-friendly error message
     */
    /**
     * Enhanced getUserFriendlyErrorMessage
     */
    private function getUserFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Timeout errors
        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return "Koneksi ke server OCR timeout.\n\nSolusi:\n- Coba upload file yang lebih kecil\n- Periksa koneksi internet Anda\n- Coba lagi dalam beberapa saat";
        }

        // Connection errors
        if (str_contains($message, 'Connection refused') || str_contains($message, 'could not connect')) {
            return "Tidak dapat terhubung ke server OCR.\n\nSolusi:\n- Periksa koneksi internet\n- Server OCR mungkin sedang maintenance\n- Hubungi administrator jika masalah berlanjut";
        }

        // OCR specific errors (already detailed)
        if (str_contains($message, 'OCR') || 
            str_contains($message, 'period') || 
            str_contains($message, 'account') ||
            str_contains($message, 'transaction')) {
            return $message; // Already detailed from getDetailedOcrError
        }

        // Generic error
        return "Gagal memproses file: " . $message;
    }

    /**
     * ✅ FIXED: Store bank statement - COMPLETE WITHOUT SKIP
     */
    public function store(Request $request)
    {
        Log::info('=== STORE START ===', [
            'user_id' => auth()->id(),
            'has_session' => session()->has('pending_upload'),
        ]);

        // ✅ 1. Validate session exists
        $pending = session('pending_upload');
        if (!$pending) {
            Log::error('Store failed: No pending upload in session');
            return redirect()
                ->route('bank-statements.create')
                ->with('error', 'Session expired. Please upload the file again.');
        }

        Log::info('Pending upload data retrieved', [
            'file_path' => $pending['file_path'] ?? 'N/A',
            'bank_id' => $pending['bank_id'] ?? 'N/A',
        ]);

        // ✅ 2. Start database transaction
        DB::beginTransaction();
        
        try {
            $fileHash = $pending['file_hash'];
            $ocrData = $pending['ocr_data'];
            
            // ✅ 3. Final duplicate check before insert
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
                
                Log::warning('Store blocked: Duplicate detected during final check', [
                    'hash' => $fileHash,
                    'account' => $ocrData['account_number'],
                ]);
                
                return redirect()
                    ->route('bank-statements.create')
                    ->with('error', 'Duplikasi terdeteksi saat menyimpan! Data tidak disimpan.');
            }

            // ✅ 4. Create bank statement record
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

            Log::info('Bank statement created', [
                'id' => $bankStatement->id,
                'account' => $bankStatement->account_number,
            ]);

            // ✅ 5. Create transactions with proper validation
            $createdCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($ocrData['transactions'] as $index => $transaction) {
                try {
                    // Validate required fields
                    if (empty($transaction['date'])) {
                        $skippedCount++;
                        $errors[] = "Transaction #{$index}: Missing date";
                        Log::warning('Skipping transaction: missing date', [
                            'index' => $index,
                            'description' => $transaction['description'] ?? 'N/A',
                        ]);
                        continue;
                    }

                    // Provide default description if empty
                    if (empty($transaction['description'])) {
                        $transaction['description'] = 'No description';
                    }

                    // Calculate amounts
                    $debitAmount = (float)($transaction['debit_amount'] ?? 0);
                    $creditAmount = (float)($transaction['credit_amount'] ?? 0);
                    $transactionType = $transaction['type'] ?? ($debitAmount > 0 ? 'debit' : 'credit');
                    
                    // ✅ Set amount = debit_amount for debit, credit_amount for credit
                    $amount = $transactionType === 'debit' ? $debitAmount : $creditAmount;

                    // ✅ Create transaction WITH amount field
                    $createdTransaction = $bankStatement->transactions()->create([
                        'transaction_date' => $transaction['date'],
                        'transaction_time' => $transaction['time'] ?? null,
                        'value_date' => $transaction['value_date'] ?? $transaction['date'],
                        'branch_code' => $transaction['branch'] ?? null,
                        'description' => $transaction['description'],
                        'reference_no' => $transaction['reference_no'] ?? null,
                        'debit_amount' => $debitAmount,
                        'credit_amount' => $creditAmount,
                        'balance' => (float)($transaction['balance'] ?? 0),
                        'transaction_type' => $transactionType,
                        'amount' => $amount, // ✅ CRITICAL: Amount field for matching
                    ]);

                    $createdCount++;
                    
                    Log::debug('Transaction created', [
                        'id' => $createdTransaction->id,
                        'date' => $createdTransaction->transaction_date,
                        'amount' => $amount,
                        'type' => $transactionType,
                    ]);

                } catch (\Exception $e) {
                    $skippedCount++;
                    $errors[] = "Transaction #{$index}: " . $e->getMessage();
                    
                    Log::error('Failed to create transaction', [
                        'index' => $index,
                        'transaction' => $transaction,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // ✅ 6. Validate at least one transaction created
            if ($createdCount === 0) {
                DB::rollBack();
                
                Log::error('Store failed: No valid transactions', [
                    'statement_id' => $bankStatement->id,
                    'total_transactions' => count($ocrData['transactions']),
                    'skipped' => $skippedCount,
                    'errors' => $errors,
                ]);

                // Delete uploaded file
                if (Storage::disk('private')->exists($pending['file_path'])) {
                    Storage::disk('private')->delete($pending['file_path']);
                }

                session()->forget('pending_upload');

                return redirect()
                    ->route('bank-statements.create')
                    ->with('error', 'Gagal membuat transaksi. Tidak ada transaksi valid yang dapat disimpan. Error: ' . implode(', ', array_slice($errors, 0, 3)));
            }

            // ✅ 7. Commit transaction
            DB::commit();
            
            // ✅ 8. Clear session AFTER successful commit
            session()->forget('pending_upload');

            // ✅ 9. Prepare success message
            $message = "Bank statement berhasil disimpan! {$createdCount} transaksi dibuat";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} transaksi dilewati karena data tidak valid.";
            }

            Log::info('=== STORE SUCCESS ===', [
                'id' => $bankStatement->id,
                'hash' => $fileHash,
                'created_transactions' => $createdCount,
                'skipped_transactions' => $skippedCount,
                'redirect_to' => route('bank-statements.show', $bankStatement),
            ]);

            // ✅ 10. Redirect to show page with success message
            return redirect()
                ->route('bank-statements.show', $bankStatement->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            // ✅ 11. Rollback on any error
            DB::rollBack();
            
            Log::error('=== STORE ERROR ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Keep session for retry
            return redirect()
                ->route('bank-statements.create')
                ->with('error', 'Gagal menyimpan bank statement: ' . $e->getMessage())
                ->withInput();
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
        // ✅ Load all necessary relationships
        $bankStatement->load([
            'bank', 
            'user', 
            'transactions' => function($query) {
                $query->orderBy('transaction_date', 'asc')
                      ->orderBy('transaction_time', 'asc');
            },
            'transactions.subCategory.category.type',
            'transactions.matchedKeyword'
        ]);

        // ✅ Calculate statistics
        $stats = [
            'total' => $bankStatement->transactions()->count(),
            'matched' => $bankStatement->transactions()->whereNotNull('matched_keyword_id')->count(),
            'unmatched' => $bankStatement->transactions()->whereNull('matched_keyword_id')->count(),
            'verified' => $bankStatement->transactions()->where('is_verified', true)->count(),
            'low_confidence' => $bankStatement->transactions()
                ->where('confidence_score', '<', 80)
                ->whereNotNull('matched_keyword_id')
                ->count(),
        ];

        $matchingStats = [
            'total_transactions' => $stats['total'],
            'matched_count' => $stats['matched'],
            'unmatched_count' => $stats['unmatched'],
            'manual_count' => $bankStatement->transactions()->where('is_manual_category', true)->count(),
            'match_percentage' => $stats['total'] > 0 ? round(($stats['matched'] / $stats['total']) * 100, 2) : 0,
        ];

        Log::info('Bank statement shown', [
            'id' => $bankStatement->id,
            'transactions_count' => $stats['total'],
        ]);

        return view('bank-statements.show', compact('bankStatement', 'stats', 'matchingStats'));
    }

    /**
     * Parse OCR response with improved validation
     */
    private function parseOcrResponse(array $ocrData): array
    {
        $transactions = [];
        $skippedCount = 0;

        foreach ($ocrData['TableData'] as $index => $row) {
            if (empty($row['Description'])) {
                Log::warning('Skipping transaction without description', ['row_index' => $index]);
                $skippedCount++;
                continue;
            }

            $transactionDate = $this->parseDate($row['Date'] ?? null);
            
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
     * Parse amount string to float
     */
    private function parseAmount(string $amount): float
    {
        if (empty($amount) || $amount === '-') {
            return 0.0;
        }

        $cleaned = preg_replace('/[^0-9.,\-]/', '', $amount);
        
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            $commaPos = strrpos($cleaned, ',');
            $dotPos = strrpos($cleaned, '.');
            
            if ($dotPos > $commaPos) {
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            }
        } elseif (strpos($cleaned, ',') !== false) {
            $parts = explode(',', $cleaned);
            if (count($parts) == 2 && strlen($parts[1]) <= 2) {
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
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

        if (preg_match('/^\d{1,2}:/', $date)) {
            Log::warning('Skipping time-only value as date', ['value' => $date]);
            return null;
        }

        try {
            $date = trim($date);
            
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
            
            // Format 3: "31-Jan-2024" or "01-JAN-24"
            if (preg_match('/^(\d{2})-([A-Za-z]{3})-(\d{2,4})$/', $date, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
                
                return Carbon::createFromFormat('d-M-Y', "$day-$month-$year")->format('Y-m-d');
            }
            
            // Format 4: "2024-01-31"
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date;
            }
            
            // Format 5: "31 Jan 2024"
            if (preg_match('/^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{4})$/', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = $matches[2];
                $year = $matches[3];
                
                return Carbon::createFromFormat('d M Y', "$day $month $year")->format('Y-m-d');
            }
            
            // Last resort: try Carbon parse
            return Carbon::parse($date)->format('Y-m-d');
            
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
/**
     * Call external OCR API with enhanced error handling
     */
    private function callExternalOcrApi(string $bankCode, $file): array
    {
        $bankCode = strtolower($bankCode);
        $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bankCode}";
        
        $maxRetries = 3;
        $retryDelay = 2;
        $lastException = null;

        Log::info('Calling OCR API', [
            'url' => $apiUrl,
            'bank_code' => $bankCode,
            'file_size' => $file->getSize(),
            'file_name' => $file->getClientOriginalName(),
        ]);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("OCR API attempt {$attempt}/{$maxRetries}");

                $response = Http::timeout(180)
                    ->connectTimeout(30)
                    ->retry(2, 100)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                    ->post($apiUrl);

                $statusCode = $response->status();
                $responseBody = $response->body();

                // Log response for debugging
                Log::info('OCR API response received', [
                    'attempt' => $attempt,
                    'status' => $statusCode,
                    'body_preview' => substr($responseBody, 0, 500),
                ]);

                if (!$response->successful()) {
                    Log::error('OCR API request failed', [
                        'attempt' => $attempt,
                        'status' => $statusCode,
                        'body' => $responseBody,
                    ]);

                    // Parse error message
                    $errorMessage = $this->parseOcrErrorMessage($responseBody, $statusCode);

                    if ($statusCode >= 400 && $statusCode < 500) {
                        throw new \Exception($errorMessage);
                    }

                    if ($attempt < $maxRetries) {
                        Log::info("Retrying in {$retryDelay} seconds...");
                        sleep($retryDelay);
                        $retryDelay *= 2;
                        continue;
                    }

                    throw new \Exception($errorMessage);
                }

                $data = $response->json();

                // Check status field
                if (!isset($data['status'])) {
                    throw new \Exception('Invalid OCR API response: missing status field');
                }

                // Handle failed status
                if ($data['status'] !== 'OK') {
                    $message = $data['status'] ?? $data['message'] ?? 'Unknown error';
                    
                    // Check if partial OCR data exists
                    if (isset($data['ocr']) && is_array($data['ocr'])) {
                        Log::warning('OCR returned partial data', [
                            'status' => $data['status'],
                            'ocr_keys' => array_keys($data['ocr']),
                        ]);
                        
                        // Try to use partial data if usable
                        if ($this->isOcrDataUsable($data['ocr'])) {
                            Log::info('Using partial OCR data despite error status');
                            return $data['ocr'];
                        }
                    }
                    
                    throw new \Exception($this->getDetailedOcrError($message));
                }

                // Validate OCR data structure
                if (!isset($data['ocr']) || !is_array($data['ocr'])) {
                    throw new \Exception('Invalid OCR API response: missing or invalid ocr data');
                }

                // Validate required fields
                $validationError = $this->validateOcrData($data['ocr']);
                if ($validationError) {
                    throw new \Exception($validationError);
                }

                Log::info('OCR API call successful', [
                    'attempt' => $attempt,
                    'transactions_count' => isset($data['ocr']['TableData']) ? count($data['ocr']['TableData']) : 0,
                ]);

                return $data['ocr'];

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                Log::error('OCR API connection failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
            } catch (\Exception $e) {
                Log::error('OCR API processing error', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        $errorMessage = $lastException 
            ? $lastException->getMessage() 
            : 'OCR API call failed after ' . $maxRetries . ' attempts';

        throw new \Exception('OCR API Error: ' . $errorMessage);
    }

    /**
     * Parse OCR error message from response body
     */
    private function parseOcrErrorMessage(string $responseBody, int $statusCode): string
    {
        try {
            $data = json_decode($responseBody, true);
            
            if (isset($data['status']) && $data['status'] !== 'OK') {
                return "OCR Error: " . $data['status'];
            }
            
            if (isset($data['message'])) {
                return "OCR Error: " . $data['message'];
            }
        } catch (\Exception $e) {
            // If JSON parse fails, return raw preview
        }

        return "OCR API Error ({$statusCode}): " . substr($responseBody, 0, 200);
    }

    /**
     * Get detailed user-friendly OCR error message
     */
    private function getDetailedOcrError(string $message): string
    {
        $detailedMessage = 'Gagal memproses file rekening koran.\n\n';

        // Specific error guidance
        if (str_contains($message, 'PeriodFrom') || str_contains($message, 'period from not found')) {
            $detailedMessage .= "Error: Informasi periode tidak dapat dibaca dari PDF.\n\n";
            $detailedMessage .= "Kemungkinan Penyebab:\n";
            $detailedMessage .= "- Format PDF tidak sesuai dengan bank yang dipilih\n";
            $detailedMessage .= "- PDF bukan rekening koran asli dari bank\n";
            $detailedMessage .= "- PDF rusak, ter-password, atau tidak dapat dibaca\n\n";
            $detailedMessage .= "Solusi:\n";
            $detailedMessage .= "- Pastikan memilih bank yang SESUAI dengan rekening koran\n";
            $detailedMessage .= "- Download ulang PDF dari internet banking resmi\n";
            $detailedMessage .= "- Pastikan PDF tidak ter-password atau terenkripsi";
        } 
        elseif (str_contains($message, 'PeriodTo') || str_contains($message, 'period to not found')) {
            $detailedMessage .= "Error: Informasi akhir periode tidak dapat dibaca.\n\n";
            $detailedMessage .= "Pastikan PDF adalah rekening koran lengkap dengan informasi periode yang jelas.";
        }
        elseif (str_contains($message, 'AccountNo') || str_contains($message, 'account')) {
            $detailedMessage .= "Error: Nomor rekening tidak dapat dibaca dari PDF.\n\n";
            $detailedMessage .= "Pastikan PDF memiliki informasi nomor rekening yang jelas dan tidak tertutup.";
        } 
        elseif (str_contains($message, 'TableData') || str_contains($message, 'transaction')) {
            $detailedMessage .= "Error: Tidak ada transaksi yang dapat dibaca.\n\n";
            $detailedMessage .= "Pastikan PDF memiliki data transaksi yang lengkap dan tidak kosong.";
        }
        elseif (str_contains($message, 'Bank') || str_contains($message, 'bank not found')) {
            $detailedMessage .= "Error: Informasi bank tidak dapat dideteksi.\n\n";
            $detailedMessage .= "Pastikan PDF adalah rekening koran resmi dari bank yang dipilih.";
        }
        else {
            $detailedMessage .= "Error: " . $message . "\n\n";
            $detailedMessage .= "Silakan periksa format PDF atau hubungi administrator.";
        }

        return $detailedMessage;
    }

    /**
     * Check if OCR data is minimally usable
     */
    private function isOcrDataUsable(array $ocrData): bool
    {
        // Minimal requirements
        if (!isset($ocrData['TableData']) || !is_array($ocrData['TableData'])) {
            return false;
        }

        if (count($ocrData['TableData']) === 0) {
            return false;
        }

        // Bank must be set
        if (empty($ocrData['Bank'])) {
            return false;
        }

        return true;
    }

    /**
     * Validate OCR data has required fields
     */
    private function validateOcrData(array $ocrData): ?string
    {
        // Check Bank
        if (empty($ocrData['Bank'])) {
            return "OCR tidak dapat mendeteksi informasi bank dari PDF. Pastikan PDF adalah rekening koran asli.";
        }

        // Check TableData
        if (!isset($ocrData['TableData']) || !is_array($ocrData['TableData'])) {
            return "Format data transaksi tidak valid. Pastikan PDF dapat dibaca dengan jelas.";
        }

        if (count($ocrData['TableData']) === 0) {
            return "Tidak ada transaksi yang dapat dibaca dari PDF. Pastikan PDF memiliki data transaksi.";
        }

        // Check at least first transaction has required fields
        $firstTransaction = $ocrData['TableData'][0];
        if (!isset($firstTransaction['Description']) && !isset($firstTransaction['Date'])) {
            return "Format transaksi tidak dapat dibaca. Pastikan PDF memiliki struktur yang jelas.";
        }

        return null; // No error
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