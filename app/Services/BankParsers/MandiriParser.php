<?php

namespace App\Services\BankParsers;

class MandiriParser extends BaseBankParser
{
    protected string $bankName = 'Mandiri';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? $ocrData;
        
        // Parse account info yang kompleks
        $accountInfo = $this->parseAccountInfo($ocr['AccountNo'] ?? '');
        
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
            'transactions' => $this->parseTransactions($ocr['TableData'] ?? []),
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
    
    private function parseTransactions(array $tableData): array
    {
        $transactions = [];
        
        foreach ($tableData as $row) {
            // Mandiri format: DD/MM/YYYY (lengkap)
            $date = $this->parseDate($row['Date'] ?? null);
            $valueDate = $this->parseDate($row['ValueDate'] ?? null);
            
            $debit = $this->parseAmount($row['Debit'] ?? '0');
            $credit = $this->parseAmount($row['Credit'] ?? '0');
            $amount = $credit > 0 ? $credit : -$debit;
            
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
                'amount' => abs($amount),
                'transaction_type' => $debit > 0 ? 'debit' : 'credit',
            ];
        }
        
        return $transactions;
    }
}