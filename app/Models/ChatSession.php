<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class ChatSession extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'title',
        'mode',
        'bank_statement_id',
        'document_collection_id',
        'date_from',
        'date_to',
        'type_ids',
        'category_ids',
        'account_ids',
        'ai_model',
        'temperature',
        'max_tokens',
        'message_count',
        'total_tokens',
        'total_cost',
        'context_summary',
        'context_transaction_count',
        'last_activity_at',
        'is_archived',
        'is_pinned',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'type_ids' => 'array',
        'category_ids' => 'array',
        'account_ids' => 'array',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'message_count' => 'integer',
        'total_tokens' => 'integer',
        'total_cost' => 'decimal:6',
        'context_summary' => 'array',
        'context_transaction_count' => 'integer',
        'last_activity_at' => 'datetime',
        'is_archived' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
            if (empty($session->last_activity_at)) {
                $session->last_activity_at = now();
            }
        });

        // Update collection usage when session is created
        static::created(function ($session) {
            if ($session->mode === 'collection' && $session->documentCollection) {
                $session->documentCollection->incrementChatCount();
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
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who owns this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bank statement (for single mode)
     */
    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    /**
     * Get the document collection (for collection mode)
     */
    public function documentCollection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class);
    }

    /**
     * Get all messages in this session
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
                    ->orderBy('created_at');
    }

    /**
     * Get latest messages
     */
    public function latestMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
                    ->latest()
                    ->limit(50);
    }

    /**
     * Get user messages only
     */
    public function userMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
                    ->where('role', 'user')
                    ->orderBy('created_at');
    }

    /**
     * Get assistant messages only
     */
    public function assistantMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)
                    ->where('role', 'assistant')
                    ->orderBy('created_at');
    }

    /**
     * Get the first message (for auto-title)
     */
    public function firstMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)
                    ->where('role', 'user')
                    ->oldest();
    }

    /**
     * Get the knowledge snapshot
     */
    public function knowledgeSnapshot(): HasOne
    {
        return $this->hasOne(ChatKnowledgeSnapshot::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active (not archived)
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Archived
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Pinned
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: Single mode
     */
    public function scopeSingleMode($query)
    {
        return $query->where('mode', 'single');
    }

    /**
     * Scope: Collection mode
     */
    public function scopeCollectionMode($query)
    {
        return $query->where('mode', 'collection');
    }

    /**
     * Scope: Recent activity
     */
    public function scopeRecentActivity($query, $days = 7)
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Owned by user
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Update activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Increment message count
     */
    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->updateActivity();
    }

    /**
     * Add to total tokens and cost
     */
    public function addUsage(int $tokens, float $cost): void
    {
        $this->increment('total_tokens', $tokens);
        $this->increment('total_cost', $cost);
    }

    /**
     * Archive this session
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive this session
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Pin this session
     */
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin this session
     */
    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    /**
     * Generate auto title from first message
     */
    public function generateTitle(): void
    {
        if (!$this->title && $this->firstMessage) {
            $title = Str::limit($this->firstMessage->content, 50, '...');
            $this->update(['title' => $title]);
        }
    }

    /**
     * Check if session is ready for chat
     */
    public function isReady(): bool
    {
        if ($this->mode === 'single') {
            return $this->bankStatement && 
                   $this->bankStatement->ocr_status === 'completed';
        }

        if ($this->mode === 'collection') {
            return $this->documentCollection && 
                   $this->documentCollection->is_active &&
                   $this->documentCollection->document_count > 0;
        }

        return false;
    }

    /**
     * Get context source description
     */
    public function getContextDescription(): string
    {
        if ($this->mode === 'single') {
            return $this->bankStatement->original_filename ?? 'Unknown PDF';
        }

        if ($this->mode === 'collection') {
            return $this->documentCollection->name ?? 'Unknown Collection';
        }

        return 'No context';
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCost(): string
    {
        return '$' . number_format($this->total_cost, 4);
    }

    /**
     * Get AI model display name
     */
    public function getModelDisplayName(): string
    {
        return match($this->ai_model) {
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            default => $this->ai_model,
        };
    }

    /**
     * Get session statistics
     */
    public function getStatistics(): array
    {
        return [
            'messages' => $this->message_count,
            'tokens' => number_format($this->total_tokens),
            'cost' => $this->getFormattedCost(),
            'transactions' => number_format($this->context_transaction_count),
            'duration' => $this->created_at->diffForHumans($this->last_activity_at),
        ];
    }
}