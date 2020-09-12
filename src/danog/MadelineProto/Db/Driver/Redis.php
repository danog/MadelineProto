<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Redis\Config;
use Amp\Redis\Redis as RedisRedis;
use Amp\Redis\RemoteExecutorFactory;
use Amp\Sql\Common\ConnectionPool;

class Redis
{
    /** @var RedisRedis[] */
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
     * @return RedisRedis
     */
    public static function getConnection(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $password = '',
        int $db = 0,
        int $maxConnections = ConnectionPool::DEFAULT_MAX_CONNECTIONS,
        int $idleTimeout = ConnectionPool::DEFAULT_IDLE_TIMEOUT
    ): RedisRedis {
        $dbKey = "$host:$port:$db";
        if (empty(static::$connections[$dbKey])) {
            $config = Config::fromUri(
                "host={$host} port={$port} password={$password} db={$db}"
            );

            static::$connections[$dbKey] = new RedisRedis((new RemoteExecutorFactory($config))->createQueryExecutor());
        }

        return static::$connections[$dbKey];
    }
}
