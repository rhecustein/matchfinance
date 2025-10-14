<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class StatementTransaction extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
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
        'is_approved',
        'is_rejected',
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

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'id';  // ✅ Gunakan ID numerik
    }
        /*
    |--------------------------------------------------------------------------
    | Relationships - Core
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function bank()
    {
        return $this->hasOneThrough(
            Bank::class,
            BankStatement::class,
            'id', // Foreign key on bank_statements table
            'id', // Foreign key on banks table
            'bank_statement_id', // Local key on statement_transactions table
            'bank_id' // Local key on bank_statements table
        )->withoutGlobalScopes();
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    //approvedBy
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    //rejectedBy
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Account Matching
    |--------------------------------------------------------------------------
    */

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function matchedAccountKeyword(): BelongsTo
    {
        return $this->belongsTo(AccountKeyword::class, 'matched_account_keyword_id');
    }

    public function accountMatchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class);
    }

    public function selectedAccountMatch(): HasOne
    {
        return $this->hasOne(AccountMatchingLog::class)->where('is_selected', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Category Matching (Denormalized)
    |--------------------------------------------------------------------------
    */

    public function matchedKeyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class, 'matched_keyword_id');
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

    /*
    |--------------------------------------------------------------------------
    | Relationships - Multi-Category Support
    |--------------------------------------------------------------------------
    */

    public function transactionCategories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class);
    }

    public function primaryCategory(): HasOne
    {
        return $this->hasOne(TransactionCategory::class)
            ->where('is_primary', true)
            ->with(['type', 'category', 'subCategory']);
    }

    public function matchingLogs(): HasMany
    {
        return $this->hasMany(MatchingLog::class);
    }

    public function selectedMatch(): HasOne
    {
        return $this->hasOne(MatchingLog::class)->where('is_selected', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeManualCategory($query)
    {
        return $query->where('is_manual_category', true);
    }

    public function scopeAutoCategory($query)
    {
        return $query->where('is_manual_category', false);
    }

    public function scopeManualAccount($query)
    {
        return $query->where('is_manual_account', true);
    }

    public function scopeAutoAccount($query)
    {
        return $query->where('is_manual_account', false);
    }

    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeLowConfidence($query, $threshold = 50)
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    public function scopeDebits($query)
    {
        return $query->where('transaction_type', 'debit');
    }

    public function scopeCredits($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    public function scopeTransfers($query)
    {
        return $query->where('is_transfer', true);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeForSubCategory($query, $subCategoryId)
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    public function scopeForType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    public function scopeUncategorized($query)
    {
        return $query->whereNull('sub_category_id');
    }

    public function scopeCategorized($query)
    {
        return $query->whereNotNull('sub_category_id');
    }

    public function scopeUnassignedAccount($query)
    {
        return $query->whereNull('account_id');
    }

    public function scopeAssignedAccount($query)
    {
        return $query->whereNotNull('account_id');
    }

    public function scopeMatched($query)
    {
        return $query->whereNotNull('sub_category_id');
    }

    public function scopeUnmatched($query)
    {
        return $query->whereNull('sub_category_id');
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('is_verified', false)
                     ->where('confidence_score', '<', 70);
    }

    public function scopeSearchDescription($query, $search)
    {
        return $query->where('description', 'like', "%{$search}%");
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('transaction_date', $year)
                     ->whereMonth('transaction_date', $month);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('transaction_date', now()->year)
                     ->whereMonth('transaction_date', now()->month);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('transaction_date', now()->year);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Check Methods
    |--------------------------------------------------------------------------
    */

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function isCategorized(): bool
    {
        return !is_null($this->sub_category_id);
    }

    public function hasAccount(): bool
    {
        return !is_null($this->account_id);
    }

    public function isManualCategory(): bool
    {
        return $this->is_manual_category;
    }

    public function isManualAccount(): bool
    {
        return $this->is_manual_account;
    }

    public function isDebit(): bool
    {
        return $this->transaction_type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->transaction_type === 'credit';
    }

    public function isTransfer(): bool
    {
        return $this->is_transfer;
    }

    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    public function needsReview(): bool
    {
        return !$this->is_verified && $this->confidence_score < 70;
    }

    public function hasHighConfidence(): bool
    {
        return $this->confidence_score >= 80;
    }

    public function hasLowConfidence(): bool
    {
        return $this->confidence_score < 50;
    }

    /*
    |--------------------------------------------------------------------------
    | Action Methods
    |--------------------------------------------------------------------------
    */

    public function markAsVerified(?int $userId = null): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_by' => $userId ?? auth()->id(),
            'verified_at' => now(),
        ]);
    }

    public function unverify(): bool
    {
        return $this->update([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function toggleVerification(?int $userId = null): bool
    {
        if ($this->is_verified) {
            return $this->unverify();
        }
        return $this->markAsVerified($userId);
    }

    public function assignAccount(int $accountId, ?int $keywordId = null, ?int $confidence = 100, ?string $reason = null): bool
    {
        return $this->update([
            'account_id' => $accountId,
            'matched_account_keyword_id' => $keywordId,
            'account_confidence_score' => $confidence,
            'is_manual_account' => true,
            'matching_reason' => $reason,
        ]);
    }

    public function assignCategory(int $subCategoryId, ?int $keywordId = null, ?int $confidence = 100, ?string $reason = null): bool
    {
        $subCategory = SubCategory::with(['category.type'])->find($subCategoryId);
        
        if (!$subCategory) {
            return false;
        }

        return $this->update([
            'sub_category_id' => $subCategory->id,
            'category_id' => $subCategory->category_id,
            'type_id' => $subCategory->category->type_id,
            'matched_keyword_id' => $keywordId,
            'confidence_score' => $confidence,
            'is_manual_category' => true,
            'matching_reason' => $reason,
        ]);
    }

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

    public function clearAccount(): bool
    {
        return $this->update([
            'account_id' => null,
            'matched_account_keyword_id' => null,
            'account_confidence_score' => null,
            'is_manual_account' => false,
        ]);
    }

    public function markAsTransfer(): bool
    {
        return $this->update(['is_transfer' => true]);
    }

    public function markAsRecurring(string $pattern = 'monthly'): bool
    {
        return $this->update([
            'is_recurring' => true,
            'recurring_pattern' => $pattern,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->amount, 0, ',', '.')
        );
    }

    public function formattedDebit(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->debit_amount, 0, ',', '.')
        );
    }

    public function formattedCredit(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->credit_amount, 0, ',', '.')
        );
    }

    public function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->balance ? 'Rp ' . number_format($this->balance, 0, ',', '.') : 'N/A'
        );
    }

    public function confidenceLabel(): Attribute
    {
        $labels = [
            [90, 'Very High'],
            [70, 'High'],
            [50, 'Medium'],
            [30, 'Low'],
            [0, 'Very Low'],
        ];

        return Attribute::make(
            get: function() use ($labels) {
                foreach ($labels as [$threshold, $label]) {
                    if ($this->confidence_score >= $threshold) {
                        return $label;
                    }
                }
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

    public function categoryPath(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->type_id || !$this->category_id || !$this->sub_category_id) {
                    return 'Uncategorized';
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

    public function directionLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transaction_type === 'credit' ? 'In' : 'Out'
        );
    }

    public function directionIcon(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transaction_type === 'credit' ? '↓' : '↑'
        );
    }

    public function directionBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transaction_type === 'credit' 
                ? 'bg-green-100 text-green-800' 
                : 'bg-red-100 text-red-800'
        );
    }

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->is_verified) return 'bg-green-100 text-green-800';
                if ($this->isCategorized()) return 'bg-blue-100 text-blue-800';
                return 'bg-gray-100 text-gray-800';
            }
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->is_verified) return 'Verified';
                if ($this->isCategorized()) return 'Categorized';
                return 'Uncategorized';
            }
        );
    }

    public function accountName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->account?->name ?? 'Unassigned'
        );
    }

    public function subCategoryName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->subCategory?->name ?? 'Uncategorized'
        );
    }

    public function bankName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->bankStatement?->bank?->name ?? 'Unknown Bank'
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
            'date' => $this->transaction_date->format('Y-m-d'),
            'time' => $this->transaction_time,
            'description' => $this->description,
            'type' => $this->transaction_type,
            'direction' => $this->direction_label,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
            'balance' => $this->balance,
            'formatted_balance' => $this->formatted_balance,
            'reference_no' => $this->reference_no,
            'account' => $this->account_name,
            'account_status' => $this->account_status,
            'account_confidence' => $this->account_confidence_label,
            'category_path' => $this->category_path,
            'categorization_status' => $this->categorization_status,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'needs_review' => $this->needsReview(),
            'is_transfer' => $this->is_transfer,
            'is_recurring' => $this->is_recurring,
            'recurring_pattern' => $this->recurring_pattern,
            'notes' => $this->notes,
        ];
    }

    public function getSummary(): array
    {
        return [
            'date' => $this->transaction_date->format('d M Y'),
            'description' => Str::limit($this->description, 50),
            'amount' => $this->formatted_amount,
            'type' => $this->direction_label,
            'category' => $this->sub_category_name,
            'account' => $this->account_name,
            'status' => $this->status_label,
        ];
    }

    public function duplicate(): self
    {
        $attributes = $this->toArray();
        
        unset($attributes['id'], $attributes['uuid'], $attributes['created_at'], 
              $attributes['updated_at'], $attributes['deleted_at'], 
              $attributes['is_verified'], $attributes['verified_by'], $attributes['verified_at']);
        
        return static::create($attributes);
    }

    public static function getTotalByType(string $type, $companyId = null)
    {
        $query = static::where('transaction_type', $type);
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (auth()->check()) {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        return $query->sum('amount');
    }

    public static function getMonthlyTotals($year, $month, $companyId = null)
    {
        $query = static::whereYear('transaction_date', $year)
                       ->whereMonth('transaction_date', $month);
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif (auth()->check()) {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        return [
            'total_debit' => $query->sum('debit_amount'),
            'total_credit' => $query->sum('credit_amount'),
            'net' => $query->sum('credit_amount') - $query->sum('debit_amount'),
        ];
    }
}