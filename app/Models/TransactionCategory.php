<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class TransactionCategory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'statement_transaction_id',
        'type_id',
        'category_id',
        'sub_category_id',
        'matched_keyword_id',
        'matched_pattern_id',
        'matched_merchant_id',
        'confidence_score',
        'is_primary',
        'is_manual',
        'reason',
        'match_metadata',
        'assigned_by',
        'assigned_at'
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'is_primary' => 'boolean',
        'is_manual' => 'boolean',
        'match_metadata' => 'array',
        'assigned_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }
            
            if (empty($category->assigned_at)) {
                $category->assigned_at = now();
            }
            
            // Auto-fill hierarchy if sub_category is set
            if ($category->sub_category_id && !$category->category_id) {
                $subCategory = SubCategory::find($category->sub_category_id);
                if ($subCategory) {
                    $category->category_id = $subCategory->category_id;
                    
                    if (!$category->type_id) {
                        $category->type_id = $subCategory->category->type_id ?? null;
                    }
                }
            }
        });
        
        // When setting as primary, unset other primaries for same transaction
        static::updating(function ($category) {
            if ($category->is_primary && $category->isDirty('is_primary')) {
                static::where('statement_transaction_id', $category->statement_transaction_id)
                    ->where('id', '!=', $category->id)
                    ->update(['is_primary' => false]);
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class, 'statement_transaction_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function matchedKeyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class, 'matched_keyword_id');
    }

    public function matchedPattern(): BelongsTo
    {
        return $this->belongsTo(KeywordPattern::class, 'matched_pattern_id');
    }

    public function matchedMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'matched_merchant_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_manual', false);
    }

    public function scopeHighConfidence($query, int $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeLowConfidence($query, int $threshold = 50)
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function makePrimary(): void
    {
        $this->update(['is_primary' => true]);
    }

    public function updateConfidence(int $score): void
    {
        $this->update(['confidence_score' => min(100, max(0, $score))]);
    }

    public function markAsManual(int $userId): void
    {
        $this->update([
            'is_manual' => true,
            'assigned_by' => $userId,
            'assigned_at' => now()
        ]);
    }

    public function getCategoryPath(): string
    {
        $path = [];
        
        if ($this->type) {
            $path[] = $this->type->name;
        }
        
        if ($this->category) {
            $path[] = $this->category->name;
        }
        
        if ($this->subCategory) {
            $path[] = $this->subCategory->name;
        }
        
        return implode(' > ', $path);
    }

    public function getMatchSource(): string
    {
        if ($this->is_manual) {
            return 'Manual Assignment';
        }
        
        if ($this->matched_merchant_id) {
            return 'Merchant: ' . ($this->matchedMerchant->name ?? 'Unknown');
        }
        
        if ($this->matched_pattern_id) {
            return 'Pattern: ' . ($this->matchedPattern->name ?? 'Unknown');
        }
        
        if ($this->matched_keyword_id) {
            return 'Keyword: ' . ($this->matchedKeyword->keyword ?? 'Unknown');
        }
        
        return 'Unknown';
    }
}