<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class CompanyInvitation extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'invited_by',
        'email',
        'role',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invitation) {
            if (empty($invitation->uuid)) {
                $invitation->uuid = (string) Str::uuid();
            }
            
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
            
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
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

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
                     ->where('expires_at', '>', now());
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByToken($query, $token)
    {
        return $query->where('token', $token);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return is_null($this->accepted_at) && !$this->isExpired();
    }

    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && is_null($this->accepted_at);
    }

    public function isValid(): bool
    {
        return $this->isPending();
    }

    public function accept(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update(['accepted_at' => now()]);
    }

    public function cancel(): bool
    {
        return $this->delete();
    }

    public function extend(int $days = 7): bool
    {
        if ($this->isAccepted()) {
            return false;
        }

        return $this->update([
            'expires_at' => now()->addDays($days)
        ]);
    }

    public function resend(): bool
    {
        if ($this->isAccepted()) {
            return false;
        }

        return $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | URL Methods
    |--------------------------------------------------------------------------
    */

    public function getAcceptUrl(): string
    {
        return route('invitations.accept', ['token' => $this->token]);
    }

    public function getSignedAcceptUrl(): string
    {
        return URL::signedRoute('invitations.accept', [
            'token' => $this->token
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function roleLabel(): Attribute
    {
        $labels = [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->role] ?? 'Unknown'
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->isAccepted()) {
                    return 'Accepted';
                }
                if ($this->isExpired()) {
                    return 'Expired';
                }
                return 'Pending';
            }
        );
    }

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->isAccepted()) {
                    return 'bg-green-100 text-green-800';
                }
                if ($this->isExpired()) {
                    return 'bg-red-100 text-red-800';
                }
                return 'bg-yellow-100 text-yellow-800';
            }
        );
    }

    public function daysUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->isExpired() || $this->isAccepted()) {
                    return 0;
                }
                return max(0, now()->diffInDays($this->expires_at, false));
            }
        );
    }

    public function hoursUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->isExpired() || $this->isAccepted()) {
                    return 0;
                }
                return max(0, now()->diffInHours($this->expires_at, false));
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function createUser(array $additionalData = []): ?User
    {
        if (!$this->isPending()) {
            return null;
        }

        $user = User::create(array_merge([
            'company_id' => $this->company_id,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => true,
        ], $additionalData));

        $this->accept();

        return $user;
    }

    public function sendInvitationEmail()
    {
        // Send invitation email logic
        // Mail::to($this->email)->send(new CompanyInvitationMail($this));
    }

    public function sendReminderEmail()
    {
        if (!$this->isPending()) {
            return false;
        }

        // Send reminder email logic
        return true;
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }

    public static function createInvitation(
        int $companyId,
        string $email,
        string $role,
        int $invitedBy,
        int $expiryDays = 7
    ): self {
        return static::create([
            'company_id' => $companyId,
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy,
            'expires_at' => now()->addDays($expiryDays),
        ]);
    }

    public function canBeAcceptedBy(string $email): bool
    {
        return $this->isPending() && 
               strtolower($this->email) === strtolower($email);
    }

    public function getInviterName(): string
    {
        return $this->inviter?->name ?? 'Unknown';
    }

    public function getCompanyName(): string
    {
        return $this->company?->name ?? 'Unknown Company';
    }

    public function getDaysRemaining(): int
    {
        return $this->days_until_expiry;
    }

    public function isExpiringSoon(int $days = 2): bool
    {
        return $this->isPending() && 
               $this->days_until_expiry <= $days && 
               $this->days_until_expiry > 0;
    }
}