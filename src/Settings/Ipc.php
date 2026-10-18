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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;

/**
 * IPC server settings.
 *
 * By default, scripts that don't start an event handler automatically start an IPC server in the background
 * (a worker process), and talk to it through a socket in the session folder.
 *
 * The IPC server can also be started separately, locally or on another machine (for example in a docker container):
 * disable autostart to connect to the IPC server of the session folder only if it's already running, or set an
 * endpoint to connect to a remote IPC server, which listens on the TCP or HTTP endpoints set with setListen.
 *
 * Remote connections are authenticated and encrypted with a shared secret, which must be set on both sides.
 *
 * To start a standalone IPC server, run `php madeline.phar madeline-server /path/to/session.madeline` (or, with
 * composer, `php vendor/danog/madelineproto/src/Ipc/Runner/entry.php madeline-server /path/to/session.madeline`),
 * with the MADELINE_IPC_LISTEN (comma-separated endpoints) and MADELINE_IPC_SECRET environment variables, plus
 * MADELINE_API_ID and MADELINE_API_HASH to create a new session; the docker image provides the `madeline-server`
 * command, which serves /data/session.madeline by default.
 */
final class Ipc extends SettingsAbstract
{
    /**
     * Minimum length of the shared secret of remote IPC connections.
     */
    public const MIN_SECRET_LENGTH = 16;

    /**
     * Whether to automatically start the IPC server of the session if it isn't running.
     */
    protected bool $autostart = true;
    /**
     * Remote IPC server to connect to, instead of the IPC server of the session folder.
     */
    protected ?string $endpoint = null;
    /**
     * Shared secret used to authenticate and encrypt remote IPC connections.
     */
    protected ?string $secret = null;
    /**
     * Endpoints on which the IPC server accepts remote connections, in addition to the IPC socket of the session folder.
     *
     * @var list<string>
     */
    protected array $listen = [];
    /**
     * Whether the remote endpoints were set explicitly.
     */
    protected bool $listenSet = false;
    /**
     * Whether the shared secret was set explicitly.
     */
    protected bool $secretSet = false;

    public function __construct()
    {
        Magic::start(light: true);
    }

