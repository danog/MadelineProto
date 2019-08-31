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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ClosedException;
use Amp\Deferred;
use danog\MadelineProto\Loop\Connection\CheckLoop;
use danog\MadelineProto\Loop\Connection\HttpWaitLoop;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\Loop\Connection\WriteLoop;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTools\Session;

/**
 * Connection class.
 *
 * Manages connection to Telegram datacenters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Connection extends Session
{
    use \danog\Serializable;
    use Tools;

    const PENDING_MAX = 2000000000;

    /**
     * The actual socket
     *
     * @var Stream
     */
    private $stream;
    /**
     * Connection context
     *
     * @var Connection context
     */
    private $ctx;

    /**
     * HTTP request count
     *
     * @var integer
     */
    private $httpReqCount = 0;
    /**
     * HTTP response count
     *
     * @var integer
     */
    private $httpResCount = 0;

    /**
     * Date of last chunk received
     *
     * @var integer
     */
    private $lastChunk = 0;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;
    /**
     * Main instance.
     *
     * @var MTProto
     */
    protected $API;

    /**
     * DC ID
     *
     * @var string
     */
    protected $datacenter;

    /**
     * Whether the socket is reading data.
     *
     * @var boolean
     */
    private $reading = false;
    /**
     * Whether the socket is writing data.
     *
     * @var boolean
     */
    private $writing = false;

    /**
     * Check if the socket is writing stuff.
     *
     * @return boolean
     */
    public function isWriting(): bool
    {
        return $this->writing;
    }
    /**
     * Check if the socket is reading stuff.
     *
     * @return boolean
     */
    public function isReading(): bool
    {
        return $this->reading;
    }
    /**
     * Set writing boolean
     *
     * @param boolean $writing
     * 
     * @return void
     */
    public function writing(bool $writing)
    {
        $this->writing = $writing;
    }
    /**
     * Set reading boolean
     *
     * @param boolean $reading
     * 
     * @return void
     */
    public function reading(bool $reading)
    {
        $this->reading = $reading;
    }

    /**
     * Tell the class that we have read a chunk of data from the socket
     *
     * @return void
     */
    public function haveRead()
    {
        $this->lastChunk = \microtime(true);
    }
    /**
     * Get the receive date of the latest chunk of data from the socket.
     *
     * @return void
     */
    public function getLastChunk()
    {
        return $this->lastChunk;
    }

    /**
     * Indicate a received HTTP response
     *
     * @return void
     */
    public function httpReceived()
    {
        $this->httpResCount++;
    }
    /**
     * Count received HTTP responses
     *
     * @return integer
     */
    public function countHttpReceived(): int
    {
        return $this->httpResCount;
    }
    /**
     * Indicate a sent HTTP request
     *
     * @return void
     */
    public function httpSent()
    {
        $this->httpReqCount++;
    }
    /**
     * Count sent HTTP requests
     *
     * @return integer
     */
    public function countHttpSent(): int
    {
        return $this->httpReqCount;
    }


    /**
     * Get connection context
     *
     * @return ConnectionContext
     */
    public function getCtx(): ConnectionContext
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
    public function connect(ConnectionContext $ctx): \Generator
    {
        $this->API->logger->logger("Trying connection via $ctx", \danog\MadelineProto\Logger::WARNING);

        $ctx->setReadCallback([$this, 'haveRead']);

        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $this->stream = yield $ctx->getStream();
        if (isset($this->old)) {
            unset($this->old);
        }

        if (!isset($this->writer)) {
            $this->writer = new WriteLoop($this);
        }
        if (!isset($this->reader)) {
            $this->reader = new ReadLoop($this);
        }
        if (!isset($this->checker)) {
            $this->checker = new CheckLoop($this);
        }
        if (!isset($this->waiter)) {
            $this->waiter = new HttpWaitLoop($this);
        }
        foreach ($this->new_outgoing as $message_id) {
            if ($this->outgoing_messages[$message_id]['unencrypted']) {
                $promise = $this->outgoing_messages[$message_id]['promise'];
                \Amp\Loop::defer(function () use ($promise) {
                    $promise->fail(new Exception('Restart because we were reconnected'));
                });
                unset($this->new_outgoing[$message_id], $this->outgoing_messages[$message_id]);
            }
        }
        $this->httpReqCount = 0;
        $this->httpResCount = 0;

        $this->writer->start();
        $this->reader->start();
        if (!$this->checker->start()) {
            $this->checker->resume();
        }
        $this->waiter->start();
    }

    public function sendMessage($message, $flush = true)
    {
        $deferred = new Deferred();

        if (!isset($message['serialized_body'])) {
            $body = \is_object($message['body']) ? yield $message['body'] : $message['body'];

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
            unset($body);
        }

        $message['send_promise'] = $deferred;
        $this->pending_outgoing[$this->pending_outgoing_key++] = $message;
        $this->pending_outgoing_key %= self::PENDING_MAX;
        if ($flush && isset($this->writer)) {
            $this->writer->resume();
        }

        return $deferred->promise();
    }

    /**
     * Connect main instance.
     *
     * @param MTProto $extra
     *
     * @return void
     */
    public function setExtra(MTProto $extra)
    {
        $this->API = $extra;
        $this->logger = $extra->logger;
    }

    /**
     * Get main instance
     *
     * @return MTProto
     */
    public function getExtra(): MTProto
    {
        return $this->API;
    }
    public function disconnect()
    {
        $this->API->logger->logger("Disconnecting from DC {$this->datacenter}");
        $this->old = true;
        foreach (['reader', 'writer', 'checker', 'waiter', 'updater'] as $loop) {
            if (isset($this->{$loop}) && $this->{$loop}) {
                $this->{$loop}->signal($loop === 'reader' ? new NothingInTheSocketException() : true);
            }
        }
        if ($this->stream) {
            try {
                $this->stream->disconnect();
            } catch (ClosedException $e) {
                $this->API->logger->logger($e);
            }
        }
        $this->API->logger->logger("Disconnected from DC {$this->datacenter}");
    }

    public function reconnect(): \Generator
    {
        $this->API->logger->logger("Reconnecting DC {$this->datacenter}");
        $this->disconnect();
        yield $this->API->datacenter->dcConnectAsync($this->ctx->getDc());
        if ($this->API->hasAllAuth() && !$this->hasPendingCalls()) {
            /*$this->callFork((function () {
                try {
                    $this->API->method_call_async_read('ping', ['ping_id' => $this->random_int()], ['datacenter' => $this->datacenter]);
                } catch (\Throwable $e) {
                    $this->API->logger("Got an error while pinging on reconnect: $e", Logger::FATAL_ERROR);
                }
            })());*/
        }
    }

    public function hasPendingCalls()
    {
        $API = $this->API;
        $datacenter = $this->datacenter;

        $dc_config_number = isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';
        $timeout = $API->settings['connection_settings'][$dc_config_number]['timeout'];
        $pfs = $API->settings['connection_settings'][$dc_config_number]['pfs'];

        foreach ($this->new_outgoing as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['sent'])
                && $this->outgoing_messages[$message_id]['sent'] + $timeout < \time()
                && ($this->temp_auth_key === null) === $this->outgoing_messages[$message_id]['unencrypted']
                && $this->outgoing_messages[$message_id]['_'] !== 'msgs_state_req'
            ) {
                if ($pfs && !isset($this->temp_auth_key['bound']) && $this->outgoing_messages[$message_id]['_'] !== 'auth.bindTempAuthKey') {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    public function getPendingCalls()
    {
        $API = $this->API;
        $datacenter = $this->datacenter;

        $dc_config_number = isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all';
        $timeout = $API->settings['connection_settings'][$dc_config_number]['timeout'];
        $pfs = $API->settings['connection_settings'][$dc_config_number]['pfs'];

        $result = [];
        foreach ($this->new_outgoing as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['sent'])
                && $this->outgoing_messages[$message_id]['sent'] + $timeout < \time()
                && ($this->temp_auth_key === null) === $this->outgoing_messages[$message_id]['unencrypted']
                && $this->outgoing_messages[$message_id]['_'] !== 'msgs_state_req'
            ) {
                if ($pfs && !isset($this->temp_auth_key['bound']) && $this->outgoing_messages[$message_id]['_'] !== 'auth.bindTempAuthKey') {
                    continue;
                }

                $result[] = $message_id;
            }
        }

        return $result;
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
}
