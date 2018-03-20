<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages updates.
 */
trait UpdateHandler
{
    private $pending_updates = [];
    private $updates_state = ['_' => 'MadelineProto.Updates_state', 'seq' => 0, 'pts' => 0, 'date' => 0, 'qts' => 0];
    private $got_state = false;
    private $channels_state = [];
    public $updates = [];
    public $updates_key = 0;

    public function pwr_update_handler($update)
    {
        /*
        if (isset($update['message']['to_id'])) {
            try {
                $full_chat = $this->get_pwr_chat($update['message']['to_id']);
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            }
        }
        if (isset($update['message']['from_id'])) {
            try {
                $full_chat = $this->get_pwr_chat($update['message']['from_id']);
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            }
        }
        */
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
        //\danog\MadelineProto\Logger::log(['Stored ', $update);
    }

    public function get_updates($params = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            $this->settings['updates']['handle_updates'] = true;
        }
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        $time = microtime(true);

        try {
            try {
                if (($error = $this->recv_message($this->datacenter->curdc)) !== true) {
                    if ($error === -404) {
                        if ($this->datacenter->sockets[$this->datacenter->curdc]->temp_auth_key !== null) {
                            \danog\MadelineProto\Logger::log('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                            $this->datacenter->sockets[$this->datacenter->curdc]->temp_auth_key = null;
                            $this->init_authorization();

                            throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                        }
                    }

                    throw new \danog\MadelineProto\RPCErrorException($error, $error);
                }
                $only_updates = $this->handle_messages($this->datacenter->curdc);
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
            }
            if (time() - $this->datacenter->sockets[$this->datacenter->curdc]->last_recv > $this->settings['updates']['getdifference_interval']) {
                $this->get_updates_difference();
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc !== 'RPC_CALL_FAIL') {
                throw $e;
            }
        } catch (\danog\MadelineProto\Exception $e) {
            $this->connect_to_all_dcs();
        }
        $default_params = ['offset' => 0, 'limit' => null, 'timeout' => 0];
        $params = array_merge($default_params, $params);
        $params['timeout'] = (int) ($params['timeout'] * 1000000 - (microtime(true) - $time));
        usleep($params['timeout'] > 0 ? $params['timeout'] : 0);
        if (empty($this->updates)) {
            return [];
        }
        if ($params['offset'] < 0) {
            $params['offset'] = array_reverse(array_keys((array) $this->updates))[abs($params['offset']) - 1];
        }
        $updates = [];
        if (isset($this->updates["\0*\0state"])) {
            unset($this->updates["\0*\0state"]);
        }
        $supdates = (array) $this->updates;
        ksort($supdates);
        foreach ($supdates as $key => $value) {
            if ($params['offset'] > $key) {
                unset($this->updates[$key]);
            } elseif ($params['limit'] === null || count($updates) < $params['limit']) {
                $updates[] = ['update_id' => $key, 'update' => $value];
            }
        }

        return $updates;
    }

    public function &load_channel_state($channel, $pts = 0)
    {
        if (!isset($this->channels_state[$channel])) {
            $this->channels_state[$channel] = ['pts' => $pts, 'sync_loading' => false];
        }

        return $this->channels_state[$channel];
    }

    public function set_channel_state($channel, $data)
    {
        if (isset($data['pts']) && $data['pts'] !== 0) {
            $this->load_channel_state($channel)['pts'] = $data['pts'];
        }
    }

