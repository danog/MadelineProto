<?php

/**
 * PeerHandler module.
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

/**
 * Manages peers.
 */
trait PeerHandler
{
    public $caching_simple = [];
    public $caching_simple_username = [];
    public $caching_possible_username = [];

    public function to_supergroup($id)
    {
        return -($id + pow(10, (int) floor(log($id, 10) + 3)));
    }

    public function from_supergroup($id)
    {
        return -$id - pow(10, (int) floor(log(-$id, 10)));
    }

    public function is_supergroup($id)
    {
        $log = log(-$id, 10);

        return ($log - intval($log)) * 1000 < 10;
    }

    public function add_support($support)
    {
        $this->supportUser = $support['user']['id'];
    }

    public function add_user($user)
    {
        if (!isset($user['access_hash'])) {
            /*if (isset($this->chats[$user['id']]['access_hash']) && $this->chats[$user['id']]['access_hash']) {
            $this->logger->logger("No access hash with user {$user['id']}, using backup");
            $user['access_hash'] = $this->chats[$user['id']]['access_hash'];
            } else {
            $this->logger->logger("No access hash with user {$user['id']}, not trying to fetch it");
            $user['access_hash'] = 0;
            }*/
            if (!isset($this->caching_simple[$user['id']]) && !(isset($user['username']) && isset($this->caching_simple_username[$user['username']]))) {
                $this->logger->logger("No access hash with user {$user['id']}, trying to fetch by ID...");
                if (isset($user['username']) && !isset($this->caching_simple_username[$user['username']])) {
                    $this->caching_possible_username[$user['id']] = $user['username'];
                }
                $this->cache_pwr_chat($user['id'], false, true);
            } elseif (isset($user['username']) && !isset($this->chats[$user['id']]) && !isset($this->caching_simple_username[$user['username']])) {
                $this->logger->logger("No access hash with user {$user['id']}, trying to fetch by username...");
                $this->cache_pwr_chat($user['username'], false, true);
            } else {
                $this->logger->logger("No access hash with user {$user['id']}, tried and failed to fetch data...");
            }

            return;
        }
        switch ($user['_']) {
            case 'user':
                if (!isset($this->chats[$user['id']]) || $this->chats[$user['id']] !== $user) {
                    $this->logger->logger("Updated user {$user['id']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    $this->chats[$user['id']] = $user;
                    $this->cache_pwr_chat($user['id'], false, true);
                }
            case 'userEmpty':
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid user provided', $user);
                break;
        }
    }

    public function add_chat_async($chat)
    {
        switch ($chat['_']) {
            case 'chat':
            case 'chatEmpty':
            case 'chatForbidden':
                if (!isset($this->chats[-$chat['id']]) || $this->chats[-$chat['id']] !== $chat) {
                    $this->logger->logger("Updated chat -{$chat['id']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    $this->chats[-$chat['id']] = $chat;
                    $this->cache_pwr_chat(-$chat['id'], $this->settings['peer']['full_fetch'], true);
                }
                break;
            case 'channelEmpty':
                break;
            case 'channel':
            case 'channelForbidden':
                $bot_api_id = $this->to_supergroup($chat['id']);
                if (!isset($chat['access_hash'])) {
                    if (!isset($this->caching_simple[$bot_api_id]) && !(isset($chat['username']) && isset($this->caching_simple_username[$chat['username']]))) {
                        $this->logger->logger("No access hash with {$chat['_']} {$bot_api_id}, trying to fetch by ID...");
                        if (isset($chat['username']) && !isset($this->caching_simple_username[$chat['username']])) {
                            $this->caching_possible_username[$bot_api_id] = $chat['username'];
                        }

                        $this->cache_pwr_chat($bot_api_id, false, true);
                    } elseif (isset($chat['username']) && !isset($this->chats[$bot_api_id]) && !isset($this->caching_simple_username[$chat['username']])) {
                        $this->logger->logger("No access hash with {$chat['_']} {$bot_api_id}, trying to fetch by username...");
                        $this->cache_pwr_chat($chat['username'], false, true);
                    } else {
                        $this->logger->logger("No access hash with {$chat['_']} {$bot_api_id}, tried and failed to fetch data...");
                    }

                    return;
                }
                if (!isset($this->chats[$bot_api_id]) || $this->chats[$bot_api_id] !== $chat) {
                    $this->logger->logger("Updated chat $bot_api_id", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    $this->chats[$bot_api_id] = $chat;

                    if ($this->settings['peer']['full_fetch'] && (!isset($this->full_chats[$bot_api_id]) || $this->full_chats[$bot_api_id]['full']['participants_count'] !== (yield $this->get_full_info_async($bot_api_id))['full']['participants_count'])) {
                        $this->cache_pwr_chat($bot_api_id, $this->settings['peer']['full_fetch'], true);
                    }
                }
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid chat provided at key '.$key.': '.var_export($chat, true));
                break;
        }
    }

    public function cache_pwr_chat($id, $full_fetch, $send)
    {
        $this->callFork((function () use ($id, $full_fetch, $send) {
            try {
                yield $this->get_pwr_chat_async($id, $full_fetch, $send);
            } catch (\danog\MadelineProto\Exception $e) {
                $this->logger->logger('While caching: '.$e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->logger->logger('While caching: '.$e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            }
        })());
    }

    public function peer_isset_async($id)
    {
        try {
            return isset($this->chats[(yield $this->get_info_async($id))['bot_api_id']]);
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

    public function entities_peer_isset_async($entities)
    {
        try {
            foreach ($entities as $entity) {
                if ($entity['_'] === 'messageEntityMentionName' || $entity['_'] === 'inputMessageEntityMentionName') {
                    if (!yield $this->peer_isset_async($entity['user_id'])) {
                        return false;
                    }
                }
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function fwd_peer_isset_async($fwd)
    {
        try {
            if (isset($fwd['user_id']) && !yield $this->peer_isset_async($fwd['user_id'])) {
                return false;
            }
            if (isset($fwd['channel_id']) && !yield $this->peer_isset_async($this->to_supergroup($fwd['channel_id']))) {
                return false;
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }

        return true;
    }

    public function get_folder_id($id)
    {
        if (!is_array($id)) {
            return null;
        }
        if (!isset($id['folder_id'])) {
            return null;
        }
        return $id['folder_id'];
    }
    public function get_id($id)
    {
        if (is_array($id)) {
            switch ($id['_']) {
                case 'updateDialogPinned':
                case 'updateDialogUnreadMark':
                case 'updateNotifySettings':
                    $id = $id['peer'];
                case 'updateDraftMessage':
                case 'inputDialogPeer':
                case 'dialogPeer':
                case 'inputNotifyPeer':
                case 'notifyPeer':
                case 'dialog':
                case 'help.proxyDataPromo':
                case 'updateChatDefaultBannedRights':
                case 'folderPeer':
                case 'inputFolderPeer':
                    return $this->get_id($id['peer']);

                case 'inputUserFromMessage':
                case 'inputPeerUserFromMessage':
                    return $id['user_id'];

                case 'inputChannelFromMessage':
                case 'inputPeerChannelFromMessage':
                    return $this->to_supergroup($id['channel_id']);

                case 'inputUserSelf':
                case 'inputPeerSelf':
                    return $this->authorization['user']['id'];
                case 'user':
                    return $id['id'];
                case 'userFull':
                    return $id['user']['id'];
                case 'inputPeerUser':
                case 'inputUser':
                case 'peerUser':
                    return $id['user_id'];
                case 'chat':
                case 'chatForbidden':
                case 'chatFull':
                    return -$id['id'];
                case 'inputPeerChat':
                case 'peerChat':
                    return -$id['chat_id'];
                case 'channelForbidden':
                case 'channel':
                case 'channelFull':
                    return $this->to_supergroup($id['id']);
                case 'inputPeerChannel':
                case 'inputChannel':
                case 'peerChannel':
                    return $this->to_supergroup($id['channel_id']);
                case 'message':
                case 'messageService':
                    if (!isset($id['from_id']) || $id['to_id']['_'] !== 'peerUser' || $id['to_id']['user_id'] !== $this->authorization['user']['id']) {
                        return $this->get_id($id['to_id']);
                    }

                    return $id['from_id'];

                case 'updateChannelReadMessagesContents':
                case 'updateChannelAvailableMessages':
                case 'updateChannel':
                case 'updateChannelWebPage':
                case 'updateChannelMessageViews':
                case 'updateReadChannelInbox':
                case 'updateReadChannelOutbox':
                case 'updateDeleteChannelMessages':
                case 'updateChannelPinnedMessage':
                case 'updateChannelTooLong':
                    return $this->to_supergroup($id['channel_id']);
                case 'updateChatParticipants':
                    $id = $id['participants'];
                case 'updateChatUserTyping':
                case 'updateChatParticipantAdd':
                case 'updateChatParticipantDelete':
                case 'updateChatParticipantAdmin':
                case 'updateChatAdmins':
                case 'updateChatPinnedMessage':
                    return -$id['chat_id'];
                case 'updateUserTyping':
                case 'updateUserStatus':
                case 'updateUserName':
                case 'updateUserPhoto':
                case 'updateUserPhone':
                case 'updateUserBlocked':
                case 'updateContactRegistered':
                case 'updateContactLink':
                case 'updateBotInlineQuery':
                case 'updateInlineBotCallbackQuery':
                case 'updateBotInlineSend':
                case 'updateBotCallbackQuery':
                case 'updateBotPrecheckoutQuery':
                case 'updateBotShippingQuery':
                case 'updateUserPinnedMessage':
                    return $id['user_id'];
                case 'updatePhoneCall':
                    return $id->getOtherID();
                case 'updateReadHistoryInbox':
                case 'updateReadHistoryOutbox':
                    return $this->get_id($id['peer']);
                case 'updateNewMessage':
                case 'updateNewChannelMessage':
                case 'updateEditMessage':
                case 'updateEditChannelMessage':
                    return $this->get_id($id['message']);
                default:
                    throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($id, true));
            }
        }
        if (is_string($id)) {
            if (strpos($id, '#') !== false) {
                if (preg_match('/^channel#(\d*)/', $id, $matches)) {
                    return $this->to_supergroup($matches[1]);
                }
                if (preg_match('/^chat#(\d*)/', $id, $matches)) {
                    $id = '-'.$matches[1];
                }
                if (preg_match('/^user#(\d*)/', $id, $matches)) {
                    return $matches[1];
                }
            }
        }
        if (is_numeric($id)) {
            if (is_string($id)) {
                $id = \danog\MadelineProto\Magic::$bigint ? (float) $id : (int) $id;
            }

            return $id;
        }

        return false;
    }

    public function get_info_async($id, $recursive = true)
    {
        if (is_array($id)) {
            switch ($id['_']) {
                case 'updateEncryption':
                    return $this->get_secret_chat($id['chat']['id']);
                case 'updateEncryptedChatTyping':
                case 'updateEncryptedMessagesRead':
                    return $this->get_secret_chat($id['chat_id']);
                case 'updateNewEncryptedMessage':
                    $id = $id['message'];
                case 'encryptedMessage':
                case 'encryptedMessageService':
                    $id = $id['chat_id'];
                    if (!isset($this->secret_chats[$id])) {
                        throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['sec_peer_not_in_db']);
                    }

                    return $this->secret_chats[$id];
            }
        }
        $folder_id = $this->get_folder_id($id);
        $try_id = $this->get_id($id);
        if ($try_id !== false) {
            $id = $try_id;
        }

        $tried_simple = false;

        if (is_numeric($id)) {
            if (!isset($this->chats[$id])) {
                try {
                    $this->caching_simple[$id] = true;
                    if ($id < 0) {
                        if ($this->is_supergroup($id)) {
                            yield $this->method_call_async_read('channels.getChannels', ['id' => [['access_hash' => 0, 'channel_id' => $this->from_supergroup($id), '_' => 'inputChannel']]], ['datacenter' => $this->datacenter->curdc]);
                        } else {
                            yield $this->method_call_async_read('messages.getFullChat', ['chat_id' => -$id], ['datacenter' => $this->datacenter->curdc]);
                        }
                    } else {
                        yield $this->method_call_async_read('users.getUsers', ['id' => [['access_hash' => 0, 'user_id' => $id, '_' => 'inputUser']]], ['datacenter' => $this->datacenter->curdc]);
                    }
                } catch (\danog\MadelineProto\Exception $e) {
                    $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                } finally {
                    if (isset($this->caching_simple[$id])) {
                        unset($this->caching_simple[$id]);
                    }
                    $tried_simple = true;
                }
            }
            if (isset($this->chats[$id])) {
                try {
                    return $this->gen_all($this->chats[$id], $folder_id);
                } catch (\danog\MadelineProto\Exception $e) {
                    if ($e->getMessage() === 'This peer is not present in the internal peer database') {
                        unset($this->chats[$id]);
                    } else {
                        throw $e;
                    }
                }
            }
            if (!isset($this->settings['pwr']['requests']) || $this->settings['pwr']['requests'] === true && $recursive) {
                $dbres = [];

                try {
                    $dbres = json_decode(yield $this->datacenter->fileGetContents('https://id.pwrtelegram.xyz/db/getusername?id='.$id), true);
                } catch (\Throwable $e) {
                    $this->logger->logger($e);
                }
                if (isset($dbres['ok']) && $dbres['ok']) {
                    yield $this->resolve_username_async('@'.$dbres['result']);

                    return yield $this->get_info_async($id, false);
                }
            }
            if ($tried_simple && isset($this->caching_possible_username[$id])) {
                $this->logger->logger("No access hash with $id, trying to fetch by username...");

                $user = $this->caching_possible_username[$id];
                unset($this->caching_possible_username[$id]);

                return yield $this->get_info_async($user);
            }

            throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
        }
        if (preg_match('@(?:t|telegram)\.(?:me|dog)/(joinchat/)?([a-z0-9_-]*)@i', $id, $matches)) {
            if ($matches[1] === '') {
                $id = $matches[2];
            } else {
                $invite = yield $this->method_call_async_read('messages.checkChatInvite', ['hash' => $matches[2]], ['datacenter' => $this->datacenter->curdc]);
                if (isset($invite['chat'])) {
                    return yield $this->get_info_async($invite['chat']);
                } else {
                    throw new \danog\MadelineProto\Exception('You have not joined this chat');
                }
            }
        }
        $id = strtolower(str_replace('@', '', $id));
        if ($id === 'me') {
            return yield $this->get_info_async($this->authorization['user']['id']);
        }
        if ($id === 'support') {
            if (!$this->supportUser) {
                yield $this->method_call_async_read('help.getSupport', [], ['datacenter' => $this->settings['connection_settings']['default_dc']]);
            }

            return yield $this->get_info_async($this->supportUser);
        }
        foreach ($this->chats as $chat) {
            if (isset($chat['username']) && strtolower($chat['username']) === $id) {
                return $this->gen_all($chat, $folder_id);
            }
        }
        if ($recursive) {
            yield $this->resolve_username_async($id);

            return yield $this->get_info_async($id, false);
        }

        throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
    }

    public function gen_all($constructor, $folder_id = null)
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
                $res['DialogPeer'] = ['_' => 'dialogPeer', 'peer' => $res['Peer']];
                $res['NotifyPeer'] = ['_' => 'notifyPeer', 'peer' => $res['Peer']];
                $res['InputDialogPeer'] = ['_' => 'inputDialogPeer', 'peer' => $res['InputPeer']];
                $res['InputNotifyPeer'] = ['_' => 'inputNotifyPeer', 'peer' => $res['InputPeer']];
                $res['user_id'] = $constructor['id'];
                $res['bot_api_id'] = $constructor['id'];
                $res['type'] = $constructor['bot'] ? 'bot' : 'user';
                break;
            case 'chat':
            case 'chatForbidden':
                $res['InputPeer'] = ['_' => 'inputPeerChat', 'chat_id' => $constructor['id']];
                $res['Peer'] = ['_' => 'peerChat', 'chat_id' => $constructor['id']];
                $res['DialogPeer'] = ['_' => 'dialogPeer', 'peer' => $res['Peer']];
                $res['NotifyPeer'] = ['_' => 'notifyPeer', 'peer' => $res['Peer']];
                $res['InputDialogPeer'] = ['_' => 'inputDialogPeer', 'peer' => $res['InputPeer']];
                $res['InputNotifyPeer'] = ['_' => 'inputNotifyPeer', 'peer' => $res['InputPeer']];
                $res['chat_id'] = $constructor['id'];
                $res['bot_api_id'] = -$constructor['id'];
                $res['type'] = 'chat';
                break;
            case 'channel':
                if (!isset($constructor['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
                }
                $res['InputPeer'] = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $res['Peer'] = ['_' => 'peerChannel', 'channel_id' => $constructor['id']];
                $res['DialogPeer'] = ['_' => 'dialogPeer', 'peer' => $res['Peer']];
                $res['NotifyPeer'] = ['_' => 'notifyPeer', 'peer' => $res['Peer']];
                $res['InputDialogPeer'] = ['_' => 'inputDialogPeer', 'peer' => $res['InputPeer']];
                $res['InputNotifyPeer'] = ['_' => 'inputNotifyPeer', 'peer' => $res['InputPeer']];
                $res['InputChannel'] = ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash']];
                $res['channel_id'] = $constructor['id'];
                $res['bot_api_id'] = $this->to_supergroup($constructor['id']);
                $res['type'] = $constructor['megagroup'] ? 'supergroup' : 'channel';
                break;
            case 'channelForbidden':
                throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor given '.var_export($constructor, true));
        }
        if ($folder_id) {
            $res['FolderPeer'] = ['_' => 'folderPeer', 'peer' => $res['Peer'], 'folder_id' => $folder_id];
            $res['InputFolderPeer'] = ['_' => 'inputFolderPeer', 'peer' => $res['InputPeer'], 'folder_id' => $folder_id];
        }
        return $res;
    }

    public function full_chat_last_updated($id)
    {
        return isset($this->full_chats[$id]['last_update']) ? $this->full_chats[$id]['last_update'] : 0;
    }

    public function get_full_info_async($id)
    {
        $partial = yield $this->get_info_async($id);
        if (time() - $this->full_chat_last_updated($partial['bot_api_id']) < (isset($this->settings['peer']['full_info_cache_time']) ? $this->settings['peer']['full_info_cache_time'] : 0)) {
            return array_merge($partial, $this->full_chats[$partial['bot_api_id']]);
        }
        switch ($partial['type']) {
            case 'user':
            case 'bot':
                $full = yield $this->method_call_async_read('users.getFullUser', ['id' => $partial['InputUser']], ['datacenter' => $this->datacenter->curdc]);
                break;
            case 'chat':
                $full = (yield $this->method_call_async_read('messages.getFullChat', $partial, ['datacenter' => $this->datacenter->curdc]))['full_chat'];
                break;
            case 'channel':
            case 'supergroup':
                $full = (yield $this->method_call_async_read('channels.getFullChannel', ['channel' => $partial['InputChannel']], ['datacenter' => $this->datacenter->curdc]))['full_chat'];
                break;
        }

        $res = [];
        $res['full'] = $full;
        $res['last_update'] = time();
        $this->full_chats[$partial['bot_api_id']] = $res;

        $partial = yield $this->get_info_async($id);

        return array_merge($partial, $res);
    }

    public function get_pwr_chat_async($id, $fullfetch = true, $send = true)
    {
        $full = $fullfetch ? yield $this->get_full_info_async($id) : yield $this->get_info_async($id);
        $res = ['id' => $full['bot_api_id'], 'type' => $full['type']];
        switch ($full['type']) {
            case 'user':
            case 'bot':
                foreach (['first_name', 'last_name', 'username', 'verified', 'restricted', 'restriction_reason', 'status', 'bot_inline_placeholder', 'access_hash', 'phone', 'lang_code', 'bot_nochats'] as $key) {
                    if (isset($full['User'][$key])) {
                        $res[$key] = $full['User'][$key];
                    }
                }
                foreach (['about', 'bot_info', 'phone_calls_available', 'phone_calls_private', 'common_chats_count', 'can_pin_message', 'pinned_msg_id', 'notify_settings'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }
                if (isset($full['full']['profile_photo']['sizes'])) {
                    $res['photo'] = yield $this->photosize_to_botapi_async(end($full['full']['profile_photo']['sizes']), $full['full']['profile_photo']);
                }
                break;
            case 'chat':
                foreach (['title', 'participants_count', 'admin', 'admins_enabled'] as $key) {
                    if (isset($full['Chat'][$key])) {
                        $res[$key] = $full['Chat'][$key];
                    }
                }
                foreach (['bot_info', 'pinned_msg_id', 'notify_settings'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }
                if (isset($res['admins_enabled'])) {
                    $res['all_members_are_administrators'] = !$res['admins_enabled'];
                }
                if (isset($full['full']['chat_photo']['sizes'])) {
                    $res['photo'] = yield $this->photosize_to_botapi_async(end($full['full']['chat_photo']['sizes']), $full['full']['chat_photo']);
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
                foreach (['read_inbox_max_id', 'read_outbox_max_id', 'hidden_prehistory', 'bot_info', 'notify_settings', 'can_set_stickers', 'stickerset', 'can_view_participants', 'can_set_username', 'participants_count', 'admins_count', 'kicked_count', 'banned_count', 'migrated_from_chat_id', 'migrated_from_max_id', 'pinned_msg_id', 'about', 'hidden_prehistory', 'available_min_id', 'can_view_stats', 'online_count'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }
                if (isset($full['full']['chat_photo']['sizes'])) {
                    $res['photo'] = yield $this->photosize_to_botapi_async(end($full['full']['chat_photo']['sizes']), $full['full']['chat_photo']);
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
                $newres['user'] = yield $this->get_pwr_chat_async($participant['user_id'], false, true);
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = yield $this->get_pwr_chat_async($participant['inviter_id'], false, true);
                }
                if (isset($participant['promoted_by'])) {
                    $newres['promoted_by'] = yield $this->get_pwr_chat_async($participant['promoted_by'], false, true);
                }
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = yield $this->get_pwr_chat_async($participant['kicked_by'], false, true);
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
        if (!isset($res['participants']) && $fullfetch && in_array($res['type'], ['supergroup', 'channel'])) {
            $total_count = (isset($res['participants_count']) ? $res['participants_count'] : 0) + (isset($res['admins_count']) ? $res['admins_count'] : 0) + (isset($res['kicked_count']) ? $res['kicked_count'] : 0) + (isset($res['banned_count']) ? $res['banned_count'] : 0);
            $res['participants'] = [];
            $limit = 200;
            $filters = ['channelParticipantsAdmins', 'channelParticipantsBots'];
            foreach ($filters as $filter) {
                yield $this->fetch_participants_async($full['InputChannel'], $filter, '', $total_count, $res);
            }
            $q = '';

            $filters = ['channelParticipantsSearch', 'channelParticipantsKicked', 'channelParticipantsBanned'];
            foreach ($filters as $filter) {
                yield $this->recurse_alphabet_search_participants_async($full['InputChannel'], $filter, $q, $total_count, $res);
            }
            $this->logger->logger('Fetched '.count($res['participants'])." out of $total_count");
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

    public function recurse_alphabet_search_participants_async($channel, $filter, $q, $total_count, &$res)
    {
        if (!yield $this->fetch_participants_async($channel, $filter, $q, $total_count, $res)) {
            return false;
        }

        for ($x = 'a'; $x !== 'aa' && $total_count > count($res['participants']); $x++) {
            yield $this->recurse_alphabet_search_participants_async($channel, $filter, $q.$x, $total_count, $res);
        }
    }

    public function fetch_participants_async($channel, $filter, $q, $total_count, &$res)
    {
        $offset = 0;
        $limit = 200;
        $has_more = false;
        $cached = false;
        $last_count = -1;

        do {
            try {
                $gres = yield $this->method_call_async_read('channels.getParticipants', ['channel' => $channel, 'filter' => ['_' => $filter, 'q' => $q], 'offset' => $offset, 'limit' => $limit, 'hash' => $hash = $this->get_participants_hash($channel, $filter, $q, $offset, $limit)], ['datacenter' => $this->datacenter->curdc, 'heavy' => true]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->rpc === 'CHAT_ADMIN_REQUIRED') {
                    $this->logger->logger($e->rpc);

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

            if ($last_count !== -1 && $last_count !== $gres['count']) {
                $has_more = true;
            } else {
                $last_count = $gres['count'];
            }

            foreach ($gres['participants'] as $participant) {
                $newres = [];
                $newres['user'] = yield $this->get_pwr_chat_async($participant['user_id'], false, true);
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = yield $this->get_pwr_chat_async($participant['inviter_id'], false, true);
                }
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = yield $this->get_pwr_chat_async($participant['kicked_by'], false, true);
                }
                if (isset($participant['promoted_by'])) {
                    $newres['promoted_by'] = yield $this->get_pwr_chat_async($participant['promoted_by'], false, true);
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
            $this->logger->logger('Fetched '.count($gres['participants'])." channel participants with filter $filter, query $q, offset $offset, limit $limit, hash $hash: ".($cached ? 'cached' : 'not cached').', '.($offset + count($gres['participants'])).' participants out of '.$gres['count'].', in total fetched '.count($res['participants']).' out of '.$total_count);
            $offset += count($gres['participants']);
        } while (count($gres['participants']));

        if ($offset === $limit) {
            return true;
        }

        return $has_more;
    }

    public function fetch_participants_cache($channel, $filter, $q, $offset, $limit)
    {
        return $this->channel_participants[$channel['channel_id']][$filter][$q][$offset][$limit];
    }

    public function store_participants_cache($gres, $channel, $filter, $q, $offset, $limit)
    {
        //return;
        unset($gres['users']);
        $ids = [];
        foreach ($gres['participants'] as $participant) {
            $ids[] = $participant['user_id'];
        }
        sort($ids, SORT_NUMERIC);
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
            return;
        }
        if (!empty($res)) {
            if (isset($res['participants'])) {
                unset($res['participants']);
            }
            $this->qres[] = $res;
        }
        if ($this->last_stored > time() && !$force) {
            //$this->logger->logger("========== WILL SERIALIZE IN ".($this->last_stored - time())." =============");
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

            $request = (new Request('https://id.pwrtelegram.xyz/db'.$this->settings['pwr']['db_token'].'/addnewmadeline?d=pls&from='.$id, 'POST'))->withHeader('content-type', 'application/json')->withBody($payload);

            $result = yield (yield $this->datacenter->getHTTPClient()->request($request))->getBody();

            $this->logger->logger("============ $result =============", \danog\MadelineProto\Logger::VERBOSE);
            $this->qres = [];
            $this->last_stored = time() + 10;
        } catch (\danog\MadelineProto\Exception $e) {
            $this->logger->logger('======= COULD NOT STORE IN DB DUE TO '.$e->getMessage().' =============', \danog\MadelineProto\Logger::VERBOSE);
        }
    }

    public function resolve_username_async($username)
    {
        try {
            $this->caching_simple_username[$username] = true;
            $res = yield $this->method_call_async_read('contacts.resolveUsername', ['username' => str_replace('@', '', $username)], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->logger->logger('Username resolution failed with error '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            if (strpos($e->rpc, 'FLOOD_WAIT_') === 0 || $e->rpc === 'AUTH_KEY_UNREGISTERED' || $e->rpc === 'USERNAME_INVALID') {
                throw $e;
            }

            return false;
        } finally {
            if (isset($this->caching_simple_username[$username])) {
                unset($this->caching_simple_username[$username]);
            }
        }
        if ($res['_'] === 'contacts.resolvedPeer') {
            return $res;
        }

        return false;
    }
}
