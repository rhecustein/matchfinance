<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class SubCategory extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'category_id',
        'name',
        'description',
        'priority',
        'sort_order'
    ];

    protected $casts = [
        'priority' => 'integer',
        'sort_order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($subCategory) {
            if (empty($subCategory->uuid)) {
                $subCategory->uuid = (string) Str::uuid();
            }
            
            if (empty($subCategory->company_id) && $subCategory->category) {
                $subCategory->company_id = $subCategory->category->company_id;
            }
            
            if (is_null($subCategory->sort_order)) {
                $subCategory->sort_order = static::where('company_id', $subCategory->company_id)
                                                 ->where('category_id', $subCategory->category_id)
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function type(): BelongsTo
    {
        return $this->category->type();
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class)->orderBy('priority', 'desc');
    }

    public function activeKeywords(): HasMany
    {
        return $this->hasMany(Keyword::class)
                    ->where('is_active', true)
                    ->orderBy('priority', 'desc');
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

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 7)->orderBy('priority', 'desc');
    }

    public function scopeMediumPriority($query)
    {
        return $query->whereBetween('priority', [4, 6])->orderBy('priority', 'desc');
    }

    public function scopeLowPriority($query)
    {
        return $query->where('priority', '<=', 3)->orderBy('priority', 'desc');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeWithKeywordCount($query)
    {
        return $query->withCount('keywords');
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

    public function scopeOrderedForMatching($query)
    {
        return $query->orderBy('priority', 'desc')
                     ->orderBy('sort_order', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | Ordering Methods
    |--------------------------------------------------------------------------
    */

    public function moveUp(): bool
    {
        $previous = static::where('company_id', $this->company_id)
                          ->where('category_id', $this->category_id)
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
                      ->where('category_id', $this->category_id)
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
                  ->where('category_id', $this->category_id)
                  ->whereBetween('sort_order', [$position, $oldPosition - 1])
                  ->increment('sort_order');
        } elseif ($position > $oldPosition) {
            static::where('company_id', $this->company_id)
                  ->where('category_id', $this->category_id)
                  ->whereBetween('sort_order', [$oldPosition + 1, $position])
                  ->decrement('sort_order');
        }

        return $this->update(['sort_order' => $position]);
    }

    public static function reorder(array $order, int $companyId, int $categoryId): void
    {
        foreach ($order as $position => $id) {
            static::where('id', $id)
                  ->where('company_id', $companyId)
                  ->where('category_id', $categoryId)
                  ->update(['sort_order' => $position]);
        }
    }

    public static function bulkUpdatePriority(array $priorities, int $companyId): void
    {
        foreach ($priorities as $id => $priority) {
            static::where('id', $id)
                  ->where('company_id', $companyId)
                  ->update(['priority' => $priority]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Priority Methods
    |--------------------------------------------------------------------------
    */

    public function increasePriority(int $amount = 1): bool
    {
        $newPriority = min(10, $this->priority + $amount);
        return $this->update(['priority' => $newPriority]);
    }

    public function decreasePriority(int $amount = 1): bool
    {
        $newPriority = max(1, $this->priority - $amount);
        return $this->update(['priority' => $newPriority]);
    }

    public function setPriority(int $priority): bool
    {
        $priority = max(1, min(10, $priority));
        return $this->update(['priority' => $priority]);
    }

    public function isHighPriority(): bool
    {
        return $this->priority >= 7;
    }

    public function isMediumPriority(): bool
    {
        return $this->priority >= 4 && $this->priority <= 6;
    }

    public function isLowPriority(): bool
    {
        return $this->priority <= 3;
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalKeywords(): int
    {
        return $this->keywords()->count();
    }

    public function getActiveKeywordsCount(): int
    {
        return $this->activeKeywords()->count();
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
    | Keyword Methods
    |--------------------------------------------------------------------------
    */

    public function hasKeywords(): bool
    {
        return $this->getTotalKeywords() > 0;
    }

    public function hasActiveKeywords(): bool
    {
        return $this->getActiveKeywordsCount() > 0;
    }

    public function getKeywordNames(): array
    {
        return $this->keywords()
                    ->pluck('keyword', 'id')
                    ->toArray();
    }

    public function getTopKeywords(int $limit = 5)
    {
        return $this->keywords()
                    ->orderBy('match_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Path Methods
    |--------------------------------------------------------------------------
    */

    public function getFullPath(): string
    {
        return $this->category->type->name . ' > ' . 
               $this->category->name . ' > ' . 
               $this->name;
    }

    public function getBreadcrumb(): array
    {
        return [
            'type' => $this->category->type->name,
            'type_id' => $this->category->type_id,
            'category' => $this->category->name,
            'category_id' => $this->category_id,
            'sub_category' => $this->name,
            'sub_category_id' => $this->id,
        ];
    }

    public function getHierarchyIds(): array
    {
        return [
            'type_id' => $this->category->type_id,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->id,
        ];
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

    public function totalKeywords(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalKeywords()
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

    public function priorityLabel(): Attribute
    {
        $labels = [
            10 => 'Critical',
            9 => 'Very High',
            8 => 'High',
            7 => 'Above Average',
            6 => 'Medium',
            5 => 'Normal',
            4 => 'Below Average',
            3 => 'Low',
            2 => 'Very Low',
            1 => 'Minimal',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->priority] ?? 'Normal'
        );
    }

    public function priorityBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->priority >= 8) return 'bg-red-100 text-red-800';
                if ($this->priority >= 6) return 'bg-yellow-100 text-yellow-800';
                if ($this->priority >= 4) return 'bg-blue-100 text-blue-800';
                return 'bg-gray-100 text-gray-800';
            }
        );
    }

    public function categoryName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->category?->name ?? 'No Category'
        );
    }

    public function typeName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->category?->type?->name ?? 'No Type'
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
            'total_keywords' => $this->getTotalKeywords(),
            'active_keywords' => $this->getActiveKeywordsCount(),
            'total_transactions' => $this->getTotalTransactions(),
            'matched_transactions' => $this->getMatchedTransactions(),
            'verified_transactions' => $this->getVerifiedTransactions(),
            'total_amount' => $this->getTotalAmount(),
            'total_debit' => $this->getTotalDebit(),
            'total_credit' => $this->getTotalCredit(),
            'matching_rate' => $this->matching_rate,
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->getTotalKeywords() === 0 && 
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
                                          ->where('category_id', $this->category_id)
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

    public function isFirst(): bool
    {
        return $this->sort_order === static::where('company_id', $this->company_id)
                                           ->where('category_id', $this->category_id)
                                           ->min('sort_order');
    }

    public function isLast(): bool
    {
        return $this->sort_order === static::where('company_id', $this->company_id)
                                           ->where('category_id', $this->category_id)
                                           ->max('sort_order');
    }

    public static function getPriorityOptions(): array
    {
        return [
            10 => 'Critical (10)',
            9 => 'Very High (9)',
            8 => 'High (8)',
            7 => 'Above Average (7)',
            6 => 'Medium (6)',
            5 => 'Normal (5)',
            4 => 'Below Average (4)',
            3 => 'Low (3)',
            2 => 'Very Low (2)',
            1 => 'Minimal (1)',
        ];
    }

    public function getMatchingScore(): int
    {
        return ($this->priority * 10) + 
               ($this->getActiveKeywordsCount() * 5) + 
               min($this->getMatchedTransactions(), 20);
    }
}