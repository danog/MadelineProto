<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;
use function Amp\call;

class MemoryArray extends \ArrayIterator implements DbArray
{
    protected function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    public static function getInstance(string $name, $value, string $tablePrefix, array $settings): DbArray
    {
        if ($value instanceof DbArray) {
            $value = $value->getArrayCopy();
        }
        return new static($value);
    }

    public static function getDbConnection(array $settings)
    {
        return null;
    }

    public function offsetGetAsync(string $offset): Promise
    {
        return call(fn() => $this->offsetGet($offset));
    }

    public function offsetSetAsync(string $offset, $value): Promise
    {
        return call(fn() => $this->offsetSet($offset, $value));
    }

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            foreach ($this as $value) {
                yield $emit($value);
            }
        });
    }
}