<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class DocumentItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'document_collection_id',
        'bank_statement_id',
        'sort_order',
        'notes',
        'tags',
        'knowledge_status',
        'knowledge_error',
        'processed_at',
        'statement_period_from',
        'statement_period_to',
        'transaction_count',
        'total_amount',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'tags' => 'array',
        'processed_at' => 'datetime',
        'statement_period_from' => 'date',
        'statement_period_to' => 'date',
        'transaction_count' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
            
            // Auto-assign next sort_order
            if (is_null($item->sort_order)) {
                $item->sort_order = static::where('document_collection_id', $item->document_collection_id)
                                          ->max('sort_order') + 1;
            }
        });

        // Update collection statistics when item changes
        static::saved(function ($item) {
            if ($item->collection) {
                $item->collection->updateStatistics();
            }
        });

        static::deleted(function ($item) {
            if ($item->collection) {
                $item->collection->updateStatistics();
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
     * Get the company (tenant)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent collection
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class, 'document_collection_id');
    }

    /**
     * Alias for collection relationship
     */
    public function documentCollection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class, 'document_collection_id');
    }

    /**
     * Get the bank statement (PDF document)
     */
    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    /**
     * Alias - Get the statement
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Ready for AI processing
     */
    public function scopeReady($query)
    {
        return $query->where('knowledge_status', 'ready');
    }

    /**
     * Scope: Pending processing
     */
    public function scopePending($query)
    {
        return $query->where('knowledge_status', 'pending');
    }

    /**
     * Scope: Currently processing
     */
    public function scopeProcessing($query)
    {
        return $query->where('knowledge_status', 'processing');
    }

    /**
     * Scope: Failed processing
     */
    public function scopeFailed($query)
    {
        return $query->where('knowledge_status', 'failed');
    }

    /**
     * Scope: In specific collection
     */
    public function scopeInCollection($query, $collectionId)
    {
        return $query->where('document_collection_id', $collectionId);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope: By knowledge status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('knowledge_status', $status);
    }

    /**
     * Scope: Processed items only
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    /**
     * Scope: Unprocessed items only
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Scope: Has errors
     */
    public function scopeHasErrors($query)
    {
        return $query->where('knowledge_status', 'failed')
                     ->whereNotNull('knowledge_error');
    }

    /**
     * Scope: Recently processed
     */
    public function scopeRecentlyProcessed($query, $hours = 24)
    {
        return $query->where('processed_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: By statement period
     */
    public function scopePeriodBetween($query, $from, $to)
    {
        return $query->where(function($q) use ($from, $to) {
            $q->whereBetween('statement_period_from', [$from, $to])
              ->orWhereBetween('statement_period_to', [$from, $to]);
        });
    }

    /**
     * Scope: Search by notes or tags
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('notes', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    /**
     * Scope: With eager loaded relationships for display
     */
    public function scopeWithDisplayData($query)
    {
        return $query->with([
            'bankStatement:id,uuid,original_filename,bank_id,period_start,period_end,ocr_status',
            'bankStatement.bank:id,name,code',
            'collection:id,uuid,name'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor & Mutator
    |--------------------------------------------------------------------------
    */

    /**
     * Get status badge class attribute
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->knowledge_status) {
            'ready' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
    }

    /**
     * Get status label attribute
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->knowledge_status) {
            'ready' => 'Ready',
            'processing' => 'Processing',
            'failed' => 'Failed',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    /**
     * Get status icon attribute
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->knowledge_status) {
            'ready' => 'check-circle',
            'processing' => 'clock',
            'failed' => 'x-circle',
            'pending' => 'hourglass',
            default => 'help-circle',
        };
    }

    /**
     * Get display name attribute
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->bankStatement) {
            return $this->bankStatement->original_filename ?? 
                   "Statement #{$this->bank_statement_id}";
        }
        
        return "Document #{$this->id}";
    }

    /**
     * Get period label attribute
     */
    public function getPeriodLabelAttribute(): ?string
    {
        if (!$this->statement_period_from || !$this->statement_period_to) {
            return null;
        }

        $from = $this->statement_period_from->format('M d, Y');
        $to = $this->statement_period_to->format('M d, Y');
        
        return "{$from} - {$to}";
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 2, ',', '.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Knowledge Status Management
    |--------------------------------------------------------------------------
    */

    /**
     * Mark knowledge as ready
     */
    public function markAsReady(): void
    {
        $this->update([
            'knowledge_status' => 'ready',
            'knowledge_error' => null,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'knowledge_status' => 'processing',
            'knowledge_error' => null,
        ]);
    }

    /**
     * Mark knowledge processing as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'knowledge_status' => 'failed',
            'knowledge_error' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Reset to pending status
     */
    public function resetToPending(): void
    {
        $this->update([
            'knowledge_status' => 'pending',
            'knowledge_error' => null,
            'processed_at' => null,
        ]);
    }

    /**
     * Retry processing
     */
    public function retry(): void
    {
        if ($this->hasFailed()) {
            $this->resetToPending();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Status Checks
    |--------------------------------------------------------------------------
    */

    /**
     * Check if ready for AI
     */
    public function isReady(): bool
    {
        return $this->knowledge_status === 'ready';
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->knowledge_status === 'pending';
    }

    /**
     * Check if processing
     */
    public function isProcessing(): bool
    {
        return $this->knowledge_status === 'processing';
    }

    /**
     * Check if processing failed
     */
    public function hasFailed(): bool
    {
        return $this->knowledge_status === 'failed';
    }

    /**
     * Check if processed (ready or failed)
     */
    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    /**
     * Check if has error
     */
    public function hasError(): bool
    {
        return !empty($this->knowledge_error);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Metadata Management
    |--------------------------------------------------------------------------
    */

    /**
     * Sync metadata from bank statement
     */
    public function syncMetadata(): void
    {
        $statement = $this->bankStatement;
        
        if (!$statement) {
            return;
        }

        $this->update([
            'statement_period_from' => $statement->period_start,
            'statement_period_to' => $statement->period_end,
            'transaction_count' => $statement->total_transactions ?? 0,
            'total_amount' => $this->calculateTotalAmount($statement),
        ]);
    }

    /**
     * Calculate total amount from statement
     */
    protected function calculateTotalAmount($statement): float
    {
        $credit = $statement->total_credit_amount ?? 0;
        $debit = $statement->total_debit_amount ?? 0;
        
        return $credit - $debit;
    }

    /**
     * Update sort order
     */
    public function updateSortOrder(int $newOrder): void
    {
        $this->update(['sort_order' => $newOrder]);
    }

    /**
     * Move up in order
     */
    public function moveUp(): bool
    {
        if ($this->sort_order <= 0) {
            return false;
        }

        $this->update(['sort_order' => $this->sort_order - 1]);
        return true;
    }

    /**
     * Move down in order
     */
    public function moveDown(): bool
    {
        $maxOrder = static::where('document_collection_id', $this->document_collection_id)
                          ->max('sort_order');
        
        if ($this->sort_order >= $maxOrder) {
            return false;
        }

        $this->update(['sort_order' => $this->sort_order + 1]);
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Tags Management
    |--------------------------------------------------------------------------
    */

    /**
     * Add tag
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove tag
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        
        $this->update(['tags' => $tags]);
    }

    /**
     * Has tag
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Get tags as comma-separated string
     */
    public function getTagsString(): string
    {
        return empty($this->tags) ? '' : implode(', ', $this->tags);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Information & Display
    |--------------------------------------------------------------------------
    */

    /**
     * Get item information
     */
    public function getInfo(): array
    {
        return [
            'uuid' => $this->uuid,
            'display_name' => $this->display_name,
            'status' => [
                'value' => $this->knowledge_status,
                'label' => $this->status_label,
                'badge_class' => $this->status_badge_class,
                'icon' => $this->status_icon,
            ],
            'metadata' => [
                'period' => $this->period_label,
                'transaction_count' => number_format($this->transaction_count),
                'total_amount' => $this->formatted_total_amount,
                'sort_order' => $this->sort_order,
            ],
            'processing' => [
                'processed_at' => $this->processed_at?->diffForHumans(),
                'has_error' => $this->hasError(),
                'error' => $this->knowledge_error,
            ],
            'organization' => [
                'tags' => $this->tags ?? [],
                'tags_string' => $this->getTagsString(),
                'has_notes' => !empty($this->notes),
            ],
        ];
    }

    /**
     * Get statement info
     */
    public function getStatementInfo(): ?array
    {
        if (!$this->bankStatement) {
            return null;
        }

        $statement = $this->bankStatement;
        
        return [
            'uuid' => $statement->uuid,
            'filename' => $statement->original_filename,
            'bank' => $statement->bank?->name,
            'period' => [
                'from' => $statement->period_start?->format('Y-m-d'),
                'to' => $statement->period_end?->format('Y-m-d'),
            ],
            'ocr_status' => $statement->ocr_status,
            'is_verified' => $statement->is_verified ?? false,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create new item for collection
     */
    public static function createForCollection(
        int $collectionId,
        int $statementId,
        array $options = []
    ): self {
        return static::create(array_merge([
            'document_collection_id' => $collectionId,
            'bank_statement_id' => $statementId,
            'knowledge_status' => 'pending',
        ], $options));
    }

    /**
     * Get items needing processing
     */
    public static function getNeedingProcessing(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::pending()
                     ->with('bankStatement')
                     ->orderBy('created_at')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Bulk update status
     */
    public static function bulkUpdateStatus(array $ids, string $status): int
    {
        return static::whereIn('id', $ids)
                     ->update(['knowledge_status' => $status]);
    }

    /**
     * Reorder items in collection
     */
    public static function reorderInCollection(int $collectionId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $itemId) {
            static::where('id', $itemId)
                  ->where('document_collection_id', $collectionId)
                  ->update(['sort_order' => $index]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get processing statistics for collection
     */
    public static function getProcessingStats(int $collectionId): array
    {
        $items = static::where('document_collection_id', $collectionId)->get();
        
        return [
            'total' => $items->count(),
            'ready' => $items->where('knowledge_status', 'ready')->count(),
            'pending' => $items->where('knowledge_status', 'pending')->count(),
            'processing' => $items->where('knowledge_status', 'processing')->count(),
            'failed' => $items->where('knowledge_status', 'failed')->count(),
            'percentage_ready' => $items->count() > 0 
                ? round(($items->where('knowledge_status', 'ready')->count() / $items->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get summary for collection
     */
    public static function getSummary(int $collectionId): array
    {
        return [
            'total_transactions' => static::where('document_collection_id', $collectionId)
                                          ->sum('transaction_count'),
            'total_amount' => static::where('document_collection_id', $collectionId)
                                    ->sum('total_amount'),
            'date_range' => [
                'earliest' => static::where('document_collection_id', $collectionId)
                                    ->whereNotNull('statement_period_from')
                                    ->min('statement_period_from'),
                'latest' => static::where('document_collection_id', $collectionId)
                                  ->whereNotNull('statement_period_to')
                                  ->max('statement_period_to'),
            ],
        ];
    }
}