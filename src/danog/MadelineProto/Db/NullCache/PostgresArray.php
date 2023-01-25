<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\PostgresArray as DbPostgresArray;

/**
 * Postgres database backend, no caching.
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @extends DbPostgresArray<TKey, TValue>
 * @internal
 */
final class PostgresArray extends DbPostgresArray
{
    use NullCacheTrait;
}
