<?php

namespace danog\MadelineProto\Db\Driver;

use danog\MadelineProto\Db\Driver;
use danog\MadelineProto\Settings\Database\Sqlite as DatabaseSqlite;
use SQLite3;
use Vajexal\AmpSQLite\SQLiteConnection;

use function Vajexal\AmpSQLite\connect;

/**
 * Sqlite driver wrapper.
 *
 * @internal
 */
class Sqlite extends Driver
{
    /**
     * @throws \Amp\Sql\ConnectionException
     * @throws \Amp\Sql\FailureException
     * @throws \Throwable
     *
     * @return \Generator<array{0: SQLiteConnection, 1: callable(string): string}>
     */
    protected static function getConnectionInternal(DatabaseSqlite $settings): \Generator
    {
        return yield connect($settings->getUri(), SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $settings->getPassword());
    }
}
