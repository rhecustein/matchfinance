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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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
        return $this->hasMany(AccountKeyword::class)
                    ->where('is_active', true)
                    ->orderBy('priority', 'desc');
    }

    /**
     * Get all transactions assigned to this account
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    /**
     * Get all matching logs for this account
     */
    public function matchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive accounts
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope by account type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope by priority order
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted account type
     */
    public function formattedAccountType(): Attribute
    {
        return Attribute::make(
            get: fn() => ucwords(str_replace('_', ' ', $this->account_type ?? 'N/A'))
        );
    }

    /**
     * Get total transactions count
     */
    public function totalTransactions(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transactions()->count()
        );
    }

    /**
     * Get total amount for this account
     */
    public function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transactions()->sum('amount')
        );
    }

    /**
     * Get formatted total amount
     */
    public function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->total_amount, 0, ',', '.')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get account display name with code
     */
    public function getDisplayName(): string
    {
        return $this->code ? "[$this->code] $this->name" : $this->name;
    }

    /**
     * Toggle active status
     */
    public function toggleActive(): bool
    {
        return $this->update(['is_active' => !$this->is_active]);
    }
}