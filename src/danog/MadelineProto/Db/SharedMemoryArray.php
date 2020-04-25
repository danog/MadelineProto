<?php

namespace danog\MadelineProto\Db;

class SharedMemoryArray extends DbArray
{
    private static SharedMemoryArray $instance;

    public static function getInstance(array $settings, string $name, $value = []): DbArray
    {
        if (empty(static::$instance)) {
            static::$instance = new static($value);
        } else {
            if ($value instanceof DbArray) {
                $value = $value->getArrayCopy();
            }
            $value = array_replace_recursive(static::$instance->getArrayCopy(), (array) $value);
            foreach ($value as $key => $item) {
                static::$instance[$key] = $item;
            }
        }

        return static::$instance;
    }
}