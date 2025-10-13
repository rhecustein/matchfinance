<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'mime_type',
        'ocr_status',
        'ocr_response',
        'ocr_error',
        'ocr_job_id',
        'bank_name',
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
        'total_transactions',
        'processed_transactions',
        'matched_transactions',
        'unmatched_transactions',
        'verified_transactions',
        'uploaded_at',
        'ocr_started_at',
        'ocr_completed_at',
    ];

    protected $casts = [
        'ocr_response' => 'array',
        'file_size' => 'integer',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_credit_amount' => 'decimal:2',
        'total_debit_amount' => 'decimal:2',
        'total_credit_count' => 'integer',
        'total_debit_count' => 'integer',
        'total_transactions' => 'integer',
        'processed_transactions' => 'integer',
        'matched_transactions' => 'integer',
        'unmatched_transactions' => 'integer',
        'verified_transactions' => 'integer',
        'period_from' => 'date',
        'period_to' => 'date',
        'uploaded_at' => 'datetime',
        'ocr_started_at' => 'datetime',
        'ocr_completed_at' => 'datetime',
    ];

    /**
     * Get the bank that owns the statement
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Get the user who uploaded the statement
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
     * Scope: Get pending OCR statements
     */
    public function scopePending($query)
    {
        return $query->where('ocr_status', 'pending');
    }

    /**
     * Scope: Get processing OCR statements
     */
    public function scopeProcessing($query)
    {
        return $query->where('ocr_status', 'processing');
    }

    /**
     * Scope: Get completed OCR statements
     */
    public function scopeCompleted($query)
    {
        return $query->where('ocr_status', 'completed');
    }

    /**
     * Scope: Get failed OCR statements
     */
    public function scopeFailed($query)
    {
        return $query->where('ocr_status', 'failed');
    }

    /**
     * Scope: Filter by bank
     */
    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_from', [$startDate, $endDate]);
    }

    /**
     * Get matching percentage
     */
    public function getMatchingPercentageAttribute(): float
    {
        if ($this->total_transactions == 0) {
            return 0;
        }

        return round(($this->matched_transactions / $this->total_transactions) * 100, 2);
    }

    /**
     * Get verification percentage
     */
    public function getVerificationPercentageAttribute(): float
    {
        if ($this->total_transactions == 0) {
            return 0;
        }

        return round(($this->verified_transactions / $this->total_transactions) * 100, 2);
    }

    /**
     * Check if OCR is completed
     */
    public function isOcrCompleted(): bool
    {
        return $this->ocr_status === 'completed';
    }

    /**
     * Check if OCR is failed
     */
    public function isOcrFailed(): bool
    {
        return $this->ocr_status === 'failed';
    }

    /**
     * Check if OCR is processing
     */
    public function isOcrProcessing(): bool
    {
        return in_array($this->ocr_status, ['pending', 'processing']);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get processing duration in seconds
     */
    public function getProcessingDurationAttribute(): ?int
    {
        if (!$this->ocr_started_at || !$this->ocr_completed_at) {
            return null;
        }

        return $this->ocr_completed_at->diffInSeconds($this->ocr_started_at);
    }
}