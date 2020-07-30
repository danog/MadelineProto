<?php

namespace danog\MadelineProto\Db;

use Amp\Postgres\ConnectionConfig;
use Amp\Postgres\Pool;
use Amp\Sql\Common\ConnectionPool;
use danog\MadelineProto\Logger;
use function Amp\call;
use function Amp\Postgres\Pool;
use function Amp\Promise\wait;

class Postgres
{
    /** @var Pool[] */
    private static array $connections;

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
     * @return Pool
     * @throws \Amp\Sql\ConnectionException
     * @throws \Amp\Sql\FailureException
     * @throws \Throwable
     */
    public static function getConnection(
        string $host = '127.0.0.1',
        int $port = 5432,
        string $user = 'root',
        string $password = '',
        string $db = 'MadelineProto',
        int $maxConnections = ConnectionPool::DEFAULT_MAX_CONNECTIONS,
        int $idleTimeout = ConnectionPool::DEFAULT_IDLE_TIMEOUT
    ): Pool {
        $dbKey = "$host:$port:$db";
        if (empty(static::$connections[$dbKey])) {
            $config = ConnectionConfig::fromString(
                "host={$host} port={$port} user={$user} password={$password} db={$db}"
            );

            static::createDb($config);
            static::$connections[$dbKey] = pool($config, $maxConnections, $idleTimeout);
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
    private static function createDb(ConnectionConfig $config)
    {
        wait(call(static function () use ($config) {
            try {
                $db = $config->getDatabase();
                $user = $config->getUser();
                $connection = pool($config->withDatabase(null));

                $result = yield $connection->query("SELECT * FROM pg_database WHERE datname = '{$db}'");

                while (yield $result->advance()) {
                    $row = $result->getCurrent();
                    if ($row===false)
                    {
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
        }));
    }
}
