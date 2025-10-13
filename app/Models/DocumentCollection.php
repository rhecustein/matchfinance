<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class DocumentCollection extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'name',
        'description',
        'color',
        'icon',
        'document_count',
        'total_transactions',
        'total_debit',
        'total_credit',
        'auto_add_new',
        'filter_settings',
        'is_active',
        'chat_count',
        'last_used_at',
    ];

    protected $casts = [
        'document_count' => 'integer',
        'total_transactions' => 'integer',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'auto_add_new' => 'boolean',
        'filter_settings' => 'array',
        'is_active' => 'boolean',
        'chat_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($collection) {
            if (empty($collection->uuid)) {
                $collection->uuid = (string) Str::uuid();
            }
        });

        // Update counts when items are added/removed
        static::saved(function ($collection) {
            $collection->updateStatistics();
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
     * Get the company that owns this collection
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this collection
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all document items in this collection
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)
                    ->orderBy('sort_order');
    }

    /**
     * Get only ready document items
     */
    public function readyItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class)
                    ->where('knowledge_status', 'ready')
                    ->orderBy('sort_order');
    }

    /**
     * Get all chat sessions using this collection
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class)
                    ->latest('last_activity_at');
    }

    /**
     * Get active chat sessions
     */
    public function activeChatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class)
                    ->where('is_archived', false)
                    ->latest('last_activity_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active collections only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Owned by specific user
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Recently used
     */
    public function scopeRecentlyUsed($query, $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Popular collections (most chats)
     */
    public function scopePopular($query, $minChats = 5)
    {
        return $query->where('chat_count', '>=', $minChats)
                    ->orderByDesc('chat_count');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Update collection statistics from items
     */
    public function updateStatistics(): void
    {
        $items = $this->items()->with('bankStatement.transactions')->get();
        
        $this->update([
            'document_count' => $items->count(),
            'total_transactions' => $items->sum(fn($item) => $item->bankStatement->total_transactions ?? 0),
            'total_debit' => $items->sum(fn($item) => $item->bankStatement->total_debit_amount ?? 0),
            'total_credit' => $items->sum(fn($item) => $item->bankStatement->total_credit_amount ?? 0),
        ]);
    }

    /**
     * Add a bank statement to this collection
     */
    public function addBankStatement(BankStatement $statement, int $sortOrder = null): DocumentItem
    {
        return DocumentItem::create([
            'document_collection_id' => $this->id,
            'bank_statement_id' => $statement->id,
            'sort_order' => $sortOrder ?? $this->items()->max('sort_order') + 1,
            'statement_period_from' => $statement->period_from,
            'statement_period_to' => $statement->period_to,
            'transaction_count' => $statement->total_transactions,
            'total_amount' => $statement->total_credit_amount - $statement->total_debit_amount,
        ]);
    }

    /**
     * Remove a bank statement from collection
     */
    public function removeBankStatement(BankStatement $statement): bool
    {
        return $this->items()
                    ->where('bank_statement_id', $statement->id)
                    ->delete() > 0;
    }

    /**
     * Check if collection contains a specific bank statement
     */
    public function hasBankStatement(BankStatement $statement): bool
    {
        return $this->items()
                    ->where('bank_statement_id', $statement->id)
                    ->exists();
    }

    /**
     * Get all bank statements in this collection
     */
    public function getBankStatements()
    {
        return BankStatement::whereIn(
            'id',
            $this->items()->pluck('bank_statement_id')
        )->get();
    }

    /**
     * Mark collection as used (update last_used_at)
     */
    public function markAsUsed(): void
    {
        $this->update([
            'last_used_at' => now(),
        ]);
    }

    /**
     * Increment chat count
     */
    public function incrementChatCount(): void
    {
        $this->increment('chat_count');
        $this->markAsUsed();
    }

    /**
     * Get collection color with fallback
     */
    public function getColorAttribute($value)
    {
        return $value ?? '#3B82F6';
    }

    /**
     * Get collection icon with fallback
     */
    public function getIconAttribute($value)
    {
        return $value ?? 'folder';
    }

    /**
     * Get formatted statistics summary
     */
    public function getStatisticsSummary(): array
    {
        return [
            'documents' => $this->document_count,
            'transactions' => $this->total_transactions,
            'total_debit' => number_format($this->total_debit, 2),
            'total_credit' => number_format($this->total_credit, 2),
            'net_amount' => number_format($this->total_credit - $this->total_debit, 2),
            'chats' => $this->chat_count,
        ];
    }
}