<?php

declare(strict_types=1);

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\Stream\Common\FileBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\VoIP\AckHandler;
use danog\MadelineProto\VoIP\Endpoint;
use danog\MadelineProto\VoIP\MessageHandler;
use Revolt\EventLoop;
use SplQueue;
use Throwable;

use function Amp\delay;
use function Amp\File\openFile;

if (\extension_loaded('php-libtgvoip')) {
    return;
}

final class VoIP
{
    use MessageHandler;
    use AckHandler;

    const PHP_LIBTGVOIP_VERSION = '1.5.0';
    const STATE_CREATED = 0;
    const STATE_WAIT_INIT = 1;
    const STATE_WAIT_INIT_ACK = 2;
    const STATE_ESTABLISHED = 3;
    const STATE_FAILED = 4;
    const STATE_RECONNECTING = 5;

    const TGVOIP_ERROR_UNKNOWN = 0;
    const TGVOIP_ERROR_INCOMPATIBLE = 1;
    const TGVOIP_ERROR_TIMEOUT = 2;
    const TGVOIP_ERROR_AUDIO_IO = 3;

    const NET_TYPE_UNKNOWN = 0;
    const NET_TYPE_GPRS = 1;
    const NET_TYPE_EDGE = 2;
    const NET_TYPE_3G = 3;
    const NET_TYPE_HSPA = 4;
    const NET_TYPE_LTE = 5;
    const NET_TYPE_WIFI = 6;
    const NET_TYPE_ETHERNET = 7;
    const NET_TYPE_OTHER_HIGH_SPEED = 8;
    const NET_TYPE_OTHER_LOW_SPEED = 9;
    const NET_TYPE_DIALUP = 10;
    const NET_TYPE_OTHER_MOBILE = 11;

    const DATA_SAVING_NEVER = 0;
    const DATA_SAVING_MOBILE = 1;
    const DATA_SAVING_ALWAYS = 2;

    const PROXY_NONE = 0;
    const PROXY_SOCKS5 = 1;

    const AUDIO_STATE_NONE = -1;
    const AUDIO_STATE_CREATED = 0;
    const AUDIO_STATE_CONFIGURED = 1;
    const AUDIO_STATE_RUNNING = 2;

    const CALL_STATE_NONE = -1;
    const CALL_STATE_REQUESTED = 0;
    const CALL_STATE_INCOMING = 1;
    const CALL_STATE_ACCEPTED = 2;
    const CALL_STATE_CONFIRMED = 3;
    const CALL_STATE_READY = 4;
    const CALL_STATE_ENDED = 5;

    const PKT_INIT = 1;
    const PKT_INIT_ACK = 2;
    const PKT_STREAM_STATE = 3;
    const PKT_STREAM_DATA = 4;
    const PKT_UPDATE_STREAMS = 5;
    const PKT_PING = 6;
    const PKT_PONG = 7;
    const PKT_STREAM_DATA_X2 = 8;
    const PKT_STREAM_DATA_X3 = 9;
    const PKT_LAN_ENDPOINT = 10;
    const PKT_NETWORK_CHANGED = 11;
    const PKT_SWITCH_PREF_RELAY = 12;
    const PKT_SWITCH_TO_P2P = 13;
    const PKT_NOP = 14;

    const TLID_DECRYPTED_AUDIO_BLOCK = "\xc1\xdb\xf9\x48";
    const TLID_SIMPLE_AUDIO_BLOCK = "\x0d\x0e\x76\xcc";

    const TLID_REFLECTOR_SELF_INFO = "\xC7\x72\x15\xc0";
    const TLID_REFLECTOR_PEER_INFO = "\x1C\x37\xD9\x27";

    const PROTO_ID = 'GrVP';

    const PROTOCOL_VERSION = 9;
    const MIN_PROTOCOL_VERSION = 9;

    const STREAM_TYPE_AUDIO = 1;
    const STREAM_TYPE_VIDEO = 2;

    const CODEC_OPUS = 'SUPO';

