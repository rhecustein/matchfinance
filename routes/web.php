<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\BankStatementController;
use App\Http\Controllers\BankStatementTransactionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\KeywordSuggestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| Authenticated Routes - All Users
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard - Accessible by all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management - Accessible by all authenticated users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Add these routes to routes/web.php (inside auth middleware group)

    // Transactions Management
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
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | User Management (Admin Only)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Master Data Management (Admin Only)
    |--------------------------------------------------------------------------
    */

    // Banks Management
    Route::resource('banks', BankController::class);
    Route::patch('banks/{bank}/toggle-active', [BankController::class, 'toggleActive'])
        ->name('banks.toggle-active');

    // Types Management
    Route::resource('types', TypeController::class);
    Route::post('types/reorder', [TypeController::class, 'reorder'])
        ->name('types.reorder');

    // Categories Management
    Route::resource('categories', CategoryController::class);
    Route::get('categories/by-type/{typeId}', [CategoryController::class, 'getByType'])
        ->name('categories.by-type');
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])
        ->name('categories.reorder');

    // Sub Categories Management
    Route::resource('sub-categories', SubCategoryController::class);
    Route::get('sub-categories/by-category/{categoryId}', [SubCategoryController::class, 'getByCategory'])
        ->name('sub-categories.by-category');
    Route::post('sub-categories/reorder', [SubCategoryController::class, 'reorder'])
        ->name('sub-categories.reorder');
    Route::post('sub-categories/bulk-update-priority', [SubCategoryController::class, 'bulkUpdatePriority'])
        ->name('sub-categories.bulk-update-priority');

    // Keywords Management
    Route::resource('keywords', KeywordController::class);

   /*
    |--------------------------------------------------------------------------
    | Bank Statements & Transactions Management (Admin Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])->prefix('bank-statements')->name('bank-statements.')->group(function () {
    
        // ✅ Resource routes (index, create, store, show, edit, update, destroy)
        Route::get('/', [BankStatementController::class, 'index'])->name('index');
        Route::get('/create', [BankStatementController::class, 'create'])->name('create');
        Route::post('/', [BankStatementController::class, 'store'])->name('store');
        Route::get('/{bankStatement}', [BankStatementController::class, 'show'])->name('show');
        Route::get('/{bankStatement}/edit', [BankStatementController::class, 'edit'])->name('edit');
        Route::put('/{bankStatement}', [BankStatementController::class, 'update'])->name('update');
        Route::delete('/{bankStatement}', [BankStatementController::class, 'destroy'])->name('destroy');

        Route::get('/{bankStatement}/transactions/{transaction}', [BankStatementController::class, 'getTransaction'])
       ->name('transactions.show');
        // ✅ Upload & Preview (before store)
        Route::post('/upload/preview', [BankStatementController::class, 'uploadAndPreview'])
            ->name('upload.preview');

        // ✅ Replace existing statement
        Route::post('/replace', [BankStatementController::class, 'replaceExisting'])
            ->name('replace');

        // ✅ Cancel upload
        Route::post('/cancel', [BankStatementController::class, 'cancelUpload'])
            ->name('cancel');

        // ✅ Download PDF
        Route::get('/{bankStatement}/download', [BankStatementController::class, 'download'])
            ->name('download');

        // ✅ Export to Excel/CSV
        Route::get('/{bankStatement}/export', [BankStatementController::class, 'export'])
            ->name('export');

        // ✅ Reprocess OCR
        Route::post('/{bankStatement}/reprocess', [BankStatementController::class, 'reprocess'])
            ->name('reprocess');

        // ✅ Matching Operations
        Route::post('/{bankStatement}/match', [BankStatementController::class, 'matchTransactions'])
            ->name('match');
        
        Route::post('/{bankStatement}/process-matching', [BankStatementController::class, 'processMatching'])
            ->name('process-matching');
        
        Route::post('/{bankStatement}/rematch-all', [BankStatementController::class, 'rematchAll'])
            ->name('rematch-all');

        // ✅ Verification Operations
        Route::post('/{bankStatement}/verify-all-matched', [BankStatementController::class, 'verifyAllMatched'])
            ->name('verify-all-matched'); // FIXED: Removed duplicate

        Route::post('/{bankStatement}/transactions/{transaction}/verify', [BankStatementController::class, 'verifyTransaction'])
            ->name('transactions.verify');

        // ✅ Get transaction detail (AJAX)
        Route::get('/{bankStatement}/transactions/{transaction}', [BankStatementController::class, 'getTransaction'])
            ->name('transactions.show');

        // ✅ Statistics
        Route::get('/stats/summary', [BankStatementController::class, 'statistics'])
            ->name('statistics');
    });

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
        // Dashboard report
        Route::get('/', [ReportController::class, 'index'])->name('index');
        
        // 1. Monthly by Bank Report
        Route::get('/monthly-by-bank', [ReportController::class, 'monthlyByBank'])->name('monthly-by-bank');
        
        // 2. By Keyword Report
        Route::get('/by-keyword', [ReportController::class, 'byKeyword'])->name('by-keyword');
        
        // 3. By Category Report
        Route::get('/by-category', [ReportController::class, 'byCategory'])->name('by-category');
        
        // 4. By Sub Category Report
        Route::get('/by-sub-category', [ReportController::class, 'bySubCategory'])->name('by-sub-category');
        
        // 5. Comparison Report
        Route::get('/comparison', [ReportController::class, 'comparison'])->name('comparison');
        
        // 6. Export
        Route::post('/export', [ReportController::class, 'export'])->name('export');
    });
});

require __DIR__.'/auth.php';