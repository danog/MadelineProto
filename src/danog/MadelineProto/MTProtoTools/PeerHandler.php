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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Http\Client\Request;
use Amp\Promise;
use danog\Decoder\FileId;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto;
use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;

use const danog\Decoder\PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG;
use const danog\Decoder\PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL;
use const danog\Decoder\PROFILE_PHOTO;

/**
 * Manages peers.
 *
 * @property Settings $settings Settings
 */
trait PeerHandler
{
    public $caching_simple = [];
    public $caching_simple_username = [];
    public $caching_possible_username = [];
    public $caching_full_info = [];

    /**
     * Convert MTProto channel ID to bot API channel ID.
     *
     * @param int $id MTProto channel ID
     *
     * @return int
     */
    public static function toSupergroup($id): int
    {
        return Magic::ZERO_CHANNEL_ID - $id;
    }
    /**
     * Convert bot API channel ID to MTProto channel ID.
     *
     * @param int $id Bot API channel ID
     *
     * @return int
     */
    public static function fromSupergroup($id): int
    {
        return (-$id) + Magic::ZERO_CHANNEL_ID;
    }
    /**
     * Check whether provided bot API ID is a channel.
     *
     * @param int $id Bot API ID
     *
     * @return boolean
     */
    public static function isSupergroup($id): bool
    {
        return $id < Magic::ZERO_CHANNEL_ID;
    }
    /**
     * Set support info.
     *
     * @param array $support Support info
     *
     * @internal
     *
     * @return void
     */
    public function addSupport(array $support): void
    {
        $this->supportUser = $support['user']['id'];
    }

