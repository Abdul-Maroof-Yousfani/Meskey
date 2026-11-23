<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent builder jo SELECT queries ka result cache karta hai (App\Traits\Cacheable models ke liye).
 *
 * - Cache key = model ka cache version + SQL + bindings. Model me kuch bhi save / delete / restore ho
 *   to version badal jata hai -> purani saari keys bekaar, agli query database se taza data.
 * - Builder se hone wale mass update / delete / insert / increment bhi version badalte hain.
 * - Cache NAHI hota jab: query me with() (eager load) ho, lockForUpdate / sharedLock ho,
 *   DB transaction chal rahi ho, ya ->withoutCache() lagaya ho.
 * - Seedha DB::table('...') wali writes is builder se nahi guzarti -> waha Model::flushModelCache() chalayein.
 */
class CachedBuilder extends Builder
{
    protected bool $cacheDisabled = false;

    /** Is query ko cache se bypass karo (hamesha database se). */
    public function withoutCache(): static
    {
        $this->cacheDisabled = true;

        return $this;
    }

    public function get($columns = ['*'])
    {
        if (!$this->shouldCache()) {
            return parent::get($columns);
        }

        $model = $this->getModel();
        $base = $this->toBase();
        $key = $model::modelCacheKey(
            'q:' . md5($base->toSql() . '|' . serialize($base->getBindings()) . '|' . serialize((array) $columns))
        );

        return Cache::remember($key, $model::modelCacheTtl(), function () use ($columns) {
            return parent::get($columns);
        });
    }

    protected function shouldCache(): bool
    {
        return !$this->cacheDisabled
            && empty($this->eagerLoad)
            && $this->getQuery()->lock === null
            && DB::connection($this->getModel()->getConnectionName())->transactionLevel() === 0;
    }

    // ---------- writes jo builder se hoti hain: cache saaf ----------

    public function update(array $values)
    {
        return $this->flushAfter(parent::update($values));
    }

    public function delete()
    {
        return $this->flushAfter(parent::delete());
    }

    public function forceDelete()
    {
        return $this->flushAfter(parent::forceDelete());
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->flushAfter(parent::increment($column, $amount, $extra));
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->flushAfter(parent::decrement($column, $amount, $extra));
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        return $this->flushAfter(parent::upsert($values, $uniqueBy, $update));
    }

    public function insert(array $values)
    {
        return $this->flushAfter($this->toBase()->insert($values));
    }

    public function insertGetId(array $values, $sequence = null)
    {
        return $this->flushAfter($this->toBase()->insertGetId($values, $sequence));
    }

    protected function flushAfter($result)
    {
        $this->getModel()::flushModelCache();

        return $result;
    }
}
