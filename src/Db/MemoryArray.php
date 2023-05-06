<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use ArrayIterator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;

/**
 * Memory database backend.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 * @extends ArrayIterator<TKey, TValue>
 * @implements DbArray<TKey, TValue>
 */
final class MemoryArray extends ArrayIterator implements DbArray
{
    public function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    /**
     * @param Memory $settings
     */
    public static function getInstance(string $table, DbType|array|null $previous, $settings): static
    {
        if ($previous instanceof MemoryArray) {
            return $previous;
        }
        if ($previous instanceof DbArray) {
            Logger::log('Loading database to memory. Please wait.', Logger::WARNING);
            if ($previous instanceof DriverArray) {
                $previous->initStartup();
            }
            $temp = $previous->getArrayCopy();
            $previous->clear();
            $previous = $temp;
        }
        return new static($previous);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     */
    public function set(string|int $key, mixed $value): void
    {
        parent::offsetSet($key, $value);
    }
    /**
     * @param TKey $key
     */
    public function isset(string|int $key): bool
    {
        return parent::offsetExists($key);
    }
    /**
     * @param TKey $key
     */
    public function unset(string|int $key): void
    {
        parent::offsetUnset($key);
    }

    /**
     * @param TKey $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return parent::offsetExists($offset);
    }

    /**
     * @param TKey $offset
     *
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return parent::offsetExists($offset) ? parent::offsetGet($offset) : null;
    }

    /**
     * @param TKey $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        parent::offsetUnset($offset);
    }

    public function count(): int
    {
        return parent::count();
    }

    public function clear(): void
    {
        parent::__construct([], parent::getFlags());
    }

    public function getIterator(): \Traversable
    {
        return $this;
    }
}
