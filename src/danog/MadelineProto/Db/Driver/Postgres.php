<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Postgres\ConnectionConfig;
use Amp\Postgres\Pool;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;

use function Amp\Postgres\Pool;

/**
 * Postgres driver wrapper.
 *
 * @internal
 */
class Postgres
{
    /** @var Pool[] */
    private static array $connections = [];

    /**
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $db
     *
     * @param int $maxConnections
     * @param int $idleTimeout
     *
     * @throws \Amp\Sql\ConnectionException
     * @throws \Amp\Sql\FailureException
     * @throws \Throwable
     *
     * @return \Generator<Pool>
     */
    public static function getConnection(DatabasePostgres $settings): \Generator
    {
        $dbKey = $settings->getKey();
        if (empty(static::$connections[$dbKey])) {
            $config = ConnectionConfig::fromString("host=".\str_replace("tcp://", "", $settings->getUri()))
                ->withUser($settings->getUsername())
                ->withPassword($settings->getPassword())
                ->withDatabase($settings->getDatabase());

            yield from static::createDb($config);
            static::$connections[$dbKey] = new Pool($config, $settings->getMaxConnections(), $settings->getIdleTimeout());
        }

        return static::$connections[$dbKey];
    }

    /**
     * @param ConnectionConfig $config
     *
     * @throws \Amp\Sql\ConnectionException
     * @throws \Amp\Sql\FailureException
     * @throws \Throwable
     */
    private static function createDb(ConnectionConfig $config): \Generator
    {
        try {
            $db = $config->getDatabase();
            $user = $config->getUser();
            $connection = pool($config->withDatabase(null));

            $result = yield $connection->query("SELECT * FROM pg_database WHERE datname = '{$db}'");

            while (yield $result->advance()) {
                $row = $result->getCurrent();
                if ($row===false) {
                    yield $connection->query("
                            CREATE DATABASE {$db}
                            OWNER {$user}
                            ENCODING utf8
                        ");
                }
            }
            yield $connection->query("
                    CREATE OR REPLACE FUNCTION update_ts()
                    RETURNS TRIGGER AS $$
                    BEGIN
                       IF row(NEW.*) IS DISTINCT FROM row(OLD.*) THEN
                          NEW.ts = now(); 
                          RETURN NEW;
                       ELSE
                          RETURN OLD;
                       END IF;
                    END;
                    $$ language 'plpgsql'
                ");
            $connection->close();
        } catch (\Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
        }
    }
}
