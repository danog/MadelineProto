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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ClosedException;
use Amp\Deferred;
use Amp\Promise;
use danog\MadelineProto\Loop\Connection\CheckLoop;
use danog\MadelineProto\Loop\Connection\HttpWaitLoop;
use danog\MadelineProto\Loop\Connection\PingLoop;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\Loop\Connection\WriteLoop;
use danog\MadelineProto\MTProtoSession\Session;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;

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

    /**
     * Writer loop.
     *
     * @var \danog\MadelineProto\Loop\Connection\WriteLoop
     */
    protected $writer;
    /**
     * Reader loop.
     *
     * @var \danog\MadelineProto\Loop\Connection\ReadLoop
     */
    protected $reader;
    /**
     * Checker loop.
     *
     * @var \danog\MadelineProto\Loop\Connection\CheckLoop
     */
    protected $checker;
    /**
     * Waiter loop.
     *
     * @var \danog\MadelineProto\Loop\Connection\HttpWaitLoop
     */
    protected $waiter;
    /**
     * Ping loop.
     *
     * @var \danog\MadelineProto\Loop\Connection\PingLoop
     */
    protected $pinger;
    /**
     * The actual socket.
     *
     * @var Stream
     */
    public $stream;
    /**
     * Connection context.
     *
     * @var ConnectionContext
     */
    private $ctx;

    /**
     * HTTP request count.
     *
     * @var integer
     */
    private $httpReqCount = 0;
    /**
     * HTTP response count.
     *
     * @var integer
     */
    private $httpResCount = 0;

    /**
     * Date of last chunk received.
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
     * Shared connection instance.
     *
     * @var DataCenterConnection
     */
    protected $shared;

    /**
     * DC ID.
     *
     * @var string
     */
    protected $datacenter;

    /**
     * Connection ID.
     *
     * @var int
     */
    private $id = 0;

    /**
     * DC ID and connection ID concatenated.
     *
     * @var
     */
    private $datacenterId = '';

    /**
     * Whether this socket has to be reconnected.
     *
     * @var boolean
     */
    private $needsReconnect = false;
    /**
     * Indicate if this socket needs to be reconnected.
     *
     * @param boolean $needsReconnect Whether the socket has to be reconnected
     *
     * @return void
     */
    public function needReconnect(bool $needsReconnect)
    {
        $this->needsReconnect = $needsReconnect;
    }
    /**
     * Whether this sockets needs to be reconnected.
     *
     * @return boolean
     */
    public function shouldReconnect(): bool
    {
        return $this->needsReconnect;
    }
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
     * Set writing boolean.
     *
     * @param boolean $writing
     *
     * @return void
     */
    public function writing(bool $writing)
    {
        $this->shared->writing($writing, $this->id);
    }
    /**
     * Set reading boolean.
     *
     * @param boolean $reading
     *
     * @return void
     */
    public function reading(bool $reading)
    {
        $this->shared->reading($reading, $this->id);
    }

    /**
     * Tell the class that we have read a chunk of data from the socket.
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
     * @return int
     */
    public function getLastChunk(): int
    {
        return $this->lastChunk;
    }

    /**
     * Indicate a received HTTP response.
     *
     * @return void
     */
    public function httpReceived()
    {
        $this->httpResCount++;
    }
    /**
     * Count received HTTP responses.
     *
     * @return integer
     */
    public function countHttpReceived(): int
    {
        return $this->httpResCount;
    }
    /**
     * Indicate a sent HTTP request.
     *
     * @return void
     */
    public function httpSent()
    {
        $this->httpReqCount++;
    }
    /**
     * Count sent HTTP requests.
     *
     * @return integer
     */
    public function countHttpSent(): int
    {
        return $this->httpReqCount;
    }

    /**
     * Get connection ID.
     *
     * @return integer
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * Get datacenter concatenated with connection ID.
     *
     * @return string
     */
    public function getDatacenterID(): string
    {
        return $this->datacenterId;
    }

    /**
     * Get connection context.
     *
     * @return ConnectionContext
     */
    public function getCtx(): ConnectionContext
    {
        return $this->ctx;
    }

    /**
     * Check if is an HTTP connection.
     *
     * @return boolean
     */
    public function isHttp(): bool
    {
        return \in_array($this->ctx->getStreamName(), [HttpStream::getName(), HttpsStream::getName()]);
    }

    /**
     * Check if is a media connection.
     *
     * @return boolean
     */
    public function isMedia(): bool
    {
        return $this->ctx->isMedia();
    }

    /**
     * Check if is a CDN connection.
     *
     * @return boolean
     */
    public function isCDN(): bool
    {
        return $this->ctx->isCDN();
    }

    /**
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters.
     *
     * @param ConnectionContext $ctx Connection context
     *
     * @return \Amp\Promise
     */
    public function connect(ConnectionContext $ctx): \Generator
    {
        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $this->datacenterId = $this->datacenter.'.'.$this->id;
        $this->API->logger->logger("Connecting to DC {$this->datacenterId}", \danog\MadelineProto\Logger::WARNING);

        $ctx->setReadCallback([$this, 'haveRead']);
        $this->stream = yield $ctx->getStream();

        if ($this->needsReconnect) {
            $this->needsReconnect = false;
        }
        $this->httpReqCount = 0;
        $this->httpResCount = 0;

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
        if (!isset($this->pinger) && ($this->ctx->hasStreamName(WssStream::getName()) || $this->ctx->hasStreamName(WsStream::getName()))) {
            $this->pinger = new PingLoop($this);
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

        $this->writer->start();
        $this->reader->start();
        if (!$this->checker->start()) {
            $this->checker->resume();
        }
        $this->waiter->start();
        if ($this->pinger) {
            $this->pinger->start();
        }
    }

    /**
     * Send an MTProto message.
     *
     * Structure of message array:
     * [
     *     // only in outgoing messages
     *     'body' => deserialized body, (optional if container)
     *     'serialized_body' => 'serialized body', (optional if container)
     *     'contentRelated' => bool,
     *     '_' => 'predicate',
     *     'promise' => deferred promise that gets resolved when a response to the message is received (optional),
     *     'send_promise' => deferred promise that gets resolved when the message is sent (optional),
     *     'file' => bool (optional),
     *     'type' => 'type' (optional),
     *     'queue' => queue ID (optional),
     *     'container' => [message ids] (optional),
     *
     *     // only in incoming messages
     *     'content' => deserialized body,
     *     'seq_no' => number (optional),
     *     'from_container' => bool (optional),
     *
     *     // can be present in both
     *     'response' => message id (optional),
     *     'msg_id' => message id (optional),
     *     'sent' => timestamp,
     *     'tries' => number
     * ]
     *
     * @param array   $message The message to send
     * @param boolean $flush   Whether to flush the message right away
     *
     * @return \Generator
     */
    public function sendMessage(array $message, bool $flush = true): \Generator
    {
        $deferred = new Deferred();

        if (!isset($message['serialized_body'])) {
            $body = \is_object($message['body']) ? yield $message['body'] : $message['body'];

            $refreshNext = isset($message['refreshNext']) && $message['refreshNext'];
            //$refreshNext = true;

            if ($refreshNext) {
                $this->API->referenceDatabase->refreshNext(true);
            }

            if ($message['method']) {
                $body = yield $this->API->getTL()->serializeMethod($message['_'], $body);
            } else {
                $body['_'] = $message['_'];
                $body = yield $this->API->getTL()->serializeObject(['type' => ''], $body, $message['_']);
            }
            if ($refreshNext) {
                $this->API->referenceDatabase->refreshNext(false);
            }
            $message['serialized_body'] = $body;
            unset($body);
        }

        $message['send_promise'] = $deferred;
        $this->pending_outgoing[$this->pending_outgoing_key++] = $message;
        if ($flush && isset($this->writer)) {
            $this->writer->resume();
        }

        return $deferred->promise();
    }

    /**
     * Flush pending packets.
     *
     * @return void
     */
    public function flush()
    {
        if (isset($this->writer)) {
            $this->writer->resume();
        }
    }
    /**
     * Resume HttpWaiter.
     *
     * @return void
     */
    public function pingHttpWaiter()
    {
        if (isset($this->waiter)) {
            $this->waiter->resume();
        }
        if (isset($this->pinger)) {
            $this->pinger->resume();
        }
    }
    /**
     * Connect main instance.
     *
     * @param DataCenterConnection $extra Shared instance
     * @param int                  $id    Connection ID
     *
     * @return void
     */
    public function setExtra(DataCenterConnection $extra, int $id)
    {
        $this->shared = $extra;
        $this->id = $id;
        $this->API = $extra->getExtra();
        $this->logger = $this->API->logger;
    }

    /**
     * Get main instance.
     *
     * @return MTProto
     */
    public function getExtra(): MTProto
    {
        return $this->API;
    }

    /**
     * Get shared connection instance.
     *
     * @return DataCenterConnection
     */
    public function getShared(): DataCenterConnection
    {
        return $this->shared;
    }

    /**
     * Disconnect from DC.
     *
     * @param bool $temporary Whether the disconnection is temporary, triggered by the reconnect method
     *
     * @return void
     */
    public function disconnect(bool $temporary = false)
    {
        $this->API->logger->logger("Disconnecting from DC {$this->datacenterId}");
        $this->needsReconnect = true;
        foreach (['reader', 'writer', 'checker', 'waiter', 'updater', 'pinger'] as $loop) {
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
        if (!$temporary) {
            $this->shared->signalDisconnect($this->id);
        }
        $this->API->logger->logger("Disconnected from DC {$this->datacenterId}");
    }

    /**
     * Reconnect to DC.
     *
     * @return \Generator
     */
    public function reconnect(): \Generator
    {
        $this->API->logger->logger("Reconnecting DC {$this->datacenterId}");
        $this->disconnect(true);
        yield $this->API->datacenter->dcConnect($this->ctx->getDc(), $this->id);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return __CLASS__;
    }
}
