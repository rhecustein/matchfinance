<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'priority',
        'sort_order',
    ];

    protected $casts = [
        'priority' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category that owns this sub category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

        public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    // Tambahkan relasi ke transaction_categories
    public function transactionCategories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class);
    }

    /**
     * Get all transactions for this sub category
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    /**
     * Get active keywords only
     */
    public function activeKeywords(): HasMany
    {
        return $this->keywords()->where('is_active', true);
    }
}