<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Keyword extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'sub_category_id',
        'keyword',
        'is_regex',
        'case_sensitive',
        'match_type',
        'pattern_description',
        'priority',
        'is_active',
        'match_count',
        'last_matched_at'
    ];

    protected $casts = [
        'is_regex' => 'boolean',
        'case_sensitive' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'match_count' => 'integer',
        'last_matched_at' => 'datetime'
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

    public function getRouteKeyName() { return 'uuid'; }

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function subCategory() { return $this->belongsTo(SubCategory::class); }
    public function matchingLogs() { return $this->hasMany(MatchingLog::class); }
    
    public function transactions() {
        return $this->hasMany(StatementTransaction::class, 'matched_keyword_id');
    }

    // Scopes
    public function scopeActive($query) { 
        return $query->where('is_active', true); 
    }
    
    public function scopeHighPriority($query) {
        return $query->where('priority', '>=', 7)->orderBy('priority', 'desc');
    }

    // Matching Methods
    public function matches($text)
    {
        $searchText = $this->case_sensitive ? $text : strtolower($text);
        $searchKeyword = $this->case_sensitive ? $this->keyword : strtolower($this->keyword);

        return match($this->match_type) {
            'exact' => $searchText === $searchKeyword,
            'contains' => str_contains($searchText, $searchKeyword),
            'starts_with' => str_starts_with($searchText, $searchKeyword),
            'ends_with' => str_ends_with($searchText, $searchKeyword),
            'regex' => $this->is_regex && @preg_match($this->keyword, $text),
            default => false
        };
    }

    public function incrementMatchCount()
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }
}