<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;

interface DbArray extends DbType, \ArrayAccess, \Countable, \Iterator, \SeekableIterator
{
    public function getArrayCopy();
    public function offsetGetAsync(string $offset): Promise;
    public function offsetSetAsync(string $offset, $value): Promise;
    public function getIterator(): Producer;
}