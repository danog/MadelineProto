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

// IMPORTANT NOTE: Please keep the above copyright notice intact if copying or rewriting this file in another language.

namespace danog\MadelineProto\Tgcalls;

use Amp\ByteStream\WritableStream;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\VoIP\Endpoint;
use danog\MadelineProto\VoIP\MessageHandler;
use danog\MadelineProto\VoIP\VoIPState;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * The legacy libtgvoip engine, used when the other party is too old to speak WebRTC.
 *
 * libtgvoip does not use WebRTC at all: it opens plain UDP (or TCP) sockets to Telegram's
 * reflectors, tagging every packet with the `peer_tag` from
 * [phoneConnection](https://core.telegram.org/constructor/phoneConnection), and carries OPUS
 * frames inside its own encrypted packet format. Only versions 2.4.4 and 2.7.7 of the protocol
 * are still negotiated in practice, and only by very old clients.
 *
 * @internal
 */
final class LegacyController
{

    private VoIPState $voipState = VoIPState::CREATED;
    private MessageHandler $messageHandler;

    /** @var array<string, Endpoint> */
    private array $sockets = [];
    private Endpoint $bestEndpoint;
    private ?string $pendingPing = null;
    private ?string $timeoutWatcher = null;
    private float $lastIncomingTimestamp = 0.0;
    private float $lastOutgoingTimestamp = 0.0;
    private int $opusTimestamp = 0;
    private bool $muted = true;
    private bool $closed = false;

    private ?OpusRecorder $recorder = null;

    public function __construct(
        private readonly PrivateCallController $call,
        private readonly string $authKey,
        private readonly bool $outgoing,
        private readonly DjLoop $dj,
        array $connections,
    ) {
        $this->messageHandler = new MessageHandler(
            $this,
            substr(hash('sha256', $authKey, true), -16)
        );
        $this->initialize($connections);
    }

    /**
     * Drop the state that cannot be serialized before the graph is written out.
     *
     * The reflector {@see Endpoint}s serialize themselves (dropping their live sockets, which they
     * reopen on wakeup). What cannot survive is the OGG recorder's open file handle and the two
     * event-loop watcher IDs; they are dropped here and re-established on wakeup.
     *
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['recorder'], $vars['pendingPing'], $vars['timeoutWatcher']);
        return $vars;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->recorder = null;
        $this->pendingPing = null;
        $this->timeoutWatcher = null;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        if ($this->closed) {
            return;
        }
        // The read and write loops are detached event-loop tasks that cannot be serialized: redo the
        // libtgvoip handshake over the reconnecting sockets, which restarts both loops. Recording,
        // if any, is re-attached by PrivateCallController::__unserialize().
        EventLoop::queue(function (): void {
            if ($this->closed) {
                return;
            }
            unset($this->bestEndpoint);
            $this->voipState = VoIPState::WAIT_INIT;
            $this->muted = true;
            $this->connectToAll();
        });
    }

    /**
     * Set the output file or stream for the incoming audio.
     */
    public function setOutput(LocalFile|WritableStream $file): void
    {
        $this->recorder?->close();
        $this->recorder = new OpusRecorder($file);
    }

    /**
     * Tear down every reflector connection.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->recorder?->close();
        $this->recorder = null;
        if ($this->timeoutWatcher !== null) {
            EventLoop::cancel($this->timeoutWatcher);
            $this->timeoutWatcher = null;
        }
        if ($this->pendingPing !== null) {
            EventLoop::cancel($this->pendingPing);
            $this->pendingPing = null;
        }
        foreach ($this->sockets as $socket) {
            $socket->disconnect();
        }
        $this->sockets = [];
    }

    /**
     * Log a message, used by the packet handlers.
     */
    public function log(string $message, int $level = Logger::NOTICE): void
    {
        $this->call->log($message, $level);
    }

    public function getVoIPState(): VoIPState
    {
        return $this->voipState;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return (string) $this->call;
    }

    public const NET_TYPE_UNKNOWN = 0;
    public const NET_TYPE_GPRS = 1;
    public const NET_TYPE_EDGE = 2;
    public const NET_TYPE_3G = 3;
    public const NET_TYPE_HSPA = 4;
    public const NET_TYPE_LTE = 5;
    public const NET_TYPE_WIFI = 6;
    public const NET_TYPE_ETHERNET = 7;
    public const NET_TYPE_OTHER_HIGH_SPEED = 8;
    public const NET_TYPE_OTHER_LOW_SPEED = 9;
    public const NET_TYPE_DIALUP = 10;
    public const NET_TYPE_OTHER_MOBILE = 11;

    public const DATA_SAVING_NEVER = 0;
    public const DATA_SAVING_MOBILE = 1;
    public const DATA_SAVING_ALWAYS = 2;

    public const PROXY_NONE = 0;
    public const PROXY_SOCKS5 = 1;

    public const AUDIO_STATE_NONE = -1;
    public const AUDIO_STATE_CREATED = 0;
    public const AUDIO_STATE_CONFIGURED = 1;
    public const AUDIO_STATE_RUNNING = 2;

    public const PKT_INIT = 1;
    public const PKT_INIT_ACK = 2;
    public const PKT_STREAM_STATE = 3;
    public const PKT_STREAM_DATA = 4;
    public const PKT_UPDATE_STREAMS = 5;
    public const PKT_PING = 6;
    public const PKT_PONG = 7;
    public const PKT_STREAM_DATA_X2 = 8;
    public const PKT_STREAM_DATA_X3 = 9;
    public const PKT_LAN_ENDPOINT = 10;
    public const PKT_NETWORK_CHANGED = 11;
    public const PKT_SWITCH_PREF_RELAY = 12;
    public const PKT_SWITCH_TO_P2P = 13;
    public const PKT_NOP = 14;

    public const TLID_DECRYPTED_AUDIO_BLOCK = "\xc1\xdb\xf9\x48";
    public const TLID_SIMPLE_AUDIO_BLOCK = "\x0d\x0e\x76\xcc";

    public const TLID_REFLECTOR_SELF_INFO = "\xC7\x72\x15\xc0";
    public const TLID_REFLECTOR_PEER_INFO = "\x1C\x37\xD9\x27";

    public const PROTO_ID = 'GrVP';

    public const PROTOCOL_VERSION = 9;
    public const MIN_PROTOCOL_VERSION = 9;

    public const STREAM_TYPE_AUDIO = 1;
    public const STREAM_TYPE_VIDEO = 2;

    public const CODEC_OPUS = 'SUPO';

    private function setVoipState(VoIPState $state): bool
    {
        if ($this->voipState->value >= $state->value) {
            return false;
        }
        $old = $this->voipState;
        $this->voipState = $state;
        $this->call->log("Changing state from {$old->name} to {$state->name} in {$this->call}");
        return true;
    }

    private function initialize(array $endpoints): void
    {
        foreach ([true, false] as $udp) {
            foreach ($endpoints as $endpoint) {
                if ($endpoint['_'] !== 'phoneConnection') {
                    continue;
                }
                if (!isset($this->sockets[($udp ? 'udp' : 'tcp').' v6 '.$endpoint['id']])) {
                    $this->sockets[($udp ? 'udp' : 'tcp').' v6 '.$endpoint['id']] = new Endpoint(
                        $udp,
                        '['.$endpoint['ipv6'].']',
                        $endpoint['port'],
                        $endpoint['peer_tag'],
                        true,
                        $this->outgoing,
                        $this->authKey,
                        $this->messageHandler
                    );
                }
                if (!isset($this->sockets[($udp ? 'udp' : 'tcp').' v4 '.$endpoint['id']])) {
                    $this->sockets[($udp ? 'udp' : 'tcp').' v4 '.$endpoint['id']] = new Endpoint(
                        $udp,
                        $endpoint['ip'],
                        $endpoint['port'],
                        $endpoint['peer_tag'],
                        true,
                        $this->outgoing,
                        $this->authKey,
                        $this->messageHandler
                    );
                }
            }
        }
        $this->setVoipState(VoIPState::WAIT_INIT);
        $this->connectToAll();
    }

    private function connectToAll(): void
    {
        $this->timeoutWatcher = EventLoop::repeat(10, function (): void {
            if (microtime(true) - $this->lastIncomingTimestamp > 10) {
                $this->call->onConnectionFailed();
            }
        });

        $promises = [];
        foreach ($this->sockets as $socket) {
            $promise = async(function () use ($socket): void {
                try {
                    $this->call->log("Connecting to $socket...");
                    $socket->connect();
                    $this->call->log("Successfully connected to $socket!");
                    $this->startReadLoop($socket);
                } catch (Throwable $e) {
                    $this->call->log("Got error while connecting to $socket: $e");
                }
            });
            if ((!isset($this->bestEndpoint) && $socket->udp) || (isset($this->bestEndpoint) && $socket === $this->bestEndpoint)) {
                $promises []= $promise;
            }
        }
        await($promises);
    }

    private function handlePacket(Endpoint $socket, array $packet): void
    {
        $cnt = 0;
        switch ($packet['_']) {
            case self::PKT_INIT:
                $this->setVoipState(VoIPState::WAIT_INIT_ACK);
                $socket->write($this->messageHandler->encryptPacket([
                    '_' => self::PKT_INIT_ACK,
                    'protocol' => self::PROTOCOL_VERSION,
                    'min_protocol' => self::MIN_PROTOCOL_VERSION,
                    'all_streams' => [
                        ['id' => 0, 'type' => self::STREAM_TYPE_AUDIO, 'codec' => self::CODEC_OPUS, 'frame_duration' => 60, 'enabled' => 1],
                    ],
                ]));
                $socket->sendInit();
                break;

            case self::PKT_INIT_ACK:
                if ($this->setVoipState(VoIPState::WAIT_PONG)) {
                    $this->pendingPing = EventLoop::repeat(0.2, $this->ping(...));
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
            case self::PKT_PING:
                $socket->write($this->messageHandler->encryptPacket(['_' => self::PKT_PONG, 'out_seq_no' => $packet['out_seq_no']]));
                break;
            case self::PKT_PONG:
                if ($this->setVoipState(VoIPState::WAIT_STREAM_INIT)) {
                    EventLoop::cancel($this->pendingPing);
                    $this->pendingPing = null;
                    $this->bestEndpoint ??= $socket;
                    $this->initStream();
                }
                break;
        }
        if ($cnt !== 0 && $this->recorder !== null) {
            unset($packet['_'], $packet['extra']);
            foreach ($packet as ['data' => $data]) {
                $this->recorder->writeOpus($data);
            }
        }
    }

    private function initStream(): void
    {
        $this->bestEndpoint->writeReliably([
            '_' => self::PKT_STREAM_STATE,
            'id' => 0,
            'enabled' => false,
        ]);

        $this->startWriteLoop();
    }

    private function ping(): void
    {
        foreach ($this->sockets as $socket) {
            EventLoop::queue(fn () => $socket->write($this->messageHandler->encryptPacket(['_' => self::PKT_PING])));
        }
    }

    private function startReadLoop(Endpoint $endpoint): void
    {
        EventLoop::queue(function () use ($endpoint): void {
            EventLoop::queue(function () use ($endpoint): void {
                while ($this->voipState->value <= VoIPState::WAIT_INIT_ACK->value) {
                    $this->call->log("Sending PKT_INIT to $endpoint...");
                    if (!$endpoint->sendInit()) {
                        return;
                    }
                    delay(0.5);
                }
            });
            $this->call->log("Started read loop in $endpoint!");
            while (true) {
                try {
                    $payload = $endpoint->read();
                } catch (Throwable $e) {
                    $this->call->log("Got $e in $endpoint, {$this->call}!");
                    continue;
                }
                if (!$payload) {
                    break;
                }
                $this->lastIncomingTimestamp = microtime(true);
                EventLoop::queue($this->handlePacket(...), $endpoint, $payload);
            }
            $this->call->log("Exiting VoIP read loop in $endpoint, {$this->call}!");
        });
    }

    private function startWriteLoop(): void
    {
        $this->setVoipState(VoIPState::ESTABLISHED);

        $delay = $this->muted ? 0.2 : 0.06;
        $t = microtime(true) + $delay;
        while (true) {
            if ($packet = $this->dj->pullPacket()) {
                if ($this->muted) {
                    $this->call->log("Unmuting outgoing audio in {$this->call}!");
                    if (!$this->bestEndpoint->writeReliably([
                        '_' => self::PKT_STREAM_STATE,
                        'id' => 0,
                        'enabled' => true,
                    ])) {
                        $this->call->log("Exiting write loop in {$this->call} because we could not write stream state!");
                        return;
                    }
                    $this->muted = false;
                    $delay = 0.06;
                    $this->opusTimestamp = 0;
                }
                $packet = $this->messageHandler->encryptPacket([
                    '_' => self::PKT_STREAM_DATA,
                    'stream_id' => 0,
                    'data' => $packet,
                    'timestamp' => $this->opusTimestamp,
                ]);
                $this->opusTimestamp += 60;
            } else {
                if (!$this->muted) {
                    $this->call->log("Muting outgoing audio in {$this->call}!");
                    if (!$this->bestEndpoint->writeReliably([
                        '_' => self::PKT_STREAM_STATE,
                        'id' => 0,
                        'enabled' => false,
                    ])) {
                        $this->call->log("Exiting write loop in {$this->call} because we could not write stream state!");
                        return;
                    }
                    $this->muted = true;
                    $delay = 0.2;
                }
                $packet = $this->messageHandler->encryptPacket([
                    '_' => self::PKT_NOP,
                ]);
            }
            //$this->call->log("Writing {$this->opusTimestamp} in {$this->call}!");
            $cur = microtime(true);
            $diff = $t - $cur;
            if ($diff > 0) {
                delay($diff);
            }

            if (!$this->bestEndpoint->write($packet)) {
                $this->call->log("Exiting write loop in {$this->call}!");
                return;
            }

            if ($diff > 0) {
                $cur += $diff;
            }
            $this->lastOutgoingTimestamp = $cur;

            $t += $delay;
        }
    }
}
