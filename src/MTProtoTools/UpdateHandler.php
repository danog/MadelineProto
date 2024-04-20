<?php

declare(strict_types=1);

/**
 * UpdateHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\TimeoutException;
use AssertionError;
use danog\AsyncOrm\Annotations\OrmMappedArray;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\BotCommands;
use danog\MadelineProto\EventHandler\Channel\ChannelParticipant;
use danog\MadelineProto\EventHandler\Channel\MessageForwards;
use danog\MadelineProto\EventHandler\Channel\MessageViewsChanged;
use danog\MadelineProto\EventHandler\Channel\UpdateChannel;
use danog\MadelineProto\EventHandler\ChatInviteRequester\BotChatInviteRequest;
use danog\MadelineProto\EventHandler\ChatInviteRequester\PendingJoinRequests;
use danog\MadelineProto\EventHandler\Delete\DeleteChannelMessages;
use danog\MadelineProto\EventHandler\Delete\DeleteMessages;
use danog\MadelineProto\EventHandler\Delete\DeleteScheduledMessages;
use danog\MadelineProto\EventHandler\InlineQuery;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\ChannelMessage;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Message\SecretMessage;
use danog\MadelineProto\EventHandler\Message\Service\DialogBotAllowed;
use danog\MadelineProto\EventHandler\Message\Service\DialogChannelCreated;
use danog\MadelineProto\EventHandler\Message\Service\DialogChannelMigrateFrom;
use danog\MadelineProto\EventHandler\Message\Service\DialogChatJoinedByLink;
use danog\MadelineProto\EventHandler\Message\Service\DialogChatMigrateTo;
use danog\MadelineProto\EventHandler\Message\Service\DialogContactSignUp;
use danog\MadelineProto\EventHandler\Message\Service\DialogCreated;
use danog\MadelineProto\EventHandler\Message\Service\DialogDeleteMessages;
use danog\MadelineProto\EventHandler\Message\Service\DialogGameScore;
use danog\MadelineProto\EventHandler\Message\Service\DialogGeoProximityReached;
use danog\MadelineProto\EventHandler\Message\Service\DialogGiftPremium;
use danog\MadelineProto\EventHandler\Message\Service\DialogGroupCall\GroupCall;
use danog\MadelineProto\EventHandler\Message\Service\DialogGroupCall\GroupCallInvited;
use danog\MadelineProto\EventHandler\Message\Service\DialogGroupCall\GroupCallScheduled;
use danog\MadelineProto\EventHandler\Message\Service\DialogHistoryCleared;
use danog\MadelineProto\EventHandler\Message\Service\DialogMemberJoinedByRequest;
use danog\MadelineProto\EventHandler\Message\Service\DialogMemberLeft;
use danog\MadelineProto\EventHandler\Message\Service\DialogMembersJoined;
use danog\MadelineProto\EventHandler\Message\Service\DialogMessagePinned;
use danog\MadelineProto\EventHandler\Message\Service\DialogPeerRequested;
use danog\MadelineProto\EventHandler\Message\Service\DialogPhoneCall;
use danog\MadelineProto\EventHandler\Message\Service\DialogPhotoChanged;
use danog\MadelineProto\EventHandler\Message\Service\DialogReadMessages;
use danog\MadelineProto\EventHandler\Message\Service\DialogScreenshotTaken;
use danog\MadelineProto\EventHandler\Message\Service\DialogSetChatTheme;
use danog\MadelineProto\EventHandler\Message\Service\DialogSetChatWallPaper;
use danog\MadelineProto\EventHandler\Message\Service\DialogSetTTL;
use danog\MadelineProto\EventHandler\Message\Service\DialogSuggestProfilePhoto;
use danog\MadelineProto\EventHandler\Message\Service\DialogTitleChanged;
use danog\MadelineProto\EventHandler\Message\Service\DialogTopicCreated;
use danog\MadelineProto\EventHandler\Message\Service\DialogTopicEdited;
use danog\MadelineProto\EventHandler\Message\Service\DialogWebView;
use danog\MadelineProto\EventHandler\Pinned;
use danog\MadelineProto\EventHandler\Pinned\PinnedChannelMessages;
use danog\MadelineProto\EventHandler\Pinned\PinnedGroupMessages;
use danog\MadelineProto\EventHandler\Pinned\PinnedPrivateMessages;
use danog\MadelineProto\EventHandler\Privacy;
use danog\MadelineProto\EventHandler\Query\ChatButtonQuery;
use danog\MadelineProto\EventHandler\Query\ChatGameQuery;
use danog\MadelineProto\EventHandler\Query\InlineButtonQuery;
use danog\MadelineProto\EventHandler\Query\InlineGameQuery;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\EventHandler\Story\StoryDeleted;
use danog\MadelineProto\EventHandler\Story\StoryReaction;
use danog\MadelineProto\EventHandler\Typing\ChatUserTyping;
use danog\MadelineProto\EventHandler\Typing\SecretUserTyping;
use danog\MadelineProto\EventHandler\Typing\SupergroupUserTyping;
use danog\MadelineProto\EventHandler\Typing\UserTyping;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\EventHandler\User\Blocked;
use danog\MadelineProto\EventHandler\User\BotStopped;
use danog\MadelineProto\EventHandler\User\Phone;
use danog\MadelineProto\EventHandler\User\Status;
use danog\MadelineProto\EventHandler\User\Status\Emoji;
use danog\MadelineProto\EventHandler\User\Username;
use danog\MadelineProto\EventHandler\Wallpaper;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\ResponseException;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\Types\Button;
use danog\MadelineProto\UpdateHandlerType;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIPController;
use Revolt\EventLoop;
use SplQueue;
use Throwable;
use Webmozart\Assert\Assert;
use function Amp\delay;

/**
 * Manages updates.
 *
 * @property Settings $settings Settings
 * @property TL       $TL       TL
 *
 * @internal
 */
