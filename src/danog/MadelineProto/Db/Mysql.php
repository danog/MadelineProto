<?php

namespace danog\MadelineProto\Db;

use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Pool;
use Amp\Sql\Common\ConnectionPool;
use danog\MadelineProto\Logger;
use function Amp\call;
use function Amp\Mysql\Pool;
use function Amp\Promise\wait;

class Mysql
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
        int $port = 3306,
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
                $connection = pool($config->withDatabase(null));
                yield $connection->query("
                    CREATE DATABASE IF NOT EXISTS `{$db}`
                    CHARACTER SET 'utf8mb4' 
                    COLLATE 'utf8mb4_general_ci'
                ");
                $connection->close();
            } catch (\Throwable $e) {
                Logger::log($e->getMessage(), Logger::ERROR);
            }
        }));
    }
}
