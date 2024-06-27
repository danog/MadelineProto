<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;

/**
 * Manages acknowledgement of messages.
 *
 * @property DataCenterConnection $shared
 *
 * @internal
 */
trait AckHandler
{
    /**
     * Acknowledge outgoing message ID.
     */
    public function ackOutgoingMessageId(int $message_id): bool
    {
        // The server acknowledges that it received my message
        if (!isset($this->outgoing_messages[$message_id])) {
            $this->API->logger("WARNING: Couldn't find message id ".$message_id.' in the array of outgoing messages. Maybe try to increase its size?', Logger::WARNING);
            return false;
        }
        return true;
    }
    /**
     * Acknowledge incoming message ID.
     */
    public function ackIncomingMessage(MTProtoIncomingMessage $message): void
    {
        // Not exactly true, but we don't care
        $message->ack();
        $message_id = $message->getMsgId();
        // I let the server know that I received its message
        $this->ack_queue[$message_id] = $message_id;
    }
}
