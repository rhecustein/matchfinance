<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BNIParser extends BaseBankParser
{
    protected string $bankName = 'BNI';
    
    /**
     * Parse OCR data dari BNI bank statement
     * 
     * @param array $ocrData
     * @return array
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        Log::info('BNIParser: Starting parse', [
            'bank' => $this->bankName,
            'period_from' => $ocr['PeriodFrom'] ?? null,
            'period_to' => $ocr['PeriodTo'] ?? null,
            'account_no' => $ocr['AccountNo'] ?? null,
            'table_data_count' => isset($ocr['TableData']) ? count($ocr['TableData']) : 0
        ]);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parsePeriodDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parsePeriodDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $ocr['AccountNo'] ?? null,
            'account_holder_name' => null, // BNI tidak provide di OCR response
            'currency' => $ocr['Currency'] ?? 'IDR',
            'branch_code' => $ocr['Branch'] ?? null,
            'branch_name' => null,
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
     * Parse transactions dari BNI OCR response
     * 
     * ✅ FIXED dari kode lama:
     * 1. BNI date sudah lengkap (DD/MM/YYYY), tidak perlu parameter $year
     * 2. Loop langsung $tableData, BUKAN $data['transactions']
     * 3. Parse time format BNI (HH.MM.SS dengan titik)
     * 4. Handle ValueDate field terpisah
     * 5. Konsisten naming: debitAmount, creditAmount, transactionDate
     * 6. Tambah validation sebelum push ke array
     * 
     * @param array $tableData
     * @return array
     */
    public function parseTransactions(array $tableData): array
    {
        $transactions = [];
        
        Log::info('BNIParser: Parsing transactions', [
            'bank' => $this->bankName,
            'table_data_count' => count($tableData),
            'has_data' => !empty($tableData)
        ]);
        
        // ✅ FIX: Loop langsung dari $tableData, BUKAN $tableData['transactions']
        foreach ($tableData as $index => $row) {
            // ✅ BNI date sudah lengkap: "01/08/2024" (DD/MM/YYYY)
            $transactionDate = $this->parseDate($row['Date'] ?? null);
            
            // ✅ Parse ValueDate jika ada, fallback ke transaction_date
            $valueDate = !empty($row['ValueDate']) 
                ? $this->parseDate($row['ValueDate']) 
                : $transactionDate;
            
            // Parse amount dengan naming konsisten
            $debitAmount = $this->parseAmount($row['Debit'] ?? '0');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0');
            
            // Determine amount and type
            $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
            $transactionType = $creditAmount > 0 ? 'credit' : 'debit';
            
            // ✅ Parse time dengan format BNI (HH.MM.SS pakai titik)
            $transactionTime = $this->parseBNITime($row['Time'] ?? null);
            
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
                
                Log::debug('BNIParser: Transaction parsed', [
                    'index' => $index,
                    'date' => $transaction['transaction_date'],
                    'time' => $transactionTime,
                    'type' => $transactionType,
                    'amount' => $amount,
                    'description_preview' => substr($transaction['description'], 0, 50)
                ]);
            } else {
                Log::warning('BNIParser: Transaction validation failed', [
                    'index' => $index,
                    'row' => $row,
                    'parsed_transaction' => $transaction
                ]);
            }
        }
        
        Log::info('BNIParser: Transactions parsed successfully', [
            'bank' => $this->bankName,
            'total_parsed' => count($transactions),
            'total_input' => count($tableData)
        ]);
        
        return $transactions;
    }
    
    /**
     * Parse period date dengan format BNI khusus: "01-Aug-2024"
     * 
     * BNI menggunakan format DD-MMM-YYYY untuk PeriodFrom dan PeriodTo
     * Contoh: "01-Aug-2024", "31-Aug-2024"
     * 
     * @param string|null $date
     * @return Carbon|null
     */
    private function parsePeriodDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $cleaned = trim($date);
            
            // BNI period format: "01-Aug-2024" (DD-MMM-YYYY)
            if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $cleaned)) {
                $parsed = Carbon::createFromFormat('d-M-Y', $cleaned);
                
                Log::debug('BNIParser: Period date parsed', [
                    'original' => $date,
                    'parsed' => $parsed->format('Y-m-d')
                ]);
                
                return $parsed;
            }
            
            // Fallback: Coba format lain (DD/MM/YYYY, YYYY-MM-DD, dll)
            return $this->parseDate($date);
            
        } catch (\Exception $e) {
            Log::warning('BNIParser: Failed to parse period date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Last resort: coba parse dengan Carbon default
            try {
                return Carbon::parse($date);
            } catch (\Exception $e2) {
                Log::error('BNIParser: All period date parsing attempts failed', [
                    'date' => $date,
                    'error' => $e2->getMessage()
                ]);
                return null;
            }
        }
    }
    
    /**
     * Parse time dengan format BNI: "07.21.07" (HH.MM.SS dengan titik)
     * 
     * BNI menggunakan titik sebagai separator, bukan colon
     * Contoh: "07.21.07" → "07:21:07"
     *         "08.00.00" → "08:00:00"
     * 
     * @param string|null $time
     * @return string|null
     */
    private function parseBNITime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }
        
        try {
            $cleaned = trim($time);
            
            // BNI format: "07.21.07" (titik sebagai separator)
            // Konversi ke format standar: "07:21:07"
            $normalized = str_replace('.', ':', $cleaned);
            
            // Validate format HH:MM:SS
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $normalized)) {
                Log::debug('BNIParser: Time parsed', [
                    'original' => $time,
                    'normalized' => $normalized
                ]);
                return $normalized;
            }
            
            // Fallback: gunakan parseTime dari BaseBankParser
            $fallback = $this->parseTime($time);
            
            if ($fallback) {
                Log::debug('BNIParser: Time parsed via fallback', [
                    'original' => $time,
                    'result' => $fallback
                ]);
            }
            
            return $fallback;
            
        } catch (\Exception $e) {
            Log::warning('BNIParser: Failed to parse time', [
                'time' => $time,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}