trait UpdateHandler
{
    private UpdateHandlerType $updateHandlerType = UpdateHandlerType::NOOP;

    private bool $got_state = false;
    /** @deprecated */
    private CombinedUpdatesState $channels_state;
    private CombinedUpdatesState $updateState;
    /** @var DbArray<int, array> */
    #[OrmMappedArray(KeyType::INT, ValueType::SCALAR)]
    private $getUpdatesQueue;
    private int $getUpdatesQueueKey = 0;
    private SplQueue $updateQueue;

    /**
     * Set NOOP update handler, ignoring all updates.
     */
    public function setNoop(): void
    {
        $this->updateHandlerType = UpdateHandlerType::NOOP;
        $this->getUpdatesQueue->clear();
        $this->getUpdatesQueueKey = 0;
        $q = new SplQueue;
        $q->setIteratorMode(SplQueue::IT_MODE_DELETE);
        $this->updateQueue = $q;
        $this->event_handler = null;
        $this->event_handler_instance = null;
        $this->eventHandlerMethods = [];
        $this->eventHandlerHandlers = [];
        $this->pluginInstances = [];
        $this->startUpdateSystem();
    }
    /**
     * PWRTelegram webhook URL.
     */
    private ?string $webhookUrl = null;
    /**
     * Set webhook update handler.
     *
     * @param string $webhookUrl Webhook URL
     */
    public function setWebhook(string $webhookUrl): void
    {
        $this->webhookUrl = $webhookUrl;
        $this->updateHandlerType = UpdateHandlerType::WEBHOOK;
        foreach ($this->getUpdatesQueue as $update) {
            $this->handleUpdate($update);
        }
        $this->getUpdatesQueue->clear();
        $this->getUpdatesQueueKey = 0;
        foreach ($this->updateQueue as $update) {
            $this->handleUpdate($update);
        }
        $this->event_handler = null;
        $this->event_handler_instance = null;
        $this->eventHandlerMethods = [];
        $this->eventHandlerHandlers = [];
        $this->pluginInstances = [];
        $this->startUpdateSystem();
    }

    /**
     * Event update handler.
     *
     * @param array $update Update
     */
    private function eventUpdateHandler(array $update): void
    {
        $updateType = $update['_'];
        if ($f = $this->event_handler_instance->waitForInternalStart()) {
            $this->logger->logger("Postponing update handling, onStart is still running (if stuck here for too long, make sure to fork long-running tasks in onStart using \$this->callFork(function () { ... }) to fix this)...", Logger::NOTICE);
            $this->updateQueue->enqueue($update);
            $f->map(function (): void {
                foreach ($this->updateQueue as $update) {
                    $this->handleUpdate($update);
                }
            });
            return;
        }
        if (\count($this->eventHandlerHandlers) !== 0 && \is_array($update)) {
            $obj = $this->wrapUpdate($update);
            if ($obj !== null) {
                foreach ($this->eventHandlerHandlers as $closure) {
                    EventLoop::queue($this->rethrowHandler, $closure, $obj);
                }
            }
        }
        if ($updateType === 'updateBroadcastProgress') {
            $update = $update['progress'];
        }
        if (isset($this->eventHandlerMethods[$updateType])) {
            foreach ($this->eventHandlerMethods[$updateType] as $closure) {
                EventLoop::queue($this->rethrowHandler, $closure, $update);
            }
        }
    }

    private function rethrowUpdateHandler(\Closure $closure, mixed $update): void
    {
        try {
            $closure($update);
        } catch (\Throwable $e) {
            $this->rethrowInner($e);
        }
    }

    /**
     * Send update to webhook.
     *
     * @param array $update Update
     */
    private function pwrWebhook(array $update): void
    {
        $payload = json_encode($update);
        Assert::notEmpty($payload);
        Assert::notNull($this->webhookUrl);
        $request = new Request($this->webhookUrl, 'POST');
        $request->setHeader('content-type', 'application/json');
        $request->setBody($payload);
        try {
            /** @var Response */
            $result = $this->datacenter->getHTTPClient()->request($request);
            $status = $result->getStatus();
            if ($status !== 200) {
                throw new Exception("Got bad status $status!");
            }
        } catch (Throwable $e) {
            $this->logger->logger("Got {$e->getMessage()} while sending webhook", Logger::FATAL_ERROR);
            $this->updateQueue->enqueue($update);
            delay(1.0);
            foreach ($this->updateQueue as $update) {
                $this->handleUpdate($update);
            }
        }
    }

    private ?DeferredFuture $update_deferred = null;
    /**
     * Signal update.
     *
     * @internal
     */
    private function addGetUpdatesUpdate($update): void
    {
        $this->getUpdatesQueue[$this->getUpdatesQueueKey++] = $update;
        if ($this->update_deferred) {
            $deferred = $this->update_deferred;
            $this->update_deferred = null;
            $deferred->complete();
        }
    }

