<?php

declare(strict_types=1);

/**
 * Files module.
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

use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\LocalMutex;
use AssertionError;
use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Db\MemoryArray;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings\Database\SerializerType;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\Tools;
use InvalidArgumentException;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

/**
 * Manages peers.
 *
 * @internal
 */
final class PeerDatabase implements TLCallback
{
    use DbPropertiesTrait;

    private const V = 0;

    /**
     * Chats.
     *
     * @var DbArray<int, array>
     */
    private DbArray $db;
    /**
     * @var DbArray<int, array>
     */
    private DbArray $fullDb;
    /**
     * @var DbArray<string, int>
     */
    private DbArray $usernames;
    private bool $hasInfo = true;
    private bool $hasUsernames = true;

    /** @var array<int, array> */
    private array $pendingDb = [];

    private int $v = self::V;

    /**
     * List of properties stored in database (memory or external).
     *
     * @see DbPropertiesFactory
     */
    protected static array $dbProperties = [
        'db' => [
            'innerMadelineProto' => true,
            'table' => 'MTProto_chats'
        ],
        'fullDb' => [
            'innerMadelineProto' => true,
            'table' => 'MTProto_full_chats'
        ],
        'usernames' => [
            'innerMadelineProto' => true,
            'innerMadelineProtoSerializer' => SerializerType::STRING,
            'table' => 'MTProto_usernames'
        ],
    ];

    private LocalMutex $decacheMutex;
    private LocalKeyedMutex $mutex;
    public function __construct(private MTProto $API)
    {
        $this->decacheMutex = new LocalMutex;
        $this->mutex = new LocalKeyedMutex;
    }
    public function __sleep()
    {
        return ['db', 'fullDb', 'usernames', 'API', 'pendingDb', 'v', 'hasInfo'];
    }
    public function __wakeup(): void
    {
        $this->decacheMutex = new LocalMutex;
        $this->mutex = new LocalKeyedMutex;
    }
    public function init(): void
    {
        $this->initDb($this->API);
        if (!$this->API->settings->getDb()->getEnableFullPeerDb()) {
            $this->fullDb->clear();
        }
        if (!$this->API->settings->getDb()->getEnablePeerInfoDb() && $this->hasInfo) {
            $this->API->logger('Cleaning up peer database...');
            $k = 0;
            $total = \count($this->db);
            foreach ($this->db as $key => $value) {
                $value = [
                    '_' => $value['_'],
                    'id' => $value['id'],
                    'access_hash' => $value['access_hash'] ?? null,
                    'min' => $value['min'] ?? false,
                ];
                $k++;
                if ($k % 500 === 0 || $k === $total) {
                    $this->API->logger("Cleaning up peer database ($k/$total)...");
                }
                $this->db[$key] = $value;
            }
            $this->hasInfo = false;
            $this->API->logger('Cleaned up peer database!');
        }

        if (!$this->API->settings->getDb()->getEnableUsernameDb()) {
            $this->usernames->clear();
        }

        EventLoop::queue(function (): void {
            $this->API->waitForInit();
            foreach ($this->pendingDb as $key => $_) {
                EventLoop::queue($key < 0 ? $this->processChat(...) : $this->processUser(...), $key);
            }
        });
    }
    public function importLegacy(MemoryArray $chats, MemoryArray $fullChats): void
    {
        foreach ($chats as $id => $chat) {
            $this->db[$id] = $chat;
            if ($this->API->settings->getDb()->getEnableUsernameDb()) {
                foreach (self::getUsernames($chat) as $username) {
                    $this->usernames[$username] = (int) $id;
                }
            }
        }
        if (!$this->API->settings->getDb()->getEnableFullPeerDb()) {
            return;
        }
        foreach ($fullChats as $id => $chat) {
            $this->fullDb[$id] = $chat;
        }
    }

    public function getFull(int $id): ?array
    {
        return $this->fullDb[$id];
    }
    public function expireFull(int $id): void
    {
        unset($this->fullDb[$id]);
    }
    public function get(int $id): ?array
    {
        while (isset($this->pendingDb[$id])) {
            if ($id < 0) {
                $this->processChat($id);
            } else {
                $this->processUser($id);
            }
        }
        return $this->db[$id];
    }
    public function isset(int $id): bool
    {
        return isset($this->pendingDb[$id]) || isset($this->db[$id]);
    }
    public function clear(int $id): void
    {
        if ($id < 0) {
            $this->processChat($id);
        } else {
            $this->processUser($id);
        }
        unset($this->db[$id]);
    }