    private MTProto $MadelineProto;
    public array $received_timestamp_map = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    public array $remote_ack_timestamp_map = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    public int $session_out_seq_no = 0;
    public int $session_in_seq_no = 0;
    public int $voip_state = 0;
    public array $configuration = ['endpoints' => [], 'shared_config' => []];
    public array $storage = [];
    public array $internalStorage = [];
    private $signal = 0;
    private ?int $callState = null;
    private $callID;
    private $creatorID;
    private $otherID;
    private $protocol;
    private $visualization;
    private $holdFiles = [];
    private $inputFiles = [];
    private $outputFile;
    private $isPlaying = false;

    private bool $creator;

    private PermAuthKey $authKey;
    private int $peerVersion = 0;

    /**
     * @var array<Endpoint>
     */
    private array $sockets = [];

    private ?Endpoint $bestEndpoint = null;

    private bool $pendingPing = true;
    /**
     * Timeout watcher.
     */
    private ?string $timeoutWatcher = null;
    /**
     * Ping watcher.
     */
    private ?string $pingWatcher = null;

    /**
     * Last incoming timestamp.
     *
     */
    private float $lastIncomingTimestamp = 0.0;
    /**
     * The outgoing timestamp.
     *
     */
    private int $timestamp = 0;
    /**
     * Packet queue.
     *
     */
    private SplQueue $packetQueue;
    /**
     * Temporary holdfile array.
     */
    private array $tempHoldFiles = [];
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        $vars = \get_object_vars($this);
        unset($vars['sockets'], $vars['bestEndpoint'], $vars['timeoutWatcher']);

