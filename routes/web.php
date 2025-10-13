<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\BankStatementController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\KeywordSuggestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountKeywordController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

// Home/Welcome Route
Route::get('/', function () {
    // Redirect authenticated users to dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('welcome');

// Alternative home route (some systems use this)
Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated Routes - All Users
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard - Accessible by all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Clear Dashboard Cache (Admin only but defined here for reference)
    Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache'])
        ->name('dashboard.clear-cache')
        ->middleware('role:admin');
    
    // Get Dashboard Stats via AJAX
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])
        ->name('dashboard.stats');

    // Profile Management - Accessible by all authenticated users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Transactions Management - Read access for all
    Route::prefix('transactions')->name('transactions.')->group(function () {
        // Read access for all authenticated users
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        
        // Write access (admin/user with permissions)
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::patch('/{transaction}', [TransactionController::class, 'update'])->name('update');
        
        // Transaction actions
        Route::post('/{transaction}/verify', [TransactionController::class, 'verify'])->name('verify');
        Route::post('/{transaction}/unverify', [TransactionController::class, 'unverify'])->name('unverify');
        Route::post('/{transaction}/rematch', [TransactionController::class, 'rematch'])->name('rematch');
        Route::post('/{transaction}/unmatch', [TransactionController::class, 'unmatch'])->name('unmatch');
        
        // Bulk actions
        Route::post('/bulk-verify', [TransactionController::class, 'bulkVerify'])->name('bulk-verify');
        
        // Delete
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
    });

    // Accounts Management
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}', [AccountController::class, 'show'])->name('show');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        
        // Account actions
        Route::post('/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{account}/rematch', [AccountController::class, 'rematch'])->name('rematch');
        Route::get('/{account}/statistics', [AccountController::class, 'statistics'])->name('statistics');
    });

    // Account Keywords Routes
    Route::prefix('account-keywords')->name('account-keywords.')->group(function () {
        Route::get('/create', [AccountKeywordController::class, 'create'])->name('create');
        Route::post('/', [AccountKeywordController::class, 'store'])->name('store');
        Route::get('/{accountKeyword}/edit', [AccountKeywordController::class, 'edit'])->name('edit');
        Route::put('/{accountKeyword}', [AccountKeywordController::class, 'update'])->name('update');
        Route::delete('/{accountKeyword}', [AccountKeywordController::class, 'destroy'])->name('destroy');
        
        // Keyword actions
        Route::post('/{accountKeyword}/toggle-status', [AccountKeywordController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/test', [AccountKeywordController::class, 'test'])->name('test');
        Route::post('/bulk-store', [AccountKeywordController::class, 'bulkStore'])->name('bulk-store');
    });

    // Bank Statements Management - Available for all authenticated users
    Route::prefix('bank-statements')->name('bank-statements.')->group(function () {
        // List & View
        Route::get('/', [BankStatementController::class, 'index'])->name('index');
        Route::get('/create', [BankStatementController::class, 'create'])->name('create');
        Route::get('/{bankStatement}', [BankStatementController::class, 'show'])->name('show');
        
        // Actions (may require admin permission depending on your business logic)
        Route::post('/', [BankStatementController::class, 'store'])->name('store');
        Route::get('/{bankStatement}/edit', [BankStatementController::class, 'edit'])->name('edit');
        Route::put('/{bankStatement}', [BankStatementController::class, 'update'])->name('update');
        Route::delete('/{bankStatement}', [BankStatementController::class, 'destroy'])->name('destroy');
        
        // Upload & Preview
        Route::post('/upload/preview', [BankStatementController::class, 'uploadAndPreview'])->name('upload.preview');
        Route::post('/replace', [BankStatementController::class, 'replaceExisting'])->name('replace');
        Route::post('/cancel', [BankStatementController::class, 'cancelUpload'])->name('cancel');
        
        // Download & Export
        Route::get('/{bankStatement}/download', [BankStatementController::class, 'download'])->name('download');
        Route::get('/{bankStatement}/export', [BankStatementController::class, 'export'])->name('export');
        
        // OCR Operations
        Route::post('/{bankStatement}/reprocess', [BankStatementController::class, 'reprocess'])->name('reprocess');
        Route::get('/{bankStatement}/status', [BankStatementController::class, 'getStatus'])->name('status');
        Route::post('/{bankStatement}/retry', [BankStatementController::class, 'retryOCR'])->name('retry');
        
        // Matching Operations
        Route::post('/{bankStatement}/match', [BankStatementController::class, 'matchTransactions'])->name('match');
        Route::post('/{bankStatement}/process-matching', [BankStatementController::class, 'processMatching'])->name('process-matching');
        Route::post('/{bankStatement}/rematch-all', [BankStatementController::class, 'rematchAll'])->name('rematch-all');
        
        // Verification Operations
        Route::post('/{bankStatement}/verify-all-matched', [BankStatementController::class, 'verifyAllMatched'])->name('verify-all-matched');
        Route::post('/{bankStatement}/transactions/{transaction}/verify', [BankStatementController::class, 'verifyTransaction'])->name('transactions.verify');
        
        // Transaction Details
        Route::get('/{bankStatement}/transactions/{transaction}', [BankStatementController::class, 'getTransaction'])->name('transactions.show');
        
        // Statistics
        Route::get('/stats/summary', [BankStatementController::class, 'statistics'])->name('statistics');
    });

    // Keyword Suggestions
    Route::prefix('keyword-suggestions')->name('keyword-suggestions.')->group(function () {
        Route::get('/{bankStatement}/analyze', [KeywordSuggestionController::class, 'analyze'])->name('analyze');
        Route::post('/create', [KeywordSuggestionController::class, 'createFromSuggestion'])->name('create');
        Route::post('/batch-create', [KeywordSuggestionController::class, 'batchCreate'])->name('batch-create');
        Route::post('/dismiss', [KeywordSuggestionController::class, 'dismiss'])->name('dismiss');
        Route::post('/preview', [KeywordSuggestionController::class, 'preview'])->name('preview');
        Route::get('/{bankStatement}/export', [KeywordSuggestionController::class, 'export'])->name('export');
        Route::post('/{bankStatement}/refresh', [KeywordSuggestionController::class, 'refresh'])->name('refresh');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/monthly-by-bank', [ReportController::class, 'monthlyByBank'])->name('monthly-by-bank');
        Route::get('/by-keyword', [ReportController::class, 'byKeyword'])->name('by-keyword');
        Route::get('/by-category', [ReportController::class, 'byCategory'])->name('by-category');
        Route::get('/by-sub-category', [ReportController::class, 'bySubCategory'])->name('by-sub-category');
        Route::get('/comparison', [ReportController::class, 'comparison'])->name('comparison');
        Route::post('/export', [ReportController::class, 'export'])->name('export');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // User Management (Admin Only)
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/toggle-role', [UserManagementController::class, 'toggleRole'])->name('toggle-role');
    });

    // Master Data Management (Admin Only)
    
    // Banks Management
    Route::resource('banks', BankController::class);
    Route::patch('banks/{bank}/toggle-active', [BankController::class, 'toggleActive'])->name('banks.toggle-active');

    // Types Management
    Route::resource('types', TypeController::class);
    Route::post('types/reorder', [TypeController::class, 'reorder'])->name('types.reorder');

    // Categories Management
    Route::resource('categories', CategoryController::class);
    Route::get('categories/by-type/{typeId}', [CategoryController::class, 'getByType'])->name('categories.by-type');
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

    // Sub Categories Management
    Route::resource('sub-categories', SubCategoryController::class);
    Route::get('sub-categories/by-category/{categoryId}', [SubCategoryController::class, 'getByCategory'])->name('sub-categories.by-category');
    Route::post('sub-categories/reorder', [SubCategoryController::class, 'reorder'])->name('sub-categories.reorder');
    Route::post('sub-categories/bulk-update-priority', [SubCategoryController::class, 'bulkUpdatePriority'])->name('sub-categories.bulk-update-priority');

    // Keywords Management
    Route::resource('keywords', KeywordController::class);
});

require __DIR__.'/auth.php';