<?php

namespace App\Services\BankParsers;

class BTNParser extends BaseBankParser
{
    protected string $bankName = 'BTN';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Extract year dari period
        $year = $this->extractYear($ocr);
        
        return [
            'bank_name' => $ocr['Bank'] ?? $this->bankName,
            'period_from' => $this->formatDate($this->parseDate($ocr['PeriodFrom'] ?? null)),
            'period_to' => $this->formatDate($this->parseDate($ocr['PeriodTo'] ?? null)),
            'account_number' => $ocr['AccountNo'] ?? null,
            'account_holder_name' => null,
            'currency' => $ocr['Currency'] ?? 'IDR',
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
    
    private function parseTransactions(array $tableData, ?string $year): array
    {
        $transactions = [];
        
        foreach ($tableData as $row) {
            // BTN format: DD/MM (tanpa tahun)
            $date = $this->parseDate($row['Date'] ?? null, $year);
            
            // Time ada di kolom Time DAN di dalam Description
            $time = $this->parseTime($row['Time'] ?? null);
            if (!$time) {
                $time = $this->extractTimeFromDescription($row['Description'] ?? '');
            }
            
            $debit = $this->parseAmount($row['Debit'] ?? '0');
            $credit = $this->parseAmount($row['Credit'] ?? '0');
            $amount = $credit > 0 ? $credit : -$debit;
            
            $transactions[] = [
                'transaction_date' => $this->formatDate($date),
                'transaction_time' => $time,
                'value_date' => $this->formatDate($this->parseDate($row['ValueDate'] ?? null, $year)),
                'branch_code' => $row['Branch'] ?? null,
                'description' => $this->cleanDescription($row['Description'] ?? ''),
                'reference_no' => $row['ReferenceNo'] ?? null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $this->parseAmount($row['Balance'] ?? '0'),
                'amount' => abs($amount),
                'transaction_type' => $debit > 0 ? 'debit' : 'credit',
            ];
        }
        
        return $transactions;
    }
}