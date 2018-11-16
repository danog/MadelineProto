<?php
/**
 * Connection module.
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
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Promise;
use Amp\Socket\ClientConnectContext;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\ObfuscatedTransportStream;
use function Amp\call;
use function Amp\Promise\wait;
use function Amp\Socket\connect;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Stream\RawProxyStreamInterface;
use danog\MadelineProto\Stream\Async\RawStream;

/**
 * Connection class.
 *
 * Manages connection to Telegram datacenters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Connection implements RawStreamInterface
{
    use RawStream;
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    const API_ENDPOINT = 0;
    const VOIP_UDP_REFLECTOR_ENDPOINT = 1;
    const VOIP_TCP_REFLECTOR_ENDPOINT = 2;
    const VOIP_UDP_P2P_ENDPOINT = 3;
    const VOIP_UDP_LAN_ENDPOINT = 4;

    public $stream;

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


    /**
     * Connect function.
     *
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters
     *
     * @param string $proxy    Proxy class name
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->stream = $ctx->getStream();
    }

    public function readAsync(): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer();
        $payload_length = $buffer->getLength();
        if ($payload_length === 4) {
            $payload = $this->unpack_signed_int(yield $buffer->bufferRead(4));
            $this->logger->logger("Received $payload from DC $datacenter", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return $payload;
        }

        $auth_key_id = yield $buffer->bufferRead(8);
        if ($auth_key_id === "\0\0\0\0\0\0\0\0") {
            $message_id = yield $buffer->bufferRead(8);
            $this->check_message_id($message_id, ['outgoing' => false, 'datacenter' => $datacenter, 'container' => false]);
            $message_length = unpack('V', $buffer->bufferRead(4))[1];
            $message_data = yield $buffer->bufferRead($message_length);
            $buffer->bufferRead($payload_length - $message_length - 4 - 8);
            $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id] = [];
        } elseif ($auth_key_id === $this->datacenter->sockets[$datacenter]->temp_auth_key['id']) {
            $message_key = yield $buffer->bufferRead(8);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], false);
            $encrypted_data = substr($payload, 24);
            $decrypted_data = $this->ige_decrypt($encrypted_data, $aes_key, $aes_iv);
            /*
            $server_salt = substr($decrypted_data, 0, 8);
            if ($server_salt != $this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt']) {
            $this->logger->logger('WARNING: Server salt mismatch (my server salt '.$this->datacenter->sockets[$datacenter]->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').', \danog\MadelineProto\Logger::WARNING);
            }
             */
            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $this->datacenter->sockets[$datacenter]->session_id) {
                throw new \danog\MadelineProto\Exception('Session id mismatch.');
            }
            $message_id = substr($decrypted_data, 16, 8);
            $this->check_message_id($message_id, ['outgoing' => false, 'datacenter' => $datacenter, 'container' => false]);
            $seq_no = unpack('V', substr($decrypted_data, 24, 4))[1];
            // Dunno how to handle any incorrect sequence numbers
            $message_data_length = unpack('V', substr($decrypted_data, 28, 4))[1];
            if ($message_data_length > strlen($decrypted_data)) {
                throw new \danog\MadelineProto\SecurityException('message_data_length is too big');
            }
            if (strlen($decrypted_data) - 32 - $message_data_length < 12) {
                throw new \danog\MadelineProto\SecurityException('padding is too small');
            }
            if (strlen($decrypted_data) - 32 - $message_data_length > 1024) {
                throw new \danog\MadelineProto\SecurityException('padding is too big');
            }
            if ($message_data_length < 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not positive');
            }
            if ($message_data_length % 4 != 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not divisible by 4');
            }
            $message_data = substr($decrypted_data, 32, $message_data_length);
            if ($message_key != substr(hash('sha256', substr($this->datacenter->sockets[$datacenter]->temp_auth_key['auth_key'], 96, 32).$decrypted_data, true), 8, 16)) {
                throw new \danog\MadelineProto\SecurityException('msg_key mismatch');
            }
            $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id] = ['seq_no' => $seq_no];
        } else {
            $this->close_and_reopen($datacenter);

            throw new \danog\MadelineProto\Exception('Got unknown auth_key id');
        }
        $deserialized = $this->deserialize($message_data, ['type' => '', 'datacenter' => $datacenter]);
        $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['content'] = $deserialized;
        $this->datacenter->sockets[$datacenter]->incoming_messages[$message_id]['response'] = -1;
        $this->datacenter->sockets[$datacenter]->new_incoming[$message_id] = $message_id;
        $this->datacenter->sockets[$datacenter]->last_recv = time();
        $this->datacenter->sockets[$datacenter]->last_http_wait = 0;

        return true;
    }
    public function getName(): string
    {
        return __CLASS__;
    }

    /**
     * Sleep function.
     *
     * @internal
     *
     * @return array
     */
    public function __sleep()
    {
        return ['proxy', 'extra', 'protocol', 'ip', 'port', 'timeout', 'parsed', 'peer_tag', 'temp_auth_key', 'auth_key', 'session_id', 'session_out_seq_no', 'session_in_seq_no', 'ipv6', 'max_incoming_id', 'max_outgoing_id', 'obfuscated', 'authorized', 'ack_queue'];
    }

}
