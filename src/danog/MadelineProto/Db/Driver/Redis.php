<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Redis\Config;
use Amp\Redis\Redis as RedisRedis;
use Amp\Redis\RemoteExecutorFactory;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;

/**
 * Redis driver wrapper.
 *
 * @internal
 */
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
     * @param int $maxConnections
     * @param int $idleTimeout
     *
     * @throws \Amp\Sql\ConnectionException
     * @throws \Amp\Sql\FailureException
     * @throws \Throwable
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, \Amp\Promise<void>, mixed, RedisRedis>
     */
    public static function getConnection(DatabaseRedis $settings): \Generator
    {
        $dbKey = $settings->getKey();
        if (empty(static::$connections[$dbKey])) {
            $config = Config::fromUri($settings->getUri())
                ->withPassword($settings->getPassword())
                ->withDatabase($settings->getDatabase());

            static::$connections[$dbKey] = new RedisRedis((new RemoteExecutorFactory($config))->createQueryExecutor());
            yield static::$connections[$dbKey]->ping();
        }

        return static::$connections[$dbKey];
    }
}
