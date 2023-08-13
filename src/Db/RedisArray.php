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

use Amp\Redis\Redis as RedisRedis;
use danog\MadelineProto\Db\Driver\Redis;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;

/**
 * Redis database backend.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 * @extends DriverArray<TKey, TValue>
 */
final class RedisArray extends DriverArray
{
    private RedisRedis $db;

    /**
     * Initialize on startup.
     */
    public function initStartup(): void
    {
        $this->initConnection($this->dbSettings);
    }
    protected function prepareTable(): void
    {
    }

    protected function moveDataFromTableToTable(string $from, string $to): void
    {
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);
        $from = "va:$from";
        $to = "va:$to";

        $request = $this->db->scan($from.'*');

        $lenK = \strlen($from);
        foreach ($request as $oldKey) {
            $newKey = $to.\substr($oldKey, $lenK);
            $value = $this->db->get($oldKey);
            $this->db->set($newKey, $value);
            $this->db->delete($oldKey);
        }
    }

    /**
     * Initialize connection.
     */
    public function initConnection(DatabaseRedis $settings): void
    {
        $this->db ??= Redis::getConnection($settings);
    }

    /**
     * Get redis key name.
     */
    private function rKey(string $key): string
    {
        return 'va:'.$this->table.':'.$key;
    }

    /**
     * Get iterator key.
     */
    private function itKey(): string
    {
        return 'va:'.$this->table.'*';
    }
    public function set(string|int $key, mixed $value): void
    {
        $this->db->set($this->rKey((string) $key), ($this->serializer)($value));
    }

    public function offsetGet(mixed $offset): mixed
    {
        $offset = (string) $offset;

        $value = $this->db->get($this->rKey($offset));

        if ($value !== null) {
            $value = ($this->deserializer)($value);
        }

        return $value;
    }

    public function unset(string|int $key): void
    {
        $this->db->delete($this->rkey((string) $key));
    }

    /**
     * Get iterator.
     *
     * @return \Traversable<array-key, mixed>
     */
    public function getIterator(): \Traversable
    {
        $request = $this->db->scan($this->itKey());

        $len = \strlen($this->rKey(''));
        foreach ($request as $key) {
            yield \substr($key, $len) => ($this->deserializer)($this->db->get($key));
        }
    }

    /**
     * Count elements.
     *
     * @link https://php.net/manual/en/arrayiterator.count.php
     * @return int The number of elements or public properties in the associated
     * array or object, respectively.
     */
    public function count(): int
    {
        return \iterator_count($this->db->scan($this->itKey()));
    }

    /**
     * Clear all elements.
     */
    public function clear(): void
    {
        $request = $this->db->scan($this->itKey());

        $keys = [];
        foreach ($request as $key) {
            $keys[] = $key;
            if (\count($keys) === 10) {
                $this->db->delete(...$keys);
                $keys = [];
            }
        }
        if ($keys) {
            $this->db->delete(...$keys);
        }
    }
}
