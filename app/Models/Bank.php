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

class Bank extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    public function activeBankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class)
                    ->whereNotNull('period_start')
                    ->whereNotNull('period_end');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StatementTransaction::class)
            ->whereHas('bankStatement', function ($query) {
                $query->whereNotNull('period_start')
                    ->whereNotNull('period_end');
            });
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
    | Statistics Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalStatements(): int
    {
        return $this->bankStatements()->count();
    }

    public function getTotalTransactions(): int
    {
        return $this->transactions()->count();
    }

    public function getTotalAmount()
    {
        return $this->transactions()->sum('amount');
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
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
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
                    ->whereBetween('period_start', [$startDate, $endDate])
                    ->orWhereBetween('period_end', [$startDate, $endDate])
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
                    ->sum('amount');
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
            
            $amount = $this->transactions()
                           ->whereMonth('transaction_date', $date->month)
                           ->whereYear('transaction_date', $date->year)
                           ->sum('amount');
            
            $trend[] = [
                'month' => $month,
                'transactions' => $count,
                'amount' => $amount,
            ];
        }
        
        return $trend;
    }

    public function getLastStatementDate()
    {
        return $this->bankStatements()
                    ->latest('period_end')
                    ->first()
                    ?->period_end;
    }

    public function getLastUploadDate()
    {
        return $this->bankStatements()
                    ->latest('created_at')
                    ->first()
                    ?->created_at;
    }
}