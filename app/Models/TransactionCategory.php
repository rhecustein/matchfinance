<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TransactionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
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

    /**
     * Get the statement transaction
     */
    public function statementTransaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class);
    }

    /**
     * Get the sub category
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the type
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Get the matched keyword
     */
    public function matchedKeyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class, 'matched_keyword_id');
    }

    /**
     * Get the user who assigned this category
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope: Only primary categories
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Only secondary categories
     */
    public function scopeSecondary($query)
    {
        return $query->where('is_primary', false);
    }

    /**
     * Scope: Only manual assignments
     */
    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    /**
     * Scope: Only automatic assignments
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_manual', false);
    }

    /**
     * Scope: High confidence (>= 90)
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 90);
    }

    /**
     * Scope: Low confidence (< 80)
     */
    public function scopeLowConfidence($query)
    {
        return $query->where('confidence_score', '<', 80);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by sub category
     */
    public function scopeBySubCategory($query, $subCategoryId)
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Check if this is primary category
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Check if manually assigned
     */
    public function isManual(): bool
    {
        return $this->is_manual;
    }

    /**
     * Check if high confidence
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence_score >= 90;
    }

    /**
     * Check if low confidence
     */
    public function isLowConfidence(): bool
    {
        return $this->confidence_score < 80;
    }

    /**
     * Set as primary category
     */
    public function setAsPrimary(): void
    {
        // Remove primary from other categories for this transaction
        static::where('statement_transaction_id', $this->statement_transaction_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Set as secondary category
     */
    public function setAsSecondary(): void
    {
        $this->update(['is_primary' => false]);
    }

    /**
     * Update confidence score
     */
    public function updateConfidence(int $score, ?string $reason = null): void
    {
        $this->update([
            'confidence_score' => $score,
            'reason' => $reason ?? $this->reason,
        ]);
    }

    /**
     * Get confidence level label
     */
    public function confidenceLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->confidence_score >= 90) return 'High';
                if ($this->confidence_score >= 80) return 'Medium';
                if ($this->confidence_score >= 70) return 'Low';
                return 'Very Low';
            }
        );
    }

    /**
     * Get confidence color for badges
     */
    public function confidenceColor(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->confidence_score >= 90) return 'green';
                if ($this->confidence_score >= 80) return 'blue';
                if ($this->confidence_score >= 70) return 'yellow';
                return 'red';
            }
        );
    }

    /**
     * Get assignment type label
     */
    public function assignmentTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_manual ? 'Manual' : 'Automatic'
        );
    }

    /**
     * Get category path (Type > Category > SubCategory)
     */
    public function categoryPath(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->relationLoaded('type') || !$this->relationLoaded('category') || !$this->relationLoaded('subCategory')) {
                    $this->load(['type', 'category', 'subCategory']);
                }

                return sprintf(
                    '%s > %s > %s',
                    $this->type->name ?? 'N/A',
                    $this->category->name ?? 'N/A',
                    $this->subCategory->name ?? 'N/A'
                );
            }
        );
    }

    /**
     * Get full details as array
     */
    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'is_primary' => $this->is_primary,
            'is_manual' => $this->is_manual,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'type' => $this->type->name ?? null,
            'category' => $this->category->name ?? null,
            'sub_category' => $this->subCategory->name ?? null,
            'keyword' => $this->matchedKeyword->keyword ?? null,
            'reason' => $this->reason,
            'assigned_by' => $this->assignedBy->name ?? null,
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
        ];
    }
}