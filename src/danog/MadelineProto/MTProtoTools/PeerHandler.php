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
 * Manages peers.
 */
trait PeerHandler
{
    public function add_users($users)
    {
        foreach ($users as $key => $user) {
            if (!isset($user['access_hash'])) {
                continue;
            }
            switch ($user['_']) {
                case 'user':
                    if (!isset($this->chats[$user['id']]) || $this->chats[$user['id']] !== $user) {
                        $this->chats[$user['id']] = $user;
                        $this->should_serialize = true;
                        try {
                            $this->get_pwr_chat($user['id'], false, true);
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
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
                        $this->should_serialize = true;
                        try {
                            $this->get_pwr_chat(-$chat['id'], true, true);
                        } catch (\danog\MadelineProto\Exception $e) {
                            \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                            \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
                        }
                    }

                case 'channelEmpty':
                    break;
                case 'channel':
                case 'channelForbidden':
                    if (!isset($chat['access_hash'])) {
                        continue;
                    }
                    $bot_api_id = $this->to_supergroup($chat['id']);
                    if (!isset($this->chats[$bot_api_id]) || $this->chats[$bot_api_id] !== $chat) {
                        $this->chats[$bot_api_id] = $chat;
                        $this->should_serialize = true;
                        if (!isset($this->full_chats[$bot_api_id]) || $this->full_chats[$bot_api_id]['full']['participants_count'] !== $this->get_full_info($bot_api_id)['full']['participants_count']) {
                            try {
                                $this->get_pwr_chat($this->to_supergroup($chat['id']), true, true);
                            } catch (\danog\MadelineProto\Exception $e) {
                                \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                                \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::WARNING);
                            }
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
            return $e->getMessage() === 'Chat forbidden';
        } catch (\danog\MadelineProto\RPCErrorException $e) {
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
            if (isset($fwd['channel_id']) && !$this->peer_isset('channel#'.$fwd['channel_id'])) {
                return false;
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function get_info($id, $recursive = true)
    {
        if ($this->is_array($id)) {
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
                    $id = -$id['id'];
                    break;
                case 'chatFull':
                    $id = -$id['id'];
                    break;
                case 'inputPeerChat':
                case 'peerChat':
                    $id = -$id['chat_id'];
                    break;

                case 'channel':
                    $id = $this->to_supergroup($id['id']);
                    break;
                case 'channelFull':
                    $id = $this->to_supergroup($id['id']);
                    break;

                case 'inputPeerChannel':
                case 'inputChannel':
                case 'peerChannel':
                    $id = $this->to_supergroup($id['channel_id']);
                    break;
                default:
                    throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($id, true));
                    break;
            }
        }
        if (is_string($id) && strpos($id, '#') !== false) {
            if (preg_match('/^channel#/', $id)) {
                $id = $this->to_supergroup(preg_replace('|\D+|', '', $id));
            }
            if (preg_match('/^chat#/', $id)) {
                $id = preg_replace('|\D+|', '-', $id);
            }
            if (preg_match('/^user#/', $id)) {
                $id = preg_replace('|\D+|', '', $id);
            }
        }

        if (is_numeric($id)) {
            if (is_string($id)) {
                $id = \danog\MadelineProto\Logger::$bigint ? ((float) $id) : ((int) $id);
            }
            if (isset($this->chats[$id])) {
                return $this->gen_all($this->chats[$id]);
            }
            if ($id < 0 && !preg_match('/^-100/', $id)) {
                $this->method_call('messages.getFullChat', ['chat_id' => -$id], ['datacenter' => $this->datacenter->curdc]);
                if (isset($this->chats[$id])) {
                    return $this->gen_all($this->chats[$id]);
                }
            }
            if (!isset($this->settings['pwr']['requests']) || $this->settings['pwr']['requests'] === true) {
                $dbres = json_decode(@file_get_contents('https://id.pwrtelegram.xyz/db/getusername?id='.$id, false, stream_context_create(['http'=> [
                        'timeout' => 2,
                    ],
                ])), true);
                if (isset($dbres['ok']) && $dbres['ok']) {
                    return $this->get_info('@'.$dbres['result']);
                }
            }
            throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
        }
        $id = str_replace('@', '', $id);
        foreach ($this->chats as $chat) {
            if (isset($chat['username']) && strtolower($chat['username']) === strtolower($id)) {
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
                throw new \danog\MadelineProto\Exception('Chat forbidden');
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
        $id = $this->get_info($id)['bot_api_id'];
        if (time() - $this->full_chat_last_updated($id) < (isset($this->settings['peer']['full_info_cache_time']) ? $this->settings['peer']['full_info_cache_time'] : 0)) {
            return $this->full_chats[$id];
        }
        $partial = $this->get_info($id);
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
        $partial['full'] = $full;
        $partial['last_update'] = time();
        $this->full_chats[$partial['bot_api_id']] = $partial;

        return $partial;
    }

    public function get_pwr_chat($id, $fullfetch = true, $send = true)
    {
        $full = $fullfetch ? $this->get_full_info($id) : $this->get_info($id);
        $res = ['id' => $full['bot_api_id'], 'type' => $full['type']];
        switch ($full['type']) {
            case 'user':
            case 'bot':
                foreach (['first_name', 'last_name', 'username', 'verified', 'restricted', 'restriction_reason', 'status', 'bot_inline_placeholder', 'access_hash', 'phone', 'lang_code'] as $key) {
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
                $bio = '';
                if ($full['type'] === 'user' && isset($res['username']) && !isset($res['about']) && $fullfetch) {
                    if (preg_match('/meta property="og:description" content=".+/', file_get_contents('https://telegram.me/'.$res['username']), $biores)) {
                        $bio = html_entity_decode(preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
                            return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
                        }, str_replace(['meta property="og:description" content="', '">'], '', $biores[0])));
                    }
                    if ($bio != '' && $bio != 'You can contact @'.$res['username'].' right away.') {
                        $res['about'] = $bio;
                    }
                }
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
                foreach (['can_view_participants', 'can_set_username', 'participants_count', 'admins_count', 'kicked_count', 'migrated_from_chat_id', 'migrated_from_max_id', 'pinned_msg_id', 'about'] as $key) {
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
        if (isset($res['participants'])) {
            foreach ($res['participants'] as $key => $participant) {
                $newres = [];
                $newres['user'] = $this->get_pwr_chat($participant['user_id'], false, false);
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = $this->get_pwr_chat($participant['inviter_id'], false, false);
                }
                if (isset($participant['date'])) {
                    $newres['date'] = $participant['date'];
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
        if (!isset($res['participants']) && isset($res['can_view_participants']) && $res['can_view_participants']) {
            $res['participants'] = [];
            $limit = 200;
            $offset = -$limit;
            $gres = $this->method_call('channels.getParticipants', ['channel' => $full['InputChannel'], 'filter' => ['_' => 'channelParticipantsRecent'], 'offset' => $offset += $limit, 'limit' => $limit], ['datacenter' => $this->datacenter->curdc]);
            $count = $gres['count'];
            $key = -1;
            while ($offset <= $count) {
                foreach ($gres['participants'] as $participant) {
                    $newres = [];
                    $newres['user'] = $this->get_pwr_chat($participant['user_id'], false, false);
                    $key++;
                    if (isset($participant['inviter_id'])) {
                        $newres['inviter'] = $this->get_pwr_chat($participant['inviter_id'], false, false);
                    }
                    if (isset($participant['date'])) {
                        $newres['date'] = $participant['date'];
                    }
                    switch ($participant['_']) {
                        case 'channelParticipant':
                        $newres['role'] = 'user';
                        break;

                        case 'channelParticipantModerator':
                        $newres['role'] = 'moderator';
                        break;

                        case 'channelParticipantEditor':
                        $newres['role'] = 'editor';
                        break;

                        case 'channelParticipantCreator':
                        $newres['role'] = 'creator';
                        break;

                        case 'channelParticipantKicked':
                        $newres['role'] = 'kicked';
                        break;

                    }
                    $res['participants'][$key] = $newres;
                }
                $gres = $this->method_call('channels.getParticipants', ['channel' => $full['InputChannel'], 'filter' => ['_' => 'channelParticipantsRecent'], 'offset' => $offset += $limit, 'limit' => $limit], ['datacenter' => $this->datacenter->curdc]);
                if (empty($gres['participants'])) {
                    break;
                }
            }
        }
        if ($fullfetch || $send) {
            $this->store_db($res);
        }

        return $res;
    }

    public function store_db($res, $force = false)
    {
        if (!isset($this->settings['pwr']) || $this->settings['pwr']['pwr'] === false) {
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
        if ($this->last_stored < time() && !$force) {
            return false;
        }
        if (empty($this->qres)) {
            return false;
        }
        try {
            $payload = json_encode($this->qres);
            $path = '/tmp/ids'.hash('sha256', $payload);
            file_put_contents($path, $payload);
            $id = isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'];
            $result = shell_exec('curl '.escapeshellarg('https://id.pwrtelegram.xyz/db'.$this->settings['pwr']['db_token'].'/addnewmadeline?d=pls&from='.$id).' -d '.escapeshellarg('@'.$path).' -s -o '.escapeshellarg($path.'.log').' >/dev/null 2>/dev/null & ');
            \danog\MadelineProto\Logger::log([$result], \danog\MadelineProto\Logger::VERBOSE);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log([$e->getMessage()], \danog\MadelineProto\Logger::VERBOSE);
        }
        $this->qres = [];
        $this->last_stored = time() + 10;
    }

    public function resolve_username($username)
    {
        //\Rollbar\Rollbar::log($username);
        $res = $this->method_call('contacts.resolveUsername', ['username' => str_replace('@', '', $username)], ['datacenter' => $this->datacenter->curdc]);
        if ($res['_'] === 'contacts.resolvedPeer') {
            return $res;
        }
        throw new \danog\MadelineProto\Exception('resolve_username returned an unexpected constructor: '.var_export($res, true));
    }

    public function to_supergroup($id)
    {
        return -($id + pow(10, (int) floor(log($id, 10) + 3)));
    }
}
