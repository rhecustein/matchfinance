<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;

/**
 * BankStatement Model
 * 
 * ✅ Auto-sanitization untuk input data:
 * - branch_code (max 100 chars)
 * - account_number (extract numeric only)
 * - currency (uppercase, max 3 chars)
 */
class BankStatement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_id',
        'user_id',
        'file_path',
        'file_hash',
        'original_filename',
        'file_size',
        'ocr_status',
        'ocr_response',
        'ocr_error',
        'period_from',
        'period_to',
        'account_number',
        'currency',
        'branch_code',
        'opening_balance',
        'closing_balance',
        'total_credit_count',
        'total_debit_count',
        'total_credit_amount',
        'total_debit_amount',
        'matched_count',
        'unmatched_count',
        'verified_count',
        'uploaded_at',
        'processed_at',
    ];

    protected $casts = [
        'ocr_response' => 'array',
        'period_from' => 'date',
        'period_to' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_credit_amount' => 'decimal:2',
        'total_debit_amount' => 'decimal:2',
        'total_credit_count' => 'integer',
        'total_debit_count' => 'integer',
        'matched_count' => 'integer',
        'unmatched_count' => 'integer',
        'verified_count' => 'integer',
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | ✅ MUTATORS - Auto Sanitization
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Auto-truncate branch_code (max 100 chars)
     * Mencegah error "Data too long for column"
     */
    public function setBranchCodeAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['branch_code'] = null;
            return;
        }

        $sanitized = trim($value);

        // Truncate jika lebih dari 100 chars
        if (strlen($sanitized) > 100) {
            $sanitized = substr($sanitized, 0, 97) . '...';
            
            Log::debug('Branch code truncated', [
                'original_length' => strlen($value),
                'truncated_length' => strlen($sanitized),
            ]);
        }

        $this->attributes['branch_code'] = $sanitized;
    }

    /**
     * ✅ Auto-extract account number (numeric only)
     * Format: "833774466 IDR KIMIA FARMA APOTEK PT" → "833774466"
     */
    public function setAccountNumberAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['account_number'] = null;
            return;
        }

        $sanitized = trim($value);

        // Extract hanya angka pertama
        if (preg_match('/^(\d+)/', $sanitized, $matches)) {
            $this->attributes['account_number'] = $matches[1];
        } else {
            // Fallback: truncate ke 50 chars
            $this->attributes['account_number'] = substr($sanitized, 0, 50);
            
            Log::warning('Account number tidak berisi angka', [
                'original' => substr($sanitized, 0, 50),
            ]);
        }
    }

    /**
     * ✅ Auto-uppercase currency (max 3 chars)
     */
    public function setCurrencyAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['currency'] = 'IDR'; // Default
            return;
        }

        $this->attributes['currency'] = strtoupper(substr(trim($value), 0, 3));
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->whereNotNull('matched_keyword_id');
    }

    public function unmatchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->whereNull('matched_keyword_id');
    }

    public function verifiedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->where('is_verified', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus($query, $status)
    {
        return $query->where('ocr_status', $status);
    }

    public function scopePeriodBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_from', [$startDate, $endDate])
            ->orWhereBetween('period_to', [$startDate, $endDate]);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isOcrCompleted(): bool
    {
        return $this->ocr_status === 'completed';
    }

    public function isOcrProcessing(): bool
    {
        return $this->ocr_status === 'processing';
    }

    public function isOcrFailed(): bool
    {
        return $this->ocr_status === 'failed';
    }

    /*
    |--------------------------------------------------------------------------
    | Update Methods
    |--------------------------------------------------------------------------
    */

    public function markAsProcessing(): void
    {
        $this->update(['ocr_status' => 'processing']);
    }

    public function markAsCompleted(array $ocrResponse, array $metadata = []): void
    {
        $this->update([
            'ocr_status' => 'completed',
            'ocr_response' => $ocrResponse,
            'processed_at' => now(),
            ...$metadata
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'ocr_status' => 'failed',
            'ocr_error' => $error,
        ]);
    }

    /**
     * ✅ Update matching statistics
     * Called after matching/verification operations
     */
    public function updateMatchingStats(): void
    {
        $matchedCount = $this->transactions()->whereNotNull('matched_keyword_id')->count();
        $unmatchedCount = $this->transactions()->whereNull('matched_keyword_id')->count();
        $verifiedCount = $this->transactions()->where('is_verified', true)->count();

        $this->update([
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
            'verified_count' => $verifiedCount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function matchingPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $total = $this->transactions()->count();
                if ($total === 0) return 0;
                
                return round(($this->matched_count / $total) * 100, 2);
            }
        );
    }

    public function verificationPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $total = $this->transactions()->count();
                if ($total === 0) return 0;
                
                return round(($this->verified_count / $total) * 100, 2);
            }
        );
    }

    public function netAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_credit_amount - $this->total_debit_amount
        );
    }

    public function balanceDifference(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->closing_balance - $this->opening_balance
        );
    }

    public function fileSizeFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->file_size) return 'N/A';
                
                $units = ['B', 'KB', 'MB', 'GB'];
                $bytes = $this->file_size;
                
                for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                    $bytes /= 1024;
                }
                
                return round($bytes, 2) . ' ' . $units[$i];
            }
        );
    }

    public function periodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->period_from?->format('M Y') ?? 'N/A'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isBalanceReconciled(): bool
    {
        $calculated = $this->opening_balance + $this->total_credit_amount - $this->total_debit_amount;
        return abs($calculated - $this->closing_balance) < 0.01;
    }

    public function getMatchingStats(): array
    {
        $transactions = $this->transactions;
        $total = $transactions->count();
        
        $matched = $transactions->filter(function($t) {
            return !is_null($t->matched_keyword_id) || $t->is_manual_category;
        })->count();
        
        $unmatched = $transactions->filter(function($t) {
            return is_null($t->matched_keyword_id) && !$t->is_manual_category;
        })->count();
        
        $manual = $transactions->filter(fn($t) => $t->is_manual_category)->count();
        
        $matchPercentage = $total > 0 ? ($matched / $total) * 100 : 0;

        return [
            'total_transactions' => $total,
            'matched_count' => $matched,
            'unmatched_count' => $unmatched,
            'manual_count' => $manual,
            'match_percentage' => round($matchPercentage, 2),
        ];
    }

    public function getLowConfidenceCount(): int
    {
        return $this->transactions()
            ->where('confidence_score', '<', 80)
            ->whereNotNull('matched_keyword_id')
            ->count();
    }
}