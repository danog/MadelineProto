<?php

namespace danog\MadelineProto\Db;

use Amp\Loop;
use danog\MadelineProto\Logger;

trait ArrayCacheTrait
{
    /**
     * Values stored in this format:
     * [
     *      [
     *          'value' => mixed,
     *          'ttl' => int
     *      ],
     *      ...
     * ].
     * @var array
     */
    protected array $cache = [];
    protected string $ttl = '+5 minutes';
    private string $ttlCheckInterval = '+1 minute';

    protected function getCache(string $key, $default = null)
    {
        $cacheItem = $this->cache[$key] ?? null;
        $result = $default;

        if (\is_array($cacheItem)) {
            $result = $cacheItem['value'];
            $this->cache[$key]['ttl'] = \strtotime($this->ttl);
        }

        return $result;
    }

    /**
     * Save item in cache.
     *
     * @param string $key
     * @param $value
     */
    protected function setCache(string $key, $value): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'ttl' => \strtotime($this->ttl),
        ];
    }

    /**
     * Remove key from cache.
     *
     * @param string $key
     */
    protected function unsetCache(string $key): void
    {
        unset($this->cache[$key]);
    }

    protected function startCacheCleanupLoop(): void
    {
        Loop::repeat(\strtotime($this->ttlCheckInterval, 0) * 1000, fn () => $this->cleanupCache());
    }

    /**
     * Remove all keys from cache.
     */
    protected function cleanupCache(): void
    {
        $now = \time();
        $oldKeys = [];
        foreach ($this->cache as $cacheKey => $cacheValue) {
            if ($cacheValue['ttl'] < $now) {
                $oldKeys[] = $cacheKey;
            }
        }
        foreach ($oldKeys as $oldKey) {
            $this->unsetCache($oldKey);
        }

        Logger::log(
            \sprintf(
                "cache for table:%s; keys left: %s; keys removed: %s",
                $this->table,
                \count($this->cache),
                \count($oldKeys)
            ),
            Logger::VERBOSE
        );
    }
}
