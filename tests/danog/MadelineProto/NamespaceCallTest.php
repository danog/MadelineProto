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
use Amp\Future;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\API;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Logger;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Error;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

use function Amp\async;
use function Amp\delay;

/**
 * The namespaces ($api->channels, ...) of an API that is no longer referenced anywhere else (#1606).
 *
 * @internal
 */
final class NamespaceCallTest extends TestCase
{
    private string $session;
    /** @var list<array> Requests received by the fake IPC server */
    private array $calls = [];
    /** Completes when the client disconnects from the fake IPC server */
    private Future $disconnected;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = sys_get_temp_dir().'/madeline-namespace-call-test-'.bin2hex(random_bytes(8));
    }
    #[\Override]
    protected function tearDown(): void
    {
        API::finalize();
        if (is_dir($this->session)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->session, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->session);
        }
    }

    /**
     * `$service->api()->channels->createChannel()`: the temporary API is freed as soon as the namespace is fetched,
     * so only the namespace keeps it alive during the call.
     */
    public function testACallOnATemporaryApiReturns(): void
    {
        $result = $this->makeApi()->channels->createChannel(title: 'test', about: 'test', broadcast: true);

        self::assertSame(['_' => 'updates', 'n' => 0], $result);
        self::assertCount(1, $this->calls);
        self::assertSame('methodCallAsyncRead', $this->calls[0][0]);
        self::assertSame('channels.createChannel', $this->calls[0][1][0]);
        self::assertSame('test', $this->calls[0][1][1]['title']);

        // Once the namespace is gone too, the destructor disconnects
        API::finalize();
        $this->disconnected->await();
    }

    /**
     * A namespace kept after the API is gone keeps working across suspensions and multiple calls.
     */
    public function testAHeldNamespaceKeepsTheApiAlive(): void
    {
        $channels = $this->makeApi()->channels;
        // The destructor of the API would run at the first suspension
        delay(0.01);
        self::assertSame(['_' => 'updates', 'n' => 0], $channels->createChannel(title: 'a', about: '', broadcast: true));
        self::assertSame(['_' => 'updates', 'n' => 1], $channels->createChannel(title: 'b', about: '', broadcast: true));
        API::finalize();
        self::assertFalse($this->disconnected->isComplete());

        unset($channels);
        API::finalize();
        $this->disconnected->await();
        self::assertCount(2, $this->calls);
    }

    public function testNamespaceProperties(): void
    {
        $api = $this->makeApi();
        self::assertTrue(isset($api->messages));
        self::assertFalse(isset($api->nonexistent));
        // A new object at each access: caching it in the API would be a reference cycle
        self::assertNotSame($api->messages, $api->messages);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Undefined property: danog\MadelineProto\API::$nonexistent');
        $api->nonexistent;
    }

    /**
     * An API connected to a fake IPC server, which answers every request with its index.
     */
    private function makeApi(): API
    {
        $toServer = new Pipe(1024*1024);
        $toClient = new Pipe(1024*1024);
        $server = new ChannelledSocket($toServer->getSource(), $toClient->getSink());
        $this->disconnected = async(function () use ($server): void {
            $id = 0;
            while ($payload = $server->receive()) {
                $this->calls[] = $payload;
                // Telegram takes a while to answer: the destructor of a temporary API runs first
                delay(0.01);
                $server->send([$id, ['_' => 'updates', 'n' => $id]]);
                $id++;
            }
        });

        Logger::$default ??= new Logger((new LoggerSettings)->setType(Logger::CALLABLE_LOGGER)->setExtra(static function (): void {}));
        $session = new SessionPaths($this->session);
        $wrapper = new APIWrapper($session);
        $wrapper->setAPI(new Client(new ChannelledSocket($toClient->getSource(), $toServer->getSink()), $session, Logger::$default));

        // The constructor would connect to a real IPC server
        $class = new ReflectionClass(API::class);
        $api = $class->newInstanceWithoutConstructor();
        $class->getProperty('session')->setValue($api, $session);
        $class->getProperty('wrapper')->setValue($api, $wrapper);
        return $api;
    }
}
