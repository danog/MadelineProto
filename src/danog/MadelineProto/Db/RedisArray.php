<?php

namespace danog\MadelineProto\Db;

use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use Amp\Redis\Redis as RedisRedis;
use Amp\Success;
use danog\MadelineProto\Db\Driver\Redis as Redis;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;
use Generator;

use function Amp\call;

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
     *
     */
    public function initStartup(): \Generator
    {
        return $this->initConnection($this->dbSettings);
    }
    /**
     *
     * @psalm-return Generator<int, Success<null>, mixed, void>
     */
    protected function prepareTable(): Generator
    {
        yield new Success;
    }

    protected function renameTable(string $from, string $to): \Generator
    {
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);
        $from = "va:$from";
        $to = "va:$to";

        $request = $this->db->scan($from.'*');

        $lenK = \strlen($from);
        while (yield $request->advance()) {
            $oldKey = $request->getCurrent();
            $newKey = $to.\substr($oldKey, $lenK);
            $value = yield $this->db->get($oldKey);
            $this->db->set($newKey, $value);
            $this->db->delete($oldKey);
        }
    }

    /**
     * Initialize connection.
     *
     * @param DatabaseRedis $settings
     */
    public function initConnection($settings): \Generator
    {
        if (!isset($this->db)) {
            $this->db = yield from Redis::getConnection($settings);
        }
    }

    /**
     * Get redis key name.
     *
     */
    private function rKey(string $key): string
    {
        return 'va:'.$this->table.':'.$key;
    }
    /**
     * Get redis ts name.
     *
     */
    private function tsKey(string $key): string
    {
        return 'ts:'.$this->table.$key;
    }

    /**
     * Get iterator key.
     *
     */
    private function itKey(): string
    {
        return 'va:'.$this->table.'*';
    }
    /**
     * Set value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetset.php
     *
     * @param string $index <p>
     * The index to set for.
     * </p>
     *
     * @throws \Throwable
     */
    public function set(string|int $index, mixed $value): Promise
    {
        if ($this->hasCache($index) && $this->getCache($index) === $value) {
            return new Success();
        }

        $this->setCache($index, $value);

        $request = $this->db->set($this->rKey($index), \serialize($value));

        //Ensure that cache is synced with latest insert in case of concurrent requests.
        $request->onResolve(fn () => $this->setCache($index, $value));

        return $request;
    }

    public function offsetGet(mixed $offset): Promise
    {
        $offset = (string) $offset;
        return call(function () use ($offset) {
            if ($this->hasCache($offset)) {
                return $this->getCache($offset);
            }

            $value = yield $this->db->get($this->rKey($offset));

            if ($value !== null && $value = \unserialize($value)) {
                $this->setCache($offset, $value);
            }

            return $value;
        });
    }

    public function unset(string|int $key): Promise
    {
        $this->unsetCache($key);

        return $this->db->delete($this->rkey($key));
    }

    /**
     * Get array copy.
     *
     * @return Promise<array>
     * @throws \Throwable
     */
    #[\ReturnTypeWillChange]
    public function getArrayCopy(): Promise
    {
        return call(function () {
            $iterator = $this->getIterator();
            $result = [];
            while (yield $iterator->advance()) {
                [$key, $value] = $iterator->getCurrent();
                $result[$key] = $value;
            }
            return $result;
        });
    }

    public function getIterator(): Iterator
    {
        return new Producer(function (callable $emit) {
            $request = $this->db->scan($this->itKey());

            $len = \strlen($this->rKey(''));
            while (yield $request->advance()) {
                $key = $request->getCurrent();
                yield $emit([\substr($key, $len), \unserialize(yield $this->db->get($key))]);
            }
        });
    }

    /**
     * Count elements.
     *
     * @link https://php.net/manual/en/arrayiterator.count.php
     * @return Promise<int> The number of elements or public properties in the associated
     * array or object, respectively.
     * @throws \Throwable
     */
    public function count(): Promise
    {
        return call(function () {
            $request = $this->db->scan($this->itKey());
            $count = 0;

            while (yield $request->advance()) {
                $count++;
            }

            return $count;
        });
    }

    /**
     * Clear all elements.
     *
     */
    public function clear(): Promise
    {
        $this->clearCache();
        return call(function () {
            $request = $this->db->scan($this->itKey());

            $keys = [];
            $k = 0;
            while (yield $request->advance()) {
                $keys[$k++] = $request->getCurrent();
                if ($k === 10) {
                    yield $this->db->delete(...$keys);
                    $keys = [];
                    $k = 0;
                }
            }
            if (!empty($keys)) {
                yield $this->db->delete(...$keys);
            }
        });
    }
}
