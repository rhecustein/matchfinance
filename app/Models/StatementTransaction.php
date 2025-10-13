<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'transaction_type',
        'amount',
        'account_id',
        'matched_account_keyword_id',
        'account_confidence_score',
        'is_manual_account',
        'matched_keyword_id',
        'sub_category_id',
        'category_id',
        'type_id',
        'confidence_score',
        'is_manual_category',
        'matching_reason',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
        'metadata',
        'is_transfer',
        'is_recurring',
        'recurring_pattern',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'amount' => 'decimal:2',
        'account_confidence_score' => 'integer',
        'is_manual_account' => 'boolean',
        'confidence_score' => 'integer',
        'is_manual_category' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
        'is_transfer' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships - Core
    |--------------------------------------------------------------------------
    */

    /**
     * Get the bank statement that owns this transaction
     */
    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    /**
     * Get the user who verified this transaction
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Account Matching
    |--------------------------------------------------------------------------
    */

    /**
     * Get the matched account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the account keyword that matched
     */
    public function matchedAccountKeyword(): BelongsTo
    {
        return $this->belongsTo(AccountKeyword::class, 'matched_account_keyword_id');
    }

    /**
     * Get all account matching logs
     */
    public function accountMatchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class);
    }

    /**
     * Get selected account match log
     */
    public function selectedAccountMatch(): HasOne
    {
        return $this->hasOne(AccountMatchingLog::class)->where('is_selected', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Category Matching (Denormalized)
    |--------------------------------------------------------------------------
    */

    /**
     * Get the matched keyword (primary)
     */
    public function matchedKeyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class, 'matched_keyword_id');
    }

    /**
     * Get the sub category (primary)
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Get the category (primary)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the type (primary)
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Multi-Category Support
    |--------------------------------------------------------------------------
    */

    /**
     * Get all category assignments for this transaction
     */
    public function transactionCategories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class);
    }

    /**
     * Get primary category assignment
     */
    public function primaryCategory(): HasOne
    {
        return $this->hasOne(TransactionCategory::class)
            ->where('is_primary', true)
            ->with(['type', 'category', 'subCategory']);
    }

    /**
     * Get all matching logs
     */
    public function matchingLogs(): HasMany
    {
        return $this->hasMany(MatchingLog::class);
    }

    /**
     * Get selected matching log
     */
    public function selectedMatch(): HasOne
    {
        return $this->hasOne(MatchingLog::class)->where('is_selected', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for verified transactions
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for unverified transactions
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope for manually categorized
     */
    public function scopeManualCategory($query)
    {
        return $query->where('is_manual_category', true);
    }

    /**
     * Scope for auto-categorized
     */
    public function scopeAutoCategory($query)
    {
        return $query->where('is_manual_category', false);
    }

    /**
     * Scope for high confidence matches
     */
    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope for low confidence matches
     */
    public function scopeLowConfidence($query, $threshold = 50)
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('transaction_type', 'debit');
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    /**
     * Scope for transfer transactions
     */
    public function scopeTransfers($query)
    {
        return $query->where('is_transfer', true);
    }

    /**
     * Scope for recurring transactions
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope for specific account
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope for specific category
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for specific type
     */
    public function scopeForType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Scope for uncategorized transactions
     */
    public function scopeUncategorized($query)
    {
        return $query->whereNull('sub_category_id');
    }

    /**
     * Scope for categorized transactions
     */
    public function scopeCategorized($query)
    {
        return $query->whereNotNull('sub_category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted amount with currency
     */
    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->amount, 0, ',', '.')
        );
    }

    /**
     * Get confidence label
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

    /**
     * Get account confidence label
     */
    public function accountConfidenceLabel(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->account_confidence_score) return 'N/A';
                if ($this->account_confidence_score >= 90) return 'Very High';
                if ($this->account_confidence_score >= 70) return 'High';
                if ($this->account_confidence_score >= 50) return 'Medium';
                if ($this->account_confidence_score >= 30) return 'Low';
                return 'Very Low';
            }
        );
    }

    /**
     * Get category path (Type > Category > SubCategory)
     */
    public function categoryPath(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->relationLoaded('type') || !$this->relationLoaded('category') || !$this->relationLoaded('subCategory')) {
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

    /**
     * Get categorization status
     */
    public function categorizationStatus(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->is_manual_category) return 'Manual';
                if ($this->sub_category_id) return 'Auto';
                return 'Uncategorized';
            }
        );
    }

    /**
     * Get account assignment status
     */
    public function accountStatus(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->is_manual_account) return 'Manual';
                if ($this->account_id) return 'Auto';
                return 'Unassigned';
            }
        );
    }

    /**
     * Check if needs review (low confidence + not verified)
     */
    public function needsReview(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->is_verified && $this->confidence_score < 70
        );
    }

    /**
     * Get transaction direction icon/label
     */
    public function directionLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transaction_type === 'credit' ? 'In' : 'Out'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark as verified
     */
    public function markAsVerified(?int $userId = null): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_by' => $userId ?? auth()->id(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Assign account manually
     */
    public function assignAccount(int $accountId, ?string $reason = null): bool
    {
        return $this->update([
            'account_id' => $accountId,
            'is_manual_account' => true,
            'account_confidence_score' => 100,
            'matching_reason' => $reason,
        ]);
    }

    /**
     * Assign category manually
     */
    public function assignCategory(int $subCategoryId, ?string $reason = null): bool
    {
        $subCategory = SubCategory::with(['category.type'])->find($subCategoryId);
        
        if (!$subCategory) {
            return false;
        }

        return $this->update([
            'sub_category_id' => $subCategory->id,
            'category_id' => $subCategory->category_id,
            'type_id' => $subCategory->category->type_id,
            'is_manual_category' => true,
            'confidence_score' => 100,
            'matching_reason' => $reason,
        ]);
    }

    /**
     * Clear categorization
     */
    public function clearCategory(): bool
    {
        return $this->update([
            'matched_keyword_id' => null,
            'sub_category_id' => null,
            'category_id' => null,
            'type_id' => null,
            'confidence_score' => 0,
            'is_manual_category' => false,
            'matching_reason' => null,
        ]);
    }

    /**
     * Clear account assignment
     */
    public function clearAccount(): bool
    {
        return $this->update([
            'account_id' => null,
            'matched_account_keyword_id' => null,
            'account_confidence_score' => null,
            'is_manual_account' => false,
        ]);
    }

    /**
     * Get full transaction details
     */
    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->transaction_date->format('Y-m-d'),
            'time' => $this->transaction_time,
            'description' => $this->description,
            'type' => $this->transaction_type,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'balance' => $this->balance,
            'reference_no' => $this->reference_no,
            'account' => $this->account?->name,
            'account_status' => $this->account_status,
            'account_confidence' => $this->account_confidence_label,
            'category_path' => $this->category_path,
            'categorization_status' => $this->categorization_status,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'is_verified' => $this->is_verified,
            'needs_review' => $this->needs_review,
            'is_transfer' => $this->is_transfer,
            'is_recurring' => $this->is_recurring,
            'notes' => $this->notes,
        ];
    }
}