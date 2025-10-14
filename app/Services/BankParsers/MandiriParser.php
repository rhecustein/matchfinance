<?php

namespace App\Services\BankParsers;

use Illuminate\Support\Facades\Log;

class MandiriParser extends BaseBankParser
{
    protected string $bankName = 'Mandiri';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Parse account info yang kompleks
        $accountInfo = $this->parseAccountInfo($ocr['AccountNo'] ?? '');
        
        // ✅ FIX: Pass TableData directly, not nested
        $tableData = $ocr['TableData'] ?? $ocr['transactions'] ?? [];
        
        Log::info("MandiriParser: Parsing transactions", [
            'has_TableData' => isset($ocr['TableData']),
            'has_transactions' => isset($ocr['transactions']),
            'data_type' => gettype($tableData),
            'data_count' => is_array($tableData) ? count($tableData) : 0,
            'first_item_keys' => !empty($tableData) && is_array($tableData[0]) ? array_keys($tableData[0]) : [],
        ]);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parseDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseDate($ocr['PeriodTo'] ?? null)),
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
     * Parse account info dari format: "1560006875217 IDR KIMIA FARMA APOTIK  BO BEKASI"
     */
    private function parseAccountInfo(string $accountString): array
    {
        $parts = preg_split('/\s+/', trim($accountString), 3);
        
        return [
            'number' => $parts[0] ?? null,
            'currency' => $parts[1] ?? 'IDR',
            'holder_name' => $parts[2] ?? null,
            'branch' => null,
            'branch_name' => null,
        ];
    }
    
    /**
     * Parse transactions from Mandiri OCR response
     * 
     * ✅ FIXED: Accept array directly, not nested object
     * 
     * @param array $data Direct array of transactions
     * @return array
     */
    public function parseTransactions(array $data): array
    {
        $transactions = [];
        
        Log::info("MandiriParser parseTransactions called", [
            'input_type' => gettype($data),
            'input_count' => count($data),
            'is_sequential' => array_keys($data) === range(0, count($data) - 1),
            'first_keys' => !empty($data) && is_array($data[0]) ? array_keys($data[0]) : 'not_array',
        ]);
        
        // ✅ FIX 1: Handle both formats
        // Format 1: Direct array [{Date: ...}, {Date: ...}]
        // Format 2: Nested {transactions: [{Date: ...}]}
        $rows = [];
        
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            // Nested format
            $rows = $data['transactions'];
            Log::info("Using nested format", ['count' => count($rows)]);
        } elseif (isset($data[0]) && is_array($data[0])) {
            // Direct array format (sequential keys)
            $rows = $data;
            Log::info("Using direct array format", ['count' => count($rows)]);
        } else {
            Log::warning("Unknown data format in parseTransactions", [
                'data_keys' => array_keys($data),
                'first_element' => !empty($data) ? $data[0] : null,
            ]);
            return [];
        }
        
        // ✅ FIX 2: Process each transaction row
        foreach ($rows as $index => $row) {
            try {
                if (!is_array($row)) {
                    Log::warning("Skipping non-array row", ['index' => $index, 'type' => gettype($row)]);
                    continue;
                }
                
                $date = $this->parseDate($row['Date'] ?? null);
                $valueDate = $this->parseDate($row['ValueDate'] ?? null);
                
                $debit = $this->parseAmount($row['Debit'] ?? '0');
                $credit = $this->parseAmount($row['Credit'] ?? '0');
                $amount = $credit > 0 ? $credit : abs($debit);
                
                $transactions[] = [
                    'transaction_date' => $this->formatDate($date),
                    'transaction_time' => $this->parseTime($row['Time'] ?? null),
                    'value_date' => $this->formatDate($valueDate),
                    'branch_code' => $row['Branch'] ?? null,
                    'description' => $this->cleanDescription($row['Description'] ?? ''),
                    'reference_no' => $row['ReferenceNo'] ?? null,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'balance' => $this->parseAmount($row['Balance'] ?? '0'),
                    'amount' => $amount,
                    'transaction_type' => $debit > 0 ? 'debit' : 'credit',
                ];
            } catch (\Exception $e) {
                Log::error("Failed to parse transaction row", [
                    'index' => $index,
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
                // Continue processing other transactions
            }
        }
        
        Log::info("MandiriParser transactions parsed", [
            'input_count' => count($rows),
            'output_count' => count($transactions),
        ]);
        
        return $transactions;
    }
}