<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Events\BankStatementOcrCompleted;
use App\Services\BankParsers\BCAParser;
use App\Services\BankParsers\BNIParser;
use App\Services\BankParsers\BRIParser;
use App\Services\BankParsers\BTNParser;
use App\Services\BankParsers\CIMBParser;
use App\Services\BankParsers\MandiriParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBankStatementOCR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $maxExceptions = 3;

    public function __construct(
        public BankStatement $bankStatement,
        public string $bankSlug
    ) {
        // ✅ Set memory limit for this job
        ini_set('memory_limit', '512M');
    }

    public function handle(): void
    {
        try {
            // ✅ Log memory usage at start
            Log::info("Processing OCR for Bank Statement", [
                'statement_id' => $this->bankStatement->id,
                'memory_start' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_limit' => ini_get('memory_limit'),
            ]);

            // Update status to processing
            $this->bankStatement->update([
                'ocr_status' => 'processing',
                'ocr_started_at' => now(),
            ]);

            // Get file path
            $filePath = $this->getFilePath();
            
            if (!$filePath) {
                throw new \Exception("File not found after trying all path strategies");
            }

            if (!is_readable($filePath)) {
                throw new \Exception("File is not readable: {$filePath}");
            }

            Log::info("File found and readable", [
                'path' => $filePath,
                'size' => filesize($filePath),
                'size_mb' => round(filesize($filePath) / 1024 / 1024, 2) . ' MB',
                'mime' => mime_content_type($filePath),
            ]);

            // Call External OCR API
            $ocrResponse = $this->callOCRApi($filePath, $this->bankSlug);

            // ✅ Free memory after API call
            unset($filePath);
            gc_collect_cycles();

            Log::info("Memory after OCR API call", [
                'memory_current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            if (!isset($ocrResponse['status']) || $ocrResponse['status'] !== 'OK') {
                throw new \Exception("OCR API returned error status: " . ($ocrResponse['message'] ?? 'Unknown error'));
            }

            // Normalize OCR data structure
            $ocrData = $ocrResponse['ocr'] ?? $ocrResponse['data'] ?? $ocrResponse;

            // Handle different field names for transactions
            if (isset($ocrData['TableData']) && !isset($ocrData['transactions'])) {
                $ocrData['transactions'] = $ocrData['TableData'];
            }

            // Ensure ocr data is properly nested
            $ocrResponse['ocr'] = $ocrData;

            Log::info("OCR Response Structure", [
                'statement_id' => $this->bankStatement->id,
                'has_ocr_key' => isset($ocrResponse['ocr']),
                'has_data_key' => isset($ocrResponse['data']),
                'has_transactions' => isset($ocrData['transactions']),
                'has_TableData' => isset($ocrData['TableData']),
                'transaction_count' => isset($ocrData['transactions']) ? count($ocrData['transactions']) : 0,
            ]);

            // Parse the OCR response based on bank
            $parser = $this->getBankParser($this->bankSlug);
            $parsedData = $parser->parse($ocrResponse);

            // ✅ Free memory after parsing
            unset($ocrResponse, $ocrData);
            gc_collect_cycles();

            // ASSIGN TRANSACTIONS FROM PARSED DATA
            $transactions = $parsedData['transactions'] ?? [];

            Log::info("Parsed Data Result", [
                'statement_id' => $this->bankStatement->id,
                'bank' => $this->bankSlug,
                'bank_name' => $parsedData['bank_name'] ?? null,
                'account_number' => $parsedData['account_number'] ?? null,
                'branch_code' => $parsedData['branch_code'] ?? null,
                'transaction_count' => count($transactions),
                'has_account_number' => !empty($parsedData['account_number']),
                'has_period' => !empty($parsedData['period_from']),
                'memory_before_insert' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            if (empty($transactions)) {
                Log::error("No transactions parsed", [
                    'statement_id' => $this->bankStatement->id,
                    'bank' => $this->bankSlug,
                    'parsed_data_keys' => array_keys($parsedData),
                ]);
                
                throw new \Exception("No transactions found in OCR response. Parser returned empty transactions array.");
            }

            DB::beginTransaction();

            Log::info("Starting transaction storage", [
                'statement_id' => $this->bankStatement->id,
                'count' => count($transactions),
            ]);

            // ✅ Process transactions in smaller chunks to avoid memory overflow
            $storedTransactions = [];
            $failedTransactions = [];
            $chunkSize = 50; // ✅ REDUCED from 100 to 50 for better memory management
            $totalChunks = ceil(count($transactions) / $chunkSize);
            
            foreach (array_chunk($transactions, $chunkSize, true) as $chunkIndex => $transactionChunk) {
                Log::info("Processing transaction chunk", [
                    'chunk' => $chunkIndex + 1,
                    'total_chunks' => $totalChunks,
                    'chunk_size' => count($transactionChunk),
                    'memory_before_chunk' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                ]);
                
                foreach ($transactionChunk as $index => $transactionData) {
                    $actualIndex = ($chunkIndex * $chunkSize) + $index;
                    
                    try {
                        if (empty($transactionData['transaction_date'])) {
                            throw new \Exception("Missing transaction_date");
                        }

                        $description = $transactionData['description'] ?? 'No description';
                        if (strlen($description) > 1000) {
                            $description = substr($description, 0, 1000);
                        }

                        $description = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $description);

                        $transaction = StatementTransaction::create([
                            'uuid' => \Illuminate\Support\Str::uuid(),
                            'company_id' => $this->bankStatement->company_id,
                            'bank_statement_id' => $this->bankStatement->id,
                            'bank_type' => $parsedData['bank_name'] ?? null,
                            'account_number' => $parsedData['account_number'] ?? null,
                            'transaction_date' => $transactionData['transaction_date'],
                            'transaction_time' => $transactionData['transaction_time'] ?? null,
                            'value_date' => $transactionData['value_date'] ?? $transactionData['transaction_date'],
                            'branch_code' => $parsedData['branch_code'] ?? $transactionData['branch_code'] ?? null,
                            'description' => $description,
                            'reference_no' => $transactionData['reference_no'] ?? null,
                            'debit_amount' => $transactionData['debit_amount'] ?? 0,
                            'credit_amount' => $transactionData['credit_amount'] ?? 0,
                            'balance' => $transactionData['balance'] ?? 0,
                            'amount' => $transactionData['amount'] ?? 0,
                            'transaction_type' => $transactionData['transaction_type'] ?? 'credit',
                        ]);
                        
                        // ✅ Only keep count, not full objects to save memory
                        $storedTransactions[] = $transaction->id;
                        
                        // ✅ Unset transaction object immediately
                        unset($transaction);
                        
                    } catch (\Exception $e) {
                        $failedTransactions[] = [
                            'index' => $actualIndex,
                            'error' => $e->getMessage(),
                        ];
                        Log::error("Failed to store transaction", [
                            'index' => $actualIndex,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    
                    // ✅ Unset transaction data immediately after processing
                    unset($transactionData);
                }
                
                // ✅ Force garbage collection after each chunk
                unset($transactionChunk);
                gc_collect_cycles();
                
                Log::info("Transaction chunk completed", [
                    'chunk' => $chunkIndex + 1,
                    'processed_total' => count($storedTransactions),
                    'failed_total' => count($failedTransactions),
                    'memory_after_chunk' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                ]);
            }

            // ✅ Free transaction array memory
            unset($transactions);
            gc_collect_cycles();

            Log::info("Transaction storage completed", [
                'statement_id' => $this->bankStatement->id,
                'successfully_stored' => count($storedTransactions),
                'failed' => count($failedTransactions),
                'memory_final' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            if (!empty($failedTransactions)) {
                Log::error("Sample of failed transactions", [
                    'statement_id' => $this->bankStatement->id,
                    'failures_sample' => array_slice($failedTransactions, 0, 5),
                ]);
            }

            // Update bank statement with parsed data
            // ✅ Don't store full ocr_response to save memory
            $this->bankStatement->update([
                'ocr_status' => 'completed',
                'ocr_completed_at' => now(),
                'ocr_response' => null, // ✅ Set to null to avoid storing large JSON
                'period_from' => $parsedData['period_from'] ?? null,
                'period_to' => $parsedData['period_to'] ?? null,
                'account_number' => $parsedData['account_number'] ?? null,
                'account_holder_name' => $parsedData['account_holder_name'] ?? null,
                'opening_balance' => $parsedData['opening_balance'] ?? 0,
                'closing_balance' => $parsedData['closing_balance'] ?? 0,
                'total_credit_count' => $parsedData['total_credit_count'] ?? 0,
                'total_debit_count' => $parsedData['total_debit_count'] ?? 0,
                'total_credit_amount' => $parsedData['total_credit_amount'] ?? 0,
                'total_debit_amount' => $parsedData['total_debit_amount'] ?? 0,
                'total_transactions' => count($storedTransactions),
                'processed_transactions' => count($storedTransactions),
            ]);

            DB::commit();

            Log::info("OCR processing completed successfully", [
                'statement_id' => $this->bankStatement->id,
                'transactions_stored' => count($storedTransactions),
                'processing_time' => now()->diffInSeconds($this->bankStatement->ocr_started_at),
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            event(new BankStatementOcrCompleted(
                $this->bankStatement,
                $parsedData,
                count($storedTransactions)
            ));

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("OCR processing failed", [
                'statement_id' => $this->bankStatement->id,
                'error' => $e->getMessage(),
                'memory_at_failure' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            $this->bankStatement->update([
                'ocr_status' => 'failed',
                'ocr_error' => $e->getMessage(),
                'ocr_completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the file path using comprehensive strategies
     * Handles both legacy (private/) and new (companies/) formats
     */
    private function getFilePath(): ?string
    {
        $dbPath = $this->bankStatement->file_path;
        $statementId = $this->bankStatement->id;

        Log::info("🔍 Starting file path resolution", [
            'statement_id' => $statementId,
            'db_path' => $dbPath,
            'company_id' => $this->bankStatement->company_id,
        ]);

        // === STRATEGY 1: Direct Storage Disk Path ===
        try {
            if (Storage::disk('local')->exists($dbPath)) {
                $fullPath = Storage::disk('local')->path($dbPath);
                
                if (file_exists($fullPath) && is_readable($fullPath)) {
                    Log::info("✅ Strategy 1 SUCCESS: Direct Storage disk path", [
                        'path' => $fullPath,
                        'size' => filesize($fullPath),
                    ]);
                    return $fullPath;
                }
            }
        } catch (\Exception $e) {
            Log::warning("⚠️ Strategy 1 exception", ['error' => $e->getMessage()]);
        }

        // === STRATEGY 2: Manual with storage_path('app/') ===
        $path2 = storage_path('app/' . $dbPath);
        Log::info("Trying Strategy 2: storage_path('app/')", [
            'path' => $path2,
            'exists' => file_exists($path2),
            'readable' => file_exists($path2) ? is_readable($path2) : false,
        ]);
        if (file_exists($path2) && is_readable($path2)) {
            Log::info("✅ Strategy 2 SUCCESS");
            return $path2;
        }

        // === STRATEGY 3: Just storage_path() ===
        $path3 = storage_path($dbPath);
        Log::info("Trying Strategy 3: storage_path()", [
            'path' => $path3,
            'exists' => file_exists($path3),
            'readable' => file_exists($path3) ? is_readable($path3) : false,
        ]);
        if (file_exists($path3) && is_readable($path3)) {
            Log::info("✅ Strategy 3 SUCCESS");
            return $path3;
        }

        // === STRATEGY 4: Try without 'private/' or 'companies/' prefix ===
        if (str_starts_with($dbPath, 'private/')) {
            $pathVariant = substr($dbPath, 8);
            try {
                if (Storage::disk('local')->exists($pathVariant)) {
                    $fullPath = Storage::disk('local')->path($pathVariant);
                    if (file_exists($fullPath) && is_readable($fullPath)) {
                        Log::info("✅ Strategy 4 SUCCESS: Without 'private/' prefix", [
                            'original' => $dbPath,
                            'variant' => $pathVariant,
                            'path' => $fullPath,
                        ]);
                        return $fullPath;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Strategy 4 exception", ['error' => $e->getMessage()]);
            }
        }

        if (str_starts_with($dbPath, 'companies/')) {
            $pathVariant = substr($dbPath, strpos($dbPath, '/', 10) + 1);
            try {
                if (Storage::disk('local')->exists($pathVariant)) {
                    $fullPath = Storage::disk('local')->path($pathVariant);
                    if (file_exists($fullPath) && is_readable($fullPath)) {
                        Log::info("✅ Strategy 4b SUCCESS: Simplified companies path", [
                            'original' => $dbPath,
                            'variant' => $pathVariant,
                            'path' => $fullPath,
                        ]);
                        return $fullPath;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Strategy 4b exception", ['error' => $e->getMessage()]);
            }
        }

        // === DEBUGGING: List directory contents ===
        $this->debugDirectoryContents($dbPath);

        // === ALL STRATEGIES FAILED ===
        Log::error("❌ File not found after ALL strategies", [
            'statement_id' => $statementId,
            'db_path' => $dbPath,
            'company_id' => $this->bankStatement->company_id,
            'bank_slug' => $this->bankSlug,
            'storage_root' => storage_path('app'),
        ]);

        return null;
    }

    /**
     * Debug helper: List actual directory contents
     */
    private function debugDirectoryContents(string $dbPath): void
    {
        $directory = dirname($dbPath);
        $filename = basename($dbPath);
        
        Log::info("🔍 Debugging: Checking directory contents", [
            'directory' => $directory,
            'expected_file' => $filename,
        ]);

        try {
            if (Storage::disk('local')->exists($directory)) {
                $files = Storage::disk('local')->files($directory);
                
                Log::error("📁 Directory exists but file not found", [
                    'directory' => $directory,
                    'expected_file' => $filename,
                    'actual_files_count' => count($files),
                    'actual_files_sample' => array_slice($files, 0, 10),
                ]);
            } else {
                Log::error("📁 Directory does NOT exist", [
                    'directory' => $directory,
                    'full_path_attempt' => storage_path('app/' . $directory),
                ]);

                $parentDir = dirname($directory);
                if ($parentDir && $parentDir !== '.' && Storage::disk('local')->exists($parentDir)) {
                    $dirs = Storage::disk('local')->directories($parentDir);
                    Log::info("📁 Parent directory contents", [
                        'parent' => $parentDir,
                        'subdirectories' => $dirs,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("⚠️ Could not debug directory", [
                'error' => $e->getMessage(),
                'directory' => $directory,
            ]);
        }
    }

    /**
     * Call the external OCR API
     * Ultra memory-optimized version with chunked streaming
     */
    private function callOCRApi(string $filePath, string $bank): array
    {
        $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bank}";

        try {
            $fileSize = filesize($filePath);
            
            Log::info("Calling OCR API", [
                'url' => $apiUrl,
                'bank' => $bank,
                'file_size' => $fileSize,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2) . ' MB',
                'memory_before' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            // ✅ CRITICAL: Use CURLFile for proper stream handling
            $response = Http::timeout(180)
                ->attach(
                    'file',
                    fopen($filePath, 'r'),
                    basename($filePath),
                    ['Content-Type' => 'application/pdf']
                )
                ->post($apiUrl);

            Log::info("OCR API request sent", [
                'memory_after' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            if (!$response->successful()) {
                throw new \Exception("OCR API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            Log::info("OCR API Response received", [
                'status' => $data['status'] ?? 'unknown',
                'has_ocr' => isset($data['ocr']),
                'has_data' => isset($data['data']),
                'memory_after_response' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            // ✅ Force garbage collection immediately
            gc_collect_cycles();

            return $data;

        } catch (\Exception $e) {
            Log::error("OCR API call failed", [
                'url' => $apiUrl,
                'error' => $e->getMessage(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);
            throw $e;
        }
    }

    /**
     * Get the appropriate parser for the bank
     */
    private function getBankParser(string $bankSlug): object
    {
        return match (strtolower($bankSlug)) {
            'bca' => new BCAParser(),
            'bni' => new BNIParser(),
            'bri' => new BRIParser(),
            'btn' => new BTNParser(),
            'cimb' => new CIMBParser(),
            'mandiri' => new MandiriParser(),
            default => throw new \Exception("Unsupported bank: {$bankSlug}"),
        };
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("OCR job failed permanently", [
            'statement_id' => $this->bankStatement->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->bankStatement->update([
            'ocr_status' => 'failed',
            'ocr_error' => "Job failed after {$this->tries} attempts: " . $exception->getMessage(),
            'ocr_completed_at' => now(),
        ]);
    }
}