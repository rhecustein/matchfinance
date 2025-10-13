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

        static::created(function ($message) {
            if ($message->chatSession) {
                $message->chatSession->incrementMessageCount();
                
                if ($message->total_tokens > 0 && $message->cost > 0) {
                    $message->chatSession->addUsage($message->total_tokens, $message->cost);
                }

                if ($message->role === 'user' && $message->chatSession->message_count === 1) {
                    $message->chatSession->generateTitle();
                }
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

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    public function scopeSystem($query)
    {
        return $query->where('role', 'system');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeStreaming($query)
    {
        return $query->where('status', 'streaming');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeWithReferences($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('referenced_transactions')
              ->orWhereNotNull('referenced_categories')
              ->orWhereNotNull('referenced_accounts');
        });
    }

    public function scopeRated($query)
    {
        return $query->whereNotNull('user_rating');
    }

    public function scopeHelpful($query)
    {
        return $query->where('user_rating', 'helpful');
    }

    public function scopeNotHelpful($query)
    {
        return $query->where('user_rating', 'not_helpful');
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('chat_session_id', $sessionId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at');
    }

    public function scopeLatest($query, $limit = 50)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor & Mutator
    |--------------------------------------------------------------------------
    */

    public function getFormattedCostAttribute(): string
    {
        return '$' . number_format($this->cost, 4);
    }

    public function getFormattedResponseTimeAttribute(): string
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms . 'ms';
        }

        return round($this->response_time_ms / 1000, 2) . 's';
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match($this->role) {
            'user' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'assistant' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'system' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'streaming' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getRoleIconAttribute(): string
    {
        return match($this->role) {
            'user' => 'user',
            'assistant' => 'cpu',
            'system' => 'settings',
            default => 'message-circle',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => 'Completed',
            'streaming' => 'Streaming',
            'failed' => 'Failed',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    public function getReferenceCountAttribute(): int
    {
        return count($this->referenced_transactions ?? []) +
               count($this->referenced_categories ?? []) +
               count($this->referenced_accounts ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Check Methods
    |--------------------------------------------------------------------------
    */

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isStreaming(): bool
    {
        return $this->status === 'streaming';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function hasReferences(): bool
    {
        return !empty($this->referenced_transactions) ||
               !empty($this->referenced_categories) ||
               !empty($this->referenced_accounts);
    }

    public function hasRating(): bool
    {
        return $this->user_rating !== null;
    }

    public function isRatedHelpful(): bool
    {
        return $this->user_rating === 'helpful';
    }

    public function isRatedNotHelpful(): bool
    {
        return $this->user_rating === 'not_helpful';
    }

    public function hasError(): bool
    {
        return !empty($this->error_message);
    }

    /*
    |--------------------------------------------------------------------------
    | Reference Methods
    |--------------------------------------------------------------------------
    */

    public function getReferencedTransactions()
    {
        if (empty($this->referenced_transactions)) {
            return collect();
        }

        return StatementTransaction::whereIn('id', $this->referenced_transactions)->get();
    }

    public function getReferencedCategories()
    {
        if (empty($this->referenced_categories)) {
            return collect();
        }

        return Category::whereIn('id', $this->referenced_categories)->get();
    }

    public function getReferencedAccounts()
    {
        if (empty($this->referenced_accounts)) {
            return collect();
        }

        return Account::whereIn('id', $this->referenced_accounts)->get();
    }

    public function getReferencesSummary(): array
    {
        return [
            'transactions' => count($this->referenced_transactions ?? []),
            'categories' => count($this->referenced_categories ?? []),
            'accounts' => count($this->referenced_accounts ?? []),
            'total' => $this->reference_count,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status Management Methods
    |--------------------------------------------------------------------------
    */

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsPending(): void
    {
        $this->update(['status' => 'pending']);
    }

    public function markAsStreaming(): void
    {
        $this->update(['status' => 'streaming']);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Rating & Feedback Methods
    |--------------------------------------------------------------------------
    */

    public function setRating(string $rating, ?string $feedback = null): void
    {
        if (!in_array($rating, ['helpful', 'not_helpful'])) {
            throw new \InvalidArgumentException('Rating must be "helpful" or "not_helpful"');
        }

        $this->update([
            'user_rating' => $rating,
            'user_feedback' => $feedback,
        ]);
    }

    public function markAsHelpful(?string $feedback = null): void
    {
        $this->setRating('helpful', $feedback);
    }

    public function markAsNotHelpful(?string $feedback = null): void
    {
        $this->setRating('not_helpful', $feedback);
    }

    public function clearRating(): void
    {
        $this->update([
            'user_rating' => null,
            'user_feedback' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Methods
    |--------------------------------------------------------------------------
    */

    public function toApiFormat(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }

    public function getStatistics(): array
    {
        return [
            'tokens' => [
                'prompt' => number_format($this->prompt_tokens),
                'completion' => number_format($this->completion_tokens),
                'total' => number_format($this->total_tokens),
            ],
            'cost' => $this->formatted_cost,
            'response_time' => $this->formatted_response_time,
            'model' => $this->model,
            'has_references' => $this->hasReferences(),
            'references' => $this->getReferencesSummary(),
            'rating' => [
                'has_rating' => $this->hasRating(),
                'value' => $this->user_rating,
                'feedback' => $this->user_feedback,
            ],
        ];
    }

    public function getInfo(): array
    {
        return [
            'uuid' => $this->uuid,
            'role' => [
                'value' => $this->role,
                'icon' => $this->role_icon,
                'badge_class' => $this->role_badge_class,
            ],
            'status' => [
                'value' => $this->status,
                'label' => $this->status_label,
                'badge_class' => $this->status_badge_class,
            ],
            'content' => $this->content,
            'statistics' => $this->getStatistics(),
            'created_at' => $this->created_at->toIso8601String(),
            'created_human' => $this->created_at->diffForHumans(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helper Methods
    |--------------------------------------------------------------------------
    */

    public static function createUserMessage(int $sessionId, string $content): self
    {
        return static::create([
            'chat_session_id' => $sessionId,
            'role' => 'user',
            'content' => $content,
            'status' => 'completed',
        ]);
    }

    public static function createAssistantMessage(
        int $sessionId,
        string $content,
        array $metadata = []
    ): self {
        return static::create(array_merge([
            'chat_session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $content,
            'status' => 'completed',
        ], $metadata));
    }

    public static function createSystemMessage(int $sessionId, string $content): self
    {
        return static::create([
            'chat_session_id' => $sessionId,
            'role' => 'system',
            'content' => $content,
            'status' => 'completed',
        ]);
    }

    public static function getConversationHistory(int $sessionId, int $limit = 50): array
    {
        return static::bySession($sessionId)
                     ->completed()
                     ->latest($limit)
                     ->get()
                     ->reverse()
                     ->map(fn($msg) => $msg->toApiFormat())
                     ->toArray();
    }

    public static function getSessionStatistics(int $sessionId): array
    {
        $messages = static::bySession($sessionId)->get();
        
        return [
            'total' => $messages->count(),
            'user' => $messages->where('role', 'user')->count(),
            'assistant' => $messages->where('role', 'assistant')->count(),
            'system' => $messages->where('role', 'system')->count(),
            'total_tokens' => $messages->sum('total_tokens'),
            'total_cost' => $messages->sum('cost'),
            'avg_response_time' => $messages->where('response_time_ms', '>', 0)->avg('response_time_ms'),
            'rated_helpful' => $messages->where('user_rating', 'helpful')->count(),
            'rated_not_helpful' => $messages->where('user_rating', 'not_helpful')->count(),
        ];
    }
}