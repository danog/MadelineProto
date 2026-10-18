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

namespace danog\MadelineProto\Ipc\Remote;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\ServerSocket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Connector;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Ipc;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Throwable;

use function Amp\Socket\listen;

/**
 * Accepts remote IPC connections on TCP and HTTP endpoints.
 *
 * @internal
 */
final class Listener implements RequestHandler
{
    /**
     * @var list<ServerSocket>
     */
    private array $sockets = [];
    /**
     * @var list<SocketHttpServer>
     */
    private array $httpServers = [];
    /**
     * Authenticated connections.
     *
     * @var \WeakMap<EncryptedChannel, true>
     */
    private \WeakMap $channels;

    /**
     * @param list<string>                                         $endpoints Endpoints
     * @param \Closure(EncryptedChannel, Handshake::TYPE_*): void $onChannel Invoked with each authenticated channel
     */
    public function __construct(
        private readonly string $secret,
        public readonly array $endpoints,
        private readonly \Closure $onChannel,
        LoggerInterface $logger,
    ) {
        $this->channels = new \WeakMap;
        try {
            foreach ($endpoints as $endpoint) {
                $parsed = Ipc::parseEndpoint($endpoint, false);
                $address = "{$parsed['host']}:{$parsed['port']}";
                if ($parsed['scheme'] === 'tcp') {
                    $socket = listen($address);
                    $this->sockets[] = $socket;
                    EventLoop::queue($this->acceptLoop(...), $socket);
                } else {
                    $server = SocketHttpServer::createForDirectAccess(
                        $logger,
                        enableCompression: false,
                        connectionLimit: PHP_INT_MAX,
                        connectionLimitPerIp: PHP_INT_MAX,
                        concurrencyLimit: null,
                    );
                    $server->expose($address);
                    $server->start($this, new DefaultErrorHandler);
                    $this->httpServers[] = $server;
                }
                Logger::log("Accepting remote IPC connections on $endpoint");
            }
        } catch (Throwable $e) {
            $this->close();
            throw new Exception("Could not listen for remote IPC connections: {$e->getMessage()}", 0, $e);
        }
    }

    private function acceptLoop(ServerSocket $server): void
    {
        while ($socket = $server->accept()) {
            EventLoop::queue($this->handshake(...), $socket);
        }
    }

    private function handshake(ReadableStream&WritableStream $socket): void
    {
        try {
            [$channel, $type] = Handshake::server($socket, $this->secret);
        } catch (Throwable $e) {
            Logger::log("Rejected remote IPC connection: {$e->getMessage()}", Logger::WARNING);
            return;
        }
        $this->channels[$channel] = true;
        ($this->onChannel)($channel, $type);
    }

    #[\Override]
    public function handleRequest(Request $request): Response
    {
        if (strtolower($request->getHeader('upgrade') ?? '') !== Connector::HTTP_UPGRADE) {
            return new Response(
                HttpStatus::UPGRADE_REQUIRED,
                ['upgrade' => Connector::HTTP_UPGRADE, 'connection' => 'Upgrade', 'content-type' => 'text/plain'],
                'This is a MadelineProto IPC endpoint',
            );
        }
        $response = new Response(headers: ['upgrade' => Connector::HTTP_UPGRADE, 'connection' => 'Upgrade']);
        $response->upgrade(function (ReadableStream&WritableStream $socket): void {
            EventLoop::queue($this->handshake(...), $socket);
        });
        return $response;
    }

    /**
     * Stop accepting connections.
     *
     * @param bool $disconnect Whether to also close the connections already accepted (when the secret changes)
     */
    public function close(bool $disconnect = false): void
    {
        if ($disconnect) {
            foreach ($this->channels as $channel => $_) {
                $channel->disconnect();
            }
        }
        foreach ($this->sockets as $socket) {
            $socket->close();
        }
        $this->sockets = [];
        foreach ($this->httpServers as $server) {
            $server->stop();
        }
        $this->httpServers = [];
    }
}
