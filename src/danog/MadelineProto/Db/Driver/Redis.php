<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\Driver;

use Amp\Redis\Redis as RedisRedis;
use Amp\Redis\RedisConfig;
use Amp\Redis\RemoteExecutorFactory;
use Amp\Sql\ConnectionException;
use Amp\Sql\FailureException;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;
use Throwable;

/**
 * Redis driver wrapper.
 *
 * @internal
 */
class Redis
{
    /** @var array<RedisRedis> */
    private static array $connections = [];

    /**
     * @throws ConnectionException
     * @throws FailureException
     * @throws Throwable
     */
    public static function getConnection(DatabaseRedis $settings): RedisRedis
    {
        $dbKey = $settings->getKey();
        if (empty(static::$connections[$dbKey])) {
            $config = RedisConfig::fromUri($settings->getUri())
                ->withPassword($settings->getPassword())
                ->withDatabase($settings->getDatabase());

            static::$connections[$dbKey] = new RedisRedis((new RemoteExecutorFactory($config))->createQueryExecutor());
            static::$connections[$dbKey]->ping();
        }

        return static::$connections[$dbKey];
    }
}
