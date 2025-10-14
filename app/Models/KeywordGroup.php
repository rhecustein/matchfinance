<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class KeywordGroup extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'sub_category_id',
        'name',
        'description',
        'logic_type',
        'priority',
        'is_active',
        'match_count',
        'last_matched_at'
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'match_count' => 'integer',
        'last_matched_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($group) {
            if (empty($group->uuid)) {
                $group->uuid = (string) Str::uuid();
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

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'keyword_group_members')
            ->withPivot(['is_required', 'is_negative', 'position', 'weight'])
            ->withTimestamps()
            ->orderBy('pivot_position');
    }

    public function members()
    {
        return $this->hasMany(KeywordGroupMember::class);
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

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function matchesDescription(string $description): bool
    {
        $description = strtoupper($description);
        
        switch ($this->logic_type) {
            case 'AND':
                return $this->matchesAllKeywords($description);
            case 'OR':
                return $this->matchesAnyKeyword($description);
            case 'COMPLEX':
                return $this->matchesComplexLogic($description);
            default:
                return false;
        }
    }

    private function matchesAllKeywords(string $description): bool
    {
        foreach ($this->keywords as $keyword) {
            $matches = stripos($description, $keyword->keyword) !== false;
            
            // Handle negative keywords (must NOT be present)
            if ($keyword->pivot->is_negative) {
                if ($matches) return false;
            } else {
                // Handle required keywords (must be present)
                if ($keyword->pivot->is_required && !$matches) {
                    return false;
                }
            }
        }
        
        return true;
    }

    private function matchesAnyKeyword(string $description): bool
    {
        foreach ($this->keywords as $keyword) {
            $matches = stripos($description, $keyword->keyword) !== false;
            
            // Skip negative keywords in OR logic
            if ($keyword->pivot->is_negative) {
                if ($matches) return false;
            } else {
                if ($matches) return true;
            }
        }
        
        return false;
    }

    private function matchesComplexLogic(string $description): bool
    {
        // Implement custom complex logic here
        // Could use a rule engine or expression evaluator
        
        $score = 0;
        $requiredScore = 0;
        
        foreach ($this->keywords as $keyword) {
            $matches = stripos($description, $keyword->keyword) !== false;
            $weight = $keyword->pivot->weight;
            
            if ($keyword->pivot->is_required) {
                $requiredScore += $weight;
                if ($matches) $score += $weight;
            } else if (!$keyword->pivot->is_negative && $matches) {
                $score += $weight;
            } else if ($keyword->pivot->is_negative && $matches) {
                return false; // Negative match fails immediately
            }
        }
        
        // Must have all required keywords
        return $score >= $requiredScore && $score > 0;
    }

    public function calculateConfidence(string $description): int
    {
        $baseScore = 60;
        $matchedCount = 0;
        $totalWeight = 0;
        $earnedWeight = 0;
        
        foreach ($this->keywords as $keyword) {
            $matches = stripos($description, $keyword->keyword) !== false;
            $weight = $keyword->pivot->weight;
            
            $totalWeight += $weight;
            
            if ($matches && !$keyword->pivot->is_negative) {
                $matchedCount++;
                $earnedWeight += $weight;
            }
        }
        
        if ($totalWeight > 0) {
            $weightScore = ($earnedWeight / $totalWeight) * 30;
            $baseScore += $weightScore;
        }
        
        // Add priority bonus
        $baseScore += ($this->priority * 2);
        
        return min($baseScore, 100);
    }

    public function incrementMatch()
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }
}