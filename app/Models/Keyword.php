<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keyword extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sub_category_id',
        'keyword',
        'priority',
        'is_regex',
        'case_sensitive',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_regex' => 'boolean',
        'case_sensitive' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the sub category that owns this keyword
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Get all matched transactions
     */
    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class, 'matched_keyword_id');
    }

    /**
     * Get all matching logs
     */
    public function matchingLogs(): HasMany
    {
        return $this->hasMany(MatchingLog::class);
    }

    /**
     * Scope: Only active keywords
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}