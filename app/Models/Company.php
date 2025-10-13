<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'admin');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class)->latest();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class)
                    ->where('status', 'active')
                    ->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now());
    }

    // Financial Data
    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class);
    }

    public function types(): HasMany
    {
        return $this->hasMany(Type::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeBySubdomain($query, $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('subscription', function($q) {
            $q->where('status', 'active');
        });
    }

    public function scopeTrialExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'trial')
                     ->whereBetween('trial_ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeTrialExpired($query)
    {
        return $query->where('status', 'trial')
                     ->where('trial_ends_at', '<', now());
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isPast();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    public function startTrial(int $days = 14): bool
    {
        return $this->update([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    public function extendTrial(int $days): bool
    {
        $currentEnd = $this->trial_ends_at ?? now();
        return $this->update([
            'trial_ends_at' => $currentEnd->addDays($days),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription Methods
    |--------------------------------------------------------------------------
    */

    public function hasActiveSubscription(): bool
    {
        return $this->subscription && $this->subscription->status === 'active';
    }

    public function hasSubscription(): bool
    {
        return $this->subscription !== null;
    }

    public function getCurrentPlan(): ?Plan
    {
        return $this->subscription?->plan;
    }

    public function isSubscribedTo($planId): bool
    {
        return $this->subscription && $this->subscription->plan_id == $planId;
    }

    public function subscriptionEndsAt()
    {
        return $this->subscription?->ends_at;
    }

    public function daysUntilSubscriptionEnds(): ?int
    {
        $endsAt = $this->subscriptionEndsAt();
        return $endsAt ? now()->diffInDays($endsAt, false) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Feature & Limit Methods
    |--------------------------------------------------------------------------
    */

    public function hasFeature($feature): bool
    {
        $plan = $this->getCurrentPlan();
        return $plan && $plan->hasFeature($feature);
    }

    public function getFeatureLimit($feature, $default = 0)
    {
        $plan = $this->getCurrentPlan();
        return $plan ? $plan->getFeatureLimit($feature, $default) : $default;
    }

    public function canAddUser(): bool
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) return false;

        if ($plan->isUnlimited('max_users')) {
            return true;
        }

        $maxUsers = $plan->getFeature('max_users', 0);
        return $this->users()->count() < $maxUsers;
    }

    public function canAddBankStatement(): bool
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) return false;

        if ($plan->isUnlimited('max_bank_statements')) {
            return true;
        }

        $maxStatements = $plan->getFeature('max_bank_statements', 0);
        $currentMonth = $this->bankStatements()
                             ->whereMonth('created_at', now()->month)
                             ->count();
        
        return $currentMonth < $maxStatements;
    }

    public function canAddKeyword(): bool
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) return false;

        if ($plan->isUnlimited('max_keywords')) {
            return true;
        }

        $maxKeywords = $plan->getFeature('max_keywords', 0);
        return $this->keywords()->count() < $maxKeywords;
    }

    public function canAddAccount(): bool
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) return false;

        if ($plan->isUnlimited('max_accounts')) {
            return true;
        }

        $maxAccounts = $plan->getFeature('max_accounts', 0);
        return $this->accounts()->count() < $maxAccounts;
    }

    public function getRemainingUsers(): int
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) return 0;

        if ($plan->isUnlimited('max_users')) {
            return PHP_INT_MAX;
        }

        $max = $plan->getFeature('max_users', 0);
        $current = $this->users()->count();
        return max(0, $max - $current);
    }

    /*
    |--------------------------------------------------------------------------
    | User Management Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalUsers(): int
    {
        return $this->users()->count();
    }

    public function getActiveUsersCount(): int
    {
        return $this->activeUsers()->count();
    }

    public function hasOwner(): bool
    {
        return $this->owner !== null;
    }

    public function getAdminsCount(): int
    {
        return $this->admins()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Settings Methods
    |--------------------------------------------------------------------------
    */

    public function getSetting($key, $default = null)
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    public function setSetting($key, $value): bool
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        return $this->update(['settings' => $settings]);
    }

    public function setSettings(array $settings): bool
    {
        return $this->update(['settings' => array_merge($this->settings ?? [], $settings)]);
    }

    /*
    |--------------------------------------------------------------------------
    | URL & Domain Methods
    |--------------------------------------------------------------------------
    */

    public function url(): string
    {
        if ($this->domain) {
            return "https://{$this->domain}";
        }
        return "https://{$this->subdomain}." . config('app.domain');
    }

    public function getLoginUrl(): string
    {
        return $this->url() . '/login';
    }

    public function getDashboardUrl(): string
    {
        return $this->url() . '/dashboard';
    }

    public function hasCustomDomain(): bool
    {
        return !empty($this->domain);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getStatistics(): array
    {
        return [
            'total_users' => $this->getTotalUsers(),
            'active_users' => $this->getActiveUsersCount(),
            'total_bank_statements' => $this->bankStatements()->count(),
            'total_transactions' => $this->transactions()->count(),
            'matched_transactions' => $this->transactions()->whereNotNull('sub_category_id')->count(),
            'verified_transactions' => $this->transactions()->where('is_verified', true)->count(),
            'total_keywords' => $this->keywords()->count(),
            'total_accounts' => $this->accounts()->count(),
            'total_categories' => $this->categories()->count(),
        ];
    }

    public function getMonthlyStatistics(): array
    {
        $startOfMonth = now()->startOfMonth();

        return [
            'statements_this_month' => $this->bankStatements()->where('created_at', '>=', $startOfMonth)->count(),
            'transactions_this_month' => $this->transactions()->where('created_at', '>=', $startOfMonth)->count(),
            'verified_this_month' => $this->transactions()->where('verified_at', '>=', $startOfMonth)->count(),
        ];
    }

    public function getUsagePercentages(): array
    {
        $plan = $this->getCurrentPlan();
        if (!$plan) {
            return [];
        }

        $usage = [];

        // Users
        if (!$plan->isUnlimited('max_users')) {
            $max = $plan->getFeature('max_users', 0);
            $current = $this->getTotalUsers();
            $usage['users'] = $max > 0 ? round(($current / $max) * 100, 1) : 0;
        }

        // Keywords
        if (!$plan->isUnlimited('max_keywords')) {
            $max = $plan->getFeature('max_keywords', 0);
            $current = $this->keywords()->count();
            $usage['keywords'] = $max > 0 ? round(($current / $max) * 100, 1) : 0;
        }

        // Accounts
        if (!$plan->isUnlimited('max_accounts')) {
            $max = $plan->getFeature('max_accounts', 0);
            $current = $this->accounts()->count();
            $usage['accounts'] = $max > 0 ? round(($current / $max) * 100, 1) : 0;
        }

        return $usage;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function statusLabel(): Attribute
    {
        $labels = [
            'active' => 'Active',
            'trial' => 'Trial',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->status] ?? 'Unknown'
        );
    }

    public function statusBadgeClass(): Attribute
    {
        $classes = [
            'active' => 'bg-green-100 text-green-800',
            'trial' => 'bg-blue-100 text-blue-800',
            'suspended' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ];

        return Attribute::make(
            get: fn() => $classes[$this->status] ?? 'bg-gray-100 text-gray-800'
        );
    }

    public function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->logo) {
                    if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
                        return $this->logo;
                    }
                    return asset('storage/' . $this->logo);
                }
                
                return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . 
                       "&background=4f46e5&color=fff&size=200";
            }
        );
    }

    public function trialDaysRemaining(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->isTrial() || !$this->trial_ends_at) {
                    return 0;
                }
                return max(0, now()->diffInDays($this->trial_ends_at, false));
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function sendWelcomeEmail()
    {
        // Send welcome email logic
    }

    public function sendTrialExpiringEmail()
    {
        // Send trial expiring email logic
    }

    public function notifyOwner($notification)
    {
        if ($owner = $this->owner) {
            $owner->notify($notification);
        }
    }

    public function notifyAdmins($notification)
    {
        $this->admins()->each(function($admin) use ($notification) {
            $admin->notify($notification);
        });
    }
}