<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BRIParser extends BaseBankParser
{
    protected string $bankName = 'BRI';
    
    /**
     * Parse OCR data dari BRI bank statement
     * 
     * @param array $ocrData
     * @return array
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Extract year dari period untuk parsing date transaksi
        // BRI menggunakan format 2 digit year (DD/MM/YY)
        $year = $this->extractYear($ocr);
        
        Log::info('BRIParser: Starting parse', [
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
            'period_from' => $this->formatDate($this->parseBRIDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseBRIDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $ocr['AccountNo'] ?? null,
            'account_holder_name' => null, // BRI tidak provide di OCR response
            'currency' => $ocr['Currency'] ?? 'IDR',
            'branch_code' => $this->extractBranchCode($ocr['Branch'] ?? null),
            'branch_name' => $ocr['Branch'] ?? null, // BRI provide full branch name
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
     * Parse transactions dari BRI OCR response
     * 
     * ✅ FIXED dari kode lama:
     * 1. BRI date format DD/MM/YY (2 digit year), perlu parameter $year
     * 2. Loop langsung $tableData, BUKAN $data['transactions']
     * 3. Parse date dengan year untuk convert YY → YYYY
     * 4. Time sudah standard (HH:MM:SS)
     * 5. Konsisten naming: debitAmount, creditAmount, transactionDate
     * 6. Tambah validation sebelum push ke array
     * 
     * @param array $tableData
     * @param string|null $year
     * @return array
     */
    public function parseTransactions(array $tableData, ?string $year = null): array
    {
        $transactions = [];
        
        Log::info('BRIParser: Parsing transactions', [
            'bank' => $this->bankName,
            'table_data_count' => count($tableData),
            'year' => $year,
            'has_data' => !empty($tableData)
        ]);
        
        // ✅ FIX: Loop langsung dari $tableData, BUKAN $tableData['transactions']
        foreach ($tableData as $index => $row) {
            // ✅ BRI date format: "01/01/24" (DD/MM/YY) - perlu year untuk convert ke 2024
            $transactionDate = $this->parseBRIDate($row['Date'] ?? null, $year);
            
            // ✅ Parse ValueDate jika ada, fallback ke transaction_date
            $valueDate = !empty($row['ValueDate']) 
                ? $this->parseBRIDate($row['ValueDate'], $year)
                : $transactionDate;
            
            // Parse amount dengan naming konsisten
            $debitAmount = $this->parseAmount($row['Debit'] ?? '0');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0');
            
            // Determine amount and type
            $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
            $transactionType = $creditAmount > 0 ? 'credit' : 'debit';
            
            // Parse time - BRI sudah standard format HH:MM:SS
            $transactionTime = $this->parseTime($row['Time'] ?? null);
            
            // Build transaction record
            $transaction = [
                'transaction_date' => $this->formatDate($transactionDate),
                'transaction_time' => $transactionTime,
                'value_date' => $this->formatDate($valueDate),
                'branch_code' => $row['Branch'] ?? null,
                'description' => $this->cleanDescription($row['Description'] ?? ''),
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
                
                Log::debug('BRIParser: Transaction parsed', [
                    'index' => $index,
                    'date' => $transaction['transaction_date'],
                    'time' => $transactionTime,
                    'type' => $transactionType,
                    'amount' => $amount,
                    'description_preview' => substr($transaction['description'], 0, 50)
                ]);
            } else {
                Log::warning('BRIParser: Transaction validation failed', [
                    'index' => $index,
                    'row' => $row,
                    'parsed_transaction' => $transaction
                ]);
            }
        }
        
        Log::info('BRIParser: Transactions parsed successfully', [
            'bank' => $this->bankName,
            'total_parsed' => count($transactions),
            'total_input' => count($tableData)
        ]);
        
        return $transactions;
    }
    
    /**
     * Parse date dengan format BRI khusus: "01/01/24" (DD/MM/YY)
     * 
     * BRI menggunakan 2 digit year, perlu convert ke 4 digit
     * Contoh: "01/01/24" → "2024-01-01"
     * 
     * @param string|null $date
     * @param string|null $year Optional year untuk fallback
     * @return Carbon|null
     */
    private function parseBRIDate(?string $date, ?string $year = null): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $cleaned = trim($date);
            
            // BRI format: "01/01/24" (DD/MM/YY dengan 2 digit year)
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $cleaned)) {
                $parts = explode('/', $cleaned);
                $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $yearShort = $parts[2];
                
                // Convert 2 digit year ke 4 digit
                // Asumsi: 00-49 = 2000-2049, 50-99 = 1950-1999
                $yearFull = (int)$yearShort < 50 ? '20' . $yearShort : '19' . $yearShort;
                
                $fullDate = "{$day}/{$month}/{$yearFull}";
                $parsed = Carbon::createFromFormat('d/m/Y', $fullDate);
                
                Log::debug('BRIParser: Date parsed', [
                    'original' => $date,
                    'year_short' => $yearShort,
                    'year_full' => $yearFull,
                    'parsed' => $parsed->format('Y-m-d')
                ]);
                
                return $parsed;
            }
            
            // Format lain: "01/01" tanpa year
            if (preg_match('/^\d{1,2}\/\d{1,2}$/', $cleaned) && $year) {
                $fullDate = $cleaned . '/' . $year;
                return Carbon::createFromFormat('d/m/Y', $fullDate);
            }
            
            // Fallback: gunakan parseDate dari BaseBankParser
            return $this->parseDate($date, $year);
            
        } catch (\Exception $e) {
            Log::warning('BRIParser: Failed to parse date', [
                'date' => $date,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            
            // Last resort
            try {
                return Carbon::parse($date);
            } catch (\Exception $e2) {
                Log::error('BRIParser: All date parsing attempts failed', [
                    'date' => $date,
                    'error' => $e2->getMessage()
                ]);
                return null;
            }
        }
    }
    
    /**
     * Extract branch code dari branch name
     * 
     * BRI format: "KC Jakarta Veteran"
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
        
        // Extract first word sebagai branch code
        $parts = explode(' ', trim($branchName), 2);
        $branchCode = $parts[0] ?? null;
        
        Log::debug('BRIParser: Branch code extracted', [
            'branch_name' => $branchName,
            'branch_code' => $branchCode
        ]);
        
        return $branchCode;
    }
}