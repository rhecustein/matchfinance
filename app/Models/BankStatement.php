<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankStatement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_id',
        'user_id',
        'file_path',
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

    /**
     * Get the bank that owns this statement
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Get the user who uploaded this statement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this statement
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    /**
     * Get matched transactions
     */
    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->whereNotNull('matched_keyword_id');
    }

    /**
     * Get unmatched transactions
     */
    public function unmatchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->whereNull('matched_keyword_id');
    }

    /**
     * Get verified transactions
     */
    public function verifiedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->where('is_verified', true);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('ocr_status', $status);
    }

    /**
     * Scope: Filter by period
     */
    public function scopePeriodBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_from', [$startDate, $endDate])
            ->orWhereBetween('period_to', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by bank
     */
    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if OCR is completed
     */
    public function isOcrCompleted(): bool
    {
        return $this->ocr_status === 'completed';
    }

    /**
     * Check if OCR is processing
     */
    public function isOcrProcessing(): bool
    {
        return $this->ocr_status === 'processing';
    }

    /**
     * Check if OCR failed
     */
    public function isOcrFailed(): bool
    {
        return $this->ocr_status === 'failed';
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['ocr_status' => 'processing']);
    }

    /**
     * Mark as completed with OCR data
     */
    public function markAsCompleted(array $ocrResponse, array $metadata = []): void
    {
        $this->update([
            'ocr_status' => 'completed',
            'ocr_response' => $ocrResponse,
            'processed_at' => now(),
            ...$metadata
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'ocr_status' => 'failed',
            'ocr_error' => $error,
        ]);
    }


    /**
     * Get matching percentage
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

    /**
     * Get verification percentage
     */
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

    /**
     * Get net amount (credits - debits)
     */
    public function netAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_credit_amount - $this->total_debit_amount
        );
    }

    /**
     * Get balance difference
     */
    public function balanceDifference(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->closing_balance - $this->opening_balance
        );
    }

    /**
     * Check if balance is reconciled
     */
    public function isBalanceReconciled(): bool
    {
        $calculated = $this->opening_balance + $this->total_credit_amount - $this->total_debit_amount;
        return abs($calculated - $this->closing_balance) < 0.01; // Allow 1 cent tolerance
    }

    /**
     * Get file size in human readable format
     */
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

    /**
     * Get period label
     */
    public function periodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->period_from?->format('M Y') ?? 'N/A'
        );
    }

    /**
     * Update matching statistics (stored in database)
     */
    public function updateMatchingStats(): void
    {
        $total = $this->transactions()->count();
        
        // Count matched (either via keyword or manual)
        $matched = $this->transactions()
            ->where(function($query) {
                $query->whereNotNull('matched_keyword_id')
                    ->orWhere('is_manual_category', true);
            })
            ->count();
        
        $unmatched = $this->transactions()
            ->whereNull('matched_keyword_id')
            ->where('is_manual_category', false)
            ->count();
        
        $manual = $this->transactions()
            ->where('is_manual_category', true)
            ->count();
        
        $matchPercentage = $total > 0 ? ($matched / $total) * 100 : 0;

        $this->update([
            'matched_transactions_count' => $matched,
            'unmatched_transactions_count' => $unmatched,
            'manual_transactions_count' => $manual,
            'match_percentage' => round($matchPercentage, 2),
        ]);
    }

    /**
     * Get matching statistics (real-time from transactions)
     */
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

    /**
     * Get low confidence count
     */
    public function getLowConfidenceCount(): int
    {
        return $this->transactions()
            ->where('confidence_score', '<', 80)
            ->whereNotNull('matched_keyword_id')
            ->count();
    }
}