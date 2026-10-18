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

namespace danog\MadelineProto\Ipc;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\HttpStatus;
use Amp\Ipc\Sync\ChannelException;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Remote\AuthenticationException;
use danog\MadelineProto\Ipc\Remote\EncryptedChannel;
use danog\MadelineProto\Ipc\Remote\Handshake;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Serialization;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\SettingsAbstract;
use Throwable;
use WeakReference;

use function Amp\delay;
use function Amp\Ipc\connect;
use function Amp\Socket\connect as socketConnect;

/**
 * Connects IPC clients to the IPC server of a session: the local one of the session folder, optionally starting
 * it, or a remote one.
 *
 * @internal
 */
final class Connector
{
    /**
     * HTTP upgrade token of remote IPC connections over HTTP.
     */
    public const HTTP_UPGRADE = 'madelineproto-ipc';
    /**
     * How long to wait for an IPC server that isn't started automatically when connecting for the first time, in seconds.
     */
    private const CONNECT_TIMEOUT = 60;

    private static ?HttpClient $httpClient = null;

    /**
     * Client using this connector.
     *
     * @var ?WeakReference<Client>
     */
    private ?WeakReference $client = null;

    private function __construct(
        public readonly SessionPaths $session,
        public readonly bool $autostart,
        private readonly ?string $endpoint,
        private readonly ?string $secret,
    ) {
        if ($endpoint !== null && $secret === null) {
            throw new Exception("An IPC secret is required to connect to the remote IPC endpoint $endpoint!");
        }
    }

    /**
     * Get the IPC settings out of any settings object.
     */
    public static function getIpcSettings(SettingsAbstract $settings): Ipc
    {
        if ($settings instanceof Settings) {
            return $settings->getIpc();
        }
        if ($settings instanceof Ipc) {
            return $settings;
        }
        return new Ipc;
    }

    public static function fromSettings(SessionPaths $session, SettingsAbstract $settings): self
    {
        $settings = self::getIpcSettings($settings);
        return new self($session, $settings->getAutostart(), $settings->getEndpoint(), $settings->getSecret());
    }

    /**
     * Whether we connect to a remote IPC server.
     */
    public function isRemote(): bool
    {
        return $this->endpoint !== null;
    }

    /**
     * Set the client using this connector: the IPC-capable objects it receives are bound to it.
     */
    public function setClient(Client $client): void
    {
        $this->client = WeakReference::create($client);
    }

    public function __toString(): string
    {
        return $this->endpoint ?? $this->session->getIpcPath();
    }

    /**
     * Connect to the main IPC server, once.
     */
    public function connect(?Cancellation $cancellation = null): ChannelledSocket|EncryptedChannel
    {
        if ($this->endpoint === null) {
            return connect($this->session->getIpcPath(), $cancellation);
        }
        return $this->connectRemote(Handshake::TYPE_MAIN, $cancellation);
    }

    /**
     * Connect to the callback IPC server.
     */
    public function connectCallback(): ChannelledSocket|EncryptedChannel
    {
        if ($this->endpoint === null) {
            return connect($this->session->getIpcCallbackPath());
        }
        return $this->connectRemote(Handshake::TYPE_CALLBACK);
    }

    /**
     * Connect to an IPC server that isn't started automatically, waiting for it to come up.
     */
    public function connectWaiting(?float $timeout = self::CONNECT_TIMEOUT): ChannelledSocket|EncryptedChannel
    {
        \assert(!$this->autostart);
        $deadline = $timeout === null ? null : microtime(true) + $timeout;
        $logged = 0.0;
        while (true) {
            try {
                return $this->connect(new TimeoutCancellation(10.0));
            } catch (AuthenticationException $e) {
                throw $e;
            } catch (Throwable $e) {
                $message = $e->getMessage();
                if ($e instanceof CancelledException || $message === 'The endpoint does not exist!') {
                    $message = 'not running';
                }
                if ($deadline !== null && microtime(true) >= $deadline) {
                    throw new Exception("Could not connect to the IPC server at $this ($message): autostart is disabled, so it must be started separately!", 0, $e);
                }
                if (microtime(true) - $logged >= self::CONNECT_TIMEOUT) {
                    Logger::log("Waiting for the IPC server at $this ($message)...", Logger::WARNING);
                    $logged = microtime(true);
                }
            }
            delay(1.0);
        }
    }

    /**
     * Reconnect to the main IPC server after the connection was lost, starting it again if needed.
     */
    public function reconnect(): ChannelledSocket|EncryptedChannel
    {
        if (!$this->autostart) {
            // Wait for servers that are restarted or redeployed, however long it takes.
            return $this->connectWaiting(null);
        }
        // Connect as soon as any server is listening, even if it was started by another process
        [$server] = Serialization::tryConnect($this->session->getIpcPath(), Server::startMe($this->session));
        if (!$server instanceof ChannelledSocket) {
            throw $server instanceof Throwable ? $server : new ChannelException("Could not reconnect to the IPC server, please check the logs!");
        }
        return $server;
    }

    private function connectRemote(int $type, ?Cancellation $cancellation = null): EncryptedChannel
    {
        \assert($this->endpoint !== null && $this->secret !== null);
        $endpoint = Ipc::parseEndpoint($this->endpoint, true);
        if ($endpoint['scheme'] === 'tcp') {
            $socket = socketConnect(
                "tcp://{$endpoint['host']}:{$endpoint['port']}",
                (new ConnectContext)->withTcpNoDelay()->withConnectTimeout(10),
                $cancellation,
            );
        } else {
            $socket = self::connectHttp($this->endpoint, $cancellation);
        }
        $channel = Handshake::client($socket, $this->secret, $type, $cancellation);
        if ($client = $this->client?->get()) {
            $channel->setOwner($client);
        }
        return $channel;
    }

    /**
     * Open a raw connection to an IPC server over HTTP, with an HTTP/1.1 upgrade.
     */
    private static function connectHttp(string $endpoint, ?Cancellation $cancellation): ReadableStream&WritableStream
    {
        self::$httpClient ??= (new HttpClientBuilder)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext)->withTcpNoDelay())))
            ->build();
        $request = new Request($endpoint, 'GET');
        $request->setProtocolVersions(['1.1']);
        $request->setHeader('connection', 'Upgrade');
        $request->setHeader('upgrade', self::HTTP_UPGRADE);
        $request->setTcpConnectTimeout(10);
        $request->setTlsHandshakeTimeout(10);

        /** @var DeferredFuture<Socket> */
        $deferred = new DeferredFuture;
        $request->setUpgradeHandler(static function (Socket $socket) use ($deferred): void {
            $deferred->complete($socket);
        });
        $response = self::$httpClient->request($request, $cancellation);
        if ($response->getStatus() !== HttpStatus::SWITCHING_PROTOCOLS) {
            throw new Exception("The IPC server at $endpoint answered with HTTP status {$response->getStatus()} instead of upgrading the connection: is it a MadelineProto IPC endpoint, and does the reverse proxy forward the Upgrade header?");
        }
        return $deferred->getFuture()->await($cancellation);
    }
}
