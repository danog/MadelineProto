<?php

/**
 * AckHandler module.
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

namespace danog\MadelineProto\MTProtoSession;

/**
 * Manages acknowledgement of messages.
 */
trait AckHandler
{
    public function ackOutgoingMessageId($message_id): bool
    {
        // The server acknowledges that it received my message
        if (!isset($this->outgoing_messages[$message_id])) {
            $this->logger->logger("WARNING: Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?', \danog\MadelineProto\Logger::WARNING);

            return false;
        }
        //$this->logger->logger("Ack-ed ".$this->outgoing_messages[$message_id]['_']." with message ID $message_id on DC $datacenter");
        /*
        if (isset($this->outgoing_messages[$message_id]['body'])) {
            unset($this->outgoing_messages[$message_id]['body']);
        }
        if (isset($this->new_outgoing[$message_id])) {
            unset($this->new_outgoing[$message_id]);
        }*/
        return true;
    }

    public function gotResponseForOutgoingMessageId($message_id): bool
    {
        // The server acknowledges that it received my message
        if (!isset($this->outgoing_messages[$message_id])) {
            $this->logger->logger("WARNING: Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?', \danog\MadelineProto\Logger::WARNING);

            return false;
        }
        if (isset($this->outgoing_messages[$message_id]['body'])) {
            unset($this->outgoing_messages[$message_id]['body']);
        }
        if (isset($this->outgoing_messages[$message_id]['serialized_body'])) {
            unset($this->outgoing_messages[$message_id]['serialized_body']);
        }
        if (isset($this->new_outgoing[$message_id])) {
            unset($this->new_outgoing[$message_id]);
        }

        return true;
    }

    public function ackIncomingMessageId($message_id): bool
    {
        // I let the server know that I received its message
        if (!isset($this->incoming_messages[$message_id])) {
            $this->logger->logger("WARNING: Couldn't find message id ".$message_id.' in the array of incoming messages. Maybe try to increase its size?', \danog\MadelineProto\Logger::WARNING);
        }
        /*if ($this->temp_auth_key['id'] === null || $this->temp_auth_key['id'] === "\0\0\0\0\0\0\0\0") {
        // || (isset($this->incoming_messages[$message_id]['ack']) && $this->incoming_messages[$message_id]['ack'])) {
        return;
        }*/
        $this->ack_queue[$message_id] = $message_id;

        return true;
    }




    /**
     * Check if there are some pending calls.
     *
     * @return boolean
     */
    public function hasPendingCalls(): bool
    {
        $settings = $this->shared->getSettings();
        $timeout = $settings['timeout'];
        $pfs = $settings['pfs'];

        foreach ($this->new_outgoing as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['sent'])
                && $this->outgoing_messages[$message_id]['sent'] + $timeout < \time()
                && $this->shared->hasTempAuthKey() === !$this->outgoing_messages[$message_id]['unencrypted']
                && $this->outgoing_messages[$message_id]['_'] !== 'msgs_state_req'
            ) {
                if ($pfs && !$this->shared->isBound() && $this->outgoing_messages[$message_id]['_'] !== 'auth.bindTempAuthKey') {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Get all pending calls.
     *
     * @return array
     */
    public function getPendingCalls(): array
    {
        $settings = $this->shared->getSettings();
        $timeout = $settings['timeout'];
        $pfs = $settings['pfs'];

        $result = [];
        foreach ($this->new_outgoing as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['sent'])
                && $this->outgoing_messages[$message_id]['sent'] + $timeout < \time()
                && $this->shared->hasTempAuthKey() === !$this->outgoing_messages[$message_id]['unencrypted']
                && $this->outgoing_messages[$message_id]['_'] !== 'msgs_state_req'
            ) {
                if ($pfs && !$this->shared->isBound() && $this->outgoing_messages[$message_id]['_'] !== 'auth.bindTempAuthKey') {
                    continue;
                }

                $result[] = $message_id;
            }
        }

        return $result;
    }
}
