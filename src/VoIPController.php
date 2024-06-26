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

use Amp\ByteStream\BufferedReader;
use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\Sync\LocalMutex;
use danog\Loop\Loop;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\RPCError\CallAlreadyAcceptedError;
use danog\MadelineProto\RPCError\CallAlreadyDeclinedError;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIP\Endpoint;
use danog\MadelineProto\VoIP\MessageHandler;
use danog\MadelineProto\VoIP\VoIPState;
use phpseclib3\Math\BigInteger;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\delay;
use function Amp\File\openFile;
use function Amp\Future\await;

/** @internal */
final class VoIPController
{
    public const CALL_PROTOCOL = [
        '_' => 'phoneCallProtocol',
        'udp_p2p' => true,
        'udp_reflector' => true,
        'min_layer' => 65,
        'max_layer' => 92,
        /*'library_versions' => [
            "2.4.4",
            "2.7.7",
            "5.0.0",
            "6.0.0",
            "7.0.0",
            "8.0.0",
            "9.0.0",
            "10.0.0",
            "11.0.0"
        ]*/
    ];
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

    private MessageHandler $messageHandler;
    private VoIPState $voipState = VoIPState::CREATED;
    private CallState $callState;

    private array $call;

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

    private DjLoop $diskJockey;

    /** Auth key */
    private readonly string $authKey;

    public readonly VoIP $public;
    /** @var ?list{string, string, string, string} */
    private ?array $visualization = null;

    private LocalMutex $authMutex;

    private ?LocalFile $outputFile = null;
    private ?OggWriter $outputStream = null;
    private ?int $outputStreamId = null;

    /**
     * Constructor.
     *
     * @internal
     */
    public function __construct(
        public readonly MTProto $API,
        array $call
    ) {
        $this->public = new VoIP($API, $call);
        $call['_'] = 'inputPhoneCall';
        $this->diskJockey = new DjLoop($this);
        Assert::true($this->diskJockey->start());
        $this->call = $call;
        if ($this->public->outgoing) {
            $this->callState = CallState::REQUESTED;
        } else {
            $this->callState = CallState::INCOMING;
        }
        $this->authMutex = new LocalMutex;
    }

    public function __serialize(): array
    {
        $result = get_object_vars($this);
        unset($result['authMutex'], $result['outputStream']);

        return $result;
    }
    /**
     * Wakeup function.
     */
    public function __unserialize(array $data): void
    {
        $this->authMutex = new LocalMutex;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->diskJockey ??= new DjLoop($this);
        Assert::true($this->diskJockey->start());
        EventLoop::queue(function (): void {
            if (isset($this->outputFile)) {
                \assert($this->outputStreamId !== null);
                $out = openFile($this->outputFile->file, 'a');
                $this->outputStream = new OggWriter($out, $this->outputStreamId);
            }
            if ($this->callState === CallState::RUNNING) {
                if ($this->voipState === VoIPState::CREATED) {
                    // No endpoints yet
                    return;
                }
                $this->lastIncomingTimestamp = microtime(true);
                $this->connectToAll();
                if ($this->voipState === VoIPState::WAIT_PONG) {
                    $this->pendingPing = EventLoop::repeat(0.2, $this->ping(...));
                } elseif ($this->voipState === VoIPState::WAIT_STREAM_INIT) {
                    $this->initStream();
                } elseif ($this->voipState === VoIPState::ESTABLISHED) {
                    $diff = (int) ((microtime(true) - $this->lastOutgoingTimestamp) * 1000);
                    $this->opusTimestamp += $diff - ($diff % 60);
                    $this->startWriteLoop();
                }
            }
        });
    }

