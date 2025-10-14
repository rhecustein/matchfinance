<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class KeywordSuggestion extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'sub_category_id',
        'keyword',
        'source_transaction_id',
        'confidence',
        'occurrence_count',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason'
    ];

    protected $casts = [
        'confidence' => 'integer',
        'occurrence_count' => 'integer',
        'reviewed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($suggestion) {
            if (empty($suggestion->uuid)) {
                $suggestion->uuid = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function sourceTransaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class, 'source_transaction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeHighConfidence($query, int $threshold = 80)
    {
        return $query->where('confidence', '>=', $threshold);
    }

    public function approve(int $userId): bool
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $userId,
            'reviewed_at' => now()
        ]);

        // Create actual keyword
        Keyword::create([
            'uuid' => Str::uuid(),
            'company_id' => $this->company_id,
            'sub_category_id' => $this->sub_category_id,
            'keyword' => $this->keyword,
            'match_type' => 'contains',
            'priority' => 5,
            'is_active' => true,
            'pattern_description' => 'Auto-approved from suggestion'
        ]);

        return true;
    }

    public function reject(int $userId, string $reason = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason
        ]);
    }
}