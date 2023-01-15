<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\PostgresArray as DbPostgresArray;

/**
 * Postgres database backend, no caching.
 *
 * @internal
 */
final class PostgresArray extends DbPostgresArray
{
    use NullCacheTrait;
}
