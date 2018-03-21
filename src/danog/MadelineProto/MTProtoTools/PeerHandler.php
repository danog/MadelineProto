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
 * Manages peers.
 */
trait PeerHandler
{
    public function to_supergroup($id)
    {
        return -($id + pow(10, (int) floor(log($id, 10) + 3)));
    }

    public function is_supergroup($id)
    {
        $log = log(-$id, 10);

        return ($log - intval($log)) * 1000 < 10;
    }

    public function handle_pending_pwrchat()
    {
        if ($this->postpone_pwrchat || empty($this->pending_pwrchat)) {
            return false;
        }
        $this->postpone_pwrchat = true;

        try {
            \danog\MadelineProto\Logger::log('Handling pending pwrchat queries...', \danog\MadelineProto\Logger::VERBOSE);
            foreach ($this->pending_pwrchat as $query => $params) {
                unset($this->pending_pwrchat[$query]);

                try {
                    $this->get_pwr_chat($query, ...$params);
                } catch (\danog\MadelineProto\Exception $e) {
                    \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                }
            }
        } finally {
            $this->postpone_pwrchat = false;
        }
    }

    public function add_users($users)
    {
        foreach ($users as $key => $user) {
            if (!isset($user['access_hash'])) {
                if (isset($user['username']) && !isset($this->chats[$user['id']])) {
                    if ($this->postpone_pwrchat) {
                        $this->pending_pwrchat[$user['username']] = [false, true];
                    } else {
                        try {
                            $this->get_pwr_chat($user['username'], false, true);
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                        }
                    }
                }
                continue;
            }
            switch ($user['_']) {
                case 'user':
                    if (!isset($this->chats[$user['id']]) || $this->chats[$user['id']] !== $user) {
                        $this->chats[$user['id']] = $user;

                        if ($this->postpone_pwrchat) {
                            $this->pending_pwrchat[$user['id']] = [false, true];
                        } else {
                            try {
                                $this->get_pwr_chat($user['id'], false, true);
                            } catch (\danog\MadelineProto\Exception $e) {
                                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                            }
                        }
                    }
                case 'userEmpty':
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid user provided at key '.$key.': '.var_export($user, true));
                    break;
            }
        }
    }

