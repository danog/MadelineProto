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

use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\TimeoutException;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\ChannelMessage;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Message\Service\DialogCreated;
use danog\MadelineProto\EventHandler\Message\Service\DialogMemberLeft;
use danog\MadelineProto\EventHandler\Message\Service\DialogMembersJoined;
use danog\MadelineProto\EventHandler\Message\Service\DialogMessagePinned;
use danog\MadelineProto\EventHandler\Message\Service\DialogPhotoChanged;
use danog\MadelineProto\EventHandler\Message\Service\DialogTitleChanged;
use danog\MadelineProto\EventHandler\Query\ChatButtonQuery;
use danog\MadelineProto\EventHandler\Query\ChatGameQuery;
use danog\MadelineProto\EventHandler\Query\InlineButtonQuery;
use danog\MadelineProto\EventHandler\Query\InlineGameQuery;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\ResponseException;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\Types\Button;
use danog\MadelineProto\Tools;
use danog\MadelineProto\UpdateHandlerType;
use danog\MadelineProto\VoIP;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\delay;

/**
 * Manages updates.
 *
 * @property Settings $settings Settings
 * @property TL $TL TL
 *
 * @internal
 */
trait UpdateHandler
{
    private UpdateHandlerType $updateHandlerType = UpdateHandlerType::NOOP;

    private bool $got_state = false;
    private CombinedUpdatesState $channels_state;
    private array $updates = [];
    private int $updates_key = 0;

    /**
     * Set NOOP update handler, ignoring all updates.
     */
    public function setNoop(): void
    {
        $this->updateHandlerType = UpdateHandlerType::NOOP;
        $this->updates = [];
        $this->updates_key = 0;
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
        \array_map($this->handleUpdate(...), $this->updates);
        $this->updates = [];
        $this->updates_key = 0;
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
        if ($update['_'] === 'updateBroadcastProgress') {
            $update = $update['progress'];
        }
        if ($f = $this->event_handler_instance->waitForInternalStart()) {
            $this->logger->logger("Postponing update handling, onStart is still running (if stuck here for too long, make sure to fork long-running tasks in onStart using \$this->callFork(function () { ... }) to fix this)...", Logger::NOTICE);
            $this->updates[$this->updates_key++] = $update;
            $f->map(function (): void {
                \array_map($this->handleUpdate(...), $this->updates);
                $this->updates = [];
                $this->updates_key = 0;
            });
            return;
        }
        if (\count($this->eventHandlerHandlers) !== 0 && \is_array($update)) {
            $obj = $this->wrapUpdate($update);
            if ($obj !== null) {
                foreach ($this->eventHandlerHandlers as $closure) {
                    $closure($obj);
                }
            }
        }
        if (isset($this->eventHandlerMethods[$updateType])) {
            foreach ($this->eventHandlerMethods[$updateType] as $closure) {
                $closure($update);
            }
        }
    }

