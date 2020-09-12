<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Pool;
use Amp\Sql\Common\ConnectionPool;
use danog\MadelineProto\Logger;
use function Amp\Mysql\Pool;

class Mysql
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
    public static function getConnection(
        string $host = '127.0.0.1',
        int $port = 3306,
        string $user = 'root',
        string $password = '',
        string $db = 'MadelineProto',
        int $maxConnections = ConnectionPool::DEFAULT_MAX_CONNECTIONS,
        int $idleTimeout = ConnectionPool::DEFAULT_IDLE_TIMEOUT
    ): \Generator {
        $dbKey = "$host:$port:$db";
        if (empty(static::$connections[$dbKey])) {
            $config = ConnectionConfig::fromString(
                "host={$host} port={$port} user={$user} password={$password} db={$db}"
            );

            yield from static::createDb($config);
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
     *
     * @return \Generator
     */
    private static function createDb(ConnectionConfig $config): \Generator
    {
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
    }
}
