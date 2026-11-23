<?php

namespace App\Traits;

use App\Support\CachedBuilder;
use Illuminate\Support\Facades\Cache;

/**
 * Master data models ke liye automatic query cache.
 *
 *   class Product extends Model { use Cacheable; }
 *
 * - Is model ki har SELECT query (get / first / find / all / relations ...) khud cache hoti hai.
 *   Kisi controller me kuch likhne ki zaroorat nahi.
 * - Model create / update / delete / restore hote hi us ka "version" badalta hai -> purana cache
 *   khud bekaar, agli request database se taza data leti hai. (File cache par bhi chalta hai, tags nahi chahiye.)
 * - Sirf kam badalne wale master data par lagayein (locations, products, suppliers ...).
 *   Transaction wale models (tickets, orders, payments) par NAHI.
 *
 * Bypass:  Product::withoutCache()->where(...)->get();
 * Manual:  Product::flushModelCache();   // e.g. DB::table('products')->update(...) ke baad
 * TTL:     model me  protected int $cacheTtl = 3600;  (default 1 din)
 */
trait Cacheable
{
    public static function bootCacheable(): void
    {
        foreach (['created', 'updated', 'saved', 'deleted'] as $event) {
            static::registerModelEvent($event, fn () => static::flushModelCache());
        }

        // SoftDeletes wale models
        if (method_exists(static::class, 'restored')) {
            static::restored(fn () => static::flushModelCache());
        }
        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(fn () => static::flushModelCache());
        }
    }

    public function newEloquentBuilder($query)
    {
        return new CachedBuilder($query);
    }

    /** Is model ka sara cache bekaar karo (version +1). */
    public static function flushModelCache(): void
    {
        Cache::forever(static::modelCacheVersionKey(), static::modelCacheVersion() + 1);
    }

    public static function modelCacheVersion(): int
    {
        return (int) Cache::get(static::modelCacheVersionKey(), 1);
    }

    public static function modelCacheKey(string $suffix): string
    {
        return 'mc:' . (new static)->getTable() . ':v' . static::modelCacheVersion() . ':' . $suffix;
    }

    public static function modelCacheTtl(): int
    {
        $model = new static;

        return property_exists($model, 'cacheTtl') ? (int) $model->cacheTtl : 86400;
    }

    protected static function modelCacheVersionKey(): string
    {
        return 'mc:' . (new static)->getTable() . ':version';
    }
}
