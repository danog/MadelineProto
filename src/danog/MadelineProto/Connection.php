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

use Amp\Deferred;
use Amp\Promise;
use danog\MadelineProto\Loop\Connection\CheckLoop;
use danog\MadelineProto\Loop\Connection\HttpWaitLoop;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\Loop\Connection\UpdateLoop;
use danog\MadelineProto\Loop\Connection\WriteLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTools\MsgIdHandler;
use danog\MadelineProto\Stream\MTProtoTools\SeqNoHandler;

/**
 * Connection class.
 *
 * Manages connection to Telegram datacenters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Connection
{
    use Crypt;
    use MsgIdHandler;
    use SeqNoHandler;
    use \danog\Serializable;
    use Tools;

    const API_ENDPOINT = 0;
    const VOIP_UDP_REFLECTOR_ENDPOINT = 1;
    const VOIP_TCP_REFLECTOR_ENDPOINT = 2;
    const VOIP_UDP_P2P_ENDPOINT = 3;
    const VOIP_UDP_LAN_ENDPOINT = 4;

    const PENDING_MAX = 2000000000;

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
    public $authorized = false;
    public $call_queue = [];
    public $ack_queue = [];
    public $i = [];
    public $last_recv = 0;
    public $last_http_wait = 0;

    public $datacenter;
    public $API;
    public $resumeWriterDeferred;
    public $ctx;
    public $pendingCheckWatcherId;

    public $http_req_count = 0;
    public $http_res_count = 0;

    public function getCtx()
    {
        return $this->ctx;
    }

    /**
     * Connect function.
     *
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters
     *
     * @param string $proxy Proxy class name
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function connect(ConnectionContext $ctx): Promise
    {
        return $this->call($this->connectAsync($ctx));
    }

    /**
     * Connect function.
     *
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters
     *
     * @param string $proxy Proxy class name
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->API->logger->logger("Trying connection via $ctx", \danog\MadelineProto\Logger::WARNING);

        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $this->stream = yield $ctx->getStream();
        if (isset($this->old)) {
            unset($this->old);
        }

        if (!isset($this->writer)) {
            $this->writer = new WriteLoop($this->API, $this->datacenter);
        }
        if (!isset($this->reader)) {
            $this->reader = new ReadLoop($this->API, $this->datacenter);
        }
        if (!isset($this->checker)) {
            $this->checker = new CheckLoop($this->API, $this->datacenter);
        }
        if (!isset($this->waiter)) {
            $this->waiter = new HttpWaitLoop($this->API, $this->datacenter);
        }
        if (!isset($this->updater)) {
            $this->updater = new UpdateLoop($this->API, $this->datacenter);
        }
        foreach ($this->new_outgoing as $message_id) {
            if ($this->outgoing_messages[$message_id]['unencrypted']) {
                $promise = $this->outgoing_messages[$message_id]['promise'];
                \Amp\Loop::defer(function () use ($promise) {
                    $promise->fail(new Exception('Restart'));
                });
                unset($this->new_outgoing[$message_id]);
                unset($this->outgoing_messages[$message_id]);
            }
        }
        $this->http_req_count = 0;
        $this->http_res_count = 0;

        $this->writer->start();
        $this->reader->start();
        if (!$this->checker->start()) {
            $this->checker->resume();
        }
        $this->waiter->start();

        if ($this->datacenter === $this->API->settings['connection_settings']['default_dc']) {
            $this->updater->start();
        }
    }

    public function sendMessage($message, $flush = true): Promise
    {
        return $this->call($this->sendMessageGenerator($message, $flush));
    }

    public function sendMessageGenerator($message, $flush = true): \Generator
    {
        $deferred = new Deferred();

        if (!isset($message['serialized_body'])) {
            $body = is_object($message['body']) ? yield $message['body'] : $message['body'];

            $refresh_next = isset($message['refresh_next']) && $message['refresh_next'];
            //$refresh_next = true;

            if ($refresh_next) {
                $this->API->referenceDatabase->refreshNext(true);
            }

            if ($message['method']) {
                $body = yield $this->API->serialize_method_async($message['_'], $body);
            } else {
                $body = yield $this->API->serialize_object_async(['type' => $message['_']], $body, $message['_']);
            }
            if ($refresh_next) {
                $this->API->referenceDatabase->refreshNext(false);
            }
            $message['serialized_body'] = $body;
        }

        $message['send_promise'] = $deferred;
        $this->pending_outgoing[$this->pending_outgoing_key++] = $message;
        $this->pending_outgoing_key %= self::PENDING_MAX;
        if ($flush) {
            $this->writer->resume();
        }

        return yield $deferred->promise();
    }

    public function setExtra($extra)
    {
        $this->API = $extra;
    }

    public function disconnect()
    {
        $this->old = true;
        foreach (['reader', 'writer', 'checker', 'waiter', 'updater'] as $loop) {
            if (isset($this->{$loop}) && $this->{$loop}) {
                $this->{$loop}->signal($loop === 'reader' ? new NothingInTheSocketException() : true);
            }
        }
        if ($this->stream) {
            $this->stream->disconnect();
        }
    }

    public function reconnect(): Promise
    {
        return $this->call($this->reconnectAsync());
    }

    public function reconnectAsync(): \Generator
    {
        $this->API->logger->logger('Reconnecting');
        $this->disconnect();
        yield $this->API->datacenter->dc_connect_async($this->ctx->getDc());
    }

    public function hasPendingCalls()
    {
        $API = $this->API;
        $datacenter = $this->datacenter;

        $dc_config_number = isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';
        $timeout = $API->settings['connection_settings'][$dc_config_number]['timeout'];
        foreach ($this->new_outgoing as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['sent'])
                && $this->outgoing_messages[$message_id]['sent'] + $timeout < time()
                && ($this->temp_auth_key === null) === $this->outgoing_messages[$message_id]['unencrypted']
                && $this->outgoing_messages[$message_id]['_'] !== 'msgs_state_req'
            ) {
                return true;
            }
        }

        return false;
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
        return ['peer_tag', 'temp_auth_key', 'auth_key', 'session_id', 'session_out_seq_no', 'session_in_seq_no', 'max_incoming_id', 'max_outgoing_id', 'authorized', 'ack_queue'];
    }

    public function __wakeup()
    {
        $this->time_delta = 0;
        $this->pending_outgoing = [];
        $this->new_outgoing = [];
        $this->new_incoming = [];
        $this->outgoing_messages = [];
        $this->incoming_messages = [];
    }
}
