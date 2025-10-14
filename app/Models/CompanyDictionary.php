<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CompanyDictionary extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'dictionary_type',
        'word',
        'weight',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($dict) {
            if (empty($dict->uuid)) {
                $dict->uuid = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('dictionary_type', $type);
    }

    public function scopeIgnoreWords($query)
    {
        return $query->where('dictionary_type', 'IGNORE_WORDS');
    }

    public function scopePriorityWords($query)
    {
        return $query->where('dictionary_type', 'PRIORITY_WORDS');
    }
}