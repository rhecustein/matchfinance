<?php

namespace App\Http\Controllers;

use App\Models\StatementTransaction;
use App\Models\Bank;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use App\Models\Company;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Helper: Get current user's company_id or null for super_admin
     */
    private function getCompanyId()
    {
        $user = auth()->user();
        
        // Super admin bisa akses semua company
        if ($user->isSuperAdmin()) {
            return null;
        }
        
        return $user->company_id;
    }

    /**
     * Helper: Check if user is super admin
     */
    private function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    /**
     * Helper: Apply company filter to query
     */
    private function applyCompanyFilter($query, $companyId = null)
    {
        // Jika super admin dan tidak memilih company tertentu, tampilkan semua
        if ($this->isSuperAdmin() && is_null($companyId)) {
            return $query;
        }

        // Jika super admin dan memilih company tertentu
        if ($this->isSuperAdmin() && !is_null($companyId)) {
            return $query->where('company_id', $companyId);
        }

        // User biasa hanya lihat company mereka sendiri
        return $query->where('company_id', $this->getCompanyId());
    }

    /**
     * Helper: Apply date range filter
     */
    private function applyDateRangeFilter($query, $request)
    {
        // Jika ada start_date dan end_date, gunakan itu
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            
            return $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }
        
        // Jika hanya ada year, filter berdasarkan tahun
        if ($request->filled('year')) {
            return $query->whereYear('transaction_date', $request->year);
        }
        
        return $query;
    }

    /**
     * Helper: Get date range info for display
     */
    private function getDateRangeInfo($request)
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return [
                'type' => 'custom',
                'start' => Carbon::parse($request->start_date),
                'end' => Carbon::parse($request->end_date),
                'label' => Carbon::parse($request->start_date)->format('d M Y') . ' - ' . 
                          Carbon::parse($request->end_date)->format('d M Y')
            ];
        }
        
        if ($request->filled('year')) {
            return [
                'type' => 'year',
                'year' => $request->year,
                'start' => Carbon::create($request->year, 1, 1),
                'end' => Carbon::create($request->year, 12, 31),
                'label' => 'Year ' . $request->year
            ];
        }
        
        // Default: current year
        $currentYear = now()->year;
        return [
            'type' => 'year',
            'year' => $currentYear,
            'start' => Carbon::create($currentYear, 1, 1),
            'end' => Carbon::create($currentYear, 12, 31),
            'label' => 'Year ' . $currentYear
        ];
    }

    /**
     * Display report index/dashboard
     */
    public function index()
    {
        $companyId = $this->getCompanyId();
        
        // Get available banks yang punya transaksi (with company filter)
        $availableBanks = Bank::whereHas('bankStatements.transactions', function($query) use ($companyId) {
            if (!$this->isSuperAdmin()) {
                $query->where('statement_transactions.company_id', $companyId);
            }
        })->get();
        
        // Get years from transactions (with company filter)
        $transactionYearsQuery = StatementTransaction::selectRaw('YEAR(transaction_date) as year');
        $transactionYearsQuery = $this->applyCompanyFilter($transactionYearsQuery, $companyId);
        $transactionYears = $transactionYearsQuery
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Generate year range from 2015 to 2027
        $yearRange = range(2027, 2015);
        $availableYears = collect($yearRange);

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        // Get all account types
        $accountTypes = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expense',
        ];

        return view('reports.index', compact(
            'availableBanks', 
            'availableYears', 
            'transactionYears',
            'companies',
            'accountTypes'
        ));
    }

    /**
     * 1. LAPORAN BULANAN PER BANK
     * Horizontal: Bank | Vertical: Bulan
     */
    public function monthlyByBank(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        // Get date range info
        $dateRange = $this->getDateRangeInfo($request);

        // Get all banks yang punya transaksi (with company filter)
        $banksQuery = Bank::whereHas('bankStatements.transactions', function($query) use ($request, $companyId) {
            $this->applyDateRangeFilter($query, $request);
            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }
        })->orderBy('name');
        
        $banks = $banksQuery->get();

        // Generate months based on date range
        $months = [];
        
        if ($dateRange['type'] === 'year') {
            // Generate 12 months untuk year-based
            for ($month = 1; $month <= 12; $month++) {
                $monthStart = Carbon::create($dateRange['year'], $month, 1)->startOfMonth();
                $monthEnd = Carbon::create($dateRange['year'], $month, 1)->endOfMonth();
                $monthName = $monthStart->format('M Y');
                
                $monthData = [
                    'month' => $monthName,
                    'month_number' => $month,
                    'start_date' => $monthStart,
                    'end_date' => $monthEnd,
                    'banks' => [],
                    'total' => 0,
                    'count' => 0,
                ];

                // Data per bank untuk bulan ini
                foreach ($banks as $bank) {
                    $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                        $q->where('bank_id', $bank->id);
                    })
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

                    // Apply company filter
                    if (!is_null($companyId)) {
                        $query->where('statement_transactions.company_id', $companyId);
                    }

                    // Filter transaction type
                    if ($transactionType !== 'all') {
                        $query->where('transaction_type', $transactionType);
                    }

                    $result = $query->selectRaw('
                        COALESCE(SUM(amount), 0) as total,
                        COUNT(*) as count
                    ')->first();

                    $total = $result->total ?? 0;
                    $count = $result->count ?? 0;

                    $monthData['banks'][$bank->id] = [
                        'total' => $total,
                        'count' => $count,
                    ];

                    $monthData['total'] += $total;
                    $monthData['count'] += $count;
                }

                $months[] = $monthData;
            }
        } else {
            // Custom date range: generate months between start and end
            $currentDate = $dateRange['start']->copy()->startOfMonth();
            $endDate = $dateRange['end']->copy()->endOfMonth();
            
            while ($currentDate <= $endDate) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();
                
                // Adjust untuk first dan last month
                if ($monthStart < $dateRange['start']) {
                    $monthStart = $dateRange['start']->copy();
                }
                if ($monthEnd > $dateRange['end']) {
                    $monthEnd = $dateRange['end']->copy();
                }
                
                $monthName = $monthStart->format('M Y');
                
                $monthData = [
                    'month' => $monthName,
                    'month_number' => $monthStart->month,
                    'start_date' => $monthStart,
                    'end_date' => $monthEnd,
                    'banks' => [],
                    'total' => 0,
                    'count' => 0,
                ];

                foreach ($banks as $bank) {
                    $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                        $q->where('bank_id', $bank->id);
                    })
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

                    if (!is_null($companyId)) {
                        $query->where('statement_transactions.company_id', $companyId);
                    }

                    if ($transactionType !== 'all') {
                        $query->where('transaction_type', $transactionType);
                    }

                    $result = $query->selectRaw('
                        COALESCE(SUM(amount), 0) as total,
                        COUNT(*) as count
                    ')->first();

                    $total = $result->total ?? 0;
                    $count = $result->count ?? 0;

                    $monthData['banks'][$bank->id] = [
                        'total' => $total,
                        'count' => $count,
                    ];

                    $monthData['total'] += $total;
                    $monthData['count'] += $count;
                }

                $months[] = $monthData;
                $currentDate->addMonth();
            }
        }

        // Grand total
        $grandTotal = [
            'total' => 0,
            'count' => 0,
            'banks' => [],
        ];

        foreach ($banks as $bank) {
            $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                $q->where('bank_id', $bank->id);
            });

            $this->applyDateRangeFilter($query, $request);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            $result = $query->selectRaw('
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            ')->first();

            $total = $result->total ?? 0;
            $count = $result->count ?? 0;

            $grandTotal['banks'][$bank->id] = [
                'total' => $total,
                'count' => $count,
            ];

            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() 
            ? Company::where('status', 'active')->orderBy('name')->get() 
            : collect();

        return view('reports.monthly-by-bank', compact(
            'banks',
            'months',
            'grandTotal',
            'dateRange',
            'transactionType',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * Show monthly transaction details
     */
    public function monthlyDetail(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'bank_id' => 'required|exists:banks,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
        $month = $request->month;
        $bankId = $request->bank_id;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $bank = Bank::findOrFail($bankId);
        $monthName = Carbon::create($year, $month)->format('F');

        // Build query for transactions
        $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bankId) {
            $q->where('bank_id', $bankId);
        })
        ->whereYear('transaction_date', $year)
        ->whereMonth('transaction_date', $month)
        ->with([
            'bankStatement:id,bank_id,account_number,original_filename',
            'bankStatement.bank:id,name,code,logo',
            'subCategory:id,name,category_id',
            'category:id,name,type_id',
            'type:id,name',
            'account:id,code,name',
            'matchedKeyword:id,keyword,sub_category_id'
        ]);

        if (!is_null($companyId)) {
            $query->where('statement_transactions.company_id', $companyId);
        }

        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }

        // Calculate summary
        $summaryQuery = clone $query;
        $allTransactions = $summaryQuery->get();
        
        $summary = [
            'total' => $allTransactions->sum('amount'),
            'count' => $allTransactions->count(),
            'matched' => $allTransactions->where('matched_keyword_id', '!=', null)->count(),
            'unmatched' => $allTransactions->where('matched_keyword_id', null)->count(),
            'verified' => $allTransactions->where('is_verified', true)->count(),
        ];

        // Get paginated transactions
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->appends($request->except('page'));

        $unmatchedTransactions = $allTransactions->where('matched_keyword_id', null);
        $suggestedKeywords = $this->generateKeywordSuggestions($unmatchedTransactions);

        return view('reports.monthly-detail', compact(
            'transactions',
            'bank',
            'suggestedKeywords',
            'summary',
            'year',
            'month',
            'monthName',
            'transactionType',
            'selectedCompanyId'
        ));
    }

    /**
     * 2. LAPORAN PER KEYWORD
     */
    public function byKeyword(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bank_id' => 'nullable|exists:banks,id',
            'category_id' => 'nullable|exists:categories,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $bankId = $request->bank_id;
        $categoryId = $request->category_id;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $dateRange = $this->getDateRangeInfo($request);

        // Get keywords
        $keywordsQuery = Keyword::with(['subCategory.category.type'])
            ->where('is_active', true);

        if (!is_null($companyId)) {
            $keywordsQuery->where('keywords.company_id', $companyId);
        }

        if ($categoryId) {
            $keywordsQuery->whereHas('subCategory', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $keywords = $keywordsQuery->get();

        // Generate months
        $months = $this->generateMonthsData($dateRange, $keywords, function($query, $monthStart, $monthEnd, $keyword) use ($companyId, $bankId, $transactionType) {
            $query->where('matched_keyword_id', $keyword->id)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        // Grand total
        $grandTotal = $this->calculateGrandTotal($keywords, $request, function($query, $keyword) use ($companyId, $bankId, $transactionType) {
            $query->where('matched_keyword_id', $keyword->id);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        // Get filter options
        $banks = Bank::all();
        
        $categoriesQuery = Category::with('type');
        if (!is_null($companyId)) {
            $categoriesQuery->where('categories.company_id', $companyId);
        }
        $categories = $categoriesQuery->get();

        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-keyword', compact(
            'keywords',
            'months',
            'grandTotal',
            'dateRange',
            'bankId',
            'categoryId',
            'transactionType',
            'banks',
            'categories',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * 3. LAPORAN PER CATEGORY
     */
    public function byCategory(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bank_id' => 'nullable|exists:banks,id',
            'type_id' => 'nullable|exists:types,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $bankId = $request->bank_id;
        $typeId = $request->type_id;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $dateRange = $this->getDateRangeInfo($request);

        // Get categories
        $categoriesQuery = Category::with('type');

        if (!is_null($companyId)) {
            $categoriesQuery->where('categories.company_id', $companyId);
        }

        if ($typeId) {
            $categoriesQuery->where('type_id', $typeId);
        }

        $categories = $categoriesQuery->get();

        // Generate months
        $months = $this->generateMonthsData($dateRange, $categories, function($query, $monthStart, $monthEnd, $category) use ($companyId, $bankId, $transactionType) {
            $query->where('category_id', $category->id)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        // Grand total
        $grandTotal = $this->calculateGrandTotal($categories, $request, function($query, $category) use ($companyId, $bankId, $transactionType) {
            $query->where('category_id', $category->id);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        $banks = Bank::all();
        
        $typesQuery = Type::query();
        if (!is_null($companyId)) {
            $typesQuery->where('types.company_id', $companyId);
        }
        $types = $typesQuery->get();

        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-category', compact(
            'categories',
            'months',
            'grandTotal',
            'dateRange',
            'bankId',
            'typeId',
            'transactionType',
            'banks',
            'types',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * 4. LAPORAN PER SUB CATEGORY
     */
    public function bySubCategory(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bank_id' => 'nullable|exists:banks,id',
            'category_id' => 'nullable|exists:categories,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $bankId = $request->bank_id;
        $categoryId = $request->category_id;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $dateRange = $this->getDateRangeInfo($request);

        // Get sub categories
        $subCategoriesQuery = SubCategory::with('category.type');

        if (!is_null($companyId)) {
            $subCategoriesQuery->where('sub_categories.company_id', $companyId);
        }

        if ($categoryId) {
            $subCategoriesQuery->where('category_id', $categoryId);
        }

        $subCategories = $subCategoriesQuery->get();

        // Generate months
        $months = $this->generateMonthsData($dateRange, $subCategories, function($query, $monthStart, $monthEnd, $subCategory) use ($companyId, $bankId, $transactionType) {
            $query->where('sub_category_id', $subCategory->id)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        // Grand total
        $grandTotal = $this->calculateGrandTotal($subCategories, $request, function($query, $subCategory) use ($companyId, $bankId, $transactionType) {
            $query->where('sub_category_id', $subCategory->id);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        $banks = Bank::all();
        
        $categoriesQuery = Category::with('type');
        if (!is_null($companyId)) {
            $categoriesQuery->where('categories.company_id', $companyId);
        }
        $categories = $categoriesQuery->get();

        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-sub-category', compact(
            'subCategories',
            'months',
            'grandTotal',
            'dateRange',
            'bankId',
            'categoryId',
            'transactionType',
            'banks',
            'categories',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * 5. LAPORAN PER ACCOUNT (NEW!)
     * Horizontal: Accounts | Vertical: Bulan
     */
    public function byAccount(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bank_id' => 'nullable|exists:banks,id',
            'account_type' => 'nullable|in:asset,liability,equity,revenue,expense',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $bankId = $request->bank_id;
        $accountType = $request->account_type;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $dateRange = $this->getDateRangeInfo($request);

        // Get accounts
        $accountsQuery = Account::where('is_active', true);

        if (!is_null($companyId)) {
            $accountsQuery->where('accounts.company_id', $companyId);
        }

        if ($accountType) {
            $accountsQuery->where('account_type', $accountType);
        }

        $accounts = $accountsQuery->orderBy('code')->get();

        // Generate months
        $months = $this->generateMonthsData($dateRange, $accounts, function($query, $monthStart, $monthEnd, $account) use ($companyId, $bankId, $transactionType) {
            $query->where('account_id', $account->id)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        // Grand total
        $grandTotal = $this->calculateGrandTotal($accounts, $request, function($query, $account) use ($companyId, $bankId, $transactionType) {
            $query->where('account_id', $account->id);

            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            if ($bankId) {
                $query->whereHas('bankStatement', function($q) use ($bankId) {
                    $q->where('bank_id', $bankId);
                });
            }

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            return $query;
        });

        $banks = Bank::all();
        
        $accountTypes = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expense',
        ];

        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-account', compact(
            'accounts',
            'months',
            'grandTotal',
            'dateRange',
            'bankId',
            'accountType',
            'transactionType',
            'banks',
            'accountTypes',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * Show account transaction details (NEW!)
     */
    public function accountDetail(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
        $month = $request->month;
        $accountId = $request->account_id;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $account = Account::findOrFail($accountId);
        $monthName = Carbon::create($year, $month)->format('F');

        // Build query for transactions
        $query = StatementTransaction::where('account_id', $accountId)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->with([
                'bankStatement:id,bank_id,account_number,original_filename',
                'bankStatement.bank:id,name,code,logo',
                'subCategory:id,name,category_id',
                'category:id,name,type_id',
                'type:id,name',
                'account:id,code,name,account_type',
                'matchedKeyword:id,keyword,sub_category_id',
                'matchedAccountKeyword:id,keyword,account_id'
            ]);

        if (!is_null($companyId)) {
            $query->where('statement_transactions.company_id', $companyId);
        }

        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }

        // Calculate summary
        $summaryQuery = clone $query;
        $allTransactions = $summaryQuery->get();
        
        $summary = [
            'total' => $allTransactions->sum('amount'),
            'count' => $allTransactions->count(),
            'debit' => $allTransactions->where('transaction_type', 'debit')->sum('amount'),
            'credit' => $allTransactions->where('transaction_type', 'credit')->sum('amount'),
            'verified' => $allTransactions->where('is_verified', true)->count(),
            'matched' => $allTransactions->where('matched_keyword_id', '!=', null)->count(),
        ];

        // Check if AJAX request
        if ($request->ajax() || $request->has('ajax')) {
            // Return JSON for AJAX modal
            $transactions = $query->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(50) // Limit untuk modal
                ->get()
                ->map(function($txn) {
                    return [
                        'id' => $txn->id,
                        'transaction_date' => $txn->transaction_date,
                        'transaction_time' => $txn->transaction_time,
                        'description' => $txn->description,
                        'reference_no' => $txn->reference_no,
                        'amount' => $txn->amount,
                        'transaction_type' => $txn->transaction_type,
                        'is_verified' => $txn->is_verified,
                        'matched_keyword_id' => $txn->matched_keyword_id,
                        'account_confidence_score' => $txn->account_confidence_score,
                        'bank_name' => $txn->bankStatement && $txn->bankStatement->bank ? $txn->bankStatement->bank->name : null,
                        'bank_logo' => $txn->bankStatement && $txn->bankStatement->bank && $txn->bankStatement->bank->logo ? Storage::url($txn->bankStatement->bank->logo) : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'account' => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                ],
                'summary' => $summary,
                'transactions' => $transactions,
                'period' => [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => $monthName,
                ],
            ]);
        }

        // Get paginated transactions for full page
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->appends($request->except('page'));

        return view('reports.account-detail', compact(
            'transactions',
            'account',
            'summary',
            'year',
            'month',
            'monthName',
            'transactionType',
            'selectedCompanyId'
        ));
    }

    /**
     * 6. LAPORAN COMPARISON (Bank vs Bank)
     */
    public function comparison(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bank_1' => 'required|exists:banks,id',
            'bank_2' => 'required|exists:banks,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $bank1Id = $request->bank_1;
        $bank2Id = $request->bank_2;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null;
        } else {
            $companyId = $this->getCompanyId();
        }

        $dateRange = $this->getDateRangeInfo($request);

        $bank1 = Bank::findOrFail($bank1Id);
        $bank2 = Bank::findOrFail($bank2Id);

        // Generate months for comparison
        $months = [];
        
        if ($dateRange['type'] === 'year') {
            for ($month = 1; $month <= 12; $month++) {
                $monthStart = Carbon::create($dateRange['year'], $month, 1)->startOfMonth();
                $monthEnd = Carbon::create($dateRange['year'], $month, 1)->endOfMonth();
                
                $months[] = $this->getComparisonMonthData($monthStart, $monthEnd, $bank1Id, $bank2Id, $companyId, $transactionType);
            }
        } else {
            $currentDate = $dateRange['start']->copy()->startOfMonth();
            $endDate = $dateRange['end']->copy()->endOfMonth();
            
            while ($currentDate <= $endDate) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();
                
                if ($monthStart < $dateRange['start']) {
                    $monthStart = $dateRange['start']->copy();
                }
                if ($monthEnd > $dateRange['end']) {
                    $monthEnd = $dateRange['end']->copy();
                }
                
                $months[] = $this->getComparisonMonthData($monthStart, $monthEnd, $bank1Id, $bank2Id, $companyId, $transactionType);
                
                $currentDate->addMonth();
            }
        }

        // Grand totals
        $grandTotal = [
            'bank_1' => [
                'total' => array_sum(array_column(array_column($months, 'bank_1'), 'total')),
                'count' => array_sum(array_column(array_column($months, 'bank_1'), 'count')),
            ],
            'bank_2' => [
                'total' => array_sum(array_column(array_column($months, 'bank_2'), 'total')),
                'count' => array_sum(array_column(array_column($months, 'bank_2'), 'count')),
            ],
        ];

        $grandTotal['difference'] = [
            'total' => $grandTotal['bank_1']['total'] - $grandTotal['bank_2']['total'],
            'count' => $grandTotal['bank_1']['count'] - $grandTotal['bank_2']['count'],
        ];

        $banks = Bank::all();
        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.comparison', compact(
            'bank1',
            'bank2',
            'months',
            'grandTotal',
            'dateRange',
            'transactionType',
            'banks',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * 7. EXPORT TO EXCEL
     */
    public function export(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:monthly,keyword,category,subcategory,account,comparison',
            'year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId();
        $selectedCompanyId = $request->company_id;

        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $exportCompanyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $exportCompanyId = null;
        } else {
            $exportCompanyId = $companyId;
        }

        Log::info('Report export requested', [
            'report_type' => $request->report_type,
            'date_range' => $this->getDateRangeInfo($request),
            'company_id' => $exportCompanyId,
            'user_id' => auth()->id(),
        ]);

        // TODO: Implement export logic here
        return back()->with('info', 'Export feature coming soon!');
    }

    /**
     * Helper: Generate months data for reports
     */
    private function generateMonthsData($dateRange, $items, $queryCallback)
    {
        $months = [];
        
        if ($dateRange['type'] === 'year') {
            for ($month = 1; $month <= 12; $month++) {
                $monthStart = Carbon::create($dateRange['year'], $month, 1)->startOfMonth();
                $monthEnd = Carbon::create($dateRange['year'], $month, 1)->endOfMonth();
                
                $months[] = $this->processMonthData($monthStart, $monthEnd, $items, $queryCallback);
            }
        } else {
            $currentDate = $dateRange['start']->copy()->startOfMonth();
            $endDate = $dateRange['end']->copy()->endOfMonth();
            
            while ($currentDate <= $endDate) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();
                
                if ($monthStart < $dateRange['start']) {
                    $monthStart = $dateRange['start']->copy();
                }
                if ($monthEnd > $dateRange['end']) {
                    $monthEnd = $dateRange['end']->copy();
                }
                
                $months[] = $this->processMonthData($monthStart, $monthEnd, $items, $queryCallback);
                
                $currentDate->addMonth();
            }
        }
        
        return $months;
    }

    /**
     * Helper: Process month data
     */
    private function processMonthData($monthStart, $monthEnd, $items, $queryCallback)
    {
        $monthName = $monthStart->format('M Y');
        
        $monthData = [
            'month' => $monthName,
            'month_number' => $monthStart->month,
            'items' => [],
            'total' => 0,
            'count' => 0,
        ];

        foreach ($items as $item) {
            $query = StatementTransaction::query();
            $query = $queryCallback($query, $monthStart, $monthEnd, $item);

            $result = $query->selectRaw('
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            ')->first();

            $total = $result->total ?? 0;
            $count = $result->count ?? 0;

            $monthData['items'][$item->id] = [
                'total' => $total,
                'count' => $count,
            ];

            $monthData['total'] += $total;
            $monthData['count'] += $count;
        }

        return $monthData;
    }

    /**
     * Helper: Calculate grand total
     */
    private function calculateGrandTotal($items, $request, $queryCallback)
    {
        $grandTotal = [
            'total' => 0,
            'count' => 0,
            'items' => [],
        ];

        foreach ($items as $item) {
            $query = StatementTransaction::query();
            $this->applyDateRangeFilter($query, $request);
            $query = $queryCallback($query, $item);

            $result = $query->selectRaw('
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            ')->first();

            $total = $result->total ?? 0;
            $count = $result->count ?? 0;

            $grandTotal['items'][$item->id] = [
                'total' => $total,
                'count' => $count,
            ];

            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        return $grandTotal;
    }

    /**
     * Helper: Get comparison month data
     */
    private function getComparisonMonthData($monthStart, $monthEnd, $bank1Id, $bank2Id, $companyId, $transactionType)
    {
        $monthName = $monthStart->format('M Y');
        
        $monthData = [
            'month' => $monthName,
            'month_number' => $monthStart->month,
        ];

        // Data bank 1
        $query1 = StatementTransaction::whereHas('bankStatement', function($q) use ($bank1Id) {
            $q->where('bank_id', $bank1Id);
        })
        ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

        if (!is_null($companyId)) {
            $query1->where('statement_transactions.company_id', $companyId);
        }

        if ($transactionType !== 'all') {
            $query1->where('transaction_type', $transactionType);
        }

        $result1 = $query1->selectRaw('
            COALESCE(SUM(amount), 0) as total,
            COUNT(*) as count
        ')->first();

        $monthData['bank_1'] = [
            'total' => $result1->total ?? 0,
            'count' => $result1->count ?? 0,
        ];

        // Data bank 2
        $query2 = StatementTransaction::whereHas('bankStatement', function($q) use ($bank2Id) {
            $q->where('bank_id', $bank2Id);
        })
        ->whereBetween('transaction_date', [$monthStart, $monthEnd]);

        if (!is_null($companyId)) {
            $query2->where('statement_transactions.company_id', $companyId);
        }

        if ($transactionType !== 'all') {
            $query2->where('transaction_type', $transactionType);
        }

        $result2 = $query2->selectRaw('
            COALESCE(SUM(amount), 0) as total,
            COUNT(*) as count
        ')->first();

        $monthData['bank_2'] = [
            'total' => $result2->total ?? 0,
            'count' => $result2->count ?? 0,
        ];

        // Difference
        $monthData['difference'] = [
            'total' => $monthData['bank_1']['total'] - $monthData['bank_2']['total'],
            'count' => $monthData['bank_1']['count'] - $monthData['bank_2']['count'],
        ];

        return $monthData;
    }

    /**
     * Generate keyword suggestions from unmatched transactions
     */
    private function generateKeywordSuggestions($transactions, $limit = 10)
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        $descriptionWords = [];
        
        foreach ($transactions as $transaction) {
            $description = strtolower($transaction->description);
            $description = preg_replace('/[^a-z0-9\s]/i', ' ', $description);
            $words = explode(' ', $description);
            
            foreach ($words as $word) {
                $word = trim($word);
                
                if (strlen($word) < 3) continue;
                
                $commonWords = ['dan', 'dari', 'untuk', 'the', 'and', 'or', 'in', 'on', 'at', 'to', 'from', 'with', 'by'];
                if (in_array($word, $commonWords)) continue;
                
                if (!isset($descriptionWords[$word])) {
                    $descriptionWords[$word] = [
                        'word' => $word,
                        'count' => 0,
                        'total_amount' => 0,
                        'sample_descriptions' => []
                    ];
                }
                
                $descriptionWords[$word]['count']++;
                $descriptionWords[$word]['total_amount'] += $transaction->amount;
                
                if (count($descriptionWords[$word]['sample_descriptions']) < 3) {
                    $descriptionWords[$word]['sample_descriptions'][] = $transaction->description;
                }
            }
        }

        usort($descriptionWords, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($descriptionWords, 0, $limit);
    }
}