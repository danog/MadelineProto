<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\Connection;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\IncomingMessage;
use danog\MadelineProto\MTProto\OutgoingMessage;

/**
 * Manages MTProto session-specific data.
 *
 * @extends Connection
 */
trait Session
{
    use AckHandler;
    use ResponseHandler;
    use SeqNoHandler;
    use CallHandler;
    use Reliable;
    /**
     * Incoming message array.
     *
     * @var IncomingMessage[]
     */
    public $incoming_messages = [];
    /**
     * Outgoing message array.
     *
     * @var OutgoingMessage[]
     */
    public $outgoing_messages = [];
    /**
     * New incoming message ID array.
     *
     * @var IncomingMessage[]
     */
    public $new_incoming = [];
    /**
     * New outgoing message array.
     *
     * @var OutgoingMessage[]
     */
    public $new_outgoing = [];
    /**
     * Pending outgoing messages.
     *
     * @var OutgoingMessage[]
     */
    public $pendingOutgoing = [];
    /**
     * Pending outgoing key.
     *
     * @var string
     */
    public $pendingOutgoingKey = 'a';
    /**
     * Time delta with server.
     *
     * @var integer
     */
    public $time_delta = 0;
    /**
     * Call queue.
     *
     * @var array
     */
    public $call_queue = [];
    /**
     * Ack queue.
     *
     * @var array
     */
    public $ack_queue = [];
    /**
     * State request queue.
     *
     * @var array
     */
    public $state_queue = [];
    /**
     * Resend request queue.
     *
     * @var array
     */
    public $resend_queue = [];
    /**
     * Message ID handler.
     *
     * @var MsgIdHandler
     */
    public $msgIdHandler;
    /**
     * Reset MTProto session.
     *
     * @return void
     */
    public function resetSession(): void
    {
        $this->API->logger->logger("Resetting session in DC {$this->datacenterId}...", \danog\MadelineProto\Logger::WARNING);
        $this->session_id = \danog\MadelineProto\Tools::random(8);
        $this->session_in_seq_no = 0;
        $this->session_out_seq_no = 0;
        if (!isset($this->msgIdHandler)) {
            $this->msgIdHandler = MsgIdHandler::createInstance($this);
        }
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
     * Create MTProto session if needed.
     *
     * @return void
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
     * @return array
     */
    public function backupSession(): array
    {
        $pending = \array_values($this->pendingOutgoing);
        return \array_merge($pending, $this->new_outgoing);
    }
}
