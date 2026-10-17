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
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\Socket\connect;
use function Amp\Socket\listen;

/**
 * A connection to Telegram that silently stops working (a half-open TCP connection, dropped along the way by
 * a NAT, for example) is replaced: nothing errors out on it and nothing ever arrives, so the requests sent
 * on it would only fail when they time out.
 *
 * The connections go through a local SOCKS5 proxy, which stops forwarding them (keeping them open) when
 * frozen.
 */
final class DeadConnectionTest extends TestCase
{
    /** How long a request may take, at most: less than the timeout of requests (60 seconds). */
    private const MAX_DURATION = 55.0;

    private ResourceServerSocket $server;
    /** @var list<array{Socket, Socket}> The connections proxied so far. */
    private array $connections = [];
    private string $dir;

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
        $this->dir = sys_get_temp_dir().'/madeline-dead-connection-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
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
        // Forward until frozen, if this connection was open by then: nothing arrives, nothing errors out.
        $forward = function (Socket $from, Socket $to) use ($index): void {
            while (($chunk = $from->read()) !== null) {
                if ($index < $this->frozenCount) {
                    continue;
                }
                $to->write($chunk);
            }
            $to->close();
        };
        async($forward, $client, $upstream)->ignore();
        async($forward, $upstream, $client)->ignore();
    }

    /** How many connections (the first ones) are frozen. */
    private int $frozenCount = 0;

    public function testADeadConnectionIsReplaced(): void
    {
        // In this process, along with the proxy.
        Magic::start(light: false);
        Magic::$isIpcWorker = true;
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash(getenv('API_HASH'));
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra("{$this->dir}/MadelineProto.log")->setLevel(Logger::VERBOSE);
        $settings->getConnection()->addProxy(SocksProxy::class, ['address' => '127.0.0.1', 'port' => $this->server->getAddress()->getPort()]);
        // No need to log in.
        $API = new API("{$this->dir}/session.madeline", $settings);
        $API->help->getConfig();
        self::assertNotEmpty($this->connections, 'The connections go through the proxy');

        // Every connection open so far stops forwarding, without being closed.
        $this->frozenCount = \count($this->connections);
        $start = microtime(true);
        $config = $API->help->getConfig();
        $duration = microtime(true) - $start;

        self::assertSame('config', $config['_']);
        self::assertGreaterThan($this->frozenCount, \count($this->connections), 'A new connection was opened');
        self::assertLessThan(self::MAX_DURATION, $duration, 'The request was answered over a new connection');
    }
}
