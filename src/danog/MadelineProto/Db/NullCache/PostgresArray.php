<?php

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Settings\Database\Postgres;

class PostgresArray extends Postgres
{
    use NullCacheTrait;
}
