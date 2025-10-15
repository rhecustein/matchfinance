<?php

namespace App\Providers;

use App\Models\StatementTransaction;
use App\Models\TransactionCategory;
use App\Models\BankStatement;

use App\Observers\TransactionObserver;
use App\Observers\TransactionCategoryObserver;
use App\Observers\BankStatementObserver;

use App\Services\OcrService;
use App\Services\KeywordSuggestionService;
use App\Services\TransactionMatchingService;
use App\Services\AccountMatchingService;

use App\Jobs\ProcessAccountMatchingPrep;
use App\Events\TransactionMatchingCompleted;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Existing services
        $this->app->singleton(TransactionMatchingService::class);
        $this->app->singleton(OcrService::class);
        $this->app->singleton(KeywordSuggestionService::class);
        
        // New service for account matching
        $this->app->singleton(AccountMatchingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Existing observers
        StatementTransaction::observe(TransactionObserver::class);
        TransactionCategory::observe(TransactionCategoryObserver::class);
        
        // Add BankStatement observer
        BankStatement::observe(BankStatementObserver::class);

        // Event listener for account matching prep
        Event::listen(TransactionMatchingCompleted::class, function ($event) {
            // Dispatch ProcessAccountMatchingPrep after transaction matching
            ProcessAccountMatchingPrep::dispatch($event->bankStatement)
                ->onQueue('matching')
                ->delay(now()->addSeconds(5));
        });
    }
}