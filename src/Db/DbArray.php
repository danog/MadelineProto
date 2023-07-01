<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use ArrayAccess;
use Countable;

/**
 * DB array interface.
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @extends ArrayAccess<TKey, TValue>
 */
interface DbArray extends DbType, ArrayAccess, Countable
{
    /**
     * Get Array copy.
     *
     * @psalm-return array<TKey, TValue>
     */
    public function getArrayCopy(): array;
    /**
     * Check if element is set.
     *
     * @param TKey $key
     */
    public function isset(string|int $key): bool;
    /**
     * Unset element.
     *
     * @param TKey $key
     */
    public function unset(string|int $key): void;
    /**
     * Set element.
     *
     * @param TKey $key
     * @param TValue $value
     */
    public function set(string|int $key, mixed $value): void;
    /**
     * Get element.
     *
     * @param TKey $index
     */
    public function offsetGet(mixed $index): mixed;
    /**
     * Set element.
     *
     * @param TKey $index
     * @param TValue $value
     */
    public function offsetSet(mixed $index, mixed $value): void;
    /**
     * Unset element.
     * @param TKey $index Offset
     */
    public function offsetUnset(mixed $index): void;
    /**
     * @see DbArray::isset();
     *
     * @param TKey $index Offset
     */
    public function offsetExists(mixed $index): bool;
    /**
     * Clear all elements.
     */
    public function clear(): void;
    /**
     * Get iterator.
     *
     * @return \Traversable<TKey, TValue>
     */
    public function getIterator(): \Traversable;
}
