<?php

namespace danog\MadelineProto\Db;

interface DbArray extends DbType, \ArrayAccess, \Countable, \Iterator, \SeekableIterator
{
    public function getArrayCopy();
}