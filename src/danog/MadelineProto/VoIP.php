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
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\Stream\Common\FileBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\Ogg\Ogg;
use danog\MadelineProto\VoIP\Endpoint;

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

    private $MadelineProto;
    public $received_timestamp_map = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    public $remote_ack_timestamp_map = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    public $session_out_seq_no = 0;
    public $session_in_seq_no = 0;
    public $voip_state = 0;
    public $configuration = ['endpoints' => [], 'shared_config' => []];
    public $storage = [];
    public $internalStorage = [];
    private $signal = 0;
    private $callState;
    private $callID;
    private $creatorID;
    private $otherID;
    private $protocol;
    private $visualization;
    private $holdFiles = [];
    private $inputFiles;
    private $outputFile;
    private $isPlaying = false;

    private bool $creator;

    private PermAuthKey $authKey;
    private int $peerVersion = 0;

    /**
     * @var Endpoint[]
     */
    private array $sockets = [];

    private $connection_settings = [];
    private $dclist = [];

    private $datacenter;

    public function __construct(bool $creator, int $otherID, MTProto $MadelineProto, $callState)
    {
        $this->creator = $creator;
        $this->otherID = $otherID;
        //$this->callID = $callID;
        $this->madeline = $this->MadelineProto = $MadelineProto;
        $this->callState = $callState;
        //$this->protocol = $protocol;
        $this->TLID_REFLECTOR_SELF_INFO = \strrev(\hex2bin(self::TLID_REFLECTOR_SELF_INFO_HEX));
        $this->TLID_REFLECTOR_PEER_INFO = \strrev(\hex2bin(self::TLID_REFLECTOR_PEER_INFO_HEX));
        $this->TLID_DECRYPTED_AUDIO_BLOCK = \strrev(\hex2bin(self::TLID_DECRYPTED_AUDIO_BLOCK_HEX));
        $this->TLID_SIMPLE_AUDIO_BLOCK = \strrev(\hex2bin(self::TLID_SIMPLE_AUDIO_BLOCK_HEX));
    }

    public static function getConnectionMaxLayer(): int
    {
        return 92;
    }

    public function deInitVoIPController()
    {
    }

    public function getDebugString(): string
    {
        return '';
    }

    public function setCall($callID)
    {
        $this->callID = $callID;
    }

    public function setVisualization($visualization)
    {
        $this->visualization = $visualization;
    }

    public function getVisualization()
    {
        return $this->visualization;
    }

    public function discard($reason = ['_' => 'phoneCallDiscardReasonDisconnect'], $rating = [], $debug = false)
    {
        if ($this->callState === self::CALL_STATE_ENDED || empty($this->configuration)) {
            return false;
        }
        $this->deinitVoIPController();

        return Tools::callFork($this->MadelineProto->discardCall($this->callID, $reason, $rating, $debug));
    }

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

    public function close()
    {
        $this->deinitVoIPController();
    }

    public function startTheMagic()
    {
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
                    Logger::log($e);
                    unset($this->sockets[$k]);
                }
            }
            $this->init_all();
            Tools::callFork((function () use ($socket) {
                while ($payload = yield from $this->recv_message($socket)) {
                    Tools::callFork($this->handlePacket($socket, $payload));
                }
            })());
        })());
        return $this;
    }
    public function handlePacket($datacenter, $packet)
    {
        //\var_dump($packet);
        switch ($packet['_']) {
            case self::PKT_INIT:
                //$this->voip_state = self::STATE_WAIT_INIT_ACK;
                $this->send_message(['_' => self::PKT_INIT_ACK, 'protocol' => self::PROTOCOL_VERSION, 'min_protocol' => self::MIN_PROTOCOL_VERSION, 'all_streams' => [['id' => 0, 'type' => self::STREAM_TYPE_AUDIO, 'codec' => self::CODEC_OPUS, 'frame_duration' => 60, 'enabled' => 1]]], $datacenter);

                if ($this->voip_state !== self::STATE_ESTABLISHED) {
                    $this->voip_state = self::STATE_ESTABLISHED;

                    $ctx = new ConnectionContext;
                    $ctx->addStream(FileBufferedStream::class, yield open('kda.opus', 'r'));
                    $stream = yield from $ctx->getStream();
                    $ogg = yield from Ogg::init($stream, 60000);
                    $it = $ogg->getEmitter()->iterate();
                    Tools::callFork($ogg->read());
                    Tools::callFork((function () use ($it, $datacenter) {
                        $timestamp = 0;
                        $frames = [];
                        while (yield $it->advance()) {
                            $frames []= $it->getCurrent();
                        }
                        foreach ($frames as $frame) {
                            $t = (\microtime(true) / 1000) + 60;
                            yield $this->send_message(['_' => self::PKT_STREAM_DATA, 'stream_id' => 0, 'data' => $frame, 'timestamp' => $timestamp], $datacenter);

                            yield new Delayed((int) ($t - (\microtime(true) / 1000)));

                            $timestamp += 60;
                        }
                    })());
                }
                break;
            case self::PKT_INIT_ACK:
                break;
        }
    }
    public $timestamp = 0;
    public function oggCallback($data)
    {
        \var_dump(\strlen($data));
        $this->send_message(['_' => self::PKT_STREAM_DATA, 'stream_id' => 0, 'data' => $data, 'timestamp' => $this->timestamp]);
        $this->timestamp += 60;
    }
    public function play($file)
    {
        $this->inputFiles[] = $file;

        return $this;
    }

    public function then($file)
    {
        $this->inputFiles[] = $file;

        return $this;
    }

    public function playOnHold($files)
    {
        $this->holdFiles = $files;

        return $this;
    }

    public function setOutputFile($file)
    {
        $this->outputFile = $file;

        return $this;
    }

    public function unsetOutputFile()
    {
        $this->outputFile = null;
    }

    public function setMadeline($MadelineProto)
    {
        $this->MadelineProto = $MadelineProto;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getOtherID()
    {
        return $this->otherID;
    }

    public function getCallID()
    {
        return $this->callID;
    }

    public function whenCreated()
    {
        return isset($this->internalStorage['created']) ? $this->internalStorage['created'] : false;
    }

    public function parseConfig()
    {
    }

    private function init_all()
    {
        foreach ($this->sockets as $socket) {
            $this->send_message(['_' => self::PKT_INIT, 'protocol' => self::PROTOCOL_VERSION, 'min_protocol' => self::MIN_PROTOCOL_VERSION, 'audio_streams' => [self::CODEC_OPUS], 'video_streams' => []], $socket);
            $this->voip_state = self::STATE_WAIT_INIT;
        }
    }

    public function getCallState()
    {
        return $this->callState;
    }

    public function getVersion()
    {
        return 'libponyvoip-1.0';
    }

    public function getPreferredRelayID()
    {
        return 0;
    }

    public function getLastError()
    {
        return '';
    }

    public function getDebugLog()
    {
        return '';
    }

    public function getSignalBarsCount()
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
}
