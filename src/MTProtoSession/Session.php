<?php

declare(strict_types=1);

/**
 * Session module.
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

use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\Tools;

/**
 * Manages MTProto session-specific data.
 *
 * @internal
 */
trait Session
{
    use AuthKeyHandler;
    use AckHandler;
    use ResponseHandler;
    use SeqNoHandler;
    use CallHandler;
    use Reliable;
    /**
     * Incoming message array.
     *
     * @var array<MTProtoIncomingMessage>
     */
    public array $incoming_messages = [];
    /**
     * Outgoing message array.
     *
     * @var array<MTProtoOutgoingMessage>
     */
    public array $outgoing_messages = [];
    /**
     * New incoming message ID array.
     *
     * @var array<MTProtoIncomingMessage>
     */
    public array $new_incoming = [];
    /**
     * New outgoing message array.
     *
     * @var array<MTProtoOutgoingMessage>
     */
    public array $new_outgoing = [];
    /**
     * Pending outgoing messages.
     *
     * @var array<MTProtoOutgoingMessage>
     */
    public array $pendingOutgoing = [];
    /**
     * Pending outgoing key.
     *
     */
    public int $pendingOutgoingKey = 0;
    /**
     * Time delta with server.
     *
     */
    public int $time_delta = 0;
    /**
     * Call queue.
     *
     */
    public array $call_queue = [];
    /**
     * Ack queue.
     *
     */
    public array $ack_queue = [];
    /**
     * Message ID handler.
     *
     */
    public MsgIdHandler $msgIdHandler;
    /**
     * Reset MTProto session.
     */
    public function resetSession(): void
    {
        $this->API->logger->logger("Resetting session in DC {$this->datacenterId}...", Logger::WARNING);
        $this->session_id = Tools::random(8);
        $this->session_in_seq_no = 0;
        $this->session_out_seq_no = 0;
        $this->msgIdHandler ??= new MsgIdHandler($this);
        foreach ($this->outgoing_messages as &$msg) {
            if ($msg->hasMsgId()) {
                $msg->setMsgId(null);
            }
            if ($msg->hasSeqNo()) {
                $msg->setSeqNo(null);
            }
        }
    }
    /**
     * Cleanup incoming and outgoing messages.
     */
    public function cleanupSession(): void
    {
        $count = 0;
        $incoming = [];
        foreach ($this->incoming_messages as $key => $message) {
            if ($message->canGarbageCollect()) {
                $count++;
            } else {
                $this->API->logger->logger("Can't garbage collect $message in DC {$this->datacenter}, not handled yet!", Logger::VERBOSE);
                $incoming[$key] = $message;
            }
        }
        $this->incoming_messages = $incoming;
        $total = \count($this->incoming_messages);
        if ($count+$total) {
            $this->API->logger->logger("Garbage collected $count incoming messages in DC {$this->datacenter}, $total left", Logger::VERBOSE);
        }

        $count = 0;
        $outgoing = [];
        foreach ($this->outgoing_messages as $key => $message) {
            if ($message->canGarbageCollect()) {
                $count++;
            } else {
                $ago = \time() - $message->getSent();
                if ($ago > 2) {
                    $this->API->logger->logger("Can't garbage collect $message in DC {$this->datacenter}, no response has been received or it wasn't yet handled!", Logger::VERBOSE);
                }
                $outgoing[$key] = $message;
            }
        }
        $this->outgoing_messages = $outgoing;
        $total = \count($this->outgoing_messages);
        if ($count+$total) {
            $this->API->logger->logger("Garbage collected $count outgoing messages in DC {$this->datacenter}, $total left", Logger::VERBOSE);
        }
    }
    /**
     * Create MTProto session if needed.
     */
    public function createSession(): void
    {
        if ($this->session_id === null) {
            $this->resetSession();
        }
    }
    /**
     * Backup eventual unsent messages before session deletion.
     *
     * @return array<MTProtoOutgoingMessage>
     */
    public function backupSession(): array
    {
        $pending = \array_values($this->pendingOutgoing);
        return \array_merge($pending, $this->new_outgoing);
    }
}
