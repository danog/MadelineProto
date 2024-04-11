<?php declare(strict_types=1);

namespace danog\MadelineProto\Db;

use ArrayObject;
use AssertionError;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\DbArrayBuilder;

/**
 * @internal
 * @deprecated Please use https://github.com/danog/AsyncOrm
 */
final class MemoryArray extends ArrayObject
{
    public function unset(string|int $key): void
    {
        throw new AssertionError("Unreachable");
    }
    public function set(string|int $key, mixed $value): void
    {
        throw new AssertionError("Unreachable");
    }
    public function get(string|int $key): mixed
    {
        throw new AssertionError("Unreachable");
    }
    public function clear(): void
    {
        throw new AssertionError("Unreachable");
    }
    public function count(): int
    {
        throw new AssertionError("Unreachable");
    }
    public function getIterator(): \Iterator
    {
        throw new AssertionError("Unreachable");
    }

    public static function getInstance(DbArrayBuilder $config, DbArray|null $previous): DbArray
    {
        throw new AssertionError("Unreachable");
    }
}
