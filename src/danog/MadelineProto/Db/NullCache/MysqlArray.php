<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\MysqlArray as DbMysqlArray;

/**
 * MySQL database backend, no caching.
 *
 * @internal
 */
class MysqlArray extends DbMysqlArray
{
    use NullCacheTrait;
}
