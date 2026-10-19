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

use Amp\Socket\ResourceServerSocket;
use Amp\Socket\Socket;
use Closure;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Socket\connect;
use function Amp\Socket\listen;

/**
 * The generation and binding of the auth keys survive slow answers and session resets.
 *
 * The connections go through a local SOCKS5 proxy, which hands the frames sent by Telegram (abridged, not
 * obfuscated) to a filter before forwarding them.
 */
final class KeyExchangeTest extends TestCase
{
    /** How long the whole login may take, at most: less than the timeout of requests (60 seconds). */
    private const MAX_DURATION = 55.0;
    /** dh_gen_ok, the answer to set_client_DH_params. */
    private const DH_GEN_OK = 0x3bcbf734;

    private ResourceServerSocket $server;
    /** @var list<array{Socket, Socket}> The connections proxied so far. */
    private array $connections = [];
    private string $dir;
    private ?API $API = null;
    /** @var Closure(int, string): string Gets the index of the connection and a frame, returns the frame to forward. */
    private Closure $filter;

    public function setUp(): void
    {
        foreach (['API_ID', 'API_HASH'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        $this->server = listen('127.0.0.1:0');
        EventLoop::queue(function (): void {
            while ($client = $this->server->accept()) {
                EventLoop::queue($this->proxy(...), $client);
            }
        });
        $this->dir = sys_get_temp_dir().'/madeline-key-exchange-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        // Before removing its session: it is serialized when destroyed.
        $this->API = null;
        while (gc_collect_cycles());
        API::finalize();
        if (isset($this->server)) {
            $this->server->close();
        }
        foreach ($this->connections as [$client, $upstream]) {
            $client->close();
            $upstream->close();
        }
        if (isset($this->dir)) {
            exec('rm -rf '.escapeshellarg($this->dir));
        }
        Magic::$isIpcWorker = false;
    }

    /**
     * Serve a connection: SOCKS5, without authentication.
     */
    private function proxy(Socket $client): void
    {
        $buffer = '';
        $read = static function (int $length) use ($client, &$buffer): string {
            while (\strlen($buffer) < $length) {
                $chunk = $client->read();
                if ($chunk === null) {
                    throw new \RuntimeException('Closed');
                }
                $buffer .= $chunk;
            }
            $result = substr($buffer, 0, $length);
            $buffer = substr($buffer, $length);
            return $result;
        };
        try {
            $methods = \ord($read(2)[1]);
            $read($methods);
            $client->write("\x05\x00");
            $type = \ord($read(4)[3]);
            $host = match ($type) {
                1 => inet_ntop($read(4)),
                4 => '['.inet_ntop($read(16)).']',
                3 => $read(\ord($read(1))),
            };
            $port = unpack('n', $read(2))[1];
            $upstream = connect("tcp://$host:$port");
            $client->write("\x05\x00\x00\x01\x00\x00\x00\x00\x00\x00");
        } catch (Throwable) {
            $client->close();
            return;
        }
        $index = \count($this->connections);
        $this->connections[] = [$client, $upstream];
        if ($buffer !== '') {
            $upstream->write($buffer);
        }
        async(static function () use ($client, $upstream): void {
            while (($chunk = $client->read()) !== null) {
                $upstream->write($chunk);
            }
            $upstream->close();
        })->ignore();
        async(function () use ($client, $upstream, $index): void {
            // Abridged frames: the length in 4-byte words, in one byte or in 0x7f and three bytes.
            $buffer = '';
            while (($chunk = $upstream->read()) !== null) {
                $buffer .= $chunk;
                while ($buffer !== '') {
                    $length = \ord($buffer[0]);
                    $header = 1;
                    if ($length >= 127) {
                        if (\strlen($buffer) < 4) {
                            break;
                        }
                        $length = unpack('V', substr($buffer, 1, 3)."\0")[1];
                        $header = 4;
                    }
                    $length <<= 2;
                    if (\strlen($buffer) < $header + $length) {
                        break;
                    }
                    $frame = substr($buffer, $header, $length);
                    $client->write(substr($buffer, 0, $header).($this->filter)($index, $frame));
                    $buffer = substr($buffer, $header + $length);
                }
            }
            $client->close();
        })->ignore();
    }

    private static function isDhGenOk(string $frame): bool
    {
        return \strlen($frame) >= 24
            && str_starts_with($frame, "\0\0\0\0\0\0\0\0")
            && unpack('V', substr($frame, 20, 4))[1] === self::DH_GEN_OK;
    }

    private function login(): void
    {
        // In this process, along with the proxy.
        Magic::start(light: false);
        Magic::$isIpcWorker = true;
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash(getenv('API_HASH'));
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra(sys_get_temp_dir().'/madeline-key-exchange.log')->setLevel(Logger::VERBOSE);
        $settings->getConnection()
            ->setProtocol(AbridgedStream::class)
            ->setObfuscated(false)
            ->addProxy(SocksProxy::class, ['address' => '127.0.0.1', 'port' => $this->server->getAddress()->getPort()]);

        // No need to log in: generating and binding the keys is enough.
        $start = microtime(true);
        $API = $this->API = new API("{$this->dir}/session.madeline", $settings);
        $config = $API->help->getConfig();
        $duration = microtime(true) - $start;

        self::assertSame('config', $config['_']);
        self::assertNotEmpty($this->connections, 'The connections go through the proxy');
        self::assertLessThan(self::MAX_DURATION, $duration, 'The keys were generated and bound');
    }

    /**
     * set_client_DH_params is sent again when not answered in time (the connection timeout, 5 seconds), and
     * then answered twice: the second answer arrives after the key was generated, and is dropped.
     */
    public function testALateDuplicateAnswerOfTheKeyExchangeIsDropped(): void
    {
        $this->filter = static function (int $index, string $frame): string {
            if (self::isDhGenOk($frame)) {
                // Holding back the frames that follow too, so that the duplicate answer arrives last.
                delay(6);
            }
            return $frame;
        };
        $this->login();
    }

    /**
     * auth.bindTempAuthKey embeds its message ID and session ID: when the session is reset while it is waiting
     * for an answer, it's built again instead of being sent again.
     */
    public function testABindingInterruptedByASessionResetIsDoneAgain(): void
    {
        $generated = [];
        $corrupted = false;
        $this->filter = static function (int $index, string $frame) use (&$generated, &$corrupted): string {
            if (self::isDhGenOk($frame)) {
                $generated[$index] = true;
            } elseif (!$corrupted
                && isset($generated[$index])
                && \strlen($frame) > 24
                && !str_starts_with($frame, "\0\0\0\0\0\0\0\0")
            ) {
                // The first encrypted message after generating the keys, while binding them: a msg_key
                // mismatch resets the session.
                $corrupted = true;
                $frame[8] = \chr(\ord($frame[8]) ^ 1);
            }
            return $frame;
        };
        $this->login();
        self::assertTrue($corrupted, 'A message was corrupted while binding the keys');
    }
}
