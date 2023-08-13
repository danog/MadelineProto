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

use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
use danog\MadelineProto\Settings\DatabaseAbstract;
use Traversable;

/**
 * Array caching proxy.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @implements DbArray<TKey, TValue>
 */
final class CachedArray implements DbArray
{
    /** @use DbArrayTrait<TKey, TValue> */
    use DbArrayTrait;

    private CacheContainer $cache;

    /**
     * Get instance.
     */
    public static function getInstance(string $table, DbArray|null $previous, DatabaseAbstract $settings): DbArray
    {
        $new = $settings->getDriverClass();
        if ($previous === null) {
            $previous = new self($new::getInstance($table, null, $settings));
        } elseif ($previous instanceof self) {
            $previous->cache->inner = $new::getInstance($table, $previous->cache->inner, $settings);
        } else {
            $previous = new self($new::getInstance($table, $previous, $settings));
        }
        if ($previous->cache->inner instanceof MemoryArray) {
            $previous->cache->flushCache();
            return $previous->cache->inner;
        }
        \assert($settings instanceof DriverDatabaseAbstract);
        $previous->cache->startCacheCleanupLoop($settings->getCacheTtl());
        return $previous;
    }

    public function __construct(DbArray $inner)
    {
        $this->cache = new CacheContainer($inner);
    }

    public function __destruct()
    {
        $this->cache->stopCacheCleanupLoop();
    }

    public function count(): int
    {
        return $this->cache->count();
    }

    public function clear(): void
    {
        $this->cache->clear();
    }

    public function offsetGet(mixed $index): mixed
    {
        return $this->cache->get($index);
    }

    public function set(string|int $key, mixed $value): void
    {
        $this->cache->set($key, $value);
    }

    public function unset(string|int $key): void
    {
        $this->cache->set($key, null);
    }

    public function getIterator(): Traversable
    {
        return $this->cache->getIterator();
    }
}
