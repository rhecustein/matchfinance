<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
// ✅ FIX: Import from correct file - all parsers are in one file
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

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankStatement $bankStatement,
        public string $bankSlug
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing OCR for Bank Statement ID: {$this->bankStatement->id}");

            // Update status to processing
            $this->bankStatement->update([
                'ocr_status' => 'processing',
                'ocr_started_at' => now(),
            ]);

            // ✅ ULTIMATE FIX: Try multiple path strategies
            $filePath = $this->getFilePath();
            
            if (!$filePath) {
                throw new \Exception("File not found after trying all path strategies");
            }

            // Verify file is readable
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

            // Parse the OCR response based on bank
            $parser = $this->getBankParser($this->bankSlug);
            $parsedData = $parser->parse($ocrResponse);

            // Begin Database Transaction
            DB::beginTransaction();

            // Update bank statement with parsed data
            $this->bankStatement->update([
                'ocr_status' => 'completed',
                'ocr_response' => $ocrResponse,
                'ocr_completed_at' => now(),
                'bank_name' => $parsedData['bank_name'],
                'period_from' => $parsedData['period_from'],
                'period_to' => $parsedData['period_to'],
                'account_number' => $parsedData['account_number'],
                'currency' => $parsedData['currency'],
                'branch_code' => $parsedData['branch_code'],
                'opening_balance' => $parsedData['opening_balance'],
                'closing_balance' => $parsedData['closing_balance'],
                'total_credit_count' => $parsedData['total_credit_count'],
                'total_debit_count' => $parsedData['total_debit_count'],
                'total_credit_amount' => $parsedData['total_credit_amount'],
                'total_debit_amount' => $parsedData['total_debit_amount'],
                'total_transactions' => count($parsedData['transactions']),
                'processed_transactions' => 0,
            ]);

            // Insert transactions
            $transactionsInserted = 0;
            foreach ($parsedData['transactions'] as $transaction) {
                if (empty($transaction['transaction_date'])) {
                    Log::warning("Skipping transaction without date", $transaction);
                    continue;
                }

                StatementTransaction::create([
                    'bank_statement_id' => $this->bankStatement->id,
                    'transaction_date' => $transaction['transaction_date'],
                    'transaction_time' => $transaction['transaction_time'],
                    'value_date' => $transaction['value_date'],
                    'branch_code' => $transaction['branch_code'],
                    'description' => $transaction['description'],
                    'reference_no' => $transaction['reference_no'],
                    'debit_amount' => $transaction['debit_amount'],
                    'credit_amount' => $transaction['credit_amount'],
                    'balance' => $transaction['balance'],
                    'transaction_type' => $transaction['transaction_type'],
                    'amount' => $transaction['amount'],
                ]);

                $transactionsInserted++;
            }

            // Update processed count
            $this->bankStatement->update([
                'processed_transactions' => $transactionsInserted,
            ]);

            DB::commit();

            Log::info("Successfully processed OCR for Bank Statement ID: {$this->bankStatement->id}, Transactions: {$transactionsInserted}");

            // Dispatch matching job after successful OCR processing
            ProcessTransactionMatching::dispatch($this->bankStatement);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("OCR Processing failed for Bank Statement ID: {$this->bankStatement->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->bankStatement->update([
                'ocr_status' => 'failed',
                'ocr_error' => $e->getMessage(),
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
        Log::error("Job failed permanently for Bank Statement ID: {$this->bankStatement->id}", [
            'error' => $exception->getMessage(),
        ]);

        $this->bankStatement->update([
            'ocr_status' => 'failed',
            'ocr_error' => "Job failed after {$this->tries} attempts: " . $exception->getMessage(),
        ]);
    }
}