<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class Type extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'description',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($type) {
            if (empty($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }
            
            if (is_null($type->sort_order)) {
                $type->sort_order = static::where('company_id', $type->company_id)
                                          ->max('sort_order') + 1;
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function subCategories(): HasManyThrough
    {
        return $this->hasManyThrough(
            SubCategory::class,
            Category::class
        )->orderBy('sub_categories.sort_order');
    }

    public function keywords(): HasManyThrough
    {
        return $this->hasManyThrough(
            Keyword::class,
            SubCategory::class,
            'category_id',
            'sub_category_id',
            'id',
            'id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    public function transactionCategories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeWithCategoryCount($query)
    {
        return $query->withCount('categories');
    }

    public function scopeWithTransactionCount($query)
    {
        return $query->withCount('transactions');
    }

    public function scopePopular($query)
    {
        return $query->withCount('transactions')
                     ->orderBy('transactions_count', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Ordering Methods
    |--------------------------------------------------------------------------
    */

    public function moveUp(): bool
    {
        $previous = static::where('company_id', $this->company_id)
                          ->where('sort_order', '<', $this->sort_order)
                          ->orderBy('sort_order', 'desc')
                          ->first();

        if ($previous) {
            $tempOrder = $this->sort_order;
            $this->sort_order = $previous->sort_order;
            $previous->sort_order = $tempOrder;
            
            $previous->save();
            return $this->save();
        }

        return false;
    }

    public function moveDown(): bool
    {
        $next = static::where('company_id', $this->company_id)
                      ->where('sort_order', '>', $this->sort_order)
                      ->orderBy('sort_order', 'asc')
                      ->first();

        if ($next) {
            $tempOrder = $this->sort_order;
            $this->sort_order = $next->sort_order;
            $next->sort_order = $tempOrder;
            
            $next->save();
            return $this->save();
        }

        return false;
    }

    public function moveToPosition(int $position): bool
    {
        $oldPosition = $this->sort_order;
        
        if ($position < $oldPosition) {
            static::where('company_id', $this->company_id)
                  ->whereBetween('sort_order', [$position, $oldPosition - 1])
                  ->increment('sort_order');
        } elseif ($position > $oldPosition) {
            static::where('company_id', $this->company_id)
                  ->whereBetween('sort_order', [$oldPosition + 1, $position])
                  ->decrement('sort_order');
        }

        return $this->update(['sort_order' => $position]);
    }

    public static function reorder(array $order): void
    {
        foreach ($order as $position => $id) {
            static::where('id', $id)->update(['sort_order' => $position]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalCategories(): int
    {
        return $this->categories()->count();
    }

    public function getTotalSubCategories(): int
    {
        return $this->subCategories()->count();
    }

    public function getTotalKeywords(): int
    {
        return $this->keywords()->count();
    }

    public function getTotalTransactions(): int
    {
        return $this->transactions()->count();
    }

    public function getMatchedTransactions(): int
    {
        return $this->transactions()
                    ->whereNotNull('matched_keyword_id')
                    ->count();
    }

    public function getVerifiedTransactions(): int
    {
        return $this->transactions()
                    ->where('is_verified', true)
                    ->count();
    }

    public function getTotalAmount()
    {
        return $this->transactions()->sum('amount');
    }

    public function getTotalDebit()
    {
        return $this->transactions()->sum('debit_amount');
    }

    public function getTotalCredit()
    {
        return $this->transactions()->sum('credit_amount');
    }

    public function getMonthlyTotal($year, $month)
    {
        return $this->transactions()
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
                    ->sum('amount');
    }

    public function getMonthlyCount($year, $month): int
    {
        return $this->transactions()
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
                    ->count();
    }

    public function getAmountByPeriod($startDate, $endDate)
    {
        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->sum('amount');
    }

    public function getTransactionsByPeriod($startDate, $endDate)
    {
        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Category Methods
    |--------------------------------------------------------------------------
    */

    public function hasCategories(): bool
    {
        return $this->getTotalCategories() > 0;
    }

    public function getActiveCategories()
    {
        return $this->categories()
                    ->orderBy('sort_order')
                    ->get();
    }

    public function getCategoryNames(): array
    {
        return $this->categories()
                    ->pluck('name', 'id')
                    ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->getTotalAmount(), 0, ',', '.')
        );
    }

    public function totalCategories(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalCategories()
        );
    }

    public function totalTransactions(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalTransactions()
        );
    }

    public function matchingRate(): Attribute
    {
        return Attribute::make(
            get: function() {
                $total = $this->getTotalTransactions();
                if ($total === 0) return 0;
                
                $matched = $this->getMatchedTransactions();
                return round(($matched / $total) * 100, 2);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getStatistics(): array
    {
        return [
            'total_categories' => $this->getTotalCategories(),
            'total_sub_categories' => $this->getTotalSubCategories(),
            'total_keywords' => $this->getTotalKeywords(),
            'total_transactions' => $this->getTotalTransactions(),
            'matched_transactions' => $this->getMatchedTransactions(),
            'verified_transactions' => $this->getVerifiedTransactions(),
            'total_amount' => $this->getTotalAmount(),
            'total_debit' => $this->getTotalDebit(),
            'total_credit' => $this->getTotalCredit(),
            'matching_rate' => $this->matching_rate,
        ];
    }

    public function getHierarchyInfo(): array
    {
        $categories = $this->categories()->withCount(['subCategories', 'keywords'])->get();
        
        return [
            'type_name' => $this->name,
            'total_categories' => $categories->count(),
            'total_sub_categories' => $categories->sum('sub_categories_count'),
            'total_keywords' => $categories->sum('keywords_count'),
            'categories' => $categories->map(function($category) {
                return [
                    'name' => $category->name,
                    'sub_categories_count' => $category->sub_categories_count,
                    'keywords_count' => $category->keywords_count,
                ];
            }),
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->getTotalCategories() === 0 && 
               $this->getTotalTransactions() === 0;
    }

    public function hasTransactions(): bool
    {
        return $this->getTotalTransactions() > 0;
    }

    public function duplicate(): self
    {
        $attributes = $this->toArray();
        
        unset($attributes['id'], $attributes['uuid'], $attributes['created_at'], 
              $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes['name'] = $attributes['name'] . ' (Copy)';
        $attributes['sort_order'] = static::where('company_id', $this->company_id)
                                          ->max('sort_order') + 1;
        
        return static::create($attributes);
    }

    public function getMonthlyTrend(int $months = 6): array
    {
        $trend = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('M Y');
            
            $count = $this->getMonthlyCount($date->year, $date->month);
            $amount = $this->getMonthlyTotal($date->year, $date->month);
            
            $trend[] = [
                'month' => $month,
                'transactions' => $count,
                'amount' => $amount,
            ];
        }
        
        return $trend;
    }

    public function getTopCategories(int $limit = 5)
    {
        return $this->categories()
                    ->withCount('transactions')
                    ->orderBy('transactions_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function isFirst(): bool
    {
        return $this->sort_order === static::where('company_id', $this->company_id)
                                           ->min('sort_order');
    }

    public function isLast(): bool
    {
        return $this->sort_order === static::where('company_id', $this->company_id)
                                           ->max('sort_order');
    }
}