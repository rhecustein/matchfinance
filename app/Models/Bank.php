<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all bank statements for this bank
     */
    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    /**
     * Scope: Only active banks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}