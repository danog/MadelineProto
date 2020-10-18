<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\RedisArray as DbRedisArray;

/**
 * Redis database backend, no caching.
 *
 * @internal
 */
class RedisArray extends DbRedisArray
{
    use NullCacheTrait;
}
