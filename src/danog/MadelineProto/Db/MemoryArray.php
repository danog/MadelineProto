<?php

namespace danog\MadelineProto\Db;

use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;

use function Amp\call;

/**
 * Memory database backend.
 */
class MemoryArray extends \ArrayIterator implements DbArray
{
    public function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    /**
     * Get instance.
     *
     * @param string $table
     * @param mixed  $previous
     * @param Memory $settings
     * @return Promise<self>
     */
    public static function getInstance(string $table, $previous, $settings): Promise
    {
        return call(static function () use ($previous) {
            if ($previous instanceof MemoryArray) {
                return $previous;
            }
            if ($previous instanceof DbArray) {
                Logger::log("Loading database to memory. Please wait.", Logger::WARNING);
                if ($previous instanceof DriverArray) {
                    yield from $previous->initStartup();
                }
                $temp = yield $previous->getArrayCopy();
                yield $previous->clear();
                $previous = $temp;
            }
            return new static($previous);
        });
    }

    public function set(string|int $key, mixed $value): Promise
    {
        parent::offsetSet($key, $value);
        return new Success();
    }
    public function isset(string|int $key): Promise
    {
        return new Success(parent::offsetExists($key));
    }
    public function unset(string|int $key): Promise
    {
        parent::offsetUnset($key);
        return new Success();
    }



    public function offsetExists(mixed $offset): bool
    {
        throw new \RuntimeException('Native isset not support promises. Use isset method');
    }

    public function offsetGet(mixed $offset): Promise
    {
        return new Success(parent::offsetExists($offset) ? parent::offsetGet($offset) : null);
    }

    public function offsetUnset(mixed $offset): void
    {
        parent::offsetUnset($offset);
    }

    #[\ReturnTypeWillChange]
    public function count(): Promise
    {
        return new Success(parent::count());
    }

    #[\ReturnTypeWillChange]
    public function getArrayCopy(): Promise
    {
        return new Success(parent::getArrayCopy());
    }

    public function clear(): Promise
    {
        parent::__construct([], parent::getFlags());
        return new Success();
    }

    public function getIterator(): Iterator
    {
        return new Producer(function (callable $emit) {
            foreach ($this as $key => $value) {
                yield $emit([$key, $value]);
            }
        });
    }
}
