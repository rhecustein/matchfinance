<?php

namespace App\Listeners;

use App\Events\KeywordSuggested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminOfSuggestion
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(KeywordSuggested $event): void
    {
        //
    }
}
