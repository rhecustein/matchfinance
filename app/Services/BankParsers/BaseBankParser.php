<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

abstract class BaseBankParser
{
    protected string $bankName;
    protected string $dateFormat = 'Y-m-d';
    protected string $periodDateFormat = 'Y-m-d';
    
    /**
     * Parse OCR data into standardized format
     * 
     * @param array $ocrData
     * @return array
     */
    abstract public function parse(array $ocrData): array;
    
    /**
     * Parse date string to Carbon instance
     * 
     * @param string|null $date
     * @param string|null $year
     * @return Carbon|null
     */
    protected function parseDate(?string $date, ?string $year = null): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            // Clean the date string
            $cleanDate = str_replace(['/', '-', '.'], '/', trim($date));
            
            // Add year if not present (for DD/MM format)
            if (substr_count($cleanDate, '/') === 1 && $year) {
                $cleanDate .= '/' . $year;
            }
            
            return Carbon::parse($cleanDate);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$date}", [
                'error' => $e->getMessage(),
                'bank' => $this->bankName ?? 'unknown'
            ]);
            return null;
        }
    }
    
    /**
     * Parse amount string to float
     * 
     * @param string|null $amount
     * @return float
     */
    protected function parseAmount(?string $amount): float
    {
        if (empty($amount)) {
            return 0;
        }
        
        // Remove currency symbols and non-numeric characters except comma, dot, and minus
        $cleaned = preg_replace('/[^\d,.-]/', '', (string) $amount);
        
        // Handle different decimal separators
        // Indonesian format: 1.000.000,50 or 1,000,000.50
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Both comma and dot present
            // Determine which is decimal separator (last one)
            $lastComma = strrpos($cleaned, ',');
            $lastDot = strrpos($cleaned, '.');
            
            if ($lastComma > $lastDot) {
                // Comma is decimal separator (Indonesian format: 1.000,50)
                $cleaned = str_replace('.', '', $cleaned); // Remove thousand separator
                $cleaned = str_replace(',', '.', $cleaned); // Convert decimal separator
            } else {
                // Dot is decimal separator (US format: 1,000.50)
                $cleaned = str_replace(',', '', $cleaned); // Remove thousand separator
            }
        } else {
            // Only one type of separator or none
            if (strpos($cleaned, ',') !== false) {
                // Check if comma is decimal separator or thousand separator
                $parts = explode(',', $cleaned);
                if (isset($parts[1]) && strlen($parts[1]) <= 2) {
                    // Comma is decimal separator (e.g., 1000,50)
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    // Comma is thousand separator (e.g., 1,000)
                    $cleaned = str_replace(',', '', $cleaned);
                }
            }
            // If only dot, assume it's decimal separator or thousand separator
            // Standard parseFloat will handle it
        }
        
        return (float) $cleaned;
    }
    
    /**
     * Parse time string to standard format
     * 
     * @param string|null $time
     * @return string|null
     */
    protected function parseTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }
        
        try {
            // Normalize time format to HH:MM:SS
            $time = str_replace(['.', ' '], ':', trim($time));
            
            // Add seconds if missing
            if (substr_count($time, ':') === 1) {
                $time .= ':00';
            }
            
            // Validate time format
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
                // Parse and format to ensure valid time
                $parts = explode(':', $time);
                $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $minutes = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $seconds = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : '00';
                
                return "{$hours}:{$minutes}:{$seconds}";
            }
            
            return null;
        } catch (\Exception $e) {
            Log::warning("Failed to parse time: {$time}", [
                'error' => $e->getMessage(),
                'bank' => $this->bankName ?? 'unknown'
            ]);
            return null;
        }
    }
    
    /**
     * Clean description text
     * 
     * @param string $description
     * @return string
     */
    protected function cleanDescription(string $description): string
    {
        // Remove extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $description);
        
        // Remove special characters that might cause issues
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned);
        
        // Trim
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }
    
    /**
     * Determine transaction type from amount
     * 
     * @param float $amount
     * @return string
     */
    protected function determineTransactionType(float $amount): string
    {
        return $amount < 0 ? 'debit' : 'credit';
    }
    
    /**
     * Get bank name
     * 
     * @return string
     */
    public function getBankName(): string
    {
        return $this->bankName ?? 'Unknown';
    }
    
    /**
     * Extract year from period dates
     * 
     * @param array $ocrData
     * @return string|null
     */
    protected function extractYear(array $ocrData): ?string
    {
        // Try to get year from period_from or period_to
        $periodFrom = $ocrData['period_from'] ?? $ocrData['period_start'] ?? null;
        $periodTo = $ocrData['period_to'] ?? $ocrData['period_end'] ?? null;
        
        $date = $periodTo ?? $periodFrom;
        
        if ($date) {
            try {
                return Carbon::parse($date)->format('Y');
            } catch (\Exception $e) {
                Log::warning("Failed to extract year from period", [
                    'period_from' => $periodFrom,
                    'period_to' => $periodTo,
                    'error' => $e->getMessage(),
                    'bank' => $this->bankName ?? 'unknown'
                ]);
                return date('Y'); // Fallback to current year
            }
        }
        
        return date('Y');
    }
    
    /**
     * Format date to database format
     * 
     * @param Carbon|null $date
     * @return string|null
     */
    protected function formatDate(?Carbon $date): ?string
    {
        return $date ? $date->format($this->dateFormat) : null;
    }
    
    /**
     * Validate required fields in transaction data
     * 
     * @param array $transaction
     * @return bool
     */
    protected function validateTransaction(array $transaction): bool
    {
        $requiredFields = ['transaction_date', 'description', 'amount', 'transaction_type'];
        
        foreach ($requiredFields as $field) {
            if (!isset($transaction[$field]) || $transaction[$field] === null || $transaction[$field] === '') {
                Log::warning("Missing required field in transaction", [
                    'field' => $field,
                    'transaction' => $transaction,
                    'bank' => $this->bankName ?? 'unknown'
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize transaction data before returning
     * 
     * @param array $transactions
     * @return array
     */
    protected function sanitizeTransactions(array $transactions): array
    {
        return array_filter($transactions, function($transaction) {
            return $this->validateTransaction($transaction);
        });
    }
}