    public function check_msg_id($message)
    {
        try {
            $peer_id = $this->get_info($message['to_id'])['bot_api_id'];
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

    public function get_channel_difference($channel)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($this->load_channel_state($channel)['sync_loading']) {
            \danog\MadelineProto\Logger::log('Not fetching '.$channel.' difference, I am already fetching it');

            return;
        }
        $this->load_channel_state($channel)['sync_loading'] = true;
        $this->postpone_updates = true;

        try {
            $input = $this->get_info('channel#'.$channel);
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
            $this->load_channel_state($channel)['sync_loading'] = false;
        }
        \danog\MadelineProto\Logger::log('Fetching '.$channel.' difference...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->load_channel_state($channel)['sync_loading'] = true;
        $this->postpone_updates = true;

        try {
            $difference = $this->method_call('updates.getChannelDifference', ['channel' => $input, 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $this->load_channel_state($channel)['pts'], 'limit' => 30], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->getMessage() === "You haven't joined this channel/supergroup") {
                return false;
            }

            throw $e;
        } catch (\danog\MadelineProto\PTSException $e) {
            unset($this->channels_state[$channel]);
            $this->load_channel_state($channel)['sync_loading'] = false;

            return $this->get_channel_difference($channel);
        } finally {
            $this->postpone_updates = false;
            $this->load_channel_state($channel)['sync_loading'] = false;
        }
        unset($input);

        switch ($difference['_']) {
            case 'updates.channelDifferenceEmpty':
                $this->set_channel_state($channel, $difference);
                break;
            case 'updates.channelDifference':
                $this->load_channel_state($channel)['sync_loading'] = true;
                $this->postpone_updates = true;

                try {
                    $this->set_channel_state($channel, $difference);
                    $this->handle_update_messages($difference['new_messages'], $channel);
                    $this->handle_multiple_update($difference['other_updates'], [], $channel);
                } finally {
                    $this->postpone_updates = false;
                    $this->load_channel_state($channel)['sync_loading'] = false;
                }
                if (!$difference['final']) {
                    unset($difference);
                    $this->get_channel_difference($channel);
                }
                break;
            case 'updates.channelDifferenceTooLong':
                \danog\MadelineProto\Logger::log('Got '.$difference['_'], \danog\MadelineProto\Logger::VERBOSE);
                $this->load_channel_state($channel)['sync_loading'] = true;
                $this->postpone_updates = true;

                try {
                    $this->set_channel_state($channel, $difference);
                    $this->handle_update_messages($difference['messages'], $channel);
                    unset($difference);
                } finally {
                    $this->postpone_updates = false;
                    $this->load_channel_state($channel)['sync_loading'] = false;
                }
                $this->get_channel_difference($channel);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                break;
        }
        $this->handle_pending_updates();
    }

    public function set_update_state($data)
    {
        if (isset($data['pts']) && $data['pts'] !== 0) {
            $this->load_update_state()['pts'] = $data['pts'];
        }
        if (isset($data['qts']) && $data['qts'] !== 0) {
            $this->load_update_state()['qts'] = $data['qts'];
        }
        if (isset($data['seq']) && $data['seq'] !== 0) {
            $this->load_update_state()['seq'] = $data['seq'];
        }
        if (isset($data['date']) && $data['date'] > $this->load_update_state()['date']) {
            $this->load_update_state()['date'] = $data['date'];
        }
    }

    public function &load_update_state()
    {
        if (!isset($this->updates_state['qts'])) {
            $this->updates_state['qts'] = 0;
        }
        if (!$this->got_state) {
            $this->got_state = true;
            $this->set_update_state($this->get_updates_state());
        }

        return $this->updates_state;
    }

    public function get_updates_difference()
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($this->updates_state['sync_loading']) {
            \danog\MadelineProto\Logger::log('Not fetching normal difference, I am already fetching it');

            return false;
        }
        $this->updates_state['sync_loading'] = true;
        $this->postpone_updates = true;
        \danog\MadelineProto\Logger::log('Fetching normal difference...', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        while (!isset($difference)) {
            try {
                $difference = $this->method_call('updates.getDifference', ['pts' => $this->load_update_state()['pts'], 'date' => $this->load_update_state()['date'], 'qts' => $this->load_update_state()['qts']], ['datacenter' => $this->datacenter->curdc]);
            } catch (\danog\MadelineProto\PTSException $e) {
                $this->updates_state['sync_loading'] = false;
                $this->got_state = false;
            } finally {
                $this->postpone_updates = false;
                $this->updates_state['sync_loading'] = false;
            }
        }
        \danog\MadelineProto\Logger::log('Got '.$difference['_'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->postpone_updates = true;
        $this->updates_state['sync_loading'] = true;

        try {
            switch ($difference['_']) {
                case 'updates.differenceEmpty':
                    $this->set_update_state($difference);
                    break;
                case 'updates.difference':
                    $this->updates_state['sync_loading'] = true;
                    $this->handle_multiple_update($difference['other_updates']);
                    foreach ($difference['new_encrypted_messages'] as $encrypted) {
                        $this->handle_encrypted_update(['_' => 'updateNewEncryptedMessage', 'message' => $encrypted], true);
                    }
                    $this->handle_update_messages($difference['new_messages']);
                    $this->set_update_state($difference['state']);
                    break;
                case 'updates.differenceSlice':
                    $this->updates_state['sync_loading'] = true;
                    $this->handle_multiple_update($difference['other_updates']);
                    $this->handle_update_messages($difference['new_messages']);
                    $this->set_update_state($difference['intermediate_state']);
                    unset($difference);
                    $this->updates_state['sync_loading'] = false;
                    $this->get_updates_difference();
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                    break;
            }
        } finally {
            $this->postpone_updates = false;
            $this->updates_state['sync_loading'] = false;
        }
        $this->handle_pending_updates();
    }

    public function get_updates_state()
    {
        $last = $this->updates_state['sync_loading'];
        $this->updates_state['sync_loading'] = true;

        try {
            $data = $this->method_call('updates.getState', [], ['datacenter' => $this->datacenter->curdc]);
            $this->get_cdn_config($this->datacenter->curdc);
        } finally {
            $this->updates_state['sync_loading'] = $last;
        }

        return $data;
    }

    public function handle_update($update, $options = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        \danog\MadelineProto\Logger::log('Handling an update of type '.$update['_'].'...', \danog\MadelineProto\Logger::VERBOSE);
        $channel_id = false;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                if ($update['message']['_'] === 'messageEmpty') {
                    \danog\MadelineProto\Logger::log('Got message empty, not saving', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    return false;
                }
                $channel_id = $update['message']['to_id']['channel_id'];
                break;
            case 'updateDeleteChannelMessages':
                $channel_id = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
                $channel_id = $update['channel_id'];
                \danog\MadelineProto\Logger::log('Got channel too long update, getting difference...', \danog\MadelineProto\Logger::VERBOSE);
                if (!isset($this->channels_state[$channel_id]) && !isset($update['pts'])) {
                    \danog\MadelineProto\Logger::log('I do not have the channel in the states and the pts is not set.', \danog\MadelineProto\Logger::ERROR);

                    return;
                }
                break;
        }
        if ($channel_id === false) {
            $cur_state = &$this->load_update_state();
        } else {
            $cur_state = &$this->load_channel_state($channel_id, (isset($update['pts']) ? $update['pts'] : 0) - (isset($update['pts_count']) ? $update['pts_count'] : 0));
        }
        /*
                if ($cur_state['sync_loading'] && in_array($update['_'], ['updateNewMessage', 'updateEditMessage', 'updateNewChannelMessage', 'updateEditChannelMessage'])) {
                    \danog\MadelineProto\Logger::log('Sync loading, not handling update', \danog\MadelineProto\Logger::NOTICE);

                    return false;
                }*/
        switch ($update['_']) {
            case 'updateChannelTooLong':
                $this->get_channel_difference($channel_id);

                return false;
            case 'updateNewMessage':
            case 'updateEditMessage':
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                if (isset($update['message']['from_id']) && !$this->peer_isset($update['message']['from_id']) || !$this->peer_isset($update['message']['to_id']) || isset($update['message']['via_bot_id']) && !$this->peer_isset($update['message']['via_bot_id']) || isset($update['message']['entities']) && !$this->entities_peer_isset($update['message']['entities']) || isset($update['message']['fwd_from']) && !$this->fwd_peer_isset($update['message']['fwd_from'])) {
                    \danog\MadelineProto\Logger::log('Not enough data for message update, getting difference...', \danog\MadelineProto\Logger::VERBOSE);
                    if ($channel_id !== false && $this->peer_isset($this->to_supergroup($channel_id))) {
                        $this->get_channel_difference($channel_id);
                    } else {
                        $this->get_updates_difference();
                    }

                    return false;
                }
                break;
            default:
                if ($channel_id !== false && !$this->peer_isset($this->to_supergroup($channel_id))) {
                    \danog\MadelineProto\Logger::log('Skipping update, I do not have the channel id '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                    return false;
                }
                break;
        }
        if (isset($update['pts'])) {
            if ($update['pts'] < $cur_state['pts']) {
                \danog\MadelineProto\Logger::log('Duplicate update, channel id: '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                return false;
            }
            if ($cur_state['pts'] + (isset($update['pts_count']) ? $update['pts_count'] : 0) !== $update['pts']) {
                \danog\MadelineProto\Logger::log('Pts hole. current pts: '.$cur_state['pts'].', pts count: '.(isset($update['pts_count']) ? $update['pts_count'] : 0).', pts: '.$update['pts'].', channel id: '.$channel_id, \danog\MadelineProto\Logger::ERROR);
                if ($channel_id !== false && $this->peer_isset($this->to_supergroup($channel_id))) {
                    $this->get_channel_difference($channel_id);
                } else {
                    $this->get_updates_difference();
                }

                return false;
            }
            if (isset($update['message']['id'], $update['message']['to_id'])) {
                if (!$this->check_msg_id($update['message'])) {
                    \danog\MadelineProto\Logger::log('Duplicate update by message id, channel id: '.$channel_id, \danog\MadelineProto\Logger::ERROR);

                    return false;
                }
            }
            \danog\MadelineProto\Logger::log('Applying pts. current pts: '.$cur_state['pts'].', new pts: '.$update['pts'].', channel id: '.$channel_id, \danog\MadelineProto\Logger::VERBOSE);
            $cur_state['pts'] = $update['pts'];
            if ($channel_id === false && isset($options['date']) && $cur_state['date'] < $options['date']) {
                $cur_state['date'] = $options['date'];
            }
        }
        if ($channel_id === false && isset($options['seq']) || isset($options['seq_start'])) {
            $seq = $options['seq'];
            $seq_start = isset($options['seq_start']) ? $options['seq_start'] : $options['seq'];
            if ($seq_start != $cur_state['seq'] + 1 && $seq_start > $cur_state['seq']) {
                \danog\MadelineProto\Logger::log('Seq hole. seq_start: '.$seq_start.' != cur seq: '.$cur_state['seq'].' + 1', \danog\MadelineProto\Logger::ERROR);
                $this->get_updates_difference();

                return false;
            }
            if ($cur_state['seq'] !== $seq) {
                $cur_state['seq'] = $seq;
                if (isset($options['date']) && $cur_state['date'] < $options['date']) {
                    $cur_state['date'] = $options['date'];
                }
            }
        }
        $this->save_update($update);
    }

    public function handle_multiple_update($updates, $options = [], $channel = false)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if ($channel === false) {
            foreach ($updates as $update) {
                $this->handle_update($update, $options);
            }
        } else {
            foreach ($updates as $update) {
                $this->handle_update($update);
            }
        }
    }

    public function handle_update_messages($messages, $channel = false)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        foreach ($messages as $message) {
            $this->handle_update(['_' => $channel === false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => $channel === false ? $this->load_update_state()['pts'] : $this->load_channel_state($channel)['pts'], 'pts_count' => 0]);
        }
    }

    public function save_update($update)
    {
        array_walk($this->calls, function ($controller, $id) {
            if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                $controller->discard();
            }
        });
        if ($update['_'] === 'updateDcOptions') {
            \danog\MadelineProto\Logger::log('Got new dc options', \danog\MadelineProto\Logger::VERBOSE);
            $this->parse_dc_options($update['dc_options']);

            return;
        }
        if ($update['_'] === 'updatePhoneCall') {
            if (!class_exists('\\danog\\MadelineProto\\VoIP')) {
                \danog\MadelineProto\Logger::log('The php-libtgvoip extension is required to accept and manage calls. See daniil.it/MadelineProto for more info.', \danog\MadelineProto\Logger::WARNING);

                return;
            }
            switch ($update['phone_call']['_']) {
                case 'phoneCallRequested':
                    if (isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }
                    $controller = new \danog\MadelineProto\VoIP(false, $update['phone_call']['admin_id'], ['_' => 'inputPhoneCall', 'id' => $update['phone_call']['id'], 'access_hash' => $update['phone_call']['access_hash']], $this, \danog\MadelineProto\VoIP::CALL_STATE_INCOMING, $update['phone_call']['protocol']);
                    $controller->storage = ['g_a_hash' => $update['phone_call']['g_a_hash']];
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']] = $controller;
                    break;
                case 'phoneCallAccepted':
                    if (!$this->confirm_call($update['phone_call'])) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCall':
                    if (!$this->complete_call($update['phone_call'])) {
                        return;
                    }
                    $update['phone_call'] = $this->calls[$update['phone_call']['id']];
                    break;
                case 'phoneCallDiscarded':
                    if (!isset($this->calls[$update['phone_call']['id']])) {
                        return;
                    }

                    return $this->calls[$update['phone_call']['id']]->discard(['_' => 'phoneCallDiscardReasonHangup'], [], $update['phone_call']['need_debug']);
            }
        }
        if ($update['_'] === 'updateNewEncryptedMessage' && !isset($update['message']['decrypted_message'])) {
            $cur_state = $this->load_update_state();
            if ($cur_state['qts'] === -1) {
                $cur_state['qts'] = $update['qts'];
            }
            if ($update['qts'] < $cur_state['qts']) {
                \danog\MadelineProto\Logger::log('Duplicate update. update qts: '.$update['qts'].' <= current qts '.$cur_state['qts'].', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::ERROR);

                return false;
            }
            if ($update['qts'] > $cur_state['qts'] + 1) {
                \danog\MadelineProto\Logger::log('Qts hole. Fetching updates manually: update qts: '.$update['qts'].' > current qts '.$cur_state['qts'].'+1, chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::ERROR);
                $this->get_updates_difference();

                return false;
            }
            \danog\MadelineProto\Logger::log('Applying qts: '.$update['qts'].' over current qts '.$cur_state['qts'].', chat id: '.$update['message']['chat_id'], \danog\MadelineProto\Logger::VERBOSE);
            $this->method_call('messages.receivedQueue', ['max_qts' => $cur_state['qts'] = $update['qts']], ['datacenter' => $this->datacenter->curdc]);
            $this->handle_encrypted_update($update);

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
                    \danog\MadelineProto\Logger::log('Accepting secret chat '.$update['chat']['id'], \danog\MadelineProto\Logger::NOTICE);
                    $this->accept_secret_chat($update['chat']);
                    break;
                case 'encryptedChatDiscarded':
                    \danog\MadelineProto\Logger::log('Deleting secret chat '.$update['chat']['id'].' because it was revoked by the other user', \danog\MadelineProto\Logger::NOTICE);
                    if (isset($this->secret_chats[$update['chat']['id']])) {
                        unset($this->secret_chats[$update['chat']['id']]);
                    }
                    if (isset($this->temp_requested_secret_chats[$update['chat']['id']])) {
                        unset($this->temp_requested_secret_chats[$update['chat']['id']]);
                    }
                    break;
                case 'encryptedChat':
                    \danog\MadelineProto\Logger::log('Completing creation of secret chat '.$update['chat']['id'], \danog\MadelineProto\Logger::NOTICE);
                    $this->complete_secret_chat($update['chat']);
                    break;
            }
            //\danog\MadelineProto\Logger::log($update, \danog\MadelineProto\Logger::NOTICE);
        }
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'messageEmpty') {
            return;
        }
        if (isset($update['message']['from_id']) && $update['message']['from_id'] === $this->authorization['user']['id']) {
            $update['message']['out'] = true;
        }
        \danog\MadelineProto\Logger::log('Saving an update of type '.$update['_'].'...', \danog\MadelineProto\Logger::VERBOSE);
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict'] && isset($this->settings['pwr']['update_handler'])) {
            $this->pwr_update_handler($update);
        } else {
            $this->get_updates_update_handler($update);
        }
    }

    public function pwr_webhook($update)
    {
        $payload = json_encode($update);
        \danog\MadelineProto\Logger::log($update, $payload, json_last_error());
        if ($payload === '') {
            \danog\MadelineProto\Logger::log('EMPTY UPDATE');

            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->hook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $parse = parse_url($this->hook_url);
        if (isset($parse['scheme']) && $parse['scheme'] == 'https') {
            if (isset($this->pem_path) && file_exists($this->pem_path)) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, $this->pem_path);
            } else {
                //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
        }
        $result = curl_exec($ch);
        curl_close($ch);
        \danog\MadelineProto\Logger::log('Result of webhook query is '.$result, \danog\MadelineProto\Logger::NOTICE);
        $result = json_decode($result, true);
        if (is_array($result) && isset($result['method']) && $result['method'] != '' && is_string($result['method'])) {
            try {
                \danog\MadelineProto\Logger::log('Reverse webhook command returned', $this->method_call($result['method'], $result, ['datacenter' => $this->datacenter->curdc]));
            } catch (\danog\MadelineProto\Exception $e) {
            } catch (\danog\MadelineProto\TL\Exception $e) {
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            } catch (\danog\MadelineProto\SecurityException $e) {
            }
        }
    }
}
