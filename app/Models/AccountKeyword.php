<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class AccountKeyword extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
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

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($keyword) {
            if (empty($keyword->uuid)) {
                $keyword->uuid = (string) Str::uuid();
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class, 'matched_account_keyword_id');
    }

    public function matchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class, 'account_keyword_id');
    }

    public function successfulMatches(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class, 'account_keyword_id')
                    ->where('is_matched', true);
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

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByMatchType($query, $type)
    {
        return $query->where('match_type', $type);
    }

    public function scopeRegexOnly($query)
    {
        return $query->where('is_regex', true);
    }

    public function scopeNonRegex($query)
    {
        return $query->where('is_regex', false);
    }

    public function scopeCaseSensitive($query)
    {
        return $query->where('case_sensitive', true);
    }

    public function scopeCaseInsensitive($query)
    {
        return $query->where('case_sensitive', false);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 7);
    }

    public function scopeRecentlyMatched($query, $days = 30)
    {
        return $query->where('last_matched_at', '>=', now()->subDays($days));
    }

    public function scopeNeverMatched($query)
    {
        return $query->where('match_count', 0)
                     ->orWhereNull('last_matched_at');
    }

    public function scopeOrderedForMatching($query)
    {
        return $query->where('is_active', true)
                     ->orderBy('priority', 'desc')
                     ->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | Matching Methods
    |--------------------------------------------------------------------------
    */

    public function matches(string $text): bool
    {
        if (!$this->is_active) {
            return false;
        }

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

    protected function matchesRegex(string $text, string $pattern): bool
    {
        try {
            $flags = $this->case_sensitive ? '' : 'i';
            $result = @preg_match("/{$pattern}/{$flags}", $text);
            
            if ($result === false) {
                Log::warning('Invalid regex pattern', [
                    'keyword_id' => $this->id,
                    'pattern' => $pattern,
                ]);
                return false;
            }
            
            return $result === 1;
        } catch (\Exception $e) {
            Log::error('Regex match error', [
                'keyword_id' => $this->id,
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function testMatch(string $text): array
    {
        $matched = $this->matches($text);
        
        return [
            'matched' => $matched,
            'keyword' => $this->keyword,
            'match_type' => $this->match_type,
            'is_regex' => $this->is_regex,
            'case_sensitive' => $this->case_sensitive,
            'priority' => $this->priority,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function incrementMatchCount(): void
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }

    public function recordMatch(): bool
    {
        return $this->update([
            'match_count' => $this->match_count + 1,
            'last_matched_at' => now(),
        ]);
    }

    public function resetMatchCount(): bool
    {
        return $this->update([
            'match_count' => 0,
            'last_matched_at' => null,
        ]);
    }

    public function getMatchRate(): float
    {
        $totalLogs = $this->matchingLogs()->count();
        if ($totalLogs === 0) return 0;
        
        $successfulLogs = $this->successfulMatches()->count();
        return round(($successfulLogs / $totalLogs) * 100, 2);
    }

    public function getAverageConfidence(): float
    {
        return round(
            $this->successfulMatches()->avg('confidence_score') ?? 0,
            2
        );
    }

    public function getDaysSinceLastMatch(): ?int
    {
        if (!$this->last_matched_at) {
            return null;
        }
        
        return now()->diffInDays($this->last_matched_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->is_active;
    }

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

    public function isRegex(): bool
    {
        return $this->is_regex;
    }

    public function isCaseSensitive(): bool
    {
        return $this->case_sensitive;
    }

    public function isHighPriority(): bool
    {
        return $this->priority >= 7;
    }

    public function hasMatches(): bool
    {
        return $this->match_count > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Methods
    |--------------------------------------------------------------------------
    */

    public function validateRegexPattern(): array
    {
        if (!$this->is_regex && $this->match_type !== 'regex') {
            return ['valid' => true, 'message' => 'Not a regex pattern'];
        }

        try {
            $flags = $this->case_sensitive ? '' : 'i';
            @preg_match("/{$this->keyword}/{$flags}", "test");
            
            $error = preg_last_error();
            
            if ($error === PREG_NO_ERROR) {
                return ['valid' => true, 'message' => 'Valid regex pattern'];
            }
            
            $errorMessages = [
                PREG_INTERNAL_ERROR => 'Internal PCRE error',
                PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
                PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
                PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
                PREG_BAD_UTF8_OFFSET_ERROR => 'Bad UTF-8 offset',
            ];
            
            return [
                'valid' => false,
                'message' => $errorMessages[$error] ?? 'Unknown regex error'
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Invalid regex: ' . $e->getMessage()
            ];
        }
    }

    public function isValidPattern(): bool
    {
        return $this->validateRegexPattern()['valid'];
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
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

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'Active' : 'Inactive'
        );
    }

    public function priorityLabel(): Attribute
    {
        $labels = [
            10 => 'Critical',
            9 => 'Very High',
            8 => 'High',
            7 => 'Above Average',
            6 => 'Medium',
            5 => 'Normal',
            4 => 'Below Average',
            3 => 'Low',
            2 => 'Very Low',
            1 => 'Minimal',
        ];

        return Attribute::make(
            get: fn () => $labels[$this->priority] ?? 'Normal'
        );
    }

    public function priorityBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->priority >= 8) return 'bg-red-100 text-red-800';
                if ($this->priority >= 6) return 'bg-yellow-100 text-yellow-800';
                if ($this->priority >= 4) return 'bg-blue-100 text-blue-800';
                return 'bg-gray-100 text-gray-800';
            }
        );
    }

    public function formattedLastMatched(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->last_matched_at) {
                    return 'Never';
                }
                return $this->last_matched_at->diffForHumans();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getDisplayName(): string
    {
        $parts = [$this->keyword];
        
        if ($this->is_regex) {
            $parts[] = '(Regex)';
        }
        
        if ($this->case_sensitive) {
            $parts[] = '(Case Sensitive)';
        }
        
        return implode(' ', $parts);
    }

    public function getDescription(): string
    {
        return $this->pattern_description ?? $this->getAutoDescription();
    }

    protected function getAutoDescription(): string
    {
        $type = $this->match_type_label;
        $keyword = $this->keyword;
        $case = $this->case_sensitive ? 'case-sensitive' : 'case-insensitive';
        
        return "{$type} match for '{$keyword}' ({$case})";
    }

    public function getStatistics(): array
    {
        return [
            'match_count' => $this->match_count,
            'last_matched_at' => $this->last_matched_at,
            'days_since_last_match' => $this->getDaysSinceLastMatch(),
            'match_rate' => $this->getMatchRate(),
            'average_confidence' => $this->getAverageConfidence(),
            'total_logs' => $this->matchingLogs()->count(),
            'successful_matches' => $this->successfulMatches()->count(),
        ];
    }

    public function duplicate(): self
    {
        $attributes = $this->toArray();
        
        unset($attributes['id'], $attributes['uuid'], $attributes['created_at'], 
              $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes['keyword'] = $attributes['keyword'] . ' (Copy)';
        $attributes['match_count'] = 0;
        $attributes['last_matched_at'] = null;
        
        return static::create($attributes);
    }

    public static function getMatchTypes(): array
    {
        return [
            'exact' => 'Exact Match',
            'contains' => 'Contains',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'regex' => 'Regular Expression',
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            10 => 'Critical (10)',
            9 => 'Very High (9)',
            8 => 'High (8)',
            7 => 'Above Average (7)',
            6 => 'Medium (6)',
            5 => 'Normal (5)',
            4 => 'Below Average (4)',
            3 => 'Low (3)',
            2 => 'Very Low (2)',
            1 => 'Minimal (1)',
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->match_count === 0;
    }

    public function getAccountName(): string
    {
        return $this->account?->name ?? 'Unknown Account';
    }

    public function getAccountCode(): ?string
    {
        return $this->account?->code;
    }
}