    /**
     * Confirm requested call.
     * @internal
     */
    public function confirm(array $params): bool
    {
        $lock = $this->authMutex->acquire();
        try {
            if ($this->callState !== CallState::REQUESTED) {
                $this->log(sprintf(Lang::$current_lang['call_error_2'], $this->public->callID));
                return false;
            }
            $this->log(sprintf(Lang::$current_lang['call_confirming'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig();
            $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
            Crypt::checkG($params['g_b'], $dh_config['p']);
            $key = str_pad($params['g_b']->powMod($this->call['a'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
            try {
                $res = ($this->API->methodCallAsyncRead('phone.confirmCall', [
                    'key_fingerprint' => substr(sha1($key, true), -8),
                    'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'],
                    'g_a' => $this->call['g_a'],
                    'protocol' => self::CALL_PROTOCOL,
                ]))['phone_call'];
            } catch (CallAlreadyAcceptedError) {
                $this->log(sprintf(Lang::$current_lang['call_already_accepted'], $params['id']));
                return true;
            } catch (CallAlreadyDeclinedError) {
                $this->log(Lang::$current_lang['call_already_declined']);
                $this->discard(DiscardReason::HANGUP);
                return false;
            }
            $visualization = [];
            $length = new BigInteger(\count(Magic::$emojis));
            \assert(isset($this->call['g_a']) && \is_string($this->call['g_a']));
            foreach (str_split(hash('sha256', $key.str_pad($this->call['g_a'], 256, \chr(0), STR_PAD_LEFT), true), 8) as $number) {
                $number[0] = \chr(\ord($number[0]) & 0x7f);
                $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
            }
            $this->visualization = $visualization;
            $this->authKey = $key;
            $this->callState = CallState::RUNNING;
            $this->messageHandler = new MessageHandler(
                $this,
                substr(hash('sha256', $key, true), -16)
            );
            $this->initialize($res['connections']);
            return true;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Accept incoming call.
     */
    public function accept(?Cancellation $cancellation = null): self
    {
        $lock = $this->authMutex->acquire();
        try {
            if ($this->callState === CallState::RUNNING || $this->callState === CallState::ENDED) {
                return $this;
            }
            Assert::eq($this->callState->name, CallState::INCOMING->name);

            $this->log(sprintf(Lang::$current_lang['accepting_call'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig($cancellation);
            $this->log('Generating b...', Logger::VERBOSE);
            $b = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
            $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
            Crypt::checkG($g_b, $dh_config['p']);

            $this->callState = CallState::ACCEPTED;
            try {
                $this->API->methodCallAsyncRead('phone.acceptCall', [
                    'peer' => [
                        'id' => $this->call['id'],
                        'access_hash' => $this->call['access_hash'],
                        '_' => 'inputPhoneCall',
                    ],
                    'g_b' => $g_b->toBytes(),
                    'protocol' => self::CALL_PROTOCOL,
                    'cancellation' => $cancellation,
                ]);
            } catch (CallAlreadyAcceptedError) {
                $this->log(sprintf(Lang::$current_lang['call_already_accepted'], $this->public->callID));
                return $this;
            } catch (CallAlreadyDeclinedError) {
                $this->log(Lang::$current_lang['call_already_declined']);
                $this->discard(DiscardReason::HANGUP);
                return $this;
            }
            $this->call['b'] = $b;

            return $this;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    /**
     * Complete call handshake.
     *
     * @internal
     */
    public function complete(array $params): bool
    {
        $lock = $this->authMutex->acquire();
        try {
            if ($this->callState !== CallState::ACCEPTED) {
                return false;
            }
            $this->log(sprintf(Lang::$current_lang['call_completing'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig();
            if (hash('sha256', (string) $params['g_a_or_b'], true) !== (string) $this->call['g_a_hash']) {
                throw new SecurityException('Invalid g_a!');
            }
            $params['g_a_or_b'] = new BigInteger((string) $params['g_a_or_b'], 256);
            Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
            $key = str_pad($params['g_a_or_b']->powMod($this->call['b'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
            if (substr(sha1($key, true), -8) != $params['key_fingerprint']) {
                throw new SecurityException(Lang::$current_lang['fingerprint_invalid']);
            }
            $visualization = [];
            $length = new BigInteger(\count(Magic::$emojis));
            foreach (str_split(hash('sha256', $key.str_pad($params['g_a_or_b']->toBytes(), 256, \chr(0), STR_PAD_LEFT), true), 8) as $number) {
                $number[0] = \chr(\ord($number[0]) & 0x7f);
                $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
            }
            $this->visualization = $visualization;
            $this->authKey = $key;
            $this->callState = CallState::RUNNING;
            $this->messageHandler = new MessageHandler(
                $this,
                substr(hash('sha256', $key, true), -16)
            );
            $this->initialize($params['connections']);
            return true;
        } finally {
            EventLoop::queue($lock->release(...));
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
     * @param int<1, 5> $rating  Call rating in stars
     * @param string    $comment Additional comment on call quality.
     */
    public function discard(DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null): self
    {
        if ($this->callState === CallState::ENDED) {
            return $this;
        }
        $this->API->waitForInit();
        $this->API->cleanupCall($this->public->callID);
        $this->callState = CallState::ENDED;
        $this->diskJockey->discard();
        $this->skip();

        $this->log("Now closing $this");
        if (isset($this->timeoutWatcher)) {
            EventLoop::cancel($this->timeoutWatcher);
        }

        if (isset($this->pendingPing)) {
            EventLoop::cancel($this->pendingPing);
        }

        $this->log("Closing all sockets in $this");
        foreach ($this->sockets as $socket) {
            $socket->disconnect();
        }
        $this->log("Closed all sockets, discarding $this");

        $this->log(sprintf(Lang::$current_lang['call_discarding'], $this->public->callID), Logger::VERBOSE);
        try {
            $this->API->methodCallAsyncRead('phone.discardCall', ['peer' => $this->call, 'duration' => time() - $this->public->date, 'connection_id' => 0, 'reason' => ['_' => match ($reason) {
                DiscardReason::BUSY => 'phoneCallDiscardReasonBusy',
                DiscardReason::HANGUP => 'phoneCallDiscardReasonHangup',
                DiscardReason::DISCONNECTED => 'phoneCallDiscardReasonDisconnect',
                DiscardReason::MISSED => 'phoneCallDiscardReasonMissed'
            }]]);
        } catch (CallAlreadyAcceptedError|CallAlreadyDeclinedError) {
        }
        if ($rating !== null) {
            $this->log(sprintf('Setting rating for call %s...', $this->call), Logger::VERBOSE);
            $this->API->methodCallAsyncRead('phone.setCallRating', ['peer' => $this->call, 'rating' => $rating, 'comment' => $comment]);
        }
        return $this;
    }

    private const SIGNALING_MIN_SIZE = 21;
    private const SIGNALING_MAX_SIZE = 128 * 1024 * 1024;
    public function onSignaling(string $data): void
    {
        if (\strlen($data) < self::SIGNALING_MIN_SIZE || \strlen($data) > self::SIGNALING_MAX_SIZE) {
            Logger::log('Wrong size in signaling!', Logger::ERROR);
            return;
        }
        $message_key = substr($data, 0, 16);
        $data = substr($data, 16);
        [$aes_key, $aes_iv, $x] = Crypt::voipKdf($message_key, $this->authKey, $this->public->outgoing, false);
        $packet = Crypt::ctrEncrypt($data, $aes_key, $aes_iv);

        if ($message_key != substr(hash('sha256', substr($this->authKey, 88 + $x, 32).$packet, true), 8, 16)) {
            Logger::log('msg_key mismatch!', Logger::ERROR);
            return;
        }

        $packet = new BufferedReader(new ReadableBuffer($packet));

        $packets = [];
        while ($packet->isReadable()) {
            $seq = unpack('N', $packet->readLength(4))[1];
            $length = unpack('N', $packet->readLength(4))[1];
            $packets []= self::deserializeRtc($packet);
        }
    }

    public static function deserializeRtc(BufferedReader $buffer): array
    {
        switch ($t = \ord($buffer->readLength(1))) {
            case 1:
                $candidates = [];
                for ($x = \ord($buffer->readLength(1)); $x > 0; $x--) {
                    $candidates []= self::readString($buffer);
                }
                return [
                    '_' => 'candidatesList',
                    'ufrag' => self::readString($buffer),
                    'pwd' => self::readString($buffer),
                ];
            case 2:
                $formats = [];
                for ($x = \ord($buffer->readLength(1)); $x > 0; $x--) {
                    $name = self::readString($buffer);
                    $parameters = [];
                    for ($x = \ord($buffer->readLength(1)); $x > 0; $x--) {
                        $key = self::readString($buffer);
                        $value = self::readString($buffer);
                        $parameters[$key] = $value;
                    }
                    $formats[]= [
                        'name' => $name,
                        'parameters' => $parameters,
                    ];
                }
                return [
                    '_' => 'videoFormats',
                    'formats' => $formats,
                    'encoders' => \ord($buffer->readLength(1)),
                ];
            case 3:
                return ['_' => 'requestVideo'];
            case 4:
                $state = \ord($buffer->readLength(1));
                return ['_' => 'remoteMediaState', 'audio' => $state & 0x01, 'video' => ($state >> 1) & 0x03];
            case 5:
                return ['_' => 'audioData', 'data' => self::readBuffer($buffer)];
            case 6:
                return ['_' => 'videoData', 'data' => self::readBuffer($buffer)];
            case 7:
                return ['_' => 'unstructuredData', 'data' => self::readBuffer($buffer)];
            case 8:
                return ['_' => 'videoParameters', 'aspectRatio' => unpack('V', $buffer->readLength(4))[1]];
            case 9:
                return ['_' => 'remoteBatteryLevelIsLow', 'isLow' => (bool) \ord($buffer->readLength(1))];
            case 10:
                $lowCost = (bool) \ord($buffer->readLength(1));
                $isLowDataRequested = (bool) \ord($buffer->readLength(1));
                return ['_' => 'remoteNetworkStatus', 'lowCost' => $lowCost, 'isLowDataRequested' => $isLowDataRequested];
        }
        return ['_' => 'unknown', 'type' => $t];
    }
    private static function readString(BufferedReader $buffer): string
    {
        /** @psalm-suppress InvalidArgument */
        return $buffer->readLength(\ord($buffer->readLength(1)));
    }
    private static function readBuffer(BufferedReader $buffer): string
    {
        return $buffer->readLength(unpack('n', $buffer->readLength(2))[1]);
    }

    private function setVoipState(VoIPState $state): bool
    {
        if ($this->voipState->value >= $state->value) {
            return false;
        }
        $old = $this->voipState;
        $this->voipState = $state;
        $this->log("Changing state from {$old->name} to {$state->name} in $this");
        return true;
    }
    /**
     * Connect to the specified endpoints.
     */
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
                        $this->public->outgoing,
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
                        $this->public->outgoing,
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
                $this->discard(DiscardReason::DISCONNECTED);
            }
        });

        $promises = [];
        foreach ($this->sockets as $socket) {
            $promise = async(function () use ($socket): void {
                try {
                    $this->log("Connecting to $socket...");
                    $socket->connect();
                    $this->log("Successfully connected to $socket!");
                    $this->startReadLoop($socket);
                } catch (Throwable $e) {
                    $this->log("Got error while connecting to $socket: $e");
                }
            });
            if ((!isset($this->bestEndpoint) && $socket->udp) || (isset($this->bestEndpoint) && $socket === $this->bestEndpoint)) {
                $promises []= $promise;
            }
        }
        await($promises);
    }
    /**
     * Handle incoming packet.
     */
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
        if ($this->outputStream !== null && $cnt) {
            unset($packet['_'], $packet['extra']);
            foreach ($packet as ['data' => $data]) {
                $this->outputStream->writeChunk($data, 2880, false);
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
                    $this->log("Sending PKT_INIT to $endpoint...");
                    if (!$endpoint->sendInit()) {
                        return;
                    }
                    delay(0.5);
                }
            });
            $this->log("Started read loop in $endpoint!");
            while (true) {
                try {
                    $payload = $endpoint->read();
                } catch (Throwable $e) {
                    $this->log("Got $e in $endpoint, $this!");
                    continue;
                }
                if (!$payload) {
                    break;
                }
                $this->lastIncomingTimestamp = microtime(true);
                EventLoop::queue($this->handlePacket(...), $endpoint, $payload);
            }
            $this->log("Exiting VoIP read loop in $endpoint, $this!");
        });
    }

    public function log(string $message, int $level = Logger::NOTICE): void
    {
        $this->API->logger->logger($message, $level);
    }
    private bool $muted = true;
    /**
     * Start write loop.
     */
    private function startWriteLoop(): void
    {
        $this->setVoipState(VoIPState::ESTABLISHED);

        $delay = $this->muted ? 0.2 : 0.06;
        $t = microtime(true) + $delay;
        while (true) {
            if ($packet = $this->diskJockey->pullPacket()) {
                if ($this->muted) {
                    $this->log("Unmuting outgoing audio in $this!");
                    if (!$this->bestEndpoint->writeReliably([
                        '_' => self::PKT_STREAM_STATE,
                        'id' => 0,
                        'enabled' => true,
                    ])) {
                        $this->log("Exiting write loop in $this because we could not write stream state!");
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
                    $this->log("Muting outgoing audio in $this!");
                    if (!$this->bestEndpoint->writeReliably([
                        '_' => self::PKT_STREAM_STATE,
                        'id' => 0,
                        'enabled' => false,
                    ])) {
                        $this->log("Exiting write loop in $this because we could not write stream state!");
                        return;
                    }
                    $this->muted = true;
                    $delay = 0.2;
                }
                $packet = $this->messageHandler->encryptPacket([
                    '_' => self::PKT_NOP,
                ]);
            }
            //$this->log("Writing {$this->opusTimestamp} in $this!");
            $cur = microtime(true);
            $diff = $t - $cur;
            if ($diff > 0) {
                delay($diff);
            }

            if (!$this->bestEndpoint->write($packet)) {
                $this->log("Exiting write loop in $this!");
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
     * Set output file or stream for incoming OPUS audio packets.
     *
     * Will write an OGG OPUS stream to the specified file or stream.
     */
    public function setOutput(LocalFile|WritableStream $file): void
    {
        if ($file instanceof LocalFile) {
            $this->outputFile = $file;
            $file = openFile($file->file, 'w');
        } else {
            $this->outputFile = null;
        }
        $this->outputStreamId = random_int(-(2**31), (2**31)-1);
        $this->outputStream = new OggWriter($file, $this->outputStreamId);
        $this->outputStream->writeHeader(1, 48000, "incoming audio stream");
    }

    /**
     * Play file.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        $this->diskJockey->play($file);
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    public function skip(): void
    {
        $this->diskJockey->skip();
    }
    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    public function stop(): void
    {
        $this->diskJockey->stopPlaying();
    }
    /**
     * Pauses the currently playing file.
     */
    public function pause(): void
    {
        $this->diskJockey->pausePlaying();
    }
    /**
     * Resumes the currently playing file.
     */
    public function resume(): void
    {
        $this->diskJockey->resumePlaying();
    }
    /**
     * Whether the file we're currently playing is paused.
     */
    public function isPaused(): bool
    {
        return $this->diskJockey->isAudioPaused();
    }
    /**
     * Files to play on hold.
     */
    public function playOnHold(LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->diskJockey->playOnHold(...$files);
    }
    /**
     * Get info about the audio currently being played.
     *
     */
    public function getCurrent(): LocalFile|RemoteUrl|string|null
    {
        return $this->diskJockey->getCurrent();
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
