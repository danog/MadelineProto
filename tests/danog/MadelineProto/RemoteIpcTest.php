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

use Amp\DeferredFuture;
use Amp\Ipc\Sync\ChannelException;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Connector;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\Ipc\Remote\AuthenticationException;
use danog\MadelineProto\Ipc\Remote\EncryptedChannel;
use danog\MadelineProto\Ipc\Remote\Handshake;
use danog\MadelineProto\Ipc\Remote\Listener;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

use function Amp\async;
use function Amp\Socket\createSocketPair;
use function Amp\Socket\listen;

/**
 * Remote IPC connections: handshake, encrypted channel, TCP and HTTP listeners.
 */
final class RemoteIpcTest extends TestCase
{
    private const SECRET = 'correct horse battery staple';

    /**
     * Set when an object is unserialized: must never happen with an unauthenticated peer.
     */
    public static bool $unserialized = false;

    protected function setUp(): void
    {
        self::$unserialized = false;
    }

    /**
     * @return array{EncryptedChannel, EncryptedChannel, int}
     */
    private static function pair(string $clientSecret = self::SECRET, string $serverSecret = self::SECRET, int $type = Handshake::TYPE_MAIN): array
    {
        [$a, $b] = createSocketPair();
        $server = async(Handshake::server(...), $b, $serverSecret);
        $client = Handshake::client($a, $clientSecret, $type);
        [$server, $serverType] = $server->await();
        return [$client, $server, $serverType];
    }

    public function testRoundTrip(): void
    {
        [$client, $server, $type] = self::pair(type: Handshake::TYPE_CALLBACK);
        $this->assertSame(Handshake::TYPE_CALLBACK, $type);

        $big = random_bytes(3 << 20);
        // Writes wait for the peer to read once the socket buffers are full.
        $sent = async(static function () use ($client, $big): void {
            $client->send(['getSelf', []]);
            $client->send($big);
            $client->send(false);
        });
        $this->assertSame(['getSelf', []], $server->receive());
        $this->assertSame($big, $server->receive());
        $this->assertFalse($server->receive());
        $sent->await();

        $server->send([0, ['id' => 1]]);
        $this->assertSame([0, ['id' => 1]], $client->receive());

        $client->disconnect();
        $this->assertNull($server->receive());
    }

    public function testWrongSecretIsRejectedBeforeUnserializing(): void
    {
        [$a, $b] = createSocketPair();
        $server = async(Handshake::server(...), $b, self::SECRET);
        try {
            Handshake::client($a, 'wrong horse battery staple', Handshake::TYPE_MAIN);
            $this->fail('The client authenticated a server with another secret');
        } catch (AuthenticationException $e) {
            $this->assertStringContainsString('rejected our IPC secret', $e->getMessage());
        }
        try {
            $server->await();
            $this->fail('The server authenticated a client with another secret');
        } catch (Exception $e) {
            $this->assertStringContainsString('wrong IPC secret', $e->getMessage());
        }
        $this->assertFalse(self::$unserialized);
    }

    public function testGarbageIsRejected(): void
    {
        [$a, $b] = createSocketPair();
        $server = async(Handshake::server(...), $b, self::SECRET);
        // A plain IPC client (or anything else) talking to a remote endpoint: a serialized object instead of the magic.
        $a->write(serialize(new RemoteIpcTestWakeup).str_repeat("\0", 128));
        $this->expectException(Exception::class);
        try {
            $server->await();
        } finally {
            $this->assertFalse(self::$unserialized);
        }
    }

    public function testTamperedRecordIsRejected(): void
    {
        [$a, $b] = createSocketPair();
        $server = async(Handshake::server(...), $b, self::SECRET);
        $client = Handshake::client($a, self::SECRET, Handshake::TYPE_MAIN);
        [$server] = $server->await();

        // Flip a bit of the ciphertext of a record carrying an object, on the wire.
        $payload = serialize(new RemoteIpcTestWakeup);
        $property = new \ReflectionProperty(EncryptedChannel::class, 'encrypt');
        $aes = $property->getValue($client);
        $header = pack('N', \strlen($payload));
        $aes->setNonce("\0\0\0\0".pack('J', 0));
        $aes->setAAD($header);
        $ciphertext = $aes->encrypt($payload);
        $ciphertext[0] = $ciphertext[0] ^ "\1";
        $a->write($header.$ciphertext.$aes->getTag(16));

        try {
            $server->receive();
            $this->fail('A tampered record was accepted');
        } catch (ChannelException $e) {
            $this->assertStringContainsString('authenticated', $e->getMessage());
        }
        $this->assertFalse(self::$unserialized);
        $this->assertNull($server->receive());
    }

