<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class BankStatement extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
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
        //tambahan
        'matching_status',
        'matching_notes',
        'matching_started_at',
        'matching_completed_at',
        'account_matching_status',
        'account_matching_started_at',
        
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

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($statement) {
            if (empty($statement->uuid)) {
                $statement->uuid = (string) Str::uuid();
            }
            
            if (empty($statement->uploaded_at)) {
                $statement->uploaded_at = now();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    /**
     * Scope: Not in any collection
     */
    public function scopeNotInCollection($query)
    {
        return $query->whereDoesntHave('documentItem');
    }

    /**
     * Scope: In a collection
     */
    public function scopeInCollection($query)
    {
        return $query->whereHas('documentItem');
    }

    /**
     * Check if this statement is already in a collection
     */
    public function isInCollection(): bool
    {
        return $this->documentItem()->exists();
    }
        /*
    |--------------------------------------------------------------------------
    | Relationships - Document Collections (AI Chat)
    |--------------------------------------------------------------------------
    */

    /**
     * Get the document item (if this statement is in a collection)
     */
    public function documentItem(): HasOne
    {
        return $this->hasOne(DocumentItem::class);
    }

    /**
     * Get the document items (if this statement is in multiple collections)
     */
    public function documentItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    /**
     * Get the document collection through document item
     */
    public function documentCollection(): HasOneThrough
    {
        return $this->hasOneThrough(
            DocumentCollection::class,
            DocumentItem::class,
            'bank_statement_id',
            'id',
            'id',
            'document_collection_id'
        );
    }

    /**
     * Get chat sessions related to this statement
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }


    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class)->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
                    ->whereNotNull('sub_category_id');
    }

    public function unmatchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
                    ->whereNull('sub_category_id');
    }

    public function verifiedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
                    ->where('is_verified', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('ocr_status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('ocr_status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('ocr_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('ocr_status', 'failed');
    }

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeForBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePeriodRange($query, $from, $to)
    {
        return $query->where(function($q) use ($from, $to) {
            $q->whereBetween('period_from', [$from, $to])
              ->orWhereBetween('period_to', [$from, $to]);
        });
    }

    public function scopeForAccount($query, $accountNumber)
    {
        return $query->where('account_number', $accountNumber);
    }

    public function scopeRecentlyUploaded($query, $days = 30)
    {
        return $query->where('uploaded_at', '>=', now()->subDays($days));
    }

    public function scopeProcessedSuccessfully($query)
    {
        return $query->where('ocr_status', 'completed')
                     ->where('total_transactions', '>', 0);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function($q) {
            $q->where('ocr_status', 'failed')
              ->orWhere(function($subQ) {
                  $subQ->where('ocr_status', 'completed')
                       ->whereColumn('matched_transactions', '<', 'total_transactions');
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | OCR Status Methods
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->ocr_status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->ocr_status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->ocr_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->ocr_status === 'failed';
    }

    public function markOcrAsStarted(?string $jobId = null): bool
    {
        return $this->update([
            'ocr_status' => 'processing',
            'ocr_started_at' => now(),
            'ocr_job_id' => $jobId,
        ]);
    }

    public function markOcrAsCompleted(array $response): bool
    {
        return $this->update([
            'ocr_status' => 'completed',
            'ocr_completed_at' => now(),
            'ocr_response' => $response,
        ]);
    }

    public function markOcrAsFailed(string $error): bool
    {
        return $this->update([
            'ocr_status' => 'failed',
            'ocr_completed_at' => now(),
            'ocr_error' => $error,
        ]);
    }

    public function resetOcrStatus(): bool
    {
        return $this->update([
            'ocr_status' => 'pending',
            'ocr_started_at' => null,
            'ocr_completed_at' => null,
            'ocr_response' => null,
            'ocr_error' => null,
            'ocr_job_id' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Methods
    |--------------------------------------------------------------------------
    */

    public function isReconciled(): bool
    {
        return $this->is_reconciled;
    }

    public function markAsReconciled(?int $userId = null): bool
    {
        return $this->update([
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => $userId ?? auth()->id(),
        ]);
    }

    public function unmarkReconciliation(): bool
    {
        return $this->update([
            'is_reconciled' => false,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    public function toggleReconciliation(?int $userId = null): bool
    {
        if ($this->is_reconciled) {
            return $this->unmarkReconciliation();
        }
        return $this->markAsReconciled($userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function updateStatistics(): bool
    {
        $stats = [
            'total_transactions' => $this->transactions()->count(),
            'processed_transactions' => $this->transactions()->whereNotNull('sub_category_id')->count(),
            'matched_transactions' => $this->transactions()
                                          ->where('is_manual_category', false)
                                          ->whereNotNull('sub_category_id')
                                          ->count(),
            'unmatched_transactions' => $this->transactions()->whereNull('sub_category_id')->count(),
            'verified_transactions' => $this->transactions()->where('is_verified', true)->count(),
        ];

        return $this->update($stats);
    }

    public function recalculateFinancials(): bool
    {
        $financials = [
            'total_credit_count' => $this->transactions()->where('credit_amount', '>', 0)->count(),
            'total_debit_count' => $this->transactions()->where('debit_amount', '>', 0)->count(),
            'total_credit_amount' => $this->transactions()->sum('credit_amount'),
            'total_debit_amount' => $this->transactions()->sum('debit_amount'),
        ];

        return $this->update($financials);
    }

    public function getCompletionPercentage(): float
    {
        if ($this->total_transactions === 0) return 0;
        return round(($this->processed_transactions / $this->total_transactions) * 100, 2);
    }

    public function getMatchingPercentage(): float
    {
        if ($this->total_transactions === 0) return 0;
        return round(($this->matched_transactions / $this->total_transactions) * 100, 2);
    }

    public function getVerificationPercentage(): float
    {
        if ($this->total_transactions === 0) return 0;
        return round(($this->verified_transactions / $this->total_transactions) * 100, 2);
    }

    public function getNetChange()
    {
        if (!$this->opening_balance || !$this->closing_balance) {
            return null;
        }
        return $this->closing_balance - $this->opening_balance;
    }

    public function getProcessingDuration(): ?int
    {
        if (!$this->ocr_started_at || !$this->ocr_completed_at) {
            return null;
        }
        return $this->ocr_started_at->diffInSeconds($this->ocr_completed_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Methods
    |--------------------------------------------------------------------------
    */

    public function isFullyProcessed(): bool
    {
        return $this->total_transactions > 0 && 
               $this->processed_transactions === $this->total_transactions;
    }

    public function isFullyMatched(): bool
    {
        return $this->total_transactions > 0 && 
               $this->matched_transactions === $this->total_transactions;
    }

    public function isFullyVerified(): bool
    {
        return $this->total_transactions > 0 && 
               $this->verified_transactions === $this->total_transactions;
    }

    public function hasErrors(): bool
    {
        return $this->ocr_status === 'failed' || !empty($this->ocr_error);
    }

    public function hasTransactions(): bool
    {
        return $this->total_transactions > 0;
    }

    public function needsReview(): bool
    {
        return $this->isCompleted() && 
               !$this->isFullyMatched() && 
               $this->hasTransactions();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
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

    public function ocrStatusLabel(): Attribute
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->ocr_status] ?? 'Unknown'
        );
    }

    public function ocrStatusBadgeClass(): Attribute
    {
        $classes = [
            'pending' => 'bg-gray-100 text-gray-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        return Attribute::make(
            get: fn() => $classes[$this->ocr_status] ?? 'bg-gray-100 text-gray-800'
        );
    }

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

    public function formattedProcessingDuration(): Attribute
    {
        return Attribute::make(
            get: function() {
                $duration = $this->getProcessingDuration();
                
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

    public function formattedNetChange(): Attribute
    {
        return Attribute::make(
            get: function() {
                $netChange = $this->getNetChange();
                
                if (is_null($netChange)) return 'N/A';
                
                $prefix = $netChange >= 0 ? '+' : '';
                return $prefix . 'Rp ' . number_format(abs($netChange), 0, ',', '.');
            }
        );
    }

    public function formattedOpeningBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->opening_balance ?? 0, 0, ',', '.')
        );
    }

    public function formattedClosingBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->closing_balance ?? 0, 0, ',', '.')
        );
    }

    public function formattedTotalCredit(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->total_credit_amount ?? 0, 0, ',', '.')
        );
    }

    public function formattedTotalDebit(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->total_debit_amount ?? 0, 0, ',', '.')
        );
    }

    public function completionPercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getCompletionPercentage()
        );
    }

    public function matchingPercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getMatchingPercentage()
        );
    }

    public function verificationPercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getVerificationPercentage()
        );
    }

    public function bankName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->bank?->name ?? $this->bank_name ?? 'Unknown Bank'
        );
    }

    public function uploaderName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->user?->name ?? 'Unknown User'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'bank' => $this->bank_name,
            'account_number' => $this->account_number,
            'period' => $this->period_label,
            'opening_balance' => $this->opening_balance,
            'closing_balance' => $this->closing_balance,
            'net_change' => $this->getNetChange(),
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

    public function getStatistics(): array
    {
        return [
            'total_transactions' => $this->total_transactions,
            'processed_transactions' => $this->processed_transactions,
            'matched_transactions' => $this->matched_transactions,
            'unmatched_transactions' => $this->unmatched_transactions,
            'verified_transactions' => $this->verified_transactions,
            'completion_percentage' => $this->completion_percentage,
            'matching_percentage' => $this->matching_percentage,
            'verification_percentage' => $this->verification_percentage,
            'total_credit_count' => $this->total_credit_count,
            'total_debit_count' => $this->total_debit_count,
            'total_credit_amount' => $this->total_credit_amount,
            'total_debit_amount' => $this->total_debit_amount,
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->total_transactions === 0 || 
               (!$this->is_reconciled && $this->verified_transactions === 0);
    }

    public function canBeProcessed(): bool
    {
        return $this->ocr_status === 'pending' || $this->ocr_status === 'failed';
    }

    public function getFileUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function duplicate(): self
    {
        $attributes = $this->toArray();
        
        unset($attributes['id'], $attributes['uuid'], $attributes['file_hash'],
              $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at'],
              $attributes['reconciled_at'], $attributes['reconciled_by']);
        
        $attributes['ocr_status'] = 'pending';
        $attributes['ocr_started_at'] = null;
        $attributes['ocr_completed_at'] = null;
        $attributes['is_reconciled'] = false;
        $attributes['uploaded_at'] = now();
        
        return static::create($attributes);
    }
}