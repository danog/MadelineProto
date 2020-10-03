<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;

/**
 * DB array interface.
 *
 * @template T as mixed
 */
interface DbArray extends DbType, \ArrayAccess, \Countable
{
    /**
     * Get Array copy.
     *
     * @psalm-return Promise<array{0: string|int, 1: T}> $value
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
    public function isset($key): Promise;
    /**
     * Get element.
     *
     * @param string|int $offset
     *
     * @psalm-return Promise<T>
     *
     * @return Promise
     */
    public function offsetGet($offset): Promise;
    /**
     * Set element.
     *
     * @param string|int $offset
     * @param mixed      $value
     *
     * @psalm-param T $value
     *
     * @return void
     */
    public function offsetSet($offset, $value);
    /**
     * Unset element.
     *
     * @param string|int $offset Offset
     * @return Promise
     */
    public function offsetUnset($offset): Promise;
    /**
     * Count number of elements.
     *
     * @return Promise<integer>
     */
    public function count(): Promise;
    /**
     * Get iterator.
     *
     * @return Producer<array{0: string|int, 1: T}>
     */
    public function getIterator(): Producer;

    /**
     * @deprecated
     * @internal
     * @see DbArray::isset();
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset);
}
