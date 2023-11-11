<?php

declare(strict_types=1);

/**
 * DataCenter DoH proxying AMPHP connector.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Dns\DnsRecord;
use Amp\Dns\DnsTimeoutException;
use Amp\NullCancellation;
use Amp\Socket\ConnectContext;
use Amp\Socket\ConnectException;
use Amp\Socket\ResourceSocket;
use Amp\Socket\Socket;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketConnector;
use AssertionError;
use danog\MadelineProto\Stream\ConnectionContext;
use Revolt\EventLoop;

use const STREAM_CLIENT_ASYNC_CONNECT;

use const STREAM_CLIENT_CONNECT;
use function Amp\Socket\Internal\parseUri;

/** @internal */
final class DoHConnector implements SocketConnector
{
    public function __construct(private DoHWrapper $dataCenter, private ConnectionContext $ctx)
    {
    }
    public function connect(
        SocketAddress|string $uri,
        ?ConnectContext $context = null,
        ?Cancellation $token = null
    ): Socket {
        $socketContext = $context ?? new ConnectContext();
        $token ??= new NullCancellation();
        $uris = [];
        $failures = [];
        [$scheme, $host, $port] = parseUri($uri);
        if ($host[0] === '[') {
            $host = substr($host, 1, -1);
        }
        if ($port === 0 || @inet_pton($host)) {
            // Host is already an IP address or file path.
            $uris = [$uri];
        } else {
            // Host is not an IP address, so resolve the domain name.
            // When we're connecting to a host, we may need to resolve the domain name, first.
            // The resolution is usually done using DNS over HTTPS.
            //
            // The DNS over HTTPS resolver needs to resolve the domain name of the DOH server:
            // this is handled internally by the DNS over HTTPS client,
            // by redirecting the resolution request to the plain DNS client.
            //
            // However, if the DoH connection is proxied with a proxy that has a domain name itself,
            // we cannot resolve it with the DoH resolver, since this will cause an infinite loop
            //
            // resolve host.com => (DoH resolver) => resolve dohserver.com => (simple resolver) => OK
            //
            //                                     |> resolve dohserver.com => (simple resolver) => OK
            // resolve host.com => (DoH resolver) =|
            //                                     |> resolve proxy.com => (non-proxied resolver) => OK
            //
            //
            // This means that we must detect if the domain name we're trying to resolve is a proxy domain name.
            //
            // Here, we simply check if the connection URI has changed since we first set it:
            // this would indicate that a proxy class has changed the connection URI to the proxy URI.
            if ($this->ctx->isDns()) {
                $records = $this->dataCenter->nonProxiedDoHClient->resolve($host, $socketContext->getDnsTypeRestriction());
            } else {
                $records = $this->dataCenter->DoHClient->resolve($host, $socketContext->getDnsTypeRestriction());
            }
            usort($records, static fn (DnsRecord $a, DnsRecord $b) => $a->getType() - $b->getType());
            if ($this->ctx->getIpv6()) {
                $records = array_reverse($records);
            }
            foreach ($records as $record) {
                if ($record->getType() === DnsRecord::AAAA) {
                    $uris[] = sprintf('%s://[%s]:%d', $scheme, $record->getValue(), $port);
                } else {
                    $uris[] = sprintf('%s://%s:%d', $scheme, $record->getValue(), $port);
                }
            }
        }
        $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;
        $timeout = $socketContext->getConnectTimeout();
        $e = null;
        foreach ($uris as $builtUri) {
            try {
                $streamContext = stream_context_create($socketContext->withoutTlsContext()->toStreamContextArray());
                /** @psalm-suppress NullArgument */
                if (!($socket = @stream_socket_client($builtUri, $errno, $errstr, null, $flags, $streamContext))) {
                    throw new ConnectException(sprintf('Connection to %s failed: [Error #%d] %s%s', (string) $uri, $errno, $errstr, $failures ? '; previous attempts: '.implode('', $failures) : ''), $errno);
                }
                stream_set_blocking($socket, false);
                $deferred = new DeferredFuture();
                /** @psalm-suppress InvalidArgument */
                $watcher = EventLoop::onWritable($socket, $deferred->complete(...));
                $id = $token->subscribe($deferred->error(...));
                try {
                    $deferred->getFuture()->await(Tools::getTimeoutCancellation($timeout));
                } catch (CancelledException $e) {
                    if (!$e->getPrevious() instanceof DnsTimeoutException) {
                        throw $e;
                    }

                    throw new ConnectException(sprintf('Connecting to %s failed: timeout exceeded (%d ms)%s', (string) $uri, $timeout, $failures ? '; previous attempts: '.implode('', $failures) : ''), 110);
                    // See ETIMEDOUT in http://www.virtsync.com/c-error-codes-include-errno
                } finally {
                    EventLoop::cancel($watcher);
                    $token->unsubscribe($id);
                }
                // The following hack looks like the only way to detect connection refused errors with PHP's stream sockets.
                if (stream_socket_get_name($socket, true) === false) {
                    fclose($socket);
                    throw new ConnectException(sprintf('Connection to %s refused%s', (string) $uri, $failures ? '; previous attempts: '.implode('', $failures) : ''), 111);
                    // See ECONNREFUSED in http://www.virtsync.com/c-error-codes-include-errno
                }
            } catch (ConnectException $e) {
                // Includes only error codes used in this file, as error codes on other OS families might be different.
                // In fact, this might show a confusing error message on OS families that return 110 or 111 by itself.
                $knownReasons = [110 => 'connection timeout', 111 => 'connection refused'];
                $code = $e->getCode();
                $reason = $knownReasons[$code] ?? 'Error #'.$code;
                $failures[] = "{$uri} ({$reason})";
                continue;
            }
            return ResourceSocket::fromClientSocket($socket, $socketContext->getTlsContext());
        }

        // This is reached if either all URIs failed or the maximum number of attempts is reached.
        if ($e) {
            throw $e;
        }
        throw new AssertionError("Unreachable!");
    }
}
