<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class Account extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'code',
        'description',
        'account_type',
        'color',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
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

    public function keywords(): HasMany
    {
        return $this->hasMany(AccountKeyword::class)->orderBy('priority', 'desc');
    }

    public function activeKeywords(): HasMany
    {
        return $this->hasMany(AccountKeyword::class)
                    ->where('is_active', true)
                    ->orderBy('priority', 'desc');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class);
    }

    public function matchingLogs(): HasMany
    {
        return $this->hasMany(AccountMatchingLog::class);
    }

    public function matchedTransactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
                    ->whereNotNull('account_id');
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

    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    public function scopeForMatching($query)
    {
        return $query->where('is_active', true)
                     ->orderBy('priority', 'desc');
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeWithTransactionCount($query)
    {
        return $query->withCount('transactions');
    }

    public function scopeWithMatchedTransactionCount($query)
    {
        return $query->withCount('matchedTransactions');
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

    public function toggleActive(): bool
    {
        return $this->update(['is_active' => !$this->is_active]);
    }

    /*
    |--------------------------------------------------------------------------
    | Account Type Methods
    |--------------------------------------------------------------------------
    */

    public function isAsset(): bool
    {
        return strtolower($this->account_type) === 'asset';
    }

    public function isLiability(): bool
    {
        return strtolower($this->account_type) === 'liability';
    }

    public function isEquity(): bool
    {
        return strtolower($this->account_type) === 'equity';
    }

    public function isRevenue(): bool
    {
        return strtolower($this->account_type) === 'revenue';
    }

    public function isExpense(): bool
    {
        return strtolower($this->account_type) === 'expense';
    }

    /*
    |--------------------------------------------------------------------------
    | Keyword Management Methods
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

    public function hasKeywords(): bool
    {
        return $this->getTotalKeywords() > 0;
    }

    public function hasActiveKeywords(): bool
    {
        return $this->getActiveKeywordsCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalTransactions(): int
    {
        return $this->transactions()->count();
    }

    public function getMatchedTransactionsCount(): int
    {
        return $this->matchedTransactions()->count();
    }

    public function getTotalDebit()
    {
        return $this->transactions()->sum('debit_amount');
    }

    public function getTotalCredit()
    {
        return $this->transactions()->sum('credit_amount');
    }

    public function getTotalAmount()
    {
        return $this->transactions()->sum('amount');
    }

    public function getBalance()
    {
        $debit = $this->getTotalDebit();
        $credit = $this->getTotalCredit();
        
        return $credit - $debit;
    }

    public function getMonthlyTransactions($year, $month)
    {
        return $this->transactions()
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
                    ->get();
    }

    public function getMonthlyTotal($year, $month)
    {
        return $this->transactions()
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
                    ->sum('amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Matching Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getMatchingSuccessRate(): float
    {
        $total = $this->matchingLogs()->count();
        if ($total === 0) return 0;
        
        $matched = $this->matchingLogs()->where('is_matched', true)->count();
        return round(($matched / $total) * 100, 2);
    }

    public function getAverageConfidenceScore(): float
    {
        return round(
            $this->matchingLogs()
                 ->where('is_matched', true)
                 ->avg('confidence_score') ?? 0,
            2
        );
    }

    public function getLastMatchedAt()
    {
        return $this->matchingLogs()
                    ->where('is_matched', true)
                    ->latest('created_at')
                    ->first()
                    ?->created_at;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function formattedAccountType(): Attribute
    {
        return Attribute::make(
            get: fn() => ucwords(str_replace('_', ' ', $this->account_type ?? 'N/A'))
        );
    }

    public function accountTypeLabel(): Attribute
    {
        $labels = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expense',
        ];

        $type = strtolower($this->account_type ?? '');
        return Attribute::make(
            get: fn() => $labels[$type] ?? 'N/A'
        );
    }

    public function accountTypeBadgeClass(): Attribute
    {
        $classes = [
            'asset' => 'bg-blue-100 text-blue-800',
            'liability' => 'bg-red-100 text-red-800',
            'equity' => 'bg-purple-100 text-purple-800',
            'revenue' => 'bg-green-100 text-green-800',
            'expense' => 'bg-orange-100 text-orange-800',
        ];

        $type = strtolower($this->account_type ?? '');
        return Attribute::make(
            get: fn() => $classes[$type] ?? 'bg-gray-100 text-gray-800'
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

    public function totalTransactions(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalTransactions()
        );
    }

    public function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalAmount()
        );
    }

    public function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->total_amount, 0, ',', '.')
        );
    }

    public function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->getBalance(), 0, ',', '.')
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

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getDisplayName(): string
    {
        return $this->code ? "[$this->code] $this->name" : $this->name;
    }

    public function getFullName(): string
    {
        $parts = array_filter([
            $this->code,
            $this->name,
            $this->account_type ? "({$this->formattedAccountType})" : null,
        ]);

        return implode(' ', $parts);
    }

    public function getStatistics(): array
    {
        return [
            'total_transactions' => $this->getTotalTransactions(),
            'matched_transactions' => $this->getMatchedTransactionsCount(),
            'total_keywords' => $this->getTotalKeywords(),
            'active_keywords' => $this->getActiveKeywordsCount(),
            'total_debit' => $this->getTotalDebit(),
            'total_credit' => $this->getTotalCredit(),
            'balance' => $this->getBalance(),
            'matching_success_rate' => $this->getMatchingSuccessRate(),
            'average_confidence' => $this->getAverageConfidenceScore(),
            'last_matched_at' => $this->getLastMatchedAt(),
        ];
    }

    public function canBeDeleted(): bool
    {
        return $this->getTotalTransactions() === 0;
    }

    public function hasTransactions(): bool
    {
        return $this->getTotalTransactions() > 0;
    }

    public static function getAccountTypes(): array
    {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expense',
        ];
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

    public function getColorStyle(): string
    {
        return "background-color: {$this->color}; border-color: {$this->color};";
    }

    public function scopeOrderedForMatching($query)
    {
        return $query->where('is_active', true)
                     ->orderBy('priority', 'desc')
                     ->orderBy('name', 'asc');
    }
}