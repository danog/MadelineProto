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

    public static function getInstance(string $name, $value = null, string $tablePrefix = '', array $settings = []): DbArray
    {
        if ($value instanceof DbArray) {
            $value = $value->getArrayCopy();
        }
        return new static($value);
    }

    public function offsetExists($offset): Promise
    {
        return call(fn() => parent::offsetExists($offset));
    }

    public function offsetGet($offset): Promise
    {
        return call(fn() => parent::offsetGet($offset));
    }

    public function offsetUnset($offset): Promise
    {
        return call(fn() => parent::offsetUnset($offset));
    }

    public function count(): Promise
    {
        return call(fn() => parent::count());
    }

    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            foreach ($this as $key => $value) {
                yield $emit([$key, $value]);
            }
        });
    }
}