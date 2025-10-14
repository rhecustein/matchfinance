<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class Merchant extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'code',
        'name',
        'display_name',
        'type',
        'keywords',
        'regex_patterns',
        'default_category_id',
        'default_sub_category_id',
        'website',
        'logo_url',
        'metadata',
        'is_active',
        'is_verified'
    ];

    protected $casts = [
        'keywords' => 'array',
        'regex_patterns' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($merchant) {
            if (empty($merchant->uuid)) {
                $merchant->uuid = (string) Str::uuid();
            }
            
            if (empty($merchant->display_name)) {
                $merchant->display_name = $merchant->name;
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

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
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

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function matchesDescription(string $description): bool
    {
        $description = strtoupper($description);
        
        // Check keywords
        if ($this->keywords) {
            foreach ($this->keywords as $keyword) {
                if (stripos($description, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        // Check regex patterns
        if ($this->regex_patterns) {
            foreach ($this->regex_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $description)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function extractMatchedKeyword(string $description): ?string
    {
        $description = strtoupper($description);
        
        // Check keywords
        if ($this->keywords) {
            foreach ($this->keywords as $keyword) {
                if (stripos($description, $keyword) !== false) {
                    return $keyword;
                }
            }
        }
        
        // Check regex patterns
        if ($this->regex_patterns) {
            foreach ($this->regex_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $description, $matches)) {
                    return $matches[0];
                }
            }
        }
        
        return null;
    }

    public function addKeyword(string $keyword): void
    {
        $keywords = $this->keywords ?? [];
        
        if (!in_array($keyword, $keywords)) {
            $keywords[] = $keyword;
            $this->keywords = $keywords;
            $this->save();
        }
    }

    public function removeKeyword(string $keyword): void
    {
        $keywords = $this->keywords ?? [];
        $keywords = array_values(array_diff($keywords, [$keyword]));
        $this->keywords = $keywords;
        $this->save();
    }

    public function getMerchantType(): string
    {
        $types = [
            'retail' => 'Retail Store',
            'restaurant' => 'Restaurant',
            'online' => 'Online Shop',
            'service' => 'Service Provider',
            'transport' => 'Transportation',
            'utility' => 'Utility Service',
            'entertainment' => 'Entertainment',
            'healthcare' => 'Healthcare',
            'other' => 'Other'
        ];
        
        return $types[$this->type] ?? 'Other';
    }
}