    /**
     * @return list<int>
     */
    public function getDialogIds(): array
    {
        $res = [];
        foreach ($this->getDialogs() as $id => $_) {
            $res []= (int) $id;
        }
        return $res;
    }

    /**
     * @return iterable<int, array>
     */
    public function getDialogs(): iterable
    {
        foreach ($this->pendingDb as $key => $_) {
            if ($key < 0) {
                $this->processChat($key);
            } else {
                $this->processUser($key);
            }
        }
        return $this->db->getIterator();
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
                    $invite = $this->API->methodCallAsyncRead('messages.checkChatInvite', ['hash' => $content]);
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
                $id = $this->API->authorization['user']['id'];
            } elseif ($id === 'support') {
                $id = $this->API->methodCallAsyncRead('help.getSupport', [])['user'];
            } else {
                $id = $this->resolveUsername($id);
            }
        }
        unset($this->fullDb[$this->API->getIdInternal($id)]);
        $this->API->getFullInfo($id);
    }
    /**
     * When were full info for this chat last cached.
     *
     * @param mixed $id Chat ID
     */
    public function fullChatLastUpdated(mixed $id): int
    {
        return $this->getFull($id)['last_update'] ?? 0;
    }

    private function recacheChatUsername(int $id, ?array $old, array $new): void
    {
        if (!$this->API->settings->getDb()->getEnableUsernameDb()) {
            return;
        }
        $new = self::getUsernames($new);
        $old = $old ? self::getUsernames($old) : [];
        $diffToRemove = \array_diff($old, $new);
        $diffToAdd = \array_diff($new, $old);
        if (!$diffToAdd && !$diffToRemove) {
            return;
        }
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
    public static function getUsernames(array $constructor): array
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

    public function getIdFromUsername(string $username): ?int
    {
        foreach ($this->pendingDb as $key => $_) {
            if ($key < 0) {
                $this->processChat($key);
            } else {
                $this->processUser($key);
            }
        }
        $result = $this->usernames[$username];
        return $result === null ? $result : (int) $result;
    }

    private array $caching_simple_username = [];
    /**
     * @param string $username Username
     */
    public function resolveUsername(string $username): ?int
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
            $result = $this->API->getIdInternal(
                ($this->API->methodCallAsyncRead('contacts.resolveUsername', ['username' => $username]))['peer'],
            );
        } catch (FloodWaitError $e) {
            throw $e;
        } catch (RPCErrorException $e) {
            $this->API->logger->logger('Username resolution failed with error '.$e->getMessage(), Logger::ERROR);
            if ($e->rpc === 'AUTH_KEY_UNREGISTERED' || $e->rpc === 'USERNAME_INVALID') {
                throw $e;
            }
        } finally {
            unset($this->caching_simple_username[$username]);
        }
        foreach ($this->pendingDb as $key => $_) {
            if ($key < 0) {
                $this->processChat($key);
            } else {
                $this->processUser($key);
            }
        }
        return $result;
    }
    /**
     * Add user info.
     *
     * @internal
     *
     * @param array $user User info
     */
    private function addUser(array $user): void
    {
        if ($user['_'] === 'userEmpty') {
            return;
        }
        if ($user['_'] !== 'user') {
            throw new AssertionError('Invalid user type '.$user['_']);
        }
        if ($user['id'] === ($this->API->authorization['user']['id'] ?? null)) {
            $this->API->authorization['user'] = $user;
        }
        if (($this->pendingDb[$user['id']] ?? null) === $user['id']) {
            return;
        }
        $this->pendingDb[$user['id']] = $user;
        EventLoop::queue($this->processUser(...), $user['id']);
    }
    private function processUser(int $id): void
    {
        if (!isset($this->pendingDb[$id])) {
            return;
        }
        $lock = $this->mutex->acquire((string) $id);
        try {
            if (!isset($this->pendingDb[$id])) {
                return;
            }
            $o = $user = $this->pendingDb[$id];
            $existingChat = $this->db[$user['id']];
            if (!isset($user['access_hash']) && !($user['min'] ?? false)) {
                if (isset($existingChat['access_hash'])) {
                    $this->API->logger->logger("No access hash with user {$user['id']}, using backup");
                    $user['access_hash'] = $existingChat['access_hash'];
                    $user['min'] = false;
                } else {
                    Assert::null($existingChat);
                    try {
                        $this->API->methodCallAsyncRead('users.getUsers', ['id' => [[
                            '_' => 'inputUser',
                            'user_id' => $user['id'],
                            'access_hash' => 0,
                        ]]]);
                        return;
                    } catch (RPCErrorException $e) {
                        $this->API->logger->logger("An error occurred while trying to fetch the missing access_hash for user {$user['id']}: {$e->getMessage()}", Logger::FATAL_ERROR);
                    }
                    foreach (self::getUsernames($user) as $username) {
                        if (($this->resolveUsername($username)) === $user['id']) {
                            return;
                        }
                    }
                    return;
                }
            }
            if ($existingChat !== $user) {
                $this->API->logger->logger("Updated user {$user['id']}", Logger::ULTRA_VERBOSE);
                if (($user['min'] ?? false) && !($existingChat['min'] ?? false)) {
                    $this->API->logger->logger("{$user['id']} is min, filling missing fields", Logger::ULTRA_VERBOSE);
                    if (isset($existingChat['access_hash'])) {
                        $user['min'] = false;
                        $user['access_hash'] = $existingChat['access_hash'];
                    }
                }
                $this->recacheChatUsername($user['id'], $existingChat, $user);
                if (!$this->API->settings->getDb()->getEnablePeerInfoDb()) {
                    $user = [
                        '_' => $user['_'],
                        'id' => $user['id'],
                        'access_hash' => $user['access_hash'] ?? null,
                        'min' => $user['min'] ?? false,
                    ];
                }
                $this->db[$user['id']] = $user;
                if ($existingChat && ($existingChat['min'] ?? false) && !($user['min'] ?? false)) {
                    $this->API->minDatabase->clearPeer($user['id']);
                }
            }
        } finally {
            if (isset($o) && $this->pendingDb[$id] === $o) {
                unset($this->pendingDb[$id]);
            }
            $lock->release();
        }
    }
    public function addChatBlocking(int $chat): void
    {
        if (isset($this->pendingDb[$chat])) {
            $this->processChat($chat);
        } else {
            $this->pendingDb[$chat] = [
                '_' => 'channel',
                'id' => DialogId::toSupergroupOrChannel($chat),
            ];
            $this->processChat($chat);
        }
    }
    public function addUserBlocking(int $user): void
    {
        if (isset($this->pendingDb[$user])) {
            $this->processChat($user);
        } else {
            $this->pendingDb[$user] = [
                '_' => 'user',
                'id' => $user,
            ];
            $this->processUser($user);
        }
    }
    /**
     * Add chat to database.
     *
     * @param array $chat Chat
     * @internal
     */
    private function addChat(array $chat): void
    {
        $id = $this->API->getIdInternal($chat);
        if (($this->pendingDb[$id] ?? null) === $chat) {
            return;
        }
        $this->pendingDb[$id] = $chat;
        EventLoop::queue($this->processChat(...), $id);
    }
    private function processChat(int $id): void
    {
        if (!isset($this->pendingDb[$id])) {
            return;
        }
        $lock = $this->mutex->acquire((string) $id);
        try {
            if (!isset($this->pendingDb[$id])) {
                return;
            }
            $o = $chat = $this->pendingDb[$id];
            if ($chat['_'] === 'chat'
                || $chat['_'] === 'chatEmpty'
                || $chat['_'] === 'chatForbidden'
            ) {
                $existingChat = $this->db[-$chat['id']];
                if (!$existingChat || $existingChat !== $chat) {
                    $this->API->logger->logger("Updated chat -{$chat['id']}", Logger::ULTRA_VERBOSE);
                    if (!$this->API->settings->getDb()->getEnablePeerInfoDb()) {
                        $chat = [
                            '_' => $chat['_'],
                            'id' => $chat['id'],
                            'access_hash' => $chat['access_hash'] ?? null,
                            'min' => $chat['min'] ?? false,
                        ];
                    }
                    $this->db[-$chat['id']] = $chat;
                }
                return;
            }
            if ($chat['_'] === 'channelEmpty') {
                return;
            }
            if ($chat['_'] !== 'channel' && $chat['_'] !== 'channelForbidden') {
                throw new InvalidArgumentException('Invalid chat type '.$chat['_']);
            }
            $bot_api_id = DialogId::fromSupergroupOrChannel($chat['id']);
            $existingChat = $this->db[$bot_api_id];
            if (!isset($chat['access_hash']) && !($chat['min'] ?? false)) {
                if (isset($existingChat['access_hash'])) {
                    $this->API->logger->logger("No access hash with channel {$bot_api_id}, using backup");
                    $chat['access_hash'] = $existingChat['access_hash'];
                } else {
                    Assert::null($existingChat);
                    try {
                        $this->API->methodCallAsyncRead('channels.getChannels', ['id' => [[
                            '_' => 'inputChannel',
                            'channel_id' => $chat['id'],
                            'access_hash' => 0,
                        ]]]);
                        return;
                    } catch (RPCErrorException $e) {
                        $this->API->logger->logger("An error occurred while trying to fetch the missing access_hash for channel {$bot_api_id}: {$e->getMessage()}", Logger::FATAL_ERROR);
                    }
                    foreach (self::getUsernames($chat) as $username) {
                        if (($this->resolveUsername($username)) === $bot_api_id) {
                            return;
                        }
                    }
                    return;
                }
            }
            if ($existingChat !== $chat) {
                $this->recacheChatUsername($bot_api_id, $existingChat, $chat);
                $this->API->logger->logger("Updated chat {$bot_api_id}", Logger::ULTRA_VERBOSE);
                if (($chat['min'] ?? false) && $existingChat && !($existingChat['min'] ?? false)) {
                    $this->API->logger->logger("{$bot_api_id} is min, filling missing fields", Logger::ULTRA_VERBOSE);
                    $newchat = $existingChat;
                    foreach (['title', 'username', 'usernames', 'photo', 'banned_rights', 'megagroup', 'verified'] as $field) {
                        if (isset($chat[$field])) {
                            $newchat[$field] = $chat[$field];
                        }
                    }
                    $chat = $newchat;
                }
                if (!$this->API->settings->getDb()->getEnablePeerInfoDb()) {
                    $chat = [
                        '_' => $chat['_'],
                        'id' => $chat['id'],
                        'access_hash' => $chat['access_hash'] ?? null,
                        'min' => $chat['min'] ?? false,
                    ];
                }
                $this->db[$bot_api_id] = $chat;
                if ($existingChat && ($existingChat['min'] ?? false) && !($chat['min'] ?? false)) {
                    $this->API->minDatabase->clearPeer($bot_api_id);
                }
            }
        } finally {
            if (isset($o) && $this->pendingDb[$id] === $o) {
                unset($this->pendingDb[$id]);
            }
            $lock->release();
        }
    }
    /**
     * @internal
     */
    private function addFullChat(array $full): void
    {
        $this->fullDb[$this->API->getIdInternal($full)] = [
            'full' => $full,
            'last_update' => \time(),
        ];
    }

    public function getMethodAfterResponseDeserializationCallbacks(): array
    {
        return [];
    }
    public function getMethodBeforeResponseDeserializationCallbacks(): array
    {
        return [];
    }
    public function getConstructorAfterDeserializationCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['chat', 'chatEmpty', 'chatForbidden', 'channel', 'channelEmpty', 'channelForbidden'], [$this->addChat(...)]),
            \array_fill_keys(['user', 'userEmpty'], [$this->addUser(...)]),
            \array_fill_keys(['chatFull', 'channelFull', 'userFull'], [$this->addFullChat(...)]),
        );
    }
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return [];
    }
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return [];
    }
    /**
     * @internal
     */
    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }

    public function __debugInfo()
    {
        return ['PeerDatabase instance '.\spl_object_hash($this)];
    }
}
