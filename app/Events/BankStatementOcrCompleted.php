<?php
// app/Events/BankStatementOcrCompleted.php

namespace App\Events;

use App\Models\BankStatement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankStatementOcrCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BankStatement $bankStatement;
    public array $ocrResult;
    public int $totalTransactions;

    /**
     * Create a new event instance.
     */
    public function __construct(
        BankStatement $bankStatement,
        array $ocrResult,
        int $totalTransactions
    ) {
        $this->bankStatement = $bankStatement;
        $this->ocrResult = $ocrResult;
        $this->totalTransactions = $totalTransactions;
    }
}