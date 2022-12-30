<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\Driver;

use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Pool;
use Amp\Sql\ConnectionException;
use Amp\Sql\FailureException;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Mysql as DatabaseMysql;
use Generator;
use Throwable;

use function Amp\Mysql\Pool;

/**
 * MySQL driver wrapper.
 *
 * @internal
 */
class Mysql
{
    /** @var Pool[] */
    private static array $connections = [];

    /**
     * @throws ConnectionException
     * @throws FailureException
     * @throws Throwable
     * @return Generator<Pool>
     */
    public static function getConnection(DatabaseMysql $settings): Generator
    {
        $dbKey = $settings->getKey();
        if (empty(static::$connections[$dbKey])) {
            $config = ConnectionConfig::fromString("host=".\str_replace("tcp://", "", $settings->getUri()))
                ->withUser($settings->getUsername())
                ->withPassword($settings->getPassword())
                ->withDatabase($settings->getDatabase());

            yield from static::createDb($config);
            static::$connections[$dbKey] = new Pool($config, $settings->getMaxConnections(), $settings->getIdleTimeout());
        }

        return static::$connections[$dbKey];
    }

    /**
     * @throws ConnectionException
     * @throws FailureException
     * @throws Throwable
     */
    private static function createDb(ConnectionConfig $config): Generator
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
        } catch (Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
        }
    }
}
