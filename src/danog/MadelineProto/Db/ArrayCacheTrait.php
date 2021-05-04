<?php

namespace danog\MadelineProto\Db;

use Amp\Loop;
use Closure;
use danog\MadelineProto\Logger;

/**
 * Array caching trait.
 */
trait ArrayCacheTrait
{
    /**
     * @var array<mixed>
     */
    protected array $cache = [];
    /**
     * @var array<int>
     */
    protected array $ttlValues = [];

    /**
     * TTL interval.
     */
    protected int $ttl = 5 * 60;

    /**
     * Cache cleanup watcher ID.
     */
    private ?string $cacheCleanupId = null;

    protected function getCache(string $key, $default = null)
    {
        if (!isset($this->ttlValues[$key])) {
            return $default;
        }
        $this->ttlValues[$key] = \time() + $this->ttl;
        return $this->cache[$key];
    }

    /**
     * Save item in cache.
     *
     * @param string $key
     * @param $value
     */
    protected function setCache(string $key, $value): void
    {
        $this->cache[$key] = $value;
        $this->ttlValues[$key] = \time() + $this->ttl;
    }

    /**
     * Remove key from cache.
     *
     * @param string $key
     */
    protected function unsetCache(string $key): void
    {
        unset($this->cache[$key], $this->ttlValues[$key]);
    }

    protected function startCacheCleanupLoop(): void
    {
        $this->cacheCleanupId = Loop::repeat(
            \max(1000, ($this->ttl * 1000) / 5),
            Closure::fromCallable([$this, 'cleanupCache']),
        );
    }
    protected function stopCacheCleanupLoop(): void
    {
        if ($this->cacheCleanupId) {
            Loop::cancel($this->cacheCleanupId);
            $this->cacheCleanupId = null;
        }
    }

    /**
     * Remove all keys from cache.
     */
    protected function cleanupCache(): void
    {
        $now = \time();
        $oldCount = 0;
        foreach ($this->ttlValues as $cacheKey => $ttl) {
            if ($ttl < $now) {
                $this->unsetCache($cacheKey);
                $oldCount++;
            }
        }

        Logger::log(
            \sprintf(
                "cache for table: %s; keys left: %s; keys removed: %s",
                (string) $this,
                \count($this->cache),
                $oldCount
            ),
            Logger::VERBOSE
        );
    }
}
