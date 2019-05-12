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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Artax\Request;
use Amp\Deferred;
use Amp\Delayed;
use Amp\Loop;
use function Amp\Promise\any;

/**
 * Manages updates.
 */
trait UpdateHandler
{
    private $pending_updates = [];
    private $updates_state;
    private $got_state = false;
    private $channels_state;
    public $updates = [];
    public $updates_key = 0;
    public $last_getdifference = 0;

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
            $this->datacenter->sockets[$this->settings['connection_settings']['default_dc']]->updater->start();
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
            yield any([$this->update_deferred->promise(), new Delayed($params['timeout'] * 1000)]);
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

    public function check_msg_id($message)
    {
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

    public function get_channel_difference_async($channel)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($this->channels_state->syncLoading($channel)) {
            $this->logger->logger('Not fetching '.$channel.' difference, I am already fetching it');

            return;
        }
        $this->channels_state->syncLoading($channel, true);
        $this->postpone_updates = true;

        try {
            $input = yield $this->get_info_async('channel#'.$channel);
            if (!isset($input['InputChannel'])) {
                throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
            }
            $input = $input['InputChannel'];
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            return false;
        } finally {
            $this->postpone_updates = false;
            $this->channels_state->syncLoading($channel, false);
        }
        $this->logger->logger('Fetching '.$channel.' difference...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->channels_state->syncLoading($channel, true);
        $this->postpone_updates = true;

        try {
            $difference = yield $this->method_call_async_read('updates.getChannelDifference', ['channel' => $input, 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $this->channels_state->get($channel)->pts(), 'limit' => 30], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->getMessage() === "You haven't joined this channel/supergroup") {
                return false;
            }

            throw $e;
        } catch (\danog\MadelineProto\PTSException $e) {
            $this->logger->logger($e->getMessage());
            $this->channels_state->remove($channel);

            return false; //yield $this->get_channel_difference_async($channel);
        } finally {
            $this->postpone_updates = false;
            $this->channels_state->syncLoading($channel, false);
        }
        unset($input);

        switch ($difference['_']) {
            case 'updates.channelDifferenceEmpty':
                $this->channels_state->get($channel, $difference);
                break;
            case 'updates.channelDifference':
                $this->channels_state->syncLoading($channel, true);
                $this->postpone_updates = true;

                try {
                    $this->channels_state->get($channel, $difference);
                    yield $this->handle_update_messages_async($difference['new_messages'], $channel);
                    yield $this->handle_multiple_update_async($difference['other_updates'], [], $channel);
                } finally {
                    $this->postpone_updates = false;
                    $this->channels_state->syncLoading($channel, false);
                }
                if (!$difference['final']) {
                    unset($difference);
                    yield $this->get_channel_difference_async($channel);
                }
                break;
            case 'updates.channelDifferenceTooLong':
                $this->logger->logger('Got '.$difference['_'], \danog\MadelineProto\Logger::VERBOSE);
                $this->channels_state->syncLoading($channel, true);
                $this->postpone_updates = true;

                try {
                    $this->channels_state->get($channel, $difference);
                    yield $this->handle_update_messages_async($difference['messages'], $channel);
                    unset($difference);
                } finally {
                    $this->postpone_updates = false;
                    $this->channels_state->syncLoading($channel, false);
                }
                yield $this->get_channel_difference_async($channel);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                break;
        }
        yield $this->handle_pending_updates_async();
    }

    public function load_update_state_async()
    {
        if (!$this->got_state) {
            $this->got_state = true;
            $this->updates_state->update(yield $this->get_updates_state_async());
        }

        return $this->updates_state;
    }

    public function get_updates_difference_async($w = null)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($this->updates_state->syncLoading()) {
            $this->logger->logger('Not fetching normal difference, I am already fetching it');

            return false;
        }
        $this->updates_state->syncLoading(true);
        $this->postpone_updates = true;
        $this->logger->logger('Fetching normal difference...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        while (!isset($difference)) {
            try {
                $state = yield $this->load_update_state_async();
                $difference = yield $this->method_call_async_read('updates.getDifference', ['pts' => $state->pts(), 'date' => $state->date(), 'qts' => $state->qts()], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
            } catch (\danog\MadelineProto\PTSException $e) {
                $this->updates_state->syncLoading(false);
                $this->got_state = false;
            } finally {
                $this->postpone_updates = false;
                $this->updates_state->syncLoading(false);
            }
        }
        $this->logger->logger('Got '.$difference['_'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->postpone_updates = true;
        $this->updates_state->syncLoading(true);
        $this->last_getdifference = time();
        $this->datacenter->sockets[$this->settings['connection_settings']['default_dc']]->updater->resume();

        try {
            switch ($difference['_']) {
                case 'updates.differenceEmpty':
                    $this->updates_state->update($difference);
                    break;
                case 'updates.difference':
                    $this->updates_state->syncLoading(true);
                    yield $this->handle_multiple_update_async($difference['other_updates']);
                    foreach ($difference['new_encrypted_messages'] as $encrypted) {
                        yield $this->handle_encrypted_update_async(['_' => 'updateNewEncryptedMessage', 'message' => $encrypted], true);
                    }
                    yield $this->handle_update_messages_async($difference['new_messages']);
                    $this->updates_state->update($difference['state']);
                    break;
                case 'updates.differenceSlice':
                    $this->updates_state->syncLoading(true);
                    yield $this->handle_multiple_update_async($difference['other_updates']);
                    yield $this->handle_update_messages_async($difference['new_messages']);
                    $this->updates_state->update($difference['intermediate_state']);
                    unset($difference);
                    $this->updates_state->syncLoading(false);
                    yield $this->get_updates_difference_async();
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                    break;
            }
        } finally {
            $this->postpone_updates = false;
            $this->updates_state->syncLoading(false);
        }
        yield $this->handle_pending_updates_async();

        if ($this->updates && $this->update_deferred) {
            $d = $this->update_deferred;
            $this->update_deferred = null;

            Loop::defer([$d, 'resolve']);
        }

        return true;
    }

    public function get_updates_state_async()
    {
        $last = $this->updates_state->syncLoading();
        $this->updates_state->syncLoading(true);

        try {
            $data = yield $this->method_call_async_read('updates.getState', [], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
            yield $this->get_cdn_config_async($this->settings['connection_settings']['default_dc']);
        } finally {
            $this->updates_state->syncLoading($last);
        }

        return $data;
    }

    public function handle_update_async($update, $options = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->logger->logger('Handling an update of type '.$update['_'].'...', \danog\MadelineProto\Logger::VERBOSE);
        $channel_id = false;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                if ($update['message']['_'] === 'messageEmpty') {
                    $this->logger->logger('Got message empty, not saving', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    return false;
                }
                $channel_id = $update['message']['to_id']['channel_id'];
                break;
            case 'updateDeleteChannelMessages':
                $channel_id = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
                $channel_id = $update['channel_id'];
                $this->logger->logger('Got channel too long update, getting difference...', \danog\MadelineProto\Logger::VERBOSE);
                if (!$this->channels_state->has($channel_id) && !isset($update['pts'])) {
                    $this->logger->logger('I do not have the channel in the states and the pts is not set.', \danog\MadelineProto\Logger::ERROR);

                    return;
                }
                break;
        }

        if ($channel_id === false) {
            $cur_state = yield $this->load_update_state_async();
        } else {
            $cur_state = $this->channels_state->get($channel_id, $update);
        }
        /*
        if ($cur_state['sync_loading'] && in_array($update['_'], ['updateNewMessage', 'updateEditMessage', 'updateNewChannelMessage', 'updateEditChannelMessage'])) {
        $this->logger->logger('Sync loading, not handling update', \danog\MadelineProto\Logger::NOTICE);

        return false;
        }*/
        switch ($update['_']) {
            case 'updateChannelTooLong':
                yield $this->get_channel_difference_async($channel_id);

                return false;
            case 'updateNewMessage':
            case 'updateEditMessage':
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                $to = false;
                $from = false;
                $via_bot = false;
                $entities = false;
                if (($from = isset($update['message']['from_id']) && !yield $this->peer_isset_async($update['message']['from_id'])) ||
                    ($to = !yield $this->peer_isset_async($update['message']['to_id'])) ||
                    ($via_bot = isset($update['message']['via_bot_id']) && !yield $this->peer_isset_async($update['message']['via_bot_id'])) ||
                    ($entities = isset($update['message']['entities']) && !yield $this->entities_peer_isset_async($update['message']['entities'])) // ||
                    //isset($update['message']['fwd_from']) && !yield $this->fwd_peer_isset_async($update['message']['fwd_from'])
                ) {
                    $log = '';
                    if ($from) {
                        $log .= "from_id {$update['message']['from_id']}, ";
                    }

                    if ($to) {
                        $log .= "to_id ".json_encode($update['message']['to_id']).", ";
                    }

                    if ($via_bot) {
                        $log .= "via_bot {$update['message']['via_bot_id']}, ";
                    }

                    if ($entities) {
                        $log .= "entities ".json_encode($update['message']['entities']).", ";
                    }

                    $this->logger->logger("Not enough data: for message update $log, getting difference...", \danog\MadelineProto\Logger::VERBOSE);
                    if ($channel_id !== false && yield $this->peer_isset_async($this->to_supergroup($channel_id))) {
                        yield $this->get_channel_difference_async($channel_id);
                    } else {
                        yield $this->get_updates_difference_async();
                    }

                    return false;
                }
                break;
            default:
                if ($channel_id !== false && !yield $this->peer_isset_async($this->to_supergroup($channel_id))) {
                    $this->logger->logger('Skipping update, I do not have the channel id '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                    return false;
                }
                break;
        }
        if (isset($update['pts'])) {
            $logger = function ($msg) use ($update, $cur_state, $channel_id) {
                $pts_count = isset($update['pts_count']) ? $update['pts_count'] : 0;
                $this->logger->logger($update);
                $double = isset($update['message']['id']) ? $update['message']['id'] * 2 : '-';
                $mid = isset($update['message']['id']) ? $update['message']['id'] : '-';
                $mypts = $cur_state->pts();
                $this->logger->logger("$msg. My pts: {$mypts}, remote pts: {$update['pts']}, remote pts count: {$pts_count}, msg id: {$mid} (*2=$double), channel id: $channel_id", \danog\MadelineProto\Logger::ERROR);
            };
            if ($update['pts'] < $cur_state->pts()) {
                $logger("PTS duplicate");

                return false;
            }
            if ($cur_state->pts() + (isset($update['pts_count']) ? $update['pts_count'] : 0) !== $update['pts']) {
                $logger("PTS hole");
                if ($channel_id !== false && yield $this->peer_isset_async($this->to_supergroup($channel_id))) {
                    yield $this->get_channel_difference_async($channel_id);
                } else {
                    yield $this->get_updates_difference_async();
                }

                return false;
            }
            if (isset($update['message']['id'], $update['message']['to_id']) && !in_array($update['_'], ['updateEditMessage', 'updateEditChannelMessage'])) {
                if (!$this->check_msg_id($update['message'])) {
                    $logger("MSGID duplicate");

                    return false;
                }
            }
            $logger("PTS OK");

            //$this->logger->logger('Applying pts. my pts: '.$cur_state->pts().', remote pts: '.$update['pts'].', channel id: '.$channel_id, \danog\MadelineProto\Logger::VERBOSE);
            $cur_state->pts($update['pts']);
            if ($channel_id === false && isset($options['date'])) {
                $cur_state->date($options['date']);
            }
        }
        if ($channel_id === false && isset($options['seq']) || isset($options['seq_start'])) {
            $seq = $options['seq'];
            $seq_start = isset($options['seq_start']) ? $options['seq_start'] : $options['seq'];
            if ($seq_start != $cur_state->seq() + 1 && $seq_start > $cur_state->seq()) {
                $this->logger->logger('Seq hole. seq_start: '.$seq_start.' != cur seq: '.$cur_state->seq().' + 1', \danog\MadelineProto\Logger::ERROR);
                yield $this->get_updates_difference_async();

                return false;
            }
            if ($cur_state->seq() !== $seq) {
                $cur_state->seq($seq);
                if (isset($options['date'])) {
                    $cur_state->date($options['date']);
                }
            }
        }
        yield $this->save_update_async($update);
    }

    public function handle_multiple_update_async($updates, $options = [], $channel = false)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($channel === false) {
            foreach ($updates as $update) {
                yield $this->handle_update_async($update, $options);
            }
        } else {
            foreach ($updates as $update) {
                yield $this->handle_update_async($update);
            }
        }
    }

    public function handle_update_messages_async($messages, $channel = false)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        foreach ($messages as $message) {
            yield $this->handle_update_async(['_' => $channel === false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => ($channel === false ? (yield $this->load_update_state_async()) : $this->channels_state->get($channel))->pts(), 'pts_count' => 0]);
        }
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
                yield $this->get_updates_difference_async();

                return false;
            }
            $this->logger->logger('Applying qts: '.$update['qts'].' over current qts '.$cur_state->qts().', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::VERBOSE);
            yield $this->method_call_async_read('messages.receivedQueue', ['max_qts' => $cur_state->qts($update['qts'])], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
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
