<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\PostgresArray as DbPostgresArray;

class PostgresArray extends DbPostgresArray
{
    use NullCacheTrait;
}
