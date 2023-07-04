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
use danog\MadelineProto\Loop\Update\SecretFeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
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
     */
    protected array $secret_chats = [];
    /**
     * Accept secret chat.
     *
     * @param array $params Secret chat ID
     */
    public function acceptSecretChat(array $params): void
    {
        //$this->logger->logger($params['id'],$this->secretChatStatus($params['id']));
        if ($this->secretChatStatus($params['id']) !== 0) {
            //$this->logger->logger($this->secretChatStatus($params['id']));
            $this->logger->logger("I've already accepted secret chat ".$params['id']);
            return;
        }
        $dh_config = ($this->getDhConfig());
        $this->logger->logger('Generating b...', Logger::VERBOSE);
        $b = new BigInteger(Tools::random(256), 256);
        $params['g_a'] = new BigInteger((string) $params['g_a'], 256);
        Crypt::checkG($params['g_a'], $dh_config['p']);
        $key = ['auth_key' => \str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
        //$this->logger->logger($key);
        $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = \substr(\sha1($key['auth_key'], true), 16);
        $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);
        $this->secret_chats[$params['id']] = [
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
            'rekeying' => [0],
            'key_x' => 'from server',
            'mtproto' => 1,
        ];
        $this->secretFeeders[$params['id']] = new SecretFeedLoop($this, $params['id']);
        $this->secretFeeders[$params['id']]->start();
        $this->secretFeeders[$params['id']]->resume();
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        Crypt::checkG($g_b, $dh_config['p']);
        $this->methodCallAsyncRead('messages.acceptEncryption', ['peer' => $params['id'], 'g_b' => $g_b->toBytes(), 'key_fingerprint' => $key['fingerprint']]);
        $this->notifyLayer($params['id']);
        $this->logger->logger('Secret chat '.$params['id'].' accepted successfully!', Logger::NOTICE);
    }
    /**
     * Request secret chat.
     *
     * @param mixed $user User to start secret chat with
     */
    public function requestSecretChat(mixed $user)
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
        $this->secret_chats[$params['id']] = ['key' => $key, 'admin' => true, 'user_id' => $params['participant_id'], 'InputEncryptedChat' => ['chat_id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputEncryptedChat'], 'in_seq_no_x' => 0, 'out_seq_no_x' => 1, 'in_seq_no' => 0, 'out_seq_no' => 0, 'layer' => 8, 'ttl' => 0, 'ttr' => 100, 'updated' => \time(), 'incoming' => [], 'outgoing' => [], 'created' => \time(), 'rekeying' => [0], 'key_x' => 'to server', 'mtproto' => 1];
        $this->secretFeeders[$params['id']] = new SecretFeedLoop($this, $params['id']);
        $this->secretFeeders[$params['id']]->start();
        $this->secretFeeders[$params['id']]->resume();
        $this->notifyLayer($params['id']);
        $this->logger->logger('Secret chat '.$params['id'].' completed successfully!', Logger::NOTICE);
    }
    private function notifyLayer($chat): void
    {
        $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNotifyLayer', 'layer' => $this->TL->getSecretLayer()]]]);
    }
    /**
     * Temporary rekeyed secret chats.
     *
     */
    protected array $temp_rekeyed_secret_chats = [];
    /**
     * Rekey secret chat.
     *
     * @param int $chat Secret chat to rekey
     */
    public function rekey(int $chat): ?string
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 0) {
            return null;
        }
        $this->logger->logger('Rekeying secret chat '.$chat.'...', Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $this->logger->logger('Generating a...', Logger::VERBOSE);
        $a = new BigInteger(Tools::random(256), 256);
        $this->logger->logger('Generating g_a...', Logger::VERBOSE);
        $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
        Crypt::checkG($g_a, $dh_config['p']);
        $e = Tools::random(8);
        $this->temp_rekeyed_secret_chats[$e] = $a;
        $this->secret_chats[$chat]['rekeying'] = [1, $e];
        $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionRequestKey', 'g_a' => $g_a->toBytes(), 'exchange_id' => $e]]]);
        $this->updaters[UpdateLoop::GENERIC]->resume();
        return $e;
    }
    /**
     * Accept rekeying.
     *
     * @param int   $chat   Chat
     * @param array $params Parameters
     */
    private function acceptRekey(int $chat, array $params): void
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 0) {
            $my_exchange_id = new BigInteger($this->secret_chats[$chat]['rekeying'][1], -256);
            $other_exchange_id = new BigInteger($params['exchange_id'], -256);
            //$this->logger->logger($my, $params);
            if ($my_exchange_id->compare($other_exchange_id) > 0) {
                return;
            }
            if ($my_exchange_id->compare($other_exchange_id) === 0) {
                $this->secret_chats[$chat]['rekeying'] = [0];
                return;
            }
        }
        $this->logger->logger('Accepting rekeying of secret chat '.$chat.'...', Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $this->logger->logger('Generating b...', Logger::VERBOSE);
        $b = new BigInteger(Tools::random(256), 256);
        $params['g_a'] = new BigInteger((string) $params['g_a'], 256);
        Crypt::checkG($params['g_a'], $dh_config['p']);
        $key = ['auth_key' => \str_pad($params['g_a']->powMod($b, $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
        $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = $this->secret_chats[$chat]['key']['visualization_orig'];
        $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);
        $this->temp_rekeyed_secret_chats[$params['exchange_id']] = $key;
        $this->secret_chats[$chat]['rekeying'] = [2, $params['exchange_id']];
        $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
        Crypt::checkG($g_b, $dh_config['p']);
        $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAcceptKey', 'g_b' => $g_b->toBytes(), 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]]);
        $this->updaters[UpdateLoop::GENERIC]->resume();
    }
    /**
     * Commit rekeying of secret chat.
     *
     * @param int   $chat   Chat
     * @param array $params Parameters
     */
    private function commitRekey(int $chat, array $params): void
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 1 || !isset($this->temp_rekeyed_secret_chats[$params['exchange_id']])) {
            $this->secret_chats[$chat]['rekeying'] = [0];
            return;
        }
        $this->logger->logger('Committing rekeying of secret chat '.$chat.'...', Logger::VERBOSE);
        $dh_config = ($this->getDhConfig());
        $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
        Crypt::checkG($params['g_b'], $dh_config['p']);
        $key = ['auth_key' => \str_pad($params['g_b']->powMod($this->temp_rekeyed_secret_chats[$params['exchange_id']], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT)];
        $key['fingerprint'] = \substr(\sha1($key['auth_key'], true), -8);
        $key['visualization_orig'] = $this->secret_chats[$chat]['key']['visualization_orig'];
        $key['visualization_46'] = \substr(\hash('sha256', $key['auth_key'], true), 20);
        if ($key['fingerprint'] !== $params['key_fingerprint']) {
            $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]]);
            throw new SecurityException('Invalid key fingerprint!');
        }
        $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionCommitKey', 'exchange_id' => $params['exchange_id'], 'key_fingerprint' => $key['fingerprint']]]]);
        unset($this->temp_rekeyed_secret_chats[$params['exchange_id']]);
        $this->secret_chats[$chat]['rekeying'] = [0];
        $this->secret_chats[$chat]['old_key'] = $this->secret_chats[$chat]['key'];
        $this->secret_chats[$chat]['key'] = $key;
        $this->secret_chats[$chat]['ttr'] = 100;
        $this->secret_chats[$chat]['updated'] = \time();
        $this->updaters[UpdateLoop::GENERIC]->resume();
    }
    /**
     * Complete rekeying.
     *
     * @param int   $chat   Chat
     * @param array $params Parameters
     */
    private function completeRekey(int $chat, array $params): bool
    {
        if ($this->secret_chats[$chat]['rekeying'][0] !== 2 || !isset($this->temp_rekeyed_secret_chats[$params['exchange_id']]['fingerprint'])) {
            return false;
        }
        if ($this->temp_rekeyed_secret_chats[$params['exchange_id']]['fingerprint'] !== $params['key_fingerprint']) {
            $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionAbortKey', 'exchange_id' => $params['exchange_id']]]]);
            throw new SecurityException('Invalid key fingerprint!');
        }
        $this->logger->logger('Completing rekeying of secret chat '.$chat.'...', Logger::VERBOSE);
        $this->secret_chats[$chat]['rekeying'] = [0];
        $this->secret_chats[$chat]['old_key'] = $this->secret_chats[$chat]['key'];
        $this->secret_chats[$chat]['key'] = $this->temp_rekeyed_secret_chats[$params['exchange_id']];
        $this->secret_chats[$chat]['ttr'] = 100;
        $this->secret_chats[$chat]['updated'] = \time();
        unset($this->temp_rekeyed_secret_chats[$params['exchange_id']]);
        $this->methodCallAsyncRead('messages.sendEncryptedService', ['peer' => $chat, 'message' => ['_' => 'decryptedMessageService', 'action' => ['_' => 'decryptedMessageActionNoop']]]);
        $this->logger->logger('Secret chat '.$chat.' rekeyed successfully!', Logger::VERBOSE);
        return true;
    }
    /**
     * Get secret chat status.
     *
     * @param int $chat Chat ID
     * @return int One of \danog\MadelineProto\API::SECRET_EMPTY, \danog\MadelineProto\API::SECRET_REQUESTED, \danog\MadelineProto\API::SECRET_READY
     */
    public function secretChatStatus(int $chat): int
    {
        if (isset($this->secret_chats[$chat])) {
            return \danog\MadelineProto\API::SECRET_READY;
        }
        if (isset($this->temp_requested_secret_chats[$chat])) {
            return \danog\MadelineProto\API::SECRET_REQUESTED;
        }
        return \danog\MadelineProto\API::SECRET_EMPTY;
    }
    /**
     * Get secret chat.
     *
     * @param array|int $chat Secret chat ID
     */
    public function getSecretChat(array|int $chat): array
    {
        return $this->secret_chats[\is_array($chat) ? $chat['chat_id'] : $chat];
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
