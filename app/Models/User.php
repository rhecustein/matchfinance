<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        
        // OAuth
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'provider_token_expires_at',
        'provider_data',
        
        // Profile
        'avatar',
        'phone',
        'bio',
        'timezone',
        'locale',
        
        // Role & Permissions
        'role',
        'permissions',
        
        // Account Status
        'is_active',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        
        // Security
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        
        // Login Tracking
        'last_login_at',
        'last_login_ip',
        'last_login_user_agent',
        'login_count',
        
        // Password Security
        'password_changed_at',
        'require_password_change',
        'failed_login_attempts',
        'locked_until',
        
        // Preferences
        'preferences',
        'notification_settings',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'provider_token',
        'provider_refresh_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'provider_data' => 'array',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
            'password_changed_at' => 'datetime',
            'require_password_change' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'preferences' => 'array',
            'notification_settings' => 'array',
            'provider_token_expires_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Route Key
    |--------------------------------------------------------------------------
    */

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the company that owns the user
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all bank statements uploaded by this user
     */
    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class, 'uploaded_by');
    }

    /**
     * Get all transactions verified by this user
     */
    public function verifiedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class, 'verified_by');
    }

    /**
     * Get all chat sessions created by this user
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get all company invitations sent by this user
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class, 'invited_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Role Management Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is super admin (system-wide access)
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is company owner
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user has admin level access (super_admin, owner, admin)
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, ['super_admin', 'owner', 'admin']);
    }

    /**
     * Check if user has management access (admin or manager)
     */
    public function hasManagementAccess(): bool
    {
        return in_array($this->role, ['super_admin', 'owner', 'admin', 'manager']);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check custom permissions array
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if user can access all companies (super admin only)
     */
    public function canAccessAllCompanies(): bool
    {
        return $this->isSuperAdmin() && is_null($this->company_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Account Status Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->is_suspended && !$this->isLocked();
    }

    /**
     * Check if account is suspended
     */
    public function isSuspended(): bool
    {
        return $this->is_suspended;
    }

    /**
     * Check if account is locked due to failed login attempts
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Suspend user account
     */
    public function suspend(string $reason = null): bool
    {
        return $this->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Unsuspend user account
     */
    public function unsuspend(): bool
    {
        return $this->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Lock account for X minutes
     */
    public function lockAccount(int $minutes = 30): bool
    {
        return $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock account
     */
    public function unlockAccount(): bool
    {
        return $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Login Tracking Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Record successful login
     */
    public function recordLogin(): bool
    {
        return $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'last_login_user_agent' => request()->userAgent(),
            'login_count' => $this->login_count + 1,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Record failed login attempt
     */
    public function recordFailedLogin(): bool
    {
        $attempts = $this->failed_login_attempts + 1;
        
        $data = [
            'failed_login_attempts' => $attempts,
        ];

        // Lock account after 5 failed attempts
        if ($attempts >= 5) {
            $data['locked_until'] = now()->addMinutes(30);
        }

        return $this->update($data);
    }

    /*
    |--------------------------------------------------------------------------
    | OAuth Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user uses OAuth
     */
    public function usesOAuth(): bool
    {
        return !empty($this->provider) && !empty($this->provider_id);
    }

    /**
     * Check if user has password (not OAuth-only)
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Check if OAuth token is expired
     */
    public function isOAuthTokenExpired(): bool
    {
        return $this->provider_token_expires_at && 
               $this->provider_token_expires_at->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | Security Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if 2FA is enabled and confirmed
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    /**
     * Check if password change is required
     */
    public function requiresPasswordChange(): bool
    {
        return $this->require_password_change;
    }

    /**
     * Force password change on next login
     */
    public function forcePasswordChange(): bool
    {
        return $this->update(['require_password_change' => true]);
    }

    /**
     * Record password change
     */
    public function recordPasswordChange(): bool
    {
        return $this->update([
            'password_changed_at' => now(),
            'require_password_change' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Capability Methods (Based on Role)
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user can manage master data
     */
    public function canManageMasterData(): bool
    {
        return $this->hasAdminAccess();
    }

    /**
     * Check if user can verify transactions
     */
    public function canVerifyTransactions(): bool
    {
        return $this->hasManagementAccess();
    }

    /**
     * Check if user can upload bank statements
     */
    public function canUploadBankStatements(): bool
    {
        return $this->hasManagementAccess();
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasAdminAccess();
    }

    /**
     * Check if user can manage company settings
     */
    public function canManageCompanySettings(): bool
    {
        return in_array($this->role, ['super_admin', 'owner', 'admin']);
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports(): bool
    {
        return $this->hasManagementAccess();
    }

    /**
     * Check if user can export data
     */
    public function canExportData(): bool
    {
        return $this->hasManagementAccess();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Super admins only
     */
    public function scopeSuperAdmins($query)
    {
        return $query->where('role', 'super_admin');
    }

    /**
     * Scope: Admins only
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope: Regular users
     */
    public function scopeRegularUsers($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope: Active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('is_suspended', false)
                     ->where(function($q) {
                         $q->whereNull('locked_until')
                           ->orWhere('locked_until', '<', now());
                     });
    }

    /**
     * Scope: Suspended users
     */
    public function scopeSuspended($query)
    {
        return $query->where('is_suspended', true);
    }

    /**
     * Scope: Users in specific company
     */
    public function scopeInCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Users with role
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Users with any of the given roles
     */
    public function scopeWithAnyRole($query, array $roles)
    {
        return $query->whereIn('role', $roles);
    }

    /**
     * Scope: OAuth users
     */
    public function scopeOAuthUsers($query)
    {
        return $query->whereNotNull('provider')
                     ->whereNotNull('provider_id');
    }

    /**
     * Scope: Recently logged in (last 30 days)
     */
    public function scopeRecentlyActive($query)
    {
        return $query->where('last_login_at', '>=', now()->subDays(30));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Attributes
    |--------------------------------------------------------------------------
    */

    /**
     * Get user's full name with email
     */
    public function fullNameWithEmail(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->name} ({$this->email})"
        );
    }

    /**
     * Get user's role label
     */
    public function roleLabel(): Attribute
    {
        $labels = [
            'super_admin' => 'Super Admin',
            'owner' => 'Owner',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'user' => 'User',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->role] ?? 'Unknown'
        );
    }

    /**
     * Get status badge class
     */
    public function statusBadgeClass(): Attribute
    {
        if ($this->is_suspended) {
            return Attribute::make(get: fn() => 'bg-red-100 text-red-800');
        }
        
        if ($this->isLocked()) {
            return Attribute::make(get: fn() => 'bg-orange-100 text-orange-800');
        }
        
        if ($this->is_active) {
            return Attribute::make(get: fn() => 'bg-green-100 text-green-800');
        }
        
        return Attribute::make(get: fn() => 'bg-gray-100 text-gray-800');
    }

    /**
     * Get status label
     */
    public function statusLabel(): Attribute
    {
        if ($this->is_suspended) {
            return Attribute::make(get: fn() => 'Suspended');
        }
        
        if ($this->isLocked()) {
            return Attribute::make(get: fn() => 'Locked');
        }
        
        if ($this->is_active) {
            return Attribute::make(get: fn() => 'Active');
        }
        
        return Attribute::make(get: fn() => 'Inactive');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get total bank statements uploaded by user
     */
    public function getTotalBankStatementsAttribute(): int
    {
        return $this->bankStatements()->count();
    }

    /**
     * Get total transactions verified by user
     */
    public function getTotalVerifiedTransactionsAttribute(): int
    {
        return $this->verifiedTransactions()->count();
    }

    /**
     * Get user's recent bank statements
     */
    public function getRecentBankStatements(int $limit = 5)
    {
        return $this->bankStatements()
            ->with('bank')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's recent verified transactions
     */
    public function getRecentVerifiedTransactions(int $limit = 10)
    {
        return $this->verifiedTransactions()
            ->with(['bankStatement.bank', 'subCategory.category.type'])
            ->latest('verified_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's activity summary
     */
    public function getActivitySummary(): array
    {
        return [
            'total_bank_statements' => $this->bankStatements()->count(),
            'total_verified_transactions' => $this->verifiedTransactions()->count(),
            'last_upload' => $this->bankStatements()->latest()->first()?->created_at,
            'last_verification' => $this->verifiedTransactions()->latest('verified_at')->first()?->verified_at,
            'last_login' => $this->last_login_at,
            'login_count' => $this->login_count,
        ];
    }

    /**
     * Get user's preferences with defaults
     */
    public function getPreference(string $key, $default = null)
    {
        $preferences = $this->preferences ?? [];
        return $preferences[$key] ?? $default;
    }

    /**
     * Set user preference
     */
    public function setPreference(string $key, $value): bool
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        
        return $this->update(['preferences' => $preferences]);
    }

    /**
     * Get notification setting
     */
    public function getNotificationSetting(string $key, $default = true)
    {
        $settings = $this->notification_settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Update notification setting
     */
    public function setNotificationSetting(string $key, bool $value): bool
    {
        $settings = $this->notification_settings ?? [];
        $settings[$key] = $value;
        
        return $this->update(['notification_settings' => $settings]);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            // If it's a full URL (OAuth avatar)
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            
            // If it's a local path
            return asset('storage/' . $this->avatar);
        }

        // Default avatar using UI Avatars
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . 
               "&background=random&color=fff&size=200";
    }

    /**
     * Get initials
     */
    public function getInitials(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }
}