    private bool $usingGetUpdates = false;
    private static ?TimeoutException $getUpdatesTimeout = null;
    /**
     * Only useful when consuming MadelineProto updates through an API in another language (like Javascript), **absolutely not recommended when directly writing MadelineProto bots**.
     *
     * `getUpdates` will **greatly slow down your bot** if used directly inside of PHP code.
     *
     * **Only use the [event handler](#async-event-driven) when writing a MadelineProto bot**, because update handling in the **event handler** is completely parallelized and non-blocking.
     *
     * @param  array{offset?: int, limit?: int, timeout?: float} $params Params
     * @return list<array{update_id: mixed, update: mixed}>
     */
    public function getUpdates(array $params = []): array
    {
        if ($this->usingGetUpdates) {
            throw new AssertionError("Concurrent getUpdates detected, aborting!");
        }
        $id = null;
        try {
            $this->usingGetUpdates = true;
            if ($this->updateHandlerType !== UpdateHandlerType::GET_UPDATES) {
                $this->updateHandlerType = UpdateHandlerType::GET_UPDATES;
                $this->event_handler = null;
                $this->event_handler_instance = null;
                $this->eventHandlerMethods = [];
                $this->eventHandlerHandlers = [];
                $this->pluginInstances = [];
            }
            foreach ($this->updateQueue as $update) {
                $this->addGetUpdatesUpdate($update);
            }

            [
                'offset' => $offset,
                'limit' => $limit,
                'timeout' => $timeout
            ] = array_merge(['offset' => 0, 'limit' => null, 'timeout' => 10.0], $params);

            if ($offset < 0) {
                $offset = $this->getUpdatesQueueKey+$offset;
            }

            Assert::nullOrPositiveInteger($limit);

            if ($timeout === INF) {
                $timeout = null;
            } elseif ($timeout !== null) {
                self::$getUpdatesTimeout ??= new TimeoutException("Operation timed out");
                $deferred = new DeferredCancellation;
                $id = EventLoop::delay($timeout, static fn () => $deferred->cancel(self::$getUpdatesTimeout));
                $timeout = $deferred->getCancellation();
            }

            do {
                if (!$this->getUpdatesQueue->count()) {
                    try {
                        $this->update_deferred = new DeferredFuture();
                        $this->update_deferred->getFuture()->await($timeout);
                    } catch (CancelledException $e) {
                        if (!$e->getPrevious() instanceof TimeoutException) {
                            throw $e;
                        }
                    }
                }
                $updates = [];
                foreach ($this->getUpdatesQueue as $key => $value) {
                    if ($offset > $key) {
                        unset($this->getUpdatesQueue[$key]);
                        continue;
                    }

                    $updates[$key] = ['update_id' => $key, 'update' => $value];

                    if ($limit !== null && \count($updates) === $limit) {
                        break;
                    }
                }
                if ($updates) {
                    ksort($updates);
                    return array_values($updates);
                }
            } while (!$timeout?->isRequested());
            return [];
        } finally {
            $this->usingGetUpdates = false;
            if ($id !== null && !$timeout->isRequested()) {
                EventLoop::cancel($id);
            }
        }
    }
    /**
     * Check message ID.
     *
     * @param array $message Message
     * @internal
     */
    public function checkMsgId(array $message): bool
    {
        if (!isset($message['peer_id'])) {
            return true;
        }
        $peer_id = $message['peer_id'];
        $message_id = $message['id'];
        if (!isset($this->msg_ids[$peer_id]) || $message_id > $this->msg_ids[$peer_id]) {
            $this->msg_ids[$peer_id] = $message_id;
            return true;
        }
        return false;
    }
    /**
     * Get channel state.
     *
     * @internal
     */
    public function loadUpdateState()
    {
        if (!$this->got_state) {
            $this->updateState->get(0, $this->getUpdatesState());
            $this->got_state = true;
        }
        return $this->updateState->get(0);
    }
    /**
     * Load channel state.
     *
     * @param null|int $channelId Channel ID
     * @param array    $init      Init
     * @internal
     * @return UpdatesState|array<UpdatesState>
     */
    public function loadChannelState(?int $channelId = null, array $init = []): UpdatesState|array
    {
        return $this->updateState->get($channelId, $init);
    }
    /**
     * Get channel states.
     *
     * @internal
     */
    public function getChannelStates(): CombinedUpdatesState
    {
        return $this->updateState;
    }
    /**
     * Get update state.
     *
     * @internal
     */
    private function getUpdatesState()
    {
        $data = $this->methodCallAsyncRead('updates.getState', []);
        $this->getCdnConfig();
        return $data;
    }
    /**
     * Wrap an Update constructor into an abstract Update object.
     */
    public function wrapUpdate(array $update): ?Update
    {
        try {
            return match ($update['_']) {
                'updateNewChannelMessage', 'updateNewMessage', 'updateNewScheduledMessage', 'updateEditMessage', 'updateEditChannelMessage','updateNewEncryptedMessage','updateNewOutgoingEncryptedMessage' => $this->wrapMessage($update['message'], $update['_'] === 'updateNewScheduledMessage'),
                'updatePinnedMessages', 'updatePinnedChannelMessages' => $this->wrapPin($update),
                'updateBotCallbackQuery' => isset($update['game_short_name'])
                    ? new ChatGameQuery($this, $update)
                    : new ChatButtonQuery($this, $update),
                'updateInlineBotCallbackQuery' => isset($update['game_short_name'])
                    ? new InlineGameQuery($this, $update)
                    : new InlineButtonQuery($this, $update),
                'updateBotInlineQuery' => new InlineQuery($this, $update),
                'updatePhoneCall' => $update['phone_call'],
                'updateBroadcastProgress' => $update['progress'],
                'updateStory' => $update['story']['_'] === 'storyItemDeleted'
                    ? new StoryDeleted($this, $update)
                    : new Story($this, $update),
                'updateSentStoryReaction'       => new StoryReaction($this, $update),
                'updateUserStatus'              => Status::fromRawStatus($this, $update),
                'updatePeerBlocked'             => new Blocked($this, $update),
                'updateBotStopped'              => new BotStopped($this, $update),
                'updateUserEmojiStatus'         => new Emoji($this, $update),
                'updateUserName'                => new Username($this, $update),
                'updateUserPhone'               => new Phone($this, $update),
                'updatePrivacy'                 => new Privacy($this, $update),
                'updateUserTyping'              => new UserTyping($this, $update),
                'updateChannelUserTyping'       => new SupergroupUserTyping($this, $update),
                'updateChatUserTyping'          => new ChatUserTyping($this, $update),
                'updateChannel'                 => new UpdateChannel($this, $update),
                'updateChannelMessageViews'     => new MessageViewsChanged($this, $update),
                'updateChannelMessageForwards'  => new MessageForwards($this, $update),
                'updateChannelParticipant'      => new ChannelParticipant($this, $update),
                'updateDeleteMessages'          => new DeleteMessages($this, $update),
                'updateDeleteChannelMessages'   => new DeleteChannelMessages($this, $update),
                'updateDeleteScheduledMessages' => new DeleteScheduledMessages($this, $update),
                'updatePendingJoinRequests'     => new PendingJoinRequests($this, $update),
                'updateBotChatInviteRequester'  => new BotChatInviteRequest($this, $update),
                'updateBotCommands'             => new BotCommands($this, $update),
                default => null
            };
        } catch (\Throwable $e) {
            $update = json_encode($update);
            $this->logger->logger("An error occured while wrapping $update: $e", Logger::FATAL_ERROR);
            $this->report("An error occured while wrapping $update: $e");
            return null;
        }
    }
    /**
     * Wrap a Pin constructor into an abstract Pinned object.
     */
    public function wrapPin(array $message): ?Pinned
    {
        return match ($this->getInfo($message)['type']) {
            API::PEER_TYPE_BOT, API::PEER_TYPE_USER => new PinnedPrivateMessages($this, $message),
            API::PEER_TYPE_GROUP, API::PEER_TYPE_SUPERGROUP => new PinnedGroupMessages($this, $message),
            API::PEER_TYPE_CHANNEL => new PinnedChannelMessages($this, $message),
            default => null,
        };
    }
    /**
     * Wrap a Message constructor into an abstract Message object.
     */
    public function wrapMessage(array $message, bool $scheduled = false): ?AbstractMessage
    {
        if ($message['_'] === 'messageEmpty') {
            return null;
        }
        $info = $this->getInfo($message);
        if ($message['_'] === 'messageService') {
            return match ($message['action']['_']) {
                'messageActionBotAllowed' => new DialogBotAllowed(
                    $this,
                    $message,
                    $info,
                ),
                'messageActionHistoryClear' => new DialogHistoryCleared(
                    $this,
                    $message,
                    $info,
                ),
                'messageActionContactSignUp' => new DialogContactSignUp(
                    $this,
                    $message,
                    $info,
                ),
                'messageActionChatJoinedByRequest' => new DialogMemberJoinedByRequest(
                    $this,
                    $message,
                    $info,
                ),
                'messageActionChatAddUser' => new DialogMembersJoined(
                    $this,
                    $message,
                    $info,
                    $message['action']['users']
                ),
                'messageActionChatDeleteUser' => new DialogMemberLeft(
                    $this,
                    $message,
                    $info,
                    $message['action']['user_id']
                ),

                'messageActionChatJoinedByLink' => new DialogChatJoinedByLink(
                    $this,
                    $message,
                    $info,
                    $message['action']['inviter_id'],
                ),
                'messageActionChatCreate' => new DialogCreated(
                    $this,
                    $message,
                    $info,
                    $message['action']['title'],
                    $message['action']['users'],
                ),
                'messageActionChatEditTitle' => new DialogTitleChanged(
                    $this,
                    $message,
                    $info,
                    $message['action']['title']
                ),
                'messageActionChatEditPhoto' => new DialogPhotoChanged(
                    $this,
                    $message,
                    $info,
                    $this->wrapMedia($message['action']['photo'])
                ),
                'messageActionChatDeletePhoto' => new DialogPhotoChanged(
                    $this,
                    $message,
                    $info,
                    null
                ),
                'messageActionPinMessage' => new DialogMessagePinned(
                    $this,
                    $message,
                    $info,
                ),
                'messageActionScreenshotTaken' => new DialogScreenshotTaken(
                    $this,
                    $message,
                    $info,
                ),
                'messageActionChannelCreate' => new DialogChannelCreated(
                    $this,
                    $message,
                    $info,
                    $message['action']['title'],
                ),
                'messageActionChatMigrateTo' => new DialogChatMigrateTo(
                    $this,
                    $message,
                    $info,
                    $message['action']['channel_id'],
                ),
                'messageActionChannelMigrateFrom' => new DialogChannelMigrateFrom(
                    $this,
                    $message,
                    $info,
                    $message['action']['title'],
                    $message['action']['chat_id'],
                ),
                'messageActionGameScore' => new DialogGameScore(
                    $this,
                    $message,
                    $info,
                    $message['action']['game_id'],
                    $message['action']['score'],
                ),
                'messageActionGeoProximityReached' => new DialogGeoProximityReached(
                    $this,
                    $message,
                    $info,
                    $this->getIdInternal($message['action']['from_id']),
                    $this->getIdInternal($message['action']['to_id']),
                    $message['action']['distance'],
                ),
                'messageActionPhoneCall' => new DialogPhoneCall(
                    $this,
                    $message,
                    $info,
                    $message['action']['video'],
                    $message['action']['call_id'],
                    DiscardReason::tryfrom($message['action']['reason']['_'] ?? ''),
                    $message['action']['duration'] ?? null,
                ),
                'messageActionGroupCall' => new GroupCall(
                    $this,
                    $message,
                    $info,
                    $message['action']['duration'] ?? null,
                ),
                'messageActionInviteToGroupCall' => new GroupCallInvited(
                    $this,
                    $message,
                    $info,
                    $message['action']['users'],
                ),
                'messageActionGroupCallScheduled' => new GroupCallScheduled(
                    $this,
                    $message,
                    $info,
                    $message['action']['schedule_date'],
                ),
                'messageActionSetMessagesTTL' => new DialogSetTTL(
                    $this,
                    $message,
                    $info,
                    $message['action']['period'],
                    $message['action']['auto_setting_from'] ?? null,
                ),
                'messageActionSetChatTheme' => new DialogSetChatTheme(
                    $this,
                    $message,
                    $info,
                    $message['action']['emoticon'],
                ),
                'messageActionWebViewDataSentMe' => new DialogWebView(
                    $this,
                    $message,
                    $info,
                    $message['action']['text'],
                    $message['action']['data'],
                ),
                'messageActionWebViewDataSent' => new DialogWebView(
                    $this,
                    $message,
                    $info,
                    $message['action']['text'],
                    null
                ),
                'messageActionGiftPremium' => new DialogGiftPremium(
                    $this,
                    $message,
                    $info,
                    $message['action']['currency'],
                    $message['action']['amount'],
                    $message['action']['months'],
                    $message['action']['crypto_currency'] ?? null,
                    $message['action']['crypto_amount'] ?? null,
                ),
                'messageActionTopicCreate' => new DialogTopicCreated(
                    $this,
                    $message,
                    $info,
                    $message['action']['title'],
                    $message['action']['icon_color'],
                    $message['action']['icon_emoji_id'] ?? null,
                ),
                'messageActionTopicEdit' => new DialogTopicEdited(
                    $this,
                    $message,
                    $info,
                    $message['action']['title'] ?? null,
                    $message['action']['icon_emoji_id'] ?? null,
                    $message['action']['closed'] ?? null,
                    $message['action']['hidden'] ?? null,
                ),
                'messageActionSuggestProfilePhoto' => new DialogSuggestProfilePhoto(
                    $this,
                    $message,
                    $info,
                    $this->wrapMedia($message['action']['photo'])
                ),
                'messageActionRequestedPeerSentMe' => new DialogPeerRequested(
                    $this,
                    $message,
                    $info,
                    $message['action']['button_id'],
                    $message['action']['peers'],
                ),
                'messageActionSetChatWallPaper' => new DialogSetChatWallPaper(
                    $this,
                    $message,
                    $info,
                    new Wallpaper($this, $message['action']['wallpaper']),
                ),
                'messageActionSetSameChatWallPaper' => new DialogSetChatWallPaper(
                    $this,
                    $message,
                    $info,
                    new Wallpaper($this, $message['action']['wallpaper']),
                    true
                ),
                default => null
            };
        }
        if ($message['_'] === 'encryptedMessageService') {
            $action = $message['decrypted_message']['action'];
            return match ($action['_']) {
                'decryptedMessageActionSetMessageTTL' => new DialogSetTTL(
                    $this,
                    $message,
                    $info,
                    $action['ttl_seconds'],
                    null,
                ),
                'decryptedMessageActionReadMessages' => new DialogReadMessages(
                    $this,
                    $message,
                    $info,
                    $action['random_ids'],
                ),
                'decryptedMessageActionDeleteMessages' => new DialogDeleteMessages(
                    $this,
                    $message,
                    $info,
                    $action['random_ids'],
                ),
                'decryptedMessageActionScreenshotMessages' => new DialogScreenshotTaken(
                    $this,
                    $message,
                    $info,
                    $action['random_ids'],
                ),
                'decryptedMessageActionFlushHistory' => new DialogHistoryCleared(
                    $this,
                    $message,
                    $info,
                ),
                'decryptedMessageActionTyping' => new SecretUserTyping(
                    $this,
                    $message,
                    $info,
                ),
                default => null
            };
        }
        if (($info['User']['username'] ?? '') === 'replies') {
            return null;
        }
        if ($message['_'] === 'encryptedMessage') {
            return new SecretMessage($this, $message, $info, $scheduled);
        }
        return match ($info['type']) {
            API::PEER_TYPE_BOT, API::PEER_TYPE_USER => new PrivateMessage($this, $message, $info, $scheduled),
            API::PEER_TYPE_GROUP, API::PEER_TYPE_SUPERGROUP => new GroupMessage($this, $message, $info, $scheduled),
            API::PEER_TYPE_CHANNEL => new ChannelMessage($this, $message, $info, $scheduled),
        };
    }
    /**
     * Sends a message.
     *
     * @param integer|string $peer                   Destination peer or username.
     * @param string         $message                Message to send
     * @param ParseMode      $parseMode              Parse mode
     * @param integer|null   $replyToMsgId           ID of message to reply to.
     * @param integer|null   $topMsgId               ID of thread where to send the message.
     * @param array|null     $replyMarkup            Keyboard information.
     * @param integer|null   $sendAs                 Peer to send the message as.
     * @param integer|null   $scheduleDate           Schedule date.
     * @param boolean        $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean        $background             Send this message as background message
     * @param boolean        $clearDraft             Clears the draft field
     * @param boolean        $noWebpage              Set this flag to disable generation of the webpage preview
     * @param boolean        $updateStickersetsOrder Whether to move used stickersets to top
     * @param ?Cancellation  $cancellation           Cancellation
     */
    public function sendMessage(
        int|string $peer,
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $noWebpage = false,
        bool $updateStickersetsOrder = false,
        ?Cancellation $cancellation = null
    ): Message {
        $result = $this->methodCallAsyncRead(
            'messages.sendMessage',
            [
                'peer' => $peer,
                'message' => $message,
                'parse_mode' => $parseMode,
                'reply_to_msg_id' => $replyToMsgId,
                'top_msg_id' => $topMsgId,
                'reply_markup' => $replyMarkup,
                'send_as' => $sendAs,
                'schedule_date' => $scheduleDate,
                'silent' => $silent,
                'noforwards' => $noForwards,
                'background' => $background,
                'clear_draft' => $clearDraft,
                'no_webpage' => $noWebpage,
                'update_stickersets_order' => $updateStickersetsOrder,
                'cancellation' => $cancellation,
            ]
        );
        if (isset($result['_'])) {
            return $this->wrapMessage($this->extractMessage($result));
        }

        $last = null;
        foreach ($result as $updates) {
            /** @var Message */
            $new = $this->wrapMessage($this->extractMessage($updates));
            if ($last) {
                $last->nextSent = $new;
            } else {
                $first = $new;
            }
            $last = $new;
        }
        return $first;
    }
    /**
     * Extract a message ID from an Updates constructor.
     */
    public function extractMessageId(array $updates): int
    {
        return $this->extractMessage($updates)['id'];
    }
    /**
     * Extract a message constructor from an Updates constructor.
     */
    public function extractMessage(array $updates): array
    {
        return ($this->extractMessageUpdate($updates))['message'];
    }
    /**
     * Extract an update message constructor from an Updates constructor.
     */
    public function extractMessageUpdate(array $updates): array
    {
        $result = null;
        foreach (($this->extractUpdates($updates)) as $update) {
            if (\in_array($update['_'], ['updateNewMessage', 'updateNewChannelMessage', 'updateEditMessage', 'updateEditChannelMessage', 'updateNewScheduledMessage', 'updateNewEncryptedMessage', 'updateNewOutgoingEncryptedMessage'], true)) {
                if ($result !== null) {
                    throw new Exception('Found more than one update of type message, use extractUpdates to extract all updates');
                }
                $result = $update;
            }
        }
        if ($result === null) {
            $updates = json_encode($updates);
            throw new Exception("Could not find any message in $updates!");
        }
        return $result;
    }
    /**
     * Extract Update constructors from an Updates constructor.
     *
     * @return array<array>
     */
    public function extractUpdates(array $updates): array
    {
        switch ($updates['_']) {
            case 'updates':
            case 'updatesCombined':
                return $updates['updates'];
            case 'updateShort':
                return [$updates['update']];
            case 'updateShortSentMessage':
                $updates['user_id'] = $this->getInfo($updates['request']['body']['peer'], API::INFO_TYPE_ID);
                // no break
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $updates = array_merge($updates['request']['body'] ?? [], $updates);
                unset($updates['request']);
                $from_id = $updates['from_id'] ?? ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = $updates['chat_id'] ?? ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $from_id;
                $message['peer_id'] = $to_id;
                $this->populateMessageFlags($message);
                return [['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']]];
            case 'updateNewOutgoingEncryptedMessage':
                return [$updates];
            default:
                throw new ResponseException('Unrecognized update received: '.$updates['_']);
        }
    }
    private function populateMessageFlags(array &$message): void
    {
        $new = ['_' => 'message'];
        foreach ($this->TL->getConstructors()->findByPredicate('message')['params'] as $param) {
            if (isset($message[$param['name']]) && $param['name'] !== 'flags') {
                $new[$param['name']] = $message[$param['name']];
            } elseif ($param['type'] === 'true') {
                $new[$param['name']] = false;
            }
        }
        $message = $new;
        if (isset($message['reply_markup']['rows'])) {
            foreach ($message['reply_markup']['rows'] as $key => $row) {
                foreach ($row['buttons'] as $bkey => $button) {
                    $message['reply_markup']['rows'][$key]['buttons'][$bkey] = $button instanceof Button ? $button : new Button($this, $message, $button);
                }
            }
        }
    }
    /**
     * @param array $updates        Updates
     * @param array $actual_updates Actual updates for deferred
     * @internal
     */
    public function handleUpdates(array $updates, ?array $actual_updates = null): void
    {
        if ($actual_updates) {
            $updates = $actual_updates;
        }
        $this->logger->logger('Parsing updates ('.$updates['_'].') received via the socket...', Logger::VERBOSE);
        switch ($updates['_']) {
            case 'updates':
            case 'updatesCombined':
                $result = [];
                foreach ($updates['updates'] as $key => $update) {
                    if ($update['_'] === 'updateNewMessage' || $update['_'] === 'updateReadMessagesContents' || $update['_'] === 'updateEditMessage' || $update['_'] === 'updateDeleteMessages' || $update['_'] === 'updateReadHistoryInbox' || $update['_'] === 'updateReadHistoryOutbox' || $update['_'] === 'updateWebPage' || $update['_'] === 'updateMessageID') {
                        $result[$this->feeders[FeedLoop::GENERIC]->feedSingle($update)] = true;
                        unset($updates['updates'][$key]);
                    }
                }
                $this->seqUpdater->addPendingWakeups($result);
                if ($updates['updates']) {
                    if ($updates['_'] === 'updatesCombined') {
                        $updates['options'] = ['seq_start' => $updates['seq_start'], 'seq_end' => $updates['seq'], 'date' => $updates['date']];
                    } else {
                        $updates['options'] = ['seq_start' => $updates['seq'], 'seq_end' => $updates['seq'], 'date' => $updates['date']];
                    }
                    $this->seqUpdater->feed($updates);
                }
                $this->seqUpdater->resume();
                break;
            case 'updateShort':
                $this->feeders[$this->feeders[FeedLoop::GENERIC]->feedSingle($updates['update'])]->resume();
                break;
            case 'updateShortSentMessage':
                if (!isset($updates['request']['body'])) {
                    break;
                }
                $updates['user_id'] = $this->getInfo($updates['request']['body']['peer'], API::INFO_TYPE_ID);
                // no break
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $updates = array_merge($updates['request']['body'] ?? [], $updates);
                unset($updates['request']);
                $from_id = $updates['from_id'] ?? ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = $updates['chat_id'] ?? ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                if (!(($this->peerIsset($from_id)) || !(($this->peerIsset($to_id)) || isset($updates['via_bot_id']) && !(($this->peerIsset($updates['via_bot_id'])) || isset($updates['entities']) && !(($this->entitiesPeerIsset($updates['entities'])) || isset($updates['fwd_from']) && !($this->fwdPeerIsset($updates['fwd_from']))))))) {
                    $this->updaters[FeedLoop::GENERIC]->resume();
                    return;
                }
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $from_id;
                $message['peer_id'] = $to_id;
                $this->populateMessageFlags($message);
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->feeders[$this->feeders[FeedLoop::GENERIC]->feedSingle($update)]->resume();
                break;
            case 'updatesTooLong':
                $this->updaters[UpdateLoop::GENERIC]->resume();
                break;
            default:
                throw new ResponseException('Unrecognized update received: '.var_export($updates, true));
                break;
        }
    }
    /**
     * Subscribe to event handler updates for a channel/supergroup we're not a member of.
     *
     * @param mixed $channel Channel/supergroup to subscribe to
     *
     * @return bool False if we were already subscribed
     */
    public function subscribeToUpdates(mixed $channel): bool
    {
        $channelId = $this->getId($channel);
        if (!DialogId::isSupergroupOrChannel($channelId)) {
            throw new Exception("You can only subscribe to channels or supergroups!");
        }
        if (!$this->getChannelStates()->has($channelId)) {
            $this->loadChannelState($channelId, ['_' => 'updateChannelTooLong', 'pts' => 1]);
            $this->feeders[$channelId] ??= new FeedLoop($this, $channelId);
            $this->updaters[$channelId] ??= new UpdateLoop($this, $channelId);
            $this->feeders[$channelId]->start();
            if (isset($this->feeders[$channelId])) {
                $this->feeders[$channelId]->resume();
            }
            $this->updaters[$channelId]->start();
            if (isset($this->updaters[$channelId])) {
                $this->updaters[$channelId]->resume();
            }
            return true;
        }
        return false;
    }
    /**
     * Save update.
     *
     * @param array $update Update to save
     * @internal
     */
    public function saveUpdate(array $update): void
    {
        $this->logger->logger("Saving update of type {$update['_']}", Logger::VERBOSE);
        if ($update['_'] === 'updateConfig') {
            $this->config['expires'] = 0;
            $this->getConfig();
        }
        if ($update['_'] === 'updateLoginToken') {
            try {
                $authorization = $this->methodCallAsyncRead(
                    'auth.exportLoginToken',
                    [
                        'api_id' => $this->settings->getAppInfo()->getApiId(),
                        'api_hash' => $this->settings->getAppInfo()->getApiHash(),
                    ],
                );
                $datacenter = $this->datacenter->currentDatacenter;
                if ($authorization['_'] === 'auth.loginTokenMigrateTo') {
                    $datacenter = $this->isTestMode() ? 10_000 + $authorization['dc_id'] : $authorization['dc_id'];
                    $this->authorized_dc = $datacenter;
                    $authorization = $this->methodCallAsyncRead(
                        'auth.importLoginToken',
                        $authorization,
                        $datacenter
                    );
                }
                $this->processAuthorization($authorization['authorization']);
            } catch (RPCErrorException $e) {
                if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                    $this->logger->logger(Lang::$current_lang['login_2fa_enabled'], Logger::NOTICE);
                    $this->authorization = $this->methodCallAsyncRead('account.getPassword', [], $datacenter ?? null);
                    if (!isset($this->authorization['hint'])) {
                        $this->authorization['hint'] = '';
                    }
                    $this->authorized = API::WAITING_PASSWORD;
                    $this->qrLoginDeferred?->cancel();
                    $this->qrLoginDeferred = null;
                    return;
                }
                throw $e;
            }
            return;
        }
        if (
            \in_array($update['_'], ['updateChannel', 'updateUser', 'updateUserName', 'updateUserPhone', 'updateUserBlocked', 'updateUserPhoto', 'updateContactRegistered', 'updateContactLink'], true)
            || (
                $update['_'] === 'updateNewChannelMessage'
                && isset($update['message']['action']['_'])
                && \in_array($update['message']['action']['_'], ['messageActionChatEditTitle', 'messageActionChatEditPhoto', 'messageActionChatDeletePhoto', 'messageActionChatMigrateTo', 'messageActionChannelMigrateFrom', 'messageActionGroupCall'], true)
            )
        ) {
            $id = $this->getIdInternal($update);
            \assert($id !== null);
            EventLoop::queue(function () use ($id): void {
                try {
                    $this->refreshPeerCache($id);
                    if ($this->getSettings()->getDb()->getEnableFullPeerDb()) {
                        $this->peerDatabase->expireFull($id);
                    }
                } catch (PeerNotInDbException) {
                } catch (FloodWaitError) {
                } catch (RPCErrorException $e) {
                    if ($e->rpc !== 'CHANNEL_PRIVATE' && $e->rpc !== 'MSG_ID_INVALID') {
                        throw $e;
                    }
                }
            });
        }
        if ($update['_'] === 'updateDcOptions') {
            $this->logger->logger('Got new dc options', Logger::VERBOSE);
            $this->config['dc_options'] = $update['dc_options'];
            $this->parseConfig();
            return;
        }
        if ($update['_'] === 'updatePhoneCallSignalingData') {
            if (isset($this->calls[$update['phone_call_id']])) {
                EventLoop::queue($this->calls[$update['phone_call_id']]->onSignaling(...), (string) $update['data']);
            }
        }
        if ($update['_'] === 'updatePhoneCall') {
            switch ($update['phone_call']['_']) {
                case 'phoneCallRequested':
                    if (isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $this->calls[$update['phone_call']['id']] = $controller = new VoIPController(
                        $this,
                        $update['phone_call'],
                    );
                    $this->callsByPeer[$controller->public->otherID] = $controller;
                    $update['phone_call'] = $controller->public;
                    break;
                case 'phoneCallWaiting':
                    if (isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $this->calls[$update['phone_call']['id']] = $controller = new VoIPController(
                        $this,
                        $update['phone_call']
                    );
                    $this->callsByPeer[$controller->public->otherID] = $controller;
                    $update['phone_call'] = $controller->public;
                    break;
                case 'phoneCallAccepted':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $controller = $this->calls[$update['phone_call']['id']];
                    $controller->confirm($update['phone_call']);
                    $update['phone_call'] = $controller->public;
                    break;
                case 'phoneCall':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $controller = $this->calls[$update['phone_call']['id']];
                    $controller->complete($update['phone_call']);
                    $update['phone_call'] = $controller->public;
                    break;
                case 'phoneCallDiscarded':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $controller = $this->calls[$update['phone_call']['id']];
                    $controller->discard();
                    $update['phone_call'] = $controller->public;
                    break;
                case 'phoneCallEmpty':
                    return;
            }
        }
        if ($update['_'] === 'updateNewEncryptedMessage' && !isset($update['message']['decrypted_message'])) {
            if (isset($update['qts'])) {
                $cur_state = ($this->loadUpdateState());
                if ($cur_state->qts() === -1) {
                    $cur_state->qts($update['qts']);
                }
                if ($update['qts'] < $cur_state->qts()) {
                    $this->logger->logger('Duplicate update. update qts: '.$update['qts'].' <= current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], Logger::ERROR);
                    return;
                }
                if ($update['qts'] > $cur_state->qts() + 1) {
                    $this->logger->logger('Qts hole. Fetching updates manually: update qts: '.$update['qts'].' > current qts '.$cur_state->qts().'+1, chat id: '.$update['message']['chat_id'], Logger::ERROR);
                    $this->updaters[UpdateLoop::GENERIC]->resume();
                    return;
                }
                $this->logger->logger('Applying qts: '.$update['qts'].' over current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], Logger::VERBOSE);
                $this->methodCallAsyncRead('messages.receivedQueue', ['max_qts' => $cur_state->qts($update['qts'])]);
            }
            EventLoop::queue(function () use ($update): void {
                if (!isset($this->secretChats[$update['message']['chat_id']])) {
                    $this->logger->logger(sprintf(Lang::$current_lang['secret_chat_skipping'], $update['message']['chat_id']));
                    return;
                }
                $this->secretChats[$update['message']['chat_id']]->feed($update);
            });
            return;
        }
        if ($update['_'] === 'updateEncryption') {
            EventLoop::queue(function () use ($update): void {
                switch ($update['chat']['_']) {
                    case 'encryptedChatRequested':
                        if (!$this->settings->getSecretChats()->canAccept($update['chat']['admin_id'])) {
                            return;
                        }
                        $this->logger->logger('Accepting secret chat '.$update['chat']['id'], Logger::NOTICE);
                        try {
                            $this->acceptSecretChat($update['chat']);
                        } catch (RPCErrorException $e) {
                            $this->logger->logger("Error while accepting secret chat: {$e}", Logger::FATAL_ERROR);
                        }
                        break;
                    case 'encryptedChatDiscarded':
                        $this->logger->logger('Deleting secret chat '.$update['chat']['id'].' because it was revoked by the other user (it was probably accepted by another client)', Logger::NOTICE);
                        $this->discardSecretChat($update['chat']['id']);
                        break;
                    case 'encryptedChat':
                        $this->logger->logger('Completing creation of secret chat '.$update['chat']['id'], Logger::NOTICE);
                        $this->completeSecretChat($update['chat']);
                        break;
                }
            });
            //$this->logger->logger($update, \danog\MadelineProto\Logger::NOTICE);
        }
        if ($this->updateHandlerType === UpdateHandlerType::NOOP) {
            return;
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'messageEmpty') {
            return;
        }
        if (isset($update['message']['from_id'])
            && $update['message']['from_id'] > 0
        ) {
            if ($update['message']['from_id'] === $this->authorization['user']['id']) {
                $update['message']['out'] = true;
            }
        } elseif (!isset($update['message']['from_id'])
            && isset($update['message']['peer_id'])
            && $update['message']['peer_id'] > 0
            && $update['message']['peer_id'] === $this->authorization['user']['id']) {
            $update['message']['out'] = true;
        }

        if (!isset($update['message']['from_id'])
            && isset($update['message']['peer_id'])
            && $update['message']['peer_id'] > 0
        ) {
            $update['message']['from_id'] = $update['message']['peer_id'];
        }

        $this->handleUpdate($update);
    }
    private function handleUpdate(array $update): void
    {
        /** @var UpdateHandlerType::EVENT_HANDLER|UpdateHandlerType::WEBHOOK|UpdateHandlerType::GET_UPDATES $this->updateHandlerType */
        match ($this->updateHandlerType) {
            UpdateHandlerType::EVENT_HANDLER => $this->eventUpdateHandler($update),
            UpdateHandlerType::WEBHOOK => EventLoop::queue($this->pwrWebhook(...), $update),
            UpdateHandlerType::GET_UPDATES => EventLoop::queue($this->addGetUpdatesUpdate(...), $update),
        };
    }
    /**
     * Sends an updateCustomEvent update to the event handler.
     */
    public function sendCustomEvent(mixed $payload): void
    {
        $this->handleUpdate(['_' => 'updateCustomEvent', 'payload' => $payload]);
    }
}
