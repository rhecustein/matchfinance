<?php

namespace App\Console\Commands;

use App\Models\BankStatement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command: Check OCR Status
 * Usage: php artisan ocr:status
 */
class CheckOCRStatus extends Command
{
    protected $signature = 'ocr:status {--bank= : Filter by bank slug}';
    protected $description = 'Check OCR processing status for all bank statements';

    public function handle()
    {
        $query = BankStatement::query();

        if ($this->option('bank')) {
            $query->whereHas('bank', function ($q) {
                $q->where('slug', $this->option('bank'));
            });
        }

        $stats = [
            'pending' => $query->clone()->where('ocr_status', 'pending')->count(),
            'processing' => $query->clone()->where('ocr_status', 'processing')->count(),
            'completed' => $query->clone()->where('ocr_status', 'completed')->count(),
            'failed' => $query->clone()->where('ocr_status', 'failed')->count(),
        ];

        $this->info('📊 OCR Processing Status:');
        $this->newLine();
        
        $this->table(
            ['Status', 'Count'],
            [
                ['⏳ Pending', $stats['pending']],
                ['🔄 Processing', $stats['processing']],
                ['✅ Completed', $stats['completed']],
                ['❌ Failed', $stats['failed']],
            ]
        );

        $this->newLine();
        $this->info('Total: ' . array_sum($stats) . ' statements');

        if ($stats['failed'] > 0) {
            $this->newLine();
            $this->warn('⚠️  There are failed statements. Run: php artisan ocr:retry-failed');
        }

        return 0;
    }
}

/**
 * Command: Retry Failed OCR
 * Usage: php artisan ocr:retry-failed
 */
class RetryFailedOCR extends Command
{
    protected $signature = 'ocr:retry-failed {--id= : Specific statement ID to retry}';
    protected $description = 'Retry failed OCR processing jobs';

    public function handle()
    {
        $query = BankStatement::where('ocr_status', 'failed');

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        $failedStatements = $query->get();

        if ($failedStatements->isEmpty()) {
            $this->info('✅ No failed statements found.');
            return 0;
        }

        $this->info("Found {$failedStatements->count()} failed statement(s).");
        
        if (!$this->confirm('Do you want to retry all failed statements?', true)) {
            return 0;
        }

        $bar = $this->output->createProgressBar($failedStatements->count());
        $bar->start();

        foreach ($failedStatements as $statement) {
            $statement->update([
                'ocr_status' => 'pending',
                'ocr_error' => null,
            ]);

            \App\Jobs\ProcessBankStatementOCR::dispatch($statement, $statement->bank->slug)
                ->onQueue('ocr-processing');

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ All failed statements have been queued for retry.');

        return 0;
    }
}

/**
 * Command: Clean Old Statements
 * Usage: php artisan ocr:clean --days=30
 */
class CleanOldStatements extends Command
{
    protected $signature = 'ocr:clean {--days=30 : Delete statements older than X days} {--dry-run : Preview without deleting}';
    protected $description = 'Clean old processed bank statements';

    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $date = now()->subDays($days);

        $query = BankStatement::where('created_at', '<', $date)
            ->where('ocr_status', 'completed');

        $count = $query->count();

        if ($count === 0) {
            $this->info('✅ No old statements found.');
            return 0;
        }

        $this->warn("Found {$count} statement(s) older than {$days} days.");

        if ($dryRun) {
            $this->info('🔍 DRY RUN - No data will be deleted.');
            
            $statements = $query->limit(10)->get();
            $this->table(
                ['ID', 'Bank', 'Filename', 'Created At'],
                $statements->map(function ($s) {
                    return [
                        $s->id,
                        $s->bank->name,
                        $s->original_filename,
                        $s->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            return 0;
        }

        if (!$this->confirm("Are you sure you want to delete {$count} statement(s)?", false)) {
            return 0;
        }

        $deleted = $query->delete();

        $this->info("✅ Deleted {$deleted} statement(s).");

        return 0;
    }
}

/**
 * Command: Queue Monitor
 * Usage: php artisan queue:monitor-status
 */
class QueueMonitorStatus extends Command
{
    protected $signature = 'queue:monitor-status';
    protected $description = 'Monitor queue status and statistics';

    public function handle()
    {
        $this->info('📊 Queue Statistics:');
        $this->newLine();

        // Jobs in queue
        $pendingJobs = DB::table('jobs')
            ->where('queue', 'ocr-processing')
            ->count();

        // Failed jobs
        $failedJobs = DB::table('failed_jobs')->count();

        // Recent jobs (last 24 hours)
        $recentJobs = BankStatement::where('created_at', '>=', now()->subDay())->count();
        $recentCompleted = BankStatement::where('ocr_status', 'completed')
            ->where('ocr_completed_at', '>=', now()->subDay())
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['⏳ Pending Jobs', $pendingJobs],
                ['❌ Failed Jobs', $failedJobs],
                ['📥 Uploaded (24h)', $recentJobs],
                ['✅ Completed (24h)', $recentCompleted],
            ]
        );

        // Average processing time
        $avgTime = BankStatement::whereNotNull('ocr_completed_at')
            ->whereNotNull('ocr_started_at')
            ->where('ocr_completed_at', '>=', now()->subDay())
            ->get()
            ->avg(function ($statement) {
                return $statement->ocr_completed_at->diffInSeconds($statement->ocr_started_at);
            });

        if ($avgTime) {
            $this->newLine();
            $this->info('⏱️  Average Processing Time: ' . round($avgTime, 2) . ' seconds');
        }

        return 0;
    }
}

/**
 * Command: Test OCR API
 * Usage: php artisan ocr:test-api {bank}
 */
class TestOCRApi extends Command
{
    protected $signature = 'ocr:test-api {bank : Bank slug (mandiri, bca, bni, bri, btn, cimb)} {file : Path to PDF file}';
    protected $description = 'Test OCR API connection with a sample file';

    public function handle()
    {
        $bank = $this->argument('bank');
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        if (!in_array($bank, ['mandiri', 'bca', 'bni', 'bri', 'btn', 'cimb'])) {
            $this->error("Invalid bank. Must be one of: mandiri, bca, bni, bri, btn, cimb");
            return 1;
        }

        $this->info("Testing OCR API for {$bank}...");
        $this->newLine();

        try {
            $apiUrl = "http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly/{$bank}";
            
            $this->line("API URL: {$apiUrl}");
            $this->line("File: {$file}");
            $this->line("Size: " . $this->formatBytes(filesize($file)));
            $this->newLine();

            $response = \Illuminate\Support\Facades\Http::timeout(120)
                ->attach('file', file_get_contents($file), basename($file))
                ->post($apiUrl);

            if ($response->successful()) {
                $this->info('✅ API Request Successful!');
                $this->newLine();
                
                $data = $response->json();
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                
                return 0;
            } else {
                $this->error('❌ API Request Failed!');
                $this->line("Status Code: {$response->status()}");
                $this->line("Response: " . $response->body());
                
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}