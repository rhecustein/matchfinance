<?php

namespace App\Events;

use App\Models\BankStatement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountMatchingCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public BankStatement $bankStatement,
        public int $matchedCount,
        public int $unmatchedCount
    ) {}
}