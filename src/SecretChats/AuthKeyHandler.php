<?php

declare(strict_types=1);

/**
 * AuthKeyHandler module.
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

namespace danog\MadelineProto\SecretChats;

use Amp\Sync\LocalKeyedMutex;
use AssertionError;
use danog\DialogId\DialogId;
use danog\MadelineProto\EventHandler\Message\SecretMessage;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\RPCError\EncryptionAlreadyAcceptedError;
use danog\MadelineProto\RPCError\EncryptionAlreadyDeclinedError;
use danog\MadelineProto\SecretPeerNotInDbException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use phpseclib3\Math\BigInteger;
use Revolt\EventLoop;

use const STR_PAD_LEFT;

/**
 * Manages secret chats.
 *
 * https://core.telegram.org/api/end-to-end
 *
 * @internal
 */
trait AuthKeyHandler
{
    /**
     * Temporary requested secret chats.
     *
     */
    protected array $temp_requested_secret_chats = [];
    /**
     * Secret chats.
     *
     * @var array<int, SecretChatController>
     */
    public array $secretChats = [];
    /**
     * Request secret chat.
     *
     * @param mixed $user User to start secret chat with
     */
    public function requestSecretChat(mixed $user): int
    {
        $user = $this->getInfo($user);
        if ($user['type'] !== 'user') {
            throw new AssertionError("Can only create a secret chat with a user!");
        }
        $user = $user['user_id'];
        $this->logger->logger('Creating secret chat with '.$user.'...', Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $this->logger->logger('Generating a...', Logger::VERBOSE);
        $a = new BigInteger(Tools::random(256), 256);
        $this->logger->logger('Generating g_a...', Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        Crypt::checkG($g_a, $dh_config['p']);
        $res = $this->methodCallAsyncRead('messages.requestEncryption', ['user_id' => $user, 'g_a' => $g_a->toBytes()]);
        $this->temp_requested_secret_chats[$res['id']] = $a;
        $this->updaters[UpdateLoop::GENERIC]->resume();
        $this->logger->logger('Secret chat '.$res['id'].' requested successfully!', Logger::NOTICE);
        return $res['id'];
    }
    private LocalKeyedMutex $acceptChatMutex;
    private LocalKeyedMutex $confirmChatMutex;
    /**
     * Accept secret chat.
     *
     * @param array $params Secret chat ID
     */
    public function acceptSecretChat(array $params): void
    {
        $lock = $this->acceptChatMutex->acquire((string) $params['id']);
        try {
            if (isset($this->secretChats[$params['id']])) {
                $this->logger->logger("I've already accepted secret chat ".$params['id']);
                return;
            }
            $dh_config = $this->getDhConfig();
            $this->logger->logger('Generating b...', Logger::VERBOSE);
            $b = new BigInteger(Tools::random(256), 256);
            $params['g_a'] = new BigInteger((string) $params['g_a'], 256);
            Crypt::checkG($params['g_a'], $dh_config['p']);
            $key = ['auth_key' => str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
            //$this->logger->logger($key);
            $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
            $key['visualization_orig'] = substr(sha1($key['auth_key'], true), 16);
            $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
            $this->secretChats[$params['id']] = $chat = new SecretChatController(
                $this,
                $key,
                $params['id'],
                $params['access_hash'],
                false,
                $params['admin_id'],
            );
            $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
            Crypt::checkG($g_b, $dh_config['p']);
            $this->methodCallAsyncRead('messages.acceptEncryption', ['peer' => $params['id'], 'g_b' => $g_b->toBytes(), 'key_fingerprint' => $key['fingerprint']]);
            $chat->notifyLayer();
            $this->logger->logger('Secret chat '.$params['id'].' accepted successfully!', Logger::NOTICE);
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Complete secret chat.
     *
     * @param array $params Secret chat
     */
    private function completeSecretChat(array $params): void
    {
        $lock = $this->confirmChatMutex->acquire((string) $params['id']);
        try {
            if (!isset($this->temp_requested_secret_chats[$params['id']])) {
                //$this->logger->logger($this->secretChatStatus($params['id']));
                $this->logger->logger('Could not find and complete secret chat '.$params['id']);
                return;
            }
            $dh_config = ($this->getDhConfig());
            $params['g_a_or_b'] = new BigInteger((string) $params['g_a_or_b'], 256);
            Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
            $key = ['auth_key' => str_pad($params['g_a_or_b']->powMod($this->temp_requested_secret_chats[$params['id']], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
            unset($this->temp_requested_secret_chats[$params['id']]);
            $key['fingerprint'] = substr(sha1($key['auth_key'], true), -8);
            //$this->logger->logger($key);
            if ($key['fingerprint'] !== $params['key_fingerprint']) {
                $this->discardSecretChat($params['id']);
                throw new SecurityException('Invalid key fingerprint!');
            }
            $key['visualization_orig'] = substr(sha1($key['auth_key'], true), 16);
            $key['visualization_46'] = substr(hash('sha256', $key['auth_key'], true), 20);
            $this->secretChats[$params['id']] = $chat = new SecretChatController(
                $this,
                $key,
                $params['id'],
                $params['access_hash'],
                true,
                $params['participant_id'],
            );
            $chat->notifyLayer();
            $this->logger->logger('Secret chat '.$params['id'].' completed successfully!', Logger::NOTICE);
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    /**
     * Gets a secret chat message.
     *
     * @param integer $chatId Secret chat ID.
     * @param integer $randomId Secret chat message ID.
     */
    public function getSecretMessage(int $chatId, int $randomId): SecretMessage
    {
        return $this->wrapMessage($this->getSecretChatController($chatId)->getMessage($randomId)['message']);
    }

    /**
     * Get secret chat.
     *
     * @param array|int $chat Secret chat ID
     */
    public function getSecretChat(array|int $chat): SecretChat
    {
        return $this->getSecretChatController($chat)->public;
    }
    /**
     * Get secret chat controller.
     *
     * @internal
     *
     * @param array|int $chat Secret chat ID
     */
    public function getSecretChatController(array|int $chat): SecretChatController
    {
        if (\is_array($chat)) {
            switch ($chat['_']) {
                case 'updateEncryption':
                    $chat = $chat['chat']['id'];
                    break;
                case 'updateNewEncryptedMessage':
                    $chat = $chat['message'];
                    // no break
                case 'inputEncryptedChat':
                case 'updateEncryptedChatTyping':
                case 'updateEncryptedMessagesRead':
                case 'encryptedMessage':
                case 'encryptedMessageService':
                    $chat = $chat['chat_id'];
                    break;
                default:
                    throw new AssertionError("Unknown update type {$chat['_']} provided!");
            }
        } elseif (DialogId::isSecretChat($chat)) {
            $chat = DialogId::toSecretChatId($chat);
        }
        if (!isset($this->secretChats[$chat])) {
            throw new SecretPeerNotInDbException;
        }
        return $this->secretChats[$chat];
    }
    /**
     * Check whether secret chat exists.
     *
     * @param array|int $chat Secret chat ID
     */
    public function hasSecretChat(array|int $chat): bool
    {
        return isset($this->secretChats[\is_array($chat) ? $chat['chat_id'] : $chat]);
    }
    /**
     * Discard secret chat.
     *
     * @param int $chat Secret chat ID
     * @param bool $deleteHistory If true, deletes the entire chat history for the other user as well.
     */
    public function discardSecretChat(int $chat, bool $deleteHistory = false): void
    {
        $this->logger->logger('Discarding secret chat '.$chat.'...', Logger::VERBOSE);
        if (isset($this->secretChats[$chat])) {
            unset($this->secretChats[$chat]);
        }
        if (isset($this->temp_requested_secret_chats[$chat])) {
            unset($this->temp_requested_secret_chats[$chat]);
        }
        try {
            $this->methodCallAsyncRead('messages.discardEncryption', ['chat_id' => $chat, 'delete_history' => $deleteHistory]);
        } catch (EncryptionAlreadyAcceptedError|EncryptionAlreadyDeclinedError) {
        }
    }
}
