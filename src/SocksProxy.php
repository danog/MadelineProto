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

class SocksProxy implements \danog\MadelineProto\Proxy
{
    private $domain;
    private $type;
    private $protocol;
    private $extra;
    private $sock;

    public function __construct(int $domain, int $type, int $protocol)
    {
        if (!in_array($domain, [AF_INET, AF_INET6])) {
            throw new \danog\MadelineProto\Exception('Wrong protocol family provided');
        }
        if (!in_array($type, [SOCK_STREAM, SOCK_DGRAM])) {
            throw new \danog\MadelineProto\Exception('Wrong connection type provided');
        }
        if (!in_array($protocol, [getprotobyname('tcp'), getprotobyname('udp'), PHP_INT_MAX])) {
            throw new \danog\MadelineProto\Exception('Wrong protocol provided');
        }
        $this->domain = $domain;
        $this->type = $type;
        $this->protocol = $protocol;
    }

    public function setExtra(array $extra = [])
    {
        $this->extra = $extra;
        $name = $this->protocol === PHP_INT_MAX ? '\\FSocket' : '\\Socket';
        $this->sock = new $name(strlen(@inet_pton($this->extra['address'])) !== 4 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, $this->protocol);
    }

    public function setOption(int $level, int $name, $value)
    {
        return $this->sock->setOption($level, $name, $value);
    }

    public function getOption(int $level, int $name)
    {
        return $this->sock->getOption($level, $name);
    }

    public function setBlocking(bool $blocking)
    {
        return $this->sock->setBlocking($blocking);
    }

    public function bind(string $address, int $port = 0)
    {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function listen(int $backlog = 0)
    {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function accept()
    {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function select(array &$read, array &$write, array &$except, int $tv_sec, int $tv_usec = 0)
    {
        return $this->sock->select($read, $write, $except, $tv_sec, $tv_usec);
    }

    public function connect(string $address, int $port = 0)
    {
        $this->sock->connect($this->extra['address'], $this->extra['port']);

        $methods = chr(0);
        if (isset($this->extra['username']) && isset($this->extra['password'])) {
            $methods .= chr(2);
        }
        $this->sock->write(chr(5).chr(strlen($methods)).$methods);

        $version = ord($this->sock->read(1));
        $method = ord($this->sock->read(1));

        if ($version !== 5) {
            throw new \danog\MadelineProto\Exception("Wrong SOCKS5 version: $version");
        }
        if ($method === 2) {
            $this->sock->write(chr(1).chr(strlen($this->extra['username'])).$this->extra['username'].chr(strlen($this->extra['password'])).$this->extra['password']);

            $version = ord($this->sock->read(1));
            if ($version !== 1) {
                throw new \danog\MadelineProto\Exception("Wrong authorized SOCKS version: $version");
            }

            $result = ord($this->sock->read(1));
            if ($result !== 0) {
                throw new \danog\MadelineProto\Exception("Wrong authorization status: $version");
            }
        } elseif ($method !== 0) {
            throw new \danog\MadelineProto\Exception("Wrong method: $method");
        }
        $payload = pack('C3', 0x05, 0x01, 0x00);

        try {
            $ip = inet_pton($address);
            $payload .= pack('C1', strlen($ip) === 4 ? 0x01 : 0x04).$ip;
        } catch (\danog\MadelineProto\Exception $e) {
            $payload .= pack('C2', 0x03, strlen($address)).$address;
        }
        $payload .= pack('n', $port);
        $this->sock->write($payload);

        $version = ord($this->sock->read(1));
        if ($version !== 5) {
            throw new \danog\MadelineProto\Exception("Wrong SOCKS5 version: $version");
        }

        $rep = ord($this->sock->read(1));
        if ($rep !== 0) {
            throw new \danog\MadelineProto\Exception("Wrong SOCKS5 rep: $rep");
        }

        $rsv = ord($this->sock->read(1));
        if ($rsv !== 0) {
            throw new \danog\MadelineProto\Exception("Wrong socks5 final RSV: $rsv");
        }
        switch (ord($this->sock->read(1))) {
            case 1:
                $ip = inet_ntop($this->sock->read(4));
                break;
            case 4:
                $ip = inet_ntop($this->sock->read(16));
                break;
            case 3:
                $ip = $this->sock->read(ord($this->sock->read(1)));
                break;
        }
        $port = unpack('n', $this->sock->read(2))[1];
        \danog\MadelineProto\Logger::log(['Connected to '.$ip.':'.$port.' via socks5']);

        return true;
    }

    public function read(int $length, int $flags = 0)
    {
        return $this->sock->read($length, $flags);
    }

    public function write(string $buffer, int $length = -1)
    {
        return $this->sock->write($buffer, $length);
    }

    public function send(string $data, int $length, int $flags)
    {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function close()
    {
        $this->sock->close();
    }

    public function getPeerName(bool $port = true)
    {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function getSockName(bool $port = true)
    {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function getProxyHeaders()
    {
        return '';
    }

    public function getResource()
    {
        return $this->sock->getResource();
    }
}
