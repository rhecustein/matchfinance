<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    // === RELATIONSHIPS ===
    
    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(CompanySubscription::class)
                    ->where('status', 'active');
    }

    public function companies()
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

    // === SCOPES ===
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_period', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_period', 'yearly');
    }

    // === HELPER METHODS ===
    
    public function getFeature($key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    public function hasFeature($key)
    {
        return isset($this->features[$key]) && $this->features[$key];
    }

    public function isUnlimited($feature)
    {
        return ($this->features[$feature] ?? 0) === -1;
    }

    public function getMonthlyPrice()
    {
        if ($this->billing_period === 'monthly') {
            return $this->price;
        }
        return $this->price / 12;
    }

    public function getYearlyPrice()
    {
        if ($this->billing_period === 'yearly') {
            return $this->price;
        }
        return $this->price * 12;
    }
}