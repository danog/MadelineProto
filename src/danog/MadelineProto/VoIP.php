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

if (!extension_loaded('php-libtgvoip') && false) {
    class VoIP
    {
        use \danog\MadelineProto\MTProtoTools\MessageHandler;

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

        private $MadelineProto;
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

        public function __construct($creator, $otherID, $callID, $MadelineProto, $callState, $protocol)
        {
            $this->creator = $creator;
            $this->otherID = $otherID;
            $this->callID = $callID;
            $this->MadelineProto = $MadelineProto;
            $this->callState = $callState;
            $this->protocol = $protocol;
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
            $this->MadelineProto->discard_call($this->callID, $reason, $rating, $debug);
            $this->deinitVoIPController();

            return $this;
        }

        public function accept()
        {
            if ($this->callState !== self::CALL_STATE_INCOMING) {
                return false;
            }
            $this->callState = self::CALL_STATE_ACCEPTED;
            if (!$this->MadelineProto->accept_call($this->callID)) {
                $this->discard_call(['_' => 'phoneCallDiscardReasonDisconnect']);

                return false;
            }

            return $this;
        }

        public function close()
        {
            $this->deinitVoIPController();
        }

        public function startTheMagic()
        {
            return $this;
        }

        public function play($file)
        {
            $this->inputFiles[] = $file;

            return $this;
        }

        public function playOnHold($files)
        {
            $this->holdFiles = $files;
        }

        public function setOutputFile($file)
        {
            $this->outputFile = $file;
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
            if (count($this->configuration['endpoints'])) {
                $this->connection_settings['all'] = $this->MadelineProto->settings['connection_settings']['all'];
                $this->connection_settings['all']['protocol'] = 'obfuscated2';
                $this->connection_settings['all']['do_not_retry'] = true;

                $test = $this->connection_settings['all']['test_mode'] ? 'test' : 'main';
                foreach ($this->configuration['endpoints'] as $endpoint) {
                    $this->dclist[$test]['ipv6'][$endpoint['id']] = ['ip_address' => $endpoint['ipv6'], 'port' => $endpoint['port'], 'peer_tag' => $endpoint['peer_tag']];
                    $this->dclist[$test]['ipv4'][$endpoint['id']] = ['ip_address' => $endpoint['ip'], 'port' => $endpoint['port'], 'peer_tag' => $endpoint['peer_tag']];
                }
                if (!isset($this->datacenter)) {
                    $this->datacenter = new DataCenter($this->dclist, $this->connection_settings);
                } else {
                    //$this->datacenter->__construct($this->dclist, $this->connection_settings);
                }
                foreach ($this->datacenter->get_dcs() as $new_dc) {
                    $this->datacenter->dc_connect($new_dc);
                }
                $this->init_all();
                foreach ($this->datacenter->get_dcs(false) as $new_dc) {
                    $this->datacenter->dc_connect($new_dc);
                }
                $this->init_all();
            }
        }

        private function init_all()
        {
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
}