    /**
     * Send update to webhook.
     *
     * @param array $update Update
     */
    private function pwrWebhook(array $update): void
    {
        $payload = \json_encode($update);
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
            $this->updates[$this->updates_key++] = $update;
            delay(1.0);
            \array_map($this->handleUpdate(...), $this->updates);
            $this->updates = [];
            $this->updates_key = 0;
        }
    }

    private ?DeferredFuture $update_deferred = null;
    /**
     * Signal update.
     *
     * @internal
     */
    private function signalUpdate(array $update): void
    {
        $this->updates[$this->updates_key++] = $update;
        if ($this->update_deferred) {
            $deferred = $this->update_deferred;
            $this->update_deferred = null;
            $deferred->complete();
        }
    }

    /**
     * Only useful when consuming MadelineProto updates through an API in another language (like Javascript), **absolutely not recommended when directly writing MadelineProto bots**.
     *
     * `getUpdates` will **greatly slow down your bot** if used directly inside of PHP code.
     *
     * **Only use the [event handler](#async-event-driven) when writing a MadelineProto bot**, because update handling in the **event handler** is completely parallelized and non-blocking.
     *
     * @param array{offset?: int, limit?: int, timeout?: float} $params Params
     * @return list<array{update_id: mixed, update: mixed}>
     */
    public function getUpdates(array $params = []): array
    {
        $this->updateHandlerType = UpdateHandlerType::GET_UPDATES;
        $this->event_handler = null;
        $this->event_handler_instance = null;
        $this->eventHandlerMethods = [];
        $this->eventHandlerHandlers = [];
        $this->pluginInstances = [];

        [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout
        ] = \array_merge(['offset' => 0, 'limit' => null, 'timeout' => INF], $params);

        if (!$this->updates) {
            try {
                $this->update_deferred = new DeferredFuture();
                $this->update_deferred->getFuture()->await(
                    $timeout === INF ? null : Tools::getTimeoutCancellation($timeout)
                );
            } catch (CancelledException $e) {
                if (!$e->getPrevious() instanceof TimeoutException) {
                    throw $e;
                }
            }
        }
        if (!$this->updates) {
            return [];
        }
        if ($offset < 0) {
            $offset = $this->updates_key+$offset;
        }
        $updates = [];
        foreach ($this->updates as $key => $value) {
            if ($offset > $key) {
                unset($this->updates[$key]);
                continue;
            }

            $updates[] = ['update_id' => $key, 'update' => $value];

            if ($limit !== null && \count($updates) === $limit) {
                break;
            }
        }
        $this->updates = \array_slice($this->updates, 0, \count($this->updates), true);
        return $updates;
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
        try {
            $peer_id = $this->getIdInternal($message['peer_id']);
        } catch (Exception $e) {
            return true;
        } catch (RPCErrorException $e) {
            return true;
        }
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
            $this->channels_state->get(0, $this->getUpdatesState());
            $this->got_state = true;
        }
        return $this->channels_state->get(0);
    }
    /**
     * Load channel state.
     *
     * @param null|int  $channelId Channel ID
     * @param array $init      Init
     * @internal
     * @return UpdatesState|array<UpdatesState>
     */
    public function loadChannelState(?int $channelId = null, array $init = []): UpdatesState|array
    {
        return $this->channels_state->get($channelId, $init);
    }
    /**
     * Get channel states.
     *
     * @internal
     */
    public function getChannelStates(): CombinedUpdatesState
    {
        return $this->channels_state;
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
        return match ($update['_']) {
            'updateNewChannelMessage', 'updateNewMessage', 'updateNewScheduledMessage', 'updateEditMessage', 'updateEditChannelMessage' => $this->wrapMessage($update['message']),
            'updateBotCallbackQuery' => isset($update['game_short_name'])
                ? new ChatGameQuery($this, $update)
                : new ChatButtonQuery($this, $update),
            'updateInlineBotCallbackQuery' => isset($update['game_short_name'])
                ? new InlineGameQuery($this, $update)
                : new InlineButtonQuery($this, $update),
            default => null
        };
    }

    /**
     * Wrap a Message constructor into an abstract Message object.
     */
    public function wrapMessage(array $message): ?AbstractMessage
    {
        if ($message['_'] === 'messageEmpty') {
            return null;
        }
        $info = $this->getInfo($message);
        if ($message['_'] === 'messageService') {
            return match ($message['action']['_']) {
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
                    $this->wrapMedia([
                        '_' => 'messageMediaPhoto',
                        'photo' => $message['action']['photo']
                    ])
                ),
                'messageActionChatDeletePhoto' => new DialogPhotoChanged(
                    $this,
                    $message,
                    $info,
                    null
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
                'messageActionPinMessage' => new DialogMessagePinned(
                    $this,
                    $message,
                    $info,
                ),
                default => null
            };
        }
        if (($this->chats[$info['bot_api_id']]['username'] ?? '') === 'replies') {
            return null;
        }
        return match ($info['type']) {
            API::PEER_TYPE_BOT, API::PEER_TYPE_USER => new PrivateMessage($this, $message, $info),
            API::PEER_TYPE_GROUP, API::PEER_TYPE_SUPERGROUP => new GroupMessage($this, $message, $info),
            API::PEER_TYPE_CHANNEL => new ChannelMessage($this, $message, $info),
        };
    }
    /**
     * Sends a message.
     *
     * @param integer|string $peer Destination peer or username.
     * @param string $message Message to send
     * @param ParseMode $parseMode Parse mode
     * @param integer|null $replyToMsgId ID of message to reply to.
     * @param integer|null $topMsgId ID of thread where to send the message.
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|null $sendAs Peer to send the message as.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $noWebpage Set this flag to disable generation of the webpage preview
     * @param boolean $updateStickersetsOrder Whether to move used stickersets to top
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
                'update_stickersets_order' => $updateStickersetsOrder
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
            if (\in_array($update['_'], ['updateNewMessage', 'updateNewChannelMessage', 'updateEditMessage', 'updateEditChannelMessage', 'updateNewScheduledMessage'], true)) {
                if ($result !== null) {
                    throw new Exception('Found more than one update of type message, use extractUpdates to extract all updates');
                }
                $result = $update;
            }
        }
        if ($result === null) {
            $updates = \json_encode($updates);
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
                $updates = \array_merge($updates['request']['body'] ?? [], $updates);
                unset($updates['request']);
                $from_id = $updates['from_id'] ?? ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $this->getInfo($from_id, API::INFO_TYPE_PEER);
                $message['peer_id'] = $this->getInfo($to_id, API::INFO_TYPE_PEER);
                $this->populateMessageFlags($message);
                return [['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']]];
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
                $updates = \array_merge($updates['request']['body'] ?? [], $updates);
                unset($updates['request']);
                $from_id = $updates['from_id'] ?? ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                if (!(($this->peerIsset($from_id)) || !(($this->peerIsset($to_id)) || isset($updates['via_bot_id']) && !(($this->peerIsset($updates['via_bot_id'])) || isset($updates['entities']) && !(($this->entitiesPeerIsset($updates['entities'])) || isset($updates['fwd_from']) && !($this->fwdPeerIsset($updates['fwd_from']))))))) {
                    $this->updaters[FeedLoop::GENERIC]->resume();
                    return;
                }
                $message = $updates;
                $message['_'] = 'message';
                try {
                    $message['from_id'] = ($this->getInfo($from_id))['Peer'];
                    $message['peer_id'] = ($this->getInfo($to_id))['Peer'];
                } catch (Exception $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', Logger::ERROR);
                    break;
                } catch (RPCErrorException $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', Logger::ERROR);
                    break;
                }
                $this->populateMessageFlags($message);
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->feeders[$this->feeders[FeedLoop::GENERIC]->feedSingle($update)]->resume();
                break;
            case 'updatesTooLong':
                $this->updaters[UpdateLoop::GENERIC]->resume();
                break;
            default:
                throw new ResponseException('Unrecognized update received: '.\var_export($updates, true));
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
        $channelId = $this->getInfo($channel, API::INFO_TYPE_ID);
        if (!MTProto::isSupergroup($channelId)) {
            throw new Exception("You can only subscribe to channels or supergroups!");
        }
        $channelId = MTProto::fromSupergroup($channelId);
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
                if ($authorization['_'] === 'auth.loginTokenMigrateTo') {
                    $authorization = $this->methodCallAsyncRead(
                        'auth.importLoginToken',
                        $authorization,
                        ['datacenter' => $this->isTestMode() ? 10_000 + $authorization['dc_id'] : $authorization['dc_id']]
                    );
                }
                $this->processAuthorization($authorization['authorization']);
            } catch (RPCErrorException $e) {
                if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                    $this->logger->logger(Lang::$current_lang['login_2fa_enabled'], Logger::NOTICE);
                    $this->authorization = $this->methodCallAsyncRead('account.getPassword', []);
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
            try {
                if ($this->getSettings()->getDb()->getEnableFullPeerDb()) {
                    $this->refreshFullPeerCache($update);
                } else {
                    $this->refreshPeerCache($update);
                }
            } catch (PeerNotInDbException) {
            } catch (RPCErrorException $e) {
                if ($e->rpc !== 'CHANNEL_PRIVATE' && $e->rpc !== 'MSG_ID_INVALID') {
                    throw $e;
                }
            }
        }
        if ($update['_'] === 'updateDcOptions') {
            $this->logger->logger('Got new dc options', Logger::VERBOSE);
            $this->config['dc_options'] = $update['dc_options'];
            $this->parseConfig();
            return;
        }
        if ($update['_'] === 'updatePhoneCall') {
            if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
                $this->logger->logger('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.', Logger::WARNING);
                return;
            }
            switch ($update['phone_call']['_']) {
                case 'phoneCallRequested':
                    if (isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $controller = new VoIP(false, $update['phone_call']['admin_id'], $this, VoIP::CALL_STATE_INCOMING);
                    $controller->setCall($update['phone_call']);
                    $controller->storage = ['g_a_hash' => $update['phone_call']['g_a_hash']];
                    $controller->storage['video'] = $update['phone_call']['video'] ?? false;
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']] = $controller;
                    break;
                case 'phoneCallAccepted':
                    if (!($this->confirmCall($update['phone_call']))) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCall':
                    if (!($this->completeCall($update['phone_call']))) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCallDiscarded':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']]->discard($update['phone_call']['reason'] ?? ['_' => 'phoneCallDiscardReasonDisconnect'], [], $update['phone_call']['need_debug'] ?? false);
                    break;
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
            if (!isset($this->secret_chats[$update['message']['chat_id']])) {
                $this->logger->logger(\sprintf(Lang::$current_lang['secret_chat_skipping'], $update['message']['chat_id']));
                return;
            }
            $this->secretFeeders[$update['message']['chat_id']]->feed($update);
            $this->secretFeeders[$update['message']['chat_id']]->resume();
            return;
        }
        if ($update['_'] === 'updateEncryption') {
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
                    if (isset($this->secret_chats[$update['chat']['id']])) {
                        unset($this->secret_chats[$update['chat']['id']]);
                    }
                    if (isset($this->temp_requested_secret_chats[$update['chat']['id']])) {
                        unset($this->temp_requested_secret_chats[$update['chat']['id']]);
                    }
                    if (isset($this->temp_rekeyed_secret_chats[$update['chat']['id']])) {
                        unset($this->temp_rekeyed_secret_chats[$update['chat']['id']]);
                    }
                    break;
                case 'encryptedChat':
                    $this->logger->logger('Completing creation of secret chat '.$update['chat']['id'], Logger::NOTICE);
                    $this->completeSecretChat($update['chat']);
                    break;
            }
            //$this->logger->logger($update, \danog\MadelineProto\Logger::NOTICE);
        }
        if ($this->updateHandlerType === UpdateHandlerType::NOOP) {
            return;
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'messageEmpty') {
            return;
        }
        if (isset($update['message']['from_id']['user_id'])) {
            if ($update['message']['from_id']['user_id'] === $this->authorization['user']['id']) {
                $update['message']['out'] = true;
            }
        } elseif (!isset($update['message']['from_id'])
            && isset($update['message']['peer_id']['user_id'])
            && $update['message']['peer_id']['user_id'] === $this->authorization['user']['id']) {
            $update['message']['out'] = true;
        }

        if (!isset($update['message']['from_id']) && isset($update['message']['peer_id']['user_id'])) {
            $update['message']['from_id'] = $update['message']['peer_id'];
        }

        if (isset($update['message']['peer_id'])) {
            $update['message']['to_id'] = $update['message']['peer_id'];
        }
        $this->handleUpdate($update);
    }
    private function handleUpdate(array $update): void
    {
        /** @var UpdateHandlerType::EVENT_HANDLER|UpdateHandlerType::WEBHOOK|UpdateHandlerType::GET_UPDATES $this->updateHandlerType */
        match ($this->updateHandlerType) {
            UpdateHandlerType::EVENT_HANDLER => $this->eventUpdateHandler($update),
            UpdateHandlerType::WEBHOOK => EventLoop::queue($this->pwrWebhook(...), $update),
            UpdateHandlerType::GET_UPDATES => EventLoop::queue($this->signalUpdate(...), $update),
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
