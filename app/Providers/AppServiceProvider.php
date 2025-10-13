<?php

namespace App\Providers;
use App\Models\StatementTransaction;
use App\Models\TransactionCategory;

use App\Observers\TransactionObserver;
use App\Observers\TransactionCategoryObserver;

use App\Services\OcrService;
use App\Services\KeywordSuggestionService;
use App\Services\TransactionMatchingService;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TransactionMatchingService::class);
        $this->app->singleton(OcrService::class);
        $this->app->singleton(KeywordSuggestionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StatementTransaction::observe(TransactionObserver::class);
        TransactionCategory::observe(TransactionCategoryObserver::class);
    }
}
