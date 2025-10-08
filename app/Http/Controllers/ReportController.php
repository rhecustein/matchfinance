<?php

namespace App\Http\Controllers;

use App\Models\StatementTransaction;
use App\Models\Bank;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display report index/dashboard
     */
    public function index()
    {
        $availableBanks = Bank::whereHas('bankStatements.transactions')->get();
        
        // Get years from transactions
        $transactionYears = StatementTransaction::selectRaw('YEAR(transaction_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Generate year range from 2015 to 2027
        $yearRange = range(2027, 2015);
        $availableYears = collect($yearRange);

        return view('reports.index', compact('availableBanks', 'availableYears', 'transactionYears'));
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
        ]);

        $year = $request->year;
        $transactionType = $request->transaction_type ?? 'all';

        // Get all banks yang punya transaksi
        $banks = Bank::whereHas('bankStatements.transactions', function($q) use ($year) {
            $q->whereYear('transaction_date', $year);
        })->get();

        // Generate data per bulan (Jan-Dec)
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month)->format('M Y');
            
            $monthData = [
                'month' => $monthName,
                'month_number' => $month,
            ];

            // Data per bank untuk bulan ini
            foreach ($banks as $bank) {
                $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                    $q->where('bank_id', $bank->id);
                })
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month);

                // Filter transaction type
                if ($transactionType !== 'all') {
                    $query->where('transaction_type', $transactionType);
                }

                $total = $query->sum('amount');
                $count = $query->count();

                $monthData['banks'][$bank->id] = [
                    'total' => $total,
                    'count' => $count,
                ];
            }

            // Total untuk bulan ini
            $monthData['total'] = array_sum(array_column($monthData['banks'], 'total'));
            $monthData['count'] = array_sum(array_column($monthData['banks'], 'count'));

            $months[] = $monthData;
        }

        // Grand total
        $grandTotal = [
            'total' => 0,
            'count' => 0,
        ];

        foreach ($banks as $bank) {
            $query = StatementTransaction::whereHas('bankStatement', function($q) use ($bank) {
                $q->where('bank_id', $bank->id);
            })->whereYear('transaction_date', $year);

            if ($transactionType !== 'all') {
                $query->where('transaction_type', $transactionType);
            }

            $total = $query->sum('amount');
            $count = $query->count();

            $grandTotal['banks'][$bank->id] = [
                'total' => $total,
                'count' => $count,
            ];
            $grandTotal['total'] += $total;
            $grandTotal['count'] += $count;
        }

        return view('reports.monthly-by-bank', compact(
            'banks',
            'months',
            'grandTotal',
            'year',
            'transactionType'
        ));
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
        ]);

        $year = $request->year;
        $bankId = $request->bank_id;
        $categoryId = $request->category_id;
        $transactionType = $request->transaction_type ?? 'all';

        // Get keywords dengan relasi
        $keywordsQuery = Keyword::with(['subCategory.category.type'])
            ->where('is_active', true);

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
            ];

            // Data per keyword untuk bulan ini
            foreach ($keywords as $keyword) {
                $query = StatementTransaction::where('matched_keyword_id', $keyword->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);

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
        ];

        foreach ($keywords as $keyword) {
            $query = StatementTransaction::where('matched_keyword_id', $keyword->id)
                ->whereYear('transaction_date', $year);

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

        // Get filter options
        $banks = Bank::all();
        $categories = Category::with('type')->get();

        return view('reports.by-keyword', compact(
            'keywords',
            'months',
            'grandTotal',
            'year',
            'bankId',
            'categoryId',
            'transactionType',
            'banks',
            'categories'
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
        ]);

        $year = $request->year;
        $bankId = $request->bank_id;
        $typeId = $request->type_id;
        $transactionType = $request->transaction_type ?? 'all';

        // Get categories dengan relasi
        $categoriesQuery = Category::with('type');

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
            ];

            // Data per category untuk bulan ini
            foreach ($categories as $category) {
                $query = StatementTransaction::where('category_id', $category->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);

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
        ];

        foreach ($categories as $category) {
            $query = StatementTransaction::where('category_id', $category->id)
                ->whereYear('transaction_date', $year);

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

        // Get filter options
        $banks = Bank::all();
        $types = Type::all();

        return view('reports.by-category', compact(
            'categories',
            'months',
            'grandTotal',
            'year',
            'bankId',
            'typeId',
            'transactionType',
            'banks',
            'types'
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
        ]);

        $year = $request->year;
        $bankId = $request->bank_id;
        $categoryId = $request->category_id;
        $transactionType = $request->transaction_type ?? 'all';

        // Get sub categories
        $subCategoriesQuery = SubCategory::with('category.type');

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
            ];

            // Data per sub category untuk bulan ini
            foreach ($subCategories as $subCategory) {
                $query = StatementTransaction::where('sub_category_id', $subCategory->id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);

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
        ];

        foreach ($subCategories as $subCategory) {
            $query = StatementTransaction::where('sub_category_id', $subCategory->id)
                ->whereYear('transaction_date', $year);

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

        // Get filter options
        $banks = Bank::all();
        $categories = Category::with('type')->get();

        return view('reports.by-sub-category', compact(
            'subCategories',
            'months',
            'grandTotal',
            'year',
            'bankId',
            'categoryId',
            'transactionType',
            'banks',
            'categories'
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
        ]);

        $year = $request->year;
        $bank1Id = $request->bank_1;
        $bank2Id = $request->bank_2;
        $transactionType = $request->transaction_type ?? 'all';

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

        return view('reports.comparison', compact(
            'bank1',
            'bank2',
            'months',
            'grandTotal',
            'year',
            'transactionType',
            'banks'
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
        ]);

        // Implement export logic here
        // You can use Laravel Excel or similar package

        return response()->download($filePath);
    }
}