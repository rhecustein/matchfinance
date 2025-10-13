<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

abstract class BaseBankParser
{
    protected string $bankName;
    protected string $dateFormat = 'Y-m-d';
    
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
            $cleanDate = trim($date);
            
            // Jika format DD/MM tanpa tahun, tambahkan tahun
            if (preg_match('/^\d{1,2}\/\d{1,2}$/', $cleanDate) && $year) {
                $cleanDate .= '/' . $year;
            }
            
            // Jika format DD/MM/YY (2 digit), konversi ke 4 digit
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $cleanDate)) {
                $parts = explode('/', $cleanDate);
                $cleanDate = $parts[0] . '/' . $parts[1] . '/20' . $parts[2];
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
     * Parse date with specific format (untuk CIMB yang pakai format US)
     */
    protected function parseDateWithFormat(?string $date, string $format, ?string $year = null): ?Carbon
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            return Carbon::createFromFormat($format, $date);
        } catch (\Exception $e) {
            // Fallback ke parseDate biasa
            return $this->parseDate($date, $year);
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
            return 0.0;
        }
        
        // Remove currency symbols
        $cleaned = preg_replace('/[^\d,.\-]/', '', (string) $amount);
        
        // Handle Indonesian format (1.234.567,89)
        if (substr_count($cleaned, '.') > 1) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        }
        // Handle standard format (1,234,567.89)
        else if (substr_count($cleaned, ',') > 1) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        // Single comma as decimal separator
        else if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') === false) {
            $cleaned = str_replace(',', '.', $cleaned);
        }
        // Remove thousand separator comma
        else if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        
        return (float) $cleaned;
    }
    
    /**
     * Parse time string
     */
    protected function parseTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }
        
        $time = trim($time);
        
        // Normalize separators
        $time = str_replace(['.', ' '], ':', $time);
        
        // Add seconds if missing
        if (substr_count($time, ':') === 1) {
            $time .= ':00';
        }
        
        return $time;
    }
    
    /**
     * Extract time from description (untuk BTN & CIMB)
     */
    protected function extractTimeFromDescription(string $description): ?string
    {
        // Pattern: HH:MM:SS atau HH:MM
        if (preg_match('/\b(\d{2}:\d{2}(?::\d{2})?)\b/', $description, $matches)) {
            return $this->parseTime($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Clean description text
     */
    protected function cleanDescription(string $description): string
    {
        $cleaned = preg_replace('/\s+/', ' ', $description);
        return trim($cleaned);
    }
    
    /**
     * Determine transaction type from amount
     */
    protected function determineTransactionType(float $amount): string
    {
        return $amount < 0 ? 'debit' : 'credit';
    }
    
    /**
     * Extract year from period dates
     */
    protected function extractYear(array $ocrData): ?string
    {
        $periodFrom = $ocrData['PeriodFrom'] ?? $ocrData['period_from'] ?? null;
        $periodTo = $ocrData['PeriodTo'] ?? $ocrData['period_to'] ?? null;
        
        $date = $periodTo ?? $periodFrom;
        
        if ($date) {
            try {
                return Carbon::parse($date)->format('Y');
            } catch (\Exception $e) {
                return date('Y');
            }
        }
        
        return date('Y');
    }
    
    /**
     * Format date to database format
     */
    protected function formatDate(?Carbon $date): ?string
    {
        return $date ? $date->format($this->dateFormat) : null;
    }
    
    /**
     * Get bank name
     */
    public function getBankName(): string
    {
        return $this->bankName;
    }
}