    public function testOversizedRecordIsRejected(): void
    {
        [$a, $b] = createSocketPair();
        $server = async(Handshake::server(...), $b, self::SECRET);
        Handshake::client($a, self::SECRET, Handshake::TYPE_MAIN);
        [$server] = $server->await();
        // Only the header: the receiver must not wait for 4 GiB.
        $a->write("\xff\xff\xff\xff");
        try {
            $server->receive();
            $this->fail('An oversized record was accepted');
        } catch (ChannelException $e) {
            $this->assertStringContainsString('record length', $e->getMessage());
        }
    }

    public function testSecretChangeDisconnects(): void
    {
        $port = self::freePort();
        $accepted = new DeferredFuture;
        $listener = new Listener(
            self::SECRET,
            ["tcp://127.0.0.1:$port"],
            static fn (EncryptedChannel $channel) => $accepted->complete($channel),
            new NullLogger,
        );
        $connector = Connector::fromSettings(
            new SessionPaths(sys_get_temp_dir().'/not-created.madeline', create: false),
            (new Ipc)->setEndpoint("tcp://127.0.0.1:$port")->setSecret(self::SECRET),
        );
        $client = $connector->connect();
        $accepted->getFuture()->await();
        $listener->close(disconnect: true);
        $this->assertNull($client->receive());
    }

    /**
     * File names chosen by the sender (audio titles) are joined to the directory of downloadToDir.
     */
    public function testDownloadNamesStayInTheDirectory(): void
    {
        $this->assertSame('.._.._etc_passwd - x', Tools::sanitizeFileName('../../etc/passwd - x'));
        $this->assertSame('_', Tools::sanitizeFileName('..'));
        $this->assertSame('a_b_c', Tools::sanitizeFileName("a\\b\0c"));
    }

    /**
     * IPC-capable objects sent by a remote server carry its session name: they must be bound to the client that received them.
     */
    public function testIpcCapableObjectsAreBoundToTheReceivingClient(): void
    {
        [$client, $server] = self::pair();
        $owner = (new \ReflectionClass(Client::class))->newInstanceWithoutConstructor();
        $client->setOwner($owner);

        $object = new RemoteIpcTestCapable;
        (fn () => $this->session = '/data/session.madeline')->call($object);
        $server->send($object);
        $received = $client->receive();

        $this->assertInstanceOf(RemoteIpcTestCapable::class, $received);
        $this->assertSame($owner, \Closure::bind(fn () => $this->API, $received, IpcCapable::class)());
        $this->assertNull(IpcCapable::$remoteClient);

        // Not bound when unserialized elsewhere.
        $plain = unserialize(serialize($object));
        $this->assertFalse(\Closure::bind(fn () => isset($this->API), $plain, IpcCapable::class)());
    }

    public function testSecretMinimumLength(): void
    {
        $this->expectException(Exception::class);
        (new Ipc)->setSecret('short');
    }

