<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Dashboard cache keys
     */
    const DASHBOARD_KEYS = [
        'dashboard_admin_stats',
        'dashboard_transactions_by_type',
        'dashboard_monthly_trend',
        'dashboard_matching_stats',
        'dashboard_user_stats',
        'dashboard_user_transactions_by_type',
        'dashboard_user_monthly_trend',
    ];

    /**
     * Report cache keys
     */
    const REPORT_KEYS = [
        'report_monthly_by_bank_',
        'report_by_keyword_',
        'report_by_category_',
        'report_by_sub_category_',
        'report_comparison_',
    ];

    /**
     * Clear all dashboard caches
     */
    public static function clearDashboard(): void
    {
        foreach (self::DASHBOARD_KEYS as $key) {
            Cache::forget($key);
        }

        Log::info('Dashboard cache cleared');
    }

    /**
     * Clear all report caches
     */
    public static function clearReports(): void
    {
        // Clear report caches with wildcard pattern
        foreach (self::REPORT_KEYS as $pattern) {
            $keys = Cache::getRedis()->keys($pattern . '*');
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }

        Log::info('Report cache cleared');
    }

    /**
     * Clear specific report cache by type and year
     */
    public static function clearReportByTypeYear(string $type, int $year): void
    {
        $key = "report_{$type}_{$year}";
        Cache::forget($key);

        Log::info("Report cache cleared: {$key}");
    }

    /**
     * Clear all application caches
     */
    public static function clearAll(): void
    {
        Cache::flush();
        Log::info('All application cache cleared');
    }

    /**
     * Clear cache by pattern
     */
    public static function clearByPattern(string $pattern): void
    {
        $keys = Cache::getRedis()->keys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::info("Cache cleared by pattern: {$pattern}");
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $stats = [
            'dashboard_cached' => 0,
            'reports_cached' => 0,
            'total_keys' => 0,
        ];

        // Count dashboard caches
        foreach (self::DASHBOARD_KEYS as $key) {
            if (Cache::has($key)) {
                $stats['dashboard_cached']++;
            }
        }

        // Count report caches
        foreach (self::REPORT_KEYS as $pattern) {
            $keys = Cache::getRedis()->keys($pattern . '*');
            $stats['reports_cached'] += count($keys);
        }

        $stats['total_keys'] = $stats['dashboard_cached'] + $stats['reports_cached'];

        return $stats;
    }

    /**
     * Warm up dashboard cache
     */
    public static function warmupDashboard(): void
    {
        // This will be called by a scheduled job
        app(\App\Http\Controllers\DashboardController::class)->index();
        
        Log::info('Dashboard cache warmed up');
    }

    /**
     * Cache key generator for reports
     */
    public static function reportKey(string $type, array $params): string
    {
        ksort($params);
        $paramString = http_build_query($params);
        return "report_{$type}_" . md5($paramString);
    }
}