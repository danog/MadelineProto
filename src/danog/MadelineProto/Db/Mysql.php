<?php

namespace danog\MadelineProto\Db;

use Amp\Loop;
use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Pool;
use function Amp\Mysql\Pool;

class Mysql
{
    /** @var Pool[] */
    private static array $connections;

    private static function connect(
        string $host = '127.0.0.1',
        int $port = 3306,
        string $user = 'root',
        string $password = '',
        string $db = 'MadelineProto'
    ) {
        $config = ConnectionConfig::fromString(
            "host={$host} port={$port} user={$user} password={$password} db={$db}"
        );

        return Pool($config);
    }

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
            static::$connections[$dbKey] = static::connect($host, $port, $user, $password, $db);
        }

        return static::$connections[$dbKey];
    }

}