<?php
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

use Amp\Delayed;
use Amp\Loop;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\Stream\Common\FileBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\Ogg\Ogg;
use danog\MadelineProto\VoIP\Endpoint;
use SplQueue;

use function Amp\File\open;

if (\extension_loaded('php-libtgvoip')) {
    return;
}

class VoIP
{
    use \danog\MadelineProto\VoIP\MessageHandler;
    use \danog\MadelineProto\VoIP\AckHandler;

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

    const TLID_DECRYPTED_AUDIO_BLOCK_HEX = 'dbf948c1';
    const TLID_SIMPLE_AUDIO_BLOCK_HEX = 'cc0d0e76';

    const TLID_REFLECTOR_SELF_INFO_HEX = 'c01572c7';
    const TLID_REFLECTOR_PEER_INFO_HEX = '27D9371C';

    const PROTO_ID = 'GrVP';

    const PROTOCOL_VERSION = 9;
    const MIN_PROTOCOL_VERSION = 9;

    const STREAM_TYPE_AUDIO = 1;
    const STREAM_TYPE_VIDEO = 2;

    const CODEC_OPUS = 'SUPO';


    private $TLID_DECRYPTED_AUDIO_BLOCK;
    private $TLID_SIMPLE_AUDIO_BLOCK;
    private $TLID_REFLECTOR_SELF_INFO;
    private $TLID_REFLECTOR_PEER_INFO;

    private MTProto $MadelineProto;
    public MTProto $madeline;
    public $received_timestamp_map = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    public $remote_ack_timestamp_map = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    public $session_out_seq_no = 0;
    public $session_in_seq_no = 0;
    public $voip_state = 0;
    public $configuration = ['endpoints' => [], 'shared_config' => []];
    public $storage = [];
    public $internalStorage = [];
    private $signal = 0;
    private int $callState;
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
     * @var Endpoint[]
     */
    private array $sockets = [];

    /**
     * Timeout watcher.
     */
    private string $timeoutWatcher;

    /**
     * Last incoming timestamp.
     *
     * @var float
     */
    private $lastIncomingTimestamp = 0.0;
    /**
     * The outgoing timestamp.
     *
     * @var int
     */
    private $timestamp = 0;
    /**
     * Packet queue.
     *
     * @var SplQueue
     */
    private $packetQueue;
    /**
     * Temporary holdfile array.
     */
    private array $tempHoldFiles = [];
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep()
    {
        $vars = \get_object_vars($this);
        unset($vars['sockets'], $vars['timeoutWatcher']);

        return \array_keys($vars);
    }
    /**
     * Wakeup function.
     */
    public function __wakeup()
    {
        if ($this->voip_state === self::STATE_ESTABLISHED) {
            $this->voip_state = self::STATE_CREATED;
            $this->startTheMagic();
        }
    }
    /**
     * Constructor.
     *
     * @param boolean $creator
     * @param integer $otherID
     * @param MTProto $MadelineProto
     * @param integer $callState
     */
    public function __construct(bool $creator, int $otherID, MTProto $MadelineProto, int $callState)
    {
        $this->creator = $creator;
        $this->otherID = $otherID;
        $this->madeline = $this->MadelineProto = $MadelineProto;
        $this->callState = $callState;
        $this->packetQueue = new SplQueue;
        $this->TLID_REFLECTOR_SELF_INFO = \strrev(\hex2bin(self::TLID_REFLECTOR_SELF_INFO_HEX));
        $this->TLID_REFLECTOR_PEER_INFO = \strrev(\hex2bin(self::TLID_REFLECTOR_PEER_INFO_HEX));
        $this->TLID_DECRYPTED_AUDIO_BLOCK = \strrev(\hex2bin(self::TLID_DECRYPTED_AUDIO_BLOCK_HEX));
        $this->TLID_SIMPLE_AUDIO_BLOCK = \strrev(\hex2bin(self::TLID_SIMPLE_AUDIO_BLOCK_HEX));
    }

    /**
     * Get max layer.
     *
     * @return integer
     */
    public static function getConnectionMaxLayer(): int
    {
        return 92;
    }

    /**
     * Get debug string.
     *
     * @return string
     */
    public function getDebugString(): string
    {
        return '';
    }

    /**
     * Set call constructor.
     *
     * @param array $callID
     * @return void
     */
    public function setCall(array $callID): void
    {
        $this->protocol = $callID['protocol'];
        $this->callID = [
            '_' => 'inputPhoneCall',
            'id' => $callID['id'],
            'access_hash' => $callID['access_hash']
        ];
    }

    /**
     * Set emojis.
     *
     * @param array $visualization
     * @return void
     */
    public function setVisualization(array $visualization): void
    {
        $this->visualization = $visualization;
    }

