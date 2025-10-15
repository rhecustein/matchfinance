<?php
// app/Events/AccountMatchingCompleted.php

namespace App\Events;

use App\Models\BankStatement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountMatchingCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BankStatement $bankStatement;
    public int $matchedCount;
    public int $unmatchedCount;

    /**
     * Create a new event instance.
     */
    public function __construct(
        BankStatement $bankStatement,
        int $matchedCount,
        int $unmatchedCount
    ) {
        $this->bankStatement = $bankStatement;
        $this->matchedCount = $matchedCount;
        $this->unmatchedCount = $unmatchedCount;
    }
}