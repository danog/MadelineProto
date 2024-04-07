<?php declare(strict_types=1);

namespace danog\MadelineProto\Db;

if (class_exists('\\danog\\MadelineProto\\Db\\NullCache\\MysqlArray')) {
    return;
}

use ArrayObject;
use AssertionError;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\DbArrayBuilder;
use danog\AsyncOrm\Internal\Containers\CacheContainer;
use danog\AsyncOrm\Internal\Driver\MysqlArray;
use danog\AsyncOrm\Internal\Driver\PostgresArray;
use danog\AsyncOrm\Internal\Driver\RedisArray;

class_alias(MysqlArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\MysqlArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\PostgresArray');
class_alias(RedisArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\RedisArray');

class_alias(MysqlArray::class, '\\danog\\MadelineProto\\Db\\MysqlArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\PostgresArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\PostgresArrayBytea');
class_alias(RedisArray::class, '\\danog\\MadelineProto\\Db\\RedisArray');
class_alias(CacheContainer::class, '\\danog\\MadelineProto\\Db\\CacheContainer');

/** @deprecated */
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
/** @deprecated */
final class CachedArray extends DbArray
{

    private readonly CacheContainer $cache;

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
    public function getIterator(): \Traversable
    {
        throw new AssertionError("Unreachable");
    }

    public static function getInstance(DbArrayBuilder $config, DbArray|null $previous): DbArray
    {
        throw new AssertionError("Unreachable");
    }

}

if ((PHP_MINOR_VERSION === 2 && PHP_VERSION_ID < 80204)
    || PHP_MAJOR_VERSION < 8
    || (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION < 2)
) {
    echo('MadelineProto requires PHP 8.3.1+ (recommended) or 8.2.14+.'.PHP_EOL);
    die(1);
}
