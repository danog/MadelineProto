<?php
/**
 * Connection module
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use function \Amp\call;
use function \Amp\Promise\wait;
use function \Amp\Socket\connect;
use \Amp\Socket\ClientConnectContext;
use \Amp\Deferred;
use \Amp\Promise;

/**
 * Connection class
 *
 * Manages connection to Telegram datacenters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Connection
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    const API_ENDPOINT = 0;
    const VOIP_UDP_REFLECTOR_ENDPOINT = 1;
    const VOIP_TCP_REFLECTOR_ENDPOINT = 2;
    const VOIP_UDP_P2P_ENDPOINT = 3;
    const VOIP_UDP_LAN_ENDPOINT = 4;

    public $sock = null;
    public $protocol = null;
    public $ip = null;
    public $port = null;
    public $timeout = null;
    public $parsed = [];
    public $time_delta = 0;
    public $type = 0;
    public $peer_tag;
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
    public $pending_outgoing = [];
    public $pending_outgoing_key = 0;
    public $max_incoming_id;
    public $max_outgoing_id;
    public $proxy = '\\Socket';
    public $extra = [];
    public $obfuscated = [];
    public $authorized = false;
    public $call_queue = [];
    public $ack_queue = [];
    public $i = [];
    public $last_recv = 0;
    public $last_http_wait = 0;

    public $packet_left = 0;

    /**
     * Connect function
     *
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters
     *
     * @param string $proxy    Proxy class name
     * @param all    $extra    Proxy parameters
     * @param string $ip       IP address/URI of DC
     * @param int    $port     Port to connect to
     * @param string $protocol Protocol name
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function connect($proxy, $extra, $ip, $port, $protocol): Promise
    {
        return call(
            function () use ($proxy, $extra, $ip, $port, $protocol) {
                \danog\MadelineProto\Logger::log('Opening...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                if ($proxy === '\\MTProxySocket') {
                    $proxy = '\\Socket';
                    $protocol = 'obfuscated2';
                }
                if (strpos($protocol, 'https') === 0 && $proxy === '\\Socket') {
                    $proxy = '\\FSocket';
                }

                $this->protocol = $protocol;
                $this->timeout = $timeout;
                $this->ipv6 = $ipv6;
                $this->ip = $ip;
                $this->port = $port;
                $this->proxy = $proxy;
                $this->extra = $extra;
                /** @var $context \Amp\ClientConnectContext */
                $context = new ClientConnectContext();
                $context = $context->withConnectTimeout($timeout);
                $context = $context->withMaxAttempts(1);
                if (($has_proxy = !in_array($proxy, ['\\MTProxySocket', '\\Socket', '\\FSocket'])) && !isset(class_implements($proxy)['danog\\MadelineProto\\Proxy'])) {
                    throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['proxy_class_invalid']);
                }
                switch ($protocol) {
                    case 'tcp_abridged':
                        $this->sock = yield connect("tcp://" . $ip . ":" . $port, $context);
                        yield $this->write(chr(239));
                        break;
                    case 'tcp_intermediate':
                        $this->sock = yield connect("tcp://" . $ip . ":" . $port, $context);
                        yield $this->write(str_repeat(chr(238), 4));
                        break;
                    case 'tcp_full':
                        $this->sock = yield connect("tcp://" . $ip . ":" . $port, $context);
                        $this->out_seq_no = -1;
                        $this->in_seq_no = -1;
                        break;
                    case 'obfuscated2':
                        $this->sock = yield connect("tcp://" . $ip . ":" . $port, $context);
                        do {
                            $random = $this->random(64);
                        } while (in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(chr(238), 4)]) || $random[0] === chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");
                        $random[56] = $random[57] = $random[58] = $random[59] = chr(0xef);

                        $reversed = strrev(substr($random, 8, 48));

                        $encrypt = new \phpseclib\Crypt\AES('ctr');
                        $encrypt->enableContinuousBuffer();
                        $encrypt->setKey(substr($random, 8, 32));
                        $encrypt->setIV(substr($random, 40, 16));

                        $decrypt = new \phpseclib\Crypt\AES('ctr');
                        $decrypt->enableContinuousBuffer();
                        $decrypt->setKey(substr($reversed, 0, 32));
                        $decrypt->setIV(substr($reversed, 32, 16));

                        $random = substr_replace($random, substr(@$encrypt->encrypt($random), 56, 8), 56, 8);

                        yield $this->sock->write($random);

                        $this->sock = new \danog\MadelineProto\Streams\ObfuscatedStream($encrypt, $decrypt, $this->sock)

                        break;
                    case 'http':
                    case 'https':
                        $this->parsed = parse_url($ip);
                        if ($this->parsed['host'][0] === '[') {
                            $this->parsed['host'] = substr($this->parsed['host'], 1, -1);
                        }
                        $method = $protocol === 'https' ? 'cryptoConnect' : 'connect';
                        $this->sock = yield $method("tcp://" . $ip . ":" . $port, $context);
                        break;
                    case 'udp':
                        throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_not_implemented']);
                    default:
                        throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);
                }
            }
        );
    }

    /**
     * Disconnect function
     *
     * Disconnects from a telegram DC
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function disconnect(): Promise
    {
        return call(
            function () {
                try {
                    yield $this->sock->end();
                    unset($this->sock);
                } catch (\danog\MadelineProto\Exception $e) {
                    \danog\MadelineProto\Logger::log("Exception while closing socket: " . $e);
                }
                \danog\MadelineProto\Logger::log("Closed!");
            }
        );
    }

    /**
     * Disconnect function
     *
     * Disconnects from a telegram DC
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function close_and_reopen()
    {
        return call(
            function () {
                yield $this->disconnect();
                yield $this->connect($this->proxy, $this->extra, $this->ip, $this->port, $this->protocol);
            }
        );
    }

    /**
     * Destruct function
     *
     * @internal
     *
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }
    /**
     * Sleep function
     *
     * @internal
     *
     * @return array
     */
    public function __sleep()
    {
        return ['proxy', 'extra', 'protocol', 'ip', 'port', 'timeout', 'parsed', 'time_delta', 'peer_tag', 'temp_auth_key', 'auth_key', 'session_id', 'session_out_seq_no', 'session_in_seq_no', 'ipv6', 'max_incoming_id', 'max_outgoing_id', 'obfuscated', 'authorized', 'ack_queue'];
    }

    /**
     * Wakeup function
     *
     * @internal
     *
     * @return void
     */
    public function __wakeup()
    {
        $keys = array_keys((array) get_object_vars($this));
        if (count($keys) !== count(array_unique($keys))) {
            throw new Bug74586Exception();
        }
        $this->time_delta = 0;
        wait($this->connect());
    }

    /**
     * Read payload chunk
     *
     * @param integer $length Size of chunk to read
     *
     * @return Amp\Promise
     */
    public function read_message_chunk($length): Promise
    {
        if ($this->packet_left) {
            $deferred = new Deferred();
            $promise = $this->read($length);
            $promise->onResolve(
                function ($error, $result) use ($deferred, $length) {
                    if ($error) {
                        return $deferred->fail($error);
                    }
                    $this->packet_left -= $length;
                    $deferred->resolve($result);
                }
            );
            return $deferred->promise();
        }
        return call(
            function () use ($length) {
                switch ($this->protocol) {
                    case 'tcp_full':
                        $packet_length_data = yield $this->read(4);
                        $packet_length = unpack('V', $packet_length_data)[1];
                        $packet = yield $this->read($packet_length - 4);
                        if (strrev(hash('crc32b', $packet_length_data . substr($packet, 0, -4), true)) !== substr($packet, -4)) {
                            throw new Exception('CRC32 was not correct!');
                        }
                        $this->in_seq_no++;
                        $in_seq_no = unpack('V', substr($packet, 0, 4))[1];
                        if ($in_seq_no != $this->in_seq_no) {
                            throw new Exception('Incoming seq_no mismatch');
                        }

                        return substr($packet, 4, $packet_length - 12);
                    case 'tcp_intermediate':
                        return yield $this->read(unpack('V', yield $this->read(4))[1]);
                    case 'obfuscated2':
                    case 'tcp_abridged':
                        $packet_length = ord(yield $this->read(1));

                        return yield $this->read($packet_length < 127 ? $packet_length << 2 : unpack('V', yield $this->read(3) . "\0")[1] << 2);
                    case 'http':
                    case 'https':
                        $response = yield $this->read_http_payload();
                        $close = $response['protocol'] === 'HTTP/1.0' || $response['code'] !== 200;
                        if (isset($response['headers']['connection'])) {
                            $close = strtolower($response['headers']['connection']) === 'close';
                        }
                        if ($close) {
                            $this->close_and_reopen();
                        }

                        if ($response['code'] !== 200) {
                            Logger::log($response['body']);

                            return $this->pack_signed_int(-$response['code']);
                        }

                        return $response['body'];
                    case 'udp':
                        throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_not_implemented']);
                    default:
                        throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);
                }
            }
        );
    }

    public function send_message($message): Promise
    {
        switch ($this->protocol) {
            case 'tcp_full':
                $this->out_seq_no++;
                $step1 = pack('VV', strlen($message) + 12, $this->out_seq_no) . $message;
                $step2 = $step1 . strrev(hash('crc32b', $step1, true));
                return $this->write($step2);
            case 'tcp_intermediate':
                return $this->write(pack('V', strlen($message)) . $message);
            case 'obfuscated2':
            case 'tcp_abridged':
                $len = strlen($message) / 4;
                if ($len < 127) {
                    $message = chr($len) . $message;
                } else {
                    $message = chr(127) . substr(pack('V', $len), 0, 3) . $message;
                }
                return $this->write($message);
            case 'http':
            case 'https':
                return $this->write('POST ' . $this->parsed['path'] . " HTTP/1.1\r\nHost: " . $this->parsed['host'] . ':' . $this->port . "\r\n" . $this->sock->getProxyHeaders() . "Content-Type: application/x-www-form-urlencoded\r\nConnection: keep-alive\r\nKeep-Alive: timeout=100000, max=10000000\r\nContent-Length: " . strlen($message) . "\r\n\r\n" . $message);
                break;
            case 'udp':
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_not_implemented']);
            default:
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);
        }
    }

    public function read_http_line(): Promise
    {
        return call(
            function () {
                $line = $lastchar = $curchar = '';
                while ($lastchar . $curchar !== "\r\n") {
                    $line .= $lastchar;
                    $lastchar = $curchar;
                    $curchar = yield $this->sock->read(1);
                }

                return $line;
            }
        );
    }

    public function read_http_payload(): Promise
    {
        return call(
            function () {
                list($protocol, $code, $description) = explode(' ', yield $this->read_http_line(), 3);
                list($protocol, $protocol_version) = explode('/', $protocol);
                if ($protocol !== 'HTTP') {
                    throw new \danog\MadelineProto\Exception('Wrong protocol');
                }
                $code = (int) $code;
                $headers = [];
                while (strlen($current_header = yield $this->read_http_line())) {
                    $current_header = explode(':', $current_header, 2);
                    $headers[strtolower($current_header[0])] = trim($current_header[1]);
                }

                $read = '';
                if (isset($headers['content-length'])) {
                    $read = yield $this->sock->read((int) $headers['content-length']);
                }

                return ['protocol' => $protocol, 'protocol_version' => $protocol_version, 'code' => $code, 'description' => $description, 'body' => $read, 'headers' => $headers];
            }
        );
    }
}
