<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;
use Amp\Redis\Redis as RedisRedis;
use Amp\Success;
use danog\MadelineProto\Db\Driver\Redis as Redis;
use danog\MadelineProto\Logger;
use Generator;

use function Amp\call;

class RedisArray extends SqlArray
{
    protected string $table;
    protected array $settings;
    private RedisRedis $db;

    protected function prepareTable(): Generator
    {
        yield new Success;
    }


    protected function renameTable(string $from, string $to): \Generator
    {
        Logger::log("Renaming table {$from} to {$to}", Logger::WARNING);
        $from = "va:$from";
        $to = "va:$to";

        $request = $this->db->scan($from.'*');

        $lenK = \strlen($from);
        while (yield $request->advance()) {
            $key = $request->getCurrent();
            yield $this->db->rename($key, $to.\substr($key, $lenK));
        }
    }

    protected function initConnection(array $settings): \Generator
    {
        if (!isset($this->db)) {
            $this->db = yield from Redis::getConnection(
                $settings['host'],
                $settings['port'],
                $settings['password'],
                $settings['database']
            );
        }
    }

    public function __sleep()
    {
        return ['table', 'settings'];
    }
    /**
     * Get redis key name.
     *
     * @param string $key
     * @return string
     */
    private function rKey(string $key): string
    {
        return 'va:'.$this->table.':'.$key;
    }
    /**
     * Get redis ts name.
     *
     * @param string $key
     * @return string
     */
    private function tsKey(string $key): string
    {
        return 'ts:'.$this->table.$key;
    }

    /**
     * Get iterator key.
     *
     * @return string
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
     * @param $value
     *
     * @throws \Throwable
     */

    public function offsetSet($index, $value): Promise
    {
        if ($this->getCache($index) === $value) {
            return new Success();
        }

        $this->setCache($index, $value);

        /*
        $request = $this->db->setMultiple(
            [
                $this->rKey($index) => \serialize($value),
                $this->tsKey($index) => \time()
            ]
        );*/
        $request = $this->db->set($this->rKey($index), \serialize($value));

        //Ensure that cache is synced with latest insert in case of concurrent requests.
        $request->onResolve(fn () => $this->setCache($index, $value));

        return $request;
    }

    /**
     * Check if key isset.
     *
     * @param $key
     *
     * @return Promise<bool> true if the offset exists, otherwise false
     */
    public function isset($key): Promise
    {
        return $this->db->has($this->rKey($key));
    }


    public function offsetGet($offset): Promise
    {
        return call(function () use ($offset) {
            if ($cached = $this->getCache($offset)) {
                return $cached;
            }

            $value = yield $this->db->get($this->rKey($offset));

            if ($value = \unserialize($value)) {
                $this->setCache($offset, $value);
            }

            return $value;
        });
    }

    /**
     * Unset value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetunset.php
     *
     * @param string $index <p>
     * The offset to unset.
     * </p>
     *
     * @return Promise
     * @throws \Throwable
     */
    public function offsetUnset($index): Promise
    {
        $this->unsetCache($index);

        return $this->db->delete($this->rkey($index));
    }

    /**
     * Get array copy.
     *
     * @return Promise<array>
     * @throws \Throwable
     */
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

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            $request = $this->db->scan($this->itKey());

            while (yield $request->advance()) {
                yield $emit([$key = $request->getCurrent(), \unserialize(yield $this->db->get($key))]);
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
}