    public function add_chats($chats)
    {
        foreach ($chats as $key => $chat) {
            switch ($chat['_']) {
                case 'chat':
                case 'chatEmpty':
                case 'chatForbidden':
                    if (!isset($this->chats[-$chat['id']]) || $this->chats[-$chat['id']] !== $chat) {
                        $this->chats[-$chat['id']] = $chat;

                        if ($this->postpone_pwrchat) {
                            $this->pending_pwrchat[-$chat['id']] = [$this->settings['peer']['full_fetch'], true];
                        } else {
                            try {
                                $this->get_pwr_chat(-$chat['id'], $this->settings['peer']['full_fetch'], true);
                            } catch (\danog\MadelineProto\Exception $e) {
                                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                                \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                            }
                        }
                    }
                case 'channelEmpty':
                    break;
                case 'channel':
                case 'channelForbidden':
                    $bot_api_id = $this->to_supergroup($chat['id']);
                    if (!isset($chat['access_hash'])) {
                        if (isset($chat['username']) && !isset($this->chats[$bot_api_id])) {
                            if ($this->postpone_pwrchat) {
                                $this->pending_pwrchat[$chat['username']] = [$this->settings['peer']['full_fetch'], true];
                            } else {
                                try {
                                    $this->get_pwr_chat($chat['username'], $this->settings['peer']['full_fetch'], true);
                                } catch (\danog\MadelineProto\Exception $e) {
                                    \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                                } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                                }
                            }
                        }
                        continue;
                    }
                    if (!isset($this->chats[$bot_api_id]) || $this->chats[$bot_api_id] !== $chat) {
                        $this->chats[$bot_api_id] = $chat;

                        try {
                            if ($this->settings['peer']['full_fetch'] && (!isset($this->full_chats[$bot_api_id]) || $this->full_chats[$bot_api_id]['full']['participants_count'] !== $this->get_full_info($bot_api_id)['full']['participants_count'])) {
                                if ($this->postpone_pwrchat) {
                                    $this->pending_pwrchat[$this->to_supergroup($chat['id'])] = [$this->settings['peer']['full_fetch'], true];
                                } else {
                                    $this->get_pwr_chat($this->to_supergroup($chat['id']), $this->settings['peer']['full_fetch'], true);
                                }
                            }
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                        }
                    }
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid chat provided at key '.$key.': '.var_export($chat, true));
                    break;
            }
        }
    }

    public function peer_isset($id)
    {
        try {
            return isset($this->chats[$this->get_info($id)['bot_api_id']]);
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'CHAT_FORBIDDEN') {
                return true;
            }
            if ($e->rpc === 'CHANNEL_PRIVATE') {
                return true;
            }

            return false;
        }
    }

    public function entities_peer_isset($entities)
    {
        try {
            foreach ($entities as $entity) {
                if ($entity['_'] === 'messageEntityMentionName' || $entity['_'] === 'inputMessageEntityMentionName') {
                    if (!$this->peer_isset($entity['user_id'])) {
                        return false;
                    }
                }
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function fwd_peer_isset($fwd)
    {
        try {
            if (isset($fwd['user_id']) && !$this->peer_isset($fwd['user_id'])) {
                return false;
            }
            if (isset($fwd['channel_id']) && !$this->peer_isset($this->to_supergroup($fwd['channel_id']))) {
                return false;
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function get_info($id, $recursive = true)
    {
        if (is_array($id)) {
            switch ($id['_']) {
                case 'inputUserSelf':
                case 'inputPeerSelf':
                    $id = $this->authorization['user']['id'];
                    break;
                case 'user':
                    $id = $id['id'];
                    break;
                case 'userFull':
                    $id = $id['user']['id'];
                    break;
                case 'inputPeerUser':
                case 'inputUser':
                case 'peerUser':
                    $id = $id['user_id'];
                    break;
                case 'chat':
                case 'chatFull':
                    $id = -$id['id'];
                    break;
                case 'inputPeerChat':
                case 'peerChat':
                    $id = -$id['chat_id'];
                    break;
                case 'channel':
                case 'channelFull':
                    $id = $this->to_supergroup($id['id']);
                    break;
                case 'inputPeerChannel':
                case 'inputChannel':
                case 'peerChannel':
                    $id = $this->to_supergroup($id['channel_id']);
                    break;
                case 'message':
                    if (!isset($id['from_id']) || $id['to_id']['_'] !== 'peerUser' || $id['to_id']['user_id'] !== $this->authorization['user']['id']) {
                        return $this->get_info($id['to_id']);
                    }
                    $id = $id['from_id'];
                    break;
                case 'encryptedMessage':
                case 'encryptedMessageService':
                    $id = $id['chat_id'];
                    if (!isset($this->secret_chats[$id])) {
                        throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['sec_peer_not_in_db']);
                    }
                    return $this->secret_chats[$id];
                case 'updateNewMessage':
                case 'updateNewChannelMessage':
                case 'updateNewEncryptedMessage':
                    return $this->get_info($id['message']);
                case 'chatForbidden':
                case 'channelForbidden':
                    throw new \danog\MadelineProto\RPCErrorException('CHAT_FORBIDDEN');
                default:
                    throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($id, true));
                    break;
            }
        }
        if (is_string($id) && strpos($id, '#') !== false) {
            if (preg_match('/^channel#(\d*)/', $id, $matches)) {
                $id = $this->to_supergroup($matches[1]);
            }
            if (preg_match('/^chat#(\d*)/', $id, $matches)) {
                $id = '-'.$matches[1];
            }
            if (preg_match('/^user#(\d*)/', $id, $matches)) {
                $id = $matches[1];
            }
        }
        if (is_numeric($id)) {
            if (is_string($id)) {
                $id = \danog\MadelineProto\Logger::$bigint ? (float) $id : (int) $id;
            }
            if (!isset($this->chats[$id]) && $id < 0 && !$this->is_supergroup($id)) {
                $this->method_call('messages.getFullChat', ['chat_id' => -$id], ['datacenter' => $this->datacenter->curdc]);
            }
            if (isset($this->chats[$id])) {
                try {
                    return $this->gen_all($this->chats[$id]);
                } catch (\danog\MadelineProto\Exception $e) {
                    if ($e->getMessage() === 'This peer is not present in the internal peer database') {
                        unset($this->chats[$id]);
                    } else {
                        throw $e;
                    }
                }
            }
            if (!isset($this->settings['pwr']['requests']) || $this->settings['pwr']['requests'] === true && $recursive) {
                $dbres = json_decode(@file_get_contents('https://id.pwrtelegram.xyz/db/getusername?id='.$id, false, stream_context_create(['http' => ['timeout' => 2]])), true);
                if (isset($dbres['ok']) && $dbres['ok']) {
                    $this->resolve_username('@'.$dbres['result']);

                    return $this->get_info($id, false);
                }
            }

            throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
        }
        if (preg_match('@(?:t|telegram)\.(?:me|dog)/(joinchat/)?([a-z0-9_-]*)@i', $id, $matches)) {
            if ($matches[1] === '') {
                $id = $matches[2];
            } else {
                $invite = $this->method_call('messages.checkChatInvite', ['hash' => $matches[2]], ['datacenter' => $this->datacenter->curdc]);
                if (isset($invite['chat'])) {
                    return $this->get_info($invite['chat']);
                } else {
                    throw new \danog\MadelineProto\Exception('You have not joined this chat');
                }
            }
        }
        $id = strtolower(str_replace('@', '', $id));
        if ($id === 'me') {
            return $this->get_info($this->authorization['user']['id']);
        }
        foreach ($this->chats as $chat) {
            if (isset($chat['username']) && strtolower($chat['username']) === $id) {
                return $this->gen_all($chat);
            }
        }
        if ($recursive) {
            $this->resolve_username($id);

            return $this->get_info($id, false);
        }

        throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
    }

    public function gen_all($constructor)
    {
        $res = [$this->constructors->find_by_predicate($constructor['_'])['type'] => $constructor];
        switch ($constructor['_']) {
            case 'user':
                if ($constructor['self']) {
                    $res['InputPeer'] = ['_' => 'inputPeerSelf'];
                    $res['InputUser'] = ['_' => 'inputUserSelf'];
                } elseif (isset($constructor['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                    $res['InputUser'] = ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                } else {
                    throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
                }
                $res['Peer'] = ['_' => 'peerUser', 'user_id' => $constructor['id']];
                $res['user_id'] = $constructor['id'];
                $res['bot_api_id'] = $constructor['id'];
                $res['type'] = $constructor['bot'] ? 'bot' : 'user';
                break;
            case 'chat':
            case 'chatForbidden':
                $res['InputPeer'] = ['_' => 'inputPeerChat', 'chat_id' => $constructor['id']];
                $res['Peer'] = ['_' => 'peerChat', 'chat_id' => $constructor['id']];
                $res['chat_id'] = $constructor['id'];
                $res['bot_api_id'] = -$constructor['id'];
                $res['type'] = 'chat';
                break;
            case 'channel':
                if (!isset($constructor['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
                }
                $res['InputPeer'] = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $res['InputChannel'] = ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $res['Peer'] = ['_' => 'peerChannel', 'channel_id' => $constructor['id']];
                $res['channel_id'] = $constructor['id'];
                $res['bot_api_id'] = $this->to_supergroup($constructor['id']);
                $res['type'] = $constructor['megagroup'] ? 'supergroup' : 'channel';
                break;
            case 'channelForbidden':
                throw new \danog\MadelineProto\RPCErrorException('CHAT_FORBIDDEN');
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($constructor, true));
                break;
        }

        return $res;
    }

    public function full_chat_last_updated($id)
    {
        return isset($this->full_chats[$id]['last_update']) ? $this->full_chats[$id]['last_update'] : 0;
    }

    public function get_full_info($id)
    {
        $partial = $this->get_info($id);
        if (time() - $this->full_chat_last_updated($partial['bot_api_id']) < (isset($this->settings['peer']['full_info_cache_time']) ? $this->settings['peer']['full_info_cache_time'] : 0)) {
            return array_merge($partial, $this->full_chats[$partial['bot_api_id']]);
        }
        switch ($partial['type']) {
            case 'user':
            case 'bot':
                $full = $this->method_call('users.getFullUser', ['id' => $partial['InputUser']], ['datacenter' => $this->datacenter->curdc]);
                break;
            case 'chat':
                $full = $this->method_call('messages.getFullChat', $partial, ['datacenter' => $this->datacenter->curdc])['full_chat'];
                break;
            case 'channel':
            case 'supergroup':
                $full = $this->method_call('channels.getFullChannel', ['channel' => $partial['InputChannel']], ['datacenter' => $this->datacenter->curdc])['full_chat'];
                break;
        }
        $res = [];
        $res['full'] = $full;
        $res['last_update'] = time();
        $this->full_chats[$partial['bot_api_id']] = $res;

        return array_merge($partial, $res);
    }

    public function get_pwr_chat($id, $fullfetch = true, $send = true)
    {
        $full = $fullfetch ? $this->get_full_info($id) : $this->get_info($id);
        $res = ['id' => $full['bot_api_id'], 'type' => $full['type']];
        switch ($full['type']) {
            case 'user':
            case 'bot':
                foreach (['first_name', 'last_name', 'username', 'verified', 'restricted', 'restriction_reason', 'status', 'bot_inline_placeholder', 'access_hash', 'phone', 'lang_code', 'bot_nochats'] as $key) {
                    if (isset($full['User'][$key])) {
                        $res[$key] = $full['User'][$key];
                    }
                }
                if (isset($full['full']['about'])) {
                    $res['about'] = $full['full']['about'];
                }
                if (isset($full['full']['bot_info'])) {
                    $res['bot_info'] = $full['full']['bot_info'];
                }
                if (isset($full['full']['phone_calls_available'])) {
                    $res['phone_calls_available'] = $full['full']['phone_calls_available'];
                }
                if (isset($full['full']['phone_calls_private'])) {
                    $res['phone_calls_private'] = $full['full']['phone_calls_private'];
                }
                if (isset($full['full']['common_chats_count'])) {
                    $res['common_chats_count'] = $full['full']['common_chats_count'];
                }
                if (isset($full['full']['profile_photo']['sizes'])) {
                    $res['photo'] = $this->photosize_to_botapi(end($full['full']['profile_photo']['sizes']), []);
                }
                /*$bio = '';
                  if ($full['type'] === 'user' && isset($res['username']) && !isset($res['about']) && $fullfetch) {
                      if (preg_match('/meta property="og:description" content=".+/', file_get_contents('https://telegram.me/'.$res['username']), $biores)) {
                          $bio = html_entity_decode(preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
                              return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
                          }, str_replace(['meta property="og:description" content="', '">'], '', $biores[0])));
                      }
                      if ($bio != '' && $bio != 'You can contact @'.$res['username'].' right away.') {
                          $res['about'] = $bio;
                      }
                  }*/
                break;
            case 'chat':
                foreach (['title', 'participants_count', 'admin', 'admins_enabled'] as $key) {
                    if (isset($full['Chat'][$key])) {
                        $res[$key] = $full['Chat'][$key];
                    }
                }
                if (isset($res['admins_enabled'])) {
                    $res['all_members_are_administrators'] = $res['admins_enabled'];
                }
                if (isset($full['full']['chat_photo']['sizes'])) {
                    $res['photo'] = $this->photosize_to_botapi(end($full['full']['chat_photo']['sizes']), []);
                }
                if (isset($full['full']['exported_invite']['link'])) {
                    $res['invite'] = $full['full']['exported_invite']['link'];
                }
                if (isset($full['full']['participants']['participants'])) {
                    $res['participants'] = $full['full']['participants']['participants'];
                }
                break;
            case 'channel':
            case 'supergroup':
                foreach (['title', 'democracy', 'restricted', 'restriction_reason', 'access_hash', 'username', 'signatures'] as $key) {
                    if (isset($full['Chat'][$key])) {
                        $res[$key] = $full['Chat'][$key];
                    }
                }
                foreach (['can_set_stickers', 'stickerset', 'can_view_participants', 'can_set_username', 'participants_count', 'admins_count', 'kicked_count', 'banned_count', 'migrated_from_chat_id', 'migrated_from_max_id', 'pinned_msg_id', 'about', 'hidden_prehistory', 'available_min_id'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }
                if (isset($full['full']['chat_photo']['sizes'])) {
                    $res['photo'] = $this->photosize_to_botapi(end($full['full']['chat_photo']['sizes']), []);
                }
                if (isset($full['full']['exported_invite']['link'])) {
                    $res['invite'] = $full['full']['exported_invite']['link'];
                }
                if (isset($full['full']['participants']['participants'])) {
                    $res['participants'] = $full['full']['participants']['participants'];
                }
                break;
        }
        if (isset($res['participants']) && $fullfetch) {
            foreach ($res['participants'] as $key => $participant) {
                $newres = [];
                $newres['user'] = $this->get_pwr_chat($participant['user_id'], false, true);
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = $this->get_pwr_chat($participant['inviter_id'], false, true);
                }
                if (isset($participant['promoted_by'])) {
                    $newres['promoted_by'] = $this->get_pwr_chat($participant['promoted_by'], false, true);
                }
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = $this->get_pwr_chat($participant['kicked_by'], false, true);
                }
                if (isset($participant['date'])) {
                    $newres['date'] = $participant['date'];
                }
                if (isset($participant['admin_rights'])) {
                    $newres['admin_rights'] = $participant['admin_rights'];
                }
                if (isset($participant['banned_rights'])) {
                    $newres['banned_rights'] = $participant['banned_rights'];
                }
                if (isset($participant['can_edit'])) {
                    $newres['can_edit'] = $participant['can_edit'];
                }
                if (isset($participant['left'])) {
                    $newres['left'] = $participant['left'];
                }
                switch ($participant['_']) {
                    case 'chatParticipant':
                        $newres['role'] = 'user';
                        break;
                    case 'chatParticipantAdmin':
                        $newres['role'] = 'admin';
                        break;
                    case 'chatParticipantCreator':
                        $newres['role'] = 'creator';
                        break;
                }
                $res['participants'][$key] = $newres;
            }
        }
        if (!isset($res['participants']) && isset($res['can_view_participants']) && $res['can_view_participants'] && $fullfetch) {
            $total_count = (isset($res['participants_count']) ? $res['participants_count'] : 0) + (isset($res['admins_count']) ? $res['admins_count'] : 0) + (isset($res['kicked_count']) ? $res['kicked_count'] : 0) + (isset($res['banned_count']) ? $res['banned_count'] : 0);
            $res['participants'] = [];
            $limit = 200;
            $filters = ['channelParticipantsAdmins', 'channelParticipantsBots'];
            foreach ($filters as $filter) {
                $this->fetch_participants($full['InputChannel'], $filter, '', $total_count, $res);
            }
            $q = '';

            $filters = ['channelParticipantsSearch', 'channelParticipantsKicked', 'channelParticipantsBanned'];
            foreach ($filters as $filter) {
                $this->recurse_alphabet_search_participants($full['InputChannel'], $filter, $q, $total_count, $res);
            }
            \danog\MadelineProto\Logger::log('Fetched '.count($res['participants'])." out of $total_count");
            $res['participants'] = array_values($res['participants']);
        }
        if (!$fullfetch) {
            unset($res['participants']);
        }
        if ($fullfetch || $send) {
            $this->store_db($res);
        }

        return $res;
    }

    public function recurse_alphabet_search_participants($channel, $filter, $q, $total_count, &$res)
    {
        if (!$this->fetch_participants($channel, $filter, $q, $total_count, $res)) {
            return false;
        }

        for ($x = 'a'; $x !== 'aa' && $total_count > count($res['participants']); $x++) {
            $this->recurse_alphabet_search_participants($channel, $filter, $q.$x, $total_count, $res);
        }
    }

    public function fetch_participants($channel, $filter, $q, $total_count, &$res)
    {
        $offset = 0;
        $limit = 200;
        $has_more = false;
        $cached = false;

        do {
            try {
                $gres = $this->method_call('channels.getParticipants', ['channel' => $channel, 'filter' => ['_' => $filter, 'q' => $q], 'offset' => $offset, 'limit' => $limit, 'hash' => $hash = $this->get_participants_hash($channel, $filter, $q, $offset, $limit)], ['datacenter' => $this->datacenter->curdc, 'heavy' => true]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->rpc === 'CHAT_ADMIN_REQUIRED') {
                    return $has_more;
                } else {
                    throw $e;
                }
            }

            if ($cached = $gres['_'] === 'channels.channelParticipantsNotModified') {
                $gres = $this->fetch_participants_cache($channel, $filter, $q, $offset, $limit);
            } else {
                $this->store_participants_cache($gres, $channel, $filter, $q, $offset, $limit);
            }

            $has_more = $gres['count'] === 10000;

            foreach ($gres['participants'] as $participant) {
                $newres = [];
                $newres['user'] = $this->get_pwr_chat($participant['user_id'], false, true);
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = $this->get_pwr_chat($participant['inviter_id'], false, true);
                }
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = $this->get_pwr_chat($participant['kicked_by'], false, true);
                }
                if (isset($participant['promoted_by'])) {
                    $newres['promoted_by'] = $this->get_pwr_chat($participant['promoted_by'], false, true);
                }
                if (isset($participant['date'])) {
                    $newres['date'] = $participant['date'];
                }
                switch ($participant['_']) {
                            case 'channelParticipantSelf':
                                $newres['role'] = 'user';
                                if (isset($newres['admin_rights'])) {
                                    $newres['admin_rights'] = $full['Chat']['admin_rights'];
                                }
                                if (isset($newres['banned_rights'])) {
                                    $newres['banned_rights'] = $full['Chat']['banned_rights'];
                                }
                                break;
                            case 'channelParticipant':
                                $newres['role'] = 'user';
                                break;
                            case 'channelParticipantCreator':
                                $newres['role'] = 'creator';
                                break;
                            case 'channelParticipantAdmin':
                                $newres['role'] = 'admin';
                                break;
                            case 'channelParticipantBanned':
                                $newres['role'] = 'banned';
                                break;
                        }
                $res['participants'][$participant['user_id']] = $newres;
            }
            \danog\MadelineProto\Logger::log("Fetched channel participants with filter $filter, query $q, offset $offset, limit $limit, hash $hash: ".($cached ? 'cached' : 'not cached').', '.count($gres['participants']).' participants out of '.$gres['count'].', in total fetched '.count($res['participants']).' out of '.$total_count);
            $offset += count($gres['participants']);
        } while (count($gres['participants']));

        return $has_more;
    }

    public function fetch_participants_cache($channel, $filter, $q, $offset, $limit)
    {
        return $this->channel_participants[$channel['channel_id']][$filter][$q][$offset][$limit];
    }

    public function store_participants_cache($gres, $channel, $filter, $q, $offset, $limit)
    {
        return;
        unset($gres['users']);
        $ids = [];
        foreach ($gres['participants'] as $participant) {
            $ids[] = $participant['user_id'];
        }
        $gres['hash'] = $this->gen_vector_hash($ids);
        $this->channel_participants[$channel['channel_id']][$filter][$q][$offset][$limit] = $gres;
    }

    public function get_participants_hash($channel, $filter, $q, $offset, $limit)
    {
        return isset($this->channel_participants[$channel['channel_id']][$filter][$q][$offset][$limit]) ? $this->channel_participants[$channel['channel_id']][$filter][$q][$offset][$limit]['hash'] : 0;
    }

    public function store_db($res, $force = false)
    {
        $settings = isset($this->settings['connection_settings'][$this->datacenter->curdc]) ? $this->settings['connection_settings'][$this->datacenter->curdc] : $this->settings['connection_settings']['all'];
        if (!isset($this->settings['pwr']) || $this->settings['pwr']['pwr'] === false || $settings['test_mode']) {
            /*
            try {
                if (isset($res['username'])) {
                    shell_exec('curl '.escapeshellarg('https://api.pwrtelegram.xyz/getchat?chat_id=@'.$res['username']).' -s -o /dev/null >/dev/null 2>/dev/null & ');
                }
            } catch (\danog\MadelineProto\Exception $e) {
                \danog\MadelineProto\Logger::log([$e->getMessage());
            }
            */
            return;
        }
        if (!empty($res)) {
            if (isset($res['participants'])) {
                unset($res['participants']);
            }
            $this->qres[] = $res;
        }
        if ($this->last_stored > time() && !$force) {
            //\danog\MadelineProto\Logger::log("========== WILL SERIALIZE IN ".($this->last_stored - time())." =============");
            return false;
        }
        if (empty($this->qres)) {
            return false;
        }

        try {
            $payload = json_encode($this->qres);
            //$path = '/tmp/ids'.hash('sha256', $payload);
            //file_put_contents($path, $payload);
            $id = isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, 'https://id.pwrtelegram.xyz/db'.$this->settings['pwr']['db_token'].'/addnewmadeline?d=pls&from='.$id);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $result = curl_exec($ch);
            curl_close($ch);
            //$result = shell_exec('curl '.escapeshellarg('https://id.pwrtelegram.xyz/db'.$this->settings['pwr']['db_token'].'/addnewmadeline?d=pls&from='.$id).' -d '.escapeshellarg('@'.$path).' -s >/dev/null 2>/dev/null & ');
            \danog\MadelineProto\Logger::log("============ $result =============", \danog\MadelineProto\Logger::VERBOSE);
            $this->qres = [];
            $this->last_stored = time() + 10;
        } catch (\danog\MadelineProto\Exception $e) {
            if (file_exists($path)) {
                unlink($path);
            }
            \danog\MadelineProto\Logger::log('======= COULD NOT STORE IN DB DUE TO '.$e->getMessage().' =============', \danog\MadelineProto\Logger::VERBOSE);
        }
    }

    public function resolve_username($username)
    {
        try {
            $res = $this->method_call('contacts.resolveUsername', ['username' => str_replace('@', '', $username)], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log('Username resolution failed with error '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            if (strpos($e->rpc, 'FLOOD_WAIT_') === 0 || $e->rpc === 'AUTH_KEY_UNREGISTERED' || $e->rpc === 'USERNAME_INVALID') {
                throw $e;
            }
            return false;
        }
        if ($res['_'] === 'contacts.resolvedPeer') {
            return $res;
        }

        return false;
    }
}
