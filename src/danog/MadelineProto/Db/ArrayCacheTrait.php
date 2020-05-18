<?php

namespace danog\MadelineProto\Db;

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
     * ]
     * @var array
     */
    protected array $cache = [];
    protected string $ttl = '+5 minutes';
    private string $ttlCheckInterval = '+1 minute';
    private int $nextTtlCheckTs = 0;

    protected function getCache(string $key, $default = null)
    {
        $cacheItem = $this->cache[$key] ?? null;
        $result = $default;

        if (\is_array($cacheItem)) {
            $result = $cacheItem['value'];
            $this->cache[$key]['ttl'] = strtotime($this->ttl);
        }

        $this->cleanupCache();

        return $result;
    }

    /**
     * Save item in cache
     *
     * @param string $key
     * @param $value
     */
    protected function setCache(string $key, $value): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'ttl' => strtotime($this->ttl),
        ];

        $this->cleanupCache();
    }

    /**
     * Remove key from cache
     *
     * @param string $key
     */
    protected function unsetCache(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Remove all keys from cache
     */
    protected function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Remove all keys from cache
     */
    protected function cleanupCache(): void
    {
        $now = time();
        if ($this->nextTtlCheckTs > $now) {
            return;
        }

        $this->nextTtlCheckTs = strtotime($this->ttlCheckInterval, $now);
        $oldKeys = [];
        foreach ($this->cache as $cacheKey => $cacheValue) {
            if ($cacheValue['ttl'] < $now) {
                $oldKeys[] = $cacheKey;
            }
        }
        foreach ($oldKeys as $oldKey) {
            $this->unsetCache($oldKey);
        }
    }

}