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
     * ✅ FIXED: Works with all cache drivers (database, file, array, redis)
     */
    public static function clearReports(): void
    {
        try {
            // ✅ Check if Redis is available
            if (self::isRedisDriver()) {
                // Use Redis-specific commands
                foreach (self::REPORT_KEYS as $pattern) {
                    $keys = Cache::getRedis()->keys($pattern . '*');
                    foreach ($keys as $key) {
                        // Remove prefix from key
                        $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                        Cache::forget($cleanKey);
                    }
                }
            } else {
                // ✅ Fallback: Clear all cache for non-Redis drivers
                // This is safe for database/file/array drivers
                Cache::flush();
            }

            Log::info('Report cache cleared');
        } catch (\Exception $e) {
            Log::warning('Failed to clear report cache with pattern, using flush', [
                'error' => $e->getMessage()
            ]);
            Cache::flush();
        }
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
     * ✅ FIXED: Works with all cache drivers
     */
    public static function clearByPattern(string $pattern): void
    {
        try {
            if (self::isRedisDriver()) {
                $keys = Cache::getRedis()->keys($pattern);
                foreach ($keys as $key) {
                    $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                    Cache::forget($cleanKey);
                }
                Log::info("Cache cleared by pattern: {$pattern}");
            } else {
                // For non-Redis drivers, clear all cache
                Log::warning("Pattern-based cache clearing not supported for current driver, using flush");
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache by pattern, using flush', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            Cache::flush();
        }
    }

    /**
     * Get cache statistics
     * ✅ FIXED: Works with all cache drivers
     */
    public static function getStats(): array
    {
        $stats = [
            'dashboard_cached' => 0,
            'reports_cached' => 0,
            'total_keys' => 0,
            'driver' => config('cache.default'),
        ];

        try {
            // Count dashboard caches
            foreach (self::DASHBOARD_KEYS as $key) {
                if (Cache::has($key)) {
                    $stats['dashboard_cached']++;
                }
            }

            // Count report caches (only for Redis)
            if (self::isRedisDriver()) {
                foreach (self::REPORT_KEYS as $pattern) {
                    $keys = Cache::getRedis()->keys($pattern . '*');
                    $stats['reports_cached'] += count($keys);
                }
            } else {
                // For non-Redis drivers, set to N/A
                $stats['reports_cached'] = 'N/A';
            }

            $stats['total_keys'] = is_numeric($stats['reports_cached']) 
                ? $stats['dashboard_cached'] + $stats['reports_cached']
                : $stats['dashboard_cached'];

        } catch (\Exception $e) {
            Log::warning('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    /**
     * Warm up dashboard cache
     */
    public static function warmupDashboard(): void
    {
        try {
            // This will be called by a scheduled job
            app(\App\Http\Controllers\DashboardController::class)->index();
            
            Log::info('Dashboard cache warmed up');
        } catch (\Exception $e) {
            Log::error('Failed to warm up dashboard cache', [
                'error' => $e->getMessage()
            ]);
        }
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

    /**
     * ✅ NEW: Check if current cache driver is Redis
     */
    private static function isRedisDriver(): bool
    {
        $driver = config('cache.default');
        return $driver === 'redis';
    }

    /**
     * ✅ NEW: Safe cache get with fallback
     */
    public static function safeGet(string $key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed, returning default', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * ✅ NEW: Safe cache put with fallback
     */
    public static function safePut(string $key, $value, $ttl = null): bool
    {
        try {
            if ($ttl) {
                Cache::put($key, $value, $ttl);
            } else {
                Cache::put($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ NEW: Safe cache remember with fallback
     */
    public static function safeRemember(string $key, $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed, executing callback directly', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }
}