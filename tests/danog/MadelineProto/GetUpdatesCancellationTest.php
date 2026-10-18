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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableResourceStream;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\TimeoutCancellation;
use Amp\TimeoutException;
use danog\AsyncOrm\Driver\MemoryArray;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\ExitFailure;
use danog\MadelineProto\Ipc\Wrapper;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplQueue;
use Throwable;

use function Amp\async;
use function Amp\delay;

/**
 * getUpdates() could only end through its timeout: the wait for updates can be cancelled, also through IPC.
 */
final class GetUpdatesCancellationTest extends TestCase
{
    private ?string $dir = null;

    public function tearDown(): void
    {
        if ($this->dir !== null) {
            exec('rm -rf '.escapeshellarg($this->dir));
        }
    }

    private static function api(): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        (function (): void {
            $this->updateQueue = new SplQueue;
            $this->getUpdatesQueue = new MemoryArray([]);
        })->call($API);
        return $API;
    }

    public function testCancellation(): void
    {
        $API = self::api();
        $deferred = new DeferredCancellation;
        $updates = async($API->getUpdates(...), ['timeout' => INF], $deferred->getCancellation());
        delay(0.1);
        $this->assertFalse($updates->isComplete());
        $deferred->cancel();
        $this->expectException(CancelledException::class);
        $updates->await();
    }

    public function testCancellationWithTimeout(): void
    {
        $deferred = new DeferredCancellation;
        $updates = async(self::api()->getUpdates(...), ['timeout' => 10.0], $deferred->getCancellation());
        delay(0.1);
        $deferred->cancel();
        $this->expectException(CancelledException::class);
        $updates->await();
    }

    /** A TimeoutCancellation of the caller is a cancellation too, not the timeout of getUpdates. */
    public function testTimeoutCancellationOfTheCaller(): void
    {
        $this->expectException(CancelledException::class);
        self::api()->getUpdates(['timeout' => 10.0], new TimeoutCancellation(0.1));
    }

    public function testTimeoutReturnsNothing(): void
    {
        $deferred = new DeferredCancellation;
        $this->assertSame([], self::api()->getUpdates(['timeout' => 0.1], $deferred->getCancellation()));
    }

    public function testUpdatesAreReturned(): void
    {
        $API = self::api();
        $deferred = new DeferredCancellation;
        $updates = async($API->getUpdates(...), ['timeout' => INF], $deferred->getCancellation());
        delay(0.1);
        (fn () => $this->addGetUpdatesUpdate(['_' => 'updateTest']))->call($API);
        $this->assertSame([['update_id' => 0, 'update' => ['_' => 'updateTest']]], $updates->await());
    }

    /**
     * Cancelling on the client side of the IPC connection stops the wait of the IPC server: the server-side proxy
     * of the cancellation is notified, and its CancelledException is sent back to the client.
     */
    public function testCancellationThroughIpc(): void
    {
        $this->dir = sys_get_temp_dir().'/madeline-getupdates-'.bin2hex(random_bytes(8));
        $session = new SessionPaths($this->dir);
        $logger = new Logger((new SettingsLogger)->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn () => null));
        $callbackServer = new IpcServer($session->getIpcCallbackPath());

        [$a, $b] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $server = new ChannelledSocket(new ReadableResourceStream($b), new WritableResourceStream($b));
        $client = new Client(new ChannelledSocket(new ReadableResourceStream($a), new WritableResourceStream($a)), $session, $logger);

        // What Server and ServerCallback do with a call: the client connects to the callback server first.
        $callback = async(static function () use ($callbackServer): ChannelledSocket {
            $callback = $callbackServer->accept();
            $callback->send(0);
            return $callback;
        });
        $API = self::api();
        $serverDone = async(static function () use ($server, $callback, $API): void {
            [$function, $arguments] = $server->receive();
            self::assertInstanceOf(Wrapper::class, $arguments);
            $callback = $callback->await();
            try {
                $result = $API->{$function}(...$arguments->unwrap($callback));
            } catch (Throwable $e) {
                $result = new ExitFailure($e);
            }
            $server->send([0, $result]);
            $arguments->disconnect();
        });

        $deferred = new DeferredCancellation;
        $updates = async($client->getUpdates(...), ['timeout' => INF], $deferred->getCancellation());
        delay(0.2);
        $this->assertFalse($updates->isComplete());
        $deferred->cancel();
        try {
            $updates->await(new TimeoutCancellation(5));
            $this->fail('getUpdates was not cancelled');
        } catch (CancelledException $e) {
            $this->assertNotInstanceOf(TimeoutException::class, $e->getPrevious());
        } finally {
            $serverDone->await(new TimeoutCancellation(5));
            $client->disconnect();
            $callbackServer->close();
        }
    }
}
