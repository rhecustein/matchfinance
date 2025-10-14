<?php

/**
 * Artisan Command: Fix Company Status
 * 
 * File: app/Console/Commands/FixCompanyStatus.php
 * 
 * Usage:
 * php artisan company:fix-status
 * php artisan company:fix-status --dry-run    # Preview changes only
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class FixCompanyStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'company:fix-status 
                            {--dry-run : Preview changes without saving}';

    /**
     * The console command description.
     */
    protected $description = 'Fix company status - ensure all companies have proper status set';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Checking company status...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        DB::beginTransaction();
        
        try {
            $stats = [
                'total' => 0,
                'fixed' => 0,
                'already_ok' => 0,
                'with_subscription' => 0,
                'with_trial' => 0,
            ];

            $companies = Company::all();
            $stats['total'] = $companies->count();

            $this->info("Found {$stats['total']} companies");
            $this->newLine();

            foreach ($companies as $company) {
                $originalStatus = $company->status;
                $needsFix = false;
                $newStatus = $originalStatus;
                $reason = '';

                // Logic untuk menentukan status yang benar
                if ($company->activeSubscription && $company->activeSubscription->isActive()) {
                    // Punya subscription aktif -> status harus 'active'
                    if ($company->status !== 'active') {
                        $newStatus = 'active';
                        $needsFix = true;
                        $reason = 'Has active subscription';
                    }
                    $stats['with_subscription']++;
                } elseif ($company->trial_ends_at) {
                    // Punya trial_ends_at
                    if ($company->trial_ends_at->isFuture()) {
                        // Trial masih berlaku -> status harus 'trial'
                        if ($company->status !== 'trial') {
                            $newStatus = 'trial';
                            $needsFix = true;
                            $reason = 'Trial period still active';
                        }
                        $stats['with_trial']++;
                    } else {
                        // Trial sudah expired -> tapi belum ada subscription
                        // Biarkan tetap 'trial' tapi user akan diredirect ke subscription
                        if ($company->status === 'suspended' || $company->status === 'cancelled') {
                            // Don't change if manually suspended/cancelled
                            $stats['already_ok']++;
                        } else {
                            $stats['already_ok']++;
                        }
                    }
                } else {
                    // Tidak ada subscription dan tidak ada trial
                    // Set ke trial dengan default 14 hari jika belum ada status yang jelas
                    if (!in_array($company->status, ['active', 'trial', 'suspended', 'cancelled'])) {
                        $newStatus = 'trial';
                        $needsFix = true;
                        $reason = 'No subscription or trial - set to trial';
                        
                        if (!$dryRun) {
                            $company->trial_ends_at = now()->addDays(14);
                        }
                    } else {
                        $stats['already_ok']++;
                    }
                }

                // Apply fix jika diperlukan
                if ($needsFix) {
                    $this->line("📝 Company: {$company->name}");
                    $this->line("   Status: {$originalStatus} → {$newStatus}");
                    $this->line("   Reason: {$reason}");
                    
                    if (!$dryRun) {
                        $company->update(['status' => $newStatus]);
                        $this->info("   ✅ Fixed!");
                    } else {
                        $this->warn("   ⚠️  Would fix (dry-run)");
                    }
                    
                    $stats['fixed']++;
                    $this->newLine();
                } else {
                    $stats['already_ok']++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('⚠️  Rolled back - no changes saved (dry-run mode)');
            } else {
                DB::commit();
                $this->info('✅ All changes committed!');
            }

            // Show summary
            $this->newLine();
            $this->info('📊 Summary:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Companies', $stats['total']],
                    ['Fixed', $stats['fixed']],
                    ['Already OK', $stats['already_ok']],
                    ['With Active Subscription', $stats['with_subscription']],
                    ['With Active Trial', $stats['with_trial']],
                ]
            );

            if ($dryRun && $stats['fixed'] > 0) {
                $this->newLine();
                $this->info('💡 Run without --dry-run to apply changes:');
                $this->line('   php artisan company:fix-status');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return self::FAILURE;
        }
    }
}

// ============================================================================
// REGISTER COMMAND
// ============================================================================

/**
 * Register command di app/Console/Kernel.php
 * 
 * protected $commands = [
 *     \App\Console\Commands\FixCompanyStatus::class,
 * ];
 */