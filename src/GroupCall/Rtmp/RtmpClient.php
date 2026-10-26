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

namespace danog\MadelineProto\GroupCall\Rtmp;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Dns\DnsException;
use Amp\Dns\DnsRecord;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use Revolt\EventLoop;
use Throwable;

use function Amp\Dns\resolve;
use function Amp\Socket\connect;

/**
 * A minimal RTMP(S) client that publishes a stream, as an RTMP encoder (OBS, ffmpeg) does.
 *
 * See the [RTMP specification](https://rtmp.veriskope.com/docs/spec/): the simple handshake, the
 * chunk stream, the protocol control messages and the AMF0 commands of NetConnection (connect,
 * createStream) and NetStream (publish).
 *
 * @internal
 */
final class RtmpClient
{
    private const CHUNK_STREAM_CONTROL = 2;
    private const CHUNK_STREAM_COMMAND = 3;
    private const CHUNK_STREAM_AUDIO = 4;
    private const CHUNK_STREAM_VIDEO = 6;
    private const CHUNK_STREAM_DATA = 5;

    private const TYPE_SET_CHUNK_SIZE = 1;
    private const TYPE_ABORT = 2;
    private const TYPE_ACKNOWLEDGEMENT = 3;
    private const TYPE_USER_CONTROL = 4;
    private const TYPE_WINDOW_ACK_SIZE = 5;
    private const TYPE_SET_PEER_BANDWIDTH = 6;
    public const TYPE_AUDIO = 8;
    public const TYPE_VIDEO = 9;
    private const TYPE_DATA_AMF0 = 18;
    private const TYPE_COMMAND_AMF0 = 20;

    private const USER_CONTROL_PING_REQUEST = 6;
    private const USER_CONTROL_PING_RESPONSE = 7;

    /** The size of the chunks we send: fewer chunk headers than the default 128 bytes. */
    private const OUTGOING_CHUNK_SIZE = 4096;

    private int $outgoingChunkSize = 128;
    private int $incomingChunkSize = 128;
    private int $windowAckSize = 2500000;
    private int $bytesReceived = 0;
    private int $bytesAcknowledged = 0;
    private string $buffer = '';
    /** @var array<int, array{timestamp: int, delta: int, length: int, type: int, stream: int, extended: bool, payload: string}> */
    private array $chunkStreams = [];
    /** @var array<int, DeferredFuture<list<mixed>>> The commands waiting for their _result, by transaction ID. */
    private array $transactions = [];
    private int $transaction = 0;
    /** @var ?DeferredFuture<null> Completed once the server starts accepting what we publish. */
    private ?DeferredFuture $publishing = null;
    private int $stream = 0;
    private bool $closed = false;
    private ?Throwable $error = null;
    /** @var DeferredFuture<null> Completed when the connection is closed. */
    private readonly DeferredFuture $closedDeferred;
    /** When the server started accepting what we publish (hrtime, in nanoseconds). */
    private ?int $publishedAt = null;
    /** How many audio and video messages were sent. */
    private int $mediaMessages = 0;
    /** When the last audio or video message was sent (hrtime, in nanoseconds). */
    private ?int $lastMediaAt = null;
    /** The last onStatus the server sent after accepting the stream, if any. */
    private ?string $lastStatus = null;

    private function __construct(private readonly Socket $socket, private readonly string $key)
    {
        $this->closedDeferred = new DeferredFuture;
    }

    /**
     * Wait until the connection is closed, by us or by the server.
     */
    public function awaitClose(?Cancellation $cancellation = null): void
    {
        $this->closedDeferred->getFuture()->await($cancellation);
    }

