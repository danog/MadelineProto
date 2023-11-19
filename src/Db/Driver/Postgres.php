<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Db\Driver;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Sync\LocalKeyedMutex;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;
use Revolt\EventLoop;
use Throwable;

/**
 * Postgres driver wrapper.
 *
 * @internal
 */
final class Postgres
{
    /** @var array<PostgresConnectionPool> */
    private static array $connections = [];

    private static ?LocalKeyedMutex $mutex = null;
    public static function getConnection(DatabasePostgres $settings): PostgresConnectionPool
    {
        self::$mutex ??= new LocalKeyedMutex;
        $dbKey = $settings->getDbIdentifier();
        $lock = self::$mutex->acquire($dbKey);

        try {
            if (empty(self::$connections[$dbKey])) {
                $host = str_replace(['tcp://', 'unix://'], '', $settings->getUri());
                if ($host[0] === '/') {
                    $port = 0;
                } else {
                    $host = explode(':', $host, 2);
                    if (\count($host) === 2) {
                        [$host, $port] = $host;
                    } else {
                        $host = $host[0];
                        $port = PostgresConfig::DEFAULT_PORT;
                    }
                }
                $config = new PostgresConfig(
                    host: $host,
                    port: (int) $port,
                    user: $settings->getUsername(),
                    password: $settings->getPassword(),
                    database: $settings->getDatabase()
                );

                self::createDb($config);
                self::$connections[$dbKey] = new PostgresConnectionPool($config, $settings->getMaxConnections(), $settings->getIdleTimeout());
            }
        } finally {
            EventLoop::queue($lock->release(...));
        }

        return self::$connections[$dbKey];
    }

    private static function createDb(PostgresConfig $config): void
    {
        try {
            $db = $config->getDatabase();
            $user = $config->getUser();
            $connection =  new PostgresConnectionPool($config->withDatabase(null));

            $result = $connection->query("SELECT * FROM pg_database WHERE datname = '{$db}'");

            // Replace with getRowCount once it gets fixed
            if (!iterator_to_array($result)) {
                $connection->query("
                    CREATE DATABASE {$db}
                    OWNER {$user}
                    ENCODING utf8
                ");
            }
            $connection->close();
        } catch (Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
        }
    }
}
