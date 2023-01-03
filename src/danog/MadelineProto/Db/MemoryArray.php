<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Iterator;
use Amp\Producer;
use ArrayIterator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;
use RuntimeException;

/**
 * Memory database backend.
 */
class MemoryArray extends ArrayIterator implements DbArray
{
    public function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    public static function getInstance(string $table, $previous, Memory $settings): static
    {
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
    }

    public function set(string|int $key, mixed $value): void
    {
        parent::offsetSet($key, $value);
    }
    public function isset(string|int $key): bool
    {
        return parent::offsetExists($key);
    }
    public function unset(string|int $key): void
    {
        parent::offsetUnset($key);
    }

    public function offsetExists(mixed $offset): bool
    {
        throw new RuntimeException('Native isset not support promises. Use isset method');
    }

    public function offsetGet(mixed $offset): mixed
    {
        return parent::offsetExists($offset) ? parent::offsetGet($offset) : null;
    }

    public function offsetUnset(mixed $offset): void
    {
        parent::offsetUnset($offset);
    }

    public function count(): int
    {
        return parent::count();
    }

    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    public function clear(): void
    {
        parent::__construct([], parent::getFlags());
    }

    public function getIterator(): Iterator
    {
        return new Producer(function (callable $emit): void {
            foreach ($this as $key => $value) {
                $emit([$key, $value]);
            }
        });
    }
}
