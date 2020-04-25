<?php

namespace danog\MadelineProto\Db;

class MemoryArray extends DbArray
{

    static function getInstance(array $settings, string $name, $value = []): DbArray
    {
        if ($value instanceof DbArray) {
            $value = $value->getArrayCopy();
        }
        return new static($value);
    }
}