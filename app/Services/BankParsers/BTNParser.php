<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BTNParser extends BaseBankParser
{
    protected string $bankName = 'BTN';
    
    /**
     * Parse OCR data dari BTN bank statement
     * 
     * @param array $ocrData
     * @return array
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Extract year dari period untuk parsing date transaksi
        // BTN date format: DD/MM (tanpa year), perlu year dari period
        $year = $this->extractYear($ocr);
        
        Log::info('BTNParser: Starting parse', [
            'bank' => $this->bankName,
            'period_from' => $ocr['PeriodFrom'] ?? null,
            'period_to' => $ocr['PeriodTo'] ?? null,
            'year_extracted' => $year,
            'account_no' => $ocr['AccountNo'] ?? null,
            'branch' => $ocr['Branch'] ?? null,
            'table_data_count' => isset($ocr['TableData']) ? count($ocr['TableData']) : 0
        ]);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parseBTNPeriodDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseBTNPeriodDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $ocr['AccountNo'] ?? null,
            'account_holder_name' => null, // BTN tidak provide di OCR response
            'currency' => $ocr['Currency'] ?? 'IDR',
            'branch_code' => $this->extractBranchCode($ocr['Branch'] ?? null),
            'branch_name' => $ocr['Branch'] ?? null, // BTN provide full branch name
            'opening_balance' => $this->parseAmount($ocr['OpeningBalance'] ?? '0'),
            'closing_balance' => $this->parseAmount($ocr['ClosingBalance'] ?? '0'),
            'total_credit_count' => !empty($ocr['CreditNo']) ? (int) $ocr['CreditNo'] : null,
            'total_debit_count' => !empty($ocr['DebitNo']) ? (int) $ocr['DebitNo'] : null,
            'total_credit_amount' => $this->parseAmount($ocr['TotalAmountCredited'] ?? '0'),
            'total_debit_amount' => $this->parseAmount($ocr['TotalAmountDebited'] ?? '0'),
            'transactions' => $this->parseTransactions($ocr['TableData'] ?? [], $year),
        ];
    }
    
    /**
     * Parse transactions dari BTN OCR response
     * 
     * ✅ FIXED dari kode lama:
     * 1. BTN date format DD/MM (tanpa year), perlu parameter $year
     * 2. Loop langsung $tableData, BUKAN $data['transactions']
     * 3. Parse date dengan year (DD/MM + year → full date)
     * 4. Clean description (remove duplicate time di awal)
     * 5. Time sudah standard (HH:MM:SS)
     * 6. Konsisten naming: debitAmount, creditAmount, transactionDate
     * 7. Tambah validation sebelum push ke array
     * 
     * @param array $tableData
     * @param string|null $year
     * @return array
     */
    public function parseTransactions(array $tableData, ?string $year = null): array
    {
        $transactions = [];
        
        Log::info('BTNParser: Parsing transactions', [
            'bank' => $this->bankName,
            'table_data_count' => count($tableData),
            'year' => $year,
            'has_data' => !empty($tableData)
        ]);
        
        // ✅ FIX: Loop langsung dari $tableData, BUKAN $tableData['transactions']
        foreach ($tableData as $index => $row) {
            // ✅ BTN date format: "01/01" (DD/MM tanpa year) - perlu year untuk complete date
            $transactionDate = $this->parseDate($row['Date'] ?? null, $year);
            
            // ✅ Parse ValueDate jika ada, fallback ke transaction_date
            $valueDate = !empty($row['ValueDate']) 
                ? $this->parseDate($row['ValueDate'], $year)
                : $transactionDate;
            
            // Parse amount dengan naming konsisten
            $debitAmount = $this->parseAmount($row['Debit'] ?? '0');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0');
            
            // Determine amount and type
            $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
            $transactionType = $creditAmount > 0 ? 'credit' : 'debit';
            
            // Parse time - BTN sudah standard format HH:MM:SS
            $transactionTime = $this->parseTime($row['Time'] ?? null);
            
            // ✅ Clean description - BTN sering ada duplicate time di awal description
            $description = $this->cleanBTNDescription($row['Description'] ?? '');
            
            // Build transaction record
            $transaction = [
                'transaction_date' => $this->formatDate($transactionDate),
                'transaction_time' => $transactionTime,
                'value_date' => $this->formatDate($valueDate),
                'branch_code' => $row['Branch'] ?? null,
                'description' => $description,
                'reference_no' => $row['ReferenceNo'] ?? null,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'balance' => $this->parseAmount($row['Balance'] ?? '0'),
                'amount' => abs($amount),
                'transaction_type' => $transactionType,
            ];
            
            // ✅ Validate sebelum adding (required: transaction_date, description, amount, transaction_type)
            if ($this->validateTransaction($transaction)) {
                $transactions[] = $transaction;
                
                Log::debug('BTNParser: Transaction parsed', [
                    'index' => $index,
                    'date' => $transaction['transaction_date'],
                    'time' => $transactionTime,
                    'type' => $transactionType,
                    'amount' => $amount,
                    'description_preview' => substr($description, 0, 50)
                ]);
            } else {
                Log::warning('BTNParser: Transaction validation failed', [
                    'index' => $index,
                    'row' => $row,
                    'parsed_transaction' => $transaction
                ]);
            }
        }
        
        Log::info('BTNParser: Transactions parsed successfully', [
            'bank' => $this->bankName,
            'total_parsed' => count($transactions),
            'total_input' => count($tableData)
        ]);
        
        return $transactions;
    }
    
    /**
     * Parse period date dengan format BTN khusus: "01-Jan-2024"
     * 
     * BTN menggunakan format DD-MMM-YYYY untuk PeriodFrom dan PeriodTo
     * Contoh: "01-Jan-2024", "31-Jan-2024"
     * 
     * @param string|null $date
     * @return Carbon|null
     */
    private function parseBTNPeriodDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $cleaned = trim($date);
            
            // BTN period format: "01-Jan-2024" (DD-MMM-YYYY)
            // Sama dengan BNI format
            if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $cleaned)) {
                $parsed = Carbon::createFromFormat('d-M-Y', $cleaned);
                
                Log::debug('BTNParser: Period date parsed', [
                    'original' => $date,
                    'parsed' => $parsed->format('Y-m-d')
                ]);
                
                return $parsed;
            }
            
            // Fallback: Coba format lain
            return $this->parseDate($date);
            
        } catch (\Exception $e) {
            Log::warning('BTNParser: Failed to parse period date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Last resort: coba parse dengan Carbon default
            try {
                return Carbon::parse($date);
            } catch (\Exception $e2) {
                Log::error('BTNParser: All period date parsing attempts failed', [
                    'date' => $date,
                    'error' => $e2->getMessage()
                ]);
                return null;
            }
        }
    }
    
    /**
     * Clean description khusus BTN
     * 
     * BTN sering ada duplicate time di awal description
     * Contoh: "02:01:52 200714638693874APOTEK KIMIA FARMA..."
     * Result: "200714638693874APOTEK KIMIA FARMA..."
     * 
     * @param string $description
     * @return string
     */
    private function cleanBTNDescription(string $description): string
    {
        // First: clean dengan method dari parent
        $cleaned = $this->cleanDescription($description);
        
        // BTN specific: Remove time pattern di awal (HH:MM:SS)
        // Pattern: "02:01:52 text..." → "text..."
        $cleaned = preg_replace('/^\d{2}:\d{2}:\d{2}\s+/', '', $cleaned);
        
        // Re-trim setelah remove time
        $cleaned = trim($cleaned);
        
        Log::debug('BTNParser: Description cleaned', [
            'original_length' => strlen($description),
            'cleaned_length' => strlen($cleaned),
            'removed_time' => $description !== $cleaned
        ]);
        
        return $cleaned;
    }
    
    /**
     * Extract branch code dari branch name
     * 
     * BTN format: "KC Jakarta Harmoni"
     * Extract: "KC" sebagai branch code
     * 
     * @param string|null $branchName
     * @return string|null
     */
    private function extractBranchCode(?string $branchName): ?string
    {
        if (empty($branchName)) {
            return null;
        }
        
        // Extract first word sebagai branch code (sama seperti BRI)
        $parts = explode(' ', trim($branchName), 2);
        $branchCode = $parts[0] ?? null;
        
        Log::debug('BTNParser: Branch code extracted', [
            'branch_name' => $branchName,
            'branch_code' => $branchCode
        ]);
        
        return $branchCode;
    }
}