    /**
     * Connect to an RTMP(S) server and start publishing a stream.
     *
     * @param string $url        The URL of the RTMP application, e.g. `rtmps://dc4-1.rtmp.t.me/s/`.
     * @param string $key        The stream key.
     * @param bool   $publicOnly Whether to connect only to a server on the internet, see {@see self::resolvePublic()}.
     *
     * @throws Exception            If the server refuses the stream.
     * @throws RtmpAddressException If the server isn't on the internet, with $publicOnly.
     */
    public static function publish(string $url, #[\SensitiveParameter] string $key, ?Cancellation $cancellation = null, bool $publicOnly = false): self
    {
        $parts = parse_url($url);
        if (!isset($parts['scheme'], $parts['host']) || !\in_array($parts['scheme'], ['rtmp', 'rtmps'], true)) {
            throw new Exception("Invalid RTMP URL: $url");
        }
        $tls = $parts['scheme'] === 'rtmps';
        $host = $parts['host'];
        $port = $parts['port'] ?? ($tls ? 443 : 1935);
        $app = trim($parts['path'] ?? '', '/');
        $context = new ConnectContext;
        if ($tls) {
            $context = $context->withTlsContext(new ClientTlsContext($host));
        }
        // With $publicOnly, connected to the address that was checked: the name isn't resolved again in between.
        $address = $publicOnly ? self::resolvePublic($host, $cancellation) : $host;
        if (str_contains($address, ':')) {
            $address = "[$address]";
        }
        $socket = connect("tcp://$address:$port", $context, $cancellation);
        if ($tls) {
            $socket->setupTls($cancellation);
        }
        $client = new self($socket, $key);
        try {
            $client->handshake();
            EventLoop::queue($client->readLoop(...));
            $client->setChunkSize();
            $client->command('connect', [
                'app' => $app,
                'type' => 'nonprivate',
                'flashVer' => 'FMLE/3.0 (compatible; MadelineProto)',
                'tcUrl' => $parts['scheme']."://$host".(isset($parts['port']) ? ":$port" : '')."/$app",
            ]);
            $client->notify('releaseStream', null, $key);
            $client->notify('FCPublish', null, $key);
            $result = $client->command('createStream', null);
            $client->stream = (int) ($result[1] ?? 1);
            $client->publishing = new DeferredFuture;
            $client->notify('publish', null, $key, 'live');
            $client->publishing->getFuture()->await($cancellation);
            $client->publishedAt = hrtime(true);
        } catch (Throwable $e) {
            $client->close();
            throw $e;
        }
        return $client;
    }

    /**
     * The address to connect to for a host, if it's on the internet: every address its name resolves to must be global
     * (no loopback, private, link-local, shared or reserved address), so that whoever chooses the URL can't make us
     * connect into our network. IPv4 if possible: not every network has IPv6.
     *
     * @throws RtmpAddressException If it isn't.
     */
    public static function resolvePublic(string $host, ?Cancellation $cancellation = null): string
    {
        $host = trim($host, '[]');
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $addresses = [$host];
        } else {
            try {
                $addresses = array_map(static fn (DnsRecord $record): string => $record->getValue(), resolve($host, cancellation: $cancellation));
            } catch (DnsException $e) {
                throw new RtmpAddressException("Could not resolve $host: {$e->getMessage()}", 0, $e);
            }
        }
        if ($addresses === []) {
            throw new RtmpAddressException("Could not resolve $host");
        }
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false || self::embedsIpv4($address)) {
                throw new RtmpAddressException("$host is not on the internet: it resolves to $address");
            }
        }
        foreach ($addresses as $address) {
            if (!str_contains($address, ':')) {
                return $address;
            }
        }
        return $addresses[0];
    }

    /**
     * IPv6 prefixes that embed an IPv4 address, which may not be global: IPv4-mapped (::ffff:0:0/96), IPv4-compatible
     * (::/96), NAT64 (64:ff9b::/96, 64:ff9b:1::/48), 6to4 (2002::/16), Teredo (2001::/32). As prefix => bits.
     */
    private const IPV4_EMBEDDING_PREFIXES = [
        '::ffff:0:0' => 96,
        '::' => 96,
        '64:ff9b::' => 96,
        '64:ff9b:1::' => 48,
        '2002::' => 16,
        '2001::' => 32,
    ];

    /**
     * Whether an IPv6 address embeds an IPv4 one, see {@see self::IPV4_EMBEDDING_PREFIXES}: refused rather than
     * checked, as where it leads depends on the network.
     */
    private static function embedsIpv4(string $address): bool
    {
        if (!str_contains($address, ':')) {
            return false;
        }
        $binary = inet_pton($address);
        if ($binary === false) {
            return true;
        }
        foreach (self::IPV4_EMBEDDING_PREFIXES as $prefix => $bits) {
            $prefix = inet_pton($prefix);
            \assert($prefix !== false);
            $bytes = intdiv($bits, 8);
            if (substr($binary, 0, $bytes) === substr($prefix, 0, $bytes)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The simple handshake: C0+C1, S0+S1+S2, C2.
     */
    private function handshake(): void
    {
        $c1 = "\0\0\0\0\0\0\0\0".random_bytes(1528);
        $this->socket->write("\x03".$c1);
        $response = '';
        while (\strlen($response) < 1 + 1536 * 2) {
            $chunk = $this->socket->read();
            if ($chunk === null) {
                throw new Exception('The RTMP server closed the connection during the handshake');
            }
            $response .= $chunk;
        }
        if ($response[0] !== "\x03") {
            throw new Exception('Unsupported RTMP version '.\ord($response[0]));
        }
        // C2 echoes S1.
        $this->socket->write(substr($response, 1, 1536));
        // Anything after S2 already belongs to the chunk stream.
        $this->buffer = substr($response, 1 + 1536 * 2);
    }

    private function setChunkSize(): void
    {
        $this->writeMessage(self::CHUNK_STREAM_CONTROL, self::TYPE_SET_CHUNK_SIZE, 0, 0, pack('N', self::OUTGOING_CHUNK_SIZE));
        $this->outgoingChunkSize = self::OUTGOING_CHUNK_SIZE;
    }

    /**
     * Send a command and wait for its _result.
     *
     * @return list<mixed> The values of the _result after the transaction ID: the command object and the reply.
     *
     * @throws Exception If the server answers with an _error.
     */
    private function command(string $name, mixed ...$arguments): array
    {
        $transaction = ++$this->transaction;
        $deferred = new DeferredFuture;
        $this->transactions[$transaction] = $deferred;
        $this->writeMessage(self::CHUNK_STREAM_COMMAND, self::TYPE_COMMAND_AMF0, 0, 0, Amf0::encode($name, (float) $transaction, ...$arguments));
        return $deferred->getFuture()->await();
    }

    /**
     * Send a command whose answer, if any, we don't wait for.
     */
    private function notify(string $name, mixed ...$arguments): void
    {
        $this->writeMessage(
            self::CHUNK_STREAM_COMMAND,
            self::TYPE_COMMAND_AMF0,
            $name === 'publish' ? $this->stream : 0,
            0,
            Amf0::encode($name, (float) ++$this->transaction, ...$arguments)
        );
    }

    /**
     * Send the onMetaData of the stream.
     */
    public function sendMetadata(string $metadata): void
    {
        $this->writeMessage(self::CHUNK_STREAM_DATA, self::TYPE_DATA_AMF0, $this->stream, 0, $metadata);
    }

    /**
     * Send an audio or video message.
     *
     * @param self::TYPE_AUDIO|self::TYPE_VIDEO $type
     * @param int $timestamp In milliseconds.
     */
    public function sendMedia(int $type, int $timestamp, string $body): void
    {
        $this->writeMessage($type === self::TYPE_AUDIO ? self::CHUNK_STREAM_AUDIO : self::CHUNK_STREAM_VIDEO, $type, $this->stream, $timestamp, $body);
        $this->mediaMessages++;
        $this->lastMediaAt = hrtime(true);
    }

    /**
     * How many audio and video messages were sent on this connection.
     *
     * @psalm-mutation-free
     */
    public function getMediaMessages(): int
    {
        return $this->mediaMessages;
    }

    /**
     * What happened on the connection, for the logs: how long the stream was published, the media
     * sent and the last status of the server.
     */
    public function describe(): string
    {
        $now = hrtime(true);
        $result = $this->publishedAt === null
            ? 'before publishing'
            : \sprintf('%.1f seconds after publishing', ($now - $this->publishedAt) / 1e9);
        $result .= ", {$this->mediaMessages} audio/video messages sent";
        if ($this->lastMediaAt !== null) {
            $result .= \sprintf(' (the last one %.1f seconds before)', ($now - $this->lastMediaAt) / 1e9);
        }
        if ($this->lastStatus !== null) {
            $result .= ", last status: {$this->lastStatus}";
        }
        return $result;
    }

    /**
     * Whether the connection is closed: the publisher has to reconnect.
     *
     * @psalm-mutation-free
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Why the connection was closed, if it was by an error.
     *
     * @psalm-mutation-free
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * Stop publishing and close the connection.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        if ($this->stream !== 0) {
            try {
                $this->notify('FCUnpublish', null, $this->key);
                $this->notify('deleteStream', null, (float) $this->stream);
            } catch (Throwable) {
            }
        }
        $this->fail(null);
    }

    private function fail(?Throwable $error): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->error = $error;
        $this->socket->close();
        if ($this->transactions !== [] || ($this->publishing !== null && !$this->publishing->isComplete())) {
            $error ??= new Exception('The RTMP connection was closed');
            foreach ($this->transactions as $deferred) {
                $deferred->error($error);
            }
            $this->transactions = [];
            if ($this->publishing !== null && !$this->publishing->isComplete()) {
                $this->publishing->error($error);
            }
        }
        $this->closedDeferred->complete();
    }

    /**
     * Write one message, split into chunks: the first one with a full (type 0) header, the others
     * with none (type 3).
     */
    private function writeMessage(int $chunkStream, int $type, int $stream, int $timestamp, string $payload): void
    {
        if ($this->closed) {
            throw $this->error ?? new Exception('The RTMP connection was closed');
        }
        $timestamp &= 0xFFFFFFFF;
        $extended = $timestamp >= 0xFFFFFF;
        $length = \strlen($payload);
        $data = \chr($chunkStream)
            .substr(pack('N', $extended ? 0xFFFFFF : $timestamp), 1)
            .substr(pack('N', $length), 1)
            .\chr($type)
            .pack('V', $stream)
            .($extended ? pack('N', $timestamp) : '');
        for ($offset = 0; $offset < $length || $offset === 0; $offset += $this->outgoingChunkSize) {
            if ($offset > 0) {
                $data .= \chr(0xC0 | $chunkStream).($extended ? pack('N', $timestamp) : '');
            }
            $data .= substr($payload, $offset, $this->outgoingChunkSize);
            if ($length === 0) {
                break;
            }
        }
        try {
            // A single write: the messages of the audio and video senders never interleave.
            $this->socket->write($data);
        } catch (Throwable $e) {
            $this->fail($e);
            throw $e;
        }
    }

    private function readLoop(): void
    {
        try {
            while (true) {
                while ($this->parseChunk()) {
                }
                $chunk = $this->socket->read();
                if ($chunk === null) {
                    if (!$this->closed) {
                        $this->fail(new Exception('The RTMP server closed the connection'));
                    }
                    return;
                }
                $this->buffer .= $chunk;
                $this->bytesReceived += \strlen($chunk);
                if ($this->bytesReceived - $this->bytesAcknowledged >= $this->windowAckSize) {
                    $this->bytesAcknowledged = $this->bytesReceived;
                    $this->writeMessage(self::CHUNK_STREAM_CONTROL, self::TYPE_ACKNOWLEDGEMENT, 0, 0, pack('N', $this->bytesReceived & 0xFFFFFFFF));
                }
            }
        } catch (Throwable $e) {
            $this->fail($e);
        }
    }

    /**
     * Parse one chunk from the buffer, if it's complete.
     */
    private function parseChunk(): bool
    {
        $buffer = $this->buffer;
        $length = \strlen($buffer);
        if ($length < 1) {
            return false;
        }
        $first = \ord($buffer[0]);
        $format = $first >> 6;
        $chunkStream = $first & 0x3F;
        $offset = 1;
        if ($chunkStream === 0) {
            if ($length < 2) {
                return false;
            }
            $chunkStream = 64 + \ord($buffer[1]);
            $offset = 2;
        } elseif ($chunkStream === 1) {
            if ($length < 3) {
                return false;
            }
            $chunkStream = 64 + \ord($buffer[1]) + \ord($buffer[2]) * 256;
            $offset = 3;
        }
        $headerLength = [11, 7, 3, 0][$format];
        if ($length < $offset + $headerLength) {
            return false;
        }
        $state = $this->chunkStreams[$chunkStream] ?? ['timestamp' => 0, 'delta' => 0, 'length' => 0, 'type' => 0, 'stream' => 0, 'extended' => false, 'payload' => ''];
        $field = 0;
        if ($format <= 2) {
            $field = unpack('N', "\0".substr($buffer, $offset, 3))[1];
        }
        if ($format <= 1) {
            $state['length'] = unpack('N', "\0".substr($buffer, $offset + 3, 3))[1];
            $state['type'] = \ord($buffer[$offset + 6]);
        }
        if ($format === 0) {
            $state['stream'] = unpack('V', substr($buffer, $offset + 7, 4))[1];
        }
        $offset += $headerLength;
        $extended = $format <= 2 ? $field === 0xFFFFFF : $state['extended'];
        if ($extended) {
            if ($length < $offset + 4) {
                return false;
            }
            $field = $format <= 2 ? unpack('N', substr($buffer, $offset, 4))[1] : $field;
            $offset += 4;
        }
        $starting = $state['payload'] === '';
        $size = min($this->incomingChunkSize, $state['length'] - \strlen($state['payload']));
        if ($length < $offset + $size) {
            return false;
        }
        if ($format === 0) {
            $state['timestamp'] = $field;
            $state['delta'] = 0;
        } elseif ($format <= 2) {
            $state['delta'] = $field;
            $state['timestamp'] += $field;
        } elseif ($starting) {
            // A new message with the header of the previous one.
            $state['timestamp'] += $state['delta'];
        }
        $state['extended'] = $extended;
        $state['payload'] .= substr($buffer, $offset, $size);
        $this->buffer = substr($buffer, $offset + $size);
        if (\strlen($state['payload']) >= $state['length']) {
            $payload = $state['payload'];
            $state['payload'] = '';
            $this->chunkStreams[$chunkStream] = $state;
            $this->onMessage($state['type'], $state['timestamp'], $payload);
        } else {
            $this->chunkStreams[$chunkStream] = $state;
        }
        return true;
    }

    private function onMessage(int $type, int $timestamp, string $payload): void
    {
        switch ($type) {
            case self::TYPE_SET_CHUNK_SIZE:
                $this->incomingChunkSize = unpack('N', $payload)[1] & 0x7FFFFFFF;
                return;
            case self::TYPE_ABORT:
                $chunkStream = unpack('N', $payload)[1];
                if (isset($this->chunkStreams[$chunkStream])) {
                    $this->chunkStreams[$chunkStream]['payload'] = '';
                }
                return;
            case self::TYPE_WINDOW_ACK_SIZE:
                $this->windowAckSize = unpack('N', $payload)[1];
                return;
            case self::TYPE_SET_PEER_BANDWIDTH:
                // Our acknowledgement window, as the peer bandwidth the server sets.
                $this->writeMessage(self::CHUNK_STREAM_CONTROL, self::TYPE_WINDOW_ACK_SIZE, 0, 0, substr($payload, 0, 4));
                return;
            case self::TYPE_USER_CONTROL:
                if (\strlen($payload) >= 6 && unpack('n', $payload)[1] === self::USER_CONTROL_PING_REQUEST) {
                    $this->writeMessage(self::CHUNK_STREAM_CONTROL, self::TYPE_USER_CONTROL, 0, 0, pack('n', self::USER_CONTROL_PING_RESPONSE).substr($payload, 2, 4));
                }
                return;
            case self::TYPE_COMMAND_AMF0:
                $this->onCommand(Amf0::decode($payload));
                return;
        }
    }

    /**
     * @param list<mixed> $values
     */
    private function onCommand(array $values): void
    {
        $name = $values[0] ?? null;
        $transaction = (int) ($values[1] ?? 0);
        if ($name === '_result' || $name === '_error') {
            $deferred = $this->transactions[$transaction] ?? null;
            if ($deferred === null) {
                return;
            }
            unset($this->transactions[$transaction]);
            if ($name === '_result') {
                $deferred->complete(\array_slice($values, 2));
            } else {
                $deferred->error(new RtmpRefusedException('The RTMP server refused the command: '.self::describeInfo($values[3] ?? null)));
            }
            return;
        }
        if ($name === 'onStatus' && $this->publishing !== null) {
            $info = $values[3] ?? null;
            if ($this->publishing->isComplete()) {
                // E.g. NetStream.Unpublish.Success before the server drops a publisher.
                $this->lastStatus = self::describeInfo($info);
                return;
            }
            $code = \is_array($info) ? (string) ($info['code'] ?? '') : '';
            if ($code === 'NetStream.Publish.Start') {
                $this->publishing->complete();
            } elseif (\is_array($info) && ($info['level'] ?? '') === 'error') {
                $this->publishing->error(new RtmpRefusedException('The RTMP server refused the stream: '.self::describeInfo($info)));
            }
        }
    }

    private static function describeInfo(mixed $info): string
    {
        if (\is_array($info)) {
            return trim(($info['code'] ?? '').' '.($info['description'] ?? ''));
        }
        return var_export($info, true);
    }
}
