<?php

namespace App\Http\Controllers;

use App\Models\StatementTransaction;
use App\Models\Bank;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        return view('reports.index', compact(
            'availableBanks', 
            'availableYears', 
            'transactionYears',
            'companies'
        ));
    }

    /**
     * 1. LAPORAN BULANAN PER BANK
     * Horizontal: Bank | Vertical: Bulan
     */
    public function monthlyByBank(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
        $transactionType = $request->transaction_type ?? 'all';
        $selectedCompanyId = $request->company_id;

        // Company filter logic
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $companyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $companyId = null; // Tampilkan semua
        } else {
            $companyId = $this->getCompanyId();
        }

        // Get all banks yang punya transaksi (with company filter)
        $banks = Bank::whereHas('bankStatements.transactions', function($query) use ($year, $companyId) {
            $query->whereYear('transaction_date', $year);
            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }
        })
        ->orderBy('name')
        ->get();

        // Generate data per bulan (Jan-Dec)
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month)->format('M Y');
            
            $monthData = [
                'month' => $monthName,
                'month_number' => $month,
                'banks' => [],
                'total' => 0,
                'count' => 0,
            ];

            // Data per bank untuk bulan ini
            foreach ($banks as $bank) {
                // Build query dengan optimize
                $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                    $q->where('bank_id', $bank->id);
                })
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month);

                // Apply company filter
                if (!is_null($companyId)) {
                    $query->where('statement_transactions.company_id', $companyId);
                }

                // Filter transaction type
                if ($transactionType !== 'all') {
                    $query->where('transaction_type', $transactionType);
                }

                // Get data dengan single query
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

                // Accumulate monthly total
                $monthData['total'] += $total;
                $monthData['count'] += $count;
            }

            $months[] = $monthData;
        }

        // Grand total dengan optimize query
        $grandTotal = [
            'total' => 0,
            'count' => 0,
            'banks' => [],
        ];

        foreach ($banks as $bank) {
            // Build query
            $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                $q->where('bank_id', $bank->id);
            })
            ->whereYear('transaction_date', $year);

            // Apply company filter
            if (!is_null($companyId)) {
                $query->where('statement_transactions.company_id', $companyId);
            }

            // Filter transaction type
            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            // Get data dengan single query
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

            // Accumulate grand total
            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() 
            ? Company::where('status', 'active')->orderBy('name')->get() 
            : collect();

        // Log untuk debugging (optional)
        Log::info('Monthly by Bank Report Generated', [
            'year' => $year,
            'transaction_type' => $transactionType,
            'company_id' => $companyId,
            'banks_count' => $banks->count(),
            'grand_total' => $grandTotal['total'],
            'user_id' => auth()->id(),
        ]);

        return view('reports.monthly-by-bank', compact(
            'banks',
            'months',
            'grandTotal',
            'year',
            'transactionType',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * Show monthly transaction details (View Page)
     * Return detail transaksi untuk 1 bulan dan 1 bank tertentu
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

        // Get bank info
        $bank = Bank::findOrFail($bankId);

        // Month name
        $monthName = Carbon::create($year, $month)->format('F');

        // Build query for transactions
        $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bankId) {
            $q->where('bank_id', $bankId);
        })
        ->whereYear('transaction_date', $year)
        ->whereMonth('transaction_date', $month)
        ->with([
            'bankStatement:id,bank_id,account_number,original_filename',
            'bankStatement.bank:id,name,code,logo',  // ✅ Fix: logo bukan logo_url
            'subCategory:id,name,category_id',
            'category:id,name,type_id',
            'type:id,name',
            'account:id,code,name',
            'matchedKeyword:id,keyword,sub_category_id'
        ]);

        // Apply company filter
        if (!is_null($companyId)) {
            $query->where('statement_transactions.company_id', $companyId);
        }

        // Filter transaction type
        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }

        // Calculate summary BEFORE pagination
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
            ->appends($request->except('page')); // Keep query params in pagination

        // Generate suggested keywords dari transaksi yang belum di-match
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
     * Generate keyword suggestions from unmatched transactions
     */
    private function generateKeywordSuggestions($transactions, $limit = 10)
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        $descriptionWords = [];
        
        foreach ($transactions as $transaction) {
            // Extract words dari description
            $description = strtolower($transaction->description);
            
            // Remove common words dan special characters
            $description = preg_replace('/[^a-z0-9\s]/i', ' ', $description);
            $words = explode(' ', $description);
            
            foreach ($words as $word) {
                $word = trim($word);
                
                // Skip short words dan common words
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

        // Sort by frequency
        usort($descriptionWords, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($descriptionWords, 0, $limit);
    }

    /**
     * 2. LAPORAN PER KEYWORD (dengan Category & Sub Category)
     * Horizontal: Keyword | Vertical: Bulan
     */
    public function byKeyword(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'bank_id' => 'nullable|exists:banks,id',
            'category_id' => 'nullable|exists:categories,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
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

        // Get keywords dengan relasi (with company filter)
        $keywordsQuery = Keyword::with(['subCategory.category.type'])
            ->where('is_active', true);

        // Apply company filter untuk keywords
        if (!is_null($companyId)) {
            $keywordsQuery->where('keywords.company_id', $companyId);
        }

        if ($categoryId) {
            $keywordsQuery->whereHas('subCategory', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $keywords = $keywordsQuery->get();

        // Generate data per bulan
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month)->format('M Y');
            
            $monthData = [
                'month' => $monthName,
                'month_number' => $month,
                'keywords' => [],
            ];

            // Data per keyword untuk bulan ini
            foreach ($keywords as $keyword) {
                $query = StatementTransaction::where('matched_keyword_id', $keyword->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);

                // Apply company filter
                if (!is_null($companyId)) {
                    $query->where('statement_transactions.company_id', $companyId);
                }

                // Filter bank
                if ($bankId) {
                    $query->whereHas('bankStatement', function($q) use ($bankId) {
                        $q->where('bank_id', $bankId);
                    });
                }

                // Filter transaction type
                if ($transactionType !== 'all') {
                    $query->where('transaction_type', $transactionType);
                }

                $total = $query->sum('amount');
                $count = $query->count();

                $monthData['keywords'][$keyword->id] = [
                    'total' => $total,
                    'count' => $count,
                ];
            }

            // Total untuk bulan ini
            $monthData['total'] = array_sum(array_column($monthData['keywords'], 'total'));
            $monthData['count'] = array_sum(array_column($monthData['keywords'], 'count'));

            $months[] = $monthData;
        }

        // Grand total per keyword
        $grandTotal = [
            'total' => 0,
            'count' => 0,
            'keywords' => [],
        ];

        foreach ($keywords as $keyword) {
            $query = StatementTransaction::where('matched_keyword_id', $keyword->id)
                ->whereYear('transaction_date', $year);

            // Apply company filter
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

            $total = $query->sum('amount');
            $count = $query->count();

            $grandTotal['keywords'][$keyword->id] = [
                'total' => $total,
                'count' => $count,
            ];
            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        // Get filter options (with company filter)
        $banks = Bank::all();
        
        $categoriesQuery = Category::with('type');
        if (!is_null($companyId)) {
            $categoriesQuery->where('categories.company_id', $companyId);
        }
        $categories = $categoriesQuery->get();

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-keyword', compact(
            'keywords',
            'months',
            'grandTotal',
            'year',
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
     * 3. LAPORAN PER CATEGORY (Type > Category > Sub Category)
     * Horizontal: Categories | Vertical: Bulan
     */
    public function byCategory(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'bank_id' => 'nullable|exists:banks,id',
            'type_id' => 'nullable|exists:types,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
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

        // Get categories dengan relasi (with company filter)
        $categoriesQuery = Category::with('type');

        // Apply company filter
        if (!is_null($companyId)) {
            $categoriesQuery->where('categories.company_id', $companyId);
        }

        if ($typeId) {
            $categoriesQuery->where('type_id', $typeId);
        }

        $categories = $categoriesQuery->get();

        // Generate data per bulan
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month)->format('M Y');
            
            $monthData = [
                'month' => $monthName,
                'month_number' => $month,
                'categories' => [],
            ];

            // Data per category untuk bulan ini
            foreach ($categories as $category) {
                $query = StatementTransaction::where('category_id', $category->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);

                // Apply company filter
                if (!is_null($companyId)) {
                    $query->where('statement_transactions.company_id', $companyId);
                }

                // Filter bank
                if ($bankId) {
                    $query->whereHas('bankStatement', function($q) use ($bankId) {
                        $q->where('bank_id', $bankId);
                    });
                }

                // Filter transaction type
                if ($transactionType !== 'all') {
                    $query->where('transaction_type', $transactionType);
                }

                $total = $query->sum('amount');
                $count = $query->count();

                $monthData['categories'][$category->id] = [
                    'total' => $total,
                    'count' => $count,
                ];
            }

            // Total untuk bulan ini
            $monthData['total'] = array_sum(array_column($monthData['categories'], 'total'));
            $monthData['count'] = array_sum(array_column($monthData['categories'], 'count'));

            $months[] = $monthData;
        }

        // Grand total
        $grandTotal = [
            'total' => 0,
            'count' => 0,
            'categories' => [],
        ];

        foreach ($categories as $category) {
            $query = StatementTransaction::where('category_id', $category->id)
                ->whereYear('transaction_date', $year);

            // Apply company filter
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

            $total = $query->sum('amount');
            $count = $query->count();

            $grandTotal['categories'][$category->id] = [
                'total' => $total,
                'count' => $count,
            ];
            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        // Get filter options (with company filter)
        $banks = Bank::all();
        
        $typesQuery = Type::query();
        if (!is_null($companyId)) {
            $typesQuery->where('types.company_id', $companyId);
        }
        $types = $typesQuery->get();

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-category', compact(
            'categories',
            'months',
            'grandTotal',
            'year',
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
     * Horizontal: Sub Categories | Vertical: Bulan
     */
    public function bySubCategory(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'bank_id' => 'nullable|exists:banks,id',
            'category_id' => 'nullable|exists:categories,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
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

        // Get sub categories (with company filter)
        $subCategoriesQuery = SubCategory::with('category.type');

        // Apply company filter
        if (!is_null($companyId)) {
            $subCategoriesQuery->where('sub_categories.company_id', $companyId);
        }

        if ($categoryId) {
            $subCategoriesQuery->where('category_id', $categoryId);
        }

        $subCategories = $subCategoriesQuery->get();

        // Generate data per bulan
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month)->format('M Y');
            
            $monthData = [
                'month' => $monthName,
                'month_number' => $month,
                'subCategories' => [],
            ];

            // Data per sub category untuk bulan ini
            foreach ($subCategories as $subCategory) {
                $query = StatementTransaction::where('sub_category_id', $subCategory->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);

                // Apply company filter
                if (!is_null($companyId)) {
                    $query->where('statement_transactions.company_id', $companyId);
                }

                // Filter bank
                if ($bankId) {
                    $query->whereHas('bankStatement', function($q) use ($bankId) {
                        $q->where('bank_id', $bankId);
                    });
                }

                // Filter transaction type
                if ($transactionType !== 'all') {
                    $query->where('transaction_type', $transactionType);
                }

                $total = $query->sum('amount');
                $count = $query->count();

                $monthData['subCategories'][$subCategory->id] = [
                    'total' => $total,
                    'count' => $count,
                ];
            }

            // Total untuk bulan ini
            $monthData['total'] = array_sum(array_column($monthData['subCategories'], 'total'));
            $monthData['count'] = array_sum(array_column($monthData['subCategories'], 'count'));

            $months[] = $monthData;
        }

        // Grand total
        $grandTotal = [
            'total' => 0,
            'count' => 0,
            'subCategories' => [],
        ];

        foreach ($subCategories as $subCategory) {
            $query = StatementTransaction::where('sub_category_id', $subCategory->id)
                ->whereYear('transaction_date', $year);

            // Apply company filter
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

            $total = $query->sum('amount');
            $count = $query->count();

            $grandTotal['subCategories'][$subCategory->id] = [
                'total' => $total,
                'count' => $count,
            ];
            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        // Get filter options (with company filter)
        $banks = Bank::all();
        
        $categoriesQuery = Category::with('type');
        if (!is_null($companyId)) {
            $categoriesQuery->where('categories.company_id', $companyId);
        }
        $categories = $categoriesQuery->get();

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.by-sub-category', compact(
            'subCategories',
            'months',
            'grandTotal',
            'year',
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
     * 5. LAPORAN COMPARISON (Bank vs Bank)
     * Compare 2 banks side by side
     */
    public function comparison(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'bank_1' => 'required|exists:banks,id',
            'bank_2' => 'required|exists:banks,id',
            'transaction_type' => 'nullable|in:all,debit,credit',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $year = $request->year;
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

        $bank1 = Bank::findOrFail($bank1Id);
        $bank2 = Bank::findOrFail($bank2Id);

        // Generate data per bulan
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month)->format('M Y');
            
            $monthData = [
                'month' => $monthName,
                'month_number' => $month,
            ];

            // Data bank 1
            $query1 = StatementTransaction::whereHas('bankStatement', function($q) use ($bank1Id) {
                $q->where('bank_id', $bank1Id);
            })
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month);

            // Apply company filter
            if (!is_null($companyId)) {
                $query1->where('statement_transactions.company_id', $companyId);
            }

            if ($transactionType !== 'all') {
                $query1->where('transaction_type', $transactionType);
            }

            $monthData['bank_1'] = [
                'total' => $query1->sum('amount'),
                'count' => $query1->count(),
            ];

            // Data bank 2
            $query2 = StatementTransaction::whereHas('bankStatement', function($q) use ($bank2Id) {
                $q->where('bank_id', $bank2Id);
            })
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month);

            // Apply company filter
            if (!is_null($companyId)) {
                $query2->where('statement_transactions.company_id', $companyId);
            }

            if ($transactionType !== 'all') {
                $query2->where('transaction_type', $transactionType);
            }

            $monthData['bank_2'] = [
                'total' => $query2->sum('amount'),
                'count' => $query2->count(),
            ];

            // Difference
            $monthData['difference'] = [
                'total' => $monthData['bank_1']['total'] - $monthData['bank_2']['total'],
                'count' => $monthData['bank_1']['count'] - $monthData['bank_2']['count'],
            ];

            $months[] = $monthData;
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

        // Get all companies (untuk super admin)
        $companies = $this->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        return view('reports.comparison', compact(
            'bank1',
            'bank2',
            'months',
            'grandTotal',
            'year',
            'transactionType',
            'banks',
            'companies',
            'selectedCompanyId'
        ));
    }

    /**
     * 6. EXPORT TO EXCEL
     */
    public function export(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:monthly,keyword,category,subcategory,comparison',
            'year' => 'required|integer',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId();
        $selectedCompanyId = $request->company_id;

        // Company filter logic untuk export
        if ($this->isSuperAdmin() && $selectedCompanyId) {
            $exportCompanyId = $selectedCompanyId;
        } elseif ($this->isSuperAdmin() && !$selectedCompanyId) {
            $exportCompanyId = null;
        } else {
            $exportCompanyId = $companyId;
        }

        Log::info('Report export requested', [
            'report_type' => $request->report_type,
            'year' => $request->year,
            'company_id' => $exportCompanyId,
            'user_id' => auth()->id(),
        ]);

        // TODO: Implement export logic here
        // You can use Laravel Excel or similar package
        // Make sure to apply company filter in export

        return back()->with('info', 'Export feature coming soon!');
    }
}