<?php

namespace App\Services\BankParsers;

use Illuminate\Support\Facades\Log;

class BCAParser extends BaseBankParser
{
    protected string $bankName = 'BCA';
    
    /**
     * Parse OCR data dari BCA bank statement
     * 
     * @param array $ocrData
     * @return array
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Extract year dari period untuk parsing date transaksi
        $year = $this->extractYear($ocr);
        
        Log::info('BCAParser: Starting parse', [
            'bank' => $this->bankName,
            'period_from' => $ocr['PeriodFrom'] ?? null,
            'period_to' => $ocr['PeriodTo'] ?? null,
            'year_extracted' => $year,
            'account_no' => $ocr['AccountNo'] ?? null
        ]);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parseDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $ocr['AccountNo'] ?? null,
            'account_holder_name' => null, // BCA tidak provide di OCR response
            'currency' => $this->normalizeCurrency($ocr['Currency'] ?? 'IDR'),
            'branch_code' => $ocr['Branch'] ?? null,
            'branch_name' => null,
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
     * Parse transactions dari BCA OCR response
     * 
     * ✅ FIXED: 
     * - Tambah parameter $year untuk parsing date DD/MM
     * - Fix structure: langsung loop TableData, bukan cari nested 'transactions'
     * - Pass $year ke parseDate()
     * - Konsisten naming variable
     * 
     * @param array $tableData
     * @param string|null $year
     * @return array
     */
    public function parseTransactions(array $tableData, ?string $year = null): array
    {
        $transactions = [];
        
        Log::info('BCAParser: Parsing transactions', [
            'bank' => $this->bankName,
            'table_data_count' => count($tableData),
            'year' => $year,
            'has_data' => !empty($tableData)
        ]);
        
        // ✅ FIX: Loop langsung dari $tableData, bukan $tableData['transactions']
        foreach ($tableData as $index => $row) {
            // ✅ FIX: Pass $year ke parseDate untuk handle format DD/MM
            $transactionDate = $this->parseDate($row['Date'] ?? null, $year);
            
            // Parse amount
            $debitAmount = $this->parseAmount($row['Debit'] ?? '0');
            $creditAmount = $this->parseAmount($row['Credit'] ?? '0');
            
            // Determine amount and type
            $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
            $transactionType = $creditAmount > 0 ? 'credit' : 'debit';
            
            // Build transaction record
            $transaction = [
                'transaction_date' => $this->formatDate($transactionDate),
                'transaction_time' => $this->parseTime($row['Time'] ?? null),
                'value_date' => $this->formatDate($transactionDate), // BCA: value_date = transaction_date
                'branch_code' => $row['Branch'] ?? null,
                'description' => $this->cleanDescription($row['Description'] ?? ''),
                'reference_no' => $row['ReferenceNo'] ?? null,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'balance' => $this->parseAmount($row['Balance'] ?? '0'),
                'amount' => abs($amount),
                'transaction_type' => $transactionType,
            ];
            
            // Validate before adding
            if ($this->validateTransaction($transaction)) {
                $transactions[] = $transaction;
                
                Log::debug('BCAParser: Transaction parsed', [
                    'index' => $index,
                    'date' => $transaction['transaction_date'],
                    'type' => $transactionType,
                    'amount' => $amount,
                    'description_preview' => substr($transaction['description'], 0, 50)
                ]);
            } else {
                Log::warning('BCAParser: Transaction validation failed', [
                    'index' => $index,
                    'row' => $row
                ]);
            }
        }
        
        Log::info('BCAParser: Transactions parsed successfully', [
            'bank' => $this->bankName,
            'total_parsed' => count($transactions),
            'total_input' => count($tableData)
        ]);
        
        return $transactions;
    }
    
    /**
     * Normalize currency format dari BCA
     * BCA menggunakan "Rp" di OCR, perlu dikonversi ke "IDR"
     * 
     * @param string|null $currency
     * @return string
     */
    private function normalizeCurrency(?string $currency): string
    {
        // Map BCA currency format ke standard ISO 4217
        $currencyMap = [
            'Rp' => 'IDR',
            'rp' => 'IDR',
            'IDR' => 'IDR',
            'USD' => 'USD',
            '$' => 'USD',
        ];
        
        $normalized = $currencyMap[$currency] ?? 'IDR';
        
        Log::debug('BCAParser: Currency normalized', [
            'original' => $currency,
            'normalized' => $normalized
        ]);
        
        return $normalized;
    }
}