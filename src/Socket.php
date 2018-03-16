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
    class FSocket
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
            $this->protocol = $protocol === PHP_INT_MAX ? 'tls' : ($protocol === PHP_INT_MAX - 1 ? 'tcp' : getprotobynumber($protocol));
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
            $errno = 0;
            $errstr = '';
            $this->sock = fsockopen($this->protocol.'://'.$address, $port, $errno, $errstr, $this->timeout['sec'] + ($this->timeout['usec'] / 1000000));
            stream_set_timeout($this->sock, $this->timeout['sec'], $this->timeout['usec']);

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
            fclose($this->sock);
            $this->sock = null;
        }

        public function getPeerName(bool $port = true)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function getSockName(bool $port = true)
        {
            throw new \danog\MadelineProto\Exception('Not supported');
        }

        public function setExtra(array $extra = [])
        {
        }

        public function getProxyHeaders()
        {
            return '';
        }
    }

if (!extension_loaded('pthreads')) {
    if (extension_loaded('sockets')) {
        class SocketBase
        {
            private $sock;

            public function __construct($sock)
            {
                $this->sock = $sock;
            }

            public function __destruct()
            {
                socket_close($this->sock);
                unset($this->sock);
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
                if ($socket = socket_accept($this->sock)) {
                    return new self($socket);
                } else {
                    return $socket;
                }
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
                socket_close($this->sock);
                $this->sock = null;
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

            public function getProxyHeaders()
            {
                return '';
            }
        }
        class Socket extends SocketBase
        {
            public function __construct(int $domain, int $type, int $protocol)
            {
                parent::__construct(socket_create($domain, $type, $protocol));
            }
        }
    } else {
        define('AF_INET', 0);
        define('AF_INET6', 1);
        define('SOCK_STREAM', 2);
        define('SOL_SOCKET', 3);
        define('SO_RCVTIMEO', 4);
        define('SO_SNDTIMEO', 5);

        class Socket extends FSocket
        {
        }
    }
}
