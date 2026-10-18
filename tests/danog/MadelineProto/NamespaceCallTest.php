<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\ByteStream\Pipe;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\API;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Logger;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function Amp\async;
use function Amp\delay;

/** @internal */
final class NamespaceCallTest extends TestCase
{
    /**
     * The namespace objects only hold the APIWrapper, so a temporary API (`$service->api()->channels->createChannel()`)
     * is destroyed while the call is in progress: its connection must survive until the call returns (#1606).
     */
    public function testACallOnATemporaryApiReturns(): void
    {
        $toServer = new Pipe(1024*1024);
        $toClient = new Pipe(1024*1024);
        $server = new ChannelledSocket($toServer->getSource(), $toClient->getSink());
        $calls = [];
        $worker = async(static function () use ($server, &$calls): void {
            $id = 0;
            while ($payload = $server->receive()) {
                $calls[] = $payload;
                // Reply after the destructor of the temporary API had the chance to run
                delay(0.1);
                $server->send([$id++, ['_' => 'updates']]);
            }
        });

        $result = self::makeApi(new ChannelledSocket($toClient->getSource(), $toServer->getSink()))
            ->channels->createChannel(title: 'test', about: 'test', broadcast: true);

        self::assertSame(['_' => 'updates'], $result);
        self::assertCount(1, $calls);
        self::assertSame('methodCallAsyncRead', $calls[0][0]);
        self::assertSame('channels.createChannel', $calls[0][1][0]);
        self::assertSame('test', $calls[0][1][1]['title']);

        // The destructor still disconnects once the call is done
        API::finalize();
        $worker->await();
    }

    private static function makeApi(ChannelledSocket $socket): API
    {
        Logger::$default ??= new Logger((new LoggerSettings)->setType(Logger::CALLABLE_LOGGER)->setExtra(static function (): void {}));
        $session = new SessionPaths(sys_get_temp_dir().'/madeline-namespace-call-test');
        $wrapper = new APIWrapper($session);
        $wrapper->setAPI(new Client($socket, $session, Logger::$default));

        $class = new ReflectionClass(API::class);
        $api = $class->newInstanceWithoutConstructor();
        $class->getProperty('session')->setValue($api, $session);
        $class->getProperty('wrapper')->setValue($api, $wrapper);
        $class->getMethod('exportNamespaces')->invoke($api);
        return $api;
    }
}
