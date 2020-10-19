<?php

/**
 * ResponseHandler module.
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

namespace danog\MadelineProto\SecretChats;

/**
 * Manages responses.
 */
trait ResponseHandler
{
    private function handleDecryptedUpdate(array $update): \Generator
    {
        $chatId = $update['message']['chat_id'];
        $decryptedMessage = $update['message']['decrypted_message'];
        if ($decryptedMessage['_'] === 'decryptedMessage') {
            yield from $this->saveUpdate($update);
            return;
        }
        if ($decryptedMessage['_'] === 'decryptedMessageService') {
            $action = $decryptedMessage['action'];
            switch ($action['_']) {
                case 'decryptedMessageActionRequestKey':
                    yield from $this->acceptRekey($chatId, $action);
                    return;
                case 'decryptedMessageActionAcceptKey':
                    yield from $this->commitRekey($chatId, $action);
                    return;
                case 'decryptedMessageActionCommitKey':
                    yield from $this->completeRekey($chatId, $action);
                    return;
                case 'decryptedMessageActionNotifyLayer':
                    $this->secret_chats[$chatId]['layer'] = $action['layer'];
                    if ($action['layer'] >= 17 && \time() - $this->secret_chats[$chatId]['created'] > 15) {
                        yield from $this->notifyLayer($chatId);
                    }
                    if ($action['layer'] >= 73) {
                        $this->secret_chats[$chatId]['mtproto'] = 2;
                    }
                    return;
                case 'decryptedMessageActionSetMessageTTL':
                    $this->secret_chats[$chatId]['ttl'] = $action['ttl_seconds'];
                    yield from $this->saveUpdate($update);
                    return;
                case 'decryptedMessageActionNoop':
                    return;
                case 'decryptedMessageActionResend':
                    $action['start_seq_no'] -= $this->secret_chats[$chatId]['out_seq_no_x'];
                    $action['end_seq_no'] -= $this->secret_chats[$chatId]['out_seq_no_x'];
                    $action['start_seq_no'] /= 2;
                    $action['end_seq_no'] /= 2;
                    $this->logger->logger('Resending messages for secret chat '.$chatId, \danog\MadelineProto\Logger::WARNING);
                    foreach ($this->secret_chats[$chatId]['outgoing'] as $seq => $message) {
                        if ($seq >= $action['start_seq_no'] && $seq <= $action['end_seq_no']) {
                            yield from $this->methodCallAsyncRead('messages.sendEncrypted', ['peer' => $chatId, 'message' => $message]);
                        }
                    }
                    return;
                default:
                    yield from $this->saveUpdate($update);
            }
            return;
        }
        if ($decryptedMessage['_'] === 'decryptedMessageLayer') {
            if ((yield from $this->checkSecretOutSeqNo($chatId, $decryptedMessage['out_seq_no']))
                && (yield from $this->checkSecretInSeqNo($chatId, $decryptedMessage['in_seq_no']))) {
                $this->secret_chats[$chatId]['in_seq_no']++;
                if ($decryptedMessage['layer'] >= 17 && $decryptedMessage['layer'] !== $this->secret_chats[$chatId]['layer']) {
                    $this->secret_chats[$chatId]['layer'] = $decryptedMessage['layer'];
                    if ($decryptedMessage['layer'] >= 17 && \time() - $this->secret_chats[$chatId]['created'] > 15) {
                        yield from $this->notifyLayer($chatId);
                    }
                }
                $update['message']['decrypted_message'] = $decryptedMessage['message'];
                yield from $this->handleDecryptedUpdate($update);
            }
            return;
        }
        throw new \danog\MadelineProto\ResponseException('Unrecognized decrypted message received: '.\var_export($update, true));
    }
}
