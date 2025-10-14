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
use App\Http\Controllers\DocumentCollectionController;
use App\Http\Controllers\DocumentItemController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\CompanySubscriptionController; // NEW
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\CompanyManagementController;
use App\Http\Controllers\Admin\PlanManagementController;
use App\Http\Controllers\Admin\SubscriptionManagementController;
use App\Http\Controllers\Admin\SystemSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

// Home/Welcome Route
Route::get('/', function () {
    // Redirect authenticated users to appropriate dashboard
    if (auth()->check()) {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('dashboard');
    }
    
    return view('welcome');
})->name('welcome');

// Alternative home route
Route::get('/home', function () {
    if (auth()->check()) {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('dashboard');
    }
    
    return redirect()->route('dashboard');
})->name('home')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Super Admin Only Routes
|--------------------------------------------------------------------------
| Access: ONLY super_admin role
| Middleware: auth, verified, super_admin
*/

Route::middleware(['auth', 'verified', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats'])->name('dashboard.stats');
    
    // Company Management (Super Admin)
    Route::resource('companies', CompanyManagementController::class);
    Route::post('companies/{company}/suspend', [CompanyManagementController::class, 'suspend'])->name('companies.suspend');
    Route::post('companies/{company}/activate', [CompanyManagementController::class, 'activate'])->name('companies.activate');
    Route::post('companies/{company}/cancel', [CompanyManagementController::class, 'cancel'])->name('companies.cancel');
    Route::get('companies/{company}/stats', [CompanyManagementController::class, 'stats'])->name('companies.stats');
    
    // Plan Management (Super Admin)
    Route::resource('plans', PlanManagementController::class);
    Route::post('plans/{plan}/toggle-active', [PlanManagementController::class, 'toggleActive'])->name('plans.toggle-active');
    Route::get('plans/{plan}/subscribers', [PlanManagementController::class, 'subscribers'])->name('plans.subscribers');
    
    // Subscription Management (Super Admin)
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionManagementController::class, 'index'])->name('index');
        Route::get('/{subscription}', [SubscriptionManagementController::class, 'show'])->name('show');
        Route::post('/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/renew', [SubscriptionManagementController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/change-plan', [SubscriptionManagementController::class, 'changePlan'])->name('change-plan');
    });
    
    // System Users Management (Super Admin)
    Route::prefix('system-users')->name('system-users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'systemIndex'])->name('index');
        Route::get('/{user}', [UserManagementController::class, 'systemShow'])->name('show');
        
        // Impersonation with loop prevention
        Route::post('/{user}/impersonate', [UserManagementController::class, 'impersonate'])
            ->name('impersonate')
            ->middleware('prevent-impersonation-loop');
        
        Route::post('/stop-impersonating', [UserManagementController::class, 'stopImpersonating'])
            ->name('stop-impersonating');
    });
    
    // System Settings (Super Admin)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::post('/update', [SystemSettingsController::class, 'update'])->name('update');
        Route::post('/clear-cache', [SystemSettingsController::class, 'clearCache'])->name('clear-cache');
        Route::get('/logs', [SystemSettingsController::class, 'logs'])->name('logs');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes - All Company Users
|--------------------------------------------------------------------------
| Access: All authenticated company users
| Middleware: auth, verified, company.member
*/

