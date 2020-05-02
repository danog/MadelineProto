<?php

namespace danog\MadelineProto\Db;

use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Pool;
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
        string $db = 'MadelineProto'
    ): Pool
    {
        $dbKey = "$host:$port:$db";
        if (empty(static::$connections[$dbKey])) {
            $config = ConnectionConfig::fromString(
                "host={$host} port={$port} user={$user} password={$password} db={$db}"
            );

            static::createDb($config);
            static::$connections[$dbKey] = pool($config);
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
    private static function createDb(ConnectionConfig $config) {
        $db = $config->getDatabase();
        wait(pool($config->withDatabase(null))->query("
            CREATE DATABASE IF NOT EXISTS `{$db}`
            CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
        "));

    }

}