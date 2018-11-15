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
use \Amp\Promise;
use \Amp\Socket\ClientConnectContext;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\ObfuscatedTransportStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;

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

    public $dc;
    public $proxy;
    public $extra = [];
    public $uri;
    public $protocol;
    public $timeout;
    public $sock;
    public $ipv6 = false;

    public $time_delta = 0;
    public $type = 0;
    public $peer_tag;
    public $temp_auth_key;
    public $auth_key;
    public $session_id;
    public $session_out_seq_no = 0;
    public $session_in_seq_no = 0;
    public $incoming_messages = [];
    public $outgoing_messages = [];
    public $new_incoming = [];
    public $new_outgoing = [];
    public $pending_outgoing = [];
    public $pending_outgoing_key = 0;
    public $max_incoming_id;
    public $max_outgoing_id;
    public $obfuscated = [];
    public $authorized = false;
    public $call_queue = [];
    public $ack_queue = [];
    public $i = [];
    public $last_recv = 0;
    public $last_http_wait = 0;

    public $ctx;

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
    public function connect($dc, $proxy, $extra, $uri, $protocol, $timeout, $ipv6): Promise
    {
        \danog\MadelineProto\Logger::log('Opening...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        if ($proxy === '\\MTProxySocket') {
            $proxy = '\\Socket';
            $protocol = 'obfuscated2';
        }
        if ($proxy === '\\Socket') {
            $proxy = DefaultStream::getName();
        }
        
        if ($protocol === 'obfuscated2' && $proxy === '\\danog\\MadelineProto\\Stream\\Transport\\DefaultStream') {
            $proxy = ObfuscatedTransportStream::getName();
        }

        if (!isset(class_implements($proxy)['danog\\MadelineProto\\Stream\\StreamInterface'])) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['proxy_class_invalid']);
        }


        /** @var $context \Amp\ClientConnectContext */
        $context = new ClientConnectContext();
        $context = $context->withConnectTimeout($timeout);
        $context = $context->withMaxAttempts(1);

        /** @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
        $ctx = new ConnectionContext();
        $ctx->setDc($dc)->setUri($uri)->setSocketContext($context)->secure($protocol === 'https');
        
        if ($proxy !== ObfuscatedTransportStream::getName() && $proxy !== DefaultStream::getName()) {
            $ctx->addStream(DefaultStream::getName(), $extra);
        }
        $ctx->addStream($proxy, $extra);

        switch ($protocol) {
            case 'tcp_abridged':
                $ctx->addStream(AbridgedStream::getName());
                break;
            case 'tcp_intermediate':
                $ctx->addStream(IntermediateStream::getName());
                break;
            case 'tcp_full':
                $ctx->addStream(FullStream::getName());
                break;
            case 'http':
            case 'https':
                $ctx->addStream(HttpStream::getName());
                break;
            case 'obfuscated2':
                if ($proxy !== ObfuscatedTransportStream::getName()) {
                    $ctx->addStream(ObfuscatedStream::getName(), $extra);
                }
                break;
            default:
                throw new Exception(\danog\MadelineProto\Lang::$current_lang['protocol_invalid']);

        }

        $this->dc = $dc;
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->ipv6 = $ipv6;
        $this->uri = $uri;
        $this->proxy = $proxy;
        $this->extra = $extra;

        return $ctx->getStream();
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
    public function close()
    {}

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
        if ($this->uri) {
            wait($this->connect($this->dc, $this->proxy, $this->extra, $this->uri, $this->protocol, $this->timeout, $this->ipv6));
        }
    }

}
