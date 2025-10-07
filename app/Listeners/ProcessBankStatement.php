<?php

namespace App\Listeners;

use App\Events\BankStatementUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessBankStatement
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
    public function handle(BankStatementUploaded $event): void
    {
        //
    }
}
