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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
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
    private function handleDecryptedUpdate($update)
    {
        /*if (isset($update['message']['decrypted_message']['random_bytes']) && strlen($update['message']['decrypted_message']['random_bytes']) < 15) {
              throw new \danog\MadelineProto\ResponseException(\danog\MadelineProto\Lang::$current_lang['rand_bytes_too_short']);
          }*/
        // already checked in TL.php
        switch ($update['message']['decrypted_message']['_']) {
            case 'decryptedMessageService':
                switch ($update['message']['decrypted_message']['action']['_']) {
                    case 'decryptedMessageActionRequestKey':
                        yield $this->acceptRekey($update['message']['chat_id'], $update['message']['decrypted_message']['action']);

                        return;
                    case 'decryptedMessageActionAcceptKey':
                        yield $this->commitRekey($update['message']['chat_id'], $update['message']['decrypted_message']['action']);

                        return;
                    case 'decryptedMessageActionCommitKey':
                        yield $this->completeRekey($update['message']['chat_id'], $update['message']['decrypted_message']['action']);

                        return;
                    case 'decryptedMessageActionNotifyLayer':
                        $this->secret_chats[$update['message']['chat_id']]['layer'] = $update['message']['decrypted_message']['action']['layer'];
                        if ($update['message']['decrypted_message']['action']['layer'] >= 17 && \time() - $this->secret_chats[$update['message']['chat_id']]['created'] > 15) {
                            yield $this->notifyLayer($update['message']['chat_id']);
                        }
                        if ($update['message']['decrypted_message']['action']['layer'] >= 73) {
                            $this->secret_chats[$update['message']['chat_id']]['mtproto'] = 2;
                        }

                        return;
                    case 'decryptedMessageActionSetMessageTTL':
                        $this->secret_chats[$update['message']['chat_id']]['ttl'] = $update['message']['decrypted_message']['action']['ttl_seconds'];

                        yield $this->saveUpdate($update);

                        return;
                    case 'decryptedMessageActionNoop':
                        return;
                    case 'decryptedMessageActionResend':
                        $update['message']['decrypted_message']['action']['start_seq_no'] -= $this->secret_chats[$update['message']['chat_id']]['out_seq_no_x'];
                        $update['message']['decrypted_message']['action']['end_seq_no'] -= $this->secret_chats[$update['message']['chat_id']]['out_seq_no_x'];
                        $update['message']['decrypted_message']['action']['start_seq_no'] /= 2;
                        $update['message']['decrypted_message']['action']['end_seq_no'] /= 2;
                        $this->logger->logger('Resending messages for secret chat '.$update['message']['chat_id'], \danog\MadelineProto\Logger::WARNING);
                        foreach ($this->secret_chats[$update['message']['chat_id']]['outgoing'] as $seq => $message) {
                            if ($seq >= $update['message']['decrypted_message']['action']['start_seq_no'] && $seq <= $update['message']['decrypted_message']['action']['end_seq_no']) {
                                //throw new \danog\MadelineProto\ResponseException(\danog\MadelineProto\Lang::$current_lang['resending_unsupported']);
                                yield $this->methodCallAsyncRead('messages.sendEncrypted', ['peer' => $update['message']['chat_id'], 'message' => $update['message']['decrypted_message']], ['datacenter' => $this->datacenter->curdc]);
                            }
                        }

                        return;
                    default:
                        //                yield $this->saveUpdate(['_' => 'updateNewDecryptedMessage', 'peer' => $this->secret_chats[$update['message']['chat_id']]['InputEncryptedChat'], 'in_seq_no' => $this->get_in_seq_no($update['message']['chat_id']), 'out_seq_no' => $this->get_out_seq_no($update['message']['chat_id']), 'message' => $update['message']['decrypted_message']]);
                        yield $this->saveUpdate($update);
                }
                break;
            case 'decryptedMessage':
                yield $this->saveUpdate($update);
                break;
            case 'decryptedMessageLayer':
                if (yield $this->checkSecretOutSeqNo($update['message']['chat_id'], $update['message']['decrypted_message']['out_seq_no']) && yield $this->checkSecretInSeqNo($update['message']['chat_id'], $update['message']['decrypted_message']['in_seq_no'])) {
                    $this->secret_chats[$update['message']['chat_id']]['in_seq_no']++;
                    if ($update['message']['decrypted_message']['layer'] >= 17) {
                        $this->secret_chats[$update['message']['chat_id']]['layer'] = $update['message']['decrypted_message']['layer'];
                        if ($update['message']['decrypted_message']['layer'] >= 17 && \time() - $this->secret_chats[$update['message']['chat_id']]['created'] > 15) {
                            yield $this->notifyLayer($update['message']['chat_id']);
                        }
                    }
                    $update['message']['decrypted_message'] = $update['message']['decrypted_message']['message'];
                    yield $this->handleDecryptedUpdate($update);
                }
                break;
            default:
                throw new \danog\MadelineProto\ResponseException(\danog\MadelineProto\Lang::$current_lang['unrecognized_dec_msg'].\var_export($update, true));
                break;
        }
    }
}
