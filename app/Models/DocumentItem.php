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
        });

        // Update collection statistics when item is saved/deleted
        static::saved(function ($item) {
            $item->collection->updateStatistics();
        });

        static::deleted(function ($item) {
            $item->collection->updateStatistics();
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
     * Get the company
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
     * Get the bank statement (PDF)
     */
    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
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
     * Scope: Failed processing
     */
    public function scopeFailed($query)
    {
        return $query->where('knowledge_status', 'failed');
    }

    /**
     * Scope: Processing
     */
    public function scopeProcessing($query)
    {
        return $query->where('knowledge_status', 'processing');
    }

    /**
     * Scope: In specific collection
     */
    public function scopeInCollection($query, $collectionId)
    {
        return $query->where('document_collection_id', $collectionId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
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
     * Reset to pending
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
     * Check if ready for AI
     */
    public function isReady(): bool
    {
        return $this->knowledge_status === 'ready';
    }

    /**
     * Check if processing failed
     */
    public function hasFailed(): bool
    {
        return $this->knowledge_status === 'failed';
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->knowledge_status) {
            'ready' => 'bg-green-100 text-green-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'failed' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
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
     * Sync metadata from bank statement
     */
    public function syncMetadata(): void
    {
        $statement = $this->bankStatement;
        
        if ($statement) {
            $this->update([
                'statement_period_from' => $statement->period_from,
                'statement_period_to' => $statement->period_to,
                'transaction_count' => $statement->total_transactions,
                'total_amount' => ($statement->total_credit_amount ?? 0) - ($statement->total_debit_amount ?? 0),
            ]);
        }
    }
}