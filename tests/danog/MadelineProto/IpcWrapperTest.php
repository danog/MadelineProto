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
use Amp\DeferredFuture;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Ipc\Wrapper;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Revolt\EventLoop;

use function Amp\delay;

/**
 * The callbacks of an IPC call (the progress callbacks of an upload, for example) can still be running when the
 * connection of their wrapper is closed, once the call returned: their results belong to nobody, they're not errors.
 */
final class IpcWrapperTest extends TestCase
{
    /** @var list<string> What the wrapper logged. */
    private array $log = [];

    /**
     * The callback of a call (a progress callback logging through the IPC client, in tests/testing.php) returns just
     * as the server closes the connection, because the call returned: before the receiver loop of the wrapper saw it.
     */
    public function testACallbackReturningOnceTheConnectionClosed(): void
    {
        [$a, $b] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $client = new ChannelledSocket(new ReadableResourceStream($a), new WritableResourceStream($a));
        $server = new ChannelledSocket(new ReadableResourceStream($b), new WritableResourceStream($b));
        $logger = new Logger((new SettingsLogger)->setType(Logger::CALLABLE_LOGGER)->setExtra(function (mixed $message): void {
            $this->log[] = (string) $message;
        })->setLevel(Logger::ULTRA_VERBOSE));
        $returned = new DeferredFuture;
        $wrapper = (new ReflectionClass(Wrapper::class))->newInstanceWithoutConstructor();
        (function () use ($client, $logger, $returned): void {
            $this->server = $client;
            $this->logger = $logger;
            $this->callbacks = [0 => static function () use ($client, $returned): void {
                // The connection is closed (as by receive(), getting the close request of the server) and the
                // callback returns without suspending: the receiver loop didn't get to see it yet.
                $client->disconnect();
                EventLoop::queue($returned->complete(...));
            }];
            EventLoop::queue($this->receiverLoop(...));
        })->call($wrapper);

        $server->send([0, []]);
        $returned->getFuture()->await();
        delay(0.1);

        $log = implode("\n", $this->log);
        $this->assertStringNotContainsString('Got error while trying to send', $log);
        $this->assertStringContainsString('Not sending the result of reverse method 0, the connection was closed', $log);
    }
}
