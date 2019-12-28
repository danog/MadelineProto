<?php

/**
 * MessageHandler module.
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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\SecretChats;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
trait MessageHandler
{
    /**
     * Encrypt secret chat message.
     *
     * @param integer $chat_id Chat ID
     * @param array   $message Message to encrypt
     *
     * @internal
     *
     * @return \Generator
     */
    public function encryptSecretMessage(int $chat_id, array $message): \Generator
    {
        if (!isset($this->secret_chats[$chat_id])) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['secret_chat_skipping'], $chat_id));

            return false;
        }
        $message['random_id'] = \danog\MadelineProto\Tools::random(8);
        $this->secret_chats[$chat_id]['ttr']--;
        if ($this->secret_chats[$chat_id]['layer'] > 8) {
            if (($this->secret_chats[$chat_id]['ttr'] <= 0 || \time() - $this->secret_chats[$chat_id]['updated'] > 7 * 24 * 60 * 60) && $this->secret_chats[$chat_id]['rekeying'][0] === 0) {
                yield $this->rekey($chat_id);
            }
            $message = ['_' => 'decryptedMessageLayer', 'layer' => $this->secret_chats[$chat_id]['layer'], 'in_seq_no' => $this->generateSecretInSeqNo($chat_id), 'out_seq_no' => $this->generateSecretOutSeqNo($chat_id), 'message' => $message];
            $this->secret_chats[$chat_id]['out_seq_no']++;
        }
        $this->secret_chats[$chat_id]['outgoing'][$this->secret_chats[$chat_id]['out_seq_no']] = $message;
        $message = yield $this->TL->serializeObject(['type' => $constructor = $this->secret_chats[$chat_id]['layer'] === 8 ? 'DecryptedMessage' : 'DecryptedMessageLayer'], $message, $constructor, $this->secret_chats[$chat_id]['layer']);
        $message = \danog\MadelineProto\Tools::packUnsignedInt(\strlen($message)).$message;
        if ($this->secret_chats[$chat_id]['mtproto'] === 2) {
            $padding = \danog\MadelineProto\Tools::posmod(-\strlen($message), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $message .= \danog\MadelineProto\Tools::random($padding);
            $message_key = \substr(\hash('sha256', \substr($this->secret_chats[$chat_id]['key']['auth_key'], 88 + ($this->secret_chats[$chat_id]['admin'] ? 0 : 8), 32).$message, true), 8, 16);
            list($aes_key, $aes_iv) = $this->aesCalculate($message_key, $this->secret_chats[$chat_id]['key']['auth_key'], $this->secret_chats[$chat_id]['admin']);
        } else {
            $message_key = \substr(\sha1($message, true), -16);
            list($aes_key, $aes_iv) = $this->oldAesCalculate($message_key, $this->secret_chats[$chat_id]['key']['auth_key'], true);
            $message .= \danog\MadelineProto\Tools::random(\danog\MadelineProto\Tools::posmod(-\strlen($message), 16));
        }
        $message = $this->secret_chats[$chat_id]['key']['fingerprint'].$message_key.$this->igeEncrypt($message, $aes_key, $aes_iv);

        return $message;
    }

    private function handleEncryptedUpdate(array $message): \Generator
    {
        if (!isset($this->secret_chats[$message['message']['chat_id']])) {
            $this->logger->logger(\sprintf(\danog\MadelineProto\Lang::$current_lang['secret_chat_skipping'], $message['message']['chat_id']));

            return false;
        }
        $auth_key_id = \substr($message['message']['bytes'], 0, 8);
        $old = false;
        if ($auth_key_id !== $this->secret_chats[$message['message']['chat_id']]['key']['fingerprint']) {
            if (isset($this->secret_chats[$message['message']['chat_id']]['old_key']['fingerprint'])) {
                if ($auth_key_id !== $this->secret_chats[$message['message']['chat_id']]['old_key']['fingerprint']) {
                    yield $this->discardSecretChat($message['message']['chat_id']);

                    throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['fingerprint_mismatch']);
                }
                $old = true;
            } else {
                yield $this->discardSecretChat($message['message']['chat_id']);

                throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['fingerprint_mismatch']);
            }
        }
        $message_key = \substr($message['message']['bytes'], 8, 16);
        $encrypted_data = \substr($message['message']['bytes'], 24);
        if ($this->secret_chats[$message['message']['chat_id']]['mtproto'] === 2) {
            $this->logger->logger('Trying MTProto v2 decryption for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);

            try {
                $message_data = $this->tryMTProtoV2Decrypt($message_key, $message['message']['chat_id'], $old, $encrypted_data);
                $this->logger->logger('MTProto v2 decryption OK for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);
            } catch (\danog\MadelineProto\SecurityException $e) {
                $this->logger->logger('MTProto v2 decryption failed with message '.$e->getMessage().', trying MTProto v1 decryption for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);
                $message_data = $this->tryMTProtoV1Decrypt($message_key, $message['message']['chat_id'], $old, $encrypted_data);
                $this->logger->logger('MTProto v1 decryption OK for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);
                $this->secret_chats[$message['message']['chat_id']]['mtproto'] = 1;
            }
        } else {
            $this->logger->logger('Trying MTProto v1 decryption for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);

            try {
                $message_data = $this->tryMTProtoV1Decrypt($message_key, $message['message']['chat_id'], $old, $encrypted_data);
                $this->logger->logger('MTProto v1 decryption OK for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);
            } catch (\danog\MadelineProto\SecurityException $e) {
                $this->logger->logger('MTProto v1 decryption failed with message '.$e->getMessage().', trying MTProto v2 decryption for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);
                $message_data = $this->tryMTProtoV2Decrypt($message_key, $message['message']['chat_id'], $old, $encrypted_data);
                $this->logger->logger('MTProto v2 decryption OK for chat '.$message['message']['chat_id'].'...', \danog\MadelineProto\Logger::NOTICE);
                $this->secret_chats[$message['message']['chat_id']]['mtproto'] = 2;
            }
        }
        $deserialized = $this->TL->deserialize($message_data, ['type' => '']);
        $this->secret_chats[$message['message']['chat_id']]['ttr']--;
        if (($this->secret_chats[$message['message']['chat_id']]['ttr'] <= 0 || \time() - $this->secret_chats[$message['message']['chat_id']]['updated'] > 7 * 24 * 60 * 60) && $this->secret_chats[$message['message']['chat_id']]['rekeying'][0] === 0) {
            yield $this->rekey($message['message']['chat_id']);
        }
        unset($message['message']['bytes']);
        $message['message']['decrypted_message'] = $deserialized;
        $this->secret_chats[$message['message']['chat_id']]['incoming'][$this->secret_chats[$message['message']['chat_id']]['in_seq_no']] = $message['message'];
        yield $this->handleDecryptedUpdate($message);
    }

    private function tryMTProtoV1Decrypt($message_key, $chat_id, $old, $encrypted_data)
    {
        list($aes_key, $aes_iv) = $this->oldAesCalculate($message_key, $this->secret_chats[$chat_id][$old ? 'old_key' : 'key']['auth_key'], true);
        $decrypted_data = $this->igeDecrypt($encrypted_data, $aes_key, $aes_iv);
        $message_data_length = \unpack('V', \substr($decrypted_data, 0, 4))[1];
        $message_data = \substr($decrypted_data, 4, $message_data_length);
        if ($message_data_length > \strlen($decrypted_data)) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['msg_data_length_too_big']);
        }
        if ($message_key != \substr(\sha1(\substr($decrypted_data, 0, 4 + $message_data_length), true), -16)) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['msg_key_mismatch']);
        }
        if (\strlen($decrypted_data) - 4 - $message_data_length > 15) {
            throw new \danog\MadelineProto\SecurityException('difference between message_data_length and the length of the remaining decrypted buffer is too big');
        }
        if (\strlen($decrypted_data) % 16 != 0) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['length_not_divisible_16']);
        }

        return $message_data;
    }

    private function tryMTProtoV2Decrypt($message_key, $chat_id, $old, $encrypted_data)
    {
        list($aes_key, $aes_iv) = $this->aesCalculate($message_key, $this->secret_chats[$chat_id][$old ? 'old_key' : 'key']['auth_key'], !$this->secret_chats[$chat_id]['admin']);
        $decrypted_data = $this->igeDecrypt($encrypted_data, $aes_key, $aes_iv);
        $message_data_length = \unpack('V', \substr($decrypted_data, 0, 4))[1];
        $message_data = \substr($decrypted_data, 4, $message_data_length);
        if ($message_data_length > \strlen($decrypted_data)) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['msg_data_length_too_big']);
        }
        if ($message_key != \substr(\hash('sha256', \substr($this->secret_chats[$chat_id][$old ? 'old_key' : 'key']['auth_key'], 88 + ($this->secret_chats[$chat_id]['admin'] ? 8 : 0), 32).$decrypted_data, true), 8, 16)) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['msg_key_mismatch']);
        }
        if (\strlen($decrypted_data) - 4 - $message_data_length < 12) {
            throw new \danog\MadelineProto\SecurityException('padding is too small');
        }
        if (\strlen($decrypted_data) - 4 - $message_data_length > 1024) {
            throw new \danog\MadelineProto\SecurityException('padding is too big');
        }
        if (\strlen($decrypted_data) % 16 != 0) {
            throw new \danog\MadelineProto\SecurityException(\danog\MadelineProto\Lang::$current_lang['length_not_divisible_16']);
        }

        return $message_data;
    }
}
