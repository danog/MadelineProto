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
    protected string $ttl = '+1 day';
    private string $ttlCheckInterval = '+1 second';
    private int $nextTtlCheckTs = 0;

    protected function getCache(string $key, $default = null)
    {
        if ($cacheItem = $this->cache[$key] ?? null) {
            $this->cache[$key]['ttl'] = strtotime($this->ttl);
        } else {
            return $default;
        }

        return $cacheItem['value'];
    }

    /**
     * Save item in cache
     *
     * @param string $key
     * @param $value
     */
    protected function setCache(string $key, $value): void
    {
        $now = time();
        $this->cache[$key] = [
            'value' => $value,
            'ttl' => $now,
        ];

        if ($this->nextTtlCheckTs < $now) {
            $this->nextTtlCheckTs = strtotime($this->ttlCheckInterval, $now);
            foreach ($this->cache as $cacheKey => $cacheValue) {
                if ($cacheValue['ttl'] < $now) {
                    $this->unsetCache($cacheKey);
                }
            }
        }
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

}