<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

class StatementTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_statement_id',
        'transaction_date',
        'transaction_time',
        'value_date',
        'branch_code',
        'description',
        'reference_no',
        'debit_amount',
        'credit_amount',
        'balance',
        'amount',
        'transaction_type',
        'matched_keyword_id',
        'sub_category_id',
        'category_id',
        'type_id',
        'confidence_score',
        'is_manual_category',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'confidence_score' => 'integer',
        'is_manual_category' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'amount',
        'signed_amount',
    ];

    /**
     * Get the bank statement that owns this transaction
     */
    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    /**
     * Get the matched keyword
     */
    public function matchedKeyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class, 'matched_keyword_id');
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
     * Get the user who verified this transaction
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get all matching logs for this transaction
     */
    public function matchingLogs(): HasMany
    {
        return $this->hasMany(MatchingLog::class, 'statement_transaction_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by matched status
     */
    public function scopeMatched(Builder $query): Builder
    {
        return $query->whereNotNull('matched_keyword_id');
    }

    /**
     * Scope: Filter by unmatched status
     */
    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->whereNull('matched_keyword_id');
    }

    /**
     * Scope: Filter by verified status
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Filter by unverified status
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope: Filter by low confidence (< 80)
     */
    public function scopeLowConfidence(Builder $query): Builder
    {
        return $query->where('confidence_score', '<', 80)
            ->whereNotNull('matched_keyword_id');
    }

    /**
     * Scope: Filter by high confidence (>= 80)
     */
    public function scopeHighConfidence(Builder $query): Builder
    {
        return $query->where('confidence_score', '>=', 80)
            ->whereNotNull('matched_keyword_id');
    }

    /**
     * Scope: Filter by transaction type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope: Filter debit transactions
     */
    public function scopeDebit(Builder $query): Builder
    {
        return $query->where('transaction_type', 'debit');
    }

    /**
     * Scope: Filter credit transactions
     */
    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('transaction_type', 'credit');
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by sub category
     */
    public function scopeBySubCategory(Builder $query, int $subCategoryId): Builder
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeByType(Builder $query, int $typeId): Builder
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Scope: Search by description
     */
    public function scopeSearchDescription(Builder $query, string $search): Builder
    {
        return $query->where('description', 'like', "%{$search}%");
    }

    /**
     * Scope: Filter manually categorized
     */
    public function scopeManuallyCategorized(Builder $query): Builder
    {
        return $query->where('is_manual_category', true);
    }

    /**
     * Scope: Filter automatically categorized
     */
    public function scopeAutoCategorized(Builder $query): Builder
    {
        return $query->where('is_manual_category', false)
            ->whereNotNull('matched_keyword_id');
    }

    /**
     * Scope: Order by date and time
     */
    public function scopeOrderByDateTime(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('transaction_date', $direction)
            ->orderBy('transaction_time', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if transaction is matched
     */
    public function isMatched(): bool
    {
        return !is_null($this->matched_keyword_id);
    }

    /**
     * Check if transaction is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Check if confidence is low (< 80)
     */
    public function isLowConfidence(): bool
    {
        return $this->confidence_score < 80 && $this->isMatched();
    }

    /**
     * Check if manually categorized
     */
    public function isManuallyCategorized(): bool
    {
        return $this->is_manual_category;
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(int $userId, ?string $notes = null): bool
    {
        $result = $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);

        if ($result && $this->bankStatement) {
            // Update statement statistics
            $this->bankStatement->updateMatchingStats();
        }

        return $result;
    }

    /**
     * Unverify transaction
     */
    public function unverify(): bool
    {
        $result = $this->update([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        if ($result && $this->bankStatement) {
            // Update statement statistics
            $this->bankStatement->updateMatchingStats();
        }

        return $result;
    }

    /**
     * Set matching result
     */
    public function setMatchingResult(
        int $keywordId,
        int $subCategoryId,
        int $categoryId,
        int $typeId,
        int $confidenceScore
    ): bool {
        $result = $this->update([
            'matched_keyword_id' => $keywordId,
            'sub_category_id' => $subCategoryId,
            'category_id' => $categoryId,
            'type_id' => $typeId,
            'confidence_score' => $confidenceScore,
            'is_manual_category' => false,
        ]);

        if ($result && $this->bankStatement) {
            // Update statement statistics
            $this->bankStatement->updateMatchingStats();
        }

        return $result;
    }

    /**
     * Set manual category
     */
    public function setManualCategory(
        int $subCategoryId,
        int $categoryId,
        int $typeId,
        ?string $notes = null
    ): bool {
        $result = $this->update([
            'sub_category_id' => $subCategoryId,
            'category_id' => $categoryId,
            'type_id' => $typeId,
            'confidence_score' => 100,
            'is_manual_category' => true,
            'matched_keyword_id' => null,
            'notes' => $notes,
        ]);

        if ($result && $this->bankStatement) {
            // Update statement statistics
            $this->bankStatement->updateMatchingStats();
        }

        return $result;
    }

    /**
     * Clear matching
     */
    public function clearMatching(): bool
    {
        $result = $this->update([
            'matched_keyword_id' => null,
            'sub_category_id' => null,
            'category_id' => null,
            'type_id' => null,
            'confidence_score' => 0,
            'is_manual_category' => false,
        ]);

        if ($result && $this->bankStatement) {
            // Update statement statistics
            $this->bankStatement->updateMatchingStats();
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get amount (debit or credit)
     * FIXED: Handle NULL values properly
     */
    public function getAmountAttribute(): float
    {
        if ($this->transaction_type === 'debit') {
            return (float) ($this->debit_amount ?? 0);
        }
        
        return (float) ($this->credit_amount ?? 0);
    }

    /**
     * Get amount with sign
     * FIXED: Handle NULL values properly
     */
    public function getSignedAmountAttribute(): float
    {
        if ($this->transaction_type === 'debit') {
            return -(float) ($this->debit_amount ?? 0);
        }
        
        return (float) ($this->credit_amount ?? 0);
    }

    /**
     * Get confidence level label
     */
    public function getConfidenceLabelAttribute(): string
    {
        if ($this->confidence_score >= 90) return 'High';
        if ($this->confidence_score >= 80) return 'Medium';
        if ($this->confidence_score >= 70) return 'Low';
        return 'Very Low';
    }

    /**
     * Get confidence color
     */
    public function getConfidenceColorAttribute(): string
    {
        if ($this->confidence_score >= 90) return 'green';
        if ($this->confidence_score >= 80) return 'blue';
        if ($this->confidence_score >= 70) return 'yellow';
        return 'red';
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_verified) return 'Verified';
        if ($this->isMatched() && $this->confidence_score >= 80) return 'Matched';
        if ($this->isMatched() && $this->confidence_score < 80) return 'Need Review';
        return 'Unmatched';
    }

    /**
     * Get formatted transaction date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->transaction_date ? $this->transaction_date->format('d M Y') : '-';
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance ?? 0, 0, ',', '.');
    }
}