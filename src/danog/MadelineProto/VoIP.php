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

if (!extension_loaded('php-libtgvoip')) {
    class VoIP
    {
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
        public $configuration = [];
        public $storage = [];
        private $internalStorage = [];
        private $otherID;
        private $creator;
        private $callState;
        private $callID;
        private $visualization;

        public function __construct($creator, $otherID, $callID, $MadelineProto, $callState, $protocol)
        {
            $this->creator = $creator;
            $this->otherID = $otherID;
            $this->callID = $callID;
            $this->MadelineProto = $MadelineProto;
            $this->callState = $callState;
            $this->configuration['protocol'] = $protocol;
        }

        public function setVisualization($visualization)
        {
            $this->visualization = $visualization;
        }

        public function parseConfig()
        {
        }

        public function startTheMagic()
        {
            var_dump($this->configuration);
        }

        public function getCallState()
        {
            return $this->callState;
        }
    }
}
