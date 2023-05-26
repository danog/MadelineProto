<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\NullCache;

use danog\MadelineProto\Db\PostgresArrayBytea as DbPostgresArrayBytea;

/**
 * Postgres database backend, no caching.
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @extends DbPostgresArray<TKey, TValue>
 * @internal
 */
final class PostgresArrayBytea extends DbPostgresArrayBytea
{
    use NullCacheTrait;
}
