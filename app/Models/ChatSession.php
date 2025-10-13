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
     * Get the company (tenant)
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
     * Owner alias (same as user)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
     * Get latest messages (for display)
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
     * Get the first user message (for auto-title generation)
     */
    public function firstMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)
                    ->where('role', 'user')
                    ->oldest();
    }

    /**
     * Get the knowledge snapshot for this session
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
     * Scope: Active sessions (not archived)
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Archived sessions
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Pinned sessions
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: Single mode sessions
     */
    public function scopeSingleMode($query)
    {
        return $query->where('mode', 'single');
    }

    /**
     * Scope: Collection mode sessions
     */
    public function scopeCollectionMode($query)
    {
        return $query->where('mode', 'collection');
    }

    /**
     * Scope: Recent activity within X days
     */
    public function scopeRecentActivity($query, $days = 7)
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Owned by specific user
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Order by last activity (newest first)
     */
    public function scopeOrderedByActivity($query)
    {
        return $query->orderByDesc('is_pinned')
                     ->orderByDesc('last_activity_at');
    }

    /**
     * Scope: With context (has bank_statement or document_collection)
     */
    public function scopeWithContext($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('bank_statement_id')
              ->orWhereNotNull('document_collection_id');
        });
    }

    /**
     * Scope: By mode and statement
     */
    public function scopeForStatement($query, $statementId)
    {
        return $query->where('mode', 'single')
                     ->where('bank_statement_id', $statementId);
    }

    /**
     * Scope: By mode and collection
     */
    public function scopeForCollection($query, $collectionId)
    {
        return $query->where('mode', 'collection')
                     ->where('document_collection_id', $collectionId);
    }

    /**
     * Scope: Search by title
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%");
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor & Mutator
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted cost attribute
     */
    public function getFormattedCostAttribute(): string
    {
        return '$' . number_format($this->total_cost, 4);
    }

    /**
     * Get model display name attribute
     */
    public function getModelDisplayNameAttribute(): string
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
     * Get context description attribute
     */
    public function getContextDescriptionAttribute(): string
    {
        if ($this->mode === 'single' && $this->bankStatement) {
            return $this->bankStatement->original_filename ?? 'Unknown PDF';
        }

        if ($this->mode === 'collection' && $this->documentCollection) {
            return $this->documentCollection->name ?? 'Unknown Collection';
        }

        return 'No context';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Activity Management
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
     * Increment message count and update activity
     */
    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->updateActivity();
    }

    /**
     * Add token usage and cost
     */
    public function addUsage(int $tokens, float $cost): void
    {
        $this->increment('total_tokens', $tokens);
        $this->increment('total_cost', $cost);
        $this->updateActivity();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Session Management
    |--------------------------------------------------------------------------
    */

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
     * Toggle archive status
     */
    public function toggleArchive(): void
    {
        $this->update(['is_archived' => !$this->is_archived]);
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
     * Toggle pin status
     */
    public function togglePin(): void
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }

    /**
     * Generate auto title from first message
     */
    public function generateTitle(): ?string
    {
        if ($this->title) {
            return $this->title; // Already has title
        }

        $firstMessage = $this->firstMessage;
        
        if (!$firstMessage) {
            return null;
        }

        $title = Str::limit($firstMessage->content, 50, '...');
        $this->update(['title' => $title]);
        
        return $title;
    }

    /**
     * Update title
     */
    public function updateTitle(string $title): void
    {
        $this->update(['title' => $title]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Context & Readiness
    |--------------------------------------------------------------------------
    */

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
     * Check if this is single mode
     */
    public function isSingleMode(): bool
    {
        return $this->mode === 'single';
    }

    /**
     * Check if this is collection mode
     */
    public function isCollectionMode(): bool
    {
        return $this->mode === 'collection';
    }

    /**
     * Check if session is archived
     */
    public function isArchived(): bool
    {
        return $this->is_archived;
    }

    /**
     * Check if session is pinned
     */
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    /**
     * Check if session has filters applied
     */
    public function hasFilters(): bool
    {
        return $this->date_from !== null ||
               $this->date_to !== null ||
               !empty($this->type_ids) ||
               !empty($this->category_ids) ||
               !empty($this->account_ids);
    }

    /**
     * Get active filter count
     */
    public function getActiveFilterCount(): int
    {
        $count = 0;
        
        if ($this->date_from || $this->date_to) $count++;
        if (!empty($this->type_ids)) $count++;
        if (!empty($this->category_ids)) $count++;
        if (!empty($this->account_ids)) $count++;
        
        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Statistics & Display
    |--------------------------------------------------------------------------
    */

    /**
     * Get session statistics
     */
    public function getStatistics(): array
    {
        return [
            'messages' => $this->message_count,
            'tokens' => number_format($this->total_tokens),
            'cost' => $this->formatted_cost,
            'transactions' => number_format($this->context_transaction_count),
            'duration' => $this->created_at->diffForHumans($this->last_activity_at, true),
            'last_activity' => $this->last_activity_at?->diffForHumans() ?? 'Never',
        ];
    }

    /**
     * Get context information
     */
    public function getContextInfo(): array
    {
        $info = [
            'mode' => $this->mode,
            'description' => $this->context_description,
            'has_filters' => $this->hasFilters(),
            'filter_count' => $this->getActiveFilterCount(),
        ];

        if ($this->mode === 'single' && $this->bankStatement) {
            $info['source'] = [
                'type' => 'bank_statement',
                'id' => $this->bankStatement->id,
                'uuid' => $this->bankStatement->uuid,
                'bank' => $this->bankStatement->bank?->name,
                'period' => $this->bankStatement->period_start->format('M Y'),
                'status' => $this->bankStatement->ocr_status,
            ];
        }

        if ($this->mode === 'collection' && $this->documentCollection) {
            $info['source'] = [
                'type' => 'document_collection',
                'id' => $this->documentCollection->id,
                'uuid' => $this->documentCollection->uuid,
                'name' => $this->documentCollection->name,
                'document_count' => $this->documentCollection->document_count,
                'status' => $this->documentCollection->is_active ? 'active' : 'inactive',
            ];
        }

        return $info;
    }

    /**
     * Get filters as readable array
     */
    public function getFilters(): array
    {
        return [
            'date_range' => $this->date_from || $this->date_to ? [
                'from' => $this->date_from?->format('Y-m-d'),
                'to' => $this->date_to?->format('Y-m-d'),
            ] : null,
            'types' => $this->type_ids,
            'categories' => $this->category_ids,
            'accounts' => $this->account_ids,
        ];
    }

    /**
     * Get AI settings
     */
    public function getAiSettings(): array
    {
        return [
            'model' => $this->ai_model,
            'model_display' => $this->model_display_name,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new single mode session
     */
    public static function createForStatement(
        int $bankStatementId,
        ?string $title = null,
        array $options = []
    ): self {
        return static::create(array_merge([
            'mode' => 'single',
            'bank_statement_id' => $bankStatementId,
            'title' => $title,
            'user_id' => auth()->id(),
        ], $options));
    }

    /**
     * Create a new collection mode session
     */
    public static function createForCollection(
        int $documentCollectionId,
        ?string $title = null,
        array $options = []
    ): self {
        return static::create(array_merge([
            'mode' => 'collection',
            'document_collection_id' => $documentCollectionId,
            'title' => $title,
            'user_id' => auth()->id(),
        ], $options));
    }

    /**
     * Get or create session for a statement
     */
    public static function getOrCreateForStatement(int $bankStatementId): self
    {
        return static::firstOrCreate(
            [
                'bank_statement_id' => $bankStatementId,
                'user_id' => auth()->id(),
                'mode' => 'single',
            ],
            [
                'title' => null, // Will be auto-generated
            ]
        );
    }
}