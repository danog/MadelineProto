<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Settings\Database\Mysql;

class MysqlArray extends Mysql
{
    use NullCacheTrait;
}