Route::middleware(['auth', 'verified', 'company.member'])->group(function () {
    
    // Dashboard - Accessible by all company users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Get Dashboard Stats via AJAX
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');

    // Profile Management - All authenticated users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Transactions Management
    |--------------------------------------------------------------------------
    | Read: All company users
    | Write: Manager+
    */
    
    Route::prefix('transactions')->name('transactions.')->group(function () {
        // Read access for all company users
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        
        // Write access - Manager+
        Route::middleware('company.manager')->group(function () {
            Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
            Route::patch('/{transaction}', [TransactionController::class, 'update'])->name('update');
            
            // Transaction actions
            Route::post('/{transaction}/verify', [TransactionController::class, 'verify'])->name('verify');
            Route::post('/{transaction}/unverify', [TransactionController::class, 'unverify'])->name('unverify');
            Route::post('/{transaction}/rematch', [TransactionController::class, 'rematch'])->name('rematch');
            Route::post('/{transaction}/unmatch', [TransactionController::class, 'unmatch'])->name('unmatch');
            
            // Bulk actions
            Route::post('/bulk-verify', [TransactionController::class, 'bulkVerify'])->name('bulk-verify');
            Route::post('/bulk-rematch', [TransactionController::class, 'bulkRematch'])->name('bulk-rematch');
            Route::post('/bulk-categorize', [TransactionController::class, 'bulkCategorize'])->name('bulk-categorize');
            
            // Delete
            Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Bank Statements Management
    |--------------------------------------------------------------------------
    | Read: All company users
    | Write: Admin+
    */
    
    Route::prefix('bank-statements')->name('bank-statements.')->group(function () {
        // Read access for all company users
        Route::get('/', [BankStatementController::class, 'index'])->name('index');
        Route::get('/create', [BankStatementController::class, 'create'])->name('create');
        Route::post('/', [BankStatementController::class, 'store'])->name('store');
        Route::get('/{bankStatement}', [BankStatementController::class, 'show'])->name('show');
        Route::get('/{bankStatement}/download', [BankStatementController::class, 'download'])->name('download');
        
        // Statistics
        Route::get('/stats/summary', [BankStatementController::class, 'statistics'])->name('statistics');
        
        // AJAX - Keywords search
        Route::get('/keywords/search', [BankStatementController::class, 'searchKeywords'])->name('keywords.search');
        
        // Write access - Admin+
        Route::middleware('company.admin')->group(function () {
            // Super Admin can select company
            Route::get('/select-company', [BankStatementController::class, 'selectCompany'])
                ->name('select-company')
                ->middleware('super_admin');
            
            Route::get('/create', [BankStatementController::class, 'create'])->name('create');
            Route::post('/', [BankStatementController::class, 'store'])->name('store');
            Route::get('/{bankStatement}/edit', [BankStatementController::class, 'edit'])->name('edit');
            Route::put('/{bankStatement}', [BankStatementController::class, 'update'])->name('update');
            Route::delete('/{bankStatement}', [BankStatementController::class, 'destroy'])->name('destroy');
            
            // Validation
            Route::get('/{bankStatement}/validate', [BankStatementController::class, 'validateView'])->name('validate');
            
            // OCR Operations
            Route::post('/{bankStatement}/reprocess', [BankStatementController::class, 'reprocess'])->name('reprocess');
            Route::post('/{bankStatement}/retry', [BankStatementController::class, 'retryOCR'])->name('retry');
            
            // Matching Operations
            Route::post('/{bankStatement}/match-transactions', [BankStatementController::class, 'matchTransactions'])->name('match-transactions');
            Route::post('/{bankStatement}/match-accounts', [BankStatementController::class, 'matchAccounts'])->name('match-accounts');
            Route::post('/{bankStatement}/rematch-all', [BankStatementController::class, 'rematchAll'])->name('rematch-all');
            Route::post('/{bankStatement}/rematch-accounts', [BankStatementController::class, 'rematchAccounts'])->name('rematch-accounts');
            
            // Verification Operations
            Route::post('/{bankStatement}/verify-all-matched', [BankStatementController::class, 'verifyAllMatched'])->name('verify-all-matched');
            Route::post('/{bankStatement}/verify-high-confidence', [BankStatementController::class, 'verifyHighConfidence'])->name('verify-high-confidence');
            
            // Reconciliation
            Route::post('/{bankStatement}/reconcile', [BankStatementController::class, 'reconcile'])->name('reconcile');
            Route::post('/{bankStatement}/unreconcile', [BankStatementController::class, 'unreconcile'])->name('unreconcile');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Statement Transactions Validation Actions (AJAX)
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('statement-transactions')->name('statement-transactions.')->middleware('company.admin')->group(function () {
      // Approve AI suggestion
        Route::post('/{transaction}/approve', [BankStatementController::class, 'approveTransaction'])
            ->name('approve');
        
        // Manual keyword assignment
        Route::post('/{transaction}/set-keyword', [BankStatementController::class, 'setKeywordManually'])
            ->name('set-keyword');
        
        // Search keywords for Select2
        Route::get('/search-keywords', [BankStatementController::class, 'searchKeywords'])
            ->name('search-keywords');

    });

    /*
    |--------------------------------------------------------------------------
    | Document Collections Management (AI Chat Context)
    |--------------------------------------------------------------------------
    | All company users can manage their collections
    */
    
    Route::prefix('document-collections')->name('document-collections.')->group(function () {
        Route::get('/', [DocumentCollectionController::class, 'index'])->name('index');
        Route::get('/create', [DocumentCollectionController::class, 'create'])->name('create');
        Route::post('/', [DocumentCollectionController::class, 'store'])->name('store');
        Route::get('/{documentCollection}', [DocumentCollectionController::class, 'show'])->name('show');
        Route::get('/{documentCollection}/edit', [DocumentCollectionController::class, 'edit'])->name('edit');
        Route::put('/{documentCollection}', [DocumentCollectionController::class, 'update'])->name('update');
        Route::delete('/{documentCollection}', [DocumentCollectionController::class, 'destroy'])->name('destroy');
        
        // Collection actions
        Route::post('/{documentCollection}/toggle-active', [DocumentCollectionController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{documentCollection}/process', [DocumentCollectionController::class, 'process'])->name('process');
        Route::get('/{documentCollection}/statistics', [DocumentCollectionController::class, 'statistics'])->name('statistics');
        Route::post('/{documentCollection}/start-chat', [DocumentCollectionController::class, 'startChat'])->name('start-chat');
        Route::get('/{documentCollection}/items', [DocumentCollectionController::class, 'items'])->name('items');
    });

    /*
    |--------------------------------------------------------------------------
    | Document Items Management
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('document-items')->name('document-items.')->group(function () {
        Route::post('/', [DocumentItemController::class, 'store'])->name('store');
        Route::get('/{documentItem}', [DocumentItemController::class, 'show'])->name('show');
        Route::put('/{documentItem}', [DocumentItemController::class, 'update'])->name('update');
        Route::delete('/{documentItem}', [DocumentItemController::class, 'destroy'])->name('destroy');
        Route::post('/{documentItem}/retry', [DocumentItemController::class, 'retry'])->name('retry');
        Route::post('/{documentItem}/sync-metadata', [DocumentItemController::class, 'syncMetadata'])->name('sync-metadata');
        Route::post('/{documentItem}/move-up', [DocumentItemController::class, 'moveUp'])->name('move-up');
        Route::post('/{documentItem}/move-down', [DocumentItemController::class, 'moveDown'])->name('move-down');
        Route::post('/bulk-process', [DocumentItemController::class, 'bulkProcess'])->name('bulk-process');
        Route::post('/bulk-delete', [DocumentItemController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/reorder', [DocumentItemController::class, 'reorder'])->name('reorder');
    });

    /*
    |--------------------------------------------------------------------------
    | AI Chat Sessions Management
    |--------------------------------------------------------------------------
    | All company users can use AI chat
    */
    
    Route::prefix('chat-sessions')->name('chat-sessions.')->group(function () {
        Route::get('/', [ChatSessionController::class, 'index'])->name('index');
        Route::get('/create', [ChatSessionController::class, 'create'])->name('create');
        Route::post('/', [ChatSessionController::class, 'store'])->name('store');
        Route::get('/{chatSession}', [ChatSessionController::class, 'show'])->name('show');
        Route::put('/{chatSession}', [ChatSessionController::class, 'update'])->name('update');
        Route::delete('/{chatSession}', [ChatSessionController::class, 'destroy'])->name('destroy');
        Route::post('/{chatSession}/archive', [ChatSessionController::class, 'archive'])->name('archive');
        Route::post('/{chatSession}/unarchive', [ChatSessionController::class, 'unarchive'])->name('unarchive');
        Route::post('/{chatSession}/pin', [ChatSessionController::class, 'pin'])->name('pin');
        Route::post('/{chatSession}/unpin', [ChatSessionController::class, 'unpin'])->name('unpin');
        Route::post('/{chatSession}/update-title', [ChatSessionController::class, 'updateTitle'])->name('update-title');
        Route::get('/{chatSession}/messages', [ChatSessionController::class, 'messages'])->name('messages');
        Route::post('/{chatSession}/messages', [ChatSessionController::class, 'sendMessage'])->name('send-message');
        Route::get('/{chatSession}/statistics', [ChatSessionController::class, 'statistics'])->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Chat Messages Management
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('chat-messages')->name('chat-messages.')->group(function () {
        Route::get('/{chatMessage}', [ChatMessageController::class, 'show'])->name('show');
        Route::delete('/{chatMessage}', [ChatMessageController::class, 'destroy'])->name('destroy');
        Route::post('/{chatMessage}/rate', [ChatMessageController::class, 'rate'])->name('rate');
        Route::post('/{chatMessage}/feedback', [ChatMessageController::class, 'feedback'])->name('feedback');
        Route::post('/{chatMessage}/regenerate', [ChatMessageController::class, 'regenerate'])->name('regenerate');
    });

    /*
    |--------------------------------------------------------------------------
    | Reports - Manager+
    |--------------------------------------------------------------------------
    | Access: Manager, Admin, Owner
    */
    
    Route::prefix('reports')->name('reports.')->middleware('company.manager')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/monthly-by-bank', [ReportController::class, 'monthlyByBank'])->name('monthly-by-bank');
        Route::get('/by-keyword', [ReportController::class, 'byKeyword'])->name('by-keyword');
        Route::get('/by-category', [ReportController::class, 'byCategory'])->name('by-category');
        Route::get('/by-sub-category', [ReportController::class, 'bySubCategory'])->name('by-sub-category');
        Route::get('/by-account', [ReportController::class, 'byAccount'])->name('by-account');
        Route::get('/comparison', [ReportController::class, 'comparison'])->name('comparison');
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/trends', [ReportController::class, 'trends'])->name('trends');
        Route::post('/export', [ReportController::class, 'export'])->name('export');
        Route::post('/generate-pdf', [ReportController::class, 'generatePdf'])->name('generate-pdf');
    });
});

/*
|--------------------------------------------------------------------------
| Company Admin Routes
|--------------------------------------------------------------------------
| Access: Owner, Admin (NOT super_admin)
| Middleware: auth, verified, company.admin
*/

 Route::middleware(['auth', 'verified'])->prefix('subscription')->name('subscription.')->group(function () {
        
        // View routes - ALL members
        Route::get('/', [CompanySubscriptionController::class, 'index'])->name('index');
        Route::get('/plans', [CompanySubscriptionController::class, 'plans'])->name('plans');
        Route::get('/billing-history', [CompanySubscriptionController::class, 'billingHistory'])->name('billing-history');
        
        // Action routes - Owner only
        Route::middleware('company.owner')->group(function () {
            Route::post('/change-plan', [CompanySubscriptionController::class, 'changePlan'])->name('change-plan');
            Route::post('/cancel', [CompanySubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('/resume', [CompanySubscriptionController::class, 'resume'])->name('resume');
        });
    });

Route::middleware(['auth', 'verified', 'company.admin'])->group(function () {
    
    // Clear Dashboard Cache
    Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
    
    /*
    |--------------------------------------------------------------------------
    | Company Subscription Management (Admin+)
    |--------------------------------------------------------------------------
    | NEW: Company admins can view and manage their own subscription
    */
        
    // User Management (Company Admin)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/toggle-role', [UserManagementController::class, 'toggleRole'])->name('toggle-role');
        Route::post('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
    });

    /*
    |--------------------------------------------------------------------------
    | Accounts Management - Admin+
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}', [AccountController::class, 'show'])->name('show');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        Route::post('/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{account}/rematch', [AccountController::class, 'rematch'])->name('rematch');
        Route::get('/{account}/statistics', [AccountController::class, 'statistics'])->name('statistics');
        Route::get('/{account}/keywords', [AccountController::class, 'keywords'])->name('keywords');
    });

    /*
    |--------------------------------------------------------------------------
    | Account Keywords Routes
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('account-keywords')->name('account-keywords.')->group(function () {
        Route::get('/create', [AccountKeywordController::class, 'create'])->name('create');
        Route::post('/', [AccountKeywordController::class, 'store'])->name('store');
        Route::get('/{accountKeyword}/edit', [AccountKeywordController::class, 'edit'])->name('edit');
        Route::put('/{accountKeyword}', [AccountKeywordController::class, 'update'])->name('update');
        Route::delete('/{accountKeyword}', [AccountKeywordController::class, 'destroy'])->name('destroy');
        Route::post('/{accountKeyword}/toggle-status', [AccountKeywordController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/test', [AccountKeywordController::class, 'test'])->name('test');
        Route::post('/bulk-store', [AccountKeywordController::class, 'bulkStore'])->name('bulk-store');
    });

    /*
    |--------------------------------------------------------------------------
    | Keyword Suggestions - Admin+
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('keyword-suggestions')->name('keyword-suggestions.')->group(function () {
        Route::get('/{bankStatement}/analyze', [KeywordSuggestionController::class, 'analyze'])->name('analyze');
        Route::post('/create', [KeywordSuggestionController::class, 'createFromSuggestion'])->name('create');
        Route::post('/batch-create', [KeywordSuggestionController::class, 'batchCreate'])->name('batch-create');
        Route::post('/dismiss', [KeywordSuggestionController::class, 'dismiss'])->name('dismiss');
        Route::post('/preview', [KeywordSuggestionController::class, 'preview'])->name('preview');
        Route::get('/{bankStatement}/export', [KeywordSuggestionController::class, 'export'])->name('export');
        Route::post('/{bankStatement}/refresh', [KeywordSuggestionController::class, 'refresh'])->name('refresh');
    });

    /*
    |--------------------------------------------------------------------------
    | Master Data Management (Company Admin Only)
    |--------------------------------------------------------------------------
    */
    
    // Banks Management
    Route::resource('banks', BankController::class);
    Route::post('banks/{bank}/toggle-active', [BankController::class, 'toggleActive'])->name('banks.toggle-active');

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
    Route::post('keywords/{keyword}/toggle-active', [KeywordController::class, 'toggleActive'])->name('keywords.toggle-active');
    Route::post('keywords/bulk-update-priority', [KeywordController::class, 'bulkUpdatePriority'])->name('keywords.bulk-update-priority');
    Route::post('keywords/test', [KeywordController::class, 'test'])->name('keywords.test');
    
    // Company Settings (Admin Only)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function () {
            return view('settings.index');
        })->name('index');
        
        Route::get('/general', function () {
            return view('settings.general');
        })->name('general');
    });
});




/*
|--------------------------------------------------------------------------
| API Routes (for AJAX calls)
|--------------------------------------------------------------------------
| Access: Authenticated users with API token
*/

Route::middleware(['auth:sanctum'])->prefix('api/v1')->name('api.')->group(function () {
    
    // Categories API
    Route::get('/types/{type}/categories', [CategoryController::class, 'apiGetByType'])->name('categories.by-type');
    Route::get('/categories/{category}/sub-categories', [SubCategoryController::class, 'apiGetByCategory'])->name('sub-categories.by-category');
    
    // Accounts API
    Route::get('/accounts/search', [AccountController::class, 'apiSearch'])->name('accounts.search');
    
    // Transactions API
    Route::get('/transactions/search', [TransactionController::class, 'apiSearch'])->name('transactions.search');
    Route::get('/transactions/{transaction}/matching-logs', [TransactionController::class, 'apiGetMatchingLogs'])->name('transactions.matching-logs');
    
    // Bank Statements API
    Route::get('/bank-statements/{bankStatement}/progress', [BankStatementController::class, 'apiGetProgress'])->name('bank-statements.progress');
    
    // Chat API
    Route::post('/chat/stream', [ChatSessionController::class, 'apiStreamMessage'])->name('chat.stream');
});

require __DIR__.'/auth.php';