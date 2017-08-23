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
    public $proxy = '\Socket';
    public $extra = [];
    public $obfuscated = [];

    public $call_queue = [];

    public $i = [];
    /*    public function __get($name) {
            echo "GETTING $name\n";
            if (isset($this->i[$name]) && $this->{$name} === null) var_dump($this->i[$name]);
            if ($this->{$name} instanceof \Volatile) $this->i[$name] = debug_backtrace(0);
    var_dump(is_null($this->{$name}));
            return $this->{$name};
        }*/

    public function ___construct($proxy, $extra, $ip, $port, $protocol, $timeout, $ipv6)
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
        $this->proxy = $proxy;
        $this->extra = $extra;

        if (($has_proxy = $proxy !== '\Socket') && !isset(class_implements($proxy)['\danog\MadelineProto\Proxy'])) {
            throw new \danog\MadelineProto\Exception('Invalid proxy class provided!');
        }
        switch ($this->protocol) {
            case 'tcp_abridged':
                $this->sock = new $proxy($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                if ($has_proxy && $this->extra !== []) {
                    $this->sock->setExtra($this->extra);
                }
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
                $this->sock = new $proxy($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                if ($has_proxy && $this->extra !== []) {
                    $this->sock->setExtra($this->extra);
                }
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
                $this->sock = new $proxy($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                if ($has_proxy && $this->extra !== []) {
                    $this->sock->setExtra($this->extra);
                }
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
            case 'obfuscated2':
                $this->sock = new $proxy($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname('tcp'));
                if ($has_proxy && $this->extra !== []) {
                    $this->sock->setExtra($this->extra);
                }
                if (!$this->sock->connect($ip, $port)) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                if (!\danog\MadelineProto\Logger::$has_thread) {
                    $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
                    $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
                }
                $this->sock->setBlocking(true);
                do {
                    $random = $this->random(64);
                } while (in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(chr(238), 4)]) || $random[0] === chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");
                $random[56] = $random[57] = $random[58] = $random[59] = chr(0xef);
                $reversed = strrev(substr($random, 8, 48));

                $this->obfuscated = ['encryption' => new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_CTR), 'decryption' => new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_CTR)];
                $this->obfuscated['encryption']->enableContinuousBuffer();
                $this->obfuscated['decryption']->enableContinuousBuffer();

                $this->obfuscated['encryption']->setKey(substr($random, 8, 32));
                $this->obfuscated['encryption']->setIV(substr($random, 40, 16));

                $this->obfuscated['decryption']->setKey(substr($reversed, 0, 32));
                $this->obfuscated['decryption']->setIV(substr($reversed, 32, 16));
                $random = substr_replace(
                    $random,
                    substr(
                        $this->obfuscated['encryption']->encrypt($random),
                        56,
                        8
                    ),
                    56,
                    8
                );
                $wrote = 0;
                if (($wrote += $this->sock->write($random)) !== 64) {
                    while (($wrote += $this->sock->write(substr($what, $wrote))) !== 64);
                }
                break;

            case 'http':
            case 'https':
                $this->parsed = parse_url($ip);
                $this->sock = new $proxy($ipv6 ? \AF_INET6 : \AF_INET, \SOCK_STREAM, getprotobyname($this->protocol === 'https' ? 'tls' : 'tcp'));
                if ($has_proxy && $this->extra !== []) {
                    $this->sock->setExtra($this->extra);
                }
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
            case 'obfuscated2':
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
        $this->__construct($this->proxy, $this->extra, $this->ip, $this->port, $this->protocol, $this->timeout, $this->ipv6);
    }

    public function __sleep()
    {
        return ['proxy', 'extra', 'protocol', 'ip', 'port', 'timeout', 'parsed', 'time_delta', 'temp_auth_key', 'auth_key', 'session_id', 'session_out_seq_no', 'session_in_seq_no', 'ipv6', 'incoming_messages', 'outgoing_messages', 'new_incoming', 'new_outgoing', 'max_incoming_id', 'max_outgoing_id', 'obfuscated'];
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
        } else {
            $length = strlen($what);
        }

        switch ($this->protocol) {
            case 'obfuscated2':
                $what = $this->obfuscated['encryption']->encrypt($what);
            case 'tcp_abridged':
            case 'tcp_intermediate':
            case 'tcp_full':
            case 'http':
            case 'https':
                $wrote = 0;
                if (($wrote += $this->sock->write($what)) !== $length) {
                    while (($wrote += $this->sock->write(substr($what, $wrote))) !== $length);
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
            case 'obfuscated2':
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

                return $this->obfuscated['decryption']->encrypt($packet);

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
                return $this->read(unpack('V', $this->read(4))[1]);

            case 'obfuscated2':
            case 'tcp_abridged':
                $packet_length = ord($this->read(1));

                return $this->read($packet_length < 127 ? $packet_length << 2 : unpack('V', $this->read(3)."\0")[1] << 2);

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
            case 'obfuscated2':
            case 'tcp_abridged':
                $len = strlen($message) / 4;
                if ($len < 127) {
                    $message = chr($len).$message;
                } else {
                    $message = chr(127).substr(pack('V', $len), 0, 3).$message;
                }
                $this->write($message);
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
