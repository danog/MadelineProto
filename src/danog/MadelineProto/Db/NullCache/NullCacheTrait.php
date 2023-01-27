<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\NullCache;

use RuntimeException;

/**
 * Trait that disables database caching.
 *
 * @internal
 */
trait NullCacheTrait
{
    protected function setCacheTtl(int $ttl): void
    {
    }
    protected function hasCache(string $key): bool
    {
        return false;
    }

    protected function getCache(string $key): void
    {
        throw new RuntimeException('Not implemented!');
    }

    /**
     * Save item in cache.
     */
    protected function setCache(string $key, $value): void
    {
    }

    /**
     * Remove key from cache.
     */
    protected function unsetCache(string $key): void
    {
    }

    protected function clearCache(): void
    {
    }

    protected function startCacheCleanupLoop(): void
    {
    }
    protected function stopCacheCleanupLoop(): void
    {
    }
}
