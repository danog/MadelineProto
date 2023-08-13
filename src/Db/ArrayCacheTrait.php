<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

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
