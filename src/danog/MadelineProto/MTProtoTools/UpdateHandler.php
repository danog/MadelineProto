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

use Amp\DeferredFuture;
use Amp\TimeoutCancellation;
use Amp\TimeoutException;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ResponseException;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\Types\Button;
use danog\MadelineProto\VoIP;
use Revolt\EventLoop;

use function Amp\async;

/**
 * Manages updates.
 *
 * @extend MTProto
 * @property Settings $settings Settings
 * @property TL $TL TL
 */
trait UpdateHandler
{
    /**
     * Update handler callback.
     *
     * @var ?callable
     */
    private $updateHandler;
    private bool $got_state = false;
    private CombinedUpdatesState $channels_state;
    public array $updates = [];
    public int $updates_key = 0;

    /**
     * Get updates.
     *
     * @param array $params Params
     * @internal
     * @return list<array{update_id: mixed, update: mixed}>
     */
    public function getUpdates(array $params = [])
    {
        $this->updateHandler = MTProto::GETUPDATES_HANDLER;
        $params = MTProto::DEFAULT_GETUPDATES_PARAMS + $params;
        if (empty($this->updates)) {
            try {
                async($this->waitUpdate(...))->await(new TimeoutCancellation($params['timeout'] ?: 100000.0));
            } catch (TimeoutException $e) {
                if (!$e->getPrevious() instanceof TimeoutException) {
                    throw $e;
                }
            }
        }
        if (empty($this->updates)) {
            return $this->updates;
        }
        if ($params['offset'] < 0) {
            $params['offset'] = \array_reverse(\array_keys((array) $this->updates))[\abs($params['offset']) - 1];
        }
        $updates = [];
        foreach ($this->updates as $key => $value) {
            if ($params['offset'] > $key) {
                unset($this->updates[$key]);
            } elseif ($params['limit'] === null || \count($updates) < $params['limit']) {
                $updates[] = ['update_id' => $key, 'update' => $value];
            }
        }
        $this->updates = \array_slice($this->updates, 0, \count($this->updates), true);
        return $updates;
    }
    private ?DeferredFuture $update_deferred = null;
    /**
     * Wait for update.
     *
     * @internal
     */
    public function waitUpdate(): void
    {
        $this->update_deferred = new DeferredFuture();
        $this->update_deferred->getFuture()->await();
    }
    /**
     * Signal update.
     *
     * @internal
     */
    public function signalUpdate(): void
    {
        if ($this->update_deferred) {
            $deferred = $this->update_deferred;
            $this->update_deferred = null;
            EventLoop::defer(fn () => $deferred->complete());
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
        try {
            $peer_id = $this->getId($message['peer_id']);
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
            $this->got_state = true;
            $this->channels_state->get(0, $this->getUpdatesState());
        }
        return $this->channels_state->get(0);
    }
    /**
     * Load channel state.
     *
     * @param ?int  $channelId Channel ID
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
    public function getUpdatesState()
    {
        $data = $this->methodCallAsyncRead('updates.getState', [], $this->settings->getDefaultDcParams());
        $this->getCdnConfig($this->settings->getDefaultDc());
        return $data;
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
            if (\in_array($update['_'], ['updateNewMessage', 'updateNewChannelMessage', 'updateEditMessage', 'updateEditChannelMessage'])) {
                if ($result !== null) {
                    throw new Exception('Found more than one update of type message, use extractUpdates to extract all updates');
                }
                $result = $update;
            }
        }
        if ($result === null) {
            throw new Exception('Could not find any message in the updates!');
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
                $updates['user_id'] = $this->getInfo($updates['request']['body']['peer'], MTProto::INFO_TYPE_ID);
                // no break
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $updates = \array_merge($updates['request']['body'] ?? [], $updates);
                unset($updates['request']);
                $from_id = $updates['from_id'] ?? ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $this->getInfo($from_id, MTProto::INFO_TYPE_PEER);
                $message['peer_id'] = $this->getInfo($to_id, MTProto::INFO_TYPE_PEER);
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
                $updates['user_id'] = $this->getInfo($updates['request']['body']['peer'], MTProto::INFO_TYPE_ID);
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
     * @return bool False if we were already subscribed
     */
    public function subscribeToUpdates(mixed $channel): bool
    {
        $channelId = $this->getInfo($channel, MTProto::INFO_TYPE_ID);
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
        if ($update['_'] === 'updateConfig') {
            $this->config['expires'] = 0;
            $this->getConfig();
        }
        if (
            \in_array($update['_'], ['updateChannel', 'updateUser', 'updateUserName', 'updateUserPhone', 'updateUserBlocked', 'updateUserPhoto', 'updateContactRegistered', 'updateContactLink'])
            || (
                $update['_'] === 'updateNewChannelMessage'
                && isset($update['message']['action']['_'])
                && \in_array($update['message']['action']['_'], ['messageActionChatEditTitle', 'messageActionChatEditPhoto', 'messageActionChatDeletePhoto', 'messageActionChatMigrateTo', 'messageActionChannelMigrateFrom', 'messageActionGroupCall'])
            )
        ) {
            if ($this->getSettings()->getDb()->getEnableFullPeerDb()) {
                $this->refreshFullPeerCache($update);
            } else {
                $this->refreshPeerCache($update);
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
                    $this->calls[$update['phone_call']['id']]->discard($update['phone_call']['reason'] ?? ['_' => 'phoneCallDiscardReasonDisconnect'], [], $update['phone_call']['need_debug'] ?? false);
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
                    $this->updaters[UpdateLoop::GENERIC]->resumeDefer();
                    return;
                }
                $this->logger->logger('Applying qts: '.$update['qts'].' over current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], Logger::VERBOSE);
                $this->methodCallAsyncRead('messages.receivedQueue', ['max_qts' => $cur_state->qts($update['qts'])], $this->settings->getDefaultDcParams());
            }
            if (!isset($this->secret_chats[$update['message']['chat_id']])) {
                $this->logger->logger(\sprintf(Lang::$current_lang['secret_chat_skipping'], $update['message']['chat_id']));
                return;
            }
            $this->secretFeeders[$update['message']['chat_id']]->feed($update);
            $this->secretFeeders[$update['message']['chat_id']]->resume();
            return;
        }
        /*
                if ($update['_'] === 'updateEncryptedChatTyping') {
                $update = ['_' => 'updateUserTyping', 'user_id' => $this->encrypted_chats[$update['chat_id']]['user_id'], 'action' => ['_' => 'sendMessageTypingAction']];
                }
        */
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
        //if ($update['_'] === 'updateServiceNotification' && strpos($update['type'], 'AUTH_KEY_DROP_') === 0) {
        //}
        if (!$this->updateHandler) {
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
        // First save to array, then once the feed loop signals resumal of loop, resume and handle
        $this->updates[$this->updates_key++] = $update;
    }
}
