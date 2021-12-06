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
     * @return Promise
     */
    public function getArrayCopy(): Promise;
    /**
     * Check if element is set.
     *
     * @param string|int $key
     *
     * @psalm-return Promise<bool>
     *
     * @return Promise
     */
    public function isset(string|int $key): Promise;
    /**
     * Unset element.
     *
     * @param string|int $key
     *
     * @psalm-return Promise<mixed>
     *
     * @return Promise
     */
    public function unset(string|int $key): Promise;
    /**
     * Set element.
     *
     * @param string|int $index
     * @param mixed      $value
     *
     * @psalm-param T $value
     *
     * @return Promise
     */
    public function set(string|int $key, mixed $value): Promise;
    /**
     * Get element.
     *
     * @param string|int $index
     *
     * @psalm-return Promise<T>
     *
     * @return Promise
     */
    public function offsetGet(mixed $index): Promise;
    /**
     * Set element.
     *
     * @param string|int $index
     * @param mixed      $value
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
     * @return void
     */
    public function offsetUnset(mixed $index): void;
    /**
     * @deprecated
     * @internal
     * @see DbArray::isset();
     *
     * @param mixed $index
     *
     * @return bool
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
     * @return Promise
     */
    public function clear(): Promise;
    /**
     * Get iterator.
     *
     * @return Iterator<array{0: string|int, 1: T}>
     */
    public function getIterator(): Iterator;
}
