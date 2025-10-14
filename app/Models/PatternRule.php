<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class PatternRule extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'description',
        'rule_type',
        'patterns',
        'conditions',
        'assign_category_id',
        'assign_sub_category_id',
        'confidence_boost',
        'priority',
        'is_active',
        'stop_on_match',
        'match_count',
        'last_matched_at'
    ];

    protected $casts = [
        'patterns' => 'array',
        'conditions' => 'array',
        'confidence_boost' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'stop_on_match' => 'boolean',
        'match_count' => 'integer',
        'last_matched_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($rule) {
            if (empty($rule->uuid)) {
                $rule->uuid = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'assign_category_id');
    }

    public function assignSubCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'assign_sub_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function incrementMatch()
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }
}