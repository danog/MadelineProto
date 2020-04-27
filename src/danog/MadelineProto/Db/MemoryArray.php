<?php

namespace danog\MadelineProto\Db;

class MemoryArray extends \ArrayIterator implements DbArray
{
    protected function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    static function getInstance(array $settings, string $name, $value = []): DbArray
    {
        if ($value instanceof DbArray) {
            $value = $value->getArrayCopy();
        }
        return new static($value);
    }
}