<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Keyword extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sub_category_id',
        'keyword',
        'is_regex',
        'case_sensitive',
        'match_type',
        'pattern_description',
        'priority',
        'is_active',
        'match_count',
        'last_matched_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_regex' => 'boolean',
        'case_sensitive' => 'boolean',
        'is_active' => 'boolean',
        'match_count' => 'integer',
        'last_matched_at' => 'datetime',
    ];

    /**
     * Get the sub category that owns this keyword
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Get the category through sub category
     */
    public function category(): BelongsTo
    {
        return $this->subCategory->category();
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
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')
            ->orderBy('match_count', 'desc');
    }

    /**
     * Scope: Filter by sub category
     */
    public function scopeBySubCategory($query, $subCategoryId)
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    /**
     * Scope: Most used keywords
     */
    public function scopeMostUsed($query, $limit = 10)
    {
        return $query->orderBy('match_count', 'desc')
            ->limit($limit);
    }

    /**
     * Check if keyword matches text
     */
    public function matches(string $text): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $keyword = $this->keyword;
        
        if (!$this->case_sensitive) {
            $text = strtolower($text);
            $keyword = strtolower($keyword);
        }

        return match ($this->match_type) {
            'exact' => $text === $keyword,
            'contains' => str_contains($text, $keyword),
            'starts_with' => str_starts_with($text, $keyword),
            'ends_with' => str_ends_with($text, $keyword),
            'regex' => $this->matchesRegex($text, $keyword),
            default => str_contains($text, $keyword),
        };
    }

    /**
     * Check regex match
     */
    private function matchesRegex(string $text, string $pattern): bool
    {
        try {
            return (bool) preg_match($pattern, $text);
        } catch (\Exception $e) {
            return false;
        }
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
     * Get priority label
     */
    public function priorityLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->priority >= 9) return 'Critical';
                if ($this->priority >= 7) return 'High';
                if ($this->priority >= 5) return 'Medium';
                if ($this->priority >= 3) return 'Low';
                return 'Very Low';
            }
        );
    }

    /**
     * Get match type label
     */
    public function matchTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => ucwords(str_replace('_', ' ', $this->match_type))
        );
    }

    /**
     * Get usage percentage (relative to total matches in sub category)
     */
    public function usagePercentage(): float
    {
        $totalMatches = $this->subCategory
            ->keywords()
            ->sum('match_count');

        if ($totalMatches === 0) return 0;

        return round(($this->match_count / $totalMatches) * 100, 2);
    }
}