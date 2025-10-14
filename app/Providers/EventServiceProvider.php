<?php

namespace App\Providers;

use App\Events\BankStatementOcrCompleted;
use App\Events\TransactionMatchingCompleted;
use App\Events\AccountMatchingCompleted;
use App\Listeners\StartTransactionMatching; // ✅ Already exists
use App\Listeners\TriggerAccountMatching; // ✅ New
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ✅ STEP 1: OCR Completed → Trigger Transaction Matching
        BankStatementOcrCompleted::class => [
            StartTransactionMatching::class, // Already exists in your repo
        ],

        // ✅ STEP 2: Transaction Matching Completed → Trigger Account Matching
        TransactionMatchingCompleted::class => [
            TriggerAccountMatching::class, // NEW - you need to create this
        ],

        // ✅ STEP 3: Account Matching Completed → (Optional: Notifications, Analytics, etc)
        AccountMatchingCompleted::class => [
            // Add your listeners here if needed
            // e.g., SendMatchingCompletedNotification::class
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