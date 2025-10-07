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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Master Data Management
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
    | Bank Statements & Transactions
    |--------------------------------------------------------------------------
    */

    // Bank Statements Management
    Route::resource('bank-statements', BankStatementController::class)->except(['edit', 'update']);
    Route::post('bank-statements/{bankStatement}/process-matching', [BankStatementController::class, 'processMatching'])
        ->name('bank-statements.process-matching');
    Route::get('bank-statements/{bankStatement}/download', [BankStatementController::class, 'download'])
        ->name('bank-statements.download');

    // Transactions Management
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::patch('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::post('/{transaction}/verify', [TransactionController::class, 'verify'])->name('verify');
        Route::post('/{transaction}/rematch', [TransactionController::class, 'rematch'])->name('rematch');
        Route::post('/bulk-verify', [TransactionController::class, 'bulkVerify'])->name('bulk-verify');
    });
});

require __DIR__.'/auth.php';