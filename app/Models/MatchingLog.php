<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchingLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'statement_transaction_id',
        'keyword_id',
        'matched_text',
        'confidence_score',
        'match_metadata',
        'matched_at',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'match_metadata' => 'array',
        'matched_at' => 'datetime',
    ];

    /**
     * Get the transaction
     */
    public function statementTransaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class);
    }

    /**
     * Get the keyword
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Scope: Recent logs
     */
    public function scopeRecent($query, $limit = 100)
    {
        return $query->orderBy('matched_at', 'desc')
            ->limit($limit);
    }

    /**
     * Scope: By keyword
     */
    public function scopeByKeyword($query, $keywordId)
    {
        return $query->where('keyword_id', $keywordId);
    }

    /**
     * Scope: High confidence
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 90);
    }
}