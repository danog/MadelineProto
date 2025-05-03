<?php declare(strict_types=1);

namespace danog\MadelineProto\Db;

use AssertionError;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\DbArrayBuilder;
use danog\AsyncOrm\Internal\Containers\CacheContainer;

/**
 * @internal
 * @deprecated Please use https://github.com/danog/AsyncOrm
 */
final class CachedArray extends DbArray
{
    private readonly CacheContainer $cache;

    #[\Override]
    public function unset(string|int $key): void
    {
        throw new AssertionError("Unreachable");
    }
    #[\Override]
    public function set(string|int $key, mixed $value): void
    {
        throw new AssertionError("Unreachable");
    }
    #[\Override]
    public function get(string|int $key): mixed
    {
        throw new AssertionError("Unreachable");
    }
    #[\Override]
    public function clear(): void
    {
        throw new AssertionError("Unreachable");
    }
    #[\Override]
    public function count(): int
    {
        throw new AssertionError("Unreachable");
    }
    #[\Override]
    public function getIterator(): \Traversable
    {
        throw new AssertionError("Unreachable");
    }

    #[\Override]
    public static function getInstance(DbArrayBuilder $config, DbArray|null $previous): DbArray
    {
        throw new AssertionError("Unreachable");
    }

}
