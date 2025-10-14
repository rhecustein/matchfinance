<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatternGroupItem extends Pivot
{
    protected $table = 'pattern_group_items';

    protected $fillable = [
        'pattern_group_id',
        'keyword_pattern_id',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    public function patternGroup(): BelongsTo
    {
        return $this->belongsTo(PatternGroup::class);
    }

    public function keywordPattern(): BelongsTo
    {
        return $this->belongsTo(KeywordPattern::class);
    }
}