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
        'original_filename',
        'ocr_status',
        'ocr_response',
        'ocr_error',
        'statement_period_start',
        'statement_period_end',
        'uploaded_at',
        'processed_at',
    ];

    protected $casts = [
        'ocr_response' => 'array',
        'statement_period_start' => 'date',
        'statement_period_end' => 'date',
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
     * Scope: Filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('ocr_status', $status);
    }

    /**
     * Check if OCR is completed
     */
    public function isOcrCompleted(): bool
    {
        return $this->ocr_status === 'completed';
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['ocr_status' => 'processing']);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(array $ocrResponse): void
    {
        $this->update([
            'ocr_status' => 'completed',
            'ocr_response' => $ocrResponse,
            'processed_at' => now(),
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
}