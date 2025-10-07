<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatementTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_statement_id',
        'transaction_date',
        'description',
        'amount',
        'balance',
        'transaction_type',
        'matched_keyword_id',
        'sub_category_id',
        'category_id',
        'type_id',
        'confidence_score',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'confidence_score' => 'integer',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
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
        return $this->hasMany(MatchingLog::class);
    }

    /**
     * Scope: Verified transactions
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Unverified transactions
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope: Matched transactions
     */
    public function scopeMatched($query)
    {
        return $query->whereNotNull('matched_keyword_id');
    }

    /**
     * Scope: Unmatched transactions
     */
    public function scopeUnmatched($query)
    {
        return $query->whereNull('matched_keyword_id');
    }

    /**
     * Scope: Low confidence
     */
    public function scopeLowConfidence($query, $threshold = 60)
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    /**
     * Check if matched
     */
    public function isMatched(): bool
    {
        return !is_null($this->matched_keyword_id);
    }

    /**
     * Verify this transaction
     */
    public function verify(int $userId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }
}