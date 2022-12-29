<?php

namespace danog\MadelineProto\Db;

use Amp\Iterator;
use Amp\Promise;

/**
 * DB array interface.
 *
 * @template T as mixed
 */
interface DbArray extends DbType, \ArrayAccess
{
    /**
     * Get Array copy.
     *
     * @psalm-return Promise<array<string|int, T>>
     *
     */
    public function getArrayCopy(): Promise;
    /**
     * Check if element is set.
     *
     *
     * @psalm-return Promise<bool>
     *
     */
    public function isset(string|int $key): Promise;
    /**
     * Unset element.
     *
     *
     * @psalm-return Promise<mixed>
     *
     */
    public function unset(string|int $key): Promise;
    /**
     * Set element.
     *
     *
     * @psalm-param T $value
     *
     */
    public function set(string|int $key, mixed $value): Promise;
    /**
     * Get element.
     *
     * @param string|int $index
     *
     * @psalm-return Promise<T>
     *
     */
    public function offsetGet(mixed $index): Promise;
    /**
     * Set element.
     *
     * @param string|int $index
     *
     * @psalm-param T $value
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet(mixed $index, mixed $value);
    /**
     * @deprecated
     * @internal
     * @see DbArray::unset();
     *
     * Unset element.
     *
     * @param string|int $index Offset
     */
    public function offsetUnset(mixed $index): void;
    /**
     * @deprecated
     * @internal
     * @see DbArray::isset();
     *
     *
     */
    public function offsetExists(mixed $index): bool;
    /**
     * Count number of elements.
     *
     * @return Promise<integer>
     */
    public function count(): Promise;
    /**
     * Clear all elements.
     *
     */
    public function clear(): Promise;
    /**
     * Get iterator.
     *
     * @return Iterator<array{0: string|int, 1: T}>
     */
    public function getIterator(): Iterator;
}
