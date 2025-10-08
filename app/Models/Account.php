<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'account_type',
        'color',
        'priority',
        'is_active',
        'match_count',
        'last_matched_at',
        'metadata',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'match_count' => 'integer',
        'last_matched_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot method untuk auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            if (empty($account->code)) {
                $account->code = 'ACC-' . str_pad(Account::max('id') + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get all keywords untuk account ini
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(AccountKeyword::class)->orderBy('priority', 'desc');
    }

    /**
     * Get active keywords saja
     */
    public function activeKeywords(): HasMany
    {
        return $this->keywords()->where('is_active', true);
    }

    /**
     * Get all transactions yang match dengan account ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    /**
     * Get matching logs
     */
    public function matchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class);
    }

    /**
     * Accessor untuk badge color
     */
    public function badgeColor(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->color ?? '#3B82F6'
        );
    }

    /**
     * Accessor untuk status label
     */
    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'Active' : 'Inactive'
        );
    }

    /**
     * Accessor untuk status badge class
     */
    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
        );
    }

    /**
     * Scope untuk filter active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    /**
     * Scope untuk filter by account type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Increment match count
     */
    public function incrementMatchCount(): void
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }

    /**
     * Get total transactions amount
     */
    public function getTotalAmount(): float
    {
        return $this->transactions()->sum('amount') ?? 0;
    }

    /**
     * Get transaction count
     */
    public function getTransactionCount(): int
    {
        return $this->transactions()->count();
    }
}