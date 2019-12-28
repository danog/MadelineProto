<?php

/**
 * SeqNoHandler module.
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
 * Manages sequence numbers.
 */
trait SeqNoHandler
{
    private function checkSecretInSeqNo($chat_id, $seqno)
    {
        $seqno = ($seqno - $this->secret_chats[$chat_id]['out_seq_no_x']) / 2;
        $last = 0;
        foreach ($this->secret_chats[$chat_id]['incoming'] as $message) {
            if (isset($message['decrypted_message']['in_seq_no'])) {
                if (($message['decrypted_message']['in_seq_no'] - $this->secret_chats[$chat_id]['out_seq_no_x']) / 2 < $last) {
                    yield $this->discardSecretChat($chat_id);

                    throw new \danog\MadelineProto\SecurityException('in_seq_no is not increasing');
                }
                $last = ($message['decrypted_message']['in_seq_no'] - $this->secret_chats[$chat_id]['out_seq_no_x']) / 2;
            }
        }
        if ($seqno > $this->secret_chats[$chat_id]['out_seq_no'] + 1) {
            yield $this->discardSecretChat($chat_id);

            throw new \danog\MadelineProto\SecurityException('in_seq_no is too big');
        }

        return true;
    }

    private function checkSecretOutSeqNo($chat_id, $seqno)
    {
        $seqno = ($seqno - $this->secret_chats[$chat_id]['in_seq_no_x']) / 2;
        $C = 0;
        foreach ($this->secret_chats[$chat_id]['incoming'] as $message) {
            if (isset($message['decrypted_message']['out_seq_no']) && $C < $this->secret_chats[$chat_id]['in_seq_no']) {
                if (($message['decrypted_message']['out_seq_no'] - $this->secret_chats[$chat_id]['in_seq_no_x']) / 2 !== $C) {
                    yield $this->discardSecretChat($chat_id);

                    throw new \danog\MadelineProto\SecurityException('out_seq_no hole: should be '.$C.', is '.($message['decrypted_message']['out_seq_no'] - $this->secret_chats[$chat_id]['in_seq_no_x']) / 2);
                }
                $C++;
            }
        }
        //$this->logger->logger($C, $seqno);
        if ($seqno < $C) {
            // <= C
            $this->logger->logger('WARNING: dropping repeated message with seqno '.$seqno);

            return false;
        }
        if ($seqno > $C) {
            // > C+1
            yield $this->discardSecretChat($chat_id);

            throw new \danog\MadelineProto\SecurityException('WARNING: out_seq_no gap detected ('.$seqno.' > '.$C.')!');
        }

        return true;
    }

    private function generateSecretInSeqNo($chat)
    {
        return $this->secret_chats[$chat]['layer'] > 8 ? $this->secret_chats[$chat]['in_seq_no'] * 2 + $this->secret_chats[$chat]['in_seq_no_x'] : -1;
    }

    private function generateSecretOutSeqNo($chat)
    {
        return $this->secret_chats[$chat]['layer'] > 8 ? $this->secret_chats[$chat]['out_seq_no'] * 2 + $this->secret_chats[$chat]['out_seq_no_x'] : -1;
    }
}
