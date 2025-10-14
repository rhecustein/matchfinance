<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CIMBParser extends BaseBankParser
{
    protected string $bankName = 'CIMB';
    
    /**
     * Parse OCR data dari CIMB bank statement
     * 
     * @param array $ocrData
     * @return array
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        Log::info('CIMBParser: Starting parse', [
            'bank' => $this->bankName,
            'period_from' => $ocr['PeriodFrom'] ?? null,
            'period_to' => $ocr['PeriodTo'] ?? null,
            'account_no' => $ocr['AccountNo'] ?? null,
            'branch' => $ocr['Branch'] ?? null,
            'table_data_count' => isset($ocr['TableData']) ? count($ocr['TableData']) : 0
        ]);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parseCIMBPeriodDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseCIMBPeriodDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $ocr['AccountNo'] ?? null,
            'account_holder_name' => null, // CIMB tidak provide di OCR response
            'currency' => $ocr['Currency'] ?? 'IDR',
            'branch_code' => $this->extractBranchCode($ocr['Branch'] ?? null),
            'branch_name' => $ocr['Branch'] ?? null, // CIMB provide full branch/account name
            'opening_balance' => $this->parseAmount($ocr['OpeningBalance'] ?? '0'),
            'closing_balance' => $this->parseAmount($ocr['ClosingBalance'] ?? '0'),
            'total_credit_count' => !empty($ocr['CreditNo']) ? (int) $ocr['CreditNo'] : null,
            'total_debit_count' => !empty($ocr['DebitNo']) ? (int) $ocr['DebitNo'] : null,
            'total_credit_amount' => $this->parseAmount($ocr['TotalAmountCredited'] ?? '0'),
            'total_debit_amount' => $this->parseAmount($ocr['TotalAmountDebited'] ?? '0'),
            'transactions' => $this->parseTransactions($ocr['TableData'] ?? []),
        ];
    }
    
    /**
     * Parse transactions dari CIMB OCR response
     * 
     * ✅ FIXED dari kode lama:
     * 1. CIMB date format MM/DD/YY (US FORMAT!) - critical difference!
     * 2. Loop langsung $tableData, BUKAN $data['transactions']
     * 3. Gunakan parseCIMBDate() untuk handle US format
     * 4. Time format HH:MM (tanpa seconds)
     * 5. Clean description (remove duplicate date+time di awal)
     * 6. Konsisten naming: debitAmount, creditAmount, transactionDate
     * 7. Tambah validation sebelum push ke array
     * 
     * @param array $tableData
     * @return array
     */
    public function parseTransactions(array $tableData): array
    {
        $transactions = [];
        
        Log::info('CIMBParser: Parsing transactions', [
            'bank' => $this->bankName,
            'table_data_count' => count($tableData),
            'has_data' => !empty($tableData)
        ]);
        
        // ✅ FIX: Loop langsung dari $tableData, BUKAN $tableData['transactions']
        foreach ($tableData as $index => $row) {
            // ✅ CRITICAL: CIMB menggunakan US date format (MM/DD/YY)!
            $transactionDate = $this->parseCIMBDate($row['Date'] ?? null);
            
            // ✅ Parse ValueDate jika ada, fallback ke transaction_date
            $valueDate = !empty($row['ValueDate']) 
                ? $this->parseCIMBDate($row['ValueDate'])
                : $transactionDate;
            
            // Parse amount dengan naming konsisten
            $debitAmount = $this->parseAmount($row['Debit'] ?? '0');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0');
            
            // Determine amount and type
            $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
            $transactionType = $creditAmount > 0 ? 'credit' : 'debit';
            
            // ✅ Parse time - CIMB format: HH:MM (tanpa seconds)
            $transactionTime = $this->parseCIMBTime($row['Time'] ?? null);
            
            // ✅ Clean description - CIMB ada duplicate date+time di awal description
            $description = $this->cleanCIMBDescription($row['Description'] ?? '');
            
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
                
                Log::debug('CIMBParser: Transaction parsed', [
                    'index' => $index,
                    'date_original' => $row['Date'] ?? null,
                    'date_parsed' => $transaction['transaction_date'],
                    'time' => $transactionTime,
                    'type' => $transactionType,
                    'amount' => $amount,
                    'description_preview' => substr($description, 0, 50)
                ]);
            } else {
                Log::warning('CIMBParser: Transaction validation failed', [
                    'index' => $index,
                    'row' => $row,
                    'parsed_transaction' => $transaction
                ]);
            }
        }
        
        Log::info('CIMBParser: Transactions parsed successfully', [
            'bank' => $this->bankName,
            'total_parsed' => count($transactions),
            'total_input' => count($tableData)
        ]);
        
        return $transactions;
    }
    
    /**
     * Parse period date dengan format CIMB: "01-Jan-2024"
     * 
     * CIMB period menggunakan format DD-MMM-YYYY (sama dengan BNI/BTN)
     * Contoh: "01-Jan-2024", "31-Jan-2024"
     * 
     * @param string|null $date
     * @return Carbon|null
     */
    private function parseCIMBPeriodDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $cleaned = trim($date);
            
            // CIMB period format: "01-Jan-2024" (DD-MMM-YYYY)
            if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $cleaned)) {
                $parsed = Carbon::createFromFormat('d-M-Y', $cleaned);
                
                Log::debug('CIMBParser: Period date parsed', [
                    'original' => $date,
                    'parsed' => $parsed->format('Y-m-d')
                ]);
                
                return $parsed;
            }
            
            // Fallback
            return $this->parseDate($date);
            
        } catch (\Exception $e) {
            Log::warning('CIMBParser: Failed to parse period date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Last resort
            try {
                return Carbon::parse($date);
            } catch (\Exception $e2) {
                Log::error('CIMBParser: All period date parsing attempts failed', [
                    'date' => $date,
                    'error' => $e2->getMessage()
                ]);
                return null;
            }
        }
    }
    
    /**
     * Parse CIMB transaction date format: "01/31/24" (MM/DD/YY - US FORMAT!)
     * 
     * 🚨 CRITICAL: CIMB menggunakan US date format (MM/DD/YY), berbeda dengan bank lain!
     * - CIMB: "01/31/24" = January 31, 2024 (Month/Day/Year)
     * - Others: "31/01/24" = January 31, 2024 (Day/Month/Year)
     * 
     * @param string|null $date
     * @return Carbon|null
     */
    private function parseCIMBDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $cleaned = trim($date);
            
            // CIMB format: "01/31/24" (MM/DD/YY dengan 2 digit year, US format!)
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $cleaned)) {
                $parts = explode('/', $cleaned);
                $month = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $day = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $yearShort = $parts[2];
                
                // Convert 2 digit year ke 4 digit
                // Asumsi: 00-49 = 2000-2049, 50-99 = 1950-1999
                $yearFull = (int)$yearShort < 50 ? '20' . $yearShort : '19' . $yearShort;
                
                // Build date dengan format US: MM/DD/YYYY
                $fullDate = "{$month}/{$day}/{$yearFull}";
                $parsed = Carbon::createFromFormat('m/d/Y', $fullDate);
                
                Log::debug('CIMBParser: US format date parsed', [
                    'original' => $date,
                    'month' => $month,
                    'day' => $day,
                    'year_short' => $yearShort,
                    'year_full' => $yearFull,
                    'parsed' => $parsed->format('Y-m-d')
                ]);
                
                return $parsed;
            }
            
            // Fallback
            return $this->parseDate($date);
            
        } catch (\Exception $e) {
            Log::warning('CIMBParser: Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Last resort
            try {
                return Carbon::parse($date);
            } catch (\Exception $e2) {
                Log::error('CIMBParser: All date parsing attempts failed', [
                    'date' => $date,
                    'error' => $e2->getMessage()
                ]);
                return null;
            }
        }
    }
    
    /**
     * Parse time dengan format CIMB: "23:16" (HH:MM tanpa seconds)
     * 
     * CIMB hanya provide hours dan minutes, perlu tambah seconds ":00"
     * 
     * @param string|null $time
     * @return string|null
     */
    private function parseCIMBTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }
        
        try {
            $cleaned = trim($time);
            
            // CIMB format: "23:16" (HH:MM tanpa seconds)
            if (preg_match('/^\d{2}:\d{2}$/', $cleaned)) {
                $withSeconds = $cleaned . ':00';
                
                Log::debug('CIMBParser: Time parsed', [
                    'original' => $time,
                    'with_seconds' => $withSeconds
                ]);
                
                return $withSeconds;
            }
            
            // Already has seconds or other format
            return $this->parseTime($time);
            
        } catch (\Exception $e) {
            Log::warning('CIMBParser: Failed to parse time', [
                'time' => $time,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Clean description khusus CIMB
     * 
     * CIMB ada duplicate date+time di awal description
     * Contoh: "01/31/24 23:16 ATM Prima Purchase QR..."
     * Result: "ATM Prima Purchase QR..."
     * 
     * @param string $description
     * @return string
     */
    private function cleanCIMBDescription(string $description): string
    {
        // First: clean dengan method dari parent
        $cleaned = $this->cleanDescription($description);
        
        // CIMB specific: Remove date+time pattern di awal
        // Pattern: "01/31/24 23:16 text..." → "text..."
        $cleaned = preg_replace('/^\d{2}\/\d{2}\/\d{2}\s+\d{2}:\d{2}\s+/', '', $cleaned);
        
        // Re-trim setelah remove date+time
        $cleaned = trim($cleaned);
        
        Log::debug('CIMBParser: Description cleaned', [
            'original_length' => strlen($description),
            'cleaned_length' => strlen($cleaned),
            'removed_datetime' => $description !== $cleaned
        ]);
        
        return $cleaned;
    }
    
    /**
     * Extract branch code dari branch name
     * 
     * CIMB format bisa bermacam-macam, extract first word
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
        
        Log::debug('CIMBParser: Branch code extracted', [
            'branch_name' => $branchName,
            'branch_code' => $branchCode
        ]);
        
        return $branchCode;
    }
}