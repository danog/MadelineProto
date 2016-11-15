<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Manages connection to telegram servers.
 */
class Connection extends Tools
{
    public $sock = null;
    public $protocol = null;
    private $_delta = 0;

    public function __construct($ip, $port, $protocol = 'tcp_full')
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
        switch ($this->protocol) {
            case 'tcp_abridged':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                stream_set_timeout($this->sock, 5);
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->write($this->string2bin('\xef'));
                break;
            case 'tcp_intermediate':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                stream_set_timeout($this->sock, 5);
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->write($this->string2bin('\xee\xee\xee\xee'));
                break;
            case 'tcp_full':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                stream_set_timeout($this->sock, 5);
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                $this->out_seq_no = -1;
                $this->in_seq_no = -1;
                break;
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
                fclose($this->sock);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function set_time_delta($delta) {
        $this->_delta = $delta;
    }
    public function get_time_delta() {
        return $this->_delta;
    }
    /**
     * Function to get hex crc32.
     *
     * @param $data Data to encode.
     */
    public function newcrc32($data)
    {
        return hexdec(hash('crc32b', $data));
    }

    public function write($what, $length = null)
    {
        if ($length != null) {
            $what = substr($what, 0, $length);
        }
        switch ($this->protocol) {
            case 'tcp_abridged':
            case 'tcp_intermediate':
            case 'tcp_full':
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }

                return fwrite($this->sock, $what);
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
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }

                return fread($this->sock, $length);
                break;
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
                if (strlen($packet_length_data) < 4) {
                    throw new Exception('Nothing in the socket!');
                }
                $packet_length = \danog\PHP\Struct::unpack('<I', $packet_length_data)[0];
                $packet = $this->read($packet_length - 4);
                if (!($this->newcrc32($packet_length_data.substr($packet, 0, -4)) == \danog\PHP\Struct::unpack('<I', substr($packet, -4))[0])) {
                    throw new Exception('CRC32 was not correct!');
                }
                $this->in_seq_no++;
                $in_seq_no = \danog\PHP\Struct::unpack('<I', substr($packet, 0, 4))[0];
                if ($in_seq_no != $this->in_seq_no) {
                    throw new Exception('Incoming seq_no mismatch');
                }
                $payload = $this->fopen_and_write('php://memory', 'rw+b', substr($packet, 4, $packet_length - 12));
                break;
            case 'tcp_intermediate':
                $packet_length_data = $this->sock->read(4);
                if (strlen($packet_length_data) < 4) {
                    throw new Exception('Nothing in the socket!');
                }
                $packet_length = \danog\PHP\Struct::unpack('<I', $packet_length_data)[0];
                $packet = $this->sock->read($packet_length);
                $payload = $this->fopen_and_write('php://memory', 'rw+b', $packet);
                break;
            case 'tcp_abridged':
                $packet_length_data = $this->sock->read(1);
                if (strlen($packet_length_data) < 1) {
                    throw new Exception('Nothing in the socket!');
                }
                $packet_length = ord($packet_length_data);
                if ($packet_length < 127) {
                    $packet_length <<= 2;
                } else {
                    $packet_length_data = $this->sock->read(3);
                    $packet_length = \danog\PHP\Struct::unpack('<I', $packet_length_data.pack('x'))[0] << 2;
                }
                $packet = $this->sock->read($packet_length);
                $payload = $this->fopen_and_write('php://memory', 'rw+b', $packet);
                break;
        }

        return $payload;
    }

    public function send_message($message)
    {
        switch ($this->protocol) {
            case 'tcp_full':
                $this->out_seq_no++;
                $step1 = \danog\PHP\Struct::pack('<II', (strlen($message) + 12), $this->out_seq_no).$message;
                $step2 = $step1.\danog\PHP\Struct::pack('<I', $this->newcrc32($step1));
                $this->write($step2);
                break;
            case 'tcp_intermediate':
                $step1 = \danog\PHP\Struct::pack('<I', strlen($message)).$message;
                $this->write($step1);
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
            default:
                break;
        }
    }
}
