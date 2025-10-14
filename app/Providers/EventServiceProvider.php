<?php

namespace App\Providers;

use App\Events\BankStatementOcrCompleted;
use App\Events\TransactionMatchingCompleted;
use App\Events\AccountMatchingCompleted;
use App\Listeners\StartTransactionMatching;
use App\Listeners\StartAccountMatching; // ✅ New
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ✅ STEP 1: OCR Complete → Transaction Matching
        BankStatementOcrCompleted::class => [
            StartTransactionMatching::class, // HANYA INI SAJA
        ],

        // ✅ STEP 2: Transaction Matching Complete → Account Matching
        TransactionMatchingCompleted::class => [
            StartAccountMatching::class, // HANYA INI SAJA
        ],

        // ✅ STEP 3: Account Matching Complete (optional)
        AccountMatchingCompleted::class => [
            // Future: notifications, analytics, etc
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}