    public function testEndpointValidation(): void
    {
        $this->assertSame(['scheme' => 'https', 'host' => 'example.com', 'port' => 443, 'path' => '/ipc'], Ipc::parseEndpoint('https://example.com/ipc', true));
        $this->assertSame(['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => 6100, 'path' => '/'], Ipc::parseEndpoint('tcp://127.0.0.1:6100', false));
        $this->expectException(Exception::class);
        // Servers don't terminate TLS: that's the job of the reverse proxy.
        (new Ipc)->setListen(['https://0.0.0.0:443']);
    }

    private static function freePort(): int
    {
        $socket = listen('127.0.0.1:0');
        $port = $socket->getAddress()->getPort();
        $socket->close();
        return $port;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSchemes(): iterable
    {
        yield 'tcp' => ['tcp'];
        yield 'http' => ['http'];
    }

    /**
     * @dataProvider provideSchemes
     */
    public function testListener(string $scheme): void
    {
        $port = self::freePort();
        /** @var DeferredFuture<list{EncryptedChannel, int}> */
        $accepted = new DeferredFuture;
        $channels = [];
        $listener = new Listener(
            self::SECRET,
            ["$scheme://127.0.0.1:$port"],
            static function (EncryptedChannel $channel, int $type) use (&$accepted, &$channels): void {
                $channels[] = [$channel, $type];
                if (\count($channels) === 2) {
                    $accepted->complete($channels);
                }
            },
            new NullLogger,
        );
        try {
            $settings = (new Ipc)
                ->setEndpoint("$scheme://127.0.0.1:$port".($scheme === 'http' ? '/some/path' : ''))
                ->setSecret(self::SECRET);
            $this->assertFalse($settings->getAutostart());
            $connector = Connector::fromSettings(new SessionPaths(sys_get_temp_dir().'/not-created.madeline', create: false), $settings);
            $this->assertTrue($connector->isRemote());
            $this->assertDirectoryDoesNotExist(sys_get_temp_dir().'/not-created.madeline');

            $main = $connector->connect();
            $callback = $connector->connectCallback();
            [[$serverMain, $mainType], [$serverCallback, $callbackType]] = $accepted->getFuture()->await();
            $this->assertSame(Handshake::TYPE_MAIN, $mainType);
            $this->assertSame(Handshake::TYPE_CALLBACK, $callbackType);

            $main->send('ping');
            $this->assertSame('ping', $serverMain->receive());
            $serverMain->send('pong');
            $this->assertSame('pong', $main->receive());
            $serverCallback->send(42);
            $this->assertSame(42, $callback->receive());

            $wrong = Connector::fromSettings(
                new SessionPaths(sys_get_temp_dir().'/not-created.madeline', create: false),
                (new Ipc)->setEndpoint("$scheme://127.0.0.1:$port")->setSecret(strrev(self::SECRET)),
            );
            $start = microtime(true);
            try {
                // Not retried, unlike servers that aren't running.
                $wrong->connectWaiting();
                $this->fail('Connected with the wrong secret');
            } catch (AuthenticationException) {
            }
            $this->assertLessThan(5, microtime(true) - $start);
            $main->disconnect();
            $callback->disconnect();
        } finally {
            $listener->close();
        }
    }

    /**
     * Clients send their settings to the server: they must not reset its endpoints.
     */
    public function testClientSettingsDontResetServer(): void
    {
        $server = new Settings;
        $server->getIpc()->setListen(['tcp://0.0.0.0:6100'])->setSecret(self::SECRET);
        $server->getIpc()->applyChanges();

        $client = new Settings;
        $client->getIpc()->setEndpoint('tcp://example.com:6100')->setSecret(self::SECRET)->setAutostart(false);
        $server->merge($client);
        $this->assertSame(['tcp://0.0.0.0:6100'], $server->getIpc()->getListen());
        $this->assertNull($server->getIpc()->getEndpoint());
        $this->assertTrue($server->getIpc()->getAutostart());
        $this->assertFalse($server->getIpc()->hasChanged());

        $server->merge(new Settings);
        $this->assertSame(['tcp://0.0.0.0:6100'], $server->getIpc()->getListen());
        $this->assertSame(self::SECRET, $server->getIpc()->getSecret());

        // Explicitly set endpoints are merged, even if empty.
        $server->merge((new Ipc)->setListen([]));
        $this->assertSame([], $server->getIpc()->getListen());
        $this->assertTrue($server->getIpc()->hasChanged());
    }

    public function testRemoteEndpointNeedsSecret(): void
    {
        $this->expectException(Exception::class);
        Connector::fromSettings(
            new SessionPaths(sys_get_temp_dir().'/not-created.madeline', create: false),
            (new Ipc)->setEndpoint('tcp://127.0.0.1:1'),
        );
    }
}

/**
 * @internal
 */
final class RemoteIpcTestWakeup
{
    public function __wakeup(): void
    {
        RemoteIpcTest::$unserialized = true;
    }
}

/**
 * @internal
 */
final class RemoteIpcTestCapable extends IpcCapable
{
    public function __construct()
    {
    }
}
