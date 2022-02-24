<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Postgres\ConnectionConfig;
use Amp\Postgres\Pool;
use danog\MadelineProto\Db\Driver;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;

use function Amp\Postgres\Pool;

/**
 * Postgres driver wrapper.
 *
 * @internal
 */
class Postgres extends Driver
{
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
     * @return \Generator<array{0: Pool, 1: callable(string): string}>
     */
    protected static function getConnectionInternal(DatabasePostgres $settings): \Generator
    {
        $config = ConnectionConfig::fromString("host=".\str_replace("tcp://", "", $settings->getUri()))
                ->withUser($settings->getUsername())
                ->withPassword($settings->getPassword())
                ->withDatabase($settings->getDatabase());
        $host = $config->getHost();
        $port = $config->getPort();
        
        yield from self::createDb($config);
        return [
            new Pool($config, $settings->getMaxConnections(), $settings->getIdleTimeout()),
            [
                new \PDO(
                    "pgsql:host={$host};port={$port}",
                    $settings->getUsername(),
                    $settings->getPassword()
                ),
                'escape'
            ]
        ];
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
