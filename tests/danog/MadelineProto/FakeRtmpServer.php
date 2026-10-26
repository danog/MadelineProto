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

use Amp\Socket\InternetAddress;
use Amp\Socket\ServerSocket;
use Amp\Socket\Socket;
use danog\MadelineProto\GroupCall\Rtmp\Amf0;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;
use function Amp\Socket\listen;

/**
 * An RTMP ingest that accepts published streams the way the one of Telegram does, closing the
 * connection of a publisher that sends no audio or video for a while (10 seconds on Telegram),
 * and optionally refusing a stream key or closing every connection right after accepting it.
 *
 * Only what a publisher sends is handled: the simple handshake, Set Chunk Size, and the connect,
 * createStream and publish commands, which are answered as nginx-rtmp does.
 */
final class FakeRtmpServer
{
    /** How many connections were accepted. */
    public int $connections = 0;
    /** How many streams were accepted for publishing. */
    public int $publishes = 0;
    /** How many audio and video messages were received. */
    public int $mediaMessages = 0;
    /** @var list<float> When each publish was accepted (microtime). */
    public array $publishedAt = [];
    /** @var list<string> The stream keys published to. */
    public array $keys = [];
    /** How many connections the publisher closed. */
    public int $disconnections = 0;

    private readonly ServerSocket $server;
    /** @var list<Socket> */
    private array $sockets = [];

    /**
     * @param ?float   $idleTimeout       Close the connection of a publisher that sent no media for this long, in seconds.
     * @param bool     $closeAfterPublish Close every connection right after accepting its stream.
     * @param ?string  $validKey          Refuse any other stream key, with NetStream.Publish.BadName.
     */
    public function __construct(
        private readonly ?float $idleTimeout = null,
        private readonly bool $closeAfterPublish = false,
        private readonly ?string $validKey = null,
    ) {
        $this->server = listen('127.0.0.1:0');
        EventLoop::queue($this->acceptLoop(...));
    }

    /**
     * The URL of the application to publish to.
     */
    public function getUrl(): string
    {
        $address = $this->server->getAddress();
        \assert($address instanceof InternetAddress);
        return 'rtmp://127.0.0.1:'.$address->getPort().'/s/';
    }

    public function close(): void
    {
        $this->server->close();
        foreach ($this->sockets as $socket) {
            $socket->close();
        }
    }

    private function acceptLoop(): void
    {
        while ($socket = $this->server->accept()) {
            $this->connections++;
            $this->sockets[] = $socket;
            EventLoop::queue(function () use ($socket): void {
                try {
                    $this->handle($socket);
                } catch (Throwable) {
                } finally {
                    $socket->close();
                }
            });
        }
    }

    private function handle(Socket $socket): void
    {
        $buffer = '';
        $read = static function (int $length) use ($socket, &$buffer): ?string {
            while (\strlen($buffer) < $length) {
                $chunk = $socket->read();
                if ($chunk === null) {
                    return null;
                }
                $buffer .= $chunk;
            }
            $result = substr($buffer, 0, $length);
            $buffer = substr($buffer, $length);
            return $result;
        };
        $c0c1 = $read(1537);
        if ($c0c1 === null) {
            return;
        }
        $socket->write("\x03"."\0\0\0\0\0\0\0\0".random_bytes(1528).substr($c0c1, 1));
        if ($read(1536) === null) {
            return;
        }

        $chunkSize = 128;
        /** @var array<int, array{length: int, type: int, payload: string}> */
        $streams = [];
        $publishing = false;
        $lastMedia = microtime(true);
        $watcher = null;
        if ($this->idleTimeout !== null) {
            $watcher = EventLoop::repeat(0.05, function () use (&$publishing, &$lastMedia, $socket): void {
                if ($publishing && microtime(true) - $lastMedia >= $this->idleTimeout) {
                    $socket->close();
                }
            });
        }
        try {
            while (true) {
                $header = $read(1);
                if ($header === null) {
                    $this->disconnections++;
                    return;
                }
                $format = \ord($header) >> 6;
                $chunkStream = \ord($header) & 0x3F;
                $state = $streams[$chunkStream] ?? ['length' => 0, 'type' => 0, 'payload' => ''];
                $fields = $read([11, 7, 3, 0][$format]);
                if ($fields === null) {
                    return;
                }
                $timestamp = $format <= 2 ? unpack('N', "\0".substr($fields, 0, 3))[1] : 0;
                if ($format <= 1) {
                    $state['length'] = unpack('N', "\0".substr($fields, 3, 3))[1];
                    $state['type'] = \ord($fields[6]);
                }
                if ($timestamp === 0xFFFFFF && $read(4) === null) {
                    return;
                }
                $data = $read(min($chunkSize, $state['length'] - \strlen($state['payload'])));
                if ($data === null) {
                    return;
                }
                $state['payload'] .= $data;
                if (\strlen($state['payload']) < $state['length']) {
                    $streams[$chunkStream] = $state;
                    continue;
                }
                $payload = $state['payload'];
                $state['payload'] = '';
                $streams[$chunkStream] = $state;
                switch ($state['type']) {
                    case 1:
                        $chunkSize = unpack('N', $payload)[1];
                        break;
                    case 8:
                    case 9:
                        $this->mediaMessages++;
                        $lastMedia = microtime(true);
                        break;
                    case 20:
                        $command = Amf0::decode($payload);
                        $transaction = $command[1] ?? 0.0;
                        switch ($command[0] ?? null) {
                            case 'connect':
                                $this->send($socket, 0, Amf0::encode('_result', $transaction, ['fmsVer' => 'FMS/3,0,1,123'], ['level' => 'status', 'code' => 'NetConnection.Connect.Success']));
                                break;
                            case 'createStream':
                                $this->send($socket, 0, Amf0::encode('_result', $transaction, null, 1.0));
                                break;
                            case 'publish':
                                $key = (string) ($command[3] ?? '');
                                $this->keys[] = $key;
                                if ($this->validKey !== null && $key !== $this->validKey) {
                                    $this->send($socket, 1, Amf0::encode('onStatus', 0.0, null, ['level' => 'error', 'code' => 'NetStream.Publish.BadName', 'description' => 'Bad name']));
                                    delay(0.05);
                                    return;
                                }
                                $this->send($socket, 1, Amf0::encode('onStatus', 0.0, null, ['level' => 'status', 'code' => 'NetStream.Publish.Start', 'description' => 'Start publishing']));
                                $this->publishes++;
                                $this->publishedAt[] = microtime(true);
                                $publishing = true;
                                $lastMedia = microtime(true);
                                if ($this->closeAfterPublish) {
                                    delay(0.05);
                                    return;
                                }
                                break;
                        }
                        break;
                }
            }
        } finally {
            if ($watcher !== null) {
                EventLoop::cancel($watcher);
            }
        }
    }

    /**
     * Send a command message, in chunks of the default size.
     */
    private function send(Socket $socket, int $stream, string $payload): void
    {
        $length = \strlen($payload);
        $data = "\x03\0\0\0".substr(pack('N', $length), 1)."\x14".pack('V', $stream);
        foreach (str_split($payload, 128) as $k => $chunk) {
            $data .= ($k > 0 ? "\xC3" : '').$chunk;
        }
        $socket->write($data);
    }
}
