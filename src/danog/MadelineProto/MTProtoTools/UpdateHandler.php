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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Deferred;
use Amp\Http\Client\Request;
use Amp\Loop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\RPCErrorException;

/**
 * Manages updates.
 */
trait UpdateHandler
{
    private $got_state = false;
    private $channels_state;
    public $updates = [];
    public $updates_key = 0;

    /**
     * PWR update handler.
     *
     * @param array $update Update
     *
     * @internal
     *
     * @return void
     */
    public function pwrUpdateHandler($update)
    {
        if (isset($this->settings['pwr']['updateHandler'])) {
            if (\is_array($this->settings['pwr']['updateHandler']) && $this->settings['pwr']['updateHandler'][0] === false) {
                $this->settings['pwr']['updateHandler'] = $this->settings['pwr']['updateHandler'][1];
            }
            if (\is_string($this->settings['pwr']['updateHandler'])) {
                return $this->{$this->settings['pwr']['updateHandler']}($update);
            }
            \in_array($this->settings['pwr']['updateHandler'], [['danog\\MadelineProto\\API', 'getUpdatesUpdateHandler'], 'getUpdatesUpdateHandler']) ? $this->getUpdatesUpdateHandler($update) : $this->settings['pwr']['updateHandler']($update);
        }
    }

