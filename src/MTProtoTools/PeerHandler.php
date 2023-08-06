<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Sync\LocalMutex;
use AssertionError;
use danog\Decoder\FileId;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto;
use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecretPeerNotInDbException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use InvalidArgumentException;
use Throwable;
use Webmozart\Assert\Assert;

use const danog\Decoder\PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG;
use const danog\Decoder\PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL;

use const danog\Decoder\PROFILE_PHOTO;
use const SORT_NUMERIC;
use function Amp\async;
use function Amp\Future\await;

/**
 * Manages peers.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait PeerHandler
{
    /**
     * Convert MTProto channel ID to bot API channel ID.
     *
     * @param int $id MTProto channel ID
     */
    public static function toSupergroup(int $id): int
    {
        return Magic::ZERO_CHANNEL_ID - $id;
    }
    /**
     * Convert bot API channel ID to MTProto channel ID.
     *
     * @param int $id Bot API channel ID
     */
    public static function fromSupergroup(int $id): int
    {
        return (-$id) + Magic::ZERO_CHANNEL_ID;
    }
    /**
     * Check whether provided bot API ID is a channel or supergroup.
     *
     * @param int $id Bot API ID
     */
    public static function isSupergroup(int $id): bool
    {
        return $id < Magic::ZERO_CHANNEL_ID;
    }
    /**
     * Set support info.
     *
     * @internal
     *
     * @param array $support Support info
     * @internal
     */
    public function addSupport(array $support): void
    {
        $this->supportUser = $support['user']['id'];
    }

    /**
     * Check if the specified peer is a forum.
     *
     */
    public function isForum(mixed $peer): bool
    {
        return $this->getInfo($peer, \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR)['forum'] ?? false;
    }
    /**
     * Add user info.
     *
     * @internal
     *
     * @param array $user User info
     */
    public function addUser(array $user): void
    {
        if ($user['_'] === 'userEmpty') {
            return;
        }
        if ($user['_'] !== 'user') {
            throw new AssertionError('Invalid user type '.$user['_']);
        }
        if ($user['id'] === ($this->authorization['user']['id'] ?? null)) {
            $this->authorization['user'] = $user;
        }
        $existingChat = $this->chats[$user['id']];
        if (!isset($user['access_hash']) && !($user['min'] ?? false)) {
            if (isset($existingChat['access_hash'])) {
                $this->logger->logger("No access hash with user {$user['id']}, using backup");
                $user['access_hash'] = $existingChat['access_hash'];
                $user['min'] = false;
            } else {
                Assert::null($existingChat);
                try {
                    $this->methodCallAsyncRead('users.getUsers', ['id' => [[
                        '_' => 'inputUser',
                        'user_id' => $user['id'],
                        'access_hash' => 0,
                    ]]]);
                    return;
                } catch (RPCErrorException $e) {
                    $this->logger->logger("An error occurred while trying to fetch the missing access_hash for user {$user['id']}: {$e->getMessage()}", Logger::FATAL_ERROR);
                }
                foreach ($this->getUsernames($user) as $username) {
                    if (($this->resolveUsername($username)) === $user['id']) {
                        return;
                    }
                }
                return;
            }
        }
        if ($existingChat !== $user) {
            $this->logger->logger("Updated user {$user['id']}", Logger::ULTRA_VERBOSE);
            if (($user['min'] ?? false) && !($existingChat['min'] ?? false)) {
                $this->logger->logger("{$user['id']} is min, filling missing fields", Logger::ULTRA_VERBOSE);
                if (isset($existingChat['access_hash'])) {
                    $user['min'] = false;
                    $user['access_hash'] = $existingChat['access_hash'];
                }
            }
            $this->recacheChatUsername($user['id'], $existingChat, $user);
            if (!$this->getSettings()->getDb()->getEnablePeerInfoDb()) {
                $user = [
                    '_' => $user['_'],
                    'id' => $user['id'],
                    'access_hash' => $user['access_hash'] ?? null,
                    'min' => $user['min'] ?? false,
                ];
            }
            $this->chats->set($user['id'], $user);
            if (!($user['min'] ?? false)) {
                $this->minDatabase->clearPeer($user['id']);
            }
        }
    }
    /**
     * Add chat to database.
     *
     * @param array $chat Chat
     * @internal
     */
    public function addChat(array $chat): void
    {
        if ($chat['_'] === 'chat'
            || $chat['_'] === 'chatEmpty'
            || $chat['_'] === 'chatForbidden'
        ) {
            $existingChat = $this->chats[-$chat['id']];
            if (!$existingChat || $existingChat !== $chat) {
                $this->logger->logger("Updated chat -{$chat['id']}", Logger::ULTRA_VERBOSE);
                if (!$this->getSettings()->getDb()->getEnablePeerInfoDb()) {
                    $chat = [
                        '_' => $chat['_'],
                        'id' => $chat['id'],
                        'access_hash' => $chat['access_hash'] ?? null,
                        'min' => $chat['min'] ?? false,
                    ];
                }
                $this->chats->set(-$chat['id'], $chat);
            }
            return;
        }
        if ($chat['_'] === 'channelEmpty') {
            return;
        }
        if ($chat['_'] !== 'channel' && $chat['_'] !== 'channelForbidden') {
            throw new InvalidArgumentException('Invalid chat type '.$chat['_']);
        }
        $bot_api_id = $this->toSupergroup($chat['id']);
        $existingChat = $this->chats[$bot_api_id];
        if (!isset($chat['access_hash']) && !($chat['min'] ?? false)) {
            if (isset($existingChat['access_hash'])) {
                $this->logger->logger("No access hash with channel {$bot_api_id}, using backup");
                $chat['access_hash'] = $existingChat['access_hash'];
            } else {
                Assert::null($existingChat);
                try {
                    $this->methodCallAsyncRead('channels.getChannels', ['id' => [[
                        '_' => 'inputChannel',
                        'channel_id' => $chat['id'],
                        'access_hash' => 0,
                    ]]]);
                    return;
                } catch (RPCErrorException $e) {
                    $this->logger->logger("An error occurred while trying to fetch the missing access_hash for channel {$bot_api_id}: {$e->getMessage()}", Logger::FATAL_ERROR);
                }
                foreach ($this->getUsernames($chat) as $username) {
                    if (($this->resolveUsername($username)) === $bot_api_id) {
                        return;
                    }
                }
                return;
            }
        }
        if ($existingChat !== $chat) {
            $this->recacheChatUsername($bot_api_id, $existingChat, $chat);
            $this->logger->logger("Updated chat {$bot_api_id}", Logger::ULTRA_VERBOSE);
            if (($chat['min'] ?? false) && $existingChat && !($existingChat['min'] ?? false)) {
                $this->logger->logger("{$bot_api_id} is min, filling missing fields", Logger::ULTRA_VERBOSE);
                $newchat = $existingChat;
                foreach (['title', 'username', 'usernames', 'photo', 'banned_rights', 'megagroup', 'verified'] as $field) {
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
            $this->chats->set($bot_api_id, $chat);
            if (!($chat['min'] ?? false)) {
                $this->minDatabase->clearPeer($bot_api_id);
            }
        }
    }

    private ?LocalMutex $decacheMutex = null;
    private function recacheChatUsername(int $id, ?array $old, array $new): void
    {
        if (!$this->getSettings()->getDb()->getEnableUsernameDb()) {
            return;
        }
        $new = $this->getUsernames($new);
        $old = $old ? $this->getUsernames($old) : [];
        $diffToRemove = \array_diff($old, $new);
        $diffToAdd = \array_diff($new, $old);
        if (!$diffToAdd && !$diffToRemove) {
            return;
        }
        $this->decacheMutex ??= new LocalMutex;
        $lock = $this->decacheMutex->acquire();
        try {
            foreach ($diffToRemove as $username) {
                if ($this->usernames[$username] === $id) {
                    unset($this->usernames[$username]);
                }
            }
            foreach ($diffToAdd as $username) {
                $this->usernames[$username] = $id;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @return list<lowercase-string>
     */
    private static function getUsernames(array $constructor): array
    {
        $usernames = [];
        if ($constructor['_'] === 'user' || $constructor['_'] === 'channel') {
            if (isset($constructor['username'])) {
                $usernames []= \strtolower($constructor['username']);
            }
            foreach ($constructor['usernames'] ?? [] as ['username' => $username]) {
                $usernames []= \strtolower($username);
            }
        }
        return $usernames;
    }

    /**
     * Check if peer is present in internal peer database.
     *
     * @param mixed $id Peer
     */
    public function peerIsset(mixed $id): bool
    {
        try {
            return isset($this->chats[$this->getIdInternal($id)]);
        } catch (Exception $e) {
            return false;
        } catch (RPCErrorException $e) {
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
     * @internal
     */
    public function entitiesPeerIsset(array $entities): bool
    {
        try {
            foreach ($entities as $entity) {
                if ($entity['_'] === 'messageEntityMentionName' || $entity['_'] === 'inputMessageEntityMentionName') {
                    if (!($this->peerIsset($entity['user_id']))) {
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    /**
     * Check if fwd peer is set.
     *
     * @param array $fwd Forward info
     * @internal
     */
    public function fwdPeerIsset(array $fwd)
    {
        try {
            if (isset($fwd['user_id']) && !($this->peerIsset($fwd['user_id']))) {
                return false;
            }
            if (isset($fwd['channel_id']) && !($this->peerIsset($this->toSupergroup($fwd['channel_id'])))) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    /**
     * Get folder ID from object.
     *
     * @param mixed $id Object
     */
    public static function getFolderId(mixed $id): ?int
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
     * Get the bot API ID of a peer.
     *
     * @param mixed $id Peer
     */
    public function getId(mixed $id): int
    {
        return $this->getInfo($id, \danog\MadelineProto\API::INFO_TYPE_ID);
    }

    /**
     * Get bot API ID from peer object.
     *
     * @internal
     *
     * @param mixed $id Peer
     */
    public function getIdInternal(mixed $id): ?int
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
                    return $this->getIdInternal($id['peer']);
                case 'inputUserFromMessage':
                case 'inputPeerUserFromMessage':
                    return $id['user_id'];
                case 'inputChannelFromMessage':
                case 'inputPeerChannelFromMessage':
                case 'updateChannelParticipant':
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
                        return $this->getIdInternal($id['peer_id']);
                    }
                    return $this->getIdInternal($id['from_id']);
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
                case 'updateUser':
                case 'contact':
                    return $id['user_id'];
                case 'updatePhoneCall':
                    return $id['phone_call']->getOtherID();
                case 'updateReadHistoryInbox':
                case 'updateReadHistoryOutbox':
                    return $this->getIdInternal($id['peer']);
                case 'updateNewMessage':
                case 'updateNewChannelMessage':
                case 'updateEditMessage':
                case 'updateEditChannelMessage':
                    return $this->getIdInternal($id['message']);
                default:
                    throw new Exception('Invalid constructor given '.$id['_']);
            }
        }
        if (\is_string($id)) {
            if (\strpos($id, '#') !== false) {
                if (\preg_match('/^channel#(\\d*)/', $id, $matches)) {
                    return $this->toSupergroup((int) $matches[1]);
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
        return null;
    }
    /**
     * Get InputPeer object.
     *
     * @internal
     * @param mixed $id Peer
     */
    public function getInputPeer(mixed $id)
    {
        return $this->getInfo($id, \danog\MadelineProto\API::INFO_TYPE_PEER);
    }
    /**
     * Get InputUser/InputChannel object.
     *
     * @internal
     * @param mixed $id Peer
     */
    public function getInputConstructor(mixed $id)
    {
        return $this->getInfo($id, \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR);
    }

    public array $caching_full_info = [];

    /**
     * Get type of peer.
     *
     * @param mixed $id Peer
     *
     * @return \danog\MadelineProto\API::PEER_TYPE_*
     */
    public function getType(mixed $id): string
    {
        return $this->getInfo($id, API::INFO_TYPE_TYPE);
    }

    /**
     * Get info about peer, returns an Info object.
     *
     * @param mixed                $id        Peer
     * @param \danog\MadelineProto\API::INFO_TYPE_* $type      Whether to generate an Input*, an InputPeer or the full set of constructors
     * @see https://docs.madelineproto.xyz/Info.html
     * @return ($type is \danog\MadelineProto\API::INFO_TYPE_ALL ? array{
     *      InputPeer: array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int},
     *      Peer: array{_: string, user_id?: int, chat_id?: int, channel_id?: int},
     *      DialogPeer: array{_: string, peer: array{_: string, user_id?: int, chat_id?: int, channel_id?: int}},
     *      NotifyPeer: array{_: string, peer: array{_: string, user_id?: int, chat_id?: int, channel_id?: int}},
     *      InputDialogPeer: array{_: string, peer: array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int}},
     *      InputNotifyPeer: array{_: string, peer: array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int}},
     *      bot_api_id: int|string,
     *      user_id?: int,
     *      chat_id?: int,
     *      channel_id?: int,
     *      InputUser?: array{_: string, user_id?: int, access_hash?: int, min?: bool},
     *      InputChannel?: array{_: string, channel_id: int, access_hash: int, min: bool},
     *      type: string
     * } : ($type is API::INFO_TYPE_TYPE ? string : ($type is \danog\MadelineProto\API::INFO_TYPE_ID ? int : array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int}|array{_: string, user_id?: int, access_hash?: int, min?: bool}|array{_: string, channel_id: int, access_hash: int, min: bool})))
     */
    public function getInfo(mixed $id, int $type = \danog\MadelineProto\API::INFO_TYPE_ALL): array|int|string
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
                        throw new SecretPeerNotInDbException;
                    }
                    return $this->secret_chats[$id];
            }
        }
        $folder_id = $this->getFolderId($id);
        $try_id = $this->getIdInternal($id);
        if ($try_id !== null) {
            $id = $try_id;
        }
        if (\is_numeric($id)) {
            $id = (int) $id;
            Assert::true($id !== 0, "An invalid ID was specified!");
            if (!$this->chats[$id]) {
                try {
                    $this->logger->logger("Try fetching {$id} with access hash 0");
                    if ($this->isSupergroup($id)) {
                        $this->addChat([
                            '_' => 'channel',
                            'id' => $this->fromSupergroup($id),
                        ]);
                    } elseif ($id < 0) {
                        $this->methodCallAsyncRead('messages.getChats', ['id' => [-$id]]);
                    } else {
                        $this->addUser([
                            '_' => 'user',
                            'id' => $id,
                        ]);
                    }
                } catch (Exception $e) {
                    $this->logger->logger($e->getMessage(), Logger::WARNING);
                } catch (RPCErrorException $e) {
                    $this->logger->logger($e->getMessage(), Logger::WARNING);
                }
            }
            $chat = $this->chats[$id];
            if (!$chat) {
                if ($this->cacheFullDialogs()) {
                    return $this->getInfo($id, $type);
                }
                throw new PeerNotInDbException();
            }
            if (($chat['min'] ?? false)
                && $this->minDatabase->hasPeer($id)
                && !isset($this->caching_full_info[$id])
            ) {
                $this->caching_full_info[$id] = true;
                $this->logger->logger("Only have min peer for {$id} in database, trying to fetch full info");
                try {
                    if ($id < 0) {
                        $this->methodCallAsyncRead('channels.getChannels', ['id' => [$this->genAll($chat, $folder_id, \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR)]]);
                    } else {
                        $this->methodCallAsyncRead('users.getUsers', ['id' => [$this->genAll($chat, $folder_id, \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR)]]);
                    }
                } catch (Exception $e) {
                    $this->logger->logger($e->getMessage(), Logger::WARNING);
                } catch (RPCErrorException $e) {
                    $this->logger->logger($e->getMessage(), Logger::WARNING);
                } finally {
                    unset($this->caching_full_info[$id]);
                }
            }
            try {
                return $this->genAll($chat, $folder_id, $type);
            } catch (PeerNotInDbException $e) {
                unset($this->chats[$id]);
                throw $e;
            }
        }
        if ($r = Tools::parseLink($id)) {
            [$invite, $content] = $r;
            if ($invite) {
                $invite = $this->methodCallAsyncRead('messages.checkChatInvite', ['hash' => $content]);
                if (isset($invite['chat'])) {
                    return $this->getInfo($invite['chat'], $type);
                }
                throw new Exception('You have not joined this chat');
            } else {
                $id = $content;
            }
        }
        $id = \strtolower(\str_replace('@', '', $id));
        if ($id === 'me') {
            return $this->getInfo($this->authorization['user']['id'], $type);
        }
        if ($id === 'support') {
            if (!$this->supportUser) {
                $this->methodCallAsyncRead('help.getSupport', []);
            }
            return $this->getInfo($this->supportUser, $type);
        }
        if ($bot_api_id = $this->usernames[$id]) {
            return $this->getInfo($bot_api_id, $type);
        }
        if ($bot_api_id = $this->resolveUsername($id)) {
            return $this->getInfo($bot_api_id, $type);
        }
        if ($this->cacheFullDialogs()) {
            return $this->getInfo($id, $type);
        }
        throw new PeerNotInDbException();
    }
    /**
     * @param array $constructor
     * @param \danog\MadelineProto\API::INFO_TYPE_* $type
     * @return ($type is \danog\MadelineProto\API::INFO_TYPE_ALL ? (array{
     *      InputPeer: array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int},
     *      Peer: array{_: string, user_id?: int, chat_id?: int, channel_id?: int},
     *      DialogPeer: array{_: string, peer: array{_: string, user_id?: int, chat_id?: int, channel_id?: int}},
     *      NotifyPeer: array{_: string, peer: array{_: string, user_id?: int, chat_id?: int, channel_id?: int}},
     *      InputDialogPeer: array{_: string, peer: array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int}},
     *      InputNotifyPeer: array{_: string, peer: array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int}},
     *      bot_api_id: int,
     *      user_id?: int,
     *      chat_id?: int,
     *      channel_id?: int,
     *      InputUser?: array{_: string, user_id?: int, access_hash?: int, min?: bool},
     *      InputChannel?: array{_: string, channel_id: int, access_hash: int, min: bool},
     *      type: string
     * }&array) : array|int)
     */
    private function genAll($constructor, $folder_id, int $type): array|int|string
    {
        if ($type === \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR) {
            if ($constructor['_'] === 'user') {
                return ($constructor['self'] ?? false) ? ['_' => 'inputUserSelf'] : ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
            if ($constructor['_'] === 'channel') {
                return ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
        }
        if ($type === \danog\MadelineProto\API::INFO_TYPE_PEER) {
            if ($constructor['_'] === 'user') {
                return ($constructor['self'] ?? false) ? ['_' => 'inputPeerSelf'] : ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
            if ($constructor['_'] === 'channel') {
                return ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
            if ($constructor['_'] === 'chat' || $constructor['_'] === 'chatForbidden') {
                return ['_' => 'inputPeerChat', 'chat_id' => $constructor['id']];
            }
        }
        if ($type === \danog\MadelineProto\API::INFO_TYPE_ID) {
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
        if ($type === \danog\MadelineProto\API::INFO_TYPE_TYPE) {
            if ($constructor['_'] === 'user') {
                return $constructor['bot'] ?? false ? 'bot' : 'user';
            }
            if ($constructor['_'] === 'channel') {
                return $constructor['megagroup'] ?? false ? 'supergroup' : 'channel';
            }
            if ($constructor['_'] === 'chat' || $constructor['_'] === 'chatForbidden') {
                return 'chat';
            }
        }
        if ($type === \danog\MadelineProto\API::INFO_TYPE_USERNAMES) {
            return $this->getUsernames($constructor);
        }
        $res = [$this->TL->getConstructors()->findByPredicate($constructor['_'])['type'] => $constructor];
        switch ($constructor['_']) {
            case 'user':
                if ($constructor['self'] ?? false) {
                    $res['InputPeer'] = ['_' => 'inputPeerSelf'];
                    $res['InputUser'] = ['_' => 'inputUserSelf'];
                } elseif (isset($constructor['access_hash'])) {
                    $res['InputPeer'] = ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
                    $res['InputUser'] = ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
                } else {
                    $this->cacheFullDialogs();
                    throw new PeerNotInDbException();
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
                    $this->cacheFullDialogs();
                    throw new PeerNotInDbException();
                }
                $res['InputPeer'] = ['_' => 'inputPeerChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
                $res['Peer'] = ['_' => 'peerChannel', 'channel_id' => $constructor['id']];
                $res['DialogPeer'] = ['_' => 'dialogPeer', 'peer' => $res['Peer']];
                $res['NotifyPeer'] = ['_' => 'notifyPeer', 'peer' => $res['Peer']];
                $res['InputDialogPeer'] = ['_' => 'inputDialogPeer', 'peer' => $res['InputPeer']];
                $res['InputNotifyPeer'] = ['_' => 'inputNotifyPeer', 'peer' => $res['InputPeer']];
                $res['InputChannel'] = ['_' => 'inputChannel', 'channel_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
                $res['channel_id'] = $constructor['id'];
                $res['bot_api_id'] = $this->toSupergroup($constructor['id']);
                $res['type'] = $constructor['megagroup'] ?? false ? 'supergroup' : 'channel';
                break;
            case 'channelForbidden':
                throw new PeerNotInDbException();
            default:
                throw new Exception('Invalid constructor given '.$constructor['_']);
        }
        if ($folder_id) {
            $res['FolderPeer'] = ['_' => 'folderPeer', 'peer' => $res['Peer'], 'folder_id' => $folder_id];
            $res['InputFolderPeer'] = ['_' => 'inputFolderPeer', 'peer' => $res['InputPeer'], 'folder_id' => $folder_id];
        }
        return $res;
    }
    /**
     * Refresh peer cache for a certain peer.
     *
     */
    public function refreshPeerCache(mixed ...$ids): void
    {
        $users = [];
        $chats = [];
        $supergroups = [];
        foreach ($ids as $id) {
            if (\is_string($id)) {
                if ($r = Tools::parseLink($id)) {
                    [$invite, $content] = $r;
                    if ($invite) {
                        $invite = $this->methodCallAsyncRead('messages.checkChatInvite', ['hash' => $content]);
                        if (isset($invite['chat'])) {
                            continue;
                        }
                        throw new Exception('You have not joined this chat');
                    } else {
                        $id = $content;
                    }
                }
                $id = \strtolower(\str_replace('@', '', $id));
                if ($id === 'me') {
                    $id = $this->authorization['user']['id'];
                } elseif ($id === 'support') {
                    $this->methodCallAsyncRead('help.getSupport', []);
                    continue;
                } else {
                    $id = $this->resolveUsername($id);
                }
            }
            $id = $this->getIdInternal($id);
            Assert::notNull($id);
            if (self::isSupergroup($id)) {
                $supergroups []= $id;
            } elseif ($id < 0) {
                $chats []= $id;
            } else {
                $users []= $id;
            }
        }
        if ($supergroups) {
            $this->methodCallAsyncRead('channels.getChannels', ['id' => $supergroups]);
        }
        if ($chats) {
            $this->methodCallAsyncRead('messages.getChats', ['id' => $chats]);
        }
        if ($users) {
            $this->methodCallAsyncRead('users.getUsers', ['id' => $users]);
        }
    }
    /**
     * Refresh full peer cache for a certain peer.
     *
     * @param mixed $id The peer to refresh
     */
    public function refreshFullPeerCache(mixed $id): void
    {
        if (\is_string($id)) {
            if ($r = Tools::parseLink($id)) {
                [$invite, $content] = $r;
                if ($invite) {
                    $invite = $this->methodCallAsyncRead('messages.checkChatInvite', ['hash' => $content]);
                    if (!isset($invite['chat'])) {
                        throw new Exception('You have not joined this chat');
                    }
                    $id = $invite['chat'];
                } else {
                    $id = $content;
                }
            }
            $id = \strtolower(\str_replace('@', '', $id));
            if ($id === 'me') {
                $id = $this->authorization['user']['id'];
            } elseif ($id === 'support') {
                $id = $this->methodCallAsyncRead('help.getSupport', [])['user'];
            } else {
                $id = $this->resolveUsername($id);
            }
        }
        unset($this->full_chats[$this->getIdInternal($id)]);
        $this->getFullInfo($id);
    }
    /**
     * When were full info for this chat last cached.
     *
     * @param mixed $id Chat ID
     */
    public function fullChatLastUpdated(mixed $id): int
    {
        return ($this->full_chats[$id])['last_update'] ?? 0;
    }
    /**
     * Get full info about peer, returns an FullInfo object.
     *
     * @param mixed $id Peer
     * @see https://docs.madelineproto.xyz/FullInfo.html
     */
    public function getFullInfo(mixed $id): array
    {
        $partial = $this->getInfo($id);
        if (\time() - ($this->fullChatLastUpdated($partial['bot_api_id'])) < $this->getSettings()->getPeer()->getFullInfoCacheTime()) {
            return \array_merge($partial, $this->full_chats[$partial['bot_api_id']]);
        }
        $full = null;
        switch ($partial['type']) {
            case 'user':
            case 'bot':
                $this->methodCallAsyncRead('users.getFullUser', ['id' => $partial['InputUser']]);
                break;
            case 'chat':
                $this->methodCallAsyncRead('messages.getFullChat', $partial);
                break;
            case 'channel':
            case 'supergroup':
                $this->methodCallAsyncRead('channels.getFullChannel', ['channel' => $partial['InputChannel']]);
                break;
        }
        return \array_merge($partial, $this->full_chats[$partial['bot_api_id']]);
    }
    /**
     * @internal
     */
    public function addFullChat(array $full): void
    {
        $this->full_chats[$this->getIdInternal($full)] = [
            'full' => $full,
            'last_update' => \time(),
        ];
    }
    /**
     * Get full info about peer (including full list of channel members), returns a Chat object.
     *
     * @param mixed $id Peer
     * @see https://docs.madelineproto.xyz/Chat.html
     */
    public function getPwrChat(mixed $id, bool $fullfetch = true): array
    {
        try {
            $full = $fullfetch && $this->getSettings()->getDb()->getEnableFullPeerDb() ? $this->getFullInfo($id) : $this->getInfo($id);
        } catch (Throwable) {
            $full = $this->getInfo($id);
        }
        $res = ['id' => $full['bot_api_id'], 'type' => $full['type']];
        switch ($full['type']) {
            case 'user':
            case 'bot':
                foreach (['first_name', 'last_name', 'username', 'usernames', 'verified', 'restricted', 'restriction_reason', 'status', 'bot_inline_placeholder', 'access_hash', 'phone', 'lang_code', 'bot_nochats'] as $key) {
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
                foreach (['title', 'democracy', 'restricted', 'restriction_reason', 'access_hash', 'username', 'usernames', 'signatures'] as $key) {
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
                $newres['user'] = ($this->getPwrChat($participant['user_id'] ?? $participant['peer'], false));
                if (isset($participant['inviter_id'])) {
                    $newres['inviter'] = ($this->getPwrChat($participant['inviter_id'], false));
                }
                if (isset($participant['promoted_by'])) {
                    $newres['promoted_by'] = ($this->getPwrChat($participant['promoted_by'], false));
                }
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = ($this->getPwrChat($participant['kicked_by'], false));
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
        if (!isset($res['participants']) && $fullfetch && \in_array($res['type'], ['supergroup', 'channel'], true)) {
            $total_count = ($res['participants_count'] ?? 0) + ($res['admins_count'] ?? 0) + ($res['kicked_count'] ?? 0) + ($res['banned_count'] ?? 0);
            $res['participants'] = [];
            $filters = ['channelParticipantsAdmins', 'channelParticipantsBots'];
            $promises = [];
            foreach ($filters as $filter) {
                $promises []= async(function () use ($full, $filter, $total_count, &$res): void {
                    $this->fetchParticipants($full['InputChannel'], $filter, '', $total_count, $res);
                });
            }
            await($promises);

            $q = '';
            $filters = ['channelParticipantsSearch', 'channelParticipantsKicked', 'channelParticipantsBanned'];
            $promises = [];
            foreach ($filters as $filter) {
                $promises []= async(function () use ($full, $filter, $q, $total_count, &$res): void {
                    $this->recurseAlphabetSearchParticipants($full['InputChannel'], $filter, $q, $total_count, $res, 0);
                });
            }
            await($promises);

            $this->logger->logger('Fetched '.\count($res['participants'])." out of {$total_count}");
            $res['participants'] = \array_values($res['participants']);
        }
        if (!$fullfetch) {
            unset($res['participants']);
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
                $fileId->setFileReference((string) $res['photo']['file_reference']);
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
    private function recurseAlphabetSearchParticipants($channel, $filter, $q, $total_count, &$res, int $depth)
    {
        if (!($this->fetchParticipants($channel, $filter, $q, $total_count, $res))) {
            return [];
        }
        ++$depth;
        $promises = [];
        for ($x = 'a'; $x !== 'aa' && $total_count > \count($res['participants']); $x++) {
            $promises []= async(function () use ($channel, $filter, $q, $x, $total_count, &$res, $depth) {
                return $this->recurseAlphabetSearchParticipants($channel, $filter, $q.$x, $total_count, $res, $depth);
            });
        }

        if ($depth > 3) {
            return $promises;
        }

        $yielded = \array_merge(...await($promises));
        while ($yielded) {
            $newYielded = [];

            foreach (\array_chunk($yielded, 10) as $promises) {
                $newYielded = \array_merge($newYielded, ...(await($promises)));
            }

            $yielded = $newYielded;
        }

        return [];
    }
    private function fetchParticipants($channel, $filter, $q, $total_count, &$res)
    {
        $offset = 0;
        $limit = 200;
        $has_more = false;
        $cached = false;
        $last_count = -1;
        do {
            try {
                $gres = $this->methodCallAsyncRead('channels.getParticipants', ['channel' => $channel, 'filter' => ['_' => $filter, 'q' => $q], 'offset' => $offset, 'limit' => $limit, 'hash' => $hash = $this->getParticipantsHash($channel, $filter, $q, $offset, $limit)], ['heavy' => true, 'FloodWaitLimit' => 86400]);
            } catch (RPCErrorException $e) {
                if ($e->rpc === 'CHAT_ADMIN_REQUIRED') {
                    $this->logger->logger($e->rpc);
                    return $has_more;
                }
                throw $e;
            }
            if ($cached = ($gres['_'] === 'channels.channelParticipantsNotModified')) {
                $gres = $this->fetchParticipantsCache($channel, $filter, $q, $offset, $limit);
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
                $promises []= async(function () use (&$res, $participant): void {
                    $newres = [];
                    $newres['user'] = ($this->getPwrChat($participant['user_id'] ?? $participant['peer'], false));
                    if (isset($participant['inviter_id'])) {
                        $newres['inviter'] = ($this->getPwrChat($participant['inviter_id'], false));
                    }
                    if (isset($participant['kicked_by'])) {
                        $newres['kicked_by'] = ($this->getPwrChat($participant['kicked_by'], false));
                    }
                    if (isset($participant['promoted_by'])) {
                        $newres['promoted_by'] = ($this->getPwrChat($participant['promoted_by'], false));
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
                    $res['participants'][$participant['user_id'] ?? $this->getIdInternal($participant['peer'])] = $newres;
                });
            }
            await($promises);
            $h = $hash ? 'present' : 'absent';
            $this->logger->logger('Fetched '.\count($gres['participants'])." channel participants with filter {$filter}, query {$q}, offset {$offset}, limit {$limit}, hash {$h}: ".($cached ? 'cached' : 'not cached').', '.($offset + \count($gres['participants'])).' participants out of '.$gres['count'].', in total fetched '.\count($res['participants']).' out of '.$total_count);
            $offset += \count($gres['participants']);
        } while (\count($gres['participants']));
        if ($offset === $limit) {
            return true;
        }
        return $has_more;
    }
    /**
     * Key for participatns cache.
     */
    private static function participantsKey(int $channelId, string $filter, string $q, int $offset, int $limit): string
    {
        return "$channelId'$filter'$q'$offset'$limit";
    }
    private function fetchParticipantsCache($channel, $filter, $q, $offset, $limit)
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
        $gres['hash'] = Tools::genVectorHash($ids);
        $this->channelParticipants[$this->participantsKey($channel['channel_id'], $filter, $q, $offset, $limit)] = $gres;
    }
    private function getParticipantsHash($channel, $filter, $q, $offset, $limit)
    {
        return ($this->channelParticipants[$this->participantsKey($channel['channel_id'], $filter, $q, $offset, $limit)])['hash'] ?? 0;
    }

    private array $caching_simple_username = [];
    /**
     * @param string $username Username
     */
    private function resolveUsername(string $username): ?int
    {
        $username = \strtolower(\str_replace('@', '', $username));
        if (!$username) {
            return null;
        }
        if (isset($this->caching_simple_username[$username])) {
            // Used to avoid possible issues from addUser
            return null;
        }
        $this->caching_simple_username[$username] = true;
        $result = null;
        try {
            $result = $this->getIdInternal(
                ($this->methodCallAsyncRead('contacts.resolveUsername', ['username' => $username]))['peer'],
            );
        } catch (FloodWaitError $e) {
            throw $e;
        } catch (RPCErrorException $e) {
            $this->logger->logger('Username resolution failed with error '.$e->getMessage(), Logger::ERROR);
            if ($e->rpc === 'AUTH_KEY_UNREGISTERED' || $e->rpc === 'USERNAME_INVALID') {
                throw $e;
            }
        } finally {
            unset($this->caching_simple_username[$username]);
        }
        return $result;
    }
}
