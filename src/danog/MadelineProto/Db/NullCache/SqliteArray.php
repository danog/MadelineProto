<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\SqliteArray as DbSqliteArray;

/**
 * SQlite database backend, no caching.
 *
 * @internal
 */
class SqliteArray extends DbSqliteArray
{
    use NullCacheTrait;
}
