<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\MysqlArray as DbMysqlArray;

/**
 * MySQL database backend, no caching.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @extends DbMysqlArray<TKey, TValue>
 */
final class MysqlArray extends DbMysqlArray
{
    use NullCacheTrait;
}
