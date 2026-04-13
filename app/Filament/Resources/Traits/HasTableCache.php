<?php

namespace App\Filament\Resources\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait HasTableCache
{
    /**
     * Cache duration in seconds (default: 5 minutes)
     */
    protected static int $cacheDuration = 300;

    /**
     * Cache tags untuk grouping
     */
    protected static array $cacheTags = [];

    /**
     * Generate cache key berdasarkan query dan user
     */
    protected static function generateCacheKey(string $suffix = ''): string
    {
        $userId = auth()->id() ?? 'guest';
        $url = request()->fullUrl();
        $query = request()->query();

        // Hanya cache key yang relevan
        $cacheKey = sprintf(
            '%s:%s:%s:%s',
            static::class,
            $userId,
            md5(serialize($query)),
            $suffix
        );

        return $cacheKey;
    }

    /**
     * Cache query builder untuk table
     */
    protected static function cacheTableQuery(Builder $query, callable $callback): Builder
    {
        $cacheKey = static::generateCacheKey('table_query');

        return Cache::tags(static::$cacheTags)
            ->remember($cacheKey, static::$cacheDuration, function () use ($query, $callback) {
                return $callback($query);
            });
    }

    /**
     * Cache hasil perhitungan/statistik
     */
    protected static function cacheCalculation(string $key, callable $callback): mixed
    {
        $cacheKey = static::generateCacheKey($key);

        return Cache::tags(static::$cacheTags)
            ->remember($cacheKey, static::$cacheDuration, $callback);
    }

    /**
     * Clear cache untuk resource ini
     */
    public static function clearCache(): void
    {
        Cache::tags(static::$cacheTags)->flush();
    }

    /**
     * Set cache tags untuk resource
     */
    protected static function setCacheTags(string ...$tags): void
    {
        static::$cacheTags = array_merge(
            [class_basename(static::class)],
            $tags
        );
    }

    /**
     * Cache status - bisa di-disable untuk development
     */
    protected static function shouldUseCache(): bool
    {
        // Disable cache di local environment untuk development
        if (app()->environment('local') && config('app.debug')) {
            return config('cache.enable_in_development', false);
        }

        return true;
    }
}
