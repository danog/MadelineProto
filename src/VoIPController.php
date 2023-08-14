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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

// Please keep the above notice the next time you copy my code, or I will sue you :)

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableStream;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIP\Endpoint;
use danog\MadelineProto\VoIP\MessageHandler;
use danog\MadelineProto\VoIP\VoIPState;
use phpseclib3\Math\BigInteger;
use Revolt\EventLoop;
use SplQueue;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\delay;

/** @internal */
final class VoIPController
{
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

    private MessageHandler $messageHandler;
    private VoIPState $voipState = VoIPState::CREATED;
    private CallState $callState;

    private array $call;

    /** @var array<LocalFile|RemoteUrl|ReadableStream> */
    private array $holdFiles = [];
    /** @var list<LocalFile|RemoteUrl|ReadableStream> */
    private array $inputFiles = [];
    private int $holdIndex = 0;

    /**
     * @var array<Endpoint>
     */
    private array $sockets = [];
    private Endpoint $bestEndpoint;
    private ?string $pendingPing = null;
    private ?string $timeoutWatcher = null;
    private float $lastIncomingTimestamp = 0.0;
    private float $lastOutgoingTimestamp = 0.0;
    private int $opusTimestamp = 0;
    private SplQueue $packetQueue;

    /** Auth key */
    private readonly string $authKey;

    public readonly VoIP $public;
    /** @var ?list{string, string, string, string} */
    private ?array $visualization = null;

    /**
     * Constructor.
     *
     * @internal
     */
    public function __construct(
        private readonly MTProto $API,
        array $call
    ) {
        $this->public = new VoIP($API, $call);
        $call['_'] = 'inputPhoneCall';
        $this->packetQueue = new SplQueue;
        $this->call = $call;
        if ($this->public->outgoing) {
            $this->callState = CallState::REQUESTED;
        } else {
            $this->callState = CallState::INCOMING;
        }
    }

