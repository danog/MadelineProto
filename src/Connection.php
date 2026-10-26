<?php

declare(strict_types=1);

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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Future;
use Amp\Sync\LocalMutex;
use danog\DialogId\DialogId;
use danog\Loop\GenericLoop;
use danog\MadelineProto\Loop\Connection\CleanupLoop;
use danog\MadelineProto\Loop\Connection\PingLoop;
use danog\MadelineProto\Loop\Connection\ReadLoop;
use danog\MadelineProto\Loop\Connection\WriteLoop;
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\MTProtoSession\Session;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
use danog\MadelineProto\TL\Exception as TLException;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;

/**
 * Connection class.
 *
 * Manages connection to Telegram datacenters
 *
 * @psalm-suppress RedundantPropertyInitializationCheck
 *
 * @internal
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class Connection
{
    use Session;
    /**
     * Writer loop.
     *
     */
    protected ?WriteLoop $writer = null;
    /**
     * Handler loop.
     *
     */
    protected ?GenericLoop $handler = null;
    /**
     * Reader loop.
     *
     */
    protected ?ReadLoop $reader = null;
    /**
     * Ping loop.
     *
     */
    public ?PingLoop $pinger = null;
    /**
     * Cleanup loop.
     *
     */
    protected ?CleanupLoop $cleanup = null;
    /**
     * The actual socket.
     * @var (MTProtoBufferInterface&BufferedStreamInterface)|null
     */
    public MTProtoBufferInterface|null $stream = null;
    /**
     * Connection context.
     */
    private ?ConnectionContext $chosenCtx = null;
    /**
     * HTTP request count.
     *
     */
    private int $httpReqCount = 0;
    /**
     * HTTP response count.
     *
     */
    private int $httpResCount = 0;
    /**
     * Whether we're currently writing an MTProto packet.
     */
    private bool $writing = false;
    /**
     * Main instance.
     *
     */
    public MTProto $API;
    /**
     * Shared connection instance.
     *
     */
    protected DataCenterConnection $shared;
    /**
     * DC ID.
     *
     */
    protected int $datacenter;
    /**
     * Connection ID.
     *
     */
    private int $id = 0;
    /**
     * DC ID and connection ID concatenated.
     */
    private string $datacenterId = '';
    /**
     * Whether this socket has to be reconnected.
     *
     */
    private bool $needsReconnect = false;
    /** Changes whenever the transport is disconnected, including temporary reconnects. */
    private int $generation = 0;

    public function getGeneration(): int
    {
        return $this->generation;
    }
    /**
     * Number of file requests sent on this socket that weren't answered yet (see DataCenterConnection::waitGetFileConnection).
     *
     * @internal
     */
    public int $fileRequests = 0;
    /**
     * Indicate if this socket needs to be reconnected.
     *
     * @param boolean $needsReconnect Whether the socket has to be reconnected
     *
     * @psalm-external-mutation-free
     */
    public function needReconnect(bool $needsReconnect): void
    {
        $this->needsReconnect = $needsReconnect;
    }
    /**
     * Whether this sockets needs to be reconnected.
     */
    public function shouldReconnect(): bool
    {
        return $this->needsReconnect;
    }
    /**
     * Set writing boolean.
     *
     * @psalm-external-mutation-free
     */
    public function writing(bool $writing): void
    {
        $this->writing = $writing;
    }
    /**
     * Whether we're currently writing an MTProto packet.
     */
    public function isWriting(): bool
    {
        return $this->writing;
    }
    /**
     * Indicate a received HTTP response.
     *
     * @psalm-external-mutation-free
     */
    public function httpReceived(): void
    {
        $this->httpResCount++;
    }
    /**
     * Count received HTTP responses.
     */
    public function countHttpReceived(): int
    {
        return $this->httpResCount;
    }
    /**
     * Indicate a sent HTTP request.
     *
     * @psalm-external-mutation-free
     */
    public function httpSent(): void
    {
        $this->httpReqCount++;
    }
    /**
     * Count sent HTTP requests.
     */
    public function countHttpSent(): int
    {
        return $this->httpReqCount;
    }
    /**
     * Get connection ID.
     */
    public function getID(): int
    {
        return $this->id;
    }
    /**
     * Get datacenter concatenated with connection ID.
     */
    public function getDatacenterID(): string
    {
        return $this->datacenterId;
    }
    public function getDatacenter(): int
    {
        return $this->datacenter;
    }
    /**
     * Get connection context.
     */
    public function getInputClientProxy(): ?array
    {
        if ($this->chosenCtx === null) {
            $this->connect();
        }
        Assert::notNull($this->chosenCtx);
        return $this->chosenCtx->getInputClientProxy();
    }
    /**
     * Check if is an HTTP connection.
     *
     * @psalm-mutation-free
     */
    public function isHttp(): bool
    {
        return $this->chosenCtx->isHttp();
    }
    /**
     * @return Publisher<ConnectionState>
     *
     * @psalm-mutation-free
     */
    public function getState(): Publisher
    {
        return $this->shared->auth->connectionState;
    }

    private ?LocalMutex $connectMutex = null;
    /**
     * Whether the connection was closed for good ({@see self::disconnect()}, not temporary): it's never connected
     * again, its pending messages were backed up by the DC, which sends the next ones on another connection.
     */
    private bool $closed = false;
    /** Cancelled once the connection is closed for good, stopping the wait before the next connection attempt. */
    private ?DeferredCancellation $closing = null;
    /** The connection attempts that run until connected, see {@see self::connectInBackground()}. */
    private ?Future $connecting = null;
    /**
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters.
     *
     * @throws CouldNotConnectException If every way to connect failed, or the connection was closed for good.
     */
    private function connect(): self
    {
        if ($this->stream) {
            return $this;
        }
        $this->connectMutex ??= new LocalMutex;
        $lock = $this->connectMutex->acquire();
        try {
            if ($this->stream) {
                return $this;
            }
            $this->createSession();
            while (true) {
                $error = null;
                foreach ($this->shared->getCtxs() as $ctx) {
                    if ($this->closed) {
                        throw new CouldNotConnectException("The connection to DC {$this->datacenterId} was closed");
                    }
                    $this->API->logger("Connecting to DC {$this->datacenterId} via $ctx ", Logger::WARNING);
                    try {
                        $stream = $ctx->getStream();
                    } catch (\Throwable $e) {
                        $error = $e;
                        $this->API->logger("$e while connecting to DC {$this->datacenterId} via $ctx, trying next...", Logger::ERROR);
                        continue;
                    }
                    if ($this->closed) {
                        // Closed for good while connecting: nothing would ever stop the loops of this connection.
                        try {
                            $stream->disconnect();
                        } catch (ClosedException) {
                        }
                        throw new CouldNotConnectException("The connection to DC {$this->datacenterId} was closed while connecting");
                    }
                    $this->stream = $stream;
                    $this->API->logger("Connected to DC {$this->datacenterId} via $ctx!", Logger::WARNING);
                    $this->chosenCtx = $ctx;

                    if ($ctx->getIpv6()) {
                        Magic::setIpv6(true);
                    }
                    if ($this->needsReconnect) {
                        $this->needsReconnect = false;
                    }
                    $this->httpReqCount = 0;
                    $this->httpResCount = 0;
                    $this->writer ??= new WriteLoop($this);
                    $this->reader ??= new ReadLoop($this);
                    $this->cleanup ??= new CleanupLoop($this);
                    $this->handler ??= new GenericLoop(function (): void {
                        $this->handleMessages($this->new_incoming);
                        if ($this->ack_queue) {
                            $this->flush(true); // Flush acks
                        }
                    }, "Handler loop");
                    if (!isset($this->pinger) && !$this->shared->auth->isMedia && !$this->shared->auth->isCdn && !$this->isHttp()) {
                        $this->pinger = new PingLoop($this);
                    }
                    foreach ($this->unencrypted_new_outgoing as $message) {
                        $message->reply(static fn () => new Exception('Restart because we were reconnected'));
                    }
                    Assert::true($this->writer->start(), "Could not start writer stream");
                    Assert::true($this->reader->start(), "Could not start reader stream");
                    Assert::true($this->cleanup->start(), "Could not start cleanup stream");
                    $this->handler->start();
                    $this->pinger?->onConnect();
                    // The requests sent on the previous connection may have been lost with it: ask for their state now
                    // (the server answers state requests right away), not when their check timer fires.
                    foreach ($this->new_outgoing as $message) {
                        if ($message->contentRelated) {
                            $message->requestState();
                        }
                    }

                    if ($this->stream !== $stream) {
                        // Disconnected meanwhile (this fiber can suspend after publishing the stream: logging, a loop
                        // waiting for its previous run to exit): disconnect() couldn't stop the loops started after it.
                        // Connecting again, unless it was closed for good (checked by the next iteration).
                        $this->API->logger("Disconnected from DC {$this->datacenterId} while connecting, connecting again", Logger::WARNING);
                        $this->stopLoops();
                        continue 2;
                    }
                    return $this;
                }
                $message = "Could not connect to DC {$this->datacenterId}";
                if ($error !== null) {
                    $message .= ": {$error->getMessage()}";
                }
                throw new CouldNotConnectException($message, 0, $error);
            }
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Connects in the background, trying again until connected (waiting 1 second, doubling up to 30) or closed for good.
     *
     * The only way connections are made (but for getInputClientProxy()), once at a time per connection: by a
     * reconnection, or by a message sent while not connected (queued until then).
     */
    private function connectInBackground(): Future
    {
        return $this->connecting ??= async($this->retryConnect(...));
    }
    private function retryConnect(): void
    {
        $this->closing ??= new DeferredCancellation;
        $closing = $this->closing->getCancellation();
        $delay = 1.0;
        try {
            while (!$this->closed) {
                try {
                    $this->connect();
                    return;
                } catch (CouldNotConnectException $e) {
                    if ($this->closed) {
                        return;
                    }
                    $this->API->logger("{$e->getMessage()}, trying again in $delay seconds", Logger::WARNING);
                }
                try {
                    delay($delay, cancellation: $closing);
                } catch (CancelledException) {
                    return;
                }
                $delay = min($delay * 2, 30.0);
            }
        } finally {
            $this->connecting = null;
        }
    }
    public function wakeupHandler(MTProtoIncomingMessage $message): void
    {
        $this->new_incoming->enqueue($message);
        \assert($this->handler !== null);
        Assert::true($this->handler->resume() || $this->handler->isRunning(), "Could not resume handler!");
    }
    /**
     * Apply method abstractions.
     *
     * @param string $method    Method name
     * @param array  $arguments Arguments
     */
    private function methodAbstractions(string &$method, array &$arguments): void
    {
        if ($method === 'messages.sendPaidReaction') {
            $arguments['random_id'] = (time() << 32) | random_int(0, 0xFF_FF_FF_FF);
        } elseif ($method === 'messages.importChatInvite' && isset($arguments['hash']) && \is_string($arguments['hash']) && $r = Tools::parseLink($arguments['hash'])) {
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
        } elseif ($method === 'messages.sendMessage' &&
            (
                (isset($arguments['peer']['_']) && \in_array($arguments['peer']['_'], ['inputEncryptedChat', 'updateEncryption', 'updateEncryptedChatTyping', 'updateEncryptedMessagesRead', 'updateNewEncryptedMessage', 'encryptedMessage', 'encryptedMessageService'], true))
                || (isset($arguments['peer']) && \is_int($arguments['peer']) && DialogId::isSecretChat($arguments['peer']))
            )
        ) {
            if (isset($arguments['rich_message'])) {
                throw new Exception("Rich messages can't be sent to secret chats");
            }
            $method = 'messages.sendEncrypted';
            $arguments = ['peer' => $arguments['peer'], 'message' => $arguments];
            if (!isset($arguments['message']['_'])) {
                $arguments['message']['_'] = 'decryptedMessage';
            }
            if (!isset($arguments['message']['ttl'])) {
                $arguments['message']['ttl'] = 0;
            }
            if (isset($arguments['message']['reply_to_msg_id'])) {
                throw new Exception("reply_to_msg_id is deprecated, please use reply_to or the new sendMessage/sendVideo/etc... method instead!");
            } elseif (isset($arguments['message']['reply_to']['reply_to_msg_id'])) {
                $arguments['message']['reply_to_random_id'] = $arguments['message']['reply_to']['reply_to_msg_id'];
            }
        } elseif ($method === 'payments.exportInvoice') {
            if (isset($arguments['invoice_media']) && \is_array($arguments['invoice_media']) && isset($arguments['invoice_media']['_'])) {
                $this->API->processMedia($arguments['invoice_media'], $arguments['cancellation'] ?? null);
                if ($arguments['invoice_media']['_'] === 'inputMediaUploadedPhoto'
                    && (
                        $arguments['invoice_media']['file'] instanceof ReadableStream
                        || (
                            $arguments['invoice_media']['file'] instanceof FileCallbackInterface
                            && $arguments['invoice_media']['file']->getFile() instanceof ReadableStream
                        )
                    )
                ) {
                    if ($arguments['invoice_media']['file'] instanceof FileCallbackInterface) {
                        $arguments['invoice_media']['file'] = new FileCallback(
                            new ReadableBuffer(buffer($arguments['invoice_media']['file']->getFile(), $arguments['cancellation'] ?? null)),
                            $arguments['invoice_media']['file']
                        );
                    } else {
                        $arguments['invoice_media']['file'] = new ReadableBuffer(buffer($arguments['invoice_media']['file'], $arguments['cancellation'] ?? null));
                    }
                }
                $this->API->processMedia($arguments['invoice_media'], $arguments['cancellation'] ?? null, true);
            }
        } elseif ($method === 'messages.uploadMedia'
            || $method === 'messages.sendMedia'
            || $method === 'messages.editMessage'
            || $method === 'messages.editInlineBotMessage'
            || $method === 'messages.uploadImportedMedia'
            || $method === 'stories.sendStory'
            || $method === 'stories.editStory'
        ) {
            if ($method === 'messages.uploadMedia') {
                if (!isset($arguments['peer']) && $this->API->getSelf() && !$this->API->isSelfBot()) {
                    $arguments['peer'] = 'me';
                }
            }
            if (isset($arguments['media']) && \is_array($arguments['media']) && isset($arguments['media']['_'])) {
                $this->API->processMedia($arguments['media'], $arguments['cancellation'] ?? null);
                if ($arguments['media']['_'] === 'inputMediaUploadedPhoto'
                    && (
                        $arguments['media']['file'] instanceof ReadableStream
                        || (
                            $arguments['media']['file'] instanceof FileCallbackInterface
                            && $arguments['media']['file']->getFile() instanceof ReadableStream
                        )
                    )
                ) {
                    if ($arguments['media']['file'] instanceof FileCallbackInterface) {
                        $arguments['media']['file'] = new FileCallback(
                            new ReadableBuffer(buffer($arguments['media']['file']->getFile(), $arguments['cancellation'] ?? null)),
                            $arguments['media']['file']
                        );
                    } else {
                        $arguments['media']['file'] = new ReadableBuffer(buffer($arguments['media']['file'], $arguments['cancellation'] ?? null));
                    }
                }
                $this->API->processMedia($arguments['media'], $arguments['cancellation'] ?? null, true);
            }
        } elseif ($method === 'messages.sendMultiMedia') {
            if (isset($arguments['multi_media'])) {
                foreach ($arguments['multi_media'] as &$singleMedia) {
                    if (\is_string($singleMedia['media'])
                        || $singleMedia['media']['_'] === 'inputMediaUploadedPhoto'
                        || $singleMedia['media']['_'] === 'inputMediaUploadedDocument'
                        || $singleMedia['media']['_'] === 'inputMediaPhotoExternal'
                        || $singleMedia['media']['_'] === 'inputMediaDocumentExternal'
                    ) {
                        $singleMedia['media'] = $this->methodCallAsyncRead('messages.uploadMedia', ['peer' => $arguments['peer'], 'media' => $singleMedia['media'], 'cancellation' => $arguments['cancellation'] ?? null]);
                    }
                }
            }
        } elseif ($method === 'messages.sendEncryptedFile' || $method === 'messages.uploadEncryptedFile') {
            if (isset($arguments['file'])) {
                if (
                    (
                        !\is_array($arguments['file'])
                        || !(
                            isset($arguments['file']['_'])
                            && $this->API->getTL()->getConstructors()->findByPredicate($arguments['file']['_'])['type'] === 'InputEncryptedFile'
                        )
                    )
                    && $this->API->getSettings()->getFiles()->getAllowAutomaticUpload()
                ) {
                    $arguments['file'] = ($this->API->uploadEncrypted($arguments['file'], cancellation: $arguments['cancellation'] ?? null));
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
            return;
        } elseif (\in_array($method, ['messages.addChatUser', 'messages.deleteChatUser', 'messages.editChatAdmin', 'messages.editChatPhoto', 'messages.editChatTitle', 'messages.getFullChat', 'messages.exportChatInvite', 'messages.editChatAdmin', 'messages.migrateChat'], true) && isset($arguments['chat_id']) && (!is_numeric($arguments['chat_id']) || $arguments['chat_id'] < 0)) {
            $res = $this->API->getInfo($arguments['chat_id']);
            if ($res['type'] !== 'chat') {
                throw new Exception('chat_id is not a chat id (only normal groups allowed, not supergroups)!');
            }
            $arguments['chat_id'] = -$res['chat_id'];
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
                if (\is_array($arguments['file']) && !\in_array($arguments['file']['_'], ['inputFile', 'inputFileBig'], true)) {
                    $method = 'photos.uploadProfilePhoto';
                    $arguments['id'] = $arguments['file'];
                }
            } elseif (isset($arguments['id'])) {
                $method = 'photos.updateProfilePhoto';
            }
        } elseif ($method === 'channels.deleteUserHistory') {
            $method = 'channels.deleteParticipantHistory';
            if (isset($arguments['user_id'])) {
                $arguments['participant'] = $arguments['user_id'];
            }
        } elseif ($method === 'messages.getChatInviteImporters') {
            if (isset($arguments['offset_user'])) {
                if (!isset($arguments['offset_date'])) {
                    throw new TLException(Lang::$current_lang['params_missing'].' offset_user');
                }
            }
        } elseif ($method === 'stories.getAllReadUserStories') {
            $method = 'stories.getAllReadPeerStories';
        } elseif ($method === 'stories.getUserStories') {
            $method = 'stories.getPeerStories';
            if (isset($arguments['user_id'])) {
                $arguments['peer'] = $arguments['user_id'];
            }
        } elseif ($method === 'users.getStoriesMaxIDs') {
            $method = 'stories.getPeerMaxIDs';
        } elseif (\in_array($method, ['channels.createForumTopic', 'channels.editForumTopic', 'channels.deleteTopicHistory', 'channels.getForumTopics', 'channels.getForumTopicsByID', 'channels.updatePinnedForumTopic', 'channels.reorderPinnedForumTopics'], true)) {
            // Moved to messages.* (forums of private chats with bots too), with a peer.
            $method = 'messages.'.substr($method, \strlen('channels.'));
            if (isset($arguments['channel'])) {
                $arguments['peer'] ??= $arguments['channel'];
                unset($arguments['channel']);
            }
        } elseif ($method === 'contacts.toggleStoriesHidden') {
            $method = 'stories.togglePeerStoriesHidden';
            if (isset($arguments['id'])) {
                $arguments['peer'] = $arguments['id'];
            }
        }
        if (isset($arguments['reply_to_msg_id'])) {
            throw new Exception("reply_to_msg_id is deprecated, please use reply_to or the new sendMessage/sendVideo/etc... methods instead!");
        }
    }
    /**
     * Send an MTProto message.
     */
    public function sendMessage(MTProtoOutgoingMessage $message): void
    {
        if ($this->closed) {
            $this->handOver($message);
            return;
        }
        $message->connection = $this;
        $message->moveFileRoom($this);
        $message->trySend();
        if (!$message->hasSerializedBody()) {
            $body = $message->getBody();
            if ($message->isMethod) {
                $resumableCall = $body['resumableCall'] ?? null;
                $referenceDatabase = $this->API->referenceDatabase;
                if ($referenceDatabase === null) {
                    $body = $this->API->getTL()->serializeMethod($message->constructor, $body);
                } else {
                    [$body, $message->sentFileReferences] = $referenceDatabase->serializeCall(
                        fn () => $this->API->getTL()->serializeMethod($message->constructor, $body),
                    );
                }
                if ($resumableCall !== null) {
                    // Its files were uploaded while serializing it.
                    $this->API->resumableCallSent($resumableCall);
                }
                if ($message->takeoutId !== null) {
                    $body = $this->API->getTL()->serializeMethod(
                        'invokeWithTakeout',
                        ['takeout_id' => $message->takeoutId, 'query' => $body],
                    );
                } elseif ($message->businessConnectionId !== null) {
                    $body = $this->API->getTL()->serializeMethod(
                        'invokeWithBusinessConnection',
                        ['connection_id' => $message->businessConnectionId, 'query' => $body],
                    );
                }
            } else {
                $body['_'] = $message->constructor;
                $body = $this->API->getTL()->serializeObject(['type' => ''], $body, $message->constructor);
            }
            $message->setSerializedBody($body);
            unset($body);
        }
        if ($this->stream === null) {
            // Queued, and sent once connected: connecting here made each sender wait for (and make) its own attempts,
            // and threw out of requests made in the background (polls, cancellations) when every way to connect
            // failed (the network is down), killing the event loop of the script.
            $this->createSession();
            $this->connectInBackground();
        }
        if ($message->hasReply()) {
            // Replied to (e.g. cancelled) while connecting: its body was already freed, and it must not be sent.
            return;
        }
        $this->pendingOutgoingGauge?->inc();
        if ($message->unencrypted) {
            $this->unencryptedPendingOutgoing->enqueue($message);
        } elseif ($message->specialMethodType === SpecialMethodType::UNAUTHED_METHOD) {
            $this->uninitedPendingOutgoing->enqueue($message);
        } else {
            $this->mainPendingOutgoing->enqueue($message);
        }
        $this->flush();
    }
    /**
     * Flush pending packets.
     */
    public function flush(bool $postpone = false): void
    {
        if (isset($this->writer)) {
            $this->writer->resume($postpone);
        }
    }
    /**
     * Connect main instance.
     *
     * @param DataCenterConnection $extra Shared instance
     * @param int                  $id    Connection ID
     *
     * @psalm-external-mutation-free
     */
    public function setExtra(DataCenterConnection $extra, int $datacenter, int $id): void
    {
        $this->shared = $extra;
        $this->id = $id;
        $this->API = $extra->getExtra();
        $this->datacenter = $datacenter;
        $this->datacenterId = $this->datacenter . '.' . $this->id;
    }
    /**
     * Get main instance.
     */
    public function getExtra(): MTProto
    {
        return $this->API;
    }
    /**
     * Get shared connection instance.
     */
    public function getShared(): DataCenterConnection
    {
        return $this->shared;
    }
    /**
     * Disconnect from DC.
     *
     * @param bool $temporary Whether the disconnection is temporary, triggered by the reconnect method
     */
    public function disconnect(bool $temporary = false): void
    {
        $this->generation++;
        $this->API->logger("Disconnecting from DC {$this->datacenterId}");
        $this->needsReconnect = true;
        if (!$temporary) {
            $this->closed = true;
            $this->closing?->cancel();
        }
        if ($this->stream) {
            try {
                $stream = $this->stream;
                $this->stream = null;
                $stream->disconnect();
            } catch (ClosedException $e) {
                $this->API->logger($e);
            }
        }

        $this->stopLoops();

        if (!$temporary) {
            $this->shared->signalDisconnect($this->id);
        }
        $this->API->logger("Disconnected from DC {$this->datacenterId}");
    }
    private function stopLoops(): void
    {
        $this->reader?->stop();
        $this->writer?->stop();
        $this->pinger?->stop();
        $this->cleanup?->stop();
    }
    /**
     * Sends a message of this connection, closed for good, on a connection of the DC (as its pending messages were
     * when it was closed): connecting it again would run loops nobody stops, on a session nobody uses.
     */
    private function handOver(MTProtoOutgoingMessage $message): void
    {
        if ($message->hasReply() || !$message->resetForResend()) {
            return;
        }
        $this->API->logger("The connection to DC {$this->datacenterId} is closed, sending $message on another one", Logger::NOTICE);
        // In the background: the DC may have no connection until it's connected again (or never, if it was closed
        // for good too), while the sender waits for the message to be sent within its timeout.
        EventLoop::queue(function () use ($message): void {
            $this->shared->waitGetConnection()->sendMessage($message);
        });
    }
    /**
     * Reconnect to DC, trying again until connected or closed for good.
     */
    public function reconnect(): void
    {
        if ($this->closed) {
            $this->API->logger("Not reconnecting DC {$this->datacenterId}, closed");
            return;
        }
        $this->API->logger("Reconnecting DC {$this->datacenterId}");
        $this->disconnect(true);
        if ($this->closed) {
            // Closed for good while disconnecting (logging can suspend).
            return;
        }
        $this->shared->connect($this->id);
        // Reconnections run in the background (a dead connection, a server error): every way to connect failing
        // (the network is down) threw out of them as an unhandled exception, killing the event loop of a script after
        // a minute of outage. The attempts already running (started by a message that couldn't be sent, or by a
        // concurrent reconnection) connect this disconnected connection too.
        $this->connectInBackground()->await();
    }
    /**
     * Get name.
     *
     * @psalm-pure
     */
    public function getName(): string
    {
        return self::class;
    }
}
