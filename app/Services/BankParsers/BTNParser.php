<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

class BTNParser extends BaseBankParser
{
    protected string $bankName = 'BTN';
    
    /**
     * Parse BTN bank statement OCR data
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'BTN',
            'period_from' => $this->parsePeriodDate($ocr['PeriodFrom'] ?? null),
            'period_to' => $this->parsePeriodDate($ocr['PeriodTo'] ?? null),
            'account_number' => $ocr['AccountNo'] ?? null,
            'currency' => $ocr['Currency'] ?? 'IDR',
            'branch_code' => $ocr['Branch'] ?? null,
            'opening_balance' => $this->parseAmount($ocr['OpeningBalance'] ?? '0'),
            'closing_balance' => $this->parseAmount($ocr['ClosingBalance'] ?? '0'),
            'total_credit_count' => (int) ($ocr['CreditNo'] ?? 0),
            'total_debit_count' => (int) ($ocr['DebitNo'] ?? 0),
            'total_credit_amount' => $this->parseAmount($ocr['TotalAmountCredited'] ?? '0'),
            'total_debit_amount' => $this->parseAmount($ocr['TotalAmountDebited'] ?? '0'),
            'transactions' => $this->parseTransactions($ocr['TableData'] ?? []),
        ];
    }
    
    /**
     * Parse period date (Format: DD/MM/YY)
     * Converts 2-digit year to 4-digit year
     */
    private function parsePeriodDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            // Convert YY to YYYY (assume 20xx)
            $parts = explode('/', $date);
            
            if (count($parts) === 3 && strlen($parts[2]) === 2) {
                $year = '20' . $parts[2];
                $date = $parts[0] . '/' . $parts[1] . '/' . $year;
            }
            
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Parse transactions data
     */
    private function parseTransactions(array $tableData): array
    {
        $transactions = [];
        
        foreach ($tableData as $row) {
            $date = $this->parsePeriodDate($row['Date'] ?? null);
            $debit = $this->parseAmount($row['Debit'] ?? '0');
            $credit = $this->parseAmount($row['Credit'] ?? '0');
            
            $transactions[] = [
                'transaction_date' => $date?->format('Y-m-d'),
                'transaction_time' => $this->parseTime($row['Time'] ?? null),
                'value_date' => null,
                'branch_code' => $row['Branch'] ?? null,
                'description' => $row['Description'] ?? null,
                'reference_no' => $row['ReferenceNo'] ?? null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $this->parseAmount($row['Balance'] ?? '0'),
                'transaction_type' => $debit > 0 ? 'debit' : 'credit',
                'amount' => max($debit, $credit),
            ];
        }
        
        return $transactions;
    }
}