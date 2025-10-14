<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class KeywordPattern extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'code',
        'name',
        'description',
        'pattern',
        'pattern_type',
        'case_sensitive',
        'extract_variant',
        'category_hint',
        'default_category_id',
        'default_sub_category_id',
        'priority',
        'is_active',
        'is_system',
        'match_count',
        'last_matched_at'
    ];

    protected $casts = [
        'case_sensitive' => 'boolean',
        'extract_variant' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'priority' => 'integer',
        'match_count' => 'integer',
        'last_matched_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($pattern) {
            if (empty($pattern->uuid)) {
                $pattern->uuid = (string) Str::uuid();
            }
        });
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

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function defaultSubCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'default_sub_category_id');
    }

    public function patternGroups(): BelongsToMany
    {
        return $this->belongsToMany(PatternGroup::class, 'pattern_group_items')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function incrementMatch()
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }

    public function match(string $text): bool
    {
        $pattern = $this->case_sensitive ? $this->pattern : strtolower($this->pattern);
        $searchText = $this->case_sensitive ? $text : strtolower($text);

        switch ($this->pattern_type) {
            case 'exact':
                return $searchText === $pattern;
            case 'contains':
                return str_contains($searchText, $pattern);
            case 'starts_with':
            case 'prefix':
                return str_starts_with($searchText, $pattern);
            case 'ends_with':
            case 'suffix':
                return str_ends_with($searchText, $pattern);
            case 'regex':
                $flags = $this->case_sensitive ? '' : 'i';
                return preg_match('/' . $this->pattern . '/' . $flags, $text) === 1;
            default:
                return false;
        }
    }

    public function extractMatch(string $text): ?string
    {
        if (!$this->match($text)) {
            return null;
        }

        if ($this->pattern_type === 'regex') {
            $flags = $this->case_sensitive ? '' : 'i';
            if (preg_match('/' . $this->pattern . '/' . $flags, $text, $matches)) {
                return $matches[0];
            }
        }

        return $this->pattern;
    }
}