<?php
// app/Jobs/ProcessBankStatementOCR.php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Events\BankStatementOcrCompleted; // ✅ ADD THIS
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
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Processing OCR for Bank Statement ID: {$this->bankStatement->id}");

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
                'mime' => mime_content_type($filePath),
            ]);

            // Call External OCR API
            $ocrResponse = $this->callOCRApi($filePath, $this->bankSlug);

            if ($ocrResponse['status'] !== 'OK') {
                throw new \Exception("OCR API returned error status");
            }

            // Parse transactions using bank parser
            $parser = $this->getBankParser($this->bankSlug);
           // ✅ NORMALIZE: Different banks use different field names
            $ocrData = $ocrResponse['ocr'] ?? $ocrResponse['data'] ?? $ocrResponse;

            // Normalize transaction field names
            if (isset($ocrData['TableData']) && !isset($ocrData['transactions'])) {
                $ocrData['transactions'] = $ocrData['TableData'];
            }

            $ocrResponse['ocr'] = $ocrData;

            // Parse the OCR response based on bank
            $parser = $this->getBankParser($this->bankSlug);
            $parsedData = $parser->parse($ocrResponse);


            if (empty($transactions)) {
                throw new \Exception("No transactions found in OCR response");
            }

            DB::beginTransaction();

            // Store transactions
            $storedTransactions = [];
            foreach ($transactions as $transactionData) {
                $transaction = StatementTransaction::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'company_id' => $this->bankStatement->company_id,
                    'bank_statement_id' => $this->bankStatement->id,
                    'transaction_date' => $transactionData['transaction_date'],
                    'transaction_time' => $transactionData['transaction_time'] ?? null,
                    'value_date' => $transactionData['value_date'] ?? null,
                    'branch_code' => $transactionData['branch_code'] ?? null,
                    'description' => $transactionData['description'],
                    'reference_no' => $transactionData['reference_no'] ?? null,
                    'debit_amount' => $transactionData['debit_amount'],
                    'credit_amount' => $transactionData['credit_amount'],
                    'balance' => $transactionData['balance'],
                    'amount' => $transactionData['amount'],
                    'transaction_type' => $transactionData['transaction_type'],
                ]);
                
                $storedTransactions[] = $transaction;
            }

            // Update bank statement
            $this->bankStatement->update([
                'ocr_status' => 'completed',
                'ocr_completed_at' => now(),
                'ocr_response' => $ocrResponse,
                'period_from' => $ocrResponse['data']['period_start'] ?? null,
                'period_to' => $ocrResponse['data']['period_end'] ?? null,
                'account_number' => $ocrResponse['data']['account_number'] ?? null,
                'account_holder_name' => $ocrResponse['data']['account_holder_name'] ?? null,
                'opening_balance' => $ocrResponse['data']['opening_balance'] ?? 0,
                'closing_balance' => $ocrResponse['data']['closing_balance'] ?? 0,
                'total_transactions' => count($transactions),
                'processed_transactions' => count($transactions),
                'total_debit_amount' => collect($transactions)->sum('debit_amount'),
                'total_credit_amount' => collect($transactions)->sum('credit_amount'),
                'total_debit_count' => collect($transactions)->where('transaction_type', 'debit')->count(),
                'total_credit_count' => collect($transactions)->where('transaction_type', 'credit')->count(),
            ]);

            DB::commit();

            Log::info("OCR processing completed successfully", [
                'statement_id' => $this->bankStatement->id,
                'total_transactions' => count($transactions),
            ]);

            // ✅ FIRE EVENT - This triggers auto-matching
            event(new BankStatementOcrCompleted(
                $this->bankStatement->fresh(),
                $ocrResponse,
                count($transactions)
            ));

        } catch (\Exception $e) {
            DB::rollBack();

            $this->bankStatement->update([
                'ocr_status' => 'failed',
                'ocr_error' => $e->getMessage(),
                'ocr_completed_at' => now(),
            ]);

            Log::error("OCR processing failed", [
                'statement_id' => $this->bankStatement->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get file path with multiple fallback strategies
     */
    private function getFilePath(): ?string
    {
        $dbPath = $this->bankStatement->file_path;
        
        // Strategy 1: Storage::path() - Laravel's default way
        $path1 = Storage::path($dbPath);
        Log::info("Trying Strategy 1: Storage::path()", ['path' => $path1, 'exists' => file_exists($path1)]);
        if (file_exists($path1)) {
            return $path1;
        }

        // Strategy 2: storage_path('app/') + db_path
        $path2 = storage_path('app/' . $dbPath);
        Log::info("Trying Strategy 2: storage_path('app/')", ['path' => $path2, 'exists' => file_exists($path2)]);
        if (file_exists($path2)) {
            return $path2;
        }

        // Strategy 3: Just storage_path() + db_path (if db_path already includes 'app')
        $path3 = storage_path($dbPath);
        Log::info("Trying Strategy 3: storage_path()", ['path' => $path3, 'exists' => file_exists($path3)]);
        if (file_exists($path3)) {
            return $path3;
        }

        // Strategy 4: Check if file exists using Storage facade
        if (Storage::exists($dbPath)) {
            $path4 = Storage::path($dbPath);
            Log::info("Trying Strategy 4: Storage::exists() confirmed", ['path' => $path4]);
            return $path4;
        }

        // Log all attempted paths for debugging
        Log::error("File not found in any strategy", [
            'db_path' => $dbPath,
            'attempted_paths' => [
                'strategy_1' => $path1,
                'strategy_2' => $path2,
                'strategy_3' => $path3,
            ],
            'storage_exists' => Storage::exists($dbPath),
        ]);

        return null;
    }

    /**
     * Call the external OCR API
     */
    private function callOCRApi(string $filePath, string $bank): array
    {
        // Your external OCR API endpoint
        $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bank}";

        try {
            $response = Http::timeout(120)
                ->attach(
                    'file',
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($apiUrl);

            if (!$response->successful()) {
                throw new \Exception("OCR API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            if (!isset($data['status']) || !isset($data['ocr'])) {
                throw new \Exception("Invalid OCR API response format");
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("OCR API call failed", [
                'url' => $apiUrl,
                'error' => $e->getMessage(),
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
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("OCR job failed permanently", [
            'statement_id' => $this->bankStatement->id,
            'error' => $exception->getMessage(),
        ]);
        
        Log::error("Job failed permanently for Bank Statement ID: {$this->bankStatement->id}", [
            'error' => $exception->getMessage(),
        ]);

        $this->bankStatement->update([
            'ocr_status' => 'failed',
            'ocr_error' => "Job failed after {$this->tries} attempts: " . $exception->getMessage(),
        ]);
    }
}