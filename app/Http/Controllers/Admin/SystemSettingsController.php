<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    /**
     * Display system settings
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $settings = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'database_connection' => config('database.default'),
        ];

        // System info
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_size' => $this->getDatabaseSize(),
            'storage_used' => $this->getStorageUsed(),
        ];

        return view('admin.settings.index', compact('settings', 'systemInfo'));
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', 'Cache berhasil dibersihkan.');
    }

    /**
     * Optimize application
     */
    public function optimize()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        Artisan::call('optimize');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return back()->with('success', 'Aplikasi berhasil dioptimasi.');
    }

    /**
     * Run database backup
     */
    public function backup()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        try {
            // This assumes you have spatie/laravel-backup installed
            Artisan::call('backup:run --only-db');

            return back()->with('success', 'Backup database berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    /**
     * View logs
     */
    public function logs()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $logFile = storage_path('logs/laravel.log');
        
        $logs = file_exists($logFile) 
            ? array_slice(file($logFile), -100) 
            : [];

        return view('admin.settings.logs', compact('logs'));
    }

    /**
     * Get database size
     */
    private function getDatabaseSize()
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $size = DB::select("
                SELECT SUM(data_length + index_length) as size
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);

            return round($size[0]->size / 1024 / 1024, 2) . ' MB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get storage used
     */
    private function getStorageUsed()
    {
        try {
            $size = 0;
            $files = Storage::allFiles('public');
            
            foreach ($files as $file) {
                $size += Storage::size($file);
            }

            return round($size / 1024 / 1024, 2) . ' MB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}