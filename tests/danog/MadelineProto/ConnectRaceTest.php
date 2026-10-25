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

use Amp\ByteStream\ClosedException;
use Amp\DeferredFuture;
use Closure;
use danog\Loop\Loop;
use danog\MadelineProto\API;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Connection as ConnectionSettings;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ContextIterator;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/** @internal */
abstract class FakeMTProtoStream implements MTProtoBufferInterface, BufferedStreamInterface
{
}

/**
 * Connection::connect() publishes the new stream, then starts the connection's loops: if it suspends in between (a
 * log line written to a full stdout pipe, a loop waiting for its previous run to exit), a disconnect() meanwhile
 * closes the stream but can't stop the loops not started yet, which connect() then starts anyway: the next
 * connect() found them running ("Could not start cleanup stream", magnaluna 8.11.12, after network issues).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class ConnectRaceTest extends TestCase
{
    /** Suspends the logging of the next line starting with $suspendAt until completed. */
    private ?DeferredFuture $suspend = null;
    private string $suspendAt = '';
    /** @var list<MTProtoBufferInterface> */
    private array $streams = [];
    /** @var list<int> Indexes of the streams disconnected. */
    private array $closed = [];

    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    private function connection(): Connection
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $logger = $this->createMock(Logger::class);
        $logger->method('logger')->willReturnCallback(function (mixed $param): void {
            if (\is_string($param) && $this->suspend && str_starts_with($param, $this->suspendAt)) {
                $suspend = $this->suspend;
                $this->suspend = null;
                $suspend->getFuture()->await();
            }
        });
        $API->logger = $logger;
        (new ReflectionProperty($API, 'settings'))->setValue($API, new Settings);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));

        $ctx = $this->createMock(ConnectionContext::class);
        $ctx->method('clone')->willReturnSelf();
        $ctx->method('getIpv6')->willReturn(false);
        $ctx->method('__toString')->willReturn('fake');
        $ctx->method('getStream')->willReturnCallback(function (): MTProtoBufferInterface {
            $stream = $this->getMockForAbstractClass(FakeMTProtoStream::class);
            // Nothing is received until it's disconnected, which fails the pending reads.
            $closed = new DeferredFuture;
            $stream->method('getReadBuffer')->willReturnCallback(static fn () => $closed->getFuture()->await());
            $index = \count($this->streams);
            $stream->method('disconnect')->willReturnCallback(function () use ($index, $closed): void {
                $this->closed[] = $index;
                $closed->error(new ClosedException('Disconnected'));
            });
            return $this->streams[] = $stream;
        });

        $shared = $this->createMock(DataCenterConnection::class);
        $shared->method('getCtxs')->willReturn(new ContextIterator([$ctx]));
        $shared->method('getSettings')->willReturn(new ConnectionSettings);
        // A CDN connection: no ping loop.
        Closure::bind(function () use ($API): void {
            $this->auth = new NewAuthKey(false, true, 2, $API->loginState);
        }, $shared, DataCenterConnection::class)();

        $connection = (new ReflectionClass(Connection::class))->newInstanceWithoutConstructor();
        $connection->API = $API;
        foreach (['shared' => $shared, 'datacenter' => 2, 'datacenterId' => '2_0', 'id' => 0] as $property => $value) {
            (new ReflectionProperty($connection, $property))->setValue($connection, $value);
        }
        return $connection;
    }

    /** @return array<string, ?Loop> */
    private static function loops(Connection $connection): array
    {
        $loops = [];
        foreach (['writer', 'reader', 'cleanup', 'handler'] as $name) {
            $loops[$name] = (new ReflectionProperty($connection, $name))->getValue($connection);
        }
        return $loops;
    }

    /**
     * Where the first reconnection suspends, and the error of the second one before the fix.
     *
     * @return array<string, array{string}>
     */
    public static function suspensionPoints(): array
    {
        return [
            // Before starting any loop: "Could not start writer stream".
            'after publishing the stream' => ['Connected to DC'],
            // Once the writer and the reader are started: "Could not start cleanup stream", as on magnaluna.
            'after starting the reader' => ['Started read loop'],
        ];
    }

    /**
     * @dataProvider suspensionPoints
     */
    public function testADisconnectWhileConnectingStopsTheLoopsStartedAfterIt(string $suspendAt): void
    {
        $connection = $this->connection();
        $connection->reconnect();
        foreach (self::loops($connection) as $name => $loop) {
            $this->assertTrue($loop->isRunning(), $name);
        }

        // The first reconnection suspends after publishing its stream, the second one disconnects it meanwhile.
        $this->suspendAt = $suspendAt;
        $this->suspend = $suspend = new DeferredFuture;
        $first = async($connection->reconnect(...));
        delay(0.01);
        $second = async($connection->reconnect(...));
        delay(0.01);
        $suspend->complete();
        await([$first, $second]);

        // The first reconnection connected again, the second one found it connected.
        $this->assertCount(3, $this->streams);
        $this->assertSame($this->streams[2], $connection->stream);
        $this->assertSame([0, 1], $this->closed);
        foreach (self::loops($connection) as $name => $loop) {
            $this->assertTrue($loop->isRunning(), $name);
        }
        $connection->disconnect(true);
        foreach (self::loops($connection) as $name => $loop) {
            if ($name !== 'handler') {
                $this->assertFalse($loop->isRunning(), $name);
            }
        }
    }
}
