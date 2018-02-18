<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/
if (!extension_loaded('pthreads')) {
    if (!extension_loaded('sockets')) {
        define('AF_INET', 0);
        define('AF_INET6', 1);
        define('SOCK_STREAM', 2);
        define('SOL_SOCKET', 3);
        define('SO_RCVTIMEO', 4);
        define('SO_SNDTIMEO', 5);
    }
    class Socket
    {
        private $sock;
        private $protocol;
        private $timeout = ['sec' => 0, 'usec' => 0];
        private $blocking = false;
        private $domain;
        private $type;

        public function __construct(int $domain, int $type, int $protocol)
        {
            $this->domain = $domain;
            $this->type = $type;
            $this->protocol = $protocol === PHP_INT_MAX ? 'tls' : getprotobynumber($protocol);
        }

        public function __destruct()
        {
            if ($this->sock !== null) {
                fclose($this->sock);
            }
        }

        public function setOption(int $level, int $name, $value)
        {
            if (in_array($name, [\SO_RCVTIMEO, \SO_SNDTIMEO])) {
                $this->timeout = ['sec' => (int) $value, 'usec' => (int) (($value - (int) $value) * 1000000)];
                stream_set_timeout($this->sock, $this->timeout['sec'], $this->timeout['usec']);

                return true;
            }

            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function getOption(int $level, int $name)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function setBlocking(bool $blocking)
        {
            return stream_set_blocking($this->sock, $blocking);
        }

        public function bind(string $address, int $port = 0)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function listen(int $backlog = 0)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function accept()
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function connect(string $address, int $port = -1)
        {
            if ($this->domain === AF_INET6 && strpos($address, ':') !== false) {
                $address = '['.$address.']';
            }

            $this->sock = fsockopen($this->protocol.'://'.$address, $port);

            return true;
        }

        public function select(array &$read, array &$write, array &$except, int $tv_sec, int $tv_usec = 0)
        {
            return stream_select($read, $write, $except, $tv_sec, $tv_usec);
        }

        public function read(int $length, int $flags = 0)
        {
            return stream_get_contents($this->sock, $length);
        }

        public function write(string $buffer, int $length = -1)
        {
            return $length === -1 ? fwrite($this->sock, $buffer) : fwrite($this->sock, $buffer, $length);
        }

        public function send(string $data, int $length, int $flags)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function close()
        {
            return fclose($this->sock);
        }

        public function getPeerName(bool $port = true)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function getSockName(bool $port = true)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }
    }
}
