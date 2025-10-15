<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class AccountMatchingLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'statement_transaction_id',
        'account_id',
        'account_keyword_id',
        'confidence_score',
        'is_matched',
        'match_reason',
        'match_details',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'is_matched' => 'boolean',
        'match_details' => 'array',
    ];

    /**
     * Boot method untuk auto-generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Route key name menggunakan UUID
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get transaction
     */
    public function statementTransaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class);
    }

    /**
     * Get account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get keyword yang dipakai untuk matching
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(AccountKeyword::class, 'account_keyword_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope matched logs
     */
    public function scopeMatched($query)
    {
        return $query->where('is_matched', true);
    }

    /**
     * Scope by transaction
     */
    public function scopeForTransaction($query, $transactionId)
    {
        return $query->where('statement_transaction_id', $transactionId);
    }

    /**
     * Scope by account
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope by keyword
     */
    public function scopeForKeyword($query, $keywordId)
    {
        return $query->where('account_keyword_id', $keywordId);
    }

    /**
     * Scope high confidence
     */
    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope low confidence
     */
    public function scopeLowConfidence($query, $threshold = 50)
    {
        return $query->where('confidence_score', '<', $threshold);
    }
}