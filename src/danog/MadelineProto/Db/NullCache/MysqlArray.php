<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\MysqlArray as DbMysqlArray;

/**
 * MySQL database backend, no caching.
 *
 * @internal
 */
final class MysqlArray extends DbMysqlArray
{
    use NullCacheTrait;
}
