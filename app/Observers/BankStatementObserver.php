<?php

namespace App\Observers;

use App\Models\BankStatement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankStatementObserver
{
    /**
     * Handle the BankStatement "creating" event.
     */
    public function creating(BankStatement $bankStatement): void
    {
        // Generate UUID jika belum ada
        if (empty($bankStatement->uuid)) {
            $bankStatement->uuid = Str::uuid();
        }

        // Set default status
        $bankStatement->status = $bankStatement->status ?? 'pending';

        // Log pembuatan bank statement
        Log::info('Creating Bank Statement', [
            'company_id' => $bankStatement->company_id,
            'bank_id' => $bankStatement->bank_id,
            'statement_date' => $bankStatement->statement_date,
        ]);
    }

    /**
     * Handle the BankStatement "created" event.
     */
    public function created(BankStatement $bankStatement): void
    {
        // Logging atau trigger event lanjutan
        Log::info('Bank Statement Created', [
            'id' => $bankStatement->id,
            'uuid' => $bankStatement->uuid,
            'company_id' => $bankStatement->company_id,
        ]);
    }

    /**
     * Handle the BankStatement "updating" event.
     */
    public function updating(BankStatement $bankStatement): void
    {
        // Logging perubahan status
        if ($bankStatement->isDirty('status')) {
            Log::info('Bank Statement Status Changing', [
                'id' => $bankStatement->id,
                'old_status' => $bankStatement->getOriginal('status'),
                'new_status' => $bankStatement->status,
            ]);
        }
    }

    /**
     * Handle the BankStatement "updated" event.
     */
    public function updated(BankStatement $bankStatement): void
    {
        // Trigger matching atau proses lanjutan berdasarkan status
        $this->handleStatusBasedProcessing($bankStatement);
    }

    /**
     * Handle status-based processing
     */
    private function handleStatusBasedProcessing(BankStatement $bankStatement): void
    {
        // Contoh: Trigger OCR atau matching berdasarkan status
        switch ($bankStatement->status) {
            case 'ocr_ready':
                // Dispatch OCR job
                \App\Jobs\ProcessBankStatementOCR::dispatch($bankStatement)
                    ->onQueue('ocr-processing');
                break;

            case 'ocr_completed':
                // Trigger transaction matching
                \App\Jobs\ProcessTransactionMatching::dispatch($bankStatement)
                    ->onQueue('matching');
                break;

            case 'matching_completed':
                // Trigger account matching prep
                \App\Jobs\ProcessAccountMatchingPrep::dispatch($bankStatement)
                    ->onQueue('matching');
                break;
        }
    }

    /**
     * Handle the BankStatement "deleting" event.
     */
    public function deleting(BankStatement $bankStatement): void
    {
        // Optional: Hapus related data
        Log::info('Deleting Bank Statement', [
            'id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
        ]);

        // Contoh: Hapus transaksi terkait
        $bankStatement->transactions()->delete();
    }

    /**
     * Handle the BankStatement "restored" event.
     */
    public function restored(BankStatement $bankStatement): void
    {
        Log::info('Bank Statement Restored', [
            'id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
        ]);
    }

    /**
     * Handle the BankStatement "force deleted" event.
     */
    public function forceDeleted(BankStatement $bankStatement): void
    {
        Log::info('Bank Statement Force Deleted', [
            'id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
        ]);
    }
}