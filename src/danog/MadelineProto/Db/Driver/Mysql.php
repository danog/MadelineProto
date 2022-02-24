<?php

namespace danog\MadelineProto\Db\Driver;

use Amp\Mysql\ConnectionConfig;
use Amp\Mysql\Pool;
use danog\MadelineProto\Db\Driver;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Mysql as DatabaseMysql;

use function Amp\Mysql\Pool;

/**
 * MySQL driver wrapper.
 *
 * @internal
 */
class Mysql extends Driver
{
    /**
     * @return \Generator<array{0: Pool, 1: callable(string): string}>
     */
    protected static function getConnectionInternal(DatabaseMysql $settings): \Generator
    {
        $config = ConnectionConfig::fromString("host=".\str_replace("tcp://", "", $settings->getUri()))
            ->withUser($settings->getUsername())
            ->withPassword($settings->getPassword())
            ->withDatabase($settings->getDatabase());

        $host = $config->getHost();
        $port = $config->getPort();

        yield from self::createDb($config);
        return [
            new Pool($config, $settings->getMaxConnections(), $settings->getIdleTimeout()),
            [
                new \PDO(
                    "mysql:host={$host};port={$port};charset=UTF8",
                    $settings->getUsername(),
                    $settings->getPassword()
                ),
                'escape'
            ]
        ];
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
