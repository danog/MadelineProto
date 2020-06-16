<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;

interface DbArray extends DbType, \ArrayAccess, \Countable
{
    public function getArrayCopy(): Promise;
    public function isset($key): Promise;
    public function offsetGet($offset): Promise;
    public function offsetSet($offset, $value);
    public function offsetUnset($offset): Promise;
    public function count(): Promise;
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
