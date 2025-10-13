<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
            
            if (empty($collection->color)) {
                $collection->color = '#3B82F6';
            }
            
            if (empty($collection->icon)) {
                $collection->icon = 'folder';
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('sort_order');
    }

    public function readyItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class)
                    ->where('knowledge_status', 'ready')
                    ->orderBy('sort_order');
    }

    public function processingItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class)
                    ->where('knowledge_status', 'processing')
                    ->orderBy('sort_order');
    }

    public function failedItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class)
                    ->where('knowledge_status', 'failed')
                    ->orderBy('sort_order');
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class)->latest('last_activity_at');
    }

    public function activeChatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class)
                    ->where('is_archived', false)
                    ->latest('last_activity_at');
    }

    public function recentChatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class)
                    ->where('last_activity_at', '>=', now()->subDays(7))
                    ->latest('last_activity_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecentlyUsed($query, $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days))
                     ->orderBy('last_used_at', 'desc');
    }

    public function scopePopular($query, $minChats = 5)
    {
        return $query->where('chat_count', '>=', $minChats)
                     ->orderByDesc('chat_count');
    }

    public function scopeWithDocuments($query, $minDocs = 1)
    {
        return $query->where('document_count', '>=', $minDocs);
    }

    public function scopeEmpty($query)
    {
        return $query->where('document_count', 0);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeOrderedByUsage($query)
    {
        return $query->orderByDesc('last_used_at');
    }

    public function scopeOrderedByPopularity($query)
    {
        return $query->orderByDesc('chat_count');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Check Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isEmpty(): bool
    {
        return $this->document_count === 0;
    }

    public function hasDocuments(): bool
    {
        return $this->document_count > 0;
    }

    public function hasAutoAdd(): bool
    {
        return $this->auto_add_new;
    }

    public function hasChats(): bool
    {
        return $this->chat_count > 0;
    }

    public function isRecentlyUsed(int $days = 7): bool
    {
        return $this->last_used_at && $this->last_used_at->gte(now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Action Methods
    |--------------------------------------------------------------------------
    */

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function toggleActive(): bool
    {
        return $this->update(['is_active' => !$this->is_active]);
    }

    public function updateStatistics(): void
    {
        $items = $this->items()->with('bankStatement')->get();
        
        $this->update([
            'document_count' => $items->count(),
            'total_transactions' => $items->sum(fn($item) => $item->transaction_count ?? 0),
            'total_debit' => $items->sum(fn($item) => $item->bankStatement?->total_debit_amount ?? 0),
            'total_credit' => $items->sum(fn($item) => $item->bankStatement?->total_credit_amount ?? 0),
        ]);
    }

    public function recalculateStatistics(): void
    {
        $this->loadMissing(['items.bankStatement']);
        
        $totalTransactions = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($this->items as $item) {
            if ($item->bankStatement) {
                $totalTransactions += $item->bankStatement->total_transactions ?? 0;
                $totalDebit += $item->bankStatement->total_debit_amount ?? 0;
                $totalCredit += $item->bankStatement->total_credit_amount ?? 0;
            }
        }
        
        $this->update([
            'document_count' => $this->items->count(),
            'total_transactions' => $totalTransactions,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ]);
    }

    public function addBankStatement(BankStatement $statement, ?int $sortOrder = null): DocumentItem
    {
        return DocumentItem::create([
            'document_collection_id' => $this->id,
            'bank_statement_id' => $statement->id,
            'sort_order' => $sortOrder ?? ($this->items()->max('sort_order') ?? 0) + 1,
            'statement_period_from' => $statement->period_from,
            'statement_period_to' => $statement->period_to,
            'transaction_count' => $statement->total_transactions,
            'total_amount' => $statement->total_credit_amount - $statement->total_debit_amount,
        ]);
    }

    public function removeBankStatement(BankStatement $statement): bool
    {
        return $this->items()
                    ->where('bank_statement_id', $statement->id)
                    ->delete() > 0;
    }

    public function hasBankStatement(BankStatement $statement): bool
    {
        return $this->items()
                    ->where('bank_statement_id', $statement->id)
                    ->exists();
    }

    public function getBankStatements()
    {
        return BankStatement::whereIn(
            'id',
            $this->items()->pluck('bank_statement_id')
        )->get();
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function incrementChatCount(): void
    {
        $this->increment('chat_count');
        $this->markAsUsed();
    }

    public function decrementChatCount(): void
    {
        if ($this->chat_count > 0) {
            $this->decrement('chat_count');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Settings Methods
    |--------------------------------------------------------------------------
    */

    public function getFilterSetting($key, $default = null)
    {
        $settings = $this->filter_settings ?? [];
        return $settings[$key] ?? $default;
    }

    public function setFilterSetting($key, $value): bool
    {
        $settings = $this->filter_settings ?? [];
        $settings[$key] = $value;
        
        return $this->update(['filter_settings' => $settings]);
    }

    public function hasFilterSettings(): bool
    {
        return !empty($this->filter_settings);
    }

    public function matchesFilter(BankStatement $statement): bool
    {
        if (!$this->hasFilterSettings()) {
            return false;
        }

        $settings = $this->filter_settings;

        // Check bank filter
        if (!empty($settings['bank_ids'])) {
            if (!in_array($statement->bank_id, $settings['bank_ids'])) {
                return false;
            }
        }

        // Check date range filter
        if (!empty($settings['date_from'])) {
            if ($statement->period_from < $settings['date_from']) {
                return false;
            }
        }

        if (!empty($settings['date_to'])) {
            if ($statement->period_to > $settings['date_to']) {
                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function formattedTotalDebit(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->total_debit, 0, ',', '.')
        );
    }

    public function formattedTotalCredit(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->total_credit, 0, ',', '.')
        );
    }

    public function netAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_credit - $this->total_debit
        );
    }

    public function formattedNetAmount(): Attribute
    {
        return Attribute::make(
            get: function() {
                $net = $this->net_amount;
                $prefix = $net >= 0 ? '+' : '';
                return $prefix . 'Rp ' . number_format(abs($net), 0, ',', '.');
            }
        );
    }

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_active 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_active ? 'Active' : 'Inactive'
        );
    }

    public function ownerName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->user?->name ?? 'Unknown User'
        );
    }

    public function colorStyle(): Attribute
    {
        return Attribute::make(
            get: fn() => "background-color: {$this->color}; border-color: {$this->color};"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getStatisticsSummary(): array
    {
        return [
            'documents' => $this->document_count,
            'transactions' => $this->total_transactions,
            'total_debit' => $this->formatted_total_debit,
            'total_credit' => $this->formatted_total_credit,
            'net_amount' => $this->formatted_net_amount,
            'chats' => $this->chat_count,
            'last_used' => $this->last_used_at?->diffForHumans(),
        ];
    }

    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'document_count' => $this->document_count,
            'total_transactions' => $this->total_transactions,
            'formatted_debit' => $this->formatted_total_debit,
            'formatted_credit' => $this->formatted_total_credit,
            'formatted_net' => $this->formatted_net_amount,
            'auto_add_new' => $this->auto_add_new,
            'is_active' => $this->is_active,
            'chat_count' => $this->chat_count,
            'last_used_at' => $this->last_used_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->owner_name,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function duplicate(?string $newName = null): self
    {
        $attributes = $this->toArray();
        
        unset($attributes['id'], $attributes['uuid'], $attributes['created_at'], 
              $attributes['updated_at'], $attributes['deleted_at'],
              $attributes['chat_count'], $attributes['last_used_at']);
        
        $attributes['name'] = $newName ?? ($this->name . ' (Copy)');
        $attributes['document_count'] = 0;
        $attributes['total_transactions'] = 0;
        $attributes['total_debit'] = 0;
        $attributes['total_credit'] = 0;
        
        return static::create($attributes);
    }

    public function canBeDeleted(): bool
    {
        return $this->chat_count === 0 && $this->document_count === 0;
    }

    public static function getAvailableIcons(): array
    {
        return [
            'folder',
            'folder-open',
            'file',
            'files',
            'briefcase',
            'archive',
            'bookmark',
            'star',
            'heart',
            'tag',
        ];
    }

    public static function getAvailableColors(): array
    {
        return [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Amber
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#F97316', // Orange
        ];
    }
}