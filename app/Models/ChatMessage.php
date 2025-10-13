<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'chat_session_id',
        'role',
        'content',
        'referenced_transactions',
        'referenced_categories',
        'referenced_accounts',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'model',
        'response_time_ms',
        'user_rating',
        'user_feedback',
        'status',
        'error_message',
    ];

    protected $casts = [
        'referenced_transactions' => 'array',
        'referenced_categories' => 'array',
        'referenced_accounts' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'decimal:6',
        'response_time_ms' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($message) {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });

        // Update session stats when message is created
        static::created(function ($message) {
            $message->chatSession->incrementMessageCount();
            
            if ($message->total_tokens > 0 && $message->cost > 0) {
                $message->chatSession->addUsage($message->total_tokens, $message->cost);
            }

            // Auto-generate title if first user message
            if ($message->role === 'user' && $message->chatSession->message_count === 1) {
                $message->chatSession->generateTitle();
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
     * Get the chat session
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: User messages
     */
    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope: Assistant messages
     */
    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Scope: System messages
     */
    public function scopeSystem($query)
    {
        return $query->where('role', 'system');
    }

    /**
     * Scope: Completed messages
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: With references
     */
    public function scopeWithReferences($query)
    {
        return $query->whereNotNull('referenced_transactions')
                    ->orWhereNotNull('referenced_categories')
                    ->orWhereNotNull('referenced_accounts');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a user message
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message
     */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Check if this is a system message
     */
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    /**
     * Check if message has references
     */
    public function hasReferences(): bool
    {
        return !empty($this->referenced_transactions) ||
               !empty($this->referenced_categories) ||
               !empty($this->referenced_accounts);
    }

    /**
     * Get referenced transactions
     */
    public function getReferencedTransactions()
    {
        if (empty($this->referenced_transactions)) {
            return collect();
        }

        return StatementTransaction::whereIn('id', $this->referenced_transactions)->get();
    }

    /**
     * Get referenced categories
     */
    public function getReferencedCategories()
    {
        if (empty($this->referenced_categories)) {
            return collect();
        }

        return Category::whereIn('id', $this->referenced_categories)->get();
    }

    /**
     * Get referenced accounts
     */
    public function getReferencedAccounts()
    {
        if (empty($this->referenced_accounts)) {
            return collect();
        }

        return Account::whereIn('id', $this->referenced_accounts)->get();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Set user rating
     */
    public function setRating(string $rating, ?string $feedback = null): void
    {
        $this->update([
            'user_rating' => $rating,
            'user_feedback' => $feedback,
        ]);
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCost(): string
    {
        return '$' . number_format($this->cost, 4);
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTime(): string
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms . 'ms';
        }

        return round($this->response_time_ms / 1000, 2) . 's';
    }

    /**
     * Get role badge class for UI
     */
    public function getRoleBadgeClass(): string
    {
        return match($this->role) {
            'user' => 'bg-blue-100 text-blue-800',
            'assistant' => 'bg-green-100 text-green-800',
            'system' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'completed' => 'bg-green-100 text-green-800',
            'streaming' => 'bg-blue-100 text-blue-800',
            'failed' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Convert message to array for API
     */
    public function toApiFormat(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }

    /**
     * Get message statistics
     */
    public function getStatistics(): array
    {
        return [
            'tokens' => [
                'prompt' => number_format($this->prompt_tokens),
                'completion' => number_format($this->completion_tokens),
                'total' => number_format($this->total_tokens),
            ],
            'cost' => $this->getFormattedCost(),
            'response_time' => $this->getFormattedResponseTime(),
            'model' => $this->model,
            'has_references' => $this->hasReferences(),
            'reference_count' => count($this->referenced_transactions ?? []) +
                               count($this->referenced_categories ?? []) +
                               count($this->referenced_accounts ?? []),
        ];
    }
}