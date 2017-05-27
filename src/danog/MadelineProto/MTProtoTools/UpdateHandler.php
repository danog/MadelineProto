<?php
/*
Copyright 2016-2017 Daniil Gentili
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
    public function pwr_update_handler($update)
    {
        if (isset($update['message']['to_id'])) {
            try {
                $full_chat = $this->get_pwr_chat($update['message']['to_id']);
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
            }
        }
        if (isset($update['message']['from_id'])) {
            try {
                $full_chat = $this->get_pwr_chat($update['message']['from_id']);
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
            }
        }
        if (isset($this->settings['pwr']['update_handler'])) {
            in_array($this->settings['pwr']['update_handler'], [['danog\MadelineProto\API', 'get_updates_update_handler'], 'get_updates_update_handler']) ? $this->get_updates_update_handler($update) : $this->settings['pwr']['update_handler']($update);
        }
    }

    public function get_updates_update_handler($update)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->updates[$this->updates_key++] = $update;
        $this->should_serialize = true;
        //\danog\MadelineProto\Logger::log(['Stored ', $update);
    }

    public function get_updates($params = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $time = microtime(true);
        $this->force_get_updates_difference();
        $default_params = ['offset' => 0, 'limit' => null, 'timeout' => 0];
        foreach ($default_params as $key => $default) {
            if (!isset($params[$key])) {
                $params[$key] = $default;
            }
        }
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
                $this->should_serialize = true;
                unset($this->updates[$key]);
            } elseif ($params['limit'] === null || count($updates) < $params['limit']) {
                $updates[] = ['update_id' => $key, 'update' => $value];
            }
        }

        return $updates;
    }

    public function &get_channel_state($channel, $pts = 0)
    {
        if (!isset($this->channels_state[$channel])) {
            $this->channels_state[$channel] = ['pts' => $pts, 'pending_pts_updates' => [], 'sync_loading' => false];
        }

        return $this->channels_state[$channel];
    }

    public function set_channel_state($channel, $data)
    {
        if (isset($data['pts']) && $data['pts'] !== 0) {
            $this->should_serialize = true;
            $this->get_channel_state($channel)['pts'] = $data['pts'];
        }
    }

    public function get_msg_id($peer)
    {
        $id = $this->get_info($peer)['bot_api_id'];

        return isset($this->msg_ids[$id]) ? $this->msg_ids[$id] : false;
    }

    public function set_msg_id($peer, $msg_id)
    {
        $id = $this->get_info($peer)['bot_api_id'];
        $this->msg_ids[$id] = $msg_id;
        $this->should_serialize = true;
    }

    public function get_channel_difference($channel)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (!$this->get_channel_state($channel)['sync_loading']) {
            $this->get_channel_state($channel)['sync_loading'] = true;
            $this->get_channel_state($channel)['pending_pts_updates'] = [];
        }
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
        }
        try {
            $difference = $this->method_call('updates.getChannelDifference', ['channel' => $input, 'filter' => ['_' => 'channelMessagesFilterEmpty'], 'pts' => $this->get_channel_state($channel)['pts'], 'limit' => 30], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->getMessage() === "You haven't joined this channel/supergroup") {
                return false;
            }
            if ($e->rpc === 'PERSISTENT_TIMESTAMP_INVALID') {
                return false;
            }
            throw $e;
        }
        $this->get_channel_state($channel)['sync_loading'] = false;
        switch ($difference['_']) {
            case 'updates.channelDifferenceEmpty':
                $this->set_channel_state($channel, $difference);
                break;
            case 'updates.channelDifference':
                $this->set_channel_state($channel, $difference);
                $this->handle_update_messages($difference['new_messages'], $channel);
                $this->handle_multiple_update($difference['other_updates'], [], $channel);
                if (!$difference['final']) {
                    unset($difference);
                    unset($input);
                    $this->get_channel_difference($channel);
                }
                break;
            case 'updates.channelDifferenceTooLong':
                \danog\MadelineProto\Logger::log(['Got '.$difference['_']], \danog\MadelineProto\Logger::VERBOSE);
                $this->set_channel_state($channel, $difference);
                $this->handle_update_messages($difference['messages'], $channel);
                unset($difference);
                unset($input);
                $this->get_channel_difference($channel);
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                break;
        }
    }

    public function set_update_state($data)
    {
        if (isset($data['pts']) && $data['pts'] !== 0) {
            $this->should_serialize = true;
            $this->get_update_state()['pts'] = $data['pts'];
        }
        if (isset($data['qts']) && $data['qts'] !== 0) {
            $this->should_serialize = true;
            $this->get_update_state()['qts'] = $data['qts'];
        }
        if (isset($data['seq']) && $data['seq'] !== 0) {
            $this->should_serialize = true;
            $this->get_update_state()['seq'] = $data['seq'];
        }
        if (isset($data['date']) && $data['date'] > $this->get_update_state()['date']) {
            $this->should_serialize = true;
            $this->get_update_state()['date'] = $data['date'];
        }
    }

    public function &get_update_state()
    {
        if (!isset($this->updates_state['qts'])) {
            $this->should_serialize = true;
            $this->updates_state['qts'] = 0;
        }
        if (!$this->got_state) {
            $this->got_state = $this->should_serialize = true;
            $this->get_updates_state();
        }

        return $this->updates_state;
    }

    public function force_get_updates_difference()
    {
        if (!$this->get_update_state()['sync_loading']) {
            $this->get_updates_difference();
        }
    }

    public function get_updates_difference()
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        if (!$this->get_update_state()['sync_loading']) {
            $this->get_update_state()['sync_loading'] = true;
            $this->get_update_state()['pending_pts_updates'] = [];
            $this->get_update_state()['pending_seq_updates'] = [];
        }
        while (!isset($difference)) {
            try {
                $difference = $this->method_call('updates.getDifference', ['pts' => $this->get_update_state()['pts'], 'date' => $this->get_update_state()['date'], 'qts' => $this->get_update_state()['qts']], ['datacenter' => $this->datacenter->curdc]);
            } catch (\danog\MadelineProto\PTSException $e) {
                $this->got_state = false;
            }
        }
        \danog\MadelineProto\Logger::log(['Got '.$difference['_']], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->get_update_state()['sync_loading'] = false;

        switch ($difference['_']) {
            case 'updates.differenceEmpty':
                $this->set_update_state($difference);
                break;
            case 'updates.difference':
                $this->set_update_state($difference['state']);
                foreach ($difference['new_encrypted_messages'] as $encrypted) {
                    $this->handle_encrypted_update(['_' => 'updateNewEncryptedMessage', 'message' => $encrypted]);
                }
                $this->handle_multiple_update($difference['other_updates']);
                $this->handle_update_messages($difference['new_messages']);
                break;
            case 'updates.differenceSlice':
                $this->set_update_state($difference['intermediate_state']);
                $this->handle_multiple_update($difference['other_updates']);
                $this->handle_update_messages($difference['new_messages']);
                unset($difference);
                $this->get_updates_difference();
                break;
            default:
                throw new \danog\MadelineProto\Exception('Unrecognized update difference received: '.var_export($difference, true));
                break;
        }
    }

    public function get_updates_state()
    {
        $this->updates_state['sync_loading'] = false;
        $this->getting_state = true;
        $this->set_update_state($this->method_call('updates.getState', [], ['datacenter' => $this->datacenter->curdc]));
        $this->getting_state = false;
        $this->handle_pending_updates();
    }

    public function handle_update($update, $options = [])
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        \danog\MadelineProto\Logger::log(['Handling an update of type '.$update['_'].'...'], \danog\MadelineProto\Logger::VERBOSE);

        $channel_id = false;
        switch ($update['_']) {
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':
                if ($update['message']['_'] === 'messageEmpty') {
                    \danog\MadelineProto\Logger::log(['Got message empty, not saving'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    return false;
                }
                $channel_id = $update['message']['to_id']['channel_id'];
                break;
            case 'updateDeleteChannelMessages':
                $channel_id = $update['channel_id'];
                break;
            case 'updateChannelTooLong':
                $channel_id = $update['channel_id'];
                \danog\MadelineProto\Logger::log(['Got channel too long update, getting difference...'], \danog\MadelineProto\Logger::VERBOSE);
                if (!isset($this->channels_state[$channel_id]) && !isset($update['pts'])) {
                    \danog\MadelineProto\Logger::log(['I do not have the channel in the states and the pts is not set.'], \danog\MadelineProto\Logger::ERROR);

                    return;
                }
                break;
        }
        if ($channel_id === false) {
            $cur_state = &$this->get_update_state();
        } else {
            $cur_state = &$this->get_channel_state($channel_id, (isset($update['pts']) ? $update['pts'] : 0) - (isset($update['pts_count']) ? $update['pts_count'] : 0));
        }
        if ($cur_state['sync_loading'] && $this->in_array($update['_'], ['updateNewMessage', 'updateEditMessage', 'updateNewChannelMessage', 'updateEditChannelMessage'])) {
            \danog\MadelineProto\Logger::log(['Sync loading, not handling update'], \danog\MadelineProto\Logger::NOTICE);

            return false;
        }

        switch ($update['_']) {
            case 'updateChannelTooLong':
                $this->get_channel_difference($channel_id);

                return false;
            case 'updateNewMessage':
            case 'updateEditMessage':
            case 'updateNewChannelMessage':
            case 'updateEditChannelMessage':

                if ((isset($update['message']['from_id']) && !$this->peer_isset($update['message']['from_id'])) ||
                    !$this->peer_isset($update['message']['to_id']) ||
                    (isset($update['message']['via_bot_id']) && !$this->peer_isset($update['message']['via_bot_id'])) ||
                    (isset($update['message']['entities']) && !$this->entities_peer_isset($update['message']['entities'])) ||
                    (isset($update['message']['fwd_from']) && !$this->fwd_peer_isset($update['message']['fwd_from']))) {
                    \danog\MadelineProto\Logger::log(['Not enough data for message update, getting difference...'], \danog\MadelineProto\Logger::VERBOSE);

                    if ($channel_id !== false && $this->peer_isset($this->to_supergroup($channel_id))) {
                        $this->get_channel_difference($channel_id);
                    } else {
                        $this->force_get_updates_difference();
                    }

                    return false;
                }
                break;
            default:
                if ($channel_id !== false && !$this->peer_isset('channel#'.$channel_id)) {
                    \danog\MadelineProto\Logger::log(['Skipping update, I do not have the channel id '.$channel_id], \danog\MadelineProto\Logger::ERROR);

                    return false;
                }
                break;
        }
        $pop_pts = false;
        $pop_seq = false;

        if (isset($update['pts'])) {
            $new_pts = $cur_state['pts'] + (isset($update['pts_count']) ? $update['pts_count'] : 0);
            if ($update['pts'] < $new_pts) {
                \danog\MadelineProto\Logger::log(['Duplicate update. current pts: '.$cur_state['pts'].' + pts count: '.(isset($update['pts_count']) ? $update['pts_count'] : 0).' = new pts: '.$new_pts.'. update pts: '.$update['pts'].' < new pts '.$new_pts.', channel id: '.$channel_id], \danog\MadelineProto\Logger::ERROR);

                return false;
            }
            if ($update['pts'] > $new_pts) {
                \danog\MadelineProto\Logger::log(['Pts hole. current pts: '.$cur_state['pts'].', pts count: '.(isset($update['pts_count']) ? $update['pts_count'] : 0).', new pts: '.$new_pts.' < update pts: '.$update['pts'].', channel id: '.$channel_id], \danog\MadelineProto\Logger::ERROR);

                $cur_state['pending_pts_updates'][] = $update;

                if ($channel_id !== false && $this->peer_isset($this->to_supergroup($channel_id))) {
                    $this->get_channel_difference($channel_id);
                } else {
                    $this->get_updates_difference();
                }

                return false;
            }
            if ($update['pts'] > $cur_state['pts']) {
                \danog\MadelineProto\Logger::log(['Applying pts. current pts: '.$cur_state['pts'].' + pts count: '.(isset($update['pts_count']) ? $update['pts_count'] : 0).' = new pts: '.$new_pts.', channel id: '.$channel_id], \danog\MadelineProto\Logger::VERBOSE);
                $cur_state['pts'] = $update['pts'];
                $this->should_serialize = true;
                $pop_pts = true;
            }
            if ($channel_id !== false && isset($options['date']) && $this->get_update_state()['date'] < $options['date']) {
                $this->get_update_state()['date'] = $options['date'];
                $this->should_serialize = true;
            }
        } elseif ($channel_id === false && isset($options['seq']) && $options['seq'] > 0) {
            $seq = $options['seq'];
            $seq_start = isset($options['seq_start']) ? $options['seq_start'] : $options['seq'];
            if ($seq_start != $cur_state['seq'] + 1 && $seq_start > $cur_state['seq']) {
                \danog\MadelineProto\Logger::log(['Seq hole. seq_start: '.$seq_start.' != cur seq: '.$cur_state['seq'].' + 1'], \danog\MadelineProto\Logger::ERROR);

                if (!isset($cur_state['pending_seq_updates'][$seq_start])) {
                    $cur_state['pending_seq_updates'][$seq_start] = ['seq' => $seq, 'date' => $options['date'], 'updates' => []];
                }
                $cur_state['pending_seq_updates'][$seq_start]['updates'][] = $update;
                $this->get_updates_difference();

                return false;
            }

            if ($cur_state['seq'] != $seq) {
                $this->should_serialize = true;
                $cur_state['seq'] = $seq;
                if (isset($options['date']) && $cur_state['date'] < $options['date']) {
                    $cur_state['date'] = $options['date'];
                }
                $pop_seq = true;
            }
        }
        $this->save_update($update);

        if ($pop_pts) {
            $this->pop_pending_pts_update($channel_id);
        } elseif ($pop_seq) {
            $this->pop_pending_seq_update();
        }
    }

    public function pop_pending_seq_update()
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->should_serialize = true;
        $next_seq = $this->get_update_state()['seq'] + 1;
        if (empty($this->get_update_state()['pending_seq_updates'][$next_seq]['updates'])) {
            return false;
        }
        foreach ($this->get_update_state()['pending_seq_updates'][$next_seq]['updates'] as $update) {
            $this->save_update($update);
        }
        $this->get_update_state()['seq'] = $this->get_update_state()['pending_seq_updates'][$next_seq]['seq'];
        if (isset($this->get_update_state()['pending_seq_updates'][$next_seq]['date']) && $this->get_update_state()['date'] < $this->get_update_state()['pending_seq_updates'][$next_seq]['date']) {
            $this->get_update_state()['date'] = $this->get_update_state()['pending_seq_updates'][$next_seq]['date'];
        }
        unset($this->get_update_state()['pending_seq_updates'][$next_seq]);
        $this->pop_pending_seq_update();

        return true;
    }

    public function pop_pending_pts_update($channel_id)
    {
        if (!$this->settings['updates']['handle_updates']) {
            return;
        }
        $this->should_serialize = true;
        if ($channel_id === false) {
            $cur_state = &$this->get_update_state();
        } else {
            $cur_state = &$this->get_channel_state($channel_id);
        }
        if (empty($cur_state['pending_pts_updates'])) {
            return false;
        }
        $pending_updates = (array) $cur_state['pending_pts_updates'];
        sort($pending_updates);
        $cur_pts = $cur_state['pts'];
        $good_pts = false;
        $good_index = false;
        foreach ($pending_updates as $i => $update) {
            $cur_pts += $update['pts_count'];
            if ($cur_pts >= $update['pts']) {
                $good_pts = $update['pts'];
                $good_index = $i;
            }
        }
        if (!$good_pts) {
            return false;
        }
        $cur_state['pts'] = $good_pts;
        for ($i = 0; $i <= $good_index; $i++) {
            $this->save_update($pending_updates[$i]);
        }
        array_splice($cur_state['pending_pts_updates'], 0, $good_index + 1);
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
            $this->handle_update(['_' => $channel === false ? 'updateNewMessage' : 'updateNewChannelMessage', 'message' => $message, 'pts' => $channel === false ? $this->get_update_state()['pts'] : $this->get_channel_state($channel)['pts'], 'pts_count' => 0]);
        }
    }

    public function save_update($update)
    {
        if ($update['_'] === 'updateDcOptions') {
            \danog\MadelineProto\Logger::log(['Got new dc options'], \danog\MadelineProto\Logger::VERBOSE);
            $this->parse_dc_options($update['dc_options']);

            return;
        }
        if ($update['_'] === 'updatePhoneCall') {
            switch ($update['phone_call']['_']) {
                case 'phoneCallRequested':
                return $this->accept_call($update['phone_call']);
                case 'phoneCallAccepted':
                $this->confirm_call($update['phone_call']);

                return;
                case 'phoneCall':
                $this->complete_call($update['phone_call']);
                break;
                case 'phoneCallDiscarded':
                \danog\MadelineProto\Logger::log(['Revoking call '.$update['phone_call']['id']], \danog\MadelineProto\Logger::NOTICE);
                if (isset($this->secret_chats[$update['phone_call']['id']])) {
                    unset($this->secret_chats[$update['phone_call']['id']]);
                }
                break;
            }
        }
        if ($update['_'] === 'updateNewEncryptedMessage' && !isset($update['message']['decrypted_message'])) {
            $cur_state = $this->get_update_state();
            if ($cur_state['qts'] === -1) {
                $cur_state['qts'] = $update['qts'];
                $this->should_serialize = true;
            }
            if ($update['qts'] < $cur_state['qts']) {
                \danog\MadelineProto\Logger::log(['Duplicate update. update qts: '.$update['qts'].' <= current qts '.$cur_state['qts'].', chat id: '.$update['message']['chat_id']], \danog\MadelineProto\Logger::ERROR);

                return false;
            }
            if ($update['qts'] > $cur_state['qts'] + 1) {
                \danog\MadelineProto\Logger::log(['Qts hole. update qts: '.$update['qts'].' > current qts '.$cur_state['qts'].'+1, chat id: '.$update['message']['chat_id']], \danog\MadelineProto\Logger::ERROR);
                $this->get_updates_difference();

                return false;
            }
            \danog\MadelineProto\Logger::log(['Applying qts: '.$update['qts'].' over current qts '.$cur_state['qts'].', chat id: '.$update['message']['chat_id']], \danog\MadelineProto\Logger::VERBOSE);
            $cur_state['qts'] = $update['qts'];
            $this->should_serialize = true;
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
                if ($this->settings['secret_chats']['accept_chats'] === false || ($this->is_array($this->settings['secret_chats']['accept_chats']) && !$this->in_array($update['chat']['admin_id'], $this->settings['secret_chats']['accept_chats']))) {
                    return;
                }
                \danog\MadelineProto\Logger::log(['Accepting secret chat '.$update['chat']['id']], \danog\MadelineProto\Logger::NOTICE);

                return $this->accept_secret_chat($update['chat']);
                case 'encryptedChatDiscarded':
                \danog\MadelineProto\Logger::log(['Revoking secret chat '.$update['chat']['id']], \danog\MadelineProto\Logger::NOTICE);
                if (isset($this->secret_chats[$update['chat']['id']])) {
                    unset($this->secret_chats[$update['chat']['id']]);
                }
                if (isset($this->temp_requested_secret_chats[$update['chat']['id']])) {
                    unset($this->temp_requested_secret_chats[$update['chat']['id']]);
                }

                break;
                case 'encryptedChat':
                \danog\MadelineProto\Logger::log(['Completing creation of secret chat '.$update['chat']['id']], \danog\MadelineProto\Logger::NOTICE);

                $this->complete_secret_chat($update['chat']);

                return;
            }
            //\danog\MadelineProto\Logger::log([$update], \danog\MadelineProto\Logger::NOTICE);
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
        \danog\MadelineProto\Logger::log(['Saving an update of type '.$update['_'].'...'], \danog\MadelineProto\Logger::VERBOSE);
        if (isset($this->settings['pwr']['strict']) && $this->settings['pwr']['strict']) {
            $this->pwr_update_handler($update);
        } else {
            $this->settings['updates']['callback'] === 'get_updates_update_handler' ? $this->get_updates_update_handler($update) : $this->settings['updates']['callback']($update);
        }
    }

    public function pwr_webhook($update)
    {
        $payload = json_encode($update);
        \danog\MadelineProto\Logger::log([$update, $payload, json_last_error()]);
        if ($payload === '') {
            \danog\MadelineProto\Logger::log(['EMPTY UPDATE']);

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
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
        }
        $result = curl_exec($ch);

        curl_close($ch);
        \danog\MadelineProto\Logger::log(['Result of webhook query is '.$result], \danog\MadelineProto\Logger::NOTICE);
        $result = json_decode($result, true);
        if ($this->is_array($result) && isset($result['method']) && $result['method'] != '' && is_string($result['method'])) {
            try {
                \danog\MadelineProto\Logger::log(['Reverse webhook command returned', $this->method_call($result['method'], $result, ['datacenter' => $this->datacenter->curdc])]);
            } catch (\danog\MadelineProto\Exception $e) {
            } catch (\danog\MadelineProto\TL\Exception $e) {
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            } catch (\danog\MadelineProto\SecurityException $e) {
            }
        }
    }
}
