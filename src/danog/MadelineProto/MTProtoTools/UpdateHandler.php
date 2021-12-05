<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCErrorException;

use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;

/**
 * Manages updates.
 *
 * @extend MTProto
 * @property Settings $settings Settings
 */
trait UpdateHandler
{
    /**
     * Update handler callback.
     *
     * @var ?callable
     */
    private $updateHandler;
    private $got_state = false;
    private $channels_state;
    public $updates = [];
    public $updates_key = 0;

    /**
     * Get updates.
     *
     * @param array $params Params
     *
     * @internal
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, \Amp\Promise<mixed|null>, mixed, list<array{update_id: mixed, update: mixed}>|mixed>
     */
    public function getUpdates($params = []): \Generator
    {
        $this->updateHandler = MTProto::GETUPDATES_HANDLER;
        $params = MTProto::DEFAULT_GETUPDATES_PARAMS + $params;
        if (empty($this->updates)) {
            $params['timeout'] *= 1000;
            yield Tools::timeoutWithDefault($this->waitUpdate(), $params['timeout'] ?: 100000);
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
    private ?Deferred $update_deferred = null;
    /**
     * Wait for update.
     *
     * @internal
     *
     * @return Promise
     */
    public function waitUpdate(): Promise
    {
        $this->update_deferred = new Deferred();
        return $this->update_deferred->promise();
    }
    /**
     * Signal update.
     *
     * @internal
     *
     * @return void
     */
    public function signalUpdate(): void
    {
        if ($this->update_deferred) {
            $deferred = $this->update_deferred;
            $this->update_deferred = null;
            Loop::defer(fn () => $deferred->resolve());
        }
    }
    /**
     * Check message ID.
     *
     * @param array $message Message
     *
     * @internal
     *
     * @return boolean
     */
    public function checkMsgId(array $message): bool
    {
        if (!isset($message['peer_id'])) {
            return true;
        }
        try {
            $peer_id = $this->getId($message['peer_id']);
        } catch (\danog\MadelineProto\Exception $e) {
            return true;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
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
     *
     * @return \Generator
     * @psalm-return <mixed, mixed, mixed, UpdatesState>
     */
    public function loadUpdateState(): \Generator
    {
        if (!$this->got_state) {
            $this->got_state = true;
            $this->channels_state->get(0, yield from $this->getUpdatesState());
        }
        return $this->channels_state->get(0);
    }
    /**
     * Load channel state.
     *
     * @param ?int  $channelId Channel ID
     * @param array $init      Init
     *
     * @internal
     *
     * @return UpdatesState|UpdatesState[]
     */
    public function loadChannelState($channelId = null, $init = [])
    {
        return $this->channels_state->get($channelId, $init);
    }
    /**
     * Get channel states.
     *
     * @internal
     *
     * @return CombinedUpdatesState
     */
    public function getChannelStates()
    {
        return $this->channels_state;
    }
    /**
     * Get update state.
     *
     * @internal
     *
     * @return \Generator
     */
    public function getUpdatesState(): \Generator
    {
        $data = yield from $this->methodCallAsyncRead('updates.getState', [], $this->settings->getDefaultDcParams());
        yield from $this->getCdnConfig($this->settings->getDefaultDc());
        return $data;
    }
    /**
     * Undocumented function.
     *
     * @param array $updates        Updates
     * @param array $actual_updates Actual updates for deferred
     *
     * @internal
     *
     * @return \Generator
     */
    public function handleUpdates($updates, $actual_updates = null): \Generator
    {
        if ($actual_updates) {
            $updates = $actual_updates;
        }
        $this->logger->logger('Parsing updates ('.$updates['_'].') received via the socket...', \danog\MadelineProto\Logger::VERBOSE);
        switch ($updates['_']) {
            case 'updates':
            case 'updatesCombined':
                $result = [];
                foreach ($updates['updates'] as $key => $update) {
                    if ($update['_'] === 'updateNewMessage' || $update['_'] === 'updateReadMessagesContents' || $update['_'] === 'updateEditMessage' || $update['_'] === 'updateDeleteMessages' || $update['_'] === 'updateReadHistoryInbox' || $update['_'] === 'updateReadHistoryOutbox' || $update['_'] === 'updateWebPage' || $update['_'] === 'updateMessageID') {
                        $result[yield from $this->feeders[FeedLoop::GENERIC]->feedSingle($update)] = true;
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
                $this->feeders[yield from $this->feeders[FeedLoop::GENERIC]->feedSingle($updates['update'])]->resume();
                break;
            case 'updateShortSentMessage':
                if (!isset($updates['request']['body'])) {
                    break;
                }
                $updates['user_id'] = yield from $this->getInfo($updates['request']['body']['peer'], MTProto::INFO_TYPE_ID);
                $updates['message'] = $updates['request']['body']['message'];
                unset($updates['request']);
            // no break
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $from_id = isset($updates['from_id']) ? $updates['from_id'] : ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                if (!((yield from $this->peerIsset($from_id)) || !((yield from $this->peerIsset($to_id)) || isset($updates['via_bot_id']) && !((yield from $this->peerIsset($updates['via_bot_id'])) || isset($updates['entities']) && !((yield from $this->entitiesPeerIsset($updates['entities'])) || isset($updates['fwd_from']) && !(yield from $this->fwdPeerIsset($updates['fwd_from']))))))) {
                    yield $this->updaters[FeedLoop::GENERIC]->resume();
                    return;
                }
                $message = $updates;
                $message['_'] = 'message';
                try {
                    $message['from_id'] = (yield from $this->getInfo($from_id))['Peer'];
                    $message['peer_id'] = (yield from $this->getInfo($to_id))['Peer'];
                } catch (\danog\MadelineProto\Exception $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    break;
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    break;
                }
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->feeders[yield from $this->feeders[FeedLoop::GENERIC]->feedSingle($update)]->resume();
                break;
            case 'updatesTooLong':
                $this->updaters[UpdateLoop::GENERIC]->resume();
                break;
            default:
                throw new \danog\MadelineProto\ResponseException('Unrecognized update received: '.\var_export($updates, true));
                break;
        }
    }
    /**
     * Save update.
     *
     * @param array $update Update to save
     *
     * @internal
     *
     * @return \Generator
     */
    public function saveUpdate(array $update): \Generator
    {
        if ($update['_'] === 'updateConfig') {
            $this->config['expires'] = 0;
            yield from $this->getConfig();
        }
        if (\in_array($update['_'], ['updateUserName', 'updateUserPhone', 'updateUserBlocked', 'updateUserPhoto', 'updateContactRegistered', 'updateContactLink']) && $this->getSettings()->getDb()->getEnableFullPeerDb()) {
            $id = $this->getId($update);
            $chat = yield $this->full_chats[$id];
            $chat['last_update'] = 0;
            $this->full_chats[$id] = $chat;
            yield from $this->getFullInfo($id);
        }
        if ($update['_'] === 'updateDcOptions') {
            $this->logger->logger('Got new dc options', \danog\MadelineProto\Logger::VERBOSE);
            $this->config['dc_options'] = $update['dc_options'];
            yield from $this->parseConfig();
            return;
        }
        if ($update['_'] === 'updatePhoneCall') {
            if (!\class_exists('\\danog\\MadelineProto\\VoIP')) {
                $this->logger->logger('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.', \danog\MadelineProto\Logger::WARNING);
                return;
            }
            switch ($update['phone_call']['_']) {
                case 'phoneCallRequested':
                    if (isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $controller = new \danog\MadelineProto\VoIP(false, $update['phone_call']['admin_id'], $this, \danog\MadelineProto\VoIP::CALL_STATE_INCOMING);
                    $controller->setCall($update['phone_call']);
                    $controller->storage = ['g_a_hash' => $update['phone_call']['g_a_hash']];
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']] = $controller;
                    break;
                case 'phoneCallAccepted':
                    if (!(yield from $this->confirmCall($update['phone_call']))) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCall':
                    if (!(yield from $this->completeCall($update['phone_call']))) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCallDiscarded':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    return $this->calls[$update['phone_call']['id']]->discard($update['phone_call']['reason'] ?? [], [], $update['phone_call']['need_debug'] ?? false);
            }
        }
        if ($update['_'] === 'updateNewEncryptedMessage' && !isset($update['message']['decrypted_message'])) {
            if (isset($update['qts'])) {
                $cur_state = (yield from $this->loadUpdateState());
                if ($cur_state->qts() === -1) {
                    $cur_state->qts($update['qts']);
                }
                if ($update['qts'] < $cur_state->qts()) {
                    $this->logger->logger('Duplicate update. update qts: '.$update['qts'].' <= current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::ERROR);
                    return false;
                }
                if ($update['qts'] > $cur_state->qts() + 1) {
                    $this->logger->logger('Qts hole. Fetching updates manually: update qts: '.$update['qts'].' > current qts '.$cur_state->qts().'+1, chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::ERROR);
                    $this->updaters[UpdateLoop::GENERIC]->resumeDefer();
                    return false;
                }
                $this->logger->logger('Applying qts: '.$update['qts'].' over current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::VERBOSE);
                yield from $this->methodCallAsyncRead('messages.receivedQueue', ['max_qts' => $cur_state->qts($update['qts'])], $this->settings->getDefaultDcParams());
            }
            if (!isset($this->secret_chats[$update['message']['chat_id']])) {
                $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['secret_chat_skipping'], $update['message']['chat_id']));
                return false;
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
                    $this->logger->logger('Accepting secret chat '.$update['chat']['id'], \danog\MadelineProto\Logger::NOTICE);
                    try {
                        yield from $this->acceptSecretChat($update['chat']);
                    } catch (RPCErrorException $e) {
                        $this->logger->logger("Error while accepting secret chat: {$e}", Logger::FATAL_ERROR);
                    }
                    break;
                case 'encryptedChatDiscarded':
                    $this->logger->logger('Deleting secret chat '.$update['chat']['id'].' because it was revoked by the other user (it was probably accepted by another client)', \danog\MadelineProto\Logger::NOTICE);
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
                    $this->logger->logger('Completing creation of secret chat '.$update['chat']['id'], \danog\MadelineProto\Logger::NOTICE);
                    yield from $this->completeSecretChat($update['chat']);
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
