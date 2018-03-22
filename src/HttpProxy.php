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


class HttpProxy implements \danog\MadelineProto\Proxy
{
    private $domain;
    private $type;
    private $protocol;
    private $extra;
    private $sock;
    public function __construct($domain, $type, $protocol) {
        if (!in_array($domain, [AF_INET, AF_INET6])) {
            throw new \danog\MadelineProto\Exception('Wrong protocol family provided');
        }
        if (!in_array($type, [SOCK_STREAM])) {
            throw new \danog\MadelineProto\Exception('Wrong connection type provided');
        }
        if (!in_array($protocol, [getprotobyname('tcp'), PHP_INT_MAX])) {
            throw new \danog\MadelineProto\Exception('Wrong protocol provided');
        }
        $this->domain = $domain;
        $this->type = $type;
        $this->protocol = $protocol;
    }
    public function setExtra(array $extra = []) {
        $this->extra = $extra;
        $name = $this->protocol === PHP_INT_MAX ? '\\FSocket' : '\\Socket';
        $this->sock = new $name(strlen(@inet_pton($this->extra['address'])) !== 4 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, $this->protocol);
    }
    public function setOption($level, $name, $value) {
        return $this->sock->setOption($level, $name, $value);
    }

    public function getOption($level, $name) {
        return $this->sock->getOption($level, $name);
    }

    public function setBlocking($blocking) {
        return $this->sock->setBlocking($blocking);
    }

    public function bind($address, $port = 0) {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function listen($backlog = 0) {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }
    public function accept() {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }


    public function select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = 0) {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }
    public function connect($address, $port = 0) {
        $this->sock->connect($this->extra['address'], $this->extra['port']);
        
        try {
            if (strlen(inet_pton($address)) !== 4) {
                $address = '['.$address.']';
            }
        } catch (\danog\MadelineProto\Exception $e) {
        }
        $this->sock->write("CONNECT $address:$port HTTP/1.1\r\nHost: $address:$port\r\n\r\n");
        $response = $this->read_http_payload();
        if ($response['code'] !== 200) {
            \danog\MadelineProto\Logger::log(trim($response['body']));
            throw new \danog\MadelineProto\Exception($response['description'], $response['code']);
        }
        \danog\MadelineProto\Logger::log('Connected to '.$address.':'.$port.' via http');
        return true;
    }
    private function http_read($length) {
        $packet = '';
        while (strlen($packet) < $length) {
            $packet .= $this->sock->read($length - strlen($packet));
            if ($packet === false || strlen($packet) === 0) {
                throw new \danog\MadelineProto\NothingInTheSocketException(\danog\MadelineProto\Lang::$current_lang['nothing_in_socket']);
            }
        }
        return $packet;
    }
    public function read_http_line()
    {
        $line = '';
        while (($curchar = $this->http_read(1)) !== "\n") {
            $line .= $curchar;
        }

        return rtrim($line);
    }

    public function read_http_payload()
    {
        $header = explode(' ', $this->read_http_line(), 3);
        $protocol = $header[0];
        $code = (int) $header[1];
        $description = $header[2];
        $headers = [];
        while (strlen($current_header = $this->read_http_line())) {
            $current_header = explode(':', $current_header, 2);
            $headers[strtolower($current_header[0])] = trim($current_header[1]);
        }

        $read = '';
        if (isset($headers['content-length'])) {
            $read = $this->http_read((int) $headers['content-length']);
        }/* elseif (isset($headers['transfer-encoding']) && $headers['transfer-encoding'] === 'chunked') {
            do {
                $length = hexdec($this->read_http_line());
                $read .= $this->http_read($length);
                $this->read_http_line();
            } while ($length);
        }*/

        return ['protocol' => $protocol, 'code' => $code, 'description' => $description, 'body' => $read, 'headers' => $headers];
    }
    
    public function read($length, $flags = 0) {
        $read = $this->sock->read($length, $flags);
        if ($read === 0) {
            throw new \danog\MadelineProto\Exception('pls reconnect');
        }
        return $read;
    }

    public function write($buffer, $length = -1) {
        return $this->sock->write($buffer, $length);
    }

    public function send($data, $length, $flags) {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function close() {
        $this->sock->close();
    }

    public function getPeerName($port = true) {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }

    public function getSockName($port = true) {
        throw new \danog\MadelineProto\Exception('Not Implemented');
    }
    public function getProxyHeaders() {
    
    }
}
