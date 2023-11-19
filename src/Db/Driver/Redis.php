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

use Amp\Redis\Connection\ReconnectingRedisLink;
use Amp\Redis\RedisClient;
use Amp\Redis\RedisConfig;
use Amp\Sync\LocalKeyedMutex;
use danog\MadelineProto\Settings\Database\Redis as DatabaseRedis;
use Revolt\EventLoop;

use function Amp\Redis\createRedisConnector;

/**
 * Redis driver wrapper.
 *
 * @internal
 */
final class Redis
{
    /** @var array<RedisClient> */
    private static array $connections = [];

    private static ?LocalKeyedMutex $mutex = null;
    public static function getConnection(DatabaseRedis $settings): RedisClient
    {
        self::$mutex ??= new LocalKeyedMutex;
        $dbKey = $settings->getDbIdentifier();
        $lock = self::$mutex->acquire($dbKey);

        try {
            if (empty(self::$connections[$dbKey])) {
                $config = RedisConfig::fromUri($settings->getUri())
                    ->withPassword($settings->getPassword())
                    ->withDatabase($settings->getDatabase());

                self::$connections[$dbKey] = new RedisClient(new ReconnectingRedisLink(createRedisConnector($config)));
                self::$connections[$dbKey]->ping();
            }
        } finally {
            EventLoop::queue($lock->release(...));
        }

        return self::$connections[$dbKey];
    }
}
