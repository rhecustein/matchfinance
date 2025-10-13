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
        
        // Remove thousand separators (commas)
        $cleaned = str_replace(',', '', $cleaned);
        
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
        
        // Normalize time format to HH:MM:SS
        $time = str_replace(['.', ' '], ':', trim($time));
        
        // Add seconds if missing
        if (substr_count($time, ':') === 1) {
            $time .= ':00';
        }
        
        return $time;
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
        return $this->bankName;
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
}