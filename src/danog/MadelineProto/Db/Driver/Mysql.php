<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db\Driver;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Mysql as DatabaseMysql;
use Throwable;

/**
 * MySQL driver wrapper.
 *
 * @internal
 */
final class Mysql
{
    /** @var array<MysqlConnectionPool> */
    private static array $connections = [];

    public static function getConnection(DatabaseMysql $settings): MysqlConnectionPool
    {
        $dbKey = $settings->getKey();
        if (!isset(self::$connections[$dbKey])) {
            $config = MysqlConfig::fromString('host='.\str_replace('tcp://', '', $settings->getUri()))
                ->withUser($settings->getUsername())
                ->withPassword($settings->getPassword())
                ->withDatabase($settings->getDatabase());

            self::createDb($config);
            self::$connections[$dbKey] = new MysqlConnectionPool($config, $settings->getMaxConnections(), $settings->getIdleTimeout());
        }

        return self::$connections[$dbKey];
    }

    private static function createDb(MysqlConfig $config): void
    {
        try {
            $db = $config->getDatabase();
            $connection = new MysqlConnectionPool($config->withDatabase(null));
            $connection->query("
                    CREATE DATABASE IF NOT EXISTS `{$db}`
                    CHARACTER SET 'utf8mb4' 
                    COLLATE 'utf8mb4_general_ci'
                ");
            try {
                $max = (int) $connection->query("SHOW VARIABLES LIKE 'max_connections'")->fetchRow()['Value'];
                if ($max < 100000) {
                    $connection->query("SET GLOBAL max_connections = 100000");
                }
            } catch (Throwable) {
            }
            $connection->close();
        } catch (Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
        }
    }
}
