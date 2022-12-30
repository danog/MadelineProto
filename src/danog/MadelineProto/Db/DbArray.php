<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Future;
use Amp\Iterator;
use ArrayAccess;
use ReturnTypeWillChange;

/**
 * DB array interface.
 *
 * @template T as mixed
 */
interface DbArray extends DbType, ArrayAccess
{
    /**
     * Get Array copy.
     *
     * @psalm-return Promise<array<string|int, T>>
     */
    public function getArrayCopy(): Future;
    /**
     * Check if element is set.
     *
     * @psalm-return Promise<bool>
     */
    public function isset(string|int $key): Future;
    /**
     * Unset element.
     *
     * @psalm-return Promise<mixed>
     */
    public function unset(string|int $key): Future;
    /**
     * Set element.
     *
     * @psalm-param T $value
     */
    public function set(string|int $key, mixed $value): Future;
    /**
     * Get element.
     *
     * @param string|int $index
     * @psalm-return Promise<T>
     */
    public function offsetGet(mixed $index): Future;
    /**
     * Set element.
     *
     * @param string|int $index
     * @psalm-param T $value
     */
    #[ReturnTypeWillChange]
    public function offsetSet(mixed $index, mixed $value): void;
    /**
     * @deprecated
     * @internal
     * @see DbArray::unset();
     *
     * Unset element.
     * @param string|int $index Offset
     */
    public function offsetUnset(mixed $index): void;
    /**
     * @deprecated
     * @internal
     * @see DbArray::isset();
     */
    public function offsetExists(mixed $index): bool;
    /**
     * Count number of elements.
     *
     * @return Promise<integer>
     */
    public function count(): Future;
    /**
     * Clear all elements.
     */
    public function clear(): Future;
    /**
     * Get iterator.
     *
     * @return Iterator<array{0: string|int, 1: T}>
     */
    public function getIterator(): Iterator;
}
