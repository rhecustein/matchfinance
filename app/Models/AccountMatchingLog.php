<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMatchingLog extends Model
{
    use HasFactory;

    protected $fillable = [
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
}