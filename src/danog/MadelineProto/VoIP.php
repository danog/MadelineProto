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

if (\extension_loaded('php-libtgvoip')) {
    return;
}

class VoIP extends Tools
{
    use \danog\MadelineProto\VoIP\MessageHandler;
    use \danog\MadelineProto\VoIP\AckHandler;

    const PHP_LIBTGVOIP_VERSION = '1.1.2';
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

    const PROTOCOL_VERSION = 3;
    const MIN_PROTOCOL_VERSION = 3;

    const STREAM_TYPE_AUDIO = 1;
    const STREAM_TYPE_VIDEO = 2;

    const CODEC_OPUS = 1;


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

    private $connection_settings = [];
    private $dclist = [];

    private $datacenter;

    public function __construct(bool $creator, int $otherID, $callID, MTProto $MadelineProto, $callState, $protocol)
    {
        $this->creator = $creator;
        $this->otherID = $otherID;
        $this->callID = $callID;
        $this->MadelineProto = $MadelineProto;
        $this->callState = $callState;
        $this->protocol = $protocol;
        $this->TLID_REFLECTOR_SELF_INFO = \strrev(\hex2bin(self::TLID_REFLECTOR_SELF_INFO_HEX));
        $this->TLID_REFLECTOR_PEER_INFO = \strrev(\hex2bin(self::TLID_REFLECTOR_PEER_INFO_HEX));
        $this->TLID_DECRYPTED_AUDIO_BLOCK = \strrev(\hex2bin(self::TLID_DECRYPTED_AUDIO_BLOCK_HEX));
        $this->TLID_SIMPLE_AUDIO_BLOCK = \strrev(\hex2bin(self::TLID_SIMPLE_AUDIO_BLOCK_HEX));
    }

    public function deInitVoIPController()
    {
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
        while (true) {
            $waiting = $this->datacenter->select();
            foreach ($waiting as $dc) {
                if ($packet = $this->recv_message($dc)) {
                    $this->handlePacket($dc, $packet);
                }
            }
        }
        return $this;
    }
    public function handlePacket($datacenter, $packet)
    {
        \var_dump($packet);
        switch ($packet['_']) {
                case self::PKT_INIT:
                    $this->voip_state = self::STATE_WAIT_INIT_ACK;
                    $this->send_message(['_' => self::PKT_INIT_ACK, 'protocol' => self::PROTOCOL_VERSION, 'min_protocol' => self::MIN_PROTOCOL_VERSION, 'all_streams' => [['id' => 0, 'type' => self::STREAM_TYPE_AUDIO, 'codec' => self::CODEC_OPUS, 'frame_duration' => 60, 'enabled' => 1]]], $datacenter);
                    //$a = fopen('paloma.opus', 'rb');
                    //(new Ogg($a, [$this, 'oggCallback']))->run();
                    break;
                case self::PKT_INIT_ACK:
                    $this->voip_state = self::STATE_ESTABLISHED;
                    $a = \fopen('paloma.opus', 'rb');
                    (new Ogg($a, [$this, 'oggCallback']))->run();

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

    public function isCreator()
    {
        return $this->creator;
    }

    public function whenCreated()
    {
        return isset($this->internalStorage['created']) ? $this->internalStorage['created'] : false;
    }

    public function parseConfig()
    {
        if (\count($this->configuration['endpoints'])) {
            $this->connection_settings['all'] = $this->MadelineProto->settings['connection_settings']['all'];
            $this->connection_settings['all']['protocol'] = 'obfuscated2';
            $this->connection_settings['all']['timeout'] = 1;
            $this->connection_settings['all']['do_not_retry'] = true;

            $test = $this->connection_settings['all']['test_mode'] ? 'test' : 'main';
            foreach ($this->configuration['endpoints'] as $endpoint) {
                $this->dclist[$test]['ipv6'][$endpoint['id']] = ['ip_address' => $endpoint['ipv6'], 'port' => $endpoint['port'], 'peer_tag' => $endpoint['peer_tag']];
                $this->dclist[$test]['ipv4'][$endpoint['id']] = ['ip_address' => $endpoint['ip'], 'port' => $endpoint['port'], 'peer_tag' => $endpoint['peer_tag']];
            }
            if (!isset($this->datacenter)) {
                $this->datacenter = new DataCenter($this->dclist, $this->connection_settings);
            }
            //$this->datacenter->__construct($this->dclist, $this->connection_settings);

            foreach ($this->datacenter->get_dcs() as $new_dc) {
                try {
                    $this->datacenter->dc_connect($new_dc);
                } catch (\danog\MadelineProto\Exception $e) {
                }
            }
            $this->init_all();
            foreach ($this->datacenter->get_dcs(false) as $new_dc) {
                try {
                    $this->datacenter->dc_connect($new_dc);
                } catch (\danog\MadelineProto\Exception $e) {
                }
            }
            $this->init_all();
        }
    }

    private function init_all()
    {
        $test = $this->connection_settings['all']['test_mode'] ? 'test' : 'main';
        foreach ($this->datacenter->sockets as $dc_id => $socket) {
            if ($socket->auth_key === null) {
                $socket->auth_key = ['id' => $this->configuration['auth_key_id'], 'auth_key' => $this->configuration['auth_key'], 'connection_inited' => false];
            }
            if ($socket->type === Connection::API_ENDPOINT) {
                $socket->type = Connection::VOIP_TCP_REFLECTOR_ENDPOINT;
            }
            if ($socket->peer_tag === null) {
                switch ($socket->type) {
                        case Connection::VOIP_TCP_REFLECTOR_ENDPOINT:
                        case Connection::VOIP_UDP_REFLECTOR_ENDPOINT:
                            $socket->peer_tag = $this->dclist[$test]['ipv4'][$dc_id]['peer_tag'];
                            break;
                        default:
                            $socket->peer_tag = $this->configuration['call_id'];
                    }
            }
            //if ($this->voip_state === self::STATE_CREATED) {
            $this->send_message(['_' => self::PKT_INIT, 'protocol' => self::PROTOCOL_VERSION, 'min_protocol' => self::MIN_PROTOCOL_VERSION, 'audio_streams' => [self::CODEC_OPUS], 'video_streams' => []], $dc_id);
            $this->voip_state = self::STATE_WAIT_INIT;
            //}
            if (isset($this->datacenter->sockets[$dc_id])) {
                $this->send_message(['_' => self::PKT_PING], $dc_id);
            }
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
}
