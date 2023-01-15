<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Iterator;
use Amp\Redis\Redis as RedisRedis;
use danog\MadelineProto\Db\Driver\Redis;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;

/**
 * Redis database backend.
 */
class RedisArray extends DriverArray
{
    protected DatabaseRedis $dbSettings;
    private RedisRedis $db;

    // Legacy
    protected array $settings;

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

    protected function renameTable(string $from, string $to): void
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
    public function set(string|int $index, mixed $value): void
    {
        if ($this->hasCache($index) && $this->getCache($index) === $value) {
            return;
        }

        $this->setCache($index, $value);

        $this->db->set($this->rKey($index), \serialize($value));
        $this->setCache($index, $value);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $offset = (string) $offset;
        if ($this->hasCache($offset)) {
            return $this->getCache($offset);
        }

        $value = $this->db->get($this->rKey($offset));

        if ($value !== null && $value = \unserialize($value)) {
            $this->setCache($offset, $value);
        }

        return $value;
    }

    public function unset(string|int $key): void
    {
        $this->unsetCache($key);

        $this->db->delete($this->rkey($key));
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
            yield \substr($key, $len) => \unserialize($this->db->get($key));
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
        $this->clearCache();
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
