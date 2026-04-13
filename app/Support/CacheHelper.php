<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheHelper
{
    /**
     * Cache duration dalam detik
     */
    const SHORT = 60;      // 1 menit

    const MEDIUM = 300;    // 5 menit

    const LONG = 900;      // 15 menit

    const DAILY = 86400;   // 24 jam

    /**
     * Cache tags umum
     */
    const TAG_PENJUALAN = 'penjualan';

    const TAG_PEMBELIAN = 'pembelian';

    const TAG_TUKAR_TAMBAH = 'tukar_tambah';

    const TAG_MASTER = 'master_data';

    /**
     * Check apakah Redis tersedia
     */
    protected static function redisAvailable(): bool
    {
        if (config('cache.default') !== 'redis') {
            return false;
        }

        try {
            Redis::connection('cache')->ping();

            return true;
        } catch (\Exception $e) {
            // Redis tidak tersedia
            return false;
        }
    }

    /**
     * Get cache store yang tersedia
     */
    protected static function getCacheStore()
    {
        if (self::redisAvailable()) {
            return Cache::store('redis');
        }

        // Fallback ke cache driver default
        return Cache::store();
    }

    /**
     * Generate cache key yang konsisten
     */
    public static function key(string $type, string $identifier, array $params = []): string
    {
        $key = "arabica:{$type}:{$identifier}";

        if (! empty($params)) {
            $key .= ':'.md5(serialize($params));
        }

        return $key;
    }

    /**
     * Remember dengan tags
     */
    public static function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed
    {
        try {
            $cache = self::getCacheStore();

            if (empty($tags) || ! self::redisAvailable()) {
                // Fallback tanpa tags kalau Redis tidak tersedia
                return $cache->remember($key, $ttl, $callback);
            }

            return $cache->tags($tags)->remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            // Kalau cache error, langsung return callback (tanpa cache)
            Log::warning('Cache error, fallback to direct callback: '.$e->getMessage());

            return $callback();
        }
    }

    /**
     * Hapus cache berdasarkan tags
     */
    public static function flush(array $tags): void
    {
        try {
            if (! self::redisAvailable()) {
                // Kalau tidak pakai Redis, coba clear all
                self::getCacheStore()->flush();

                return;
            }

            foreach ($tags as $tag) {
                try {
                    Cache::tags([$tag])->flush();
                } catch (\Exception $e) {
                    // Tag mungkin tidak support di driver tertentu
                }
            }
        } catch (\Exception $e) {
            Log::warning('Cache flush error: '.$e->getMessage());
        }
    }

    /**
     * Cache untuk data statis (master data)
     */
    public static function masterData(string $key, callable $callback)
    {
        return self::remember(
            self::key('master', $key),
            self::DAILY,
            $callback,
            [self::TAG_MASTER]
        );
    }

    /**
     * Cache untuk perhitungan transaksi
     */
    public static function calculation(string $type, int $id, callable $callback)
    {
        return self::remember(
            self::key('calc', $type, ['id' => $id]),
            self::MEDIUM,
            $callback,
            [$type]
        );
    }

    /**
     * Get cache info untuk debugging
     */
    public static function info(): array
    {
        try {
            if (self::redisAvailable()) {
                $redis = Redis::connection('cache');

                return [
                    'driver' => 'redis',
                    'status' => 'connected',
                    'keys_count' => $redis->command('DBSIZE'),
                    'memory_used' => $redis->command('INFO', ['memory'])['memory']['used_memory_human'] ?? 'N/A',
                ];
            }
        } catch (\Exception $e) {
            // Redis tidak tersedia
        }

        return [
            'driver' => config('cache.default'),
            'status' => 'fallback_mode',
            'message' => 'Redis not available, using '.config('cache.default'),
        ];
    }

    /**
     * Check apakah cache enabled
     */
    public static function enabled(): bool
    {
        // Disable cache di local debug mode kecuali di-config explicit
        if (app()->environment('local') && config('app.debug')) {
            return config('cache.enable_in_dev', false);
        }

        return ! app()->environment('testing');
    }

    /**
     * Force clear semua cache Arabica
     */
    public static function clearAll(): void
    {
        try {
            if (! self::redisAvailable()) {
                // Kalau tidak pakai Redis, clear semua cache
                self::getCacheStore()->flush();

                return;
            }

            $tags = [
                self::TAG_PENJUALAN,
                self::TAG_PEMBELIAN,
                self::TAG_TUKAR_TAMBAH,
                self::TAG_MASTER,
            ];

            foreach ($tags as $tag) {
                try {
                    Cache::tags([$tag])->flush();
                } catch (\Exception $e) {
                    // Tag mungkin tidak support di driver tertentu
                }
            }

            // Fallback: clear prefix arabica
            $redis = Redis::connection('cache');
            $keys = $redis->command('KEYS', ['arabica:*']);
            if (! empty($keys)) {
                $redis->command('DEL', $keys);
            }
        } catch (\Exception $e) {
            Log::warning('Cache clear error: '.$e->getMessage());
        }
    }
}