    /**
     * Getupdates update handler.
     *
     * @param array $update Update
     *
     * @internal
     *
     * @return void
     */
    public function getUpdatesUpdateHandler(array $update): void
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->updates[$this->updates_key++] = $update;
    }

    /**
     * Get updates.
     *
     * @param array $params Params
     *
     * @internal
     *
     * @return \Generator<array>
     */
    public function getUpdates($params = []): \Generator
    {
        if (!$this->settings['updates']['handle_updates']) {
            $this->settings['updates']['handle_updates'] = true;
            $this->startUpdateSystem();
        }
        if (!$this->settings['updates']['run_callback']) {
            $this->settings['updates']['run_callback'] = true;
        }

        $params = \array_merge(self::DEFAULT_GETUPDATES_PARAMS, $params);

        if (empty($this->updates)) {
            $this->update_deferred = new Deferred();
            if (!$params['timeout']) {
                $params['timeout'] = 0.001;
            }
            yield \danog\MadelineProto\Tools::first([$this->waitUpdate(), \danog\MadelineProto\Tools::sleep($params['timeout'])]);
        }

        if (empty($this->updates)) {
            return [];
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

        return $updates;
    }

    public $update_resolved = false;
    public $update_deferred;

    /**
     * Wait for update.
     *
     * @internal
     *
     * @return \Generator
     */
    public function waitUpdate(): \Generator
    {
        if (!$this->update_deferred) {
            $this->update_deferred = new Deferred();
        }
        yield $this->update_deferred->promise();
        $this->update_resolved = false;
        $this->update_deferred = new Deferred();
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
        if (!$this->update_deferred) {
            $this->update_deferred = new Deferred();
        }
        Loop::defer(function () {
            if (!$this->update_resolved) {
                $this->update_resolved = true;
                $this->update_deferred->resolve();
            }
        });
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
        if (!isset($message['to_id'])) {
            return true;
        }

        try {
            $peer_id = $this->getId($message['to_id']);
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
     * @return UpdatesState|UpdatesState[]
     */
    public function loadUpdateState()
    {
        if (!$this->got_state) {
            $this->got_state = true;
            $this->channels_state->get(false, yield $this->getUpdatesState());
        }

        return $this->channels_state->get(false);
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
        $data = yield $this->methodCallAsyncRead('updates.getState', [], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
        yield $this->getCdnConfig($this->settings['connection_settings']['default_dc']);

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
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($actual_updates) {
            $updates = $actual_updates;
        }
        $this->logger->logger('Parsing updates ('.$updates['_'].') received via the socket...', \danog\MadelineProto\Logger::VERBOSE);
        switch ($updates['_']) {
            case 'updates':
            case 'updatesCombined':
                $result = [];
                foreach ($updates['updates'] as $key => $update) {
                    if ($update['_'] === 'updateNewMessage' || $update['_'] === 'updateReadMessagesContents' ||
                        $update['_'] === 'updateEditMessage' || $update['_'] === 'updateDeleteMessages' ||
                        $update['_'] === 'updateReadHistoryInbox' || $update['_'] === 'updateReadHistoryOutbox' ||
                        $update['_'] === 'updateWebPage' || $update['_'] === 'updateMessageID') {
                        $result[yield $this->feeders[false]->feedSingle($update)] = true;
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
                $this->feeders[yield $this->feeders[false]->feedSingle($updates['update'])]->resume();
                break;
            case 'updateShortSentMessage':
                if (!isset($updates['request']['body'])) {
                    break;
                }
                $updates['user_id'] = (yield $this->getInfo($updates['request']['body']['peer']))['bot_api_id'];
                $updates['message'] = $updates['request']['body']['message'];
                unset($updates['request']);
                // no break
            case 'updateShortMessage':
            case 'updateShortChatMessage':
                $from_id = isset($updates['from_id']) ? $updates['from_id'] : ($updates['out'] ? $this->authorization['user']['id'] : $updates['user_id']);
                $to_id = isset($updates['chat_id']) ? -$updates['chat_id'] : ($updates['out'] ? $updates['user_id'] : $this->authorization['user']['id']);
                if (!yield $this->peerIsset($from_id) || !yield $this->peerIsset($to_id) || isset($updates['via_bot_id']) && !yield $this->peerIsset($updates['via_bot_id']) || isset($updates['entities']) && !yield $this->entitiesPeerIsset($updates['entities']) || isset($updates['fwd_from']) && !yield $this->fwdPeerIsset($updates['fwd_from'])) {
                    yield $this->updaters[false]->resume();

                    return;
                }
                $message = $updates;
                $message['_'] = 'message';
                $message['from_id'] = $from_id;

                try {
                    $message['to_id'] = (yield $this->getInfo($to_id))['Peer'];
                } catch (\danog\MadelineProto\Exception $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    //$this->pending_updates[] = $updates;
                    break;
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $this->logger->logger('Still did not get user in database, postponing update', \danog\MadelineProto\Logger::ERROR);
                    //$this->pending_updates[] = $updates;
                    break;
                }
                $update = ['_' => 'updateNewMessage', 'message' => $message, 'pts' => $updates['pts'], 'pts_count' => $updates['pts_count']];
                $this->feeders[yield $this->feeders[false]->feedSingle($update)]->resume();
                break;
            case 'updatesTooLong':
                $this->updaters[false]->resume();
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
            yield $this->getConfig();
        }
        if (\in_array($update['_'], ['updateUserName', 'updateUserPhone', 'updateUserBlocked', 'updateUserPhoto', 'updateContactRegistered', 'updateContactLink'])) {
            $id = $this->getId($update);
            $this->full_chats[$id]['last_update'] = 0;
            yield $this->getFullInfo($id);
        }
        if ($update['_'] === 'updateDcOptions') {
            $this->logger->logger('Got new dc options', \danog\MadelineProto\Logger::VERBOSE);
            $this->config['dc_options'] = $update['dc_options'];
            yield $this->parseConfig();

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
                    if (!yield $this->confirmCall($update['phone_call'])) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCall':
                    if (!yield $this->completeCall($update['phone_call'])) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCallDiscarded':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }

                    return $this->calls[$update['phone_call']['id']]->discard($update['phone_call']['reason'], [], $update['phone_call']['need_debug']);
            }
        }
        if ($update['_'] === 'updateNewEncryptedMessage' && !isset($update['message']['decrypted_message'])) {
            if (isset($update['qts'])) {
                $cur_state = yield $this->loadUpdateState();
                if ($cur_state->qts() === -1) {
                    $cur_state->qts($update['qts']);
                }
                if ($update['qts'] < $cur_state->qts()) {
                    $this->logger->logger('Duplicate update. update qts: '.$update['qts'].' <= current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::ERROR);

                    return false;
                }
                if ($update['qts'] > $cur_state->qts() + 1) {
                    $this->logger->logger('Qts hole. Fetching updates manually: update qts: '.$update['qts'].' > current qts '.$cur_state->qts().'+1, chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::ERROR);
                    $this->updaters[false]->resumeDefer();

                    return false;
                }
                $this->logger->logger('Applying qts: '.$update['qts'].' over current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::VERBOSE);
                yield $this->methodCallAsyncRead('messages.receivedQueue', ['max_qts' => $cur_state->qts($update['qts'])], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
            }
            yield $this->handleEncryptedUpdate($update);

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
                    if ($this->settings['secret_chats']['accept_chats'] === false || \is_array($this->settings['secret_chats']['accept_chats']) && !\in_array($update['chat']['admin_id'], $this->settings['secret_chats']['accept_chats'])) {
                        return;
                    }
                    $this->logger->logger('Accepting secret chat '.$update['chat']['id'], \danog\MadelineProto\Logger::NOTICE);
                    try {
                        yield $this->acceptSecretChat($update['chat']);
                    } catch (RPCErrorException $e) {
                        $this->logger->logger("Error while accepting secret chat: $e", Logger::FATAL_ERROR);
                    }
                    break;
                case 'encryptedChatDiscarded':
                    $this->logger->logger('Deleting secret chat '.$update['chat']['id'].' because it was revoked by the other user', \danog\MadelineProto\Logger::NOTICE);
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
                    yield $this->completeSecretChat($update['chat']);
                    break;
            }
            //$this->logger->logger($update, \danog\MadelineProto\Logger::NOTICE);
        }
        //if ($update['_'] === 'updateServiceNotification' && strpos($update['type'], 'AUTH_KEY_DROP_') === 0) {

        //}
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'messageEmpty') {
            return;
        }
        if (isset($update['message']['from_id']) && $update['message']['from_id'] === $this->authorization['user']['id']) {
            $update['message']['out'] = true;
        }
        //$this->logger->logger('Saving an update of type '.$update['_'].'...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict'] && isset($this->settings['pwr']['updateHandler'])) {
            $this->pwrUpdateHandler($update);
        } elseif ($this->settings['updates']['run_callback']) {
            $this->getUpdatesUpdateHandler($update);
        }
    }

    /**
     * Send update to webhook.
     *
     * @param array $update Update
     *
     * @return void
     */
    private function pwrWebhook(array $update): void
    {
        $payload = \json_encode($update);
        //$this->logger->logger($update, $payload, json_last_error());
        if ($payload === '') {
            $this->logger->logger('EMPTY UPDATE');

            return;
        }
        \danog\MadelineProto\Tools::callFork((function () use ($payload) {
            $request = new Request($this->hook_url, 'POST');
            $request->setHeader('content-type', 'application/json');
            $request->setBody($payload);

            $result = yield (yield $this->datacenter->getHTTPClient()->request($request))->getBody()->buffer();

            $this->logger->logger('Result of webhook query is '.$result, \danog\MadelineProto\Logger::NOTICE);
            $result = \json_decode($result, true);
            if (\is_array($result) && isset($result['method']) && $result['method'] != '' && \is_string($result['method'])) {
                try {
                    $this->logger->logger('Reverse webhook command returned', yield $this->methodCallAsyncRead($result['method'], $result, ['datacenter' => $this->datacenter->curdc]));
                } catch (\Throwable $e) {
                    $this->logger->logger("Reverse webhook command returned: $e");
                }
            }
        })());
    }
}
