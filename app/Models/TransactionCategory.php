<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class TransactionCategory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'statement_transaction_id',
        'sub_category_id',
        'category_id',
        'type_id',
        'matched_keyword_id',
        'confidence_score',
        'is_primary',
        'is_manual',
        'reason',
        'match_metadata',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'is_primary' => 'boolean',
        'is_manual' => 'boolean',
        'match_metadata' => 'array',
        'assigned_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transactionCategory) {
            if (empty($transactionCategory->uuid)) {
                $transactionCategory->uuid = (string) Str::uuid();
            }
            
            if (empty($transactionCategory->assigned_at)) {
                $transactionCategory->assigned_at = now();
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

    public function statementTransaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function matchedKeyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class, 'matched_keyword_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeSecondary($query)
    {
        return $query->where('is_primary', false);
    }

    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_manual', false);
    }

    public function scopeHighConfidence($query, $threshold = 90)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeMediumConfidence($query)
    {
        return $query->whereBetween('confidence_score', [70, 89]);
    }

    public function scopeLowConfidence($query, $threshold = 70)
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySubCategory($query, $subCategoryId)
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    public function scopeForTransaction($query, $transactionId)
    {
        return $query->where('statement_transaction_id', $transactionId);
    }

    public function scopeRecentlyAssigned($query, $days = 30)
    {
        return $query->where('assigned_at', '>=', now()->subDays($days));
    }

    public function scopeOrderedByConfidence($query)
    {
        return $query->orderBy('confidence_score', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Check Methods
    |--------------------------------------------------------------------------
    */

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function isSecondary(): bool
    {
        return !$this->is_primary;
    }

    public function isManual(): bool
    {
        return $this->is_manual;
    }

    public function isAutomatic(): bool
    {
        return !$this->is_manual;
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence_score >= 90;
    }

    public function isMediumConfidence(): bool
    {
        return $this->confidence_score >= 70 && $this->confidence_score < 90;
    }

    public function isLowConfidence(): bool
    {
        return $this->confidence_score < 70;
    }

    public function hasKeyword(): bool
    {
        return !is_null($this->matched_keyword_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Action Methods
    |--------------------------------------------------------------------------
    */

    public function setAsPrimary(): bool
    {
        // Remove primary from other categories for this transaction
        static::where('statement_transaction_id', $this->statement_transaction_id)
              ->where('id', '!=', $this->id)
              ->update(['is_primary' => false]);

        return $this->update(['is_primary' => true]);
    }

    public function setAsSecondary(): bool
    {
        return $this->update(['is_primary' => false]);
    }

    public function togglePrimary(): bool
    {
        if ($this->is_primary) {
            return $this->setAsSecondary();
        }
        return $this->setAsPrimary();
    }

    public function updateConfidence(int $score, ?string $reason = null): bool
    {
        $score = max(0, min(100, $score)); // Ensure 0-100 range
        
        return $this->update([
            'confidence_score' => $score,
            'reason' => $reason ?? $this->reason,
        ]);
    }

    public function markAsManual(?int $userId = null, ?string $reason = null): bool
    {
        return $this->update([
            'is_manual' => true,
            'assigned_by' => $userId ?? auth()->id(),
            'assigned_at' => now(),
            'reason' => $reason ?? $this->reason,
            'confidence_score' => 100,
        ]);
    }

    public function updateReason(string $reason): bool
    {
        return $this->update(['reason' => $reason]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function confidenceLabel(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->confidence_score >= 90) return 'Very High';
                if ($this->confidence_score >= 70) return 'High';
                if ($this->confidence_score >= 50) return 'Medium';
                if ($this->confidence_score >= 30) return 'Low';
                return 'Very Low';
            }
        );
    }

    public function confidenceBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->confidence_score >= 90) return 'bg-green-100 text-green-800';
                if ($this->confidence_score >= 70) return 'bg-blue-100 text-blue-800';
                if ($this->confidence_score >= 50) return 'bg-yellow-100 text-yellow-800';
                if ($this->confidence_score >= 30) return 'bg-orange-100 text-orange-800';
                return 'bg-red-100 text-red-800';
            }
        );
    }

    public function assignmentTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_manual ? 'Manual' : 'Automatic'
        );
    }

    public function assignmentTypeBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_manual 
                ? 'bg-purple-100 text-purple-800' 
                : 'bg-blue-100 text-blue-800'
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_primary ? 'Primary' : 'Secondary'
        );
    }

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_primary 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
        );
    }

    public function categoryPath(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->relationLoaded('type') || 
                    !$this->relationLoaded('category') || 
                    !$this->relationLoaded('subCategory')) {
                    $this->loadMissing(['type', 'category', 'subCategory']);
                }
                
                return sprintf(
                    '%s > %s > %s',
                    $this->type?->name ?? 'N/A',
                    $this->category?->name ?? 'N/A',
                    $this->subCategory?->name ?? 'N/A'
                );
            }
        );
    }

    public function typeName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->type?->name ?? 'N/A'
        );
    }

    public function categoryName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->category?->name ?? 'N/A'
        );
    }

    public function subCategoryName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->subCategory?->name ?? 'N/A'
        );
    }

    public function keywordText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->matchedKeyword?->keyword ?? 'N/A'
        );
    }

    public function assignerName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->assignedBy?->name ?? 'System'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'is_primary' => $this->is_primary,
            'is_manual' => $this->is_manual,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'type' => $this->type_name,
            'category' => $this->category_name,
            'sub_category' => $this->sub_category_name,
            'keyword' => $this->keyword_text,
            'category_path' => $this->category_path,
            'assignment_type' => $this->assignment_type_label,
            'status' => $this->status_label,
            'reason' => $this->reason,
            'assigned_by' => $this->assigner_name,
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function getHierarchyIds(): array
    {
        return [
            'type_id' => $this->type_id,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'keyword_id' => $this->matched_keyword_id,
        ];
    }

    public function getSummary(): array
    {
        return [
            'category_path' => $this->category_path,
            'confidence' => $this->confidence_label,
            'type' => $this->assignment_type_label,
            'status' => $this->status_label,
        ];
    }

    public static function createForTransaction(
        int $transactionId,
        int $subCategoryId,
        ?int $keywordId = null,
        int $confidence = 100,
        bool $isPrimary = true,
        bool $isManual = false,
        ?string $reason = null,
        ?int $assignedBy = null
    ): self {
        $subCategory = SubCategory::with(['category.type'])->findOrFail($subCategoryId);
        
        return static::create([
            'statement_transaction_id' => $transactionId,
            'sub_category_id' => $subCategory->id,
            'category_id' => $subCategory->category_id,
            'type_id' => $subCategory->category->type_id,
            'matched_keyword_id' => $keywordId,
            'confidence_score' => $confidence,
            'is_primary' => $isPrimary,
            'is_manual' => $isManual,
            'reason' => $reason,
            'assigned_by' => $assignedBy ?? auth()->id(),
        ]);
    }

    public static function getPrimaryForTransaction(int $transactionId): ?self
    {
        return static::where('statement_transaction_id', $transactionId)
                     ->where('is_primary', true)
                     ->first();
    }

    public static function getSecondariesForTransaction(int $transactionId)
    {
        return static::where('statement_transaction_id', $transactionId)
                     ->where('is_primary', false)
                     ->get();
    }

    public static function getAverageConfidence($companyId = null): float
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return round($query->avg('confidence_score') ?? 0, 2);
    }

    public static function getManualVsAutoRatio($companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $total = $query->count();
        $manual = $query->clone()->where('is_manual', true)->count();
        $auto = $total - $manual;
        
        return [
            'total' => $total,
            'manual' => $manual,
            'automatic' => $auto,
            'manual_percentage' => $total > 0 ? round(($manual / $total) * 100, 2) : 0,
            'auto_percentage' => $total > 0 ? round(($auto / $total) * 100, 2) : 0,
        ];
    }

    public static function getConfidenceDistribution($companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return [
            'very_high' => $query->clone()->where('confidence_score', '>=', 90)->count(),
            'high' => $query->clone()->whereBetween('confidence_score', [70, 89])->count(),
            'medium' => $query->clone()->whereBetween('confidence_score', [50, 69])->count(),
            'low' => $query->clone()->whereBetween('confidence_score', [30, 49])->count(),
            'very_low' => $query->clone()->where('confidence_score', '<', 30)->count(),
        ];
    }
}