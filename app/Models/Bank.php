<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Bank extends Model
{
    use HasFactory, SoftDeletes;
    // ❌ HAPUS: BelongsToTenant (Bank adalah GLOBAL, bukan per-tenant)

    protected $fillable = [
        'uuid',
        // ❌ HAPUS: 'company_id' (Bank tidak punya company_id)
        'code',
        'slug',
        'name',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($bank) {
            if (empty($bank->uuid)) {
                $bank->uuid = (string) Str::uuid();
            }
            
            if (empty($bank->slug)) {
                $bank->slug = Str::slug($bank->name);
            }
        });
        
        static::updating(function ($bank) {
            if ($bank->isDirty('name') && empty($bank->slug)) {
                $bank->slug = Str::slug($bank->name);
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

    /**
     * ❌ HAPUS: company() relationship
     * Bank tidak belongs to company, bank adalah GLOBAL
     */
    // public function company(): BelongsTo
    // {
    //     return $this->belongsTo(Company::class);
    // }

    /**
     * ✅ Bank Statements (dari berbagai companies)
     */
    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    /**
     * ✅ Get statements untuk company tertentu
     */
    public function bankStatementsForCompany($companyId): HasMany
    {
        return $this->hasMany(BankStatement::class)->where('company_id', $companyId);
    }

    /**
     * ✅ Active statements
     */
    public function activeBankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class)
                    ->whereNotNull('period_from')
                    ->whereNotNull('period_to');
    }

    /**
     * ✅ Transactions through bank statements
     */
    public function transactions(): HasMany
    {
        return $this->hasManyThrough(
            StatementTransaction::class,
            BankStatement::class,
            'bank_id', // Foreign key on bank_statements table
            'bank_statement_id', // Foreign key on statement_transactions table
            'id', // Local key on banks table
            'id' // Local key on bank_statements table
        );
    }

    /**
     * ✅ Get transactions untuk company tertentu
     */
    public function transactionsForCompany($companyId)
    {
        return $this->hasManyThrough(
            StatementTransaction::class,
            BankStatement::class,
            'bank_id',
            'bank_statement_id',
            'id',
            'id'
        )->where('bank_statements.company_id', $companyId);
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

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    public function scopeWithStatementCount($query)
    {
        return $query->withCount('bankStatements');
    }

    public function scopeWithTransactionCount($query)
    {
        return $query->withCount('transactions');
    }

    public function scopePopular($query)
    {
        return $query->withCount('bankStatements')
                     ->orderBy('bank_statements_count', 'desc');
    }

    /**
     * ✅ Scope untuk company tertentu
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->whereHas('bankStatements', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
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
    | Statistics Methods (Updated untuk multi-tenant)
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Get total statements (all companies)
     */
    public function getTotalStatements(): int
    {
        return $this->bankStatements()->count();
    }

    /**
     * ✅ Get total statements untuk company tertentu
     */
    public function getTotalStatementsForCompany($companyId): int
    {
        return $this->bankStatements()->where('company_id', $companyId)->count();
    }

    /**
     * ✅ Get total transactions (all companies)
     */
    public function getTotalTransactions(): int
    {
        return $this->transactions()->count();
    }

    /**
     * ✅ Get total transactions untuk company tertentu
     */
    public function getTotalTransactionsForCompany($companyId): int
    {
        return $this->transactionsForCompany($companyId)->count();
    }

    public function getTotalAmount()
    {
        return $this->transactions()
            ->sum(DB::raw('COALESCE(debit_amount, 0) + COALESCE(credit_amount, 0)'));
    }

    public function getTotalDebit()
    {
        return $this->transactions()->sum('debit_amount');
    }

    public function getTotalCredit()
    {
        return $this->transactions()->sum('credit_amount');
    }

    public function getProcessedStatementsCount(): int
    {
        return $this->bankStatements()
                    ->where('total_transactions', '>', 0)
                    ->count();
    }

    public function getUnprocessedStatementsCount(): int
    {
        return $this->bankStatements()
                    ->where('total_transactions', 0)
                    ->count();
    }

    public function getRecentStatements(int $limit = 5)
    {
        return $this->bankStatements()
                    ->latest()
                    ->limit($limit)
                    ->get();
    }

    public function getStatementsThisMonth(): int
    {
        return $this->bankStatements()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
    }

    public function getTransactionsThisMonth(): int
    {
        return $this->transactions()
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Period Methods
    |--------------------------------------------------------------------------
    */

    public function getStatementsByPeriod($startDate, $endDate)
    {
        return $this->bankStatements()
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('period_from', [$startDate, $endDate])
                          ->orWhereBetween('period_to', [$startDate, $endDate]);
                    })
                    ->get();
    }

    public function getTransactionsByPeriod($startDate, $endDate)
    {
        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();
    }

    public function getAmountByPeriod($startDate, $endDate)
    {
        return $this->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->sum(DB::raw('COALESCE(debit_amount, 0) + COALESCE(credit_amount, 0)'));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

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
                       "&background=random&color=fff&size=200";
            }
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

    public function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->getTotalAmount(), 0, ',', '.')
        );
    }

    public function totalStatements(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalStatements()
        );
    }

    public function totalTransactions(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTotalTransactions()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Get statistics (all companies)
     */
    public function getStatistics(): array
    {
        return [
            'total_statements' => $this->getTotalStatements(),
            'processed_statements' => $this->getProcessedStatementsCount(),
            'unprocessed_statements' => $this->getUnprocessedStatementsCount(),
            'total_transactions' => $this->getTotalTransactions(),
            'total_amount' => $this->getTotalAmount(),
            'total_debit' => $this->getTotalDebit(),
            'total_credit' => $this->getTotalCredit(),
            'statements_this_month' => $this->getStatementsThisMonth(),
            'transactions_this_month' => $this->getTransactionsThisMonth(),
        ];
    }

    /**
     * ✅ Get statistics untuk company tertentu
     */
    public function getStatisticsForCompany($companyId): array
    {
        return [
            'total_statements' => $this->getTotalStatementsForCompany($companyId),
            'total_transactions' => $this->getTotalTransactionsForCompany($companyId),
            'total_debit' => $this->transactionsForCompany($companyId)->sum('debit_amount'),
            'total_credit' => $this->transactionsForCompany($companyId)->sum('credit_amount'),
        ];
    }

    public function hasStatements(): bool
    {
        return $this->getTotalStatements() > 0;
    }

    public function hasTransactions(): bool
    {
        return $this->getTotalTransactions() > 0;
    }

    public function canBeDeleted(): bool
    {
        return $this->getTotalStatements() === 0;
    }

    public function getDisplayName(): string
    {
        return $this->code ? "[{$this->code}] {$this->name}" : $this->name;
    }

    public static function getBankCodes(): array
    {
        return static::pluck('name', 'code')->toArray();
    }

    public static function getActiveBanks()
    {
        return static::active()->orderBy('name')->get();
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public function getMonthlyTrend(int $months = 6): array
    {
        $trend = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('M Y');
            
            $count = $this->transactions()
                          ->whereMonth('transaction_date', $date->month)
                          ->whereYear('transaction_date', $date->year)
                          ->count();
            
            $debit = $this->transactions()
                          ->whereMonth('transaction_date', $date->month)
                          ->whereYear('transaction_date', $date->year)
                          ->sum('debit_amount');
            
            $credit = $this->transactions()
                           ->whereMonth('transaction_date', $date->month)
                           ->whereYear('transaction_date', $date->year)
                           ->sum('credit_amount');
            
            $trend[] = [
                'month' => $month,
                'transactions' => $count,
                'debit' => $debit,
                'credit' => $credit,
                'total' => $debit + $credit,
            ];
        }
        
        return $trend;
    }

    public function getLastStatementDate()
    {
        return $this->bankStatements()
                    ->latest('period_to')
                    ->first()
                    ?->period_to;
    }

    public function getLastUploadDate()
    {
        return $this->bankStatements()
                    ->latest('created_at')
                    ->first()
                    ?->created_at;
    }

    /**
     * ✅ Check if bank is used by specific company
     */
    public function isUsedByCompany($companyId): bool
    {
        return $this->bankStatements()->where('company_id', $companyId)->exists();
    }

    /**
     * ✅ Get companies yang menggunakan bank ini
     */
    public function getCompanies()
    {
        return Company::whereHas('bankStatements', function($q) {
            $q->where('bank_id', $this->id);
        })->get();
    }

    /**
     * ✅ Count companies yang menggunakan bank ini
     */
    public function getCompaniesCount(): int
    {
        return Company::whereHas('bankStatements', function($q) {
            $q->where('bank_id', $this->id);
        })->count();
    }
}