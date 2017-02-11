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
class Connection
{
    use \danog\MadelineProto\Tools;
    public $sock = null;

    public $protocol = null;
    public $ip = null;
    public $port = null;
    public $timeout = null;

    public $time_delta = 0;
    public $temp_auth_key;
    public $auth_key;
    public $session_id;
    public $seq_no = 0;
    public $authorized = false;
    public $authorization = null;
    public $login_temp_status = 'none';

    public $incoming_messages = [];
    public $outgoing_messages = [];
    public $new_incoming = [];
    public $new_outgoing = [];

    public function __construct($ip, $port, $protocol, $timeout)
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
        $this->ip = $ip;
        $this->port = $port;
        switch ($this->protocol) {
            case 'tcp_abridged':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                stream_set_timeout($this->sock, $timeout);
                if (!(get_resource_type($this->sock) === 'file' || get_resource_type($this->sock) === 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->write(chr(239));
                break;
            case 'tcp_intermediate':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                stream_set_timeout($this->sock, $timeout);
                if (!(get_resource_type($this->sock) === 'file' || get_resource_type($this->sock) === 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->write(str_repeat(chr(238), 4));
                break;
            case 'tcp_full':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                stream_set_timeout($this->sock, $timeout);
                if (!(get_resource_type($this->sock) === 'file' || get_resource_type($this->sock) === 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->out_seq_no = -1;
                $this->in_seq_no = -1;
                break;
            case 'http':
            case 'https':
                $this->parsed = parse_url($ip);
                $this->sock = fsockopen(($this->protocol === 'https' ? 'tls' : 'tcp').'://'.$this->parsed['host'].':'.$port);
                stream_set_timeout($this->sock, $timeout);
                if (!(get_resource_type($this->sock) === 'file' || get_resource_type($this->sock) === 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
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
        switch ($this->protocol) {
            case 'tcp_abridged':
            case 'tcp_intermediate':
            case 'tcp_full':
            case 'http':
            case 'https':
                fclose($this->sock);
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
        $this->__construct($this->ip, $this->port, $this->protocol, $this->timeout);
    }

    public function __wakeup()
    {
        $this->__construct($this->ip, $this->port, $this->protocol, $this->timeout);
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
                if (!(get_resource_type($this->sock) === 'file' || get_resource_type($this->sock) === 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                if (($wrote = fwrite($this->sock, $what)) !== strlen($what)) {
                    throw new \danog\MadelineProto\Exception("WARNING: Wrong length was read (should've written ".strlen($what).', wrote '.$wrote.')!');
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
                if (!(get_resource_type($this->sock) === 'file' || get_resource_type($this->sock) === 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $packet = stream_get_contents($this->sock, $length);
                if ($packet === false) {
                    throw new NothingInTheSocketException('Nothing in the socket!');
                }
                if (strlen($packet) != $length) {
                    throw new \danog\MadelineProto\Exception("WARNING: Wrong length was read (should've read ".($length).', read '.strlen($packet).')!');
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
                $packet_length = \danog\PHP\Struct::unpack('<I', $packet_length_data)[0];
                $packet = $this->read($packet_length - 4);
                if (strrev(hash('crc32b', $packet_length_data.substr($packet, 0, -4), true)) !== substr($packet, -4)) {
                    throw new Exception('CRC32 was not correct!');
                }
                $this->in_seq_no++;
                $in_seq_no = \danog\PHP\Struct::unpack('<I', substr($packet, 0, 4))[0];
                if ($in_seq_no != $this->in_seq_no) {
                    throw new Exception('Incoming seq_no mismatch');
                }

                return substr($packet, 4, $packet_length - 12);
            case 'tcp_intermediate':
                $packet_length_data = $this->read(4);
                $packet_length = \danog\PHP\Struct::unpack('<I', $packet_length_data)[0];

                return $this->read($packet_length);
            case 'tcp_abridged':
                $packet_length_data = $this->read(1);
                $packet_length = ord($packet_length_data);
                if ($packet_length < 127) {
                    $packet_length <<= 2;
                } else {
                    $packet_length_data = $this->read(3);
                    $packet_length = \danog\PHP\Struct::unpack('<I', $packet_length_data.pack('x'))[0] << 2;
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
                        $length = preg_replace('|Content-Length: |i', '', $current_header);
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
                $step1 = \danog\PHP\Struct::pack('<II', (strlen($message) + 12), $this->out_seq_no).$message;
                $step2 = $step1.strrev(hash('crc32b', $step1, true));
                $this->write($step2);
                break;
            case 'tcp_intermediate':
                $this->write(\danog\PHP\Struct::pack('<I', strlen($message)).$message);
                break;
            case 'tcp_abridged':
                $len = strlen($message) / 4;
                if ($len < 127) {
                    $step1 = chr($len).$message;
                } else {
                    $step1 = chr(127).substr(\danog\PHP\Struct::pack('<I', $len), 0, 3).$message;
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
