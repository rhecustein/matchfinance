<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'account_type',
        'color',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all keywords for this account
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(AccountKeyword::class)->orderBy('priority', 'desc');
    }

    /**
     * Get active keywords only
     */
    public function activeKeywords(): HasMany
    {
        return $this->keywords()->where('is_active', true);
    }

    /**
     * Get all transactions for this account
     * Note: Assuming you have a transactions table with account_id
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class, 'account_id');
    }

    /**
     * Scope: Only active accounts
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only inactive accounts
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc')
                     ->orderBy('name', 'asc');
    }

    /**
     * Scope: Filter by account type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('account_type', $type);
    }

    /**
     * Check if account has keywords
     */
    public function hasKeywords(): bool
    {
        return $this->keywords()->exists();
    }

    /**
     * Check if account has active keywords
     */
    public function hasActiveKeywords(): bool
    {
        return $this->activeKeywords()->exists();
    }

    /**
     * Get total match count from all keywords
     */
    public function getTotalMatchCountAttribute(): int
    {
        return $this->keywords()->sum('match_count') ?? 0;
    }
}