<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

abstract class BaseBankParser
{
    protected string $bankName;
    protected string $dateFormat;
    protected string $periodDateFormat;
    
    abstract public function parse(array $ocrData): array;
    
    protected function parseDate(?string $date, ?string $year = null): ?Carbon
    {
        if (empty($date)) return null;
        
        try {
            // Handle different date formats
            $cleanDate = str_replace(['/', '-', '.'], '/', trim($date));
            
            // Add year if not present (for DD/MM format)
            if (substr_count($cleanDate, '/') === 1 && $year) {
                $cleanDate .= '/' . $year;
            }
            
            return Carbon::parse($cleanDate);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    protected function parseAmount(?string $amount): float
    {
        if (empty($amount)) return 0;
        
        // Remove currency symbols and clean the string
        $cleaned = preg_replace('/[^\d,.-]/', '', $amount);
        $cleaned = str_replace(',', '', $cleaned);
        
        return (float) $cleaned;
    }
    
    protected function parseTime(?string $time): ?string
    {
        if (empty($time)) return null;
        
        // Normalize time format to HH:MM:SS
        $time = str_replace(['.', ' '], ':', trim($time));
        
        // Add seconds if missing
        if (substr_count($time, ':') === 1) {
            $time .= ':00';
        }
        
        return $time;
    }
}

// BCA Parser
class BCAParser extends BaseBankParser
{
    protected string $bankName = 'BCA';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'BCA',
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
    
    private function parsePeriodDate(?string $date): ?Carbon
    {
        // Format: DD/MM/YYYY
        return $this->parseDate($date);
    }
    
    private function parseTransactions(array $tableData, ?string $periodFrom): array
    {
        $year = null;
        if ($periodFrom) {
            $year = Carbon::parse($periodFrom)->year;
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

// BNI Parser
class BNIParser extends BaseBankParser
{
    protected string $bankName = 'BNI';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'BNI',
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
    
    private function parsePeriodDate(?string $date): ?Carbon
    {
        // Format: DD-MMM-YYYY (e.g., 01-Aug-2024)
        return $this->parseDate($date);
    }
    
    private function parseTransactions(array $tableData): array
    {
        $transactions = [];
        foreach ($tableData as $row) {
            $date = $this->parseDate($row['Date'] ?? null);
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

// BRI Parser
class BRIParser extends BaseBankParser
{
    protected string $bankName = 'BRI';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'BRI',
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
    
    private function parsePeriodDate(?string $date): ?Carbon
    {
        // Format: DD/MM/YY
        if (empty($date)) return null;
        
        try {
            // Convert YY to YYYY
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

// BTN Parser
class BTNParser extends BaseBankParser
{
    protected string $bankName = 'BTN';
    
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
            'transactions' => $this->parseTransactions($ocr['TableData'] ?? [], $ocr['PeriodFrom'] ?? null),
        ];
    }
    
    private function parsePeriodDate(?string $date): ?Carbon
    {
        // Format: DD-MMM-YYYY (e.g., 01-Jan-2024)
        return $this->parseDate($date);
    }
    
    private function parseTransactions(array $tableData, ?string $periodFrom): array
    {
        $year = null;
        if ($periodFrom) {
            $year = Carbon::parse($periodFrom)->year;
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

// CIMB Parser
class CIMBParser extends BaseBankParser
{
    protected string $bankName = 'CIMB';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'CIMB',
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
    
    private function parsePeriodDate(?string $date): ?Carbon
    {
        // Format: DD-MMM-YYYY
        return $this->parseDate($date);
    }
    
    private function parseTransactions(array $tableData): array
    {
        $transactions = [];
        foreach ($tableData as $row) {
            // CIMB uses MM/DD/YY format
            $date = $this->parseCIMBDate($row['Date'] ?? null);
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
    
    private function parseCIMBDate(?string $date): ?Carbon
    {
        if (empty($date)) return null;
        
        try {
            // CIMB format: MM/DD/YY
            return Carbon::createFromFormat('m/d/y', $date);
        } catch (\Exception $e) {
            return null;
        }
    }
}

// Mandiri Parser
class MandiriParser extends BaseBankParser
{
    protected string $bankName = 'Mandiri';
    
    public function parse(array $ocrData): array
    {
        $ocr = $ocrData['ocr'] ?? [];
        
        return [
            'bank_name' => $ocr['Bank'] ?? 'Mandiri',
            'period_from' => $this->parsePeriodDate($ocr['PeriodFrom'] ?? null),
            'period_to' => $this->parsePeriodDate($ocr['PeriodTo'] ?? null),
            'account_number' => $this->extractAccountNumber($ocr['AccountNo'] ?? null),
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
    
    private function extractAccountNumber(?string $accountNo): ?string
    {
        if (empty($accountNo)) return null;
        
        // Extract only the account number from "1560006875217 IDR KIMIA FARMA APOTIK  BO BEKASI"
        preg_match('/^(\d+)/', $accountNo, $matches);
        return $matches[1] ?? $accountNo;
    }
    
    private function parsePeriodDate(?string $date): ?Carbon
    {
        // Format: DD MMM YYYY (e.g., 01 Jan 2024)
        if (empty($date)) return null;
        
        try {
            return Carbon::parse(str_replace('  ', ' ', $date));
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function parseTransactions(array $tableData): array
    {
        $transactions = [];
        foreach ($tableData as $row) {
            $date = $this->parseDate($row['Date'] ?? null);
            $debit = $this->parseAmount($row['Debit'] ?? '0');
            $credit = $this->parseAmount($row['Credit'] ?? '0');
            
            $transactions[] = [
                'transaction_date' => $date?->format('Y-m-d'),
                'transaction_time' => $this->parseTime($row['Time'] ?? null),
                'value_date' => $this->parseDate($row['ValueDate'] ?? null)?->format('Y-m-d'),
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