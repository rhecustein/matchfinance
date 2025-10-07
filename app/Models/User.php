<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Role Management Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is regular user
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope query to only include admins
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope query to only include regular users
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRegularUsers($query)
    {
        return $query->where('role', 'user');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all bank statements uploaded by this user
     *
     * @return HasMany
     */
    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    /**
     * Get all transactions verified by this user
     *
     * @return HasMany
     */
    public function verifiedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class, 'verified_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get total bank statements uploaded by user
     *
     * @return int
     */
    public function getTotalBankStatementsAttribute(): int
    {
        return $this->bankStatements()->count();
    }

    /**
     * Get total transactions verified by user
     *
     * @return int
     */
    public function getTotalVerifiedTransactionsAttribute(): int
    {
        return $this->verifiedTransactions()->count();
    }

    /**
     * Get user's recent bank statements
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
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
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
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
     * Check if user can manage master data (admin only)
     *
     * @return bool
     */
    public function canManageMasterData(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can verify transactions
     *
     * @return bool
     */
    public function canVerifyTransactions(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can upload bank statements
     *
     * @return bool
     */
    public function canUploadBankStatements(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can manage users (admin only)
     *
     * @return bool
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get user's activity summary
     *
     * @return array
     */
    public function getActivitySummary(): array
    {
        return [
            'total_bank_statements' => $this->bankStatements()->count(),
            'total_verified_transactions' => $this->verifiedTransactions()->count(),
            'last_upload' => $this->bankStatements()->latest()->first()?->created_at,
            'last_verification' => $this->verifiedTransactions()->latest('verified_at')->first()?->verified_at,
        ];
    }
}