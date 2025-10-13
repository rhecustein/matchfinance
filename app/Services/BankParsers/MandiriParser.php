<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

class MandiriParser extends BaseBankParser
{
    protected string $bankName = 'Mandiri';
    
    /**
     * Parse Mandiri bank statement OCR data
     */
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'Mandiri',
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
            'transactions' => $this->parseTransactions($ocr['TableData'] ?? [], $ocr['PeriodFrom'] ?? null),
        ];
    }
    
    /**
     * Parse period date (Format: DD/MM/YYYY)
     */
    private function parsePeriodDate(?string $date): ?Carbon
    {
        return $this->parseDate($date);
    }
    
    /**
     * Parse transactions data
     */
    private function parseTransactions(array $tableData, ?string $periodFrom): array
    {
        // Extract year from period for date parsing
        $year = null;
        if ($periodFrom) {
            try {
                $year = Carbon::parse($periodFrom)->year;
            } catch (\Exception $e) {
                $year = null;
            }
        }
        
        $transactions = [];
        
        foreach ($tableData as $row) {
            $date = $this->parseDate($row['Date'] ?? null, $year);
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