<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MandiriParser extends BaseBankParser
{
    protected string $bankName = 'Mandiri';
    
    /**
     * Parse OCR data dari Mandiri bank statement
     * 
     * @param array $ocrData
     * @return array
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Parse account info yang kompleks dari Mandiri
        // Format: "1560006875217 IDR KIMIA FARMA APOTIK  BO BEKASI"
        $accountInfo = $this->parseAccountInfo($ocr['AccountNo'] ?? '');
        
        // Handle TableData - support both direct array dan nested format
        $tableData = $ocr['TableData'] ?? $ocr['transactions'] ?? [];
        
        Log::info('MandiriParser: Starting parse', [
            'bank' => $this->bankName,
            'period_from' => $ocr['PeriodFrom'] ?? null,
            'period_to' => $ocr['PeriodTo'] ?? null,
            'account_no' => $accountInfo['number'] ?? null,
            'branch' => $ocr['Branch'] ?? null,
            'has_TableData' => isset($ocr['TableData']),
            'has_transactions' => isset($ocr['transactions']),
            'table_data_count' => is_array($tableData) ? count($tableData) : 0
        ]);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parseMandiriPeriodDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseMandiriPeriodDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $accountInfo['number'],
            'account_holder_name' => $accountInfo['holder_name'],
            'currency' => $accountInfo['currency'] ?? ($ocr['Currency'] ?? 'IDR'),
            'branch_code' => $ocr['Branch'] ?? $accountInfo['branch'],
            'branch_name' => $accountInfo['branch_name'],
            'opening_balance' => $this->parseAmount($ocr['OpeningBalance'] ?? '0'),
            'closing_balance' => $this->parseAmount($ocr['ClosingBalance'] ?? '0'),
            'total_credit_count' => !empty($ocr['CreditNo']) ? (int) $ocr['CreditNo'] : null,
            'total_debit_count' => !empty($ocr['DebitNo']) ? (int) $ocr['DebitNo'] : null,
            'total_credit_amount' => $this->parseAmount($ocr['TotalAmountCredited'] ?? '0'),
            'total_debit_amount' => $this->parseAmount($ocr['TotalAmountDebited'] ?? '0'),
            'transactions' => $this->parseTransactions($tableData),
        ];
    }
    
    /**
     * Parse transactions dari Mandiri OCR response
     * 
     * ✅ IMPROVED dari kode lama:
     * 1. Support both direct array dan nested format
     * 2. Tambah validation sebelum push
     * 3. Konsisten naming: debitAmount, creditAmount, transactionDate
     * 4. Better error handling per transaction
     * 5. More detailed logging
     * 
     * @param array $tableData
     * @return array
     */
    public function parseTransactions(array $tableData): array
    {
        $transactions = [];
        
        Log::info('MandiriParser: Parsing transactions', [
            'bank' => $this->bankName,
            'input_type' => gettype($tableData),
            'input_count' => count($tableData),
            'is_sequential' => array_keys($tableData) === range(0, count($tableData) - 1),
            'first_keys' => !empty($tableData) && is_array($tableData[0]) ? array_keys($tableData[0]) : null
        ]);
        
        // ✅ Handle both formats:
        // Format 1: Direct array [{Date: ...}, {Date: ...}]
        // Format 2: Nested {transactions: [{Date: ...}]}
        $rows = [];
        
        if (isset($tableData['transactions']) && is_array($tableData['transactions'])) {
            // Nested format
            $rows = $tableData['transactions'];
            Log::info('MandiriParser: Using nested format', ['count' => count($rows)]);
        } elseif (isset($tableData[0]) && is_array($tableData[0])) {
            // Direct array format (sequential keys)
            $rows = $tableData;
            Log::info('MandiriParser: Using direct array format', ['count' => count($rows)]);
        } else {
            Log::warning('MandiriParser: Unknown data format', [
                'data_keys' => array_keys($tableData),
                'first_element' => !empty($tableData) ? $tableData[0] : null
            ]);
            return [];
        }
        
        // Process each transaction row
        foreach ($rows as $index => $row) {
            try {
                if (!is_array($row)) {
                    Log::warning('MandiriParser: Skipping non-array row', [
                        'index' => $index,
                        'type' => gettype($row)
                    ]);
                    continue;
                }
                
                // Parse dates - Mandiri sudah full format DD/MM/YYYY
                $transactionDate = $this->parseDate($row['Date'] ?? null);
                $valueDate = !empty($row['ValueDate'])
                    ? $this->parseDate($row['ValueDate'])
                    : $transactionDate;
                
                // Parse amount dengan naming konsisten
                $debitAmount = $this->parseAmount($row['Debit'] ?? '0');
                $creditAmount = $this->parseAmount($row['Credit'] ?? '0');
                
                // Determine amount and type
                $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
                $transactionType = $creditAmount > 0 ? 'credit' : 'debit';
                
                // Parse time - Mandiri sudah standard HH:MM:SS
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
                
                // ✅ Validate sebelum adding
                if ($this->validateTransaction($transaction)) {
                    $transactions[] = $transaction;
                    
                    Log::debug('MandiriParser: Transaction parsed', [
                        'index' => $index,
                        'date' => $transaction['transaction_date'],
                        'time' => $transactionTime,
                        'type' => $transactionType,
                        'amount' => $amount,
                        'description_preview' => substr($transaction['description'], 0, 50)
                    ]);
                } else {
                    Log::warning('MandiriParser: Transaction validation failed', [
                        'index' => $index,
                        'row' => $row,
                        'parsed_transaction' => $transaction
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('MandiriParser: Failed to parse transaction row', [
                    'index' => $index,
                    'row' => $row,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue processing other transactions
            }
        }
        
        Log::info('MandiriParser: Transactions parsed successfully', [
            'bank' => $this->bankName,
            'total_parsed' => count($transactions),
            'total_input' => count($rows)
        ]);
        
        return $transactions;
    }
    
    /**
     * Parse period date dengan format Mandiri: "01 Jan 2024"
     * 
     * Mandiri menggunakan format DD MMM YYYY dengan spasi
     * Contoh: "01 Jan 2024", "31 Jan 2024"
     * 
     * @param string|null $date
     * @return Carbon|null
     */
    private function parseMandiriPeriodDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $cleaned = trim($date);
            
            // Mandiri period format: "01 Jan 2024" (DD MMM YYYY dengan spasi)
            if (preg_match('/^\d{2}\s+[A-Za-z]{3}\s+\d{4}$/', $cleaned)) {
                $parsed = Carbon::createFromFormat('d M Y', $cleaned);
                
                Log::debug('MandiriParser: Period date parsed', [
                    'original' => $date,
                    'parsed' => $parsed->format('Y-m-d')
                ]);
                
                return $parsed;
            }
            
            // Fallback: Coba format lain
            return $this->parseDate($date);
            
        } catch (\Exception $e) {
            Log::warning('MandiriParser: Failed to parse period date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Last resort
            try {
                return Carbon::parse($date);
            } catch (\Exception $e2) {
                Log::error('MandiriParser: All period date parsing attempts failed', [
                    'date' => $date,
                    'error' => $e2->getMessage()
                ]);
                return null;
            }
        }
    }
    
    /**
     * Parse account info dari format kompleks Mandiri
     * 
     * Format: "1560006875217 IDR KIMIA FARMA APOTIK  BO BEKASI"
     * Extract:
     * - Account number: "1560006875217"
     * - Currency: "IDR"
     * - Holder name: "KIMIA FARMA APOTIK"
     * - Branch name: "BO BEKASI"
     * 
     * @param string $accountString
     * @return array
     */
    private function parseAccountInfo(string $accountString): array
    {
        if (empty($accountString)) {
            return [
                'number' => null,
                'currency' => 'IDR',
                'holder_name' => null,
                'branch' => null,
                'branch_name' => null,
            ];
        }
        
        // Split by whitespace, max 4 parts
        // Format: [AccountNo] [Currency] [HolderName...] [BranchName...]
        $parts = preg_split('/\s+/', trim($accountString), 4);
        
        $accountNumber = $parts[0] ?? null;
        $currency = $parts[1] ?? 'IDR';
        $remainingText = $parts[2] ?? null;
        
        // Jika ada parts[3], berarti ada branch name terpisah
        $holderName = null;
        $branchName = null;
        
        if (isset($parts[3])) {
            // Ada 4+ parts: AccountNo Currency HolderName BranchName
            $holderName = $parts[2];
            $branchName = $parts[3];
        } elseif ($remainingText) {
            // Hanya 3 parts: AccountNo Currency HolderName
            $holderName = $remainingText;
            
            // Coba detect branch dari holder name
            // Pattern: "KIMIA FARMA APOTIK  BO BEKASI" → split by double space atau "BO"/"KC"
            if (preg_match('/^(.+?)\s{2,}(.+)$/', $holderName, $matches)) {
                $holderName = trim($matches[1]);
                $branchName = trim($matches[2]);
            }
        }
        
        $result = [
            'number' => $accountNumber,
            'currency' => strtoupper($currency),
            'holder_name' => $holderName,
            'branch' => null, // Mandiri tidak provide branch code di AccountNo
            'branch_name' => $branchName,
        ];
        
        Log::debug('MandiriParser: Account info parsed', [
            'original' => $accountString,
            'parsed' => $result
        ]);
        
        return $result;
    }
}