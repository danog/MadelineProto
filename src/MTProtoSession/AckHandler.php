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

use Amp\TimeoutException;
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
     * We have gotten a response for an outgoing message.
     */
    public function gotResponseForOutgoingMessage(MTProtoOutgoingMessage $outgoingMessage): void
    {
        // The server acknowledges that it received my message
        unset($this->new_outgoing[$outgoingMessage->getMsgId()]);
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

    /**
     * Check if there are some pending calls.
     */
    public function hasPendingCalls(): bool
    {
        $timeout = (int) ($this->shared->getSettings()->getTimeout() * 1_000_000_000.0);
        $pfs = $this->shared->getGenericSettings()->getAuth()->getPfs();
        $unencrypted = !$this->shared->hasTempAuthKey();
        $notBound = !$this->shared->isBound();
        $pfsNotBound = $pfs && $notBound;
        /** @var MTProtoOutgoingMessage */
        foreach ($this->new_outgoing as $message) {
            if ($message->wasSent()
                && $message->getSent() + $timeout < hrtime(true)
                && $message->unencrypted === $unencrypted
                && $message->constructor !== 'msgs_state_req') {
                if (!$unencrypted && $pfsNotBound && $message->constructor !== 'auth.bindTempAuthKey') {
                    continue;
                }
                return true;
            }
        }
        return false;
    }
    /**
     * Get all pending calls (also clear pending state requests).
     */
    public function getPendingCalls(): array
    {
        $settings = $this->shared->getSettings();
        $global = $this->shared->getGenericSettings();
        $dropTimeout = (int) ($global->getRpc()->getRpcDropTimeout() * 1_000_000_000.0);
        $timeout = (int) ($settings->getTimeout() * 1_000_000_000.0);
        $pfs = $global->getAuth()->getPfs();
        $unencrypted = !$this->shared->hasTempAuthKey();
        $notBound = !$this->shared->isBound();
        $pfsNotBound = $pfs && $notBound;
        if ($this->datacenter < 0) {
            $dropTimeout *= 10;
        }
        $result = [];
        /** @var MTProtoOutgoingMessage $message */
        foreach ($this->new_outgoing as $message_id => $message) {
            if ($message->wasSent()
                && $message->getSent() + $timeout < hrtime(true)
                && $message->unencrypted === $unencrypted
            ) {
                if (!$unencrypted && $pfsNotBound && $message->constructor !== 'auth.bindTempAuthKey') {
                    continue;
                }
                if ($message->constructor === 'msgs_state_req' || $message->constructor === 'ping_delay_disconnect'
                    || ($message->getSent() + $dropTimeout < hrtime(true))
                ) {
                    Logger::log('No reply for message: ' . $message, Logger::WARNING);
                    $this->handleReject($message, static fn () => new TimeoutException('Request timeout'));
                    continue;
                }
                if ($message->getState() & MTProtoOutgoingMessage::STATE_REPLIED) {
                    $this->API->logger("Already replied to message $message, but still in new_outgoing");
                    unset($this->new_outgoing[$message_id]);
                    continue;
                }
                $result[] = $message_id;
            }
        }
        return $result;
    }
}
