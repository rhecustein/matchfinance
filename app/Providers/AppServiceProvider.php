<?php

namespace App\Providers;
use App\Models\StatementTransaction;
use App\Observers\TransactionObserver;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StatementTransaction::observe(TransactionObserver::class);
    }
}
