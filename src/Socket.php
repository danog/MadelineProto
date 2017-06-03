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
    class Socket
    {
        private $sock;

        public function __construct(int $domain, int $type, int $protocol)
        {
            $this->sock = socket_create($domain, $type, $protocol);
        }

        public function setOption(int $level, int $name, $value)
        {
            if (in_array($name, [\SO_RCVTIMEO, \SO_SNDTIMEO])) {
                $value = ['sec' => (int) $value, 'usec' => (int) (($value - (int) $value) * 1000000)];
            }

            return socket_set_option($this->sock, $level, $name, $value);
        }

        public function getOption(int $level, int $name)
        {
            return socket_get_option($this->sock, $level, $name);
        }

        public function setBlocking(bool $blocking)
        {
            if ($blocking) {
                return socket_set_block($this->sock);
            }

            return socket_set_nonblock($this->sock);
        }

        public function bind(string $address, int $port = 0)
        {
            return socket_bind($this->sock, $address, $port);
        }

        public function listen(int $backlog = 0)
        {
            return socket_listen($this->sock, $backlog);
        }

        public function accept()
        {
            return socket_accept($this->sock);
        }

        public function connect(string $address, int $port = 0)
        {
            return socket_connect($this->sock, $address, $port);
        }

        public function select(array &$read, array &$write, array &$except, int $tv_sec, int $tv_usec = 0)
        {
            return socket_select($read, $write, $except, $tv_sec, $tv_usec);
        }

        public function read(int $length, int $flags = 0)
        {
            return socket_read($this->sock, $length, $flags);
        }

        public function write(string $buffer, int $length = -1)
        {
            return $length === -1 ? socket_write($this->sock, $buffer) : socket_write($this->sock, $buffer, $Length);
        }

        public function send(string $data, int $length, int $flags)
        {
            return socket_send($data, $length, $flags);
        }

        public function close()
        {
            return socket_close($this->sock);
        }

        public function getPeerName(bool $port = true)
        {
            $address = '';
            $port = 0;
            $port ? socket_getpeername($this->sock, $address, $ip) : socket_getpeername($this->sock, $address);

            return $port ? ['host' => $address, 'port' => $port] : ['host' => $address];
        }

        public function getSockName(bool $port = true)
        {
            $address = '';
            $port = 0;
            $port ? socket_getsockname($this->sock, $address, $ip) : socket_getsockname($this->sock, $address);

            return $port ? ['host' => $address, 'port' => $port] : ['host' => $address];
        }
    }
}