    /**
     * Get WARNING: this will cause slow startup if enabled.
     *
     * @psalm-pure
     */
    public function getSlow(): bool
    {
        return Magic::$isIpcWorker || \PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Get whether to automatically start the IPC server of the session if it isn't running.
     *
     * Always false if a remote endpoint is set.
     */
    public function getAutostart(): bool
    {
        return $this->autostart && $this->endpoint === null;
    }

    /**
     * Set whether to automatically start the IPC server of the session if it isn't running (default: true).
     *
     * If disabled, scripts connect to the IPC server of the session folder (or to the remote endpoint, if set)
     * without ever starting it, waiting for it to come up, and fail if it doesn't.
     * Disabling autostart also prevents scripts from loading the session in-process, so startAndLoop must be run
     * on the server.
     *
     * @psalm-external-mutation-free
     */
    public function setAutostart(bool $autostart): self
    {
        $this->autostart = $autostart;
        return $this;
    }

    /**
     * Get the remote IPC server to connect to, instead of the IPC server of the session folder.
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Set the remote IPC server to connect to, instead of the IPC server of the session folder.
     *
     * Supported endpoints are `tcp://host:port`, `http://host:port/path` and `https://host/path` (for IPC servers
     * behind a reverse proxy, which must forward the `Upgrade: madelineproto-ipc` header, like for websockets).
     * The server must listen on the endpoint (see setListen), and the shared secret must be set on both sides.
     *
     * If set, the IPC server is never started automatically, and no session files are created locally: the session
     * name is only used as a local identifier of the client.
     *
     * @psalm-external-mutation-free
     */
    public function setEndpoint(?string $endpoint): self
    {
        if ($endpoint !== null) {
            self::parseEndpoint($endpoint, true);
        }
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Get the shared secret used to authenticate and encrypt remote IPC connections.
     */
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * Set the shared secret used to authenticate and encrypt remote IPC connections.
     *
     * Required to connect to a remote endpoint, and to listen on remote endpoints: anyone who knows it has full
     * control over the session, so generate a long random one (for example with `openssl rand -hex 32`): it isn't
     * stretched, so a short or guessable secret could be guessed offline from a recorded handshake.
     *
     * Client and server trust each other completely (the IPC protocol exchanges serialized PHP objects): only
     * connect to servers you control.
     *
     * Changing the secret of a running server disconnects the clients authenticated with the previous one.
     *
     * @psalm-external-mutation-free
     */
    public function setSecret(?string $secret): self
    {
        if ($secret !== null && \strlen($secret) < self::MIN_SECRET_LENGTH) {
            throw new Exception('The IPC secret must be at least '.self::MIN_SECRET_LENGTH.' bytes long!');
        }
        $this->secret = $secret;
        $this->secretSet = true;
        return $this;
    }

    /**
     * Get the endpoints on which the IPC server accepts remote connections.
     *
     * @return list<string>
     */
    public function getListen(): array
    {
        return $this->listen;
    }

    /**
     * Set the endpoints on which the IPC server accepts remote connections, in addition to the IPC socket of the
     * session folder.
     *
     * Supported endpoints are `tcp://host:port` and `http://host:port` (all paths are accepted, the secret
     * authenticates clients; use a reverse proxy for TLS). Requires the shared secret.
     *
     * @param list<string> $listen Endpoints, for example `['tcp://0.0.0.0:6100']`
     *
     * @psalm-external-mutation-free
     */
    public function setListen(array $listen): self
    {
        foreach ($listen as $endpoint) {
            self::parseEndpoint($endpoint, false);
        }
        $this->listen = array_values($listen);
        $this->listenSet = true;
        return $this;
    }

    /**
     * Restore the endpoints and secret that are actually in use, after new ones couldn't be applied.
     *
     * @internal
     *
     * @param list<string> $listen
     */
    public function restore(array $listen, ?string $secret): void
    {
        $this->listen = $listen;
        $this->secret = $secret;
    }

    /**
     * Merge with other settings instance.
     *
     * IPC clients send their settings to the IPC server: only the settings of the server (endpoints and secret)
     * are merged, and only if they were set explicitly, so that clients (remote ones especially) don't reset them
     * to the defaults, nor make the server connect to itself.
     *
     * @internal
     */
    #[\Override]
    public function merge(SettingsAbstract $other): void
    {
        if (!$other instanceof self) {
            return;
        }
        if ($other->listenSet && $other->listen !== $this->listen) {
            $this->setListen($other->listen);
            $this->changed = true;
        }
        if ($other->secretSet && $other->secret !== $this->secret) {
            $this->setSecret($other->secret);
            $this->changed = true;
        }
    }

    /**
     * Parse and validate a remote IPC endpoint.
     *
     * @internal
     *
     * @return array{scheme: 'tcp'|'http'|'https', host: string, port: int, path: string}
     *
     * @psalm-pure
     */
    public static function parseEndpoint(string $endpoint, bool $client): array
    {
        $parsed = parse_url($endpoint);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $allowed = $client ? ['tcp', 'http', 'https'] : ['tcp', 'http'];
        if (!\in_array($scheme, $allowed, true) || !isset($parsed['host'])) {
            throw new Exception("Invalid IPC endpoint $endpoint, the supported endpoints are ".implode(', ', array_map(static fn (string $s) => "$s://host:port", $allowed)));
        }
        $port = $parsed['port'] ?? match ($scheme) {
            'http' => 80,
            'https' => 443,
            'tcp' => throw new Exception("Missing port in IPC endpoint $endpoint"),
        };
        return [
            'scheme' => $scheme,
            'host' => $parsed['host'],
            'port' => $port,
            'path' => $parsed['path'] ?? '/',
        ];
    }
}
