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
        'account_holder_name',
        'currency',
        'branch_code',
        'branch_name',
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
        'notes',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by',
        'uploaded_at',
        'ocr_started_at',
        'ocr_completed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'ocr_response' => 'array',
        'period_from' => 'date',
        'period_to' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_credit_count' => 'integer',
        'total_debit_count' => 'integer',
        'total_credit_amount' => 'decimal:2',
        'total_debit_amount' => 'decimal:2',
        'total_transactions' => 'integer',
        'processed_transactions' => 'integer',
        'matched_transactions' => 'integer',
        'unmatched_transactions' => 'integer',
        'verified_transactions' => 'integer',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'ocr_started_at' => 'datetime',
        'ocr_completed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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
     * Get the user who reconciled this statement
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Get all transactions for this statement
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for pending OCR
     */
    public function scopePending($query)
    {
        return $query->where('ocr_status', 'pending');
    }

    /**
     * Scope for processing OCR
     */
    public function scopeProcessing($query)
    {
        return $query->where('ocr_status', 'processing');
    }

    /**
     * Scope for completed OCR
     */
    public function scopeCompleted($query)
    {
        return $query->where('ocr_status', 'completed');
    }

    /**
     * Scope for failed OCR
     */
    public function scopeFailed($query)
    {
        return $query->where('ocr_status', 'failed');
    }

    /**
     * Scope for reconciled statements
     */
    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    /**
     * Scope for unreconciled statements
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    /**
     * Scope for specific bank
     */
    public function scopeForBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range
     */
    public function scopePeriodRange($query, $from, $to)
    {
        return $query->where(function($q) use ($from, $to) {
            $q->whereBetween('period_from', [$from, $to])
              ->orWhereBetween('period_to', [$from, $to]);
        });
    }

    /**
     * Scope for specific account number
     */
    public function scopeForAccount($query, $accountNumber)
    {
        return $query->where('account_number', $accountNumber);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted file size
     */
    public function formattedFileSize(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->file_size) return 'N/A';
                
                $units = ['B', 'KB', 'MB', 'GB'];
                $size = $this->file_size;
                $unit = 0;
                
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                
                return round($size, 2) . ' ' . $units[$unit];
            }
        );
    }

    /**
     * Get OCR status label
     */
    public function ocrStatusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => ucfirst($this->ocr_status)
        );
    }

    /**
     * Get OCR status color for UI
     */
    public function ocrStatusColor(): Attribute
    {
        return Attribute::make(
            get: function() {
                return match($this->ocr_status) {
                    'pending' => 'gray',
                    'processing' => 'blue',
                    'completed' => 'green',
                    'failed' => 'red',
                    default => 'gray',
                };
            }
        );
    }

    /**
     * Get period label
     */
    public function periodLabel(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->period_from || !$this->period_to) {
                    return 'N/A';
                }
                
                return $this->period_from->format('d M Y') . ' - ' . 
                       $this->period_to->format('d M Y');
            }
        );
    }

    /**
     * Get processing duration
     */
    public function processingDuration(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->ocr_started_at || !$this->ocr_completed_at) {
                    return null;
                }
                
                return $this->ocr_started_at->diffInSeconds($this->ocr_completed_at);
            }
        );
    }

    /**
     * Get formatted processing duration
     */
    public function formattedProcessingDuration(): Attribute
    {
        return Attribute::make(
            get: function() {
                $duration = $this->processing_duration;
                
                if (!$duration) return 'N/A';
                
                if ($duration < 60) {
                    return $duration . 's';
                }
                
                $minutes = floor($duration / 60);
                $seconds = $duration % 60;
                
                return $minutes . 'm ' . $seconds . 's';
            }
        );
    }

    /**
     * Get net change (closing - opening balance)
     */
    public function netChange(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->opening_balance || !$this->closing_balance) {
                    return null;
                }
                
                return $this->closing_balance - $this->opening_balance;
            }
        );
    }

    /**
     * Get formatted net change
     */
    public function formattedNetChange(): Attribute
    {
        return Attribute::make(
            get: function() {
                $netChange = $this->net_change;
                
                if (!$netChange) return 'N/A';
                
                $prefix = $netChange >= 0 ? '+' : '';
                return $prefix . 'Rp ' . number_format(abs($netChange), 0, ',', '.');
            }
        );
    }

    /**
     * Get completion percentage
     */
    public function completionPercentage(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->total_transactions === 0) return 0;
                
                return round(($this->processed_transactions / $this->total_transactions) * 100, 2);
            }
        );
    }

    /**
     * Get matching percentage
     */
    public function matchingPercentage(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->total_transactions === 0) return 0;
                
                return round(($this->matched_transactions / $this->total_transactions) * 100, 2);
            }
        );
    }

    /**
     * Get verification percentage
     */
    public function verificationPercentage(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->total_transactions === 0) return 0;
                
                return round(($this->verified_transactions / $this->total_transactions) * 100, 2);
            }
        );
    }

    /**
     * Check if OCR is complete
     */
    public function isOcrComplete(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ocr_status === 'completed'
        );
    }

    /**
     * Check if has errors
     */
    public function hasErrors(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ocr_status === 'failed' || !empty($this->ocr_error)
        );
    }

    /**
     * Check if fully processed
     */
    public function isFullyProcessed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_transactions > 0 && 
                        $this->processed_transactions === $this->total_transactions
        );
    }

    /**
     * Check if fully matched
     */
    public function isFullyMatched(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_transactions > 0 && 
                        $this->matched_transactions === $this->total_transactions
        );
    }

    /**
     * Check if fully verified
     */
    public function isFullyVerified(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_transactions > 0 && 
                        $this->verified_transactions === $this->total_transactions
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark OCR as started
     */
    public function markOcrAsStarted(?string $jobId = null): bool
    {
        return $this->update([
            'ocr_status' => 'processing',
            'ocr_started_at' => now(),
            'ocr_job_id' => $jobId,
        ]);
    }

    /**
     * Mark OCR as completed
     */
    public function markOcrAsCompleted(array $response): bool
    {
        return $this->update([
            'ocr_status' => 'completed',
            'ocr_completed_at' => now(),
            'ocr_response' => $response,
        ]);
    }

    /**
     * Mark OCR as failed
     */
    public function markOcrAsFailed(string $error): bool
    {
        return $this->update([
            'ocr_status' => 'failed',
            'ocr_completed_at' => now(),
            'ocr_error' => $error,
        ]);
    }

    /**
     * Update statistics
     */
    public function updateStatistics(): bool
    {
        $stats = [
            'total_transactions' => $this->transactions()->count(),
            'processed_transactions' => $this->transactions()->whereNotNull('sub_category_id')->count(),
            'matched_transactions' => $this->transactions()->where('is_manual_category', false)->whereNotNull('sub_category_id')->count(),
            'unmatched_transactions' => $this->transactions()->whereNull('sub_category_id')->count(),
            'verified_transactions' => $this->transactions()->where('is_verified', true)->count(),
        ];

        return $this->update($stats);
    }

    /**
     * Mark as reconciled
     */
    public function markAsReconciled(?int $userId = null): bool
    {
        return $this->update([
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Unmark reconciliation
     */
    public function unmarkReconciliation(): bool
    {
        return $this->update([
            'is_reconciled' => false,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    /**
     * Get summary data
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'bank' => $this->bank->name,
            'account_number' => $this->account_number,
            'period' => $this->period_label,
            'opening_balance' => $this->opening_balance,
            'closing_balance' => $this->closing_balance,
            'net_change' => $this->net_change,
            'formatted_net_change' => $this->formatted_net_change,
            'total_transactions' => $this->total_transactions,
            'completion_percentage' => $this->completion_percentage,
            'matching_percentage' => $this->matching_percentage,
            'verification_percentage' => $this->verification_percentage,
            'ocr_status' => $this->ocr_status,
            'is_reconciled' => $this->is_reconciled,
            'file_size' => $this->formatted_file_size,
            'uploaded_at' => $this->uploaded_at->format('Y-m-d H:i:s'),
        ];
    }
}