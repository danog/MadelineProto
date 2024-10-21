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

use AssertionError;
use danog\DialogId\DialogId;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\Message\Entities\InputMentionName;
use danog\MadelineProto\EventHandler\Message\Entities\MentionName;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\RPCError\ChannelPrivateError;
use danog\MadelineProto\RPCError\ChatAdminRequiredError;
use danog\MadelineProto\RPCError\ChatForbiddenError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use Throwable;
use Webmozart\Assert\Assert;

use const SORT_NUMERIC;
use function Amp\async;
use function Amp\Future\await;

/**
 * Manages peers.
 *
 * @property Settings     $settings     Settings
 * @property PeerDatabase $peerDatabase
 *
 * @internal
 */
trait PeerHandler
{
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
     * Check if the specified peer is a bot.
     *
     */
    public function isBot(mixed $peer): bool
    {
        return $this->getType($peer) === API::PEER_TYPE_BOT;
    }

    /**
     * Check if peer is present in internal peer database.
     *
     * @param mixed $id Peer
     */
    public function peerIsset(mixed $id): bool
    {
        try {
            return $this->peerDatabase->isset($this->getIdInternal($id));
        } catch (ChannelPrivateError|ChatForbiddenError) {
            return true;
        } catch (RPCErrorException|Exception) {
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
                if ($entity instanceof MentionName) {
                    if (!($this->peerIsset($entity->userId))) {
                        return false;
                    }
                } elseif ($entity instanceof InputMentionName) {
                    if (!($this->peerIsset($entity->userId))) {
                        return false;
                    }
                } elseif (\is_array($entity) && ($entity['_'] === 'messageEntityMentionName' || $entity['_'] === 'inputMessageEntityMentionName')) {
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
            if (isset($fwd['channel_id']) && !($this->peerIsset($fwd['channel_id']))) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get the bot API ID of a peer.
     *
     * @param mixed $id Peer
     */
    public function getId(mixed $id): int
    {
        if (\is_int($id)) {
            return $id;
        }
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
                case 'updateDraftMessage':
                case 'updateReadHistoryInbox':
                case 'updateReadHistoryOutbox':
                case 'updateChatDefaultBannedRights':
                case 'updateDeleteScheduledMessages':
                case 'updateSentStoryReaction':
                case 'updateBotCommands':
                case 'updateBotChatInviteRequester':
                case 'updatePendingJoinRequests':
                case 'updateStory':
                case 'updatePinnedMessages':
                case 'dialog':
                case 'dialogPeer':
                case 'notifyPeer':
                case 'help.proxyDataPromo':
                case 'folderPeer':
                case 'inputDialogPeer':
                case 'inputNotifyPeer':
                case 'inputFolderPeer':
                    return $this->getIdInternal($id['peer']);
                case 'inputUserSelf':
                case 'inputPeerSelf':
                    return $this->authorization['user']['id'];
                case 'user':
                case 'userFull':
                    return $id['id'];
                case 'messageActionChatJoinedByLink':
                    return $id['inviter_id'];
                case 'chat':
                case 'chatForbidden':
                case 'chatFull':
                    return $id['id'];
                case 'inputPeerChat':
                case 'peerChat':
                    return $id['chat_id'];
                case 'channelForbidden':
                case 'channel':
                case 'channelFull':
                    return $id['id'];
                case 'updatePeerBlocked':
                case 'message':
                case 'messageService':
                    if (!isset($id['from_id']) // No other option
                        // It's a channel/chat, 100% what we need
                        || $id['peer_id'] < 0
                        // It is a user, and it's not ourselves
                        || $id['peer_id'] !== $this->authorization['user']['id']
                    ) {
                        return $id['peer_id'];
                    }
                    return $id['from_id'];
                case 'peerChannel':
                case 'requestedPeerChannel':
                case 'inputChannel':
                case 'inputPeerChannel':
                case 'inputChannelFromMessage':
                case 'inputPeerChannelFromMessage':
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
                case 'updateChannelParticipant':
                case 'updatePinnedChannelMessages':
                case 'updateChannelMessageForwards':
                case 'updateChannelUserTyping':
                    return $id['channel_id'];
                case 'updateChatParticipants':
                    $id = $id['participants'];
                    // no break
                case 'updateChatUserTyping':
                case 'updateChatParticipantAdd':
                case 'updateChatParticipantDelete':
                case 'updateChatParticipantAdmin':
                case 'updateChatAdmins':
                case 'updateChatPinnedMessage':
                    return $id['chat_id'];
                case 'contact':
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
                case 'updateUserEmojiStatus':
                case 'updateBotStopped':
                case 'inputUserFromMessage':
                case 'inputPeerUserFromMessage':
                case 'inputPeerUser':
                case 'inputUser':
                case 'peerUser':
                case 'requestedPeerUser':
                case 'messageEntityMentionName':
                case 'messageActionChatDeleteUser':
                    return $id['user_id'];
                case 'updatePhoneCall':
                    return $id['phone_call']->getOtherID();
                case 'updateNewMessage':
                case 'updateNewChannelMessage':
                case 'updateEditMessage':
                case 'updateEditChannelMessage':
                    return $this->getIdInternal($id['message']);
                default:
                    throw new Exception('Invalid constructor given '.$id['_']);
            }
        }
        if (is_numeric($id)) {
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
     * If passed a secret chat ID, returns information about the user, not about the secret chat.
     * Use getSecretChat to return information about the secret chat.
     *
     * @param mixed                                 $id   Peer
     * @param \danog\MadelineProto\API::INFO_TYPE_* $type Whether to generate an Input*, an InputPeer or the full set of constructors
     * @see https://docs.madelineproto.xyz/Info.html
     * @return ($type is \danog\MadelineProto\API::INFO_TYPE_ALL ? array{
     *      User?: array,
     *      Chat?: array,
     *      bot_api_id: int,
     *      user_id?: int,
     *      chat_id?: int,
     *      channel_id?: int,
     *      type: string
     * } : ($type is API::INFO_TYPE_TYPE ? string : ($type is \danog\MadelineProto\API::INFO_TYPE_ID ? int : array{_: string, user_id?: int, access_hash?: int, min?: bool, chat_id?: int, channel_id?: int}|array{_: string, user_id?: int, access_hash?: int, min?: bool}|array{_: string, channel_id: int, access_hash: int, min: bool})))
     */
    public function getInfo(mixed $id, int $type = \danog\MadelineProto\API::INFO_TYPE_ALL): array|int|string
    {
        if (\is_array($id)) {
            switch ($id['_']) {
                case 'updateEncryption':
                    $id = $this->getSecretChat($id['chat']['id'])->otherID;
                    break;
                case 'updateNewEncryptedMessage':
                    $id = $id['message'];
                    // no break
                case 'inputEncryptedChat':
                case 'updateEncryptedChatTyping':
                case 'updateEncryptedMessagesRead':
                case 'encryptedMessage':
                case 'encryptedMessageService':
                    $id = $this->getSecretChat($id['chat_id'])->otherID;
                    break;
            }
        }
        $try_id = $this->getIdInternal($id);
        if ($try_id !== null) {
            $id = $try_id;
        }
        if (is_numeric($id)) {
            $id = (int) $id;
            Assert::true($id !== 0, "An invalid ID was specified!");
            if (DialogId::isSecretChat($id)) {
                $id = $this->getSecretChat($id)->otherID;
            }
            if (!$this->peerDatabase->isset($id)) {
                try {
                    $this->logger->logger("Try fetching {$id} with access hash 0");
                    if (DialogId::isSupergroupOrChannel($id)) {
                        $this->peerDatabase->addChatBlocking($id);
                    } elseif ($id < 0) {
                        $this->methodCallAsyncRead('messages.getChats', ['id' => [-$id]]);
                    } else {
                        $this->peerDatabase->addUserBlocking($id);
                    }
                } catch (Exception $e) {
                    $this->logger->logger($e->getMessage(), Logger::WARNING);
                } catch (RPCErrorException $e) {
                    $this->logger->logger($e->getMessage(), Logger::WARNING);
                }
            }
            $chat = $this->peerDatabase->get($id);
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
                        $this->methodCallAsyncRead('channels.getChannels', ['id' => [$this->genAll($chat, \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR)]]);
                    } else {
                        $this->methodCallAsyncRead('users.getUsers', ['id' => [$this->genAll($chat, \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR)]]);
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
                return $this->genAll($chat, $type);
            } catch (PeerNotInDbException $e) {
                $this->peerDatabase->clear($id);
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
        if (\is_string($id)) {
            $id = strtolower(str_replace('@', '', $id));
            if ($id === 'me') {
                return $this->getInfo($this->authorization['user']['id'], $type);
            }
            if ($id === 'support') {
                if (!$this->supportUser) {
                    $this->methodCallAsyncRead('help.getSupport', []);
                }
                return $this->getInfo($this->supportUser, $type);
            }
            if ($bot_api_id = $this->peerDatabase->getIdFromUsername($id)) {
                return $this->getInfo($bot_api_id, $type);
            }
            if ($bot_api_id = $this->peerDatabase->resolveUsername($id)) {
                return $this->getInfo($bot_api_id, $type);
            }
        } else {
            return $this->getInfo($id, $type);
        }
        if ($this->cacheFullDialogs()) {
            return $this->getInfo($id, $type);
        }
        throw new PeerNotInDbException();
    }
    /**
     * @param array                                 $constructor
     * @param \danog\MadelineProto\API::INFO_TYPE_* $type
     * @return ($type is \danog\MadelineProto\API::INFO_TYPE_ALL ? (array{
     *      User?: array,
     *      Chat?: array,
     *      bot_api_id: int,
     *      user_id?: int,
     *      chat_id?: int,
     *      channel_id?: int,
     *      type: string
     * }&array) : array|int)
     */
    private function genAll($constructor, int $type): array|int|string
    {
        if ($type === \danog\MadelineProto\API::INFO_TYPE_CONSTRUCTOR) {
            if ($constructor['_'] === 'user') {
                return ($constructor['self'] ?? false) ? ['_' => 'inputUserSelf'] : ['_' => 'inputUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
            if ($constructor['_'] === 'channel') {
                return ['_' => 'inputChannel', 'channel_id' => (-$constructor['id']) + Magic::ZERO_CHANNEL_ID, 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
        }
        if ($type === \danog\MadelineProto\API::INFO_TYPE_PEER) {
            if ($constructor['_'] === 'user') {
                return ($constructor['self'] ?? false) ? ['_' => 'inputPeerSelf'] : ['_' => 'inputPeerUser', 'user_id' => $constructor['id'], 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
            if ($constructor['_'] === 'channel') {
                return ['_' => 'inputPeerChannel', 'channel_id' => (-$constructor['id']) + Magic::ZERO_CHANNEL_ID, 'access_hash' => $constructor['access_hash'], 'min' => $constructor['min'] ?? false];
            }
            if ($constructor['_'] === 'chat' || $constructor['_'] === 'chatForbidden') {
                return ['_' => 'inputPeerChat', 'chat_id' => -$constructor['id']];
            }
        }
        if ($type === \danog\MadelineProto\API::INFO_TYPE_ID) {
            return $constructor['id'];
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
            return PeerDatabase::getUsernames($constructor);
        }
        $res = [$this->TL->getConstructors()->findByPredicate($constructor['_'])['type'] => $constructor];
        switch ($constructor['_']) {
            case 'user':
                if (!isset($constructor['access_hash'])) {
                    $this->cacheFullDialogs();
                    throw new PeerNotInDbException();
                }
                $res['user_id'] = $constructor['id'];
                $res['bot_api_id'] = $constructor['id'];
                $res['type'] = $constructor['bot'] ?? false ? 'bot' : 'user';
                break;
            case 'chat':
            case 'chatForbidden':
                $res['chat_id'] = $constructor['id'];
                $res['bot_api_id'] = $constructor['id'];
                $res['type'] = 'chat';
                break;
            case 'channel':
                if (!isset($constructor['access_hash'])) {
                    $this->cacheFullDialogs();
                    throw new PeerNotInDbException();
                }
                $res['channel_id'] = $constructor['id'];
                $res['bot_api_id'] = $constructor['id'];
                $res['type'] = $constructor['megagroup'] ?? false ? 'supergroup' : 'channel';
                break;
            case 'channelForbidden':
                throw new PeerNotInDbException();
            default:
                throw new Exception('Invalid constructor given '.$constructor['_']);
        }
        return $res;
    }
    /**
     * Refresh full peer cache for a certain peer.
     *
     * @param mixed $id The peer to refresh
     */
    public function refreshFullPeerCache(mixed $id): void
    {
        $this->peerDatabase->refreshFullPeerCache($id);
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
                if (\is_string($id)) {
                    $id = strtolower(str_replace('@', '', $id));
                    if ($id === 'me') {
                        $id = $this->authorization['user']['id'];
                    } elseif ($id === 'support') {
                        $this->methodCallAsyncRead('help.getSupport', []);
                        continue;
                    } else {
                        $id = $this->peerDatabase->resolveUsername($id);
                        if ($id === null) {
                            throw new PeerNotInDbException;
                        }
                    }
                }
            }
            $id = $this->getIdInternal($id);
            Assert::notNull($id);
            switch (DialogId::getType($id)) {
                case DialogId::CHANNEL_OR_SUPERGROUP:
                    $supergroups []= $id;
                    break;
                case DialogId::CHAT:
                    $chats []= $id;
                    break;
                case DialogId::USER:
                    $users []= $id;
                    break;
                default:
                    throw new AssertionError("Invalid ID $id!");
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
     * When was full info for this chat last cached.
     *
     * @param mixed $id Chat ID
     */
    public function fullChatLastUpdated(mixed $id): int
    {
        return $this->peerDatabase->getFull($this->getIdInternal($id))['inserted'] ?? 0;
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
        if (DialogId::isSecretChat($partial['bot_api_id'])) {
            return $partial;
        }
        $full = $this->peerDatabase->getFull($partial['bot_api_id']);
        if (time() - ($full['inserted'] ?? 0) < $this->getSettings()->getPeer()->getFullInfoCacheTime()) {
            return array_merge($partial, $full);
        }
        switch ($partial['type']) {
            case 'user':
            case 'bot':
                $this->methodCallAsyncRead('users.getFullUser', ['id' => $partial['bot_api_id']]);
                break;
            case 'chat':
                $this->methodCallAsyncRead('messages.getFullChat', $partial);
                break;
            case 'channel':
            case 'supergroup':
                $this->methodCallAsyncRead('channels.getFullChannel', ['channel' => $partial['bot_api_id']]);
                break;
        }
        return array_merge($partial, $this->peerDatabase->getFull($partial['bot_api_id']));
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
            $full = $this->getSettings()->getDb()->getEnableFullPeerDb() ? $this->getFullInfo($id) : $this->getInfo($id);
        } catch (Throwable) {
            $full = $this->getInfo($id);
        }
        $res = ['id' => $full['bot_api_id'], 'type' => $full['type']];
        switch ($full['type']) {
            case 'user':
            case 'bot':
                foreach (['first_name', 'last_name', 'username', 'usernames', 'verified', 'restricted', 'restriction_reason', 'status', 'bot_inline_placeholder', 'phone', 'lang_code', 'bot_nochats'] as $key) {
                    if (isset($full['User'][$key])) {
                        $res[$key] = $full['User'][$key];
                    }
                }
                foreach (['about', 'bot_info', 'phone_calls_available', 'phone_calls_private', 'common_chats_count', 'can_pin_message', 'pinned_msg_id', 'notify_settings'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }
                if (isset($full['full']['profile_photo'])) {
                    $res['photo'] = $full['full']['profile_photo'];
                }
                if (isset($full['full']['fallback_photo'])) {
                    $res['fallback_photo'] = $full['full']['fallback_photo'];
                }
                if (isset($full['full']['personal_photo'])) {
                    $res['personal_photo'] = $full['full']['personal_photo'];
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
                if (isset($full['full']['chat_photo'])) {
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
                foreach (['title', 'democracy', 'restricted', 'restriction_reason', 'username', 'usernames', 'signatures'] as $key) {
                    if (isset($full['Chat'][$key])) {
                        $res[$key] = $full['Chat'][$key];
                    }
                }
                foreach (['read_inbox_max_id', 'read_outbox_max_id', 'hidden_prehistory', 'bot_info', 'notify_settings', 'can_set_stickers', 'stickerset', 'can_view_participants', 'can_set_username', 'participants_count', 'admins_count', 'kicked_count', 'banned_count', 'migrated_from_chat_id', 'migrated_from_max_id', 'pinned_msg_id', 'about', 'hidden_prehistory', 'available_min_id', 'can_view_stats', 'online_count'] as $key) {
                    if (isset($full['full'][$key])) {
                        $res[$key] = $full['full'][$key];
                    }
                }
                if (isset($full['full']['chat_photo'])) {
                    $res['photo'] = $full['full']['chat_photo'];
                }
                if (isset($full['full']['exported_invite']['link'])) {
                    $res['invite'] = $full['full']['exported_invite']['link'];
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
                if (isset($participant['kicked_by'])) {
                    $newres['kicked_by'] = ($this->getPwrChat($participant['kicked_by'], false));
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
        if (!isset($res['participants']) && $fullfetch && \in_array($res['type'], ['supergroup', 'channel'], true)) {
            $total_count = ($res['participants_count'] ?? 0) + ($res['admins_count'] ?? 0) + ($res['kicked_count'] ?? 0) + ($res['banned_count'] ?? 0);
            $res['participants'] = [];
            $filters = ['channelParticipantsAdmins', 'channelParticipantsBots'];
            $promises = [];
            foreach ($filters as $filter) {
                $promises []= async(function () use ($filter, $total_count, &$res): void {
                    $this->fetchParticipants($res['id'], $filter, '', $total_count, $res);
                });
            }
            await($promises);

            $q = '';
            $filters = ['channelParticipantsSearch', 'channelParticipantsKicked', 'channelParticipantsBanned'];
            $promises = [];
            foreach ($filters as $filter) {
                $promises []= async(function () use ($filter, $q, $total_count, &$res): void {
                    $this->recurseAlphabetSearchParticipants($res['id'], $filter, $q, $total_count, $res, 0);
                });
            }
            await($promises);

            $this->logger->logger('Fetched '.\count($res['participants'])." out of {$total_count}");
            $res['participants'] = array_values($res['participants']);
        }
        if (!$fullfetch) {
            unset($res['participants']);
        }
        return $res;
    }
    private function recurseAlphabetSearchParticipants(int $channel, string $filter, string $q, int $total_count, array &$res, int $depth): array
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

        $yielded = array_merge(...await($promises));
        while ($yielded) {
            $newYielded = [];

            foreach (array_chunk($yielded, 10) as $promises) {
                $newYielded = array_merge($newYielded, ...(await($promises)));
            }

            $yielded = $newYielded;
        }

        return [];
    }
    private function fetchParticipants(int $channel, string $filter, string $q, int $total_count, array &$res): bool
    {
        $offset = 0;
        $limit = 200;
        $has_more = false;
        $cached = false;
        $last_count = -1;
        do {
            try {
                $gres = $this->methodCallAsyncRead('channels.getParticipants', ['channel' => $channel, 'filter' => ['_' => $filter, 'q' => $q], 'offset' => $offset, 'limit' => $limit, 'hash' => $hash = $this->getParticipantsHash($channel, $filter, $q, $offset, $limit), 'floodWaitLimit' => 86400]);
            } catch (ChatAdminRequiredError) {
                $this->logger->logger('CHAT_ADMIN_REQUIRED');
                return $has_more;
            }
            if ($cached = ($gres['_'] === 'channels.channelParticipantsNotModified')) {
                $gres = $this->fetchParticipantsCache($channel, $filter, $q, $offset, $limit);
            } else {
                $this->storeParticipantsCache($gres, $channel, $filter, $q, $offset, $limit);
            }
            \assert($gres !== null);
            if ($last_count !== -1 && $last_count !== $gres['count']) {
                $has_more = true;
            } else {
                $last_count = $gres['count'];
            }
            $promises = [];
            foreach ($gres['participants'] as $participant) {
                $promises []= async(function () use (&$res, $participant): void {
                    $newres = $participant;
                    unset($newres['user_id'], $newres['peer']);
                    $newres['user'] = ($this->getPwrChat($participant['user_id'] ?? $participant['peer'], false));
                    if (isset($participant['inviter_id'])) {
                        unset($newres['inviter_id']);
                        $newres['inviter'] = ($this->getPwrChat($participant['inviter_id'], false));
                    }
                    if (isset($participant['kicked_by'])) {
                        unset($newres['kicked_by']);
                        $newres['kicked_by'] = ($this->getPwrChat($participant['kicked_by'], false));
                    }
                    if (isset($participant['promoted_by'])) {
                        unset($newres['promoted_by']);
                        $newres['promoted_by'] = ($this->getPwrChat($participant['promoted_by'], false));
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
            $h = $hash !== null ? 'present' : 'absent';
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
    private function fetchParticipantsCache(int $channel, string $filter, string $q, int $offset, int $limit): ?array
    {
        return $this->channelParticipants[$this->participantsKey($channel, $filter, $q, $offset, $limit)];
    }
    private function storeParticipantsCache(array $gres, int $channel, string $filter, string $q, int $offset, int $limit): void
    {
        unset($gres['users']);
        $ids = [];
        foreach ($gres['participants'] as $participant) {
            if (isset($participant['user_id'])) {
                $ids[] = $participant['user_id'];
            }
        }
        sort($ids, SORT_NUMERIC);
        /** @psalm-suppress MixedArgumentTypeCoercion Typechecks are done inside */
        $gres['hash'] = Tools::genVectorHash($ids);
        $this->channelParticipants[$this->participantsKey($channel, $filter, $q, $offset, $limit)] = $gres;
    }
    private function getParticipantsHash(int $channel, string $filter, string $q, int $offset, int $limit): ?string
    {
        return $this->fetchParticipantsCache($channel, $filter, $q, $offset, $limit)['hash'] ?? null;
    }
}
