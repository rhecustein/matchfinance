<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'price',
        'billing_period',
        'features',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($plan) {
            if (empty($plan->uuid)) {
                $plan->uuid = (string) Str::uuid();
            }
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
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
    
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class)
                    ->where('status', 'active');
    }

    public function companies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Company::class,
            CompanySubscription::class,
            'plan_id',
            'id',
            'id',
            'company_id'
        );
    }

    public function activeCompanies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Company::class,
            CompanySubscription::class,
            'plan_id',
            'id',
            'id',
            'company_id'
        )->where('company_subscriptions.status', 'active');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_period', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_period', 'yearly');
    }

    public function scopePopular($query)
    {
        return $query->withCount('activeSubscriptions')
                     ->orderBy('active_subscriptions_count', 'desc');
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /*
    |--------------------------------------------------------------------------
    | Feature Methods
    |--------------------------------------------------------------------------
    */
    
    public function getFeature($key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    public function hasFeature($key): bool
    {
        return isset($this->features[$key]) && $this->features[$key];
    }

    public function isUnlimited($feature): bool
    {
        return ($this->features[$feature] ?? 0) === -1;
    }

    public function getFeatureLimit($key, $default = 0)
    {
        $value = $this->getFeature($key, $default);
        return $value === -1 ? PHP_INT_MAX : $value;
    }

    public function canAddMore($feature, $currentCount): bool
    {
        if ($this->isUnlimited($feature)) {
            return true;
        }

        $limit = $this->getFeature($feature, 0);
        return $currentCount < $limit;
    }

    /*
    |--------------------------------------------------------------------------
    | Price Calculation Methods
    |--------------------------------------------------------------------------
    */

    public function getMonthlyPrice()
    {
        if ($this->billing_period === 'monthly') {
            return $this->price;
        }
        return round($this->price / 12, 2);
    }

    public function getYearlyPrice()
    {
        if ($this->billing_period === 'yearly') {
            return $this->price;
        }
        return $this->price * 12;
    }

    public function getSavingsAmount()
    {
        if ($this->billing_period === 'yearly') {
            $monthlyEquivalent = $this->getMonthlyPrice() * 12;
            return $monthlyEquivalent - $this->price;
        }
        return 0;
    }

    public function getSavingsPercentage()
    {
        if ($this->billing_period === 'yearly') {
            $monthlyEquivalent = $this->getMonthlyPrice() * 12;
            if ($monthlyEquivalent > 0) {
                return round((($monthlyEquivalent - $this->price) / $monthlyEquivalent) * 100, 0);
            }
        }
        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function toggleStatus(): bool
    {
        return $this->update(['is_active' => !$this->is_active]);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalSubscriptions(): int
    {
        return $this->subscriptions()->count();
    }

    public function getActiveSubscriptionsCount(): int
    {
        return $this->activeSubscriptions()->count();
    }

    public function getTotalRevenue()
    {
        return $this->activeSubscriptions()
                    ->sum('amount') ?? 0;
    }

    public function getMonthlyRecurringRevenue()
    {
        $total = $this->activeSubscriptions()
                      ->where('billing_period', 'monthly')
                      ->sum('amount') ?? 0;

        $yearlyTotal = $this->activeSubscriptions()
                            ->where('billing_period', 'yearly')
                            ->sum('amount') ?? 0;

        return $total + ($yearlyTotal / 12);
    }

    /*
    |--------------------------------------------------------------------------
    | Comparison Methods
    |--------------------------------------------------------------------------
    */

    public function isCheaperThan(Plan $plan): bool
    {
        $thisMonthly = $this->getMonthlyPrice();
        $otherMonthly = $plan->getMonthlyPrice();
        
        return $thisMonthly < $otherMonthly;
    }

    public function hasMoreFeaturesThan(Plan $plan): bool
    {
        $thisCount = count($this->features ?? []);
        $otherCount = count($plan->features ?? []);
        
        return $thisCount > $otherCount;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->price, 0, ',', '.')
        );
    }

    public function billingPeriodLabel(): Attribute
    {
        $labels = [
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->billing_period] ?? 'Unknown'
        );
    }

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_active 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_active ? 'Active' : 'Inactive'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Feature List Helpers
    |--------------------------------------------------------------------------
    */

    public function getFeaturesList(): array
    {
        $features = $this->features ?? [];
        $formatted = [];

        $labels = [
            'max_users' => 'Maximum Users',
            'max_bank_statements' => 'Bank Statements per Month',
            'max_transactions' => 'Transactions per Month',
            'max_keywords' => 'Maximum Keywords',
            'max_accounts' => 'Maximum Accounts',
            'max_storage_mb' => 'Storage Space (MB)',
            'ai_matching' => 'AI-Powered Matching',
            'auto_categorization' => 'Auto Categorization',
            'advanced_reports' => 'Advanced Reports',
            'api_access' => 'API Access',
            'priority_support' => 'Priority Support',
            'custom_branding' => 'Custom Branding',
            'export_formats' => 'Export Formats',
            'multi_company' => 'Multi-Company',
        ];

        foreach ($features as $key => $value) {
            $label = $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
            
            if ($value === true || $value === 1) {
                $formatted[] = $label;
            } elseif ($value === -1) {
                $formatted[] = $label . ' (Unlimited)';
            } elseif (is_numeric($value)) {
                $formatted[] = $label . ': ' . $value;
            } else {
                $formatted[] = $label . ': ' . $value;
            }
        }

        return $formatted;
    }

    public function getFeaturesForDisplay(): array
    {
        $features = $this->features ?? [];
        $display = [];

        foreach ($features as $key => $value) {
            $display[] = [
                'key' => $key,
                'value' => $value,
                'formatted' => $this->formatFeatureValue($key, $value),
                'unlimited' => $value === -1,
                'enabled' => in_array($value, [true, 1, -1], true),
            ];
        }

        return $display;
    }

    private function formatFeatureValue($key, $value)
    {
        if ($value === true || $value === 1) {
            return '✓ Included';
        }
        
        if ($value === false || $value === 0) {
            return '✗ Not included';
        }
        
        if ($value === -1) {
            return '∞ Unlimited';
        }
        
        if (is_numeric($value)) {
            if (str_contains($key, 'storage')) {
                return $value . ' MB';
            }
            return number_format($value);
        }
        
        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    public function isPremium(): bool
    {
        return $this->price > 0;
    }

    public function getDuration(): string
    {
        return $this->billing_period === 'monthly' ? '1 month' : '1 year';
    }

    public function getDurationInDays(): int
    {
        return $this->billing_period === 'monthly' ? 30 : 365;
    }

    public function getDurationInMonths(): int
    {
        return $this->billing_period === 'monthly' ? 1 : 12;
    }

    public function canBeDeleted(): bool
    {
        return $this->activeSubscriptions()->count() === 0;
    }

    public function getRecommendationScore(): int
    {
        $score = 0;
        
        // Base score from active subscriptions
        $score += min($this->getActiveSubscriptionsCount() * 10, 50);
        
        // Feature richness
        $featureCount = count($this->features ?? []);
        $score += min($featureCount * 5, 30);
        
        // Price competitiveness (inverse scoring)
        $monthlyPrice = $this->getMonthlyPrice();
        if ($monthlyPrice < 100000) $score += 20;
        elseif ($monthlyPrice < 500000) $score += 10;
        
        return min($score, 100);
    }
}