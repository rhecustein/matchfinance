<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Jobs\ProcessBankStatementOCR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BankStatementController extends Controller
{
    /**
     * Display a listing of bank statements
     */
    public function index(Request $request)
    {
        $query = BankStatement::with(['bank', 'user'])
            ->latest('uploaded_at');

        // Filter by bank
        if ($request->has('bank_id') && $request->bank_id) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by OCR status
        if ($request->has('ocr_status') && $request->ocr_status) {
            $query->where('ocr_status', $request->ocr_status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('period_from', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('period_to', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('original_filename', 'like', '%' . $request->search . '%')
                  ->orWhere('account_number', 'like', '%' . $request->search . '%');
            });
        }

        $statements = $query->paginate(20)->withQueryString();

        // Get banks for filter
        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        return view('bank-statements.index', compact('statements', 'banks'));
    }

    /**
     * Show the form for creating a new bank statement (upload form)
     */
    public function create()
    {
        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        
        return view('bank-statements.create', compact('banks'));
    }

    /**
     * Store a newly created bank statement (from web form)
     * Updated: Replace duplicate files instead of skipping
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:pdf|max:10240',
        ]);

        $bank = Bank::findOrFail($request->bank_id);
        
        $uploadedCount = 0;
        $replacedCount = 0;
        $failedFiles = [];

        DB::beginTransaction();

        try {
            foreach ($request->file('files') as $file) {
                $fileHash = hash_file('sha256', $file->getRealPath());

                // Check for existing file (including soft deleted)
                $existingStatement = BankStatement::withTrashed()
                    ->where('file_hash', $fileHash)
                    ->where('bank_id', $bank->id)
                    ->first();

                try {
                    // Generate unique filename with full timestamp
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    
                    $timestamp = now()->format('Ymd_His');
                    $microseconds = now()->format('u');
                    $randomString = Str::random(8);
                    
                    $baseFilename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
                    $filename = sprintf(
                        '%s_%s_%s_%s.%s',
                        $baseFilename,
                        $timestamp,
                        $microseconds,
                        $randomString,
                        $extension
                    );

                    // Store file - use default 'local' disk
                    $path = $file->storeAs(
                        "bank-statements/{$bank->slug}/" . date('Y/m'),
                        $filename
                    );

                    // Verify file was stored
                    if (!Storage::exists($path)) {
                        throw new \Exception("Failed to store file: {$originalName}");
                    }

                    if ($existingStatement) {
                        // REPLACE: Update existing record and delete old file
                        
                        // Delete old file from storage if exists
                        if ($existingStatement->file_path && Storage::exists($existingStatement->file_path)) {
                            Storage::delete($existingStatement->file_path);
                            Log::info('Old file deleted', ['old_path' => $existingStatement->file_path]);
                        }

                        // Delete existing transactions
                        $existingStatement->transactions()->delete();

                        // Restore if soft deleted
                        if ($existingStatement->trashed()) {
                            $existingStatement->restore();
                            Log::info('Soft deleted statement restored', ['id' => $existingStatement->id]);
                        }

                        // Update existing record
                        $existingStatement->update([
                            'user_id' => Auth::id(),
                            'file_path' => $path,
                            'file_hash' => $fileHash,
                            'original_filename' => $originalName,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'ocr_status' => 'pending',
                            'ocr_job_id' => Str::uuid()->toString(),
                            'ocr_error' => null,
                            'ocr_response' => null,
                            'ocr_started_at' => null,
                            'ocr_completed_at' => null,
                            'uploaded_at' => now(),
                            // Reset financial data
                            'bank_name' => null,
                            'period_from' => null,
                            'period_to' => null,
                            'account_number' => null,
                            'opening_balance' => null,
                            'closing_balance' => null,
                            'total_credit_count' => 0,
                            'total_debit_count' => 0,
                            'total_credit_amount' => 0,
                            'total_debit_amount' => 0,
                            'total_transactions' => 0,
                            'processed_transactions' => 0,
                            'matched_transactions' => 0,
                            'unmatched_transactions' => 0,
                            'verified_transactions' => 0,
                        ]);

                        // Dispatch new OCR job
                        ProcessBankStatementOCR::dispatch($existingStatement, $bank->slug)
                            ->onQueue('ocr-processing');

                        $replacedCount++;

                        Log::info('Bank statement REPLACED successfully', [
                            'bank_statement_id' => $existingStatement->id,
                            'user_id' => Auth::id(),
                            'filename' => $originalName,
                            'stored_as' => $filename,
                            'bank' => $bank->name,
                            'file_path' => $path,
                        ]);

                    } else {
                        // NEW: Create new record
                        $bankStatement = BankStatement::create([
                            'bank_id' => $bank->id,
                            'user_id' => Auth::id(),
                            'file_path' => $path,
                            'file_hash' => $fileHash,
                            'original_filename' => $originalName,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'ocr_status' => 'pending',
                            'ocr_job_id' => Str::uuid()->toString(),
                            'uploaded_at' => now(),
                        ]);

                        // Dispatch job
                        ProcessBankStatementOCR::dispatch($bankStatement, $bank->slug)
                            ->onQueue('ocr-processing');

                        $uploadedCount++;

                        Log::info('Bank statement uploaded successfully', [
                            'bank_statement_id' => $bankStatement->id,
                            'user_id' => Auth::id(),
                            'filename' => $originalName,
                            'stored_as' => $filename,
                            'bank' => $bank->name,
                            'file_path' => $path,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to upload individual file', [
                        'filename' => $originalName ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    $failedFiles[] = $originalName ?? 'unknown file';
                }
            }

            DB::commit();

            // Build success message
            $messageParts = [];
            
            if ($uploadedCount > 0) {
                $messageParts[] = "{$uploadedCount} new file(s) uploaded";
            }
            
            if ($replacedCount > 0) {
                $messageParts[] = "{$replacedCount} file(s) replaced";
            }
            
            if (count($failedFiles) > 0) {
                $messageParts[] = count($failedFiles) . " file(s) failed";
            }

            $message = implode(', ', $messageParts);
            if ($uploadedCount > 0 || $replacedCount > 0) {
                $message .= ' and queued for OCR processing';
            }

            // Flash messages for UI
            return redirect()->route('bank-statements.index')
                ->with('success', $message)
                ->with('queued_count', $uploadedCount + $replacedCount)
                ->with('uploaded_count', $uploadedCount)
                ->with('replaced_count', $replacedCount)
                ->with('failed_files', $failedFiles);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bank statement upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()
                ->with('error', 'Upload failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified bank statement
     */
    public function show(BankStatement $bankStatement)
    {
        $bankStatement->load([
            'bank',
            'user',
            'transactions' => function ($query) {
                $query->with(['subCategory.category.type', 'verifiedBy'])
                      ->latest('transaction_date');
            }
        ]);

        return view('bank-statements.show', compact('bankStatement'));
    }

    /**
     * Show the form for editing the specified bank statement
     */
    public function edit(BankStatement $bankStatement)
    {
        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        
        return view('bank-statements.edit', compact('bankStatement', 'banks'));
    }

    /**
     * Update the specified bank statement
     */
    public function update(Request $request, BankStatement $bankStatement)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
        ]);

        $bankStatement->update($validated);

        return redirect()->route('bank-statements.show', $bankStatement)
            ->with('success', 'Bank statement updated successfully.');
    }

    /**
     * Remove the specified bank statement
     */
    public function destroy(BankStatement $bankStatement)
    {
        try {
            // Delete file from storage
            if ($bankStatement->file_path && Storage::exists($bankStatement->file_path)) {
                Storage::delete($bankStatement->file_path);
            }

            // Delete record (will cascade delete transactions)
            $bankStatement->delete();

            return redirect()->route('bank-statements.index')
                ->with('success', 'Bank statement deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete bank statement', [
                'id' => $bankStatement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to delete bank statement.');
        }
    }

    /**
     * Download bank statement PDF
     */
    public function download(BankStatement $bankStatement)
    {
        if (!Storage::exists($bankStatement->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::download(
            $bankStatement->file_path,
            $bankStatement->original_filename
        );
    }

    /**
     * Reprocess OCR for a statement
     */
    public function reprocess(BankStatement $bankStatement)
    {
        $bankStatement->update([
            'ocr_status' => 'pending',
            'ocr_error' => null,
            'ocr_response' => null,
        ]);

        // Delete existing transactions
        $bankStatement->transactions()->delete();

        // Dispatch OCR job again
        ProcessBankStatementOCR::dispatch($bankStatement, $bankStatement->bank->slug)
            ->onQueue('ocr-processing');

        return back()->with('success', 'OCR processing has been queued again.');
    }

    /**
     * Match transactions for a statement
     */
    public function matchTransactions(BankStatement $bankStatement)
    {
        \App\Jobs\ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching');

        return back()->with('success', 'Transaction matching has been queued.');
    }

    /**
     * Rematch all transactions
     */
    public function rematchAll(BankStatement $bankStatement)
    {
        // Reset matching data
        $bankStatement->transactions()->update([
            'matched_keyword_id' => null,
            'confidence_score' => 0,
            'type_id' => null,
            'category_id' => null,
            'sub_category_id' => null,
            'is_manual_category' => false,
        ]);

        // Dispatch matching job
        \App\Jobs\ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching');

        return back()->with('success', 'All transactions will be re-matched.');
    }

    /**
     * Verify all matched transactions
     */
    public function verifyAllMatched(BankStatement $bankStatement)
    {
        $updated = $bankStatement->transactions()
            ->whereNotNull('matched_keyword_id')
            ->where('is_verified', false)
            ->update([
                'is_verified' => true,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

        return back()->with('success', "{$updated} transaction(s) verified.");
    }

    /**
     * Get transaction details (AJAX)
     */
    public function getTransaction(BankStatement $bankStatement, $transactionId)
    {
        $transaction = $bankStatement->transactions()
            ->with(['subCategory.category.type', 'matchedKeyword', 'verifiedBy'])
            ->findOrFail($transactionId);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Verify single transaction
     */
    public function verifyTransaction(BankStatement $bankStatement, $transactionId)
    {
        $transaction = $bankStatement->transactions()->findOrFail($transactionId);

        $transaction->update([
            'is_verified' => true,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Transaction verified successfully.');
    }

    /**
     * Get statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => BankStatement::count(),
            'pending' => BankStatement::where('ocr_status', 'pending')->count(),
            'processing' => BankStatement::where('ocr_status', 'processing')->count(),
            'completed' => BankStatement::where('ocr_status', 'completed')->count(),
            'failed' => BankStatement::where('ocr_status', 'failed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // ========================================
    // API Methods for Multi-Bank Upload
    // ========================================

    /**
     * Upload Bank Statement - Mandiri (API)
     */
    public function uploadMandiri(Request $request)
    {
        return $this->uploadBankStatement($request, 'mandiri');
    }

    /**
     * Upload Bank Statement - BCA (API)
     */
    public function uploadBCA(Request $request)
    {
        return $this->uploadBankStatement($request, 'bca');
    }

    /**
     * Upload Bank Statement - BNI (API)
     */
    public function uploadBNI(Request $request)
    {
        return $this->uploadBankStatement($request, 'bni');
    }

    /**
     * Upload Bank Statement - BRI (API)
     */
    public function uploadBRI(Request $request)
    {
        return $this->uploadBankStatement($request, 'bri');
    }

    /**
     * Upload Bank Statement - BTN (API)
     */
    public function uploadBTN(Request $request)
    {
        return $this->uploadBankStatement($request, 'btn');
    }

    /**
     * Upload Bank Statement - CIMB (API)
     */
    public function uploadCIMB(Request $request)
    {
        return $this->uploadBankStatement($request, 'cimb');
    }

    /**
     * Core upload method for all banks with multi-file support (API)
     * Updated: Replace duplicate files instead of skipping
     */
    private function uploadBankStatement(Request $request, string $bankSlug)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'files' => 'required|array|min:1|max:10',
                'files.*' => 'required|file|mimes:pdf|max:10240',
            ], [
                'files.required' => 'Please select at least one file to upload.',
                'files.array' => 'Files must be uploaded as an array.',
                'files.min' => 'Please select at least one file.',
                'files.max' => 'You can only upload maximum 10 files at once.',
                'files.*.required' => 'Each file is required.',
                'files.*.file' => 'Each upload must be a valid file.',
                'files.*.mimes' => 'Only PDF files are allowed.',
                'files.*.max' => 'Each file must not exceed 10MB.',
            ]);

            // Find bank by slug
            $bank = Bank::where('slug', $bankSlug)->firstOrFail();

            $uploadedStatements = [];
            $replacedStatements = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->file('files') as $index => $file) {
                try {
                    // Calculate file hash for duplicate detection
                    $fileHash = hash_file('sha256', $file->getRealPath());

                    // Check for existing file (including soft deleted)
                    $existingStatement = BankStatement::withTrashed()
                        ->where('file_hash', $fileHash)
                        ->where('bank_id', $bank->id)
                        ->first();

                    // Generate unique filename with full timestamp
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    
                    $timestamp = now()->format('Ymd_His');
                    $microseconds = now()->format('u');
                    $randomString = Str::random(8);
                    
                    $baseFilename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
                    $filename = sprintf(
                        '%s_%s_%s_%s.%s',
                        $baseFilename,
                        $timestamp,
                        $microseconds,
                        $randomString,
                        $extension
                    );

                    // Store file using default 'local' disk
                    $path = $file->storeAs(
                        "bank-statements/{$bankSlug}/" . date('Y/m'),
                        $filename
                    );

                    if (!$path) {
                        throw new \Exception("Failed to store file: {$originalName}");
                    }

                    // Verify file exists
                    if (!Storage::exists($path)) {
                        throw new \Exception("File was not properly stored: {$originalName}");
                    }

                    if ($existingStatement) {
                        // REPLACE: Update existing record
                        
                        // Delete old file from storage if exists
                        if ($existingStatement->file_path && Storage::exists($existingStatement->file_path)) {
                            Storage::delete($existingStatement->file_path);
                        }

                        // Delete existing transactions
                        $existingStatement->transactions()->delete();

                        // Restore if soft deleted
                        if ($existingStatement->trashed()) {
                            $existingStatement->restore();
                        }

                        // Update existing record
                        $existingStatement->update([
                            'user_id' => Auth::id(),
                            'file_path' => $path,
                            'file_hash' => $fileHash,
                            'original_filename' => $originalName,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'ocr_status' => 'pending',
                            'ocr_job_id' => Str::uuid()->toString(),
                            'ocr_error' => null,
                            'ocr_response' => null,
                            'ocr_started_at' => null,
                            'ocr_completed_at' => null,
                            'uploaded_at' => now(),
                            // Reset financial data
                            'bank_name' => null,
                            'period_from' => null,
                            'period_to' => null,
                            'account_number' => null,
                            'opening_balance' => null,
                            'closing_balance' => null,
                            'total_credit_count' => 0,
                            'total_debit_count' => 0,
                            'total_credit_amount' => 0,
                            'total_debit_amount' => 0,
                            'total_transactions' => 0,
                            'processed_transactions' => 0,
                            'matched_transactions' => 0,
                            'unmatched_transactions' => 0,
                            'verified_transactions' => 0,
                        ]);

                        // Dispatch OCR processing job
                        ProcessBankStatementOCR::dispatch($existingStatement, $bankSlug)
                            ->onQueue('ocr-processing');

                        $replacedStatements[] = [
                            'id' => $existingStatement->id,
                            'filename' => $originalName,
                            'stored_as' => $filename,
                            'size' => $this->formatBytes($file->getSize()),
                            'status' => 'replaced',
                            'message' => 'File replaced successfully and queued for OCR processing.',
                        ];

                        Log::info("Bank statement REPLACED", [
                            'bank' => $bankSlug,
                            'user_id' => Auth::id(),
                            'statement_id' => $existingStatement->id,
                            'filename' => $originalName,
                            'stored_as' => $filename,
                            'path' => $path,
                        ]);

                    } else {
                        // NEW: Create new record
                        $bankStatement = BankStatement::create([
                            'bank_id' => $bank->id,
                            'user_id' => Auth::id(),
                            'file_path' => $path,
                            'file_hash' => $fileHash,
                            'original_filename' => $originalName,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'ocr_status' => 'pending',
                            'ocr_job_id' => Str::uuid()->toString(),
                            'uploaded_at' => now(),
                        ]);

                        // Dispatch OCR processing job
                        ProcessBankStatementOCR::dispatch($bankStatement, $bankSlug)
                            ->onQueue('ocr-processing');

                        $uploadedStatements[] = [
                            'id' => $bankStatement->id,
                            'filename' => $originalName,
                            'stored_as' => $filename,
                            'size' => $this->formatBytes($file->getSize()),
                            'status' => 'queued',
                            'message' => 'File uploaded successfully and queued for OCR processing.',
                        ];

                        Log::info("Bank statement uploaded", [
                            'bank' => $bankSlug,
                            'user_id' => Auth::id(),
                            'statement_id' => $bankStatement->id,
                            'filename' => $originalName,
                            'stored_as' => $filename,
                            'path' => $path,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to upload file", [
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $errors[] = [
                        'filename' => $file->getClientOriginalName(),
                        'message' => 'Failed to upload: ' . $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            // Prepare response
            $response = [
                'success' => true,
                'message' => $this->generateResponseMessage(
                    count($uploadedStatements),
                    count($replacedStatements),
                    count($errors)
                ),
                'data' => [
                    'bank' => [
                        'id' => $bank->id,
                        'name' => $bank->name,
                        'slug' => $bank->slug,
                    ],
                    'uploaded' => $uploadedStatements,
                    'total_uploaded' => count($uploadedStatements),
                ],
            ];

            if (!empty($replacedStatements)) {
                $response['data']['replaced'] = $replacedStatements;
                $response['data']['total_replaced'] = count($replacedStatements);
            }

            if (!empty($errors)) {
                $response['data']['errors'] = $errors;
                $response['data']['total_errors'] = count($errors);
            }

            return response()->json($response, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Bank statement upload failed", [
                'bank' => $bankSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate response message based on upload results
     * Updated: Include replaced count
     */
    private function generateResponseMessage(int $uploaded, int $replaced, int $errors): string
    {
        $messages = [];

        if ($uploaded > 0) {
            $messages[] = "{$uploaded} new file(s) uploaded";
        }

        if ($replaced > 0) {
            $messages[] = "{$replaced} file(s) replaced";
        }

        if ($errors > 0) {
            $messages[] = "{$errors} file(s) failed";
        }

        return implode(', ', $messages) . '.';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get upload status for a bank statement (API)
     */
    public function getStatus(BankStatement $bankStatement)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bankStatement->id,
                'bank' => $bankStatement->bank->name,
                'filename' => $bankStatement->original_filename,
                'ocr_status' => $bankStatement->ocr_status,
                'uploaded_at' => $bankStatement->uploaded_at,
                'processed_at' => $bankStatement->ocr_completed_at,
                'total_transactions' => $bankStatement->total_transactions,
                'processed_transactions' => $bankStatement->processed_transactions,
                'matched_transactions' => $bankStatement->matched_transactions,
                'unmatched_transactions' => $bankStatement->unmatched_transactions,
                'error' => $bankStatement->ocr_error,
            ],
        ]);
    }

    /**
     * Retry failed OCR processing (API)
     */
    public function retryOCR(BankStatement $bankStatement)
    {
        if ($bankStatement->ocr_status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed statements can be retried.',
            ], 400);
        }

        $bankStatement->update([
            'ocr_status' => 'pending',
            'ocr_error' => null,
        ]);

        ProcessBankStatementOCR::dispatch($bankStatement, $bankStatement->bank->slug)
            ->onQueue('ocr-processing');

        return response()->json([
            'success' => true,
            'message' => 'OCR processing has been queued again.',
        ]);
    }
}