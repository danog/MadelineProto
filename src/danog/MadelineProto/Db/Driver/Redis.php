<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Redis\Config;
use Amp\Redis\Redis as RedisRedis;
use Amp\Redis\RemoteExecutorFactory;
use danog\MadelineProto\Db\Driver;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;

/**
 * Redis driver wrapper.
 *
 * @internal
 */
class Redis extends Driver
{
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
    protected static function getConnectionInternal(DatabaseRedis $settings): \Generator
    {
        $config = Config::fromUri($settings->getUri())
            ->withPassword($settings->getPassword())
            ->withDatabase($settings->getDatabase());

        $connection = new RedisRedis((new RemoteExecutorFactory($config))->createQueryExecutor());
        yield $connection->ping();
        return $connection;
    }
}
