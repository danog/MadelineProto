<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\RedisArray as DbRedisArray;

class RedisArray extends DbRedisArray
{
    use NullCacheTrait;
}
