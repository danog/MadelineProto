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
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\TimeoutCancellation;
use Amp\TimeoutException;
use Closure;
use danog\MadelineProto\API;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
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
use RuntimeException;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/** @internal */
abstract class FakeOutageStream implements MTProtoBufferInterface, BufferedStreamInterface
{
}

/**
 * While no way to connect to a DC works (the network is down), Connection::connect() throws: called by whatever
 * needed a connection, including background work (a reconnection, the rpc_drop_answer of a timed-out request), the
 * exception escaped as an unhandled one and killed the event loop of the script. Messages are now queued until the
 * connection attempts, retried with a backoff, connect; a connection closed for good stops them.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class NetworkOutageTest extends TestCase
{
    private bool $down = true;
    /** @var list<float> When each connection attempt was made. */
    private array $attempts = [];
    private DataCenterConnection $shared;
    /** Suspends the next connection attempt until completed. */
    private ?DeferredFuture $hold = null;
    /** How many streams were disconnected. */
    private int $disconnected = 0;

    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    private function connection(): Connection
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = $this->createMock(Logger::class);
        (new ReflectionProperty($API, 'settings'))->setValue($API, new Settings);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));

        $ctx = $this->createMock(ConnectionContext::class);
        $ctx->method('clone')->willReturnSelf();
        $ctx->method('getIpv6')->willReturn(false);
        $ctx->method('__toString')->willReturn('fake');
        $ctx->method('getStream')->willReturnCallback(function (): FakeOutageStream {
            $this->attempts[] = hrtime(true) / 1e9;
            if ($this->hold) {
                $hold = $this->hold;
                $this->hold = null;
                $hold->getFuture()->await();
            }
            if ($this->down) {
                throw new RuntimeException('Network is unreachable');
            }
            $stream = $this->getMockForAbstractClass(FakeOutageStream::class);
            // Nothing is received until it's disconnected, which fails the pending reads.
            $closed = new DeferredFuture;
            $stream->method('getReadBuffer')->willReturnCallback(static fn () => $closed->getFuture()->await());
            $stream->method('disconnect')->willReturnCallback(function () use ($closed): void {
                $this->disconnected++;
                if (!$closed->isComplete()) {
                    $closed->error(new ClosedException('Disconnected'));
                }
            });
            return $stream;
        });

        $this->shared = $shared = $this->createMock(DataCenterConnection::class);
        $shared->method('getCtxs')->willReturn(new ContextIterator([$ctx]));
        $shared->method('getSettings')->willReturn(new ConnectionSettings);
        // A CDN connection: no ping loop, unencrypted messages.
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

    private function message(Connection $connection, float $timeout = 60): MTProtoOutgoingMessage
    {
        $message = new MTProtoOutgoingMessage(
            connection: $connection,
            body: [],
            constructor: 'req_pq_multi',
            type: 'ResPQ',
            isMethod: true,
            unencrypted: true,
            specialMethodType: null,
            cancellation: new TimeoutCancellation($timeout, 'Timeout while waiting for req_pq_multi'),
            resultDeferred: new DeferredFuture,
        );
        $message->setSerializedBody(str_repeat("\0", 20));
        return $message;
    }

    private static function connecting(Connection $connection): ?Future
    {
        return (new ReflectionProperty($connection, 'connecting'))->getValue($connection);
    }

    public function testMessagesAreSentOnceConnectedWithOneAttemptAtATime(): void
    {
        $connection = $this->connection();

        // Concurrent senders: queued, they make one attempt between them, and the retries back off.
        $messages = [];
        for ($x = 0; $x < 5; $x++) {
            $messages[] = $message = $this->message($connection);
            async($connection->sendMessage(...), $message);
        }
        delay(0.1);
        $this->assertCount(1, $this->attempts, 'one attempt for all the senders');
        $this->assertNotNull(self::connecting($connection));
        $this->assertNull($connection->stream);
        // More messages while the retries wait: queued, no attempt.
        $messages[] = $message = $this->message($connection);
        $connection->sendMessage($message);
        $this->assertCount(1, $this->attempts);

        // The retries: 1 second later, then 2 more.
        delay(1.2);
        $this->assertCount(2, $this->attempts);
        $this->down = false;
        await(array_map(static fn (MTProtoOutgoingMessage $message) => $message->getSendPromise(), $messages));
        $this->assertCount(3, $this->attempts);
        // Timers never fire early; late by less than a second even on a loaded machine.
        $this->assertGreaterThanOrEqual(1.0, $this->attempts[1] - $this->attempts[0]);
        $this->assertLessThan(2.0, $this->attempts[1] - $this->attempts[0]);
        $this->assertGreaterThanOrEqual(2.0, $this->attempts[2] - $this->attempts[1]);
        $this->assertLessThan(3.0, $this->attempts[2] - $this->attempts[1]);
        $this->assertNotNull($connection->stream);
        $this->assertNull(self::connecting($connection));
        foreach ($messages as $message) {
            $this->assertTrue($message->wasSent());
        }

        $connection->disconnect(true);
    }

    public function testATimeoutSaysWhyItCouldNotConnect(): void
    {
        $connection = $this->connection();
        $message = $this->message($connection, 0.5);
        $connection->sendMessage($message);
        try {
            // Replied to with a closure that throws the error.
            $message->getResultPromise()->await()();
            $this->fail('Not timed out');
        } catch (CancelledException $e) {
            $timeout = $e->getPrevious();
            $this->assertInstanceOf(TimeoutException::class, $timeout);
            $this->assertSame(
                'Timeout while waiting for req_pq_multi (Could not connect to DC 2_0: Network is unreachable)',
                substr($timeout->getMessage(), 0, strcspn($timeout->getMessage(), "\r\n")),
            );
        }
        $connection->disconnect();
    }

    public function testClosingStopsTheRetries(): void
    {
        $connection = $this->connection();
        $connection->sendMessage($this->message($connection));
        $connecting = self::connecting($connection);
        $this->assertNotNull($connecting);

        // Closed for good while waiting for the next attempt: the retries stop right away.
        $start = hrtime(true);
        $connection->disconnect();
        $connecting->await();
        $this->assertLessThan(0.1, (hrtime(true) - $start) / 1e9);
        $this->assertCount(1, $this->attempts);

        // Never connected again: not by a reconnection, nor by a message, which goes to another connection of the DC.
        $this->down = false;
        $connection->reconnect();
        $other = $this->createMock(Connection::class);
        $message = $this->message($connection);
        $other->expects($this->once())->method('sendMessage')->with($message);
        $this->shared->method('waitGetConnection')->willReturn($other);
        $connection->sendMessage($message);
        delay(0.1);
        $this->assertCount(1, $this->attempts);
        $this->assertNull($connection->stream);
        $this->assertNull(self::connecting($connection));
    }

    public function testClosingWhileConnectingDoesNotConnect(): void
    {
        $connection = $this->connection();
        $this->down = false;
        // Closed for good while the attempt connects: its stream is disconnected, no loop runs.
        $this->hold = $hold = new DeferredFuture;
        $reconnect = async($connection->reconnect(...));
        delay(0.01);
        $this->assertCount(1, $this->attempts);
        $connection->disconnect();
        $hold->complete();
        $reconnect->await();
        $this->assertNull($connection->stream);
        $this->assertSame(1, $this->disconnected);
        $this->assertCount(1, $this->attempts);
        $this->assertNull(self::connecting($connection));
        foreach (['writer', 'reader', 'cleanup', 'handler'] as $loop) {
            $this->assertNull((new ReflectionProperty($connection, $loop))->getValue($connection), $loop);
        }
    }
}
