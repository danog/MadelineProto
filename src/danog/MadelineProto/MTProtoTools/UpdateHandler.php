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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Artax\Request;
use Amp\Deferred;
use Amp\Loop;

/**
 * Manages updates.
 */
trait UpdateHandler
{
    private $got_state = false;
    private $channels_state;
    public $updates = [];
    public $updates_key = 0;

    public function pwr_update_handler($update)
    {
        if (isset($this->settings['pwr']['update_handler'])) {
            if (is_array($this->settings['pwr']['update_handler']) && $this->settings['pwr']['update_handler'][0] === false) {
                $this->settings['pwr']['update_handler'] = $this->settings['pwr']['update_handler'][1];
            }
            if (is_string($this->settings['pwr']['update_handler'])) {
                return $this->{$this->settings['pwr']['update_handler']}($update);
            }
            in_array($this->settings['pwr']['update_handler'], [['danog\\MadelineProto\\API', 'get_updates_update_handler'], 'get_updates_update_handler']) ? $this->get_updates_update_handler($update) : $this->settings['pwr']['update_handler']($update);
        }
    }

    public function get_updates_update_handler($update)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->updates[$this->updates_key++] = $update;
    }

    public function get_updates_async($params = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            $this->settings['updates']['handle_updates'] = true;
            $this->startUpdateSystem();
        }
        if (!$this->settings['updates']['run_callback']) {
            $this->settings['updates']['run_callback'] = true;
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });

        $params = array_merge(self::DEFAULT_GETUPDATES_PARAMS, $params);

        if (empty($this->updates)) {
            $this->update_deferred = new Deferred();
            if (!$params['timeout']) {
                $params['timeout'] = 0.001;
            }
            yield $this->first([$this->waitUpdate(), $this->sleep($params['timeout'])]);
        }

        if (empty($this->updates)) {
            return [];
        }

        if ($params['offset'] < 0) {
            $params['offset'] = array_reverse(array_keys((array) $this->updates))[abs($params['offset']) - 1];
        }
        $updates = [];
        foreach ($this->updates as $key => $value) {
            if ($params['offset'] > $key) {
                unset($this->updates[$key]);
            } elseif ($params['limit'] === null || count($updates) < $params['limit']) {
                $updates[] = ['update_id' => $key, 'update' => $value];
            }
        }

        return $updates;
    }

    public $update_resolved = false;
    public $update_deferred;

    public function waitUpdate()
    {
        if (!$this->update_deferred) {
            $this->update_deferred = new Deferred();
        }
        yield $this->update_deferred->promise();
        $this->update_resolved = false;
        $this->update_deferred = new Deferred();
    }

    public function signalUpdate()
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

    public function check_msg_id($message)
    {
        if (!isset($message['to_id'])) {
            return true;
        }

        try {
            $peer_id = $this->get_id($message['to_id']);
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

    public function load_update_state_async()
    {
        if (!$this->got_state) {
            $this->got_state = true;
            $this->channels_state->get(false, yield $this->get_updates_state_async());
        }

        return $this->channels_state->get(false);
    }

    public function loadChannelState($channelId = null, $init = [])
    {
        return $this->channels_state->get($channelId, $init);
    }

    public function getChannelStates()
    {
        return $this->channels_state;
    }

    public function get_updates_state_async()
    {
        $data = yield $this->method_call_async_read('updates.getState', [], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
        yield $this->get_cdn_config_async($this->settings['connection_settings']['default_dc']);

        return $data;
    }

    public function save_update_async($update)
    {
        if ($update['_'] === 'updateConfig') {
            $this->config['expires'] = 0;
            yield $this->get_config_async();
        }
        if (in_array($update['_'], ['updateUserName', 'updateUserPhone', 'updateUserBlocked', 'updateUserPhoto', 'updateContactRegistered', 'updateContactLink'])) {
            $id = $this->get_id($update);
            $this->full_chats[$id]['last_update'] = 0;
            yield $this->get_full_info_async($id);
        }
        if ($update['_'] === 'updateDcOptions') {
            $this->logger->logger('Got new dc options', \danog\MadelineProto\Logger::VERBOSE);
            yield $this->parse_dc_options_async($update['dc_options']);

            return;
        }
        if ($update['_'] === 'updatePhoneCall') {
            if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
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
                    if (!yield $this->confirm_call_async($update['phone_call'])) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCall':
                    if (!yield $this->complete_call_async($update['phone_call'])) {
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
                $cur_state = yield $this->load_update_state_async();
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
                yield $this->method_call_async_read('messages.receivedQueue', ['max_qts' => $cur_state->qts($update['qts'])], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
            }
            yield $this->handle_encrypted_update_async($update);

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
                    if ($this->settings['secret_chats']['accept_chats'] === false || is_array($this->settings['secret_chats']['accept_chats']) && !in_array($update['chat']['admin_id'], $this->settings['secret_chats']['accept_chats'])) {
                        return;
                    }
                    $this->logger->logger('Accepting secret chat '.$update['chat']['id'], \danog\MadelineProto\Logger::NOTICE);
                    yield $this->accept_secret_chat_async($update['chat']);
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
                    yield $this->complete_secret_chat_async($update['chat']);
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
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict'] && isset($this->settings['pwr']['update_handler'])) {
            $this->pwr_update_handler($update);
        } elseif ($this->settings['updates']['run_callback']) {
            $this->get_updates_update_handler($update);
        }
    }

    public function pwr_webhook($update)
    {
        $payload = json_encode($update);
        //$this->logger->logger($update, $payload, json_last_error());
        if ($payload === '') {
            $this->logger->logger('EMPTY UPDATE');

            return false;
        }
        $this->callFork((function () use ($payload) {
            $request = (new Request($this->hook_url, 'POST'))->withHeader('content-type', 'application/json')->withBody($payload);

            $result = yield (yield $this->datacenter->getHTTPClient()->request($request))->getBody();

            $this->logger->logger('Result of webhook query is '.$result, \danog\MadelineProto\Logger::NOTICE);
            $result = json_decode($result, true);
            if (is_array($result) && isset($result['method']) && $result['method'] != '' && is_string($result['method'])) {
                try {
                    $this->logger->logger('Reverse webhook command returned', yield $this->method_call_async_read($result['method'], $result, ['datacenter' => $this->datacenter->curdc]));
                } catch (\Throwable $e) {
                    $this->logger->logger("Reverse webhook command returned: $e");
                }
            }
        })());
    }
}
