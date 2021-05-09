<?php

namespace danog\MadelineProto\Db\NullCache;

/**
 * Trait that disables database caching.
 *
 * @internal
 */
trait NullCacheTrait
{
    /**
     * @return void
     */
    protected function getCache(string $key, $default = null)
    {
        return $default;
    }

    /**
     * Save item in cache.
     *
     * @param string $key
     * @param $value
     */
    protected function setCache(string $key, $value): void
    {
    }

    /**
     * Remove key from cache.
     *
     * @param string $key
     */
    protected function unsetCache(string $key): void
    {
    }

    protected function startCacheCleanupLoop(): void
    {
    }
    protected function stopCacheCleanupLoop(): void
    {
    }
}
