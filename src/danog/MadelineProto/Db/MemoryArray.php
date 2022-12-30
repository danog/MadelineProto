<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Future;
use Amp\Iterator;
use Amp\Producer;
use Amp\Success;
use ArrayIterator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;
use ReturnTypeWillChange;
use RuntimeException;

use function Amp\call;

/**
 * Memory database backend.
 */
class MemoryArray extends ArrayIterator implements DbArray
{
    public function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    /**
     * Get instance.
     *
     * @return Promise<self>
     */
    public static function getInstance(string $table, $previous, Memory $settings): Future
    {
        return call(static function () use ($previous) {
            if ($previous instanceof MemoryArray) {
                return $previous;
            }
            if ($previous instanceof DbArray) {
                Logger::log("Loading database to memory. Please wait.", Logger::WARNING);
                if ($previous instanceof DriverArray) {
                    $previous->initStartup();
                }
                $temp = $previous->getArrayCopy();
                $previous->clear();
                $previous = $temp;
            }
            return new static($previous);
        });
    }

    public function set(string|int $key, mixed $value): Future
    {
        parent::offsetSet($key, $value);
        return new Success();
    }
    public function isset(string|int $key): Future
    {
        return new Success(parent::offsetExists($key));
    }
    public function unset(string|int $key): Future
    {
        parent::offsetUnset($key);
        return new Success();
    }

    public function offsetExists(mixed $offset): bool
    {
        throw new RuntimeException('Native isset not support promises. Use isset method');
    }

    public function offsetGet(mixed $offset): Future
    {
        return new Success(parent::offsetExists($offset) ? parent::offsetGet($offset) : null);
    }

    public function offsetUnset(mixed $offset): void
    {
        parent::offsetUnset($offset);
    }

    #[ReturnTypeWillChange]
    public function count(): Future
    {
        return new Success(parent::count());
    }

    #[ReturnTypeWillChange]
    public function getArrayCopy(): Future
    {
        return new Success(parent::getArrayCopy());
    }

    public function clear(): Future
    {
        parent::__construct([], parent::getFlags());
        return new Success();
    }

    public function getIterator(): Iterator
    {
        return new Producer(function (callable $emit) {
            foreach ($this as $key => $value) {
                $emit([$key, $value]);
            }
        });
    }
}
