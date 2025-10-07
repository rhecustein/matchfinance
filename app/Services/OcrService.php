<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\StatementTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.ocr.url');
        $this->apiKey = config('services.ocr.key');
    }

    /**
     * Process OCR for a bank statement
     */
    public function processStatement(BankStatement $statement): bool
    {
        $statement->markAsProcessing();

        try {
            // Get file from storage
            $filePath = Storage::path($statement->file_path);
            
            // Send to OCR API
            $response = $this->sendToOcrApi($filePath);

            if (!$response || !isset($response['success']) || !$response['success']) {
                throw new \Exception($response['message'] ?? 'OCR processing failed');
            }

            // Save OCR response
            $statement->markAsCompleted($response);

            // Parse and create transactions
            $this->createTransactionsFromOcr($statement, $response['data']);

            return true;

        } catch (\Exception $e) {
            Log::error('OCR processing failed', [
                'statement_id' => $statement->id,
                'error' => $e->getMessage()
            ]);

            $statement->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Send file to external OCR API
     */
    private function sendToOcrApi(string $filePath): ?array
    {
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($this->apiUrl);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('OCR API request failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create transactions from OCR data
     */
    private function createTransactionsFromOcr(BankStatement $statement, array $ocrData): void
    {
        if (!isset($ocrData['transactions']) || !is_array($ocrData['transactions'])) {
            throw new \Exception('Invalid OCR data format');
        }

        foreach ($ocrData['transactions'] as $txn) {
            StatementTransaction::create([
                'bank_statement_id' => $statement->id,
                'transaction_date' => $this->parseDate($txn['date'] ?? null),
                'description' => $txn['description'] ?? '',
                'amount' => $this->parseAmount($txn['amount'] ?? 0),
                'balance' => $this->parseAmount($txn['balance'] ?? null),
                'transaction_type' => $this->determineTransactionType($txn),
                'confidence_score' => 0,
            ]);
        }

        // Update statement period
        if (isset($ocrData['period_start']) && isset($ocrData['period_end'])) {
            $statement->update([
                'statement_period_start' => $this->parseDate($ocrData['period_start']),
                'statement_period_end' => $this->parseDate($ocrData['period_end']),
            ]);
        }
    }

    /**
     * Parse date from various formats
     */
    private function parseDate(?string $date): ?\Carbon\Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse amount string to decimal
     */
    private function parseAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        // Remove currency symbols and formatting
        $cleaned = preg_replace('/[^0-9,.-]/', '', $amount);
        $cleaned = str_replace(',', '', $cleaned);
        
        return (float) $cleaned;
    }

    /**
     * Determine transaction type (debit/credit)
     */
    private function determineTransactionType(array $txn): string
    {
        if (isset($txn['type'])) {
            return strtolower($txn['type']) === 'credit' ? 'credit' : 'debit';
        }

        // Determine by amount sign
        $amount = $this->parseAmount($txn['amount'] ?? 0);
        return $amount < 0 ? 'debit' : 'credit';
    }

    /**
     * Get mock OCR response for testing
     */
    public function getMockOcrResponse(): array
    {
        return [
            'success' => true,
            'message' => 'OCR processed successfully',
            'data' => [
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-31',
                'transactions' => [
                    [
                        'date' => '2024-01-05',
                        'description' => 'KR OTOMATIS TANGGAL :31/07 MID : 885000156348 APOTEK KIMIA FARMA QR : 1684007.00 DDR: 11788.03',
                        'amount' => -1684007.00,
                        'balance' => 11788.03,
                        'type' => 'debit'
                    ],
                    [
                        'date' => '2024-01-10',
                        'description' => 'TRANSFER DARI JOHN DOE REF: TRF20240110001',
                        'amount' => 5000000.00,
                        'balance' => 5011788.03,
                        'type' => 'credit'
                    ],
                ]
            ]
        ];
    }
}