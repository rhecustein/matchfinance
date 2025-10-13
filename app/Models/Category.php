<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'type_id',
        'slug',
        'name',
        'description',
        'color',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }
            
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name, $category->company_id);
            }
            
            if (is_null($category->sort_order)) {
                $category->sort_order = static::where('company_id', $category->company_id)
                                              ->where('type_id', $category->type_id)
                                              ->max('sort_order') + 1;
            }
        });
        
        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = static::generateUniqueSlug(
                    $category->name, 
                    $category->company_id, 
                    $category->id
                );
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class)->orderBy('sort_order');
    }

    public function keywords(): HasManyThrough
    {
        return $this->hasManyThrough(
            Keyword::class,
            SubCategory::class
        )->orderBy('keywords.priority', 'desc');
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

    public function scopeForType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeWithSubCategoryCount($query)
    {
        return $query->withCount('subCategories');
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
                          ->where('type_id', $this->type_id)
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
                      ->where('type_id', $this->type_id)
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
                  ->where('type_id', $this->type_id)
                  ->whereBetween('sort_order', [$position, $oldPosition - 1])
                  ->increment('sort_order');
        } elseif ($position > $oldPosition) {
            static::where('company_id', $this->company_id)
                  ->where('type_id', $this->type_id)
                  ->whereBetween('sort_order', [$oldPosition + 1, $position])
                  ->decrement('sort_order');
        }

        return $this->update(['sort_order' => $position]);
    }

    public static function reorder(array $order, int $companyId, int $typeId): void
    {
        foreach ($order as $position => $id) {
            static::where('id', $id)
                  ->where('company_id', $companyId)
                  ->where('type_id', $typeId)
                  ->update(['sort_order' => $position]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | SubCategory Methods
    |--------------------------------------------------------------------------
    */

    public function hasSubCategories(): bool
    {
        return $this->getTotalSubCategories() > 0;
    }

    public function getActiveSubCategories()
    {
        return $this->subCategories()
                    ->orderBy('sort_order')
                    ->get();
    }

    public function getSubCategoryNames(): array
    {
        return $this->subCategories()
                    ->pluck('name', 'id')
                    ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Path Methods
    |--------------------------------------------------------------------------
    */

    public function getFullPath(): string
    {
        return "{$this->type->name} > {$this->name}";
    }

    public function getBreadcrumb(): array
    {
        return [
            'type' => $this->type->name,
            'category' => $this->name,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Slug Methods
    |--------------------------------------------------------------------------
    */

    protected static function generateUniqueSlug(string $name, int $companyId, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::slugExists($slug, $companyId, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected static function slugExists(string $slug, int $companyId, ?int $excludeId = null): bool
    {
        $query = static::where('company_id', $companyId)
                       ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
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

    public function totalSubCategories(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalSubCategories()
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

    public function typeName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->type?->name ?? 'No Type'
        );
    }

    public function colorStyle(): Attribute
    {
        return Attribute::make(
            get: fn() => "background-color: {$this->color}; border-color: {$this->color};"
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
        $subCategories = $this->subCategories()->withCount('keywords')->get();
        
        return [
            'type_name' => $this->type->name,
            'category_name' => $this->name,
            'total_sub_categories' => $subCategories->count(),
            'total_keywords' => $subCategories->sum('keywords_count'),
            'sub_categories' => $subCategories->map(function($subCategory) {
                return [
                    'name' => $subCategory->name,
                    'keywords_count' => $subCategory->keywords_count,
                ];
            }),
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->getTotalSubCategories() === 0 && 
               $this->getTotalTransactions() === 0;
    }

    public function hasTransactions(): bool
    {
        return $this->getTotalTransactions() > 0;
    }

    public function duplicate(): self
    {
        $attributes = $this->toArray();
        
        unset($attributes['id'], $attributes['uuid'], $attributes['slug'], 
              $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes['name'] = $attributes['name'] . ' (Copy)';
        $attributes['sort_order'] = static::where('company_id', $this->company_id)
                                          ->where('type_id', $this->type_id)
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

    public function getTopSubCategories(int $limit = 5)
    {
        return $this->subCategories()
                    ->withCount('transactions')
                    ->orderBy('transactions_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function isFirst(): bool
    {
        return $this->sort_order === static::where('company_id', $this->company_id)
                                           ->where('type_id', $this->type_id)
                                           ->min('sort_order');
    }

    public function isLast(): bool
    {
        return $this->sort_order === static::where('company_id', $this->company_id)
                                           ->where('type_id', $this->type_id)
                                           ->max('sort_order');
    }

    public static function findBySlug(string $slug, int $companyId): ?self
    {
        return static::where('company_id', $companyId)
                     ->where('slug', $slug)
                     ->first();
    }
}