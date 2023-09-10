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

use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Secret\SecretFeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\DialogId;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use phpseclib3\Math\BigInteger;

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
    public array $secret_chats = [];
    /**
     * Request secret chat.
     *
     * @param mixed $user User to start secret chat with
     */
    public function requestSecretChat(mixed $user): int
    {
        $user = ($this->getInfo($user));
        if (!isset($user['InputUser'])) {
            throw new PeerNotInDbException();
        }
        $user = $user['InputUser'];
        $this->logger->logger('Creating secret chat with '.$user['user_id'].'...', Logger::VERBOSE);
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
    /**
     * Accept secret chat.
     *
     * @param array $params Secret chat ID
     */
    public function acceptSecretChat(array $params): void
    {
        if ($this->secretChatStatus($params['id']) !== 0) {
            $this->logger->logger("I've already accepted secret chat ".$params['id']);
            return;
        }
        $dh_config = $this->getDhConfig();
        $this->logger->logger('Generating b...', Logger::VERBOSE);
        $b = new BigInteger(Tools::random(256), 256);
        $params['g_a'] = new BigInteger((string) $params['g_a'], 256);
        Crypt::checkG($params['g_a'], $dh_config['p']);
        $key = ['auth_key' => \str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
        //$this->logger->logger($key);
        $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = \substr(\sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);
        $this->secret_chats[$params['id']] = new SecretChatController(
            $this,
            new SecretChat(
                DialogId::fromSecretChatId($params['id']),
                false,
                $params['admin_id'],
                time(),
                0
            )
        ) [
            'chat_id' => $params['id'],
            'key' => $key,
            'admin' => false,
            'user_id' => $params['admin_id'],
            'InputEncryptedChat' => [
                '_' => 'inputEncryptedChat',
                'chat_id' => $params['id'],
                'access_hash' => $params['access_hash'],
            ],
            'in_seq_no_x' => 1,
            'out_seq_no_x' => 0,
            'in_seq_no' => 0,
            'out_seq_no' => 0,
            'layer' => 8,
            'ttl' => 0,
            'ttr' => 100,
            'updated' => \time(),
            'incoming' => [],
            'outgoing' => [],
            'created' => \time(),
            'mtproto' => 1,
        ];
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        Crypt::checkG($g_b, $dh_config['p']);
        $this->methodCallAsyncRead('messages.acceptEncryption', ['peer' => $params['id'], 'g_b' => $g_b->toBytes(), 'key_fingerprint' => $key['fingerprint']]);
        $this->notifyLayer($params['id']);
        $this->logger->logger('Secret chat '.$params['id'].' accepted successfully!', Logger::NOTICE);
    }
    /**
     * Complete secret chat.
     *
     * @param array $params Secret chat
     */
    private function completeSecretChat(array $params)
    {
        if ($this->secretChatStatus($params['id']) !== 1) {
            //$this->logger->logger($this->secretChatStatus($params['id']));
            $this->logger->logger('Could not find and complete secret chat '.$params['id']);
            return false;
        }
        $dh_config = ($this->getDhConfig());
        $params['g_a_or_b'] = new BigInteger((string) $params['g_a_or_b'], 256);
        Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
        $key = ['auth_key' => \str_pad($params['g_a_or_b']->powMod($this->temp_requested_secret_chats[$params['id']], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
        unset($this->temp_requested_secret_chats[$params['id']]);
        $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
        //$this->logger->logger($key);
        if ($key['fingerprint'] !== $params['key_fingerprint']) {
            $this->discardSecretChat($params['id']);
            throw new SecurityException('Invalid key fingerprint!');
        }
        $key['visualization_orig'] = \substr(\sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);
        $this->secret_chats[$params['id']] = $chat = new SecretChatController(
            $this,
            new SecretChat(
                DialogId::fromSecretChatId($params['id']),
                true,
                $params['participant_id'],
                time(),
                0
            ),
            1,
            0
        );
        $chat->notifyLayer();
        $this->logger->logger('Secret chat '.$params['id'].' completed successfully!', Logger::NOTICE);
    }
    /**
     * Get secret chat.
     *
     * @param array|int $chat Secret chat ID
     */
    public function getSecretChat(array|int $chat): SecretChat
    {
        if (is_array($chat)) {
            return $this->getInfo($chat);
        } elseif (DialogId::isSecretChat($chat)) {
            $chat = DialogId::toSecretChatId($chat);
        }
        return $this->secret_chats[$chat];
    }
    /**
     * Check whether secret chat exists.
     *
     * @param array|int $chat Secret chat ID
     */
    public function hasSecretChat(array|int $chat): bool
    {
        return isset($this->secret_chats[\is_array($chat) ? $chat['chat_id'] : $chat]);
    }
    /**
     * Discard secret chat.
     *
     * @param int $chat Secret chat ID
     */
    public function discardSecretChat(int $chat): void
    {
        $this->logger->logger('Discarding secret chat '.$chat.'...', Logger::VERBOSE);
        if (isset($this->secret_chats[$chat])) {
            unset($this->secret_chats[$chat]);
        }
        if (isset($this->temp_requested_secret_chats[$chat])) {
            unset($this->temp_requested_secret_chats[$chat]);
        }
        try {
            $this->methodCallAsyncRead('messages.discardEncryption', ['chat_id' => $chat]);
        } catch (RPCErrorException $e) {
            if ($e->rpc !== 'ENCRYPTION_ALREADY_DECLINED') {
                throw $e;
            }
        }
    }
}
