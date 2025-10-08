<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;


class AccountKeyword extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
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
     * Get account yang memiliki keyword ini
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get semua transactions yang match dengan keyword ini
     */
    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class, 'matched_account_keyword_id');
    }

    /**
     * Get matching logs
     */
    public function matchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class, 'account_keyword_id');
    }

    /**
     * Accessor untuk match type label
     */
    public function matchTypeLabel(): Attribute
    {
        $labels = [
            'exact' => 'Exact Match',
            'contains' => 'Contains',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'regex' => 'Regular Expression',
        ];

        return Attribute::make(
            get: fn () => $labels[$this->match_type] ?? 'Unknown'
        );
    }

    /**
     * Accessor untuk status badge
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
     * Check apakah keyword ini match dengan text
     */
    public function matches(string $text): bool
    {
        $keyword = $this->keyword;
        $searchText = $this->case_sensitive ? $text : strtolower($text);
        $searchKeyword = $this->case_sensitive ? $keyword : strtolower($keyword);

        return match($this->match_type) {
            'exact' => $searchText === $searchKeyword,
            'contains' => str_contains($searchText, $searchKeyword),
            'starts_with' => str_starts_with($searchText, $searchKeyword),
            'ends_with' => str_ends_with($searchText, $searchKeyword),
            'regex' => $this->matchesRegex($text, $keyword),
            default => false,
        };
    }

    /**
     * Match menggunakan regex
     */
    protected function matchesRegex(string $text, string $pattern): bool
    {
        try {
            $flags = $this->case_sensitive ? '' : 'i';
            return preg_match("/{$pattern}/{$flags}", $text) === 1;
        } catch (\Exception $e) {
            Log::error('Regex match error', [
                'keyword_id' => $this->id,
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
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
     * Scope active keywords
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}