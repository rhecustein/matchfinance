<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword_group_id',
        'keyword_id',
        'is_required',
        'is_negative',
        'position',
        'weight'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_negative' => 'boolean',
        'position' => 'integer',
        'weight' => 'integer'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function keywordGroup(): BelongsTo
    {
        return $this->belongsTo(KeywordGroup::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeNegative($query)
    {
        return $query->where('is_negative', true);
    }

    public function scopePositive($query)
    {
        return $query->where('is_negative', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
