<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Manages connection to telegram servers.
 */
class Connection extends \Volatile
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    public $sock = null;

    public $protocol = null;
    public $ip = null;
    public $port = null;
    public $timeout = null;
    public $parsed = [];
    public $time_delta = 0;
    public $temp_auth_key;
    public $auth_key;
    public $session_id;
    public $session_out_seq_no = 0;
    public $session_in_seq_no = 0;
    public $ipv6 = false;
    public $incoming_messages = [];
    public $outgoing_messages = [];
    public $new_incoming = [];
    public $new_outgoing = [];
    public $max_incoming_id;
    public $max_outgoing_id;

    public function ___construct($ip, $port, $protocol, $timeout, $ipv6)
    {

        // Can use:
        /*
        - tcp_full
        - tcp_abridged
        - tcp_intermediate
        - http
        - https
        - udp
        */
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->ipv6 = $ipv6;
        $this->ip = $ip;
        $this->port = $port;
        switch ($this->protocol) {
            case 'tcp_abridged':
                $this->sock = new \Socket($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                if (!\danog\MadelineProto\Logger::$has_thread) {
                    $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
                    $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
                }
                $this->sock->setBlocking(true);
                if (!$this->sock->connect($ip, $port)) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->write(chr(239));
                break;
            case 'tcp_intermediate':
                $this->sock = new \Socket($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
                $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
                if (!$this->sock->connect($ip, $port)) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                if (!\danog\MadelineProto\Logger::$has_thread) {
                    $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
                    $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
                }
                $this->sock->setBlocking(true);
                $this->write(str_repeat(chr(238), 4));
                break;
            case 'tcp_full':

                $this->sock = new \Socket($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                if (!$this->sock->connect($ip, $port)) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                if (!\danog\MadelineProto\Logger::$has_thread) {
                    $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
                    $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
                }
                $this->sock->setBlocking(true);

                $this->out_seq_no = -1;
                $this->in_seq_no = -1;
                break;
            case 'http':
            case 'https':
                $this->parsed = parse_url($ip);
                $this->sock = new \Socket($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname($this->protocol === 'https' ? 'tls' : 'tcp'));
                if (!$this->sock->connect($this->parsed['host'], $port)) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                if (!\danog\MadelineProto\Logger::$has_thread) {
                    $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
                    $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
                }
                $this->sock->setBlocking(true);
                break;
            case 'udp':
                throw new Exception("Connection: This protocol isn't implemented yet.");
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function __destruct()
    {
        if (\danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
        switch ($this->protocol) {
            case 'tcp_abridged':
            case 'tcp_intermediate':
            case 'tcp_full':
            case 'http':
            case 'https':
                try {
                    unset($this->sock);
                } catch (\danog\MadelineProto\Exception $e) {
                }
                break;
            case 'udp':
                throw new Exception("Connection: This protocol wasn't implemented yet.");
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function close_and_reopen()
    {
        $this->__destruct();
        $this->__construct($this->ip, $this->port, $this->protocol, $this->timeout, $this->ipv6);
    }

    public function __sleep()
    {
        return ['protocol', 'ip', 'port', 'timeout', 'parsed', 'time_delta', 'temp_auth_key', 'auth_key', 'session_id', 'session_out_seq_no', 'session_in_seq_no', 'ipv6', 'incoming_messages', 'outgoing_messages', 'new_incoming', 'new_outgoing', 'max_incoming_id', 'max_outgoing_id'];
    }

    public function __wakeup()
    {
        if (\danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
        $keys = array_keys((array) get_object_vars($this));
        if (count($keys) !== count(array_unique($keys))) {
            throw new Bug74586Exception();
        }
        $this->time_delta = 0;
        //$this->__construct($this->ip, $this->port, $this->protocol, $this->timeout, $this->ipv6);
    }

    public function write($what, $length = null)
    {
        if ($length !== null) {
            $what = substr($what, 0, $length);
        }
        switch ($this->protocol) {
            case 'tcp_abridged':
            case 'tcp_intermediate':
            case 'tcp_full':
            case 'http':
            case 'https':
                $wrote = 0;
                $len = strlen($what);
                if (($wrote += $this->sock->write($what)) !== $len) {
                    while (($wrote += $this->sock->write(substr($what, $wrote))) !== $len);
                }

                return $wrote;
                break;
            case 'udp':
                throw new Exception("Connection: This protocol wasn't implemented yet.");
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function read($length)
    {
        switch ($this->protocol) {
            case 'tcp_abridged':
            case 'tcp_intermediate':
            case 'tcp_full':
            case 'http':
            case 'https':
                $packet = '';
                while (strlen($packet) < $length) {
                    $packet .= $this->sock->read($length - strlen($packet));
                    if ($packet === false || strlen($packet) === 0) {
                        throw new \danog\MadelineProto\NothingInTheSocketException('Nothing in the socket!');
                    }
                }
                if (strlen($packet) !== $length) {
                    $this->close_and_reopen();
                    throw new Exception("WARNING: Wrong length was read (should've read ".($length).', read '.strlen($packet).')!');
                }

                return $packet;
            case 'udp':
                throw new Exception("Connection: This protocol wasn't implemented yet.");
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function read_message()
    {
        switch ($this->protocol) {
            case 'tcp_full':
                $packet_length_data = $this->read(4);
                $packet_length = unpack('V', $packet_length_data)[1];
                $packet = $this->read($packet_length - 4);

                if (strrev(hash('crc32b', $packet_length_data.substr($packet, 0, -4), true)) !== substr($packet, -4)) {
                    throw new Exception('CRC32 was not correct!');
                }
                $this->in_seq_no++;
                $in_seq_no = unpack('V', substr($packet, 0, 4))[1];
                if ($in_seq_no != $this->in_seq_no) {
                    throw new Exception('Incoming seq_no mismatch');
                }

                return substr($packet, 4, $packet_length - 12);
            case 'tcp_intermediate':
                $packet_length_data = $this->read(4);
                $packet_length = unpack('V', $packet_length_data)[1];

                return $this->read($packet_length);
            case 'tcp_abridged':
                $packet_length_data = $this->read(1);
                $packet_length = ord($packet_length_data);
                if ($packet_length < 127) {
                    $packet_length <<= 2;
                } else {
                    $packet_length_data = $this->read(3);
                    $packet_length = unpack('V', $packet_length_data."\0")[1] << 2;
                }

                return $this->read($packet_length);
            case 'http':
            case 'https':
                $headers = [];
                $close = false;
                while (true) {
                    $current_header = '';
                    while (($curchar = $this->read(1)) !== "\n") {
                        $current_header .= $curchar;
                    }
                    $current_header = rtrim($current_header);
                    if ($current_header === '') {
                        break;
                    }
                    if ($current_header === false) {
                        throw new Exception('No data in the socket!');
                    }
                    if (preg_match('|^Content-Length: |i', $current_header)) {
                        $length = (int) preg_replace('|Content-Length: |i', '', $current_header);
                    }
                    if (preg_match('|^Connection: close|i', $current_header)) {
                        $close = true;
                    }
                    $headers[] = $current_header;
                }
                $read = $this->read($length);
                if ($headers[0] !== 'HTTP/1.1 200 OK') {
                    throw new Exception($headers[0]);
                }
                if ($close) {
                    $this->close_and_reopen();
                }

                return $read;
            case 'udp':
                throw new Exception("Connection: This protocol wasn't implemented yet.");
        }
    }

    public function send_message($message)
    {
        switch ($this->protocol) {
            case 'tcp_full':
                $this->out_seq_no++;
                $step1 = pack('VV', (strlen($message) + 12), $this->out_seq_no).$message;
                $step2 = $step1.strrev(hash('crc32b', $step1, true));
                $this->write($step2);
                break;
            case 'tcp_intermediate':
                $this->write(pack('V', strlen($message)).$message);
                break;
            case 'tcp_abridged':
                $len = strlen($message) / 4;
                if ($len < 127) {
                    $step1 = chr($len).$message;
                } else {
                    $step1 = chr(127).substr(pack('V', $len), 0, 3).$message;
                }
                $this->write($step1);
                break;
            case 'http':
            case 'https':
                $this->write('POST '.$this->parsed['path']." HTTP/1.1\r\nHost: ".$this->parsed['host']."\r\nContent-Type: application/x-www-form-urlencoded\r\nConnection: keep-alive\r\nContent-Length: ".strlen($message)."\r\n\r\n".$message);
                break;

            case 'udp':
                throw new Exception("Connection: This protocol wasn't implemented yet.");
            default:
                break;
        }
    }
}
