<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class CompanySubscription extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($subscription) {
            if (empty($subscription->uuid)) {
                $subscription->uuid = (string) Str::uuid();
            }
            
            if (empty($subscription->starts_at)) {
                $subscription->starts_at = now();
            }
        });

        static::created(function ($subscription) {
            $subscription->calculateEndDate();
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForPlan($query, $planId)
    {
        return $query->where('plan_id', $planId);
    }

    public function scopeEndingSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
                     ->whereNotNull('ends_at')
                     ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpiredRecently($query, $days = 30)
    {
        return $query->where('status', 'expired')
                     ->where('ends_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->ends_at && $this->ends_at->isPast());
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function isEndingSoon(int $days = 30): bool
    {
        return $this->isActive() && 
               $this->ends_at && 
               $this->ends_at->lte(now()->addDays($days));
    }

    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | Action Methods
    |--------------------------------------------------------------------------
    */

    public function activate(): bool
    {
        return $this->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function markAsExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    public function markAsPastDue(): bool
    {
        return $this->update(['status' => 'past_due']);
    }

    public function renew(int $duration = null): bool
    {
        $plan = $this->plan;
        
        if (!$duration) {
            $duration = $plan->billing_period === 'monthly' ? 1 : 12;
        }

        $startsAt = $this->ends_at && $this->ends_at->isFuture() 
            ? $this->ends_at 
            : now();

        return $this->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonths($duration),
            'cancelled_at' => null,
        ]);
    }

    public function extend(int $months): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $currentEnd = $this->ends_at ?? now();
        
        return $this->update([
            'ends_at' => $currentEnd->copy()->addMonths($months),
        ]);
    }

    public function switchPlan(Plan $newPlan): bool
    {
        return $this->update([
            'plan_id' => $newPlan->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Date Calculation Methods
    |--------------------------------------------------------------------------
    */

    public function calculateEndDate(): bool
    {
        if ($this->ends_at) {
            return true;
        }

        $plan = $this->plan;
        $months = $plan->billing_period === 'monthly' ? 1 : 12;
        
        return $this->update([
            'ends_at' => $this->starts_at->copy()->addMonths($months),
        ]);
    }

    public function getDurationInMonths(): int
    {
        return $this->plan->billing_period === 'monthly' ? 1 : 12;
    }

    public function getDurationInDays(): int
    {
        if (!$this->ends_at) {
            return 0;
        }
        
        return $this->starts_at->diffInDays($this->ends_at);
    }

    public function getDaysRemaining(): int
    {
        if (!$this->ends_at || $this->isExpired()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function getDaysActive(): int
    {
        return $this->starts_at->diffInDays(now());
    }

    public function getProgressPercentage(): float
    {
        if (!$this->ends_at) {
            return 0;
        }

        $total = $this->starts_at->diffInDays($this->ends_at);
        $elapsed = $this->starts_at->diffInDays(now());
        
        return $total > 0 ? min(100, round(($elapsed / $total) * 100, 1)) : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Feature Access Methods
    |--------------------------------------------------------------------------
    */

    public function hasFeature($feature): bool
    {
        return $this->isActive() && $this->plan->hasFeature($feature);
    }

    public function getFeatureLimit($feature, $default = 0)
    {
        if (!$this->isActive()) {
            return 0;
        }
        
        return $this->plan->getFeatureLimit($feature, $default);
    }

    public function isUnlimited($feature): bool
    {
        return $this->isActive() && $this->plan->isUnlimited($feature);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    */

    public function getAmount()
    {
        return $this->plan->price;
    }

    public function getMonthlyAmount()
    {
        return $this->plan->getMonthlyPrice();
    }

    public function getTotalAmount()
    {
        return $this->plan->price;
    }

    public function getNextBillingDate()
    {
        return $this->ends_at;
    }

    public function getBillingPeriod(): string
    {
        return $this->plan->billing_period;
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
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            'past_due' => 'Past Due',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->status] ?? 'Unknown'
        );
    }

    public function statusBadgeClass(): Attribute
    {
        $classes = [
            'active' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            'expired' => 'bg-red-100 text-red-800',
            'past_due' => 'bg-yellow-100 text-yellow-800',
        ];

        return Attribute::make(
            get: fn() => $classes[$this->status] ?? 'bg-gray-100 text-gray-800'
        );
    }

    public function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getDaysRemaining()
        );
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->getAmount(), 0, ',', '.')
        );
    }

    public function planName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->plan?->name ?? 'No Plan'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function needsRenewal(int $days = 7): bool
    {
        return $this->isActive() && 
               $this->ends_at && 
               $this->ends_at->lte(now()->addDays($days));
    }

    public function canBeRenewed(): bool
    {
        return in_array($this->status, ['active', 'expired', 'past_due']);
    }

    public function canBeCancelled(): bool
    {
        return $this->status === 'active';
    }

    public function canBeReactivated(): bool
    {
        return in_array($this->status, ['cancelled', 'expired']);
    }

    public function getSubscriptionPeriod(): string
    {
        $start = $this->starts_at->format('M d, Y');
        $end = $this->ends_at ? $this->ends_at->format('M d, Y') : 'Ongoing';
        
        return "{$start} - {$end}";
    }

    public function getInfo(): array
    {
        return [
            'plan_name' => $this->plan->name,
            'status' => $this->status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'days_remaining' => $this->getDaysRemaining(),
            'is_active' => $this->isActive(),
            'amount' => $this->getAmount(),
            'billing_period' => $this->getBillingPeriod(),
        ];
    }

    public static function createSubscription(
        int $companyId,
        int $planId,
        ?string $startsAt = null
    ): self {
        return static::create([
            'company_id' => $companyId,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => $startsAt ?? now(),
        ]);
    }

    public function sendRenewalReminder()
    {
        // Send renewal reminder email
    }

    public function sendCancellationConfirmation()
    {
        // Send cancellation confirmation email
    }

    public function sendExpiryNotification()
    {
        // Send expiry notification email
    }
}