    /**
     * Confirm requested call.
     * @internal
     */
    public function confirm(array $params): bool
    {
        if ($this->callState !== CallState::REQUESTED) {
            $this->API->logger->logger(\sprintf(Lang::$current_lang['call_error_2'], $this->public->callID));
            return false;
        }
        $this->API->logger->logger(\sprintf(Lang::$current_lang['call_confirming'], $this->public->otherID), Logger::VERBOSE);
        $dh_config = $this->API->getDhConfig();
        $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
        Crypt::checkG($params['g_b'], $dh_config['p']);
        $key = \str_pad($params['g_b']->powMod($this->call['a'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
        try {
            $res = ($this->API->methodCallAsyncRead('phone.confirmCall', ['key_fingerprint' => \substr(\sha1($key, true), -8), 'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'], 'g_a' => $this->call['g_a'], 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'min_layer' => 65, 'max_layer' => 92]]))['phone_call'];
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->API->logger->logger(\sprintf(Lang::$current_lang['call_already_accepted'], $params['id']));
                return true;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->API->logger->logger(Lang::$current_lang['call_already_declined']);
                $this->discard(DiscardReason::HANGUP);
                return false;
            }
            throw $e;
        }
        $visualization = [];
        $length = new BigInteger(\count(Magic::$emojis));
        foreach (\str_split(\hash('sha256', $key.\str_pad($this->call['g_a'], 256, \chr(0), STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->visualization = $visualization;
        $this->authKey = $key;
        $this->callState = CallState::RUNNING;
        $this->messageHandler = new MessageHandler(
            $this,
            \substr(\hash('sha256', $key, true), -16)
        );
        $this->initialize($res['connections']);
        return true;
    }
    /**
     * Accept incoming call.
     */
    public function accept(): self
    {
        if ($this->callState === CallState::RUNNING || $this->callState === CallState::ENDED) {
            return $this;
        }
        Assert::eq($this->callState->name, CallState::INCOMING->name);

        $this->API->logger->logger(\sprintf(Lang::$current_lang['accepting_call'], $this->public->otherID), Logger::VERBOSE);
        $dh_config = $this->API->getDhConfig();
        $this->API->logger->logger('Generating b...', Logger::VERBOSE);
        $b = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        Crypt::checkG($g_b, $dh_config['p']);

        $this->callState = CallState::ACCEPTED;
        try {
            $this->API->methodCallAsyncRead('phone.acceptCall', ['peer' => ['id' => $this->call['id'], 'access_hash' => $this->call['access_hash'], '_' => 'inputPhoneCall'], 'g_b' => $g_b->toBytes(), 'protocol' => ['_' => 'phoneCallProtocol', 'udp_reflector' => true, 'udp_p2p' => true, 'min_layer' => 65, 'max_layer' => 92]]);
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'CALL_ALREADY_ACCEPTED') {
                $this->API->logger->logger(\sprintf(Lang::$current_lang['call_already_accepted'], $this->public->callID));
                return $this;
            }
            if ($e->rpc === 'CALL_ALREADY_DECLINED') {
                $this->API->logger->logger(Lang::$current_lang['call_already_declined']);
                $this->discard(DiscardReason::HANGUP);
                return $this;
            }
            throw $e;
        }
        $this->call['b'] = $b;

        return $this;
    }

    /**
     * Complete call handshake.
     *
     * @internal
     */
    public function complete(array $params): bool
    {
        if ($this->callState !== CallState::ACCEPTED) {
            $this->API->logger->logger(\sprintf(Lang::$current_lang['call_error_3'], $params['id']));
            return false;
        }
        $this->API->logger->logger(\sprintf(Lang::$current_lang['call_completing'], $this->public->otherID), Logger::VERBOSE);
        $dh_config = $this->API->getDhConfig();
        if (\hash('sha256', (string) $params['g_a_or_b'], true) !== (string) $this->call['g_a_hash']) {
            throw new SecurityException('Invalid g_a!');
        }
        $params['g_a_or_b'] = new BigInteger((string) $params['g_a_or_b'], 256);
        Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
        $key = \str_pad($params['g_a_or_b']->powMod($this->call['b'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
        if (\substr(\sha1($key, true), -8) != $params['key_fingerprint']) {
            throw new SecurityException(Lang::$current_lang['fingerprint_invalid']);
        }
        $visualization = [];
        $length = new BigInteger(\count(Magic::$emojis));
        foreach (\str_split(\hash('sha256', $key.\str_pad($params['g_a_or_b']->toBytes(), 256, \chr(0), STR_PAD_LEFT), true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        $this->visualization = $visualization;
        $this->authKey = $key;
        $this->callState = CallState::RUNNING;
        $this->messageHandler = new MessageHandler(
            $this,
            \substr(\hash('sha256', $key, true), -16)
        );
        $this->initialize($params['connections']);
        return true;
    }
    /**
     * Wakeup function.
     */
    public function __wakeup(): void
    {
        if ($this->callState === CallState::RUNNING) {
            $this->lastIncomingTimestamp = \microtime(true);
            $this->startReadLoop();
            if ($this->pendingPing) {
                $this->pendingPing = EventLoop::repeat(0.2, $this->ping(...));
            }
            if ($this->voipState === VoIPState::ESTABLISHED) {
                $diff = (int) ((\microtime(true) - $this->lastOutgoingTimestamp) * 1000);
                $this->opusTimestamp += $diff - ($diff % 60);
                EventLoop::queue($this->startWriteLoop(...));
            }
        }
    }

    /**
     * Get call emojis (will return null if the call is not inited yet).
     *
     * @return ?list{string, string, string, string}
     */
    public function getVisualization(): ?array
    {
        return $this->visualization;
    }

    /**
     * Discard call.
     *
     * @param int<1, 5> $rating Call rating in stars
     * @param string $comment Additional comment on call quality.
     */
    public function discard(DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null): self
    {
        if ($this->callState === CallState::ENDED) {
            return $this;
        }
        $this->API->cleanupCall($this->public->callID);

        Logger::log("Now closing $this");
        if (isset($this->timeoutWatcher)) {
            EventLoop::cancel($this->timeoutWatcher);
        }

        if (isset($this->pendingPing)) {
            EventLoop::cancel($this->pendingPing);
        }

        Logger::log("Closing all sockets in $this");
        foreach ($this->sockets as $socket) {
            $socket->disconnect();
        }
        $this->packetQueue = new SplQueue;
        Logger::log("Closed all sockets, discarding $this");

        $this->API->logger->logger(\sprintf(Lang::$current_lang['call_discarding'], $this->public->callID), Logger::VERBOSE);
        try {
            $this->API->methodCallAsyncRead('phone.discardCall', ['peer' => $this->call, 'duration' => \time() - $this->public->date, 'connection_id' => 0, 'reason' => ['_' => match ($reason) {
                DiscardReason::BUSY => 'phoneCallDiscardReasonBusy',
                DiscardReason::HANGUP => 'phoneCallDiscardReasonHangup',
                DiscardReason::DISCONNECTED => 'phoneCallDiscardReasonDisconnect',
                DiscardReason::MISSED => 'phoneCallDiscardReasonMissed'
            }]]);
        } catch (RPCErrorException $e) {
            if (!\in_array($e->rpc, ['CALL_ALREADY_DECLINED', 'CALL_ALREADY_ACCEPTED'], true)) {
                throw $e;
            }
        }
        if ($rating !== null) {
            $this->API->logger->logger(\sprintf('Setting rating for call %s...', $this->call), Logger::VERBOSE);
            $this->API->methodCallAsyncRead('phone.setCallRating', ['peer' => $this->call, 'rating' => $rating, 'comment' => $comment]);
        }
        $this->callState = CallState::ENDED;
        return $this;
    }

    /**
     * Connect to the specified endpoints.
     */
    private function initialize(array $endpoints): void
    {
        foreach ($endpoints as $endpoint) {
            try {
                $this->sockets['v6 '.$endpoint['id']] = new Endpoint(
                    '['.$endpoint['ipv6'].']',
                    $endpoint['port'],
                    $endpoint['peer_tag'],
                    true,
                    $this->public->outgoing,
                    $this->authKey,
                    $this->messageHandler
                );
            } catch (Throwable) {
            }
            try {
                $this->sockets['v4 '.$endpoint['id']] = new Endpoint(
                    $endpoint['ip'],
                    $endpoint['port'],
                    $endpoint['peer_tag'],
                    true,
                    $this->public->outgoing,
                    $this->authKey,
                    $this->messageHandler
                );
            } catch (Throwable) {
            }
        }
        $this->voipState = VoIPState::WAIT_INIT;
        $this->startReadLoop();
        foreach ($this->sockets as $socket) {
            $socket->udpPing();
            $socket->write($this->messageHandler->encryptPacket([
                '_' => self::PKT_INIT,
                'protocol' => self::PROTOCOL_VERSION,
                'min_protocol' => self::MIN_PROTOCOL_VERSION,
                'audio_streams' => [self::CODEC_OPUS],
                'video_streams' => []
            ], true));
        }
    }
    /**
     * Handle incoming packet.
     */
    private function handlePacket(Endpoint $socket, array $packet): void
    {
        switch ($packet['_']) {
            case self::PKT_INIT:
                //$this->voipState = VoIPState::WAIT_INIT_ACK;
                $socket->write($this->messageHandler->encryptPacket([
                    '_' => self::PKT_INIT_ACK,
                    'protocol' => self::PROTOCOL_VERSION,
                    'min_protocol' => self::MIN_PROTOCOL_VERSION,
                    'all_streams' => [
                        ['id' => 0, 'type' => self::STREAM_TYPE_AUDIO, 'codec' => self::CODEC_OPUS, 'frame_duration' => 60, 'enabled' => 1]
                    ]
                ]));
                $socket->write($this->messageHandler->encryptPacket([
                    '_' => self::PKT_INIT,
                    'protocol' => self::PROTOCOL_VERSION,
                    'min_protocol' => self::MIN_PROTOCOL_VERSION,
                    'audio_streams' => [self::CODEC_OPUS],
                    'video_streams' => []
                ]));
                break;

            case self::PKT_INIT_ACK:
                if (!isset($this->bestEndpoint)) {
                    $this->bestEndpoint = $socket;
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
                if ($this->pendingPing) {
                    EventLoop::cancel($this->pendingPing);
                    $this->pendingPing = null;
                    if ($this->bestEndpoint !== $socket) {
                        Logger::log("Changing best endpoint from {$this->bestEndpoint} to $socket");
                        $this->bestEndpoint = $socket;
                    }
                    $this->startWriteLoop();
                }
                break;
        }
    }
    private function ping(): void
    {
        foreach ($this->sockets as $socket) {
            EventLoop::queue(fn () => $socket->write($this->messageHandler->encryptPacket(['_' => self::PKT_PING])));
        }
    }
    private function startReadLoop(): void
    {
        foreach ($this->sockets as $socket) {
            EventLoop::queue(function () use ($socket): void {
                while ($payload = $socket->read()) {
                    $this->lastIncomingTimestamp = \microtime(true);
                    EventLoop::queue($this->handlePacket(...), $socket, $payload);
                }
                Logger::log("Exiting VoIP read loop in $this!");
            });
        }
    }
    private bool $muted = false;
    private bool $playingHold = false;
    private function pullPacket(): ?string
    {
        if ($this->packetQueue->isEmpty()) {
            $file = \array_shift($this->inputFiles);
            if ($file) {
                $this->playingHold = false;
            } else {
                $this->playingHold = true;
                if (!$this->holdFiles) {
                    return null;
                }
                $file = $this->holdFiles[($this->holdIndex++) % \count($this->holdFiles)];
            }
            $it = new Ogg($file);
            foreach ($it->opusPackets as $packet) {
                $this->packetQueue->enqueue($packet);
            }
        }
        return $this->packetQueue->dequeue();
    }
    /**
     * Start write loop.
     */
    private function startWriteLoop(): void
    {
        $this->voipState = VoIPState::ESTABLISHED;
        Logger::log("Call established in $this, sending OPUS data!");

        $this->timeoutWatcher = EventLoop::repeat(10, function (): void {
            if (\microtime(true) - $this->lastIncomingTimestamp > 10) {
                $this->discard(DiscardReason::DISCONNECTED);
            }
        });

        $delay = $this->muted ? 0.2 : 0.06;
        $t = \microtime(true) + $delay;
        while (true) {
            if ($packet = $this->pullPacket()) {
                if ($this->muted) {
                    if (!$this->bestEndpoint->writeReliably([
                        '_' => self::PKT_STREAM_STATE,
                        'id' => 0,
                        'enabled' => true
                    ])) {
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
                    'timestamp' => $this->opusTimestamp
                ]);
                $this->opusTimestamp += 60;
            } else {
                if (!$this->muted) {
                    if (!$this->bestEndpoint->writeReliably([
                        '_' => self::PKT_STREAM_STATE,
                        'id' => 0,
                        'enabled' => false
                    ])) {
                        return;
                    }
                    $this->muted = true;
                    $delay = 0.2;
                }
                $packet = $this->messageHandler->encryptPacket([
                    '_' => self::PKT_NOP
                ]);
            }
            //Logger::log("Writing {$this->opusTimestamp} in $this!");
            $cur = \microtime(true);
            $diff = $t - $cur;
            if ($diff > 0) {
                delay($diff);
            } else {
                EventLoop::queue(Logger::log(...), "We're late while sending audio data!");
            }

            if (!$this->bestEndpoint->write($packet)) {
                return;
            }

            if ($diff > 0) {
                $cur += $diff;
            }
            $this->lastOutgoingTimestamp = $cur;

            $t += $delay;
        }
    }
    /**
     * Play file.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file): self
    {
        $this->inputFiles[] = $file;
        if ($this->playingHold) {
            $this->packetQueue = new SplQueue;
        }

        return $this;
    }

    /**
     * Files to play on hold.
     *
     * @param array<LocalFile|RemoteUrl|ReadableStream> $files
     */
    public function playOnHold(array $files): self
    {
        $this->holdFiles = $files;

        return $this;
    }

    /**
     * Get call state.
     */
    public function getCallState(): CallState
    {
        return $this->callState;
    }
    /**
     * Get VoIP state.
     */
    public function getVoIPState(): VoIPState
    {
        return $this->voipState;
    }

    /**
     * Get call representation.
     */
    public function __toString(): string
    {
        return $this->public->__toString();
    }
}
