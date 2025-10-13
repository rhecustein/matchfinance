<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'domain',
        'subdomain',
        'status',
        'logo',
        'settings',
        'trial_ends_at'
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($company) {
            if (empty($company->uuid)) {
                $company->uuid = (string) Str::uuid();
            }
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
            if (empty($company->subdomain)) {
                $company->subdomain = $company->slug;
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function subscription()
    {
        return $this->hasOne(CompanySubscription::class)->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function invitations()
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySubdomain($query, $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    // Helper Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isTrial()
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function hasActiveSubscription()
    {
        return $this->subscription && $this->subscription->status === 'active';
    }

    public function canAddUser()
    {
        $subscription = $this->subscription;
        if (!$subscription) return false;

        $maxUsers = $subscription->plan->features['max_users'] ?? 0;
        if ($maxUsers === -1) return true; // unlimited

        return $this->users()->count() < $maxUsers;
    }

    public function url()
    {
        if ($this->domain) {
            return "https://{$this->domain}";
        }
        return "https://{$this->subdomain}." . config('app.domain');
    }
}