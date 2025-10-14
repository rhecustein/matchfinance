<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\BankStatementOcrCompleted;
use App\Listeners\StartTransactionMatching;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        BankStatementOcrCompleted::class => [
            StartTransactionMatching::class,
            // ✨ Easy to add more listeners:
            // SendOcrCompletedNotification::class,
            // UpdateCompanyStatistics::class,
            // TriggerAccountMatching::class,
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