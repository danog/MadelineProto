<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\MysqlArray as DbMysqlArray;

class MysqlArray extends DbMysqlArray
{
    use NullCacheTrait;
}
