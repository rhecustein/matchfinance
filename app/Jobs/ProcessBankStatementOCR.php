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

    public $timeout = 600; // 10 minutes
    public $tries = 2; // Reduced attempts to save memory
    public $maxExceptions = 2;

    public function __construct(
        public BankStatement $bankStatement,
        public string $bankSlug
    ) {
        // ✅ CRITICAL: Set very high memory limit for PDF processing
        @ini_set('memory_limit', '2048M'); // 2GB
        @ini_set('max_execution_time', '600');
        
        // Enable aggressive garbage collection
        gc_enable();
    }

    public function handle(): void
    {
        // Force garbage collection at start
        gc_collect_cycles();
        
        try {
            Log::info("🚀 Processing OCR for Bank Statement", [
                'statement_id' => $this->bankStatement->id,
                'memory_limit' => ini_get('memory_limit'),
                'memory_start' => $this->formatMemory(memory_get_usage(true)),
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

            $fileSize = filesize($filePath);
            Log::info("📄 File found", [
                'path' => $filePath,
                'size' => $this->formatBytes($fileSize),
                'mime' => mime_content_type($filePath),
            ]);

            // Call External OCR API with streaming
            $ocrResponse = $this->callOCRApi($filePath, $this->bankSlug);
            
            // ✅ Free file path from memory immediately
            unset($filePath);
            gc_collect_cycles();

            Log::info("💾 Memory after OCR call", [
                'current' => $this->formatMemory(memory_get_usage(true)),
                'peak' => $this->formatMemory(memory_get_peak_usage(true)),
            ]);

            if (!isset($ocrResponse['status']) || $ocrResponse['status'] !== 'OK') {
                throw new \Exception("OCR API returned error status: " . ($ocrResponse['message'] ?? 'Unknown error'));
            }

            // Normalize OCR data structure
            $ocrData = $ocrResponse['ocr'] ?? $ocrResponse['data'] ?? $ocrResponse;

            if (isset($ocrData['TableData']) && !isset($ocrData['transactions'])) {
                $ocrData['transactions'] = $ocrData['TableData'];
            }

            $ocrResponse['ocr'] = $ocrData;

            Log::info("📊 OCR Response Structure", [
                'statement_id' => $this->bankStatement->id,
                'transaction_count' => isset($ocrData['transactions']) ? count($ocrData['transactions']) : 0,
            ]);

            // Parse the OCR response
            $parser = $this->getBankParser($this->bankSlug);
            $parsedData = $parser->parse($ocrResponse);

            // ✅ CRITICAL: Free OCR response immediately after parsing
            unset($ocrResponse, $ocrData, $parser);
            gc_collect_cycles();

            $transactions = $parsedData['transactions'] ?? [];

            Log::info("✅ Parsed Data Result", [
                'statement_id' => $this->bankStatement->id,
                'bank' => $this->bankSlug,
                'transaction_count' => count($transactions),
                'memory' => $this->formatMemory(memory_get_usage(true)),
            ]);

            if (empty($transactions)) {
                throw new \Exception("No transactions found in OCR response");
            }

            // Process and store transactions
            $storedCount = $this->storeTransactions($transactions, $parsedData);
            
            // ✅ Free transactions array immediately
            unset($transactions);
            gc_collect_cycles();

            // Update bank statement (DON'T store full response!)
            $this->bankStatement->update([
                'ocr_status' => 'completed',
                'ocr_completed_at' => now(),
                'ocr_response' => null, // ✅ NEVER store - causes memory issues
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
                'total_transactions' => $storedCount,
                'processed_transactions' => $storedCount,
            ]);

            Log::info("🎉 OCR completed successfully", [
                'statement_id' => $this->bankStatement->id,
                'transactions_stored' => $storedCount,
                'time' => now()->diffInSeconds($this->bankStatement->ocr_started_at) . 's',
                'memory_peak' => $this->formatMemory(memory_get_peak_usage(true)),
            ]);

            // Dispatch event (pass minimal data)
            event(new BankStatementOcrCompleted(
                $this->bankStatement,
                ['period_from' => $parsedData['period_from'] ?? null, 'period_to' => $parsedData['period_to'] ?? null],
                $storedCount
            ));

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("❌ OCR processing failed", [
                'statement_id' => $this->bankStatement->id,
                'error' => $e->getMessage(),
                'memory_at_failure' => $this->formatMemory(memory_get_usage(true)),
                'memory_peak' => $this->formatMemory(memory_get_peak_usage(true)),
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
     * Store transactions in small batches with aggressive memory cleanup
     */
    private function storeTransactions(array $transactions, array $parsedData): int
    {
        DB::beginTransaction();

        $storedCount = 0;
        $failedCount = 0;
        $batchSize = 25; // Small batches for memory efficiency
        $totalBatches = ceil(count($transactions) / $batchSize);

        Log::info("📝 Starting transaction storage", [
            'total' => count($transactions),
            'batch_size' => $batchSize,
            'total_batches' => $totalBatches,
        ]);

        foreach (array_chunk($transactions, $batchSize, true) as $batchIndex => $batch) {
            $batchNum = $batchIndex + 1;
            
            Log::info("⚙️ Processing batch {$batchNum}/{$totalBatches}", [
                'memory_before' => $this->formatMemory(memory_get_usage(true)),
            ]);

            foreach ($batch as $index => $txData) {
                try {
                    if (empty($txData['transaction_date'])) {
                        throw new \Exception("Missing transaction_date");
                    }

                    $description = $txData['description'] ?? 'No description';
                    if (strlen($description) > 1000) {
                        $description = substr($description, 0, 1000);
                    }
                    $description = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $description);

                    StatementTransaction::create([
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'company_id' => $this->bankStatement->company_id,
                        'bank_statement_id' => $this->bankStatement->id,
                        'bank_type' => $parsedData['bank_name'] ?? null,
                        'account_number' => $parsedData['account_number'] ?? null,
                        'transaction_date' => $txData['transaction_date'],
                        'transaction_time' => $txData['transaction_time'] ?? null,
                        'value_date' => $txData['value_date'] ?? $txData['transaction_date'],
                        'branch_code' => $parsedData['branch_code'] ?? $txData['branch_code'] ?? null,
                        'description' => $description,
                        'reference_no' => $txData['reference_no'] ?? null,
                        'debit_amount' => $txData['debit_amount'] ?? 0,
                        'credit_amount' => $txData['credit_amount'] ?? 0,
                        'balance' => $txData['balance'] ?? 0,
                        'amount' => $txData['amount'] ?? 0,
                        'transaction_type' => $txData['transaction_type'] ?? 'credit',
                    ]);
                    
                    $storedCount++;
                    
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to store transaction", [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                // Free transaction data immediately
                unset($txData);
            }
            
            // Force garbage collection after each batch
            unset($batch);
            gc_collect_cycles();
            
            Log::info("✓ Batch {$batchNum} completed", [
                'stored_total' => $storedCount,
                'failed_total' => $failedCount,
                'memory_after' => $this->formatMemory(memory_get_usage(true)),
            ]);
        }

        DB::commit();

        Log::info("💯 Transaction storage completed", [
            'total_stored' => $storedCount,
            'total_failed' => $failedCount,
            'memory_final' => $this->formatMemory(memory_get_usage(true)),
        ]);

        return $storedCount;
    }

    /**
     * Get file path with comprehensive strategies
     */
    private function getFilePath(): ?string
    {
        $dbPath = $this->bankStatement->file_path;

        Log::info("🔍 Resolving file path", ['db_path' => $dbPath]);

        // Strategy 1: Direct Storage path
        if (Storage::disk('local')->exists($dbPath)) {
            $path = Storage::disk('local')->path($dbPath);
            if (file_exists($path) && is_readable($path)) {
                Log::info("✅ Found via Storage::disk");
                return $path;
            }
        }

        // Strategy 2: Manual path construction
        $path2 = storage_path('app/' . $dbPath);
        if (file_exists($path2) && is_readable($path2)) {
            Log::info("✅ Found via storage_path('app/')");
            return $path2;
        }

        // Strategy 3: Alternative path
        $path3 = storage_path($dbPath);
        if (file_exists($path3) && is_readable($path3)) {
            Log::info("✅ Found via storage_path()");
            return $path3;
        }

        Log::error("❌ File not found", [
            'db_path' => $dbPath,
            'tried_paths' => [$path2, $path3],
        ]);

        return null;
    }

    /**
     * Call OCR API with streaming to minimize memory
     */
    private function callOCRApi(string $filePath, string $bank): array
    {
        $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bank}";
        $fileSize = filesize($filePath);

        Log::info("📡 Calling OCR API", [
            'url' => $apiUrl,
            'bank' => $bank,
            'file_size' => $this->formatBytes($fileSize),
        ]);

        try {
            // ✅ Use streaming instead of file_get_contents
            $response = Http::timeout(180)
                ->attach('file', fopen($filePath, 'r'), basename($filePath))
                ->post($apiUrl);

            if (!$response->successful()) {
                throw new \Exception("OCR API failed: HTTP {$response->status()}");
            }

            $data = $response->json();
            
            Log::info("✅ OCR API response received", [
                'status' => $data['status'] ?? 'unknown',
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error("❌ OCR API error", [
                'error' => $e->getMessage(),
                'memory' => $this->formatMemory(memory_get_usage(true)),
            ]);
            throw $e;
        }
    }

    /**
     * Get appropriate bank parser
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
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("💀 OCR job failed permanently", [
            'statement_id' => $this->bankStatement->id,
            'error' => $exception->getMessage(),
            'memory_peak' => $this->formatMemory(memory_get_peak_usage(true)),
        ]);

        $this->bankStatement->update([
            'ocr_status' => 'failed',
            'ocr_error' => "Failed after {$this->tries} attempts: " . $exception->getMessage(),
            'ocr_completed_at' => now(),
        ]);
    }

    /**
     * Format memory for logging
     */
    private function formatMemory(int $bytes): string
    {
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }

    /**
     * Format bytes for logging
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}