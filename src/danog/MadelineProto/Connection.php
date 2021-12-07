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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ClosedException;
use Amp\Deferred;
use Amp\Failure;
use danog\MadelineProto\Loop\Connection\CheckLoop;
use danog\MadelineProto\Loop\Connection\CleanupLoop;
use danog\MadelineProto\Loop\Connection\HttpWaitLoop;
use danog\MadelineProto\Loop\Connection\PingLoop;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\Loop\Connection\WriteLoop;
use danog\MadelineProto\MTProto\OutgoingMessage;
use danog\MadelineProto\MTProtoSession\Session;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\StreamInterface;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;

/**
 * Connection class.
 *
 * Manages connection to Telegram datacenters
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Connection
{
    use Session;
    use \danog\Serializable;
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
     * Cleanup loop.
     *
     * @var \danog\MadelineProto\Loop\Connection\CleanupLoop
     */
    protected $cleanup;
    /**
     * The actual socket.
     *
     * @var StreamInterface
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
     * @var float
     */
    private float $lastChunk = 0;
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
    public $API;
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
     * Set writing boolean.
     *
     * @param boolean $writing
     *
     * @return void
     */
    public function writing(bool $writing): void
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
    public function reading(bool $reading): void
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
     * @return float
     */
    public function getLastChunk(): float
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
        return \in_array($this->ctx->getStreamName(), [HttpStream::class, HttpsStream::class]);
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
     * @return \Generator
     *
     * @psalm-return \Generator<mixed, StreamInterface, mixed, void>
     */
    public function connect(ConnectionContext $ctx): \Generator
    {
        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $this->datacenterId = $this->datacenter . '.' . $this->id;
        $this->API->logger->logger("Connecting to DC {$this->datacenterId}", \danog\MadelineProto\Logger::WARNING);
        $this->createSession();
        $ctx->setReadCallback([$this, 'haveRead']);
        $this->stream = (yield from $ctx->getStream());
        $this->API->logger->logger("Connected to DC {$this->datacenterId}!", \danog\MadelineProto\Logger::WARNING);
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
        if (!isset($this->cleanup)) {
            $this->cleanup = new CleanupLoop($this);
        }
        if (!isset($this->waiter)) {
            $this->waiter = new HttpWaitLoop($this);
        }
        if (!isset($this->pinger) && !$this->ctx->isMedia()) { // && ($this->ctx->hasStreamName(WssStream::class) || $this->ctx->hasStreamName(WsStream::class))) {
            //$this->pinger = new PingLoop($this);
        }
        foreach ($this->new_outgoing as $message_id => $message) {
            if ($message->isUnencrypted()) {
                if (!($message->getState() & OutgoingMessage::STATE_REPLIED)) {
                    $message->reply(new Failure(new Exception('Restart because we were reconnected')));
                }
                unset($this->new_outgoing[$message_id], $this->outgoing_messages[$message_id]);
            }
        }
        $this->writer->start();
        $this->reader->start();
        if (!$this->checker->start()) {
            $this->checker->resume();
        }
        $this->cleanup->start();
        $this->waiter->start();
        if ($this->pinger) {
            $this->pinger->start();
        }
    }
    /**
     * Apply method abstractions.
     *
     * @param string $method    Method name
     * @param array  $arguments Arguments
     *
     * @return \Generator Whether we need to resolve a queue promise
     */
    private function methodAbstractions(string &$method, array &$arguments): \Generator
    {
        if ($method === 'messages.importChatInvite' && isset($arguments['hash']) && \is_string($arguments['hash']) && $r = Tools::parseLink($arguments['hash'])) {
            [$invite, $content] = $r;
            if ($invite) {
                $arguments['hash'] = $content;
            } else {
                $method = 'channels.joinChannel';
                $arguments['channel'] = $content;
            }
        } elseif ($method === 'messages.checkChatInvite' && isset($arguments['hash']) && \is_string($arguments['hash']) && $r = Tools::parseLink($arguments['hash'])) {
            [$invite, $content] = $r;
            if (!$invite) {
                throw new TL\Exception('This is not an invite link!');
            }
            $arguments['hash'] = $content;
        } elseif ($method === 'channels.joinChannel' && isset($arguments['channel']) && \is_string($arguments['channel']) && $r = Tools::parseLink($arguments['channel'])) {
            [$invite, $content] = $r;
            if ($invite) {
                $method = 'messages.importChatInvite';
                $arguments['hash'] = $content;
            } else {
                $arguments['channel'] = $content;
            }
        } elseif ($method === 'messages.sendMessage' && isset($arguments['peer']['_']) && \in_array($arguments['peer']['_'], ['inputEncryptedChat', 'updateEncryption', 'updateEncryptedChatTyping', 'updateEncryptedMessagesRead', 'updateNewEncryptedMessage', 'encryptedMessage', 'encryptedMessageService'])) {
            $method = 'messages.sendEncrypted';
            $arguments = ['peer' => $arguments['peer'], 'message' => $arguments];
            if (!isset($arguments['message']['_'])) {
                $arguments['message']['_'] = 'decryptedMessage';
            }
            if (!isset($arguments['message']['ttl'])) {
                $arguments['message']['ttl'] = 0;
            }
            if (isset($arguments['message']['reply_to_msg_id'])) {
                $arguments['message']['reply_to_random_id'] = $arguments['message']['reply_to_msg_id'];
            }
        } elseif ($method === 'messages.sendEncryptedFile' || $method === 'messages.uploadEncryptedFile') {
            if (isset($arguments['file'])) {
                if ((!\is_array($arguments['file']) || !(isset($arguments['file']['_']) && $this->API->getTL()->getConstructors()->findByPredicate($arguments['file']['_']) === 'InputEncryptedFile')) && $this->API->getSettings()->getFiles()->getAllowAutomaticUpload()) {
                    $arguments['file'] = (yield from $this->API->uploadEncrypted($arguments['file']));
                }
                if (isset($arguments['file']['key'])) {
                    $arguments['message']['media']['key'] = $arguments['file']['key'];
                }
                if (isset($arguments['file']['iv'])) {
                    $arguments['message']['media']['iv'] = $arguments['file']['iv'];
                }
                if (isset($arguments['file']['size'])) {
                    $arguments['message']['media']['size'] = $arguments['file']['size'];
                }
            }
            $arguments['queuePromise'] = new Deferred;
            return $arguments['queuePromise'];
        } elseif (\in_array($method, ['messages.addChatUser', 'messages.deleteChatUser', 'messages.editChatAdmin', 'messages.editChatPhoto', 'messages.editChatTitle', 'messages.getFullChat', 'messages.exportChatInvite', 'messages.editChatAdmin', 'messages.migrateChat']) && isset($arguments['chat_id']) && (!\is_numeric($arguments['chat_id']) || $arguments['chat_id'] < 0)) {
            $res = yield from $this->API->getInfo($arguments['chat_id']);
            if ($res['type'] !== 'chat') {
                throw new \danog\MadelineProto\Exception('chat_id is not a chat id (only normal groups allowed, not supergroups)!');
            }
            $arguments['chat_id'] = $res['chat_id'];
        } elseif ($method === 'photos.updateProfilePhoto') {
            if (isset($arguments['id'])) {
                if (!\is_array($arguments['id'])) {
                    $method = 'photos.uploadProfilePhoto';
                    $arguments['file'] = $arguments['id'];
                }
            } elseif (isset($arguments['file'])) {
                $method = 'photos.uploadProfilePhoto';
            }
        } elseif ($method === 'photos.uploadProfilePhoto') {
            if (isset($arguments['file'])) {
                if (\is_array($arguments['file']) && !\in_array($arguments['file']['_'], ['inputFile', 'inputFileBig'])) {
                    $method = 'photos.uploadProfilePhoto';
                    $arguments['id'] = $arguments['file'];
                }
            } elseif (isset($arguments['id'])) {
                $method = 'photos.updateProfilePhoto';
            }
        } elseif ($method === 'messages.uploadMedia') {
            if (!isset($arguments['peer']) && !$this->API->getSelf()['bot']) {
                $arguments['peer'] = 'me';
            }
        } elseif ($method === 'channels.deleteUserHistory') {
            $method = 'channels.deleteParticipantHistory';
            if (isset($arguments['user_id'])) {
                $arguments['participant'] = $arguments['user_id'];
            }
        }
        if ($method === 'messages.sendEncrypted' || $method === 'messages.sendEncryptedService') {
            $arguments['queuePromise'] = new Deferred;
            return $arguments['queuePromise'];
        }
        return null;
    }
    /**
     * Send an MTProto message.
     *
     * @param OutgoingMessage $message The message to send
     * @param boolean         $flush   Whether to flush the message right away
     *
     * @return \Generator
     */
    public function sendMessage(OutgoingMessage $message, bool $flush = true): \Generator
    {
        $message->trySend();
        $promise = $message->getSendPromise();
        if (!$message->hasSerializedBody() || $message->shouldRefreshReferences()) {
            $body = yield from $message->getBody();
            if ($message->shouldRefreshReferences()) {
                $this->API->referenceDatabase->refreshNext(true);
            }
            if ($message->isMethod()) {
                $method = $message->getConstructor();
                $queuePromise = yield from $this->methodAbstractions($method, $body);
                $body = yield from $this->API->getTL()->serializeMethod($method, $body);
            } else {
                $body['_'] = $message->getConstructor();
                $body = yield from $this->API->getTL()->serializeObject(['type' => ''], $body, $message->getConstructor());
            }
            if ($message->shouldRefreshReferences()) {
                $this->API->referenceDatabase->refreshNext(false);
            }
            $message->setSerializedBody($body);
            unset($body);
        }
        $this->pendingOutgoing[$this->pendingOutgoingKey++] = $message;
        if (isset($queuePromise)) {
            $queuePromise->resolve();
        }
        if ($flush && isset($this->writer)) {
            $this->writer->resumeDeferOnce();
        }
        return yield $promise;
    }
    /**
     * Flush pending packets.
     *
     * @return void
     */
    public function flush()
    {
        if (isset($this->writer)) {
            $this->writer->resumeDeferOnce();
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
    public function setExtra($extra, int $id)
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
        foreach (['reader', 'writer', 'checker', 'waiter', 'updater', 'pinger', 'cleanup'] as $loop) {
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
        yield from $this->API->datacenter->dcConnect($this->ctx->getDc(), $this->id);
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
