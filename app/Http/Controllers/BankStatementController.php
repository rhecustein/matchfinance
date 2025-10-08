<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\BankStatementTransaction;
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
     * Upload and process OCR - SAVE FILE IMMEDIATELY
     */
    public function uploadAndPreview(Request $request)
    {
        Log::info('=== UPLOAD START ===');

        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $bank = Bank::findOrFail($request->bank_id);
            $file = $request->file('file');

            Log::info('Processing upload', [
                'bank' => $bank->name,
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            // Call OCR API first
            $ocrResponse = $this->callExternalOcrApi($bank->code, $file);

            // Validate bank match
            $ocrBankName = strtoupper($ocrResponse['Bank'] ?? '');
            $selectedBankCode = strtoupper($bank->code);

            if ($ocrBankName !== $selectedBankCode) {
                return response()->json([
                    'success' => false,
                    'message' => "Bank tidak sesuai!",
                    'error_type' => 'bank_mismatch',
                ], 422);
            }

            // Parse OCR data
            $parsedData = $this->parseOcrResponse($ocrResponse);

            // NOW SAVE FILE PERMANENTLY (skip temp)
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            
            // Sanitize filename
            $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nameWithoutExt);
            $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
            $sanitizedName = trim($sanitizedName, '_');
            
            $filename = time() . '_' . uniqid() . '_' . $sanitizedName . '.' . $extension;
            
            // Save to PERMANENT location directly
            $permanentPath = 'bank-statements/' . $filename;
            $filePath = $file->storeAs('bank-statements', $filename, 'private');
            
            Log::info('File saved permanently', [
                'path' => $filePath,
                'exists' => Storage::disk('private')->exists($filePath),
            ]);

            // Store in session for reference
            session()->put('pending_upload', [
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'file_size' => $file->getSize(),
                'bank_id' => $bank->id,
                'ocr_data' => $parsedData,
                'uploaded_at' => now()->toIso8601String(),
            ]);

            Log::info('=== UPLOAD SUCCESS ===');

            return response()->json([
                'success' => true,
                'message' => 'OCR berhasil diproses.',
                'data' => [
                    'file_path' => $filePath, // Send permanent path
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
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses: ' . $e->getMessage(),
                'error_type' => 'ocr_failed'
            ], 500);
        }
    }

    /**
     * Save to database - FILE ALREADY SAVED
     */
    public function store(Request $request)
    {
        Log::info('=== STORE START ===');

        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'file_path' => 'required|string', // Changed from temp_file_path
            'original_filename' => 'required|string',
            'file_size' => 'required|integer',
            'ocr_data' => 'required|json',
        ]);

        DB::beginTransaction();

        try {
            $ocrData = json_decode($request->ocr_data, true);
            $filePath = $request->file_path;
            
            // Verify file exists
            if (!Storage::disk('private')->exists($filePath)) {
                Log::error('File not found', ['path' => $filePath]);
                throw new \Exception('File tidak ditemukan');
            }

            Log::info('Creating bank statement', [
                'file_path' => $filePath,
                'bank_id' => $request->bank_id,
            ]);

            // Create bank statement
            $statement = BankStatement::create([
                'bank_id' => $request->bank_id,
                'user_id' => auth()->id(),
                'file_path' => $filePath,
                'original_filename' => $request->original_filename,
                'file_size' => $request->file_size,
                'ocr_status' => 'completed',
                'ocr_response' => $ocrData,
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
                'uploaded_at' => now(),
                'processed_at' => now(),
            ]);

            Log::info('Bank statement created', ['id' => $statement->id]);

            // Create transactions
            $created = 0;
            $skipped = 0;
            
            foreach ($ocrData['transactions'] as $index => $transaction) {
                // Validate required fields
                if (empty($transaction['date'])) {
                    Log::warning('Skipping transaction with missing date', [
                        'index' => $index,
                        'transaction' => $transaction
                    ]);
                    $skipped++;
                    continue;
                }

                // Parse date
                $transactionDate = $this->parseDate($transaction['date']);
                
                if (!$transactionDate) {
                    Log::warning('Skipping transaction with invalid date', [
                        'index' => $index,
                        'date' => $transaction['date']
                    ]);
                    $skipped++;
                    continue;
                }

                try {
                    $statement->transactions()->create([
                        'transaction_date' => $transactionDate,
                        'transaction_time' => $transaction['time'] ?? null,
                        'value_date' => !empty($transaction['value_date']) ? $this->parseDate($transaction['value_date']) : null,
                        'branch_code' => $transaction['branch'] ?? null,
                        'description' => $transaction['description'] ?? '',
                        'reference_no' => (!empty($transaction['reference_no']) && $transaction['reference_no'] !== '-') ? $transaction['reference_no'] : null,
                        'debit_amount' => $transaction['debit_amount'] ?? 0,
                        'credit_amount' => $transaction['credit_amount'] ?? 0,
                        'balance' => $transaction['balance'] ?? 0,
                        'transaction_type' => $transaction['type'] ?? 'debit',
                    ]);
                    $created++;
                } catch (\Exception $e) {
                    Log::error('Failed to create transaction', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'transaction' => $transaction
                    ]);
                    $skipped++;
                }
            }

            Log::info('Transactions processed', [
                'created' => $created,
                'skipped' => $skipped,
                'total' => count($ocrData['transactions'])
            ]);

            // Update statistics
            $statement->updateMatchingStats();

            // Clear session
            session()->forget('pending_upload');

            DB::commit();

            Log::info('=== STORE SUCCESS ===', ['statement_id' => $statement->id]);

            return response()->json([
                'success' => true,
                'message' => 'Bank statement berhasil disimpan!',
                'data' => [
                    'id' => $statement->id,
                    'redirect_url' => route('bank-statements.show', $statement)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== STORE ERROR ===', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            // DON'T delete file on error - keep it for retry
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel upload - DELETE FILE
     */
    public function cancelUpload(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string', // Changed from temp_file_path
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
/**
 * Display the specified bank statement
 */
public function show(BankStatement $bankStatement)
{
    $bankStatement->load(['bank', 'user', 'transactions.subCategory.category.type']);

    // Stats untuk view lama (yang menggunakan $stats)
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

            // Delete transactions first (cascade might not work in some cases)
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
     * Match transactions with sales data
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
    public function getTransaction(BankStatement $bankStatement, BankStatementTransaction $transaction)
    {
        try {
            // Validate transaction belongs to this bank statement
            if ($transaction->bank_statement_id !== $bankStatement->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan dalam bank statement ini'
                ], 404);
            }

            $transaction->load(['matchedPayment.order.customer']);

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
                    'is_matched' => $transaction->is_matched,
                    'is_verified' => $transaction->is_verified,
                    'match_confidence' => $transaction->match_confidence,
                    'match_type' => $transaction->match_type,
                    'matched_at' => $transaction->matched_at?->format('Y-m-d H:i:s'),
                    'verified_at' => $transaction->verified_at?->format('Y-m-d H:i:s'),
                    'matched_payment' => $transaction->matchedPayment ? [
                        'id' => $transaction->matchedPayment->id,
                        'order_id' => $transaction->matchedPayment->order_id,
                        'amount' => $transaction->matchedPayment->amount,
                        'payment_method' => $transaction->matchedPayment->payment_method,
                        'payment_date' => $transaction->matchedPayment->payment_date,
                        'customer_name' => $transaction->matchedPayment->order->customer->name ?? null,
                    ] : null,
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
     * Get possible matches for manual matching
     */
    public function getPossibleMatches(BankStatement $bankStatement, BankStatementTransaction $transaction)
    {
        try {
            // Validate transaction belongs to this bank statement
            if ($transaction->bank_statement_id !== $bankStatement->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            $possibleMatches = $this->matchingService->findPossibleMatches($transaction);

            return response()->json([
                'success' => true,
                'data' => $possibleMatches
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to find possible matches', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari kemungkinan match'
            ], 500);
        }
    }

    /**
     * Manual match transaction with payment
     */
    public function manualMatchTransaction(Request $request, BankStatement $bankStatement, BankStatementTransaction $transaction)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        // Validate transaction belongs to this bank statement
        if ($transaction->bank_statement_id !== $bankStatement->id) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($request->payment_id);

            // Check if payment already matched
            if ($payment->bankStatementTransaction()->exists()) {
                return back()->with('error', 'Payment sudah di-match dengan transaksi lain');
            }

            // Check if transaction already matched
            if ($transaction->is_matched) {
                return back()->with('error', 'Transaksi sudah di-match. Unmatch terlebih dahulu jika ingin match ulang');
            }

            // Update transaction
            $transaction->update([
                'is_matched' => true,
                'matched_payment_id' => $payment->id,
                'match_confidence' => 100, // Manual match = 100% confidence
                'match_type' => 'manual',
                'matched_at' => now(),
            ]);

            // Update bank statement matching stats
            $bankStatement->updateMatchingStats();

            DB::commit();

            Log::info('Transaction manually matched', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'matched_by' => auth()->id()
            ]);

            return back()->with('success', 'Transaksi berhasil di-match secara manual dengan Payment #' . $payment->id);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to manual match transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal match transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Unmatch transaction
     */
    public function unmatchTransaction(BankStatement $bankStatement, BankStatementTransaction $transaction)
    {
        // Validate transaction belongs to this bank statement
        if ($transaction->bank_statement_id !== $bankStatement->id) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        DB::beginTransaction();

        try {
            if (!$transaction->is_matched) {
                return back()->with('error', 'Transaksi tidak dalam status matched');
            }

            $oldPaymentId = $transaction->matched_payment_id;

            $transaction->update([
                'is_matched' => false,
                'matched_payment_id' => null,
                'match_confidence' => null,
                'match_type' => null,
                'matched_at' => null,
                'is_verified' => false,
                'verified_at' => null,
                'verified_by' => null,
            ]);

            // Update bank statement matching stats
            $bankStatement->updateMatchingStats();

            DB::commit();

            Log::info('Transaction unmatched', [
                'transaction_id' => $transaction->id,
                'old_payment_id' => $oldPaymentId,
                'unmatched_by' => auth()->id()
            ]);

            return back()->with('success', 'Match transaksi berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to unmatch transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal unmatch transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Call external OCR API
     */
    private function callExternalOcrApi(string $bankCode, $file): array
    {
        $bankCode = strtolower($bankCode);
        $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bankCode}";

        Log::info('Calling OCR API', [
            'url' => $apiUrl,
            'bank_code' => $bankCode
        ]);

        $response = Http::timeout(120)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($apiUrl);

        if (!$response->successful()) {
            Log::error('OCR API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('OCR API Error: ' . $response->body());
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new \Exception('OCR processing failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return $data['ocr'];
    }

    /**
     * Parse OCR response to standardized format
     */
    private function parseOcrResponse(array $ocrData): array
    {
        $transactions = [];

        foreach ($ocrData['TableData'] as $row) {
            $debitAmount = $this->parseAmount($row['Debit'] ?? '0.00');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0.00');

            $transactions[] = [
                'date' => $this->parseDate($row['Date']),
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
        // Remove any non-numeric characters except dot and minus
        $cleaned = preg_replace('/[^0-9.\-]/', '', $amount);
        
        return (float) str_replace(',', '', $cleaned);
    }

    /**
     * Parse date string to Y-m-d format
     */
    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Handle different date formats
            // Format 1: "01/31/24" (MM/DD/YY)
            // Format 2: "01-Jan-2024" (DD-Mon-YYYY)
            // Format 3: "2024-01-31" (Y-m-d)
            
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $date, $matches)) {
                // MM/DD/YY format
                $month = $matches[1];
                $day = $matches[2];
                $year = '20' . $matches[3];
                return "{$year}-{$month}-{$day}";
            }
            
            if (preg_match('/^(\d{2})-([A-Za-z]{3})-(\d{4})$/', $date, $matches)) {
                // 01-Jan-2024 format
                return Carbon::createFromFormat('d-M-Y', $date)->format('Y-m-d');
            }

            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date)) {
                // Already in Y-m-d format
                return $date;
            }

            // Try to parse with Carbon
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
     * Get statistics for dashboard
     */
    public function statistics(Request $request)
    {
        try {
            $query = BankStatement::query();

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('period_from', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('period_to', '<=', $request->end_date);
            }

            // Filter by bank
            if ($request->filled('bank_id')) {
                $query->where('bank_id', $request->bank_id);
            }

            $stats = [
                'total_statements' => $query->count(),
                'total_transactions' => BankStatementTransaction::whereIn(
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

    /**
 * Process matching for all transactions in a bank statement
 */
public function processMatching(BankStatement $bankStatement)
{
    try {
        Log::info('=== PROCESS MATCHING START ===', [
            'statement_id' => $bankStatement->id,
        ]);

        // Check if statement has transactions
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
 * Rematch all transactions (including already matched ones)
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
        // Validate transaction belongs to this statement
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
}