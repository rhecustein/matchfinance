<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BankParsers\BCAParser;
use App\Services\BankParsers\BNIParser;
use App\Services\BankParsers\BRIParser;
use App\Services\BankParsers\BTNParser;
use App\Services\BankParsers\CIMBParser;
use App\Services\BankParsers\MandiriParser;

class BankParserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register each bank parser as singleton
        $this->app->singleton(BCAParser::class, function ($app) {
            return new BCAParser();
        });

        $this->app->singleton(BNIParser::class, function ($app) {
            return new BNIParser();
        });

        $this->app->singleton(BRIParser::class, function ($app) {
            return new BRIParser();
        });

        $this->app->singleton(BTNParser::class, function ($app) {
            return new BTNParser();
        });

        $this->app->singleton(CIMBParser::class, function ($app) {
            return new CIMBParser();
        });

        $this->app->singleton(MandiriParser::class, function ($app) {
            return new MandiriParser();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}