<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

abstract class BaseBankParser
{
    protected string $bankName;
    protected string $dateFormat;
    protected string $periodDateFormat;
    
    /**
     * Parse OCR data into standardized format
     */
    abstract public function parse(array $ocrData): array;
    
    /**
     * Parse date string to Carbon instance
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
            return null;
        }
    }
    
    /**
     * Parse amount string to float
     */
    protected function parseAmount(?string $amount): float
    {
        if (empty($amount)) {
            return 0;
        }
        
        // Remove currency symbols and non-numeric characters except comma, dot, and minus
        $cleaned = preg_replace('/[^\d,.-]/', '', $amount);
        
        // Remove thousand separators (commas)
        $cleaned = str_replace(',', '', $cleaned);
        
        return (float) $cleaned;
    }
    
    /**
     * Parse time string to standard format
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
     * Get bank name
     */
    public function getBankName(): string
    {
        return $this->bankName;
    }
}