    /**
     * Get emojis.
     *
     * @return array
     */
    public function getVisualization(): array
    {
        return $this->visualization;
    }

    /**
     * Discard call.
     *
     * @param array $reason
     * @param array $rating
     * @param boolean $debug
     * @return self|false
     */
    public function discard($reason = ['_' => 'phoneCallDiscardReasonDisconnect'], $rating = [], $debug = false)
    {
        if (($this->callState ?? self::CALL_STATE_ENDED) === self::CALL_STATE_ENDED || empty($this->configuration)) {
            return false;
        }
        $this->callState = self::CALL_STATE_ENDED;
        Logger::log("Now closing $this");
        if (isset($this->timeoutWatcher)) {
            Loop::cancel($this->timeoutWatcher);
        }

        Logger::log("Closing all sockets in $this");
        foreach ($this->sockets as $socket) {
            $socket->disconnect();
        }
        Logger::log("Closed all sockets, discarding $this");

        return Tools::callFork($this->MadelineProto->discardCall($this->callID, $reason, $rating, $debug));
    }

    public function __destruct()
    {
        $this->discard(['_' => 'phoneCallDiscardReasonDisconnect']);
    }
    /**
     * Accept call.
     *
     * @return self|false
     */
    public function accept()
    {
        if ($this->callState !== self::CALL_STATE_INCOMING) {
            return false;
        }
        $this->callState = self::CALL_STATE_ACCEPTED;

        Tools::call($this->MadelineProto->acceptCall($this->callID))->onResolve(function ($e, $res) {
            if ($e || !$res) {
                $this->discard(['_' => 'phoneCallDiscardReasonDisconnect']);
            }
        });

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
        $this->timeoutWatcher = Loop::repeat(10000, function () {
            if (\microtime(true) - $this->lastIncomingTimestamp > 10) {
                $this->discard(['_' => 'phoneCallDiscardReasonDisconnect']);
            }
        });
        Tools::callFork((function () {
            $this->authKey = new PermAuthKey();
            $this->authKey->setAuthKey($this->configuration['auth_key']);

            foreach ($this->configuration['endpoints'] as $endpoint) {
                $this->sockets['v6 '.$endpoint['id']] = new Endpoint('['.$endpoint['ipv6'].']', $endpoint['port'], $endpoint['peer_tag'], true, $this);
                $this->sockets['v4 '.$endpoint['id']] = new Endpoint($endpoint['ip'], $endpoint['port'], $endpoint['peer_tag'], true, $this);
            }
            foreach ($this->sockets as $k => $socket) {
                try {
                    yield from $socket->connect();
                } catch (\Throwable $e) {
                    unset($this->sockets[$k]);
                }
            }
            foreach ($this->sockets as $socket) {
                $this->send_message(['_' => self::PKT_INIT, 'protocol' => self::PROTOCOL_VERSION, 'min_protocol' => self::MIN_PROTOCOL_VERSION, 'audio_streams' => [self::CODEC_OPUS], 'video_streams' => []], $socket);
                Tools::callFork((function () use ($socket) {
                    while ($payload = yield from $this->recv_message($socket)) {
                        $this->lastIncomingTimestamp = \microtime(true);
                        Tools::callFork($this->handlePacket($socket, $payload));
                    }
                    Logger::log("Exiting VoIP read loop in $this!");
                })());
            }
        })());
        return $this;
    }
    /**
     * Handle incoming packet.
     */
    private function handlePacket(Endpoint $socket, array $packet): \Generator
    {
        switch ($packet['_']) {
            case self::PKT_INIT:
                //$this->voip_state = self::STATE_WAIT_INIT_ACK;
                $this->send_message(['_' => self::PKT_INIT_ACK, 'protocol' => self::PROTOCOL_VERSION, 'min_protocol' => self::MIN_PROTOCOL_VERSION, 'all_streams' => [['id' => 0, 'type' => self::STREAM_TYPE_AUDIO, 'codec' => self::CODEC_OPUS, 'frame_duration' => 60, 'enabled' => 1]]], $socket);

                yield from $this->startWriteLoop($socket);
                break;
            case self::PKT_INIT_ACK:
                yield from $this->startWriteLoop($socket);
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
        }
        if (isset($cnt)) {
        }
    }
    /**
     * Start write loop.
     *
     * @param Endpoint $socket
     * @return \Generator
     */
    private function startWriteLoop(Endpoint $socket): \Generator
    {
        if ($this->voip_state === self::STATE_ESTABLISHED) {
            return;
        }
        $this->voip_state = self::STATE_ESTABLISHED;

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
            $it = yield from $this->openFile($file);
            if ($this->MadelineProto->getSettings()->getVoip()->getPreloadAudio()) {
                while (yield $it->advance()) {
                    $this->packetQueue->enqueue($it->getCurrent());
                }
                $t = (\microtime(true) / 1000) + 60;
                while (!$this->packetQueue->isEmpty()) {
                    if (!yield $this->send_message(['_' => self::PKT_STREAM_DATA, 'stream_id' => 0, 'data' => $this->packetQueue->dequeue(), 'timestamp' => $this->timestamp], $socket)) {
                        Logger::log("Exiting VoIP write loop in $this!");
                        return;
                    }

                    //Logger::log("Writing {$this->timestamp} in $this!");
                    yield new Delayed((int) ($t - (\microtime(true) / 1000)));
                    $t = (\microtime(true) / 1000) + 60;

                    $this->timestamp += 60;
                }
            } else {
                $t = (\microtime(true) / 1000) + 60;
                while (yield $it->advance()) {
                    if (!yield $this->send_message(['_' => self::PKT_STREAM_DATA, 'stream_id' => 0, 'data' => $it->getCurrent(), 'timestamp' => $this->timestamp], $socket)) {
                        Logger::log("Exiting VoIP write loop in $this!");
                        return;
                    }

                    //Logger::log("Writing {$this->timestamp} in $this!");
                    yield new Delayed((int) ($t - (\microtime(true) / 1000)));
                    $t = (\microtime(true) / 1000) + 60;

                    $this->timestamp += 60;
                }
            }
        }
    }
    /**
     * Open OGG file for reading.
     *
     * @param string $file
     * @return \Generator
     */
    private function openFile(string $file): \Generator
    {
        $ctx = new ConnectionContext;
        $ctx->addStream(FileBufferedStream::class, yield open($file, 'r'));
        $stream = yield from $ctx->getStream();
        $ogg = yield from Ogg::init($stream, 60000);
        $it = $ogg->getEmitter()->iterate();
        Tools::callFork($ogg->read());
        return $it;
    }
    /**
     * Play file.
     *
     * @param string $file
     * @return self
     */
    public function play(string $file): self
    {
        $this->inputFiles[] = $file;

        return $this;
    }

    /**
     * Play file.
     *
     * @param string $file
     * @return self
     */
    public function then(string $file): self
    {
        $this->inputFiles[] = $file;

        return $this;
    }

    /**
     * Files to play on hold.
     *
     * @param array $files
     * @return self
     */
    public function playOnHold(array $files): self
    {
        $this->holdFiles = $files;

        return $this;
    }

    /**
     * Set output file.
     *
     * @param string $file
     * @return self
     */
    public function setOutputFile(string $file): self
    {
        $this->outputFile = $file;

        return $this;
    }

    /**
     * Unset output file.
     *
     * @return self
     */
    public function unsetOutputFile(): self
    {
        $this->outputFile = null;

        return $this;
    }


    /**
     * Set MadelineProto instance.
     *
     * @param MTProto $MadelineProto
     * @return void
     */
    public function setMadeline(MTProto $MadelineProto): void
    {
        $this->MadelineProto = $this->madeline = $MadelineProto;
    }

    /**
     * Get call protocol.
     *
     * @return array
     */
    public function getProtocol(): array
    {
        return $this->protocol;
    }

    /**
     * Get ID of other user.
     *
     * @return int
     */
    public function getOtherID(): int
    {
        return $this->otherID;
    }

    /**
     * Get call ID.
     *
     * @return string|int
     */
    public function getCallID()
    {
        return $this->callID;
    }

    /**
     * Get creation date.
     *
     * @return int|bool
     */
    public function whenCreated()
    {
        return isset($this->internalStorage['created']) ? $this->internalStorage['created'] : false;
    }

    /**
     * Parse config.
     *
     * @return void
     */
    public function parseConfig(): void
    {
    }

    /**
     * Get call state.
     *
     * @return int
     */
    public function getCallState(): int
    {
        return $this->callState ?? self::CALL_STATE_ENDED;
    }

    /**
     * Get library version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return 'libponyvoip-1.0';
    }

    /**
     * Get preferred relay ID.
     *
     * @return integer
     */
    public function getPreferredRelayID(): int
    {
        return 0;
    }

    /**
     * Get last error.
     *
     * @return string
     */
    public function getLastError(): string
    {
        return '';
    }

    /**
     * Get debug log.
     *
     * @return string
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
     *
     * @return bool
     */
    public function isCreator(): bool
    {
        return $this->creator;
    }

    /**
     * Get the value of authKey.
     *
     * @return PermAuthKey
     */
    public function getAuthKey(): PermAuthKey
    {
        return $this->authKey;
    }

    /**
     * Get the value of peerVersion.
     *
     * @return int
     */
    public function getPeerVersion(): int
    {
        return $this->peerVersion;
    }

    /**
     * Get call representation.
     *
     * @return string
     */
    public function __toString()
    {
        $id = $this->callID['id'];
        return "call {$id} with {$this->otherID}";
    }
}
