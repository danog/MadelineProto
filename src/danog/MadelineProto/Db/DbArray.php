<?php

namespace danog\MadelineProto\Db;

abstract class DbArray extends \ArrayIterator implements DbType
{
    protected function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }
}