    /**
     * Add user info.
     *
     * @param array $user User info
     *
     * @return \Generator
     * @throws \danog\MadelineProto\Exception
     */
    public function addUser(array $user): \Generator
    {
        $existingChat = yield $this->chats[$user['id']];
        if ($existingChat) {
            $this->cacheChatUsername($user['id'], $user);
        }
        if (!isset($user['access_hash']) && !($user['min'] ?? false)) {
            if (!empty($existingChat['access_hash'])) {
                $this->logger->logger("No access hash with user {$user['id']}, using backup");
                $user['access_hash'] = $existingChat['access_hash'];
            } elseif (!isset($this->caching_simple[$user['id']]) && !(isset($user['username']) && isset($this->caching_simple_username[$user['username']]))) {
                $this->logger->logger("No access hash with user {$user['id']}, trying to fetch by ID...");
                if (isset($user['username']) && !isset($this->caching_simple_username[$user['username']])) {
                    $this->caching_possible_username[$user['id']] = $user['username'];
                }
                $this->cachePwrChat($user['id'], false, true);
            } elseif (isset($user['username']) && !$existingChat && !isset($this->caching_simple_username[$user['username']])) {
                $this->logger->logger("No access hash with user {$user['id']}, trying to fetch by username...");
                $this->cachePwrChat($user['username'], false, true);
            } else {
                $this->logger->logger("No access hash with user {$user['id']}, tried and failed to fetch data...");
            }
            return;
        }
        switch ($user['_']) {
            case 'user':
                if (!$existingChat || $existingChat !== $user) {
                    $this->logger->logger("Updated user {$user['id']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    if (($user['min'] ?? false) && !($existingChat['min'] ?? false)) {
                        $this->logger->logger("{$user['id']} is min, filling missing fields", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        if (isset($existingChat['access_hash'])) {
                            $user['min'] = false;
                            $user['access_hash'] = $existingChat['access_hash'];
                        }
                    }
                    if (!$this->getSettings()->getDb()->getEnablePeerInfoDb()) {
                        $user = [
                            '_' => $user['_'],
                            'id' => $user['id'],
                            'access_hash' => $user['access_hash'] ?? null,
                            'min' => $user['min'] ?? false,
                        ];
                    }
                    yield $this->chats->set($user['id'], $user);
                    $this->cachePwrChat($user['id'], false, true);
                }
                $this->cacheChatUsername($user['id'], $user);
                break;
            case 'userEmpty':
                break;
            default:
                throw new \danog\MadelineProto\Exception('Invalid user provided', $user);
        }
    }
    /**
     * Add chat to database.
     *
     * @param array $chat Chat
     *
     * @internal
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, \Amp\Promise|null, mixed, void>
     */
    public function addChat($chat): \Generator
    {
        switch ($chat['_']) {
            case 'chat':
            case 'chatEmpty':
            case 'chatForbidden':
                $existingChat = yield $this->chats[-$chat['id']];
                if (!$existingChat || $existingChat !== $chat) {
                    $this->logger->logger("Updated chat -{$chat['id']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    if (!$this->getSettings()->getDb()->getEnablePeerInfoDb()) {
                        $chat = [
                            '_' => $chat['_'],
                            'id' => $chat['id'],
                            'access_hash' => $chat['access_hash'] ?? null,
                            'min' => $chat['min'] ?? false,
                        ];
                    }
                    yield $this->chats->set(-$chat['id'], $chat);
                    $this->cachePwrChat(-$chat['id'], $this->getSettings()->getPeer()->getFullFetch(), true);
                }
                $this->cacheChatUsername(-$chat['id'], $chat);
                break;
            case 'channelEmpty':
                break;
            case 'channel':
            case 'channelForbidden':
                $bot_api_id = $this->toSupergroup($chat['id']);
                if (!isset($chat['access_hash'])) {
                    if (!isset($this->caching_simple[$bot_api_id]) && !(isset($chat['username']) && isset($this->caching_simple_username[$chat['username']]))) {
                        $this->logger->logger("No access hash with {$chat['_']} {$bot_api_id}, trying to fetch by ID...");
                        if (isset($chat['username']) && !isset($this->caching_simple_username[$chat['username']])) {
                            $this->caching_possible_username[$bot_api_id] = $chat['username'];
                        }
                        $this->cachePwrChat($bot_api_id, false, true);
                    } elseif (isset($chat['username']) && !(yield $this->chats[$bot_api_id]) && !isset($this->caching_simple_username[$chat['username']])) {
                        $this->logger->logger("No access hash with {$chat['_']} {$bot_api_id}, trying to fetch by username...");
                        $this->cachePwrChat($chat['username'], false, true);
                    } else {
                        $this->logger->logger("No access hash with {$chat['_']} {$bot_api_id}, tried and failed to fetch data...");
                    }
                    return;
                }
                $existingChat = yield $this->chats[$bot_api_id];
                if (!$existingChat || $existingChat !== $chat) {
                    $this->logger->logger("Updated chat {$bot_api_id}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                    if (($chat['min'] ?? false) && $existingChat && !($existingChat['min'] ?? false)) {
                        $this->logger->logger("{$bot_api_id} is min, filling missing fields", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                        $newchat = $existingChat;
                        foreach (['title', 'username', 'photo', 'banned_rights', 'megagroup', 'verified'] as $field) {
                            if (isset($chat[$field])) {
                                $newchat[$field] = $chat[$field];
                            }
                        }
                        $chat = $newchat;
                    }
                    if (!$this->getSettings()->getDb()->getEnablePeerInfoDb()) {
                        $chat = [
                            '_' => $chat['_'],
                            'id' => $chat['id'],
                            'access_hash' => $chat['access_hash'] ?? null,
                            'min' => $chat['min'] ?? false,
                        ];
                    }
                    yield $this->chats->set($bot_api_id, $chat);
                    $fullChat = yield $this->full_chats[$bot_api_id];
                    if ($this->getSettings()->getPeer()->getFullFetch() && $this->getSettings()->getDb()->getEnableFullPeerDb() && (!$fullChat || $fullChat['full']['participants_count'] !== (yield from $this->getFullInfo($bot_api_id))['full']['participants_count'])) {
                        $this->cachePwrChat($bot_api_id, $this->getSettings()->getPeer()->getFullFetch(), true);
                    }
                }
                $this->cacheChatUsername($bot_api_id, $chat);
                break;
        }
    }

    private function cacheChatUsername($id, array $chat): void
    {
        if ($id && !empty($chat['username']) && $this->getSettings()->getDb()->getEnableUsernameDb()) {
            $this->usernames[\strtolower($chat['username'])] = $id;
        }
    }

    private function cachePwrChat($id, $full_fetch, $send): void
    {
        \danog\MadelineProto\Tools::callFork((function () use ($id, $full_fetch, $send): \Generator {
            try {
                yield from $this->getPwrChat($id, $full_fetch, $send);
            } catch (\danog\MadelineProto\Exception $e) {
                $this->logger->logger('While caching: '.$e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->logger->logger('While caching: '.$e->getMessage(), \danog\MadelineProto\Logger::WARNING);
            }
        })());
    }
    /**
     * Check if peer is present in internal peer database.
     *
     * @param mixed $id Peer
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int|mixed, \Amp\Promise|array, mixed, bool>
     */
    public function peerIsset($id): \Generator
    {
        try {
            return (yield $this->chats[yield from $this->getInfo($id, MTProto::INFO_TYPE_ID)]) !== null;
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
    /**
     * Check if all peer entities are in db.
     *
     * @param array $entities Entity list
     *
     * @internal
     *
     * @return \Generator<bool>
     */
    public function entitiesPeerIsset(array $entities): \Generator
    {
        try {
            foreach ($entities as $entity) {
                if ($entity['_'] === 'messageEntityMentionName' || $entity['_'] === 'inputMessageEntityMentionName') {
                    if (!(yield from $this->peerIsset($entity['user_id']))) {
                        return false;
                    }
                }
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }
        return true;
    }
    /**
     * Check if fwd peer is set.
     *
     * @param array $fwd Forward info
     *
     * @internal
     *
     * @return \Generator
     */
    public function fwdPeerIsset(array $fwd): \Generator
    {
        try {
            if (isset($fwd['user_id']) && !(yield from $this->peerIsset($fwd['user_id']))) {
                return false;
            }
            if (isset($fwd['channel_id']) && !(yield from $this->peerIsset($this->toSupergroup($fwd['channel_id'])))) {
                return false;
            }
        } catch (\danog\MadelineProto\Exception $e) {
            return false;
        }
        return true;
    }
    /**
     * Get folder ID from object.
     *
     * @param mixed $id Object
     *
     * @return ?int
     */
    public static function getFolderId($id): ?int
    {
        if (!\is_array($id)) {
            return null;
        }
        if (!isset($id['folder_id'])) {
            return null;
        }
        return $id['folder_id'];
    }
    /**
     * Get bot API ID from peer object.
     *
     * @param mixed $id Peer
     *
     * @return int
     */
    public function getId($id)
    {
        if (\is_array($id)) {
            switch ($id['_']) {
                case 'updateDialogPinned':
                case 'updateDialogUnreadMark':
                case 'updateNotifySettings':
                    $id = $id['peer'];
                // no break
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
                    return $this->getId($id['peer']);
                case 'inputUserFromMessage':
                case 'inputPeerUserFromMessage':
                    return $id['user_id'];
                case 'inputChannelFromMessage':
                case 'inputPeerChannelFromMessage':
                    return $this->toSupergroup($id['channel_id']);
                case 'inputUserSelf':
                case 'inputPeerSelf':
                    return $this->authorization['user']['id'];
                case 'user':
                    return $id['id'];
                case 'userFull':
                    return $id['id'];
                case 'inputPeerUser':
                case 'inputUser':
                case 'peerUser':
                case 'messageEntityMentionName':
                case 'messageActionChatDeleteUser':
                    return $id['user_id'];
                case 'messageActionChatJoinedByLink':
                    return $id['inviter_id'];
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
                    return $this->toSupergroup($id['id']);
                case 'inputPeerChannel':
                case 'inputChannel':
                case 'peerChannel':
                    return $this->toSupergroup($id['channel_id']);
                case 'message':
                case 'messageService':
                    if (!isset($id['from_id']) // No other option
                        // It's a channel/chat, 100% what we need
                        || $id['peer_id']['_'] !== 'peerUser'
                        // It is a user, and it's not ourselves
                        || $id['peer_id']['user_id'] !== $this->authorization['user']['id']
                    ) {
                        return $this->getId($id['peer_id']);
                    }
                    return $this->getId($id['from_id']);
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
                    return $this->toSupergroup($id['channel_id']);
                case 'updateChatParticipants':
                    $id = $id['participants'];
                // no break
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
                case 'contact':
                    return $id['user_id'];
                case 'updatePhoneCall':
                    return $id['phone_call']->getOtherID();
                case 'updateReadHistoryInbox':
                case 'updateReadHistoryOutbox':
                    return $this->getId($id['peer']);
                case 'updateNewMessage':
                case 'updateNewChannelMessage':
                case 'updateEditMessage':
                case 'updateEditChannelMessage':
                    return $this->getId($id['message']);
                default:
                    throw new \danog\MadelineProto\Exception('Invalid constructor given '.$id['_']);
            }
        }
        if (\is_string($id)) {
            if (\strpos($id, '#') !== false) {
                if (\preg_match('/^channel#(\\d*)/', $id, $matches)) {
                    return $this->toSupergroup($matches[1]);
                }
                if (\preg_match('/^chat#(\\d*)/', $id, $matches)) {
                    $id = '-'.$matches[1];
                }
                if (\preg_match('/^user#(\\d*)/', $id, $matches)) {
                    return $matches[1];
                }
            }
        }
        if (\is_numeric($id)) {
            if (\is_string($id)) {
                $id = (int) $id;
            }
            return $id;
        }
        return false;
    }
    /**
     * Get InputPeer object.
     *
     * @internal
     *
     * @param mixed $id Peer
     * @return \Generator
     */
    public function getInputPeer($id): \Generator
    {
        return $this->getInfo($id, MTProto::INFO_TYPE_PEER);
    }
    /**
     * Get InputUser/InputChannel object.
     *
     * @internal
     *
     * @param mixed $id Peer
     * @return \Generator
     */
    public function getInputConstructor($id): \Generator
    {
        return $this->getInfo($id, MTProto::INFO_TYPE_CONSTRUCTOR);
    }
    /**
     * Get info about peer, returns an Info object.
     *
     * @param mixed                $id        Peer
     * @param MTProto::INFO_TYPE_* $type      Whether to generate an Input*, an InputPeer or the full set of constructors
     * @param boolean              $recursive Internal
     *
     * @see https://docs.madelineproto.xyz/Info.html
     *
     * @return \Generator Info object
     *
     * @template TConstructor
     * @psalm-param $id array{_: TConstructor}|mixed
     *
     * @return (((mixed|string)[]|mixed|string)[]|int|mixed|string)[]
     *
     * @psalm-return \Generator<int|mixed, \Amp\Promise|\Amp\Promise<string>|array, mixed, array{
     *      TConstructor: array
     *      InputPeer: array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed},
     *      Peer: array{_: string, user_id?: mixed, chat_id?: mixed, channel_id?: mixed},
     *      DialogPeer: array{_: string, peer: array{_: string, user_id?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      NotifyPeer: array{_: string, peer: array{_: string, user_id?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      InputDialogPeer: array{_: string, peer: array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      InputNotifyPeer: array{_: string, peer: array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      bot_api_id: int|string,
     *      user_id?: int,
     *      chat_id?: int,
     *      channel_id?: int,
     *      InputUser?: array{_: string, user_id?: int, access_hash?: mixed, min?: bool},
     *      InputChannel?: array{_: string, channel_id: int, access_hash: mixed, min: bool},
     *      type: string
     * }>|int|array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed}|array{_: string, user_id?: int, access_hash?: mixed, min?: bool}|array{_: string, channel_id: int, access_hash: mixed, min: bool}
     */
    public function getInfo($id, int $type = MTProto::INFO_TYPE_ALL, $recursive = true): \Generator
    {
        if (\is_array($id)) {
            switch ($id['_']) {
                case 'updateEncryption':
                    return $this->getSecretChat($id['chat']['id']);
                case 'inputEncryptedChat':
                case 'updateEncryptedChatTyping':
                case 'updateEncryptedMessagesRead':
                    return $this->getSecretChat($id['chat_id']);
                case 'updateNewEncryptedMessage':
                    $id = $id['message'];
                // no break
                case 'encryptedMessage':
                case 'encryptedMessageService':
                    $id = $id['chat_id'];
                    if (!isset($this->secret_chats[$id])) {
                        throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['sec_peer_not_in_db']);
                    }
                    return $this->secret_chats[$id];
            }
        }
        $folder_id = $this->getFolderId($id);
        $try_id = $this->getId($id);
        if ($try_id !== false) {
            $id = $try_id;
        }
        $tried_simple = false;
        if (\is_numeric($id)) {
            if (!yield $this->chats[$id]) {
                try {
                    $this->logger->logger("Try fetching {$id} with access hash 0");
                    $this->caching_simple[$id] = true;
                    if ($id < 0) {
                        if ($this->isSupergroup($id)) {
                            yield from $this->methodCallAsyncRead('channels.getChannels', ['id' => [['access_hash' => 0, 'channel_id' => $this->fromSupergroup($id), '_' => 'inputChannel']]]);
                        } else {
                            yield from $this->methodCallAsyncRead('messages.getFullChat', ['chat_id' => -$id]);
                        }
                    } else {
                        yield from $this->methodCallAsyncRead('users.getUsers', ['id' => [['access_hash' => 0, 'user_id' => $id, '_' => 'inputUser']]]);
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
            if (yield $this->chats[$id]) {
                if (((yield $this->chats[$id])['min'] ?? false) && yield $this->minDatabase->hasPeer($id) && !isset($this->caching_full_info[$id])) {
                    $this->caching_full_info[$id] = true;
                    $this->logger->logger("Only have min peer for {$id} in database, trying to fetch full info");
                    try {
                        if ($id < 0) {
                            yield from $this->methodCallAsyncRead('channels.getChannels', ['id' => [$this->genAll(yield $this->chats[$id], $folder_id, MTProto::INFO_TYPE_CONSTRUCTOR)]]);
                        } else {
                            yield from $this->methodCallAsyncRead('users.getUsers', ['id' => [$this->genAll(yield $this->chats[$id], $folder_id, MTProto::INFO_TYPE_CONSTRUCTOR)]]);
                        }
                    } catch (\danog\MadelineProto\Exception $e) {
                        $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                    } finally {
                        unset($this->caching_full_info[$id]);
                    }
                }
                try {
                    return $this->genAll(yield $this->chats[$id], $folder_id, $type);
                } catch (\danog\MadelineProto\Exception $e) {
                    if ($e->getMessage() === 'This peer is not present in the internal peer database') {
                        yield $this->chats->unset($id);/** @uses DbArray::offsetUnset() */
                    } else {
                        throw $e;
                    }
                }
            }
            if ($this->settings->getPwr()->getRequests() && $recursive) {
                $dbres = [];
                try {
                    $dbres = \json_decode(yield from $this->datacenter->fileGetContents('https://id.pwrtelegram.xyz/db/getusername?id='.$id), true);
                } catch (\Throwable $e) {
                    $this->logger->logger($e);
                }
                if (isset($dbres['ok']) && $dbres['ok']) {
                    yield from $this->resolveUsername('@'.$dbres['result']);
                    return yield from $this->getInfo($id, $type, false);
                }
            }
            if ($tried_simple && isset($this->caching_possible_username[$id])) {
                $this->logger->logger("No access hash with {$id}, trying to fetch by username...");
                $user = $this->caching_possible_username[$id];
                unset($this->caching_possible_username[$id]);
                return yield from $this->getInfo($user, $type);
            }
            throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
        }
        if ($r = Tools::parseLink($id)) {
            [$invite, $content] = $r;
            if ($invite) {
                $invite = yield from $this->methodCallAsyncRead('messages.checkChatInvite', ['hash' => $content]);
                if (isset($invite['chat'])) {
                    return yield from $this->getInfo($invite['chat'], $type);
                }
                throw new \danog\MadelineProto\Exception('You have not joined this chat');
            } else {
                $id = $content;
            }
        }
        $id = \strtolower(\str_replace('@', '', $id));
        if ($id === 'me') {
            return yield from $this->getInfo($this->authorization['user']['id'], $type);
        }
        if ($id === 'support') {
            if (!$this->supportUser) {
                yield from $this->methodCallAsyncRead('help.getSupport', [], $this->settings->getDefaultDcParams());
            }
            return yield from $this->getInfo($this->supportUser, $type);
        }
        if ($bot_api_id = yield $this->usernames[$id]) {
            $chat = yield $this->chats[$bot_api_id];
            if (empty($chat['username']) || \strtolower($chat['username']) !== $id) {
                yield $this->usernames->unset($id); /** @uses DbArray::offsetUnset() */
            }

            if (isset($chat['username']) && \strtolower($chat['username']) === $id) {
                if ($chat['min'] ?? false && !isset($this->caching_full_info[$bot_api_id])) {
                    $this->caching_full_info[$bot_api_id] = true;
                    $this->logger->logger("Only have min peer for {$bot_api_id} in database, trying to fetch full info");
                    try {
                        if ($bot_api_id < 0) {
                            yield from $this->methodCallAsyncRead('channels.getChannels', ['id' => [$this->genAll(yield $this->chats[$bot_api_id], $folder_id, MTProto::INFO_TYPE_CONSTRUCTOR)]]);
                        } else {
                            yield from $this->methodCallAsyncRead('users.getUsers', ['id' => [$this->genAll(yield $this->chats[$bot_api_id], $folder_id, MTProto::INFO_TYPE_CONSTRUCTOR)]]);
                        }
                    } catch (\danog\MadelineProto\Exception $e) {
                        $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $this->logger->logger($e->getMessage(), \danog\MadelineProto\Logger::WARNING);
                    } finally {
                        unset($this->caching_full_info[$bot_api_id]);
                    }
                }
                return $this->genAll(yield $this->chats[$bot_api_id], $folder_id, $type);
            }
        }

        if ($recursive) {
            yield from $this->resolveUsername($id);
            return yield from $this->getInfo($id, $type, false);
        }
        throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
    }
    /**
     * @template TConstructor
     * @psalm-param $constructor array{_: TConstructor}
     * @psalm-param $type        bool|null
     *
     * @return (((mixed|string)[]|mixed|string)[]|int|mixed|string)[]
     *
     * @psalm-return array{
     *      TConstructor: array
     *      InputPeer: array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed},
     *      Peer: array{_: string, user_id?: mixed, chat_id?: mixed, channel_id?: mixed},
     *      DialogPeer: array{_: string, peer: array{_: string, user_id?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      NotifyPeer: array{_: string, peer: array{_: string, user_id?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      InputDialogPeer: array{_: string, peer: array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      InputNotifyPeer: array{_: string, peer: array{_: string, user_id?: mixed, access_hash?: mixed, min?: mixed, chat_id?: mixed, channel_id?: mixed}},
     *      bot_api_id: int|string,
     *      user_id?: int,
     *      chat_id?: int,
     *      channel_id?: int,
     *      InputUser?: {_: string, user_id?: int, access_hash?: mixed, min?: bool},
     *      InputChannel?: {_: string, channel_id: int, access_hash: mixed, min: bool},
     *      type: string
     * }
     */
    private function genAll($constructor, $folder_id, int $type)
    {
        if ($type === MTProto::INFO_TYPE_CONSTRUCTOR) {
            if ($constructor['_'] === 'user') {
                return ($constructor['self'] ?? false) ? ['_' => 'inputUserSelf'] : ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
            }
            if ($constructor['_'] === 'channel') {
                return ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
            }
        }
        if ($type === MTProto::INFO_TYPE_PEER) {
            if ($constructor['_'] === 'user') {
                return ($constructor['self'] ?? false) ? ['_' => 'inputPeerSelf'] : ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
            }
            if ($constructor['_'] === 'channel') {
                return ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
            }
            if ($constructor['_'] === 'chat' || $constructor['_'] === 'chatForbidden') {
                return ['_' => 'inputPeerChat', 'chat_id' => $constructor['id']];
            }
        }
        if ($type === MTProto::INFO_TYPE_ID) {
            if ($constructor['_'] === 'user') {
                return $constructor['id'];
            }
            if ($constructor['_'] === 'channel') {
                return $this->toSupergroup($constructor['id']);
            }
            if ($constructor['_'] === 'chat' || $constructor['_'] === 'chatForbidden') {
                return -$constructor['id'];
            }
        }
        $res = [$this->TL->getConstructors()->findByPredicate($constructor['_'])['type'] => $constructor];
        switch ($constructor['_']) {
            case 'user':
                if ($constructor['self'] ?? false) {
                    $res['InputPeer'] = ['_' => 'inputPeerSelf'];
                    $res['InputUser'] = ['_' => 'inputUserSelf'];
                } elseif (isset($constructor['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
                    $res['InputUser'] = ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
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
                $res['type'] = $constructor['bot'] ?? false ? 'bot' : 'user';
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
                $res['InputPeer'] = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
                $res['Peer'] = ['_' => 'peerChannel', 'channel_id' => $constructor['id']];
                $res['DialogPeer'] = ['_' => 'dialogPeer', 'peer' => $res['Peer']];
                $res['NotifyPeer'] = ['_' => 'notifyPeer', 'peer' => $res['Peer']];
                $res['InputDialogPeer'] = ['_' => 'inputDialogPeer', 'peer' => $res['InputPeer']];
                $res['InputNotifyPeer'] = ['_' => 'inputNotifyPeer', 'peer' => $res['InputPeer']];
                $res['InputChannel'] = ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min']];
                $res['channel_id'] = $constructor['id'];
                $res['bot_api_id'] = $this->toSupergroup($constructor['id']);
                $res['type'] = $constructor['megagroup'] ?? false ? 'supergroup' : 'channel';
                break;
            case 'channelForbidden':
                throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor given '.$constructor['_']);
        }
        if ($folder_id) {
            $res['FolderPeer'] = ['_' => 'folderPeer', 'peer' => $res['Peer'], 'folder_id' => $folder_id];
            $res['InputFolderPeer'] = ['_' => 'inputFolderPeer', 'peer' => $res['InputPeer'], 'folder_id' => $folder_id];
        }
        return $res;
    }
    /**
     * When were full info for this chat last cached.
     *
     * @param mixed $id Chat ID
     *
     * @return \Generator<integer>
     */
    public function fullChatLastUpdated($id): \Generator
    {
        return (yield $this->full_chats[$id])['last_update'] ?? 0;
    }
    /**
     * Get full info about peer, returns an FullInfo object.
     *
     * @param mixed $id Peer
     *
     * @see https://docs.madelineproto.xyz/FullInfo.html
     *
     * @return \Generator FullInfo object
     *
     * @psalm-return \Generator<int|mixed, \Amp\Promise|array, mixed, array>
     */
    public function getFullInfo($id): \Generator
    {
        $partial = (yield from $this->getInfo($id));
        if (\time() - (yield from $this->fullChatLastUpdated($partial['bot_api_id'])) < $this->getSettings()->getPeer()->getFullInfoCacheTime()) {
            return \array_merge($partial, yield $this->full_chats[$partial['bot_api_id']]);
        }
        $full = null;
        switch ($partial['type']) {
            case 'user':
            case 'bot':
                $full = yield from $this->methodCallAsyncRead('users.getFullUser', ['id' => $partial['InputUser']]);
                break;
            case 'chat':
                $full = (yield from $this->methodCallAsyncRead('messages.getFullChat', $partial))['full_chat'];
                break;
            case 'channel':
            case 'supergroup':
                $full = (yield from $this->methodCallAsyncRead('channels.getFullChannel', ['channel' => $partial['InputChannel']]))['full_chat'];
                break;
        }
        $res = [];
        $res['full'] = $full;
        $res['last_update'] = \time();
        $this->full_chats[$partial['bot_api_id']] = $res;
        $partial = (yield from $this->getInfo($id));
        return \array_merge($partial, $res);
    }
    /**
     * Get full info about peer (including full list of channel members), returns a Chat object.
     *
     * @param mixed $id Peer
     *
     * @see https://docs.madelineproto.xyz/Chat.html
     *
     * @return \Generator Chat object
     */
    public function getPwrChat($id, bool $fullfetch = true, bool $send = true): \Generator
    {
        $full = $fullfetch && $this->getSettings()->getDb()->getEnableFullPeerDb() ? yield from $this->getFullInfo($id) : yield from $this->getInfo($id);
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
                    $res['photo'] = $full['full']['profile_photo'];
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
                    $res['photo'] = $full['full']['chat_photo'];
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
                    $res['photo'] = $full['full']['chat_photo'];
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
                $newres['user'] = (yield from $this->getPwrChat($participant['user_id'] ?? $participant['peer'], false, true));
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = (yield from $this->getPwrChat($participant['inviter_id'], false, true));
                }
                if (isset($participant['promoted_by'])) {
                    $newres['promoted_by'] = (yield from $this->getPwrChat($participant['promoted_by'], false, true));
                }
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = (yield from $this->getPwrChat($participant['kicked_by'], false, true));
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
        if (!isset($res['participants']) && $fullfetch && \in_array($res['type'], ['supergroup', 'channel'])) {
            $total_count = (isset($res['participants_count']) ? $res['participants_count'] : 0) + (isset($res['admins_count']) ? $res['admins_count'] : 0) + (isset($res['kicked_count']) ? $res['kicked_count'] : 0) + (isset($res['banned_count']) ? $res['banned_count'] : 0);
            $res['participants'] = [];
            $filters = ['channelParticipantsAdmins', 'channelParticipantsBots'];
            $promises = [];
            foreach ($filters as $filter) {
                $promises []= $this->fetchParticipants($full['InputChannel'], $filter, '', $total_count, $res);
            }
            yield Tools::all($promises);

            $q = '';
            $filters = ['channelParticipantsSearch', 'channelParticipantsKicked', 'channelParticipantsBanned'];
            $promises = [];
            foreach ($filters as $filter) {
                $promises []= $this->recurseAlphabetSearchParticipants($full['InputChannel'], $filter, $q, $total_count, $res, 0);
            }
            yield Tools::all($promises);

            $this->logger->logger('Fetched '.\count($res['participants'])." out of {$total_count}");
            $res['participants'] = \array_values($res['participants']);
        }
        if (!$fullfetch) {
            unset($res['participants']);
        }
        if ($fullfetch || $send) {
            $this->storeDb($res);
        }
        if (isset($res['photo'])) {
            $photo = [];
            foreach ([
                'small' => $res['photo']['sizes'][0],
                'big' => Tools::maxSize($res['photo']['sizes']),
            ] as $type => $size) {
                $fileId = new FileId;
                $fileId->setId($res['photo']['id']);
                $fileId->setAccessHash($res['photo']['access_hash']);
                $fileId->setFileReference($res['photo']['file_reference']);
                $fileId->setDcId($res['photo']['dc_id']);
                $fileId->setType(PROFILE_PHOTO);

                $photoSize = new PhotoSizeSourceDialogPhoto($type === 'small' ? PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL : PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG);
                $photoSize->setDialogId($res['id']);
                $photoSize->setDialogAccessHash($res['access_hash'] ?? 0);

                $fileId->setPhotoSizeSource($photoSize);

                $photo[$type.'_file_id'] = (string) $fileId;
                $photo[$type.'_file_unique_id'] = $fileId->getUniqueBotAPI();
            }
            $res['photo'] += $photo;
        }
        return $res;
    }
    private function recurseAlphabetSearchParticipants($channel, $filter, $q, $total_count, &$res, int $depth): \Generator
    {
        if (!(yield from $this->fetchParticipants($channel, $filter, $q, $total_count, $res))) {
            return [];
        }
        $promises = [];
        for ($x = 'a'; $x !== 'aa' && $total_count > \count($res['participants']); $x++) {
            $promises []= $this->recurseAlphabetSearchParticipants($channel, $filter, $q.$x, $total_count, $res, $depth + 1);
        }

        if ($depth > 2) {
            return $promises;
        }

        $yielded = [...yield Tools::all($promises)];
        while ($yielded) {
            $newYielded = [];

            foreach (\array_chunk($yielded, 10) as $promises) {
                $newYielded = \array_merge($newYielded, ...(yield Tools::all($promises)));
            }

            $yielded = $newYielded;
        }

        return [];
    }
    private function fetchParticipants($channel, $filter, $q, $total_count, &$res): \Generator
    {
        $offset = 0;
        $limit = 200;
        $has_more = false;
        $cached = false;
        $last_count = -1;
        do {
            try {
                $gres = yield from $this->methodCallAsyncRead('channels.getParticipants', ['channel' => $channel, 'filter' => ['_' => $filter, 'q' => $q], 'offset' => $offset, 'limit' => $limit, 'hash' => $hash = yield from $this->getParticipantsHash($channel, $filter, $q, $offset, $limit)], ['datacenter' => $this->datacenter->curdc, 'heavy' => true]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->rpc === 'CHAT_ADMIN_REQUIRED') {
                    $this->logger->logger($e->rpc);
                    return $has_more;
                }
                throw $e;
            }
            if ($cached = ($gres['_'] === 'channels.channelParticipantsNotModified')) {
                $gres = yield $this->fetchParticipantsCache($channel, $filter, $q, $offset, $limit);
            } else {
                $this->storeParticipantsCache($gres, $channel, $filter, $q, $offset, $limit);
            }
            if ($last_count !== -1 && $last_count !== $gres['count']) {
                $has_more = true;
            } else {
                $last_count = $gres['count'];
            }
            $promises = [];
            foreach ($gres['participants'] as $participant) {
                $promises []= Tools::call((function () use (&$res, $participant) {
                    $newres = [];
                    $newres['user'] = (yield from $this->getPwrChat($participant['user_id'] ?? $participant['peer'], false, true));
                    if (isset($participant['inviter_id'])) {
                        $newres['inviter'] = (yield from $this->getPwrChat($participant['inviter_id'], false, true));
                    }
                    if (isset($participant['kicked_by'])) {
                        $newres['kicked_by'] = (yield from $this->getPwrChat($participant['kicked_by'], false, true));
                    }
                    if (isset($participant['promoted_by'])) {
                        $newres['promoted_by'] = (yield from $this->getPwrChat($participant['promoted_by'], false, true));
                    }
                    if (isset($participant['date'])) {
                        $newres['date'] = $participant['date'];
                    }
                    if (isset($participant['rank'])) {
                        $newres['rank'] = $participant['rank'];
                    }
                    if (isset($participant['admin_rights'])) {
                        $newres['admin_rights'] = $participant['admin_rights'];
                    }
                    if (isset($participant['banned_rights'])) {
                        $newres['banned_rights'] = $participant['banned_rights'];
                    }
                    switch ($participant['_']) {
                        case 'channelParticipantSelf':
                            $newres['role'] = 'user';
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
                    $res['participants'][$participant['user_id'] ?? $this->getId($participant['peer'])] = $newres;
                })());
            }
            yield Tools::all($promises);
            $this->logger->logger('Fetched '.\count($gres['participants'])." channel participants with filter {$filter}, query {$q}, offset {$offset}, limit {$limit}, hash {$hash}: ".($cached ? 'cached' : 'not cached').', '.($offset + \count($gres['participants'])).' participants out of '.$gres['count'].', in total fetched '.\count($res['participants']).' out of '.$total_count);
            $offset += \count($gres['participants']);
        } while (\count($gres['participants']));
        if ($offset === $limit) {
            return true;
        }
        return $has_more;
    }
    /**
     * Key for participatns cache.
     *
     * @param integer $channelId
     * @param string $filter
     * @param string $q
     * @param integer $offset
     * @param integer $limit
     * @return string
     */
    private function participantsKey(int $channelId, string $filter, string $q, int $offset, int $limit): string
    {
        return "$channelId'$filter'$q'$offset'$limit";
    }
    private function fetchParticipantsCache($channel, $filter, $q, $offset, $limit): Promise
    {
        return $this->channelParticipants[$this->participantsKey($channel['channel_id'], $filter, $q, $offset, $limit)];
    }
    private function storeParticipantsCache($gres, $channel, $filter, $q, $offset, $limit): void
    {
        unset($gres['users']);
        $ids = [];
        foreach ($gres['participants'] as $participant) {
            if (isset($participant['user_id'])) {
                $ids[] = $participant['user_id'];
            }
        }
        \sort($ids, SORT_NUMERIC);
        $gres['hash'] = \danog\MadelineProto\Tools::genVectorHash($ids);
        $this->channelParticipants[$this->participantsKey($channel['channel_id'], $filter, $q, $offset, $limit)] = $gres;
    }
    private function getParticipantsHash($channel, $filter, $q, $offset, $limit): \Generator
    {
        return (yield $this->channelParticipants[$this->participantsKey($channel['channel_id'], $filter, $q, $offset, $limit)])['hash'] ?? 0;
    }
    private function storeDb($res, $force = false): \Generator
    {
        if (!$this->settings->getPwr()->getDbToken() || $this->settings->getConnection()->getTestMode()) {
            return;
        }
        if (!empty($res)) {
            if (isset($res['participants'])) {
                unset($res['participants']);
            }
            $this->qres[] = $res;
        }
        if ($this->last_stored > \time() && !$force) {
            //$this->logger->logger("========== WILL SERIALIZE IN ".($this->last_stored - time())." =============");
            return false;
        }
        if (empty($this->qres)) {
            return false;
        }
        try {
            $payload = \json_encode($this->qres);
            //$path = '/tmp/ids'.hash('sha256', $payload);
            //file_put_contents($path, $payload);
            $id = isset($this->authorization['user']['username']) ? $this->authorization['user']['username'] : $this->authorization['user']['id'];
            $request = new Request('https://id.pwrtelegram.xyz/db'.$this->settings->getPwr()->getDbToken().'/addnewmadeline?d=pls&from='.$id, 'POST');
            $request->setHeader('content-type', 'application/json');
            $request->setBody($payload);
            $result = yield (yield $this->datacenter->getHTTPClient()->request($request))->getBody()->buffer();
            $this->logger->logger("============ {$result} =============", \danog\MadelineProto\Logger::VERBOSE);
            $this->qres = [];
            $this->last_stored = \time() + 10;
        } catch (\danog\MadelineProto\Exception $e) {
            $this->logger->logger('======= COULD NOT STORE IN DB DUE TO '.$e->getMessage().' =============', \danog\MadelineProto\Logger::VERBOSE);
        }
    }
    /**
     * Resolve username (use getInfo instead).
     *
     * @param string $username Username
     *
     * @return \Generator
     */
    public function resolveUsername(string $username): \Generator
    {
        $username = \str_replace('@', '', $username);
        if (!$username) {
            return false;
        }
        try {
            $this->caching_simple_username[$username] = true;
            $res = yield from $this->methodCallAsyncRead('contacts.resolveUsername', ['username' => $username]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->logger->logger('Username resolution failed with error '.$e->getMessage(), \danog\MadelineProto\Logger::ERROR);
            if (\strpos($e->rpc, 'FLOOD_WAIT_') === 0 || $e->rpc === 'AUTH_KEY_UNREGISTERED' || $e->rpc === 'USERNAME_INVALID') {
                throw $e;
            }
            return false;
        } finally {
            if (isset($this->caching_simple_username[$username])) {
                unset($this->caching_simple_username[$username]);
            }
        }
        if ($res['_'] === 'contacts.resolvedPeer') {
            foreach ($res['chats'] as $chat) {
                yield from $this->addChat($chat);
            }
            foreach ($res['users'] as $user) {
                yield from $this->addUser($user);
            }
            return $res;
        }
        return false;
    }
}