        return \array_keys($vars);
    }
    /**
     * Wakeup function.
     */
    public function __wakeup(): void
    {
        if ($this->voip_state === self::STATE_ESTABLISHED) {
            $this->voip_state = self::STATE_CREATED;
            $this->startTheMagic();
        }
    }
    /**
     * Constructor.
     */
    public function __construct(bool $creator, int $otherID, MTProto $MadelineProto, int $callState)
    {
        $this->creator = $creator;
        $this->otherID = $otherID;
        $this->MadelineProto = $MadelineProto;
        $this->callState = $callState;
        $this->packetQueue = new SplQueue;
    }

    /**
     * Get max layer.
     */
    public static function getConnectionMaxLayer(): int
    {
        return 92;
    }

    /**
     * Get debug string.
     */
    public function getDebugString(): string
    {
        return '';
    }

    /**
     * Set call constructor.
     */
    public function setCall(array $callID): void
    {
        $this->protocol = $callID['protocol'];
        $this->callID = [
            '_' => 'inputPhoneCall',
            'id' => $callID['id'],
            'access_hash' => $callID['access_hash'],
        ];
    }

    /**
     * Set emojis.
     */
    public function setVisualization(array $visualization): void
    {
        $this->visualization = $visualization;
    }

    /**
     * Get emojis.
     */
    public function getVisualization(): array
    {
        return $this->visualization;
    }

    /**
     * Discard call.
     *
     */
    public function discard(array $reason = ['_' => 'phoneCallDiscardReasonDisconnect'], array $rating = [], bool $debug = false): self
    {
        if (($this->callState ?? self::CALL_STATE_ENDED) === self::CALL_STATE_ENDED || empty($this->configuration)) {
            return $this;
        }
        $this->callState = self::CALL_STATE_ENDED;
        Logger::log("Now closing $this");
        if (isset($this->timeoutWatcher)) {
            EventLoop::cancel($this->timeoutWatcher);
        }

        Logger::log("Closing all sockets in $this");
        foreach ($this->sockets as $socket) {
            $socket->disconnect();
        }
        Logger::log("Closed all sockets, discarding $this");

        $this->MadelineProto->discardCall($this->callID, $reason, $rating, $debug);
        return $this;
    }

    public function __destruct()
    {
        EventLoop::queue($this->discard(...), ['_' => 'phoneCallDiscardReasonDisconnect']);
    }
    /**
     * Accept call.
     *
     */
    public function accept(): self|false
    {
        if ($this->callState !== self::CALL_STATE_INCOMING) {
            return false;
        }
        $this->callState = self::CALL_STATE_ACCEPTED;

        $res = $this->MadelineProto->acceptCall($this->callID);

        if (!$res) {
            $this->discard(['_' => 'phoneCallDiscardReasonDisconnect']);
        }

        return $this;
    }

    /**
     * Start the actual call.
     */
    public function startTheMagic(): self
    {
        if ($this->voip_state !== self::STATE_CREATED) {
            return $this;
        }
        $this->voip_state = self::STATE_WAIT_INIT;
        $this->timeoutWatcher = EventLoop::repeat(10, function (): void {
            if (\microtime(true) - $this->lastIncomingTimestamp > 10) {
                $this->discard(['_' => 'phoneCallDiscardReasonDisconnect']);
            }
        });
        EventLoop::queue(function (): void {
            $this->authKey = new PermAuthKey();
            $this->authKey->setAuthKey($this->configuration['auth_key']);

            foreach ($this->configuration['endpoints'] as $endpoint) {
                try {
                    $this->sockets['v6 '.$endpoint['id']] = new Endpoint('['.$endpoint['ipv6'].']', $endpoint['port'], $endpoint['peer_tag'], true, $this);
                } catch (Throwable) {
                }
                try {
                    $this->sockets['v4 '.$endpoint['id']] = new Endpoint($endpoint['ip'], $endpoint['port'], $endpoint['peer_tag'], true, $this);
                } catch (Throwable) {
                }
            }
            foreach ($this->sockets as $socket) {
                $socket->write($this->encryptPacket([
                    '_' => self::PKT_INIT,
                    'protocol' => self::PROTOCOL_VERSION,
                    'min_protocol' => self::MIN_PROTOCOL_VERSION,
                    'audio_streams' => [self::CODEC_OPUS],
                    'video_streams' => []
                ], false));
                EventLoop::queue(function () use ($socket): void {
                    while ($payload = $socket->read()) {
                        $this->lastIncomingTimestamp = \microtime(true);
                        EventLoop::queue($this->handlePacket(...), $socket, $payload);
                    }
                    Logger::log("Exiting VoIP read loop in $this!");
                });
            }
        });
        return $this;
    }
    /**
     * Handle incoming packet.
     */
    private function handlePacket(Endpoint $socket, array $packet): void
    {
        switch ($packet['_']) {
            case self::PKT_INIT:
                //$this->voip_state = self::STATE_WAIT_INIT_ACK;
                $this->peerVersion = $packet['protocol'];
                $socket->write(
                    $this->encryptPacket([
                        '_' => self::PKT_INIT_ACK,
                        'protocol' => self::PROTOCOL_VERSION,
                        'min_protocol' => self::MIN_PROTOCOL_VERSION,
                        'all_streams' => [
                            ['id' => 0, 'type' => self::STREAM_TYPE_AUDIO, 'codec' => self::CODEC_OPUS, 'frame_duration' => 60, 'enabled' => 1]
                        ]
                    ])
                );

                // no break
            case self::PKT_INIT_ACK:
                if (!$this->bestEndpoint) {
                    $this->bestEndpoint = $socket;
                    $this->pingWatcher = EventLoop::delay(1.0, function (): void {
                        $this->pendingPing = true;
                        foreach ($this->sockets as $socket) {
                            //$socket->udpPing();
                            $packet = $this->encryptPacket(['_' => self::PKT_PING]);
                            EventLoop::queue(fn () => $socket->write($packet));
                        }
                    });
                    $this->startWriteLoop();
                }
                break;
            case self::PKT_STREAM_DATA:
                $cnt = 1;
                break;
            case self::PKT_STREAM_DATA_X2:
                $cnt = 2;
                break;
            case self::PKT_STREAM_DATA_X3:
                $cnt = 3;
                break;
            case self::PKT_PONG:
                if ($this->pendingPing) {
                    $this->pendingPing = false;
                    if ($this->bestEndpoint !== $socket) {
                        Logger::log("Changing best endpoint from {$this->bestEndpoint} to $socket");
                        $this->bestEndpoint = $socket;
                    }
                }
                break;
            default:
                \var_dump($packet);
        }
    }
    /**
     * Start write loop.
     */
    private function startWriteLoop(): void
    {
        $this->voip_state = self::STATE_ESTABLISHED;
        Logger::log("Call established, sending OPUS data!");

        $this->tempHoldFiles = [];
        while (true) {
            $file = \array_shift($this->inputFiles);
            if (!$file) {
                if (empty($this->tempHoldFiles)) {
                    $this->tempHoldFiles = $this->holdFiles;
                }
                if (empty($this->tempHoldFiles)) {
                    return;
                }
                $file = \array_shift($this->tempHoldFiles);
            }
            $it = $this->openFile($file);
            foreach ($it->opusPackets as $packet) {
                $this->packetQueue->enqueue($packet);
            }
            $t = \microtime(true) + 0.060;
            while (!$this->packetQueue->isEmpty()) {
                $packet = $this->encryptPacket(['_' => self::PKT_STREAM_DATA, 'stream_id' => 0, 'data' => $this->packetQueue->dequeue(), 'timestamp' => $this->timestamp]);

                //Logger::log("Writing {$this->timestamp} in $this!");
                $diff = $t - \microtime(true);
                if ($diff > 0) {
                    delay($diff);
                }
                $this->bestEndpoint->write($packet);
                $t += 0.060;

                $this->timestamp += 60;
            }
        }
    }
    /**
     * Open OGG file for reading.
     */
    private function openFile(string $file): Ogg
    {
        $ctx = new ConnectionContext;
        $ctx->addStream(FileBufferedStream::class, openFile($file, 'r'));
        $stream = $ctx->getStream();
        return new Ogg($stream);
    }
    /**
     * Play file.
     */
    public function play(string $file): self
    {
        $this->inputFiles[] = $file;

        return $this;
    }

    /**
     * Play file.
     */
    public function then(string $file): self
    {
        $this->inputFiles[] = $file;

        return $this;
    }

    /**
     * Files to play on hold.
     */
    public function playOnHold(array $files): self
    {
        $this->holdFiles = $files;

        return $this;
    }

    /**
     * Set output file.
     */
    public function setOutputFile(string $file): self
    {
        $this->outputFile = $file;

        return $this;
    }

    /**
     * Unset output file.
     */
    public function unsetOutputFile(): self
    {
        $this->outputFile = null;

        return $this;
    }

    /**
     * Set MadelineProto instance.
     */
    public function setMadeline(MTProto $MadelineProto): void
    {
        $this->MadelineProto = $MadelineProto;
    }

    /**
     * Get call protocol.
     */
    public function getProtocol(): array
    {
        return $this->protocol;
    }

    /**
     * Get ID of other user.
     */
    public function getOtherID(): int
    {
        return $this->otherID;
    }

    /**
     * Get call ID.
     *
     */
    public function getCallID(): string|int
    {
        return $this->callID;
    }

    /**
     * Get creation date.
     *
     */
    public function whenCreated(): int|bool
    {
        return $this->internalStorage['created'] ?? false;
    }

    /**
     * Parse config.
     */
    public function parseConfig(): void
    {
    }

    /**
     * Get call state.
     */
    public function getCallState(): int
    {
        return $this->callState ?? self::CALL_STATE_ENDED;
    }

    /**
     * Get library version.
     */
    public function getVersion(): string
    {
        return 'libponyvoip-1.0';
    }

    /**
     * Get preferred relay ID.
     */
    public function getPreferredRelayID(): int
    {
        return 0;
    }

    /**
     * Get last error.
     */
    public function getLastError(): string
    {
        return '';
    }

    /**
     * Get debug log.
     */
    public function getDebugLog(): string
    {
        return '';
    }

    /**
     * Get signal bar count.
     */
    public function getSignalBarsCount(): int
    {
        return $this->signal;
    }

    /**
     * Get the value of creator.
     */
    public function isCreator(): bool
    {
        return $this->creator;
    }

    /**
     * Get the value of authKey.
     */
    public function getAuthKey(): PermAuthKey
    {
        return $this->authKey;
    }

    /**
     * Get the value of peerVersion.
     */
    public function getPeerVersion(): int
    {
        return $this->peerVersion;
    }

    /**
     * Get call representation.
     */
    public function __toString(): string
    {
        $id = $this->callID['id'];
        return "call {$id} with {$this->otherID}";
    }
}
