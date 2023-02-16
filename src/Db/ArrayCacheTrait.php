<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use Revolt\EventLoop;

/**
 * Array caching trait.
 *
 * @internal
 */
trait ArrayCacheTrait
{
    /**
     * @var array<mixed>
     */
    private array $cache = [];
    /**
     * @var array<int>
     */
    private array $ttl = [];

    private int $cacheTtl = 5 * 60;

    /**
     * Cache cleanup watcher ID.
     */
    private ?string $cacheCleanupId = null;

    protected function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }

    protected function getCache(string $key)
    {
        $this->ttl[$key] = \time() + $this->cacheTtl;
        return $this->cache[$key];
    }

    protected function hasCache(string $key): bool
    {
        return isset($this->ttl[$key]);
    }

    /**
     * Save item in cache.
     */
    protected function setCache(string $key, $value): void
    {
        $this->cache[$key] = $value;
        $this->ttl[$key] = \time() + $this->cacheTtl;
    }

    /**
     * Remove key from cache.
     */
    protected function unsetCache(string $key): void
    {
        unset($this->cache[$key], $this->ttl[$key]);
    }

    protected function startCacheCleanupLoop(): void
    {
        $this->cacheCleanupId = EventLoop::repeat(
            \max(1, $this->cacheTtl / 5),
            fn () => $this->cleanupCache(),
        );
    }
    protected function stopCacheCleanupLoop(): void
    {
        if ($this->cacheCleanupId) {
            EventLoop::cancel($this->cacheCleanupId);
            $this->cacheCleanupId = null;
        }
    }

    protected function clearCache(): void
    {
        $this->cache = [];
        $this->ttl = [];
    }

    /**
     * Remove all keys from cache.
     */
    private function cleanupCache(): void
    {
        $newValues = [];
        $newTtl = [];
        $now = \time();
        $oldCount = 0;
        foreach ($this->ttl as $key => $ttl) {
            if ($ttl < $now) {
                $oldCount++;
            } else {
                $newTtl[$key] = $this->ttl[$key];
                $newValues[$key] = $this->cache[$key];
            }
        }
        $this->ttl = $newTtl;
        $this->cache = $newValues;

        Logger::log(
            \sprintf(
                'cache for table: %s; keys left: %s; keys removed: %s',
                (string) $this,
                \count($this->cache),
                $oldCount,
            ),
            Logger::VERBOSE,
        );
    }
}
