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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\Sync\LocalKeyedMutex;
use danog\BetterPrometheus\BetterCounter;
use danog\BetterPrometheus\BetterGauge;
use danog\BetterPrometheus\BetterHistogram;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\LinkedList;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\Tools;
use SplQueue;

/**
 * Manages MTProto session-specific data.
 *
 * @internal
 */
trait Session
{
    use AuthKeyHandler;
    use ResponseHandler;
    use SeqNoHandler;
    use CallHandler;
    use Reliable;
    public ?BetterGauge $pendingOutgoingGauge = null;
    public ?BetterGauge $inFlightGauge = null;
    public ?BetterCounter $incomingCtr = null;
    public ?BetterCounter $outgoingCtr = null;
    public ?BetterCounter $incomingBytesCtr = null;
    public ?BetterCounter $outgoingBytesCtr = null;

    public ?BetterHistogram $requestLatencies = null;

    public ?BetterCounter $requestResponse = null;
    /**
     * Incoming message array.
     *
     * @var array<MTProtoIncomingMessage>
     */
    //public array $incoming_messages = [];
    /**
     * Outgoing message array.
     *
     * @var array<MTProtoOutgoingMessage>
     */
    //public array $outgoing_messages = [];
    /**
     * New incoming message ID array.
     *
     * @var SplQueue<MTProtoIncomingMessage>
     */
    public SplQueue $new_incoming;
    /**
     * New outgoing message array.
     *
     * @var array<MTProtoOutgoingMessage>
     */
    public array $new_outgoing = [];
    /**
     * New unencrypted outgoing message array.
     *
     * @var array<MTProtoOutgoingMessage>
     */
    public array $unencrypted_new_outgoing = [];

    /**
     * Pending outgoing messages.
     */
    public LinkedList $unencryptedPendingOutgoing;
    /**
     * Pending outgoing messages.
     */
    public LinkedList $uninitedPendingOutgoing;
    /**
     * Pending outgoing messages.
     */
    public LinkedList $mainPendingOutgoing;

    /**
     * Time delta with server.
     *
     */
    public int $time_delta = 0;
    /**
     * Ack queue.
     *
     * @var list<int>
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
    public function resetSession(string $why): void
    {
        $this->API->logger("Resetting session in DC {$this->datacenterId} due to $why...", Logger::WARNING);
        $this->session_id = Tools::random(8);
        $this->session_in_seq_no = 0;
        $this->session_out_seq_no = 0;
        $this->msgIdHandler ??= new MsgIdHandler($this);
        if (!isset($this->new_incoming)) {
            $q = new SplQueue;
            $q->setIteratorMode(SplQueue::IT_MODE_DELETE);
            $this->new_incoming = $q;
        }
        foreach ($this->new_outgoing as $msg) {
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
     *
     * @psalm-external-mutation-free
     */
    public function cleanupSession(): void
    {
        $new_outgoing = [];
        foreach ($this->new_outgoing as $key => $message) {
            $new_outgoing[$key] = $message;
        }
        $this->new_outgoing = $new_outgoing;

        $unencrypted_new_outgoing = [];
        foreach ($this->unencrypted_new_outgoing as $key => $message) {
            $unencrypted_new_outgoing[$key] = $message;
        }
        $this->unencrypted_new_outgoing = $unencrypted_new_outgoing;
    }
    /**
     * Create MTProto session if needed.
     */
    public function createSession(): void
    {
        $labels = ['datacenter' => (string) $this->datacenter, 'connection' => (string) $this->id];
        $this->pendingOutgoingGauge = $this->API->getPromGauge("MadelineProto", "pending_outgoing_mtproto_messages_count", "Number of not-yet sent outgoing MTProto messages", $labels);
        $this->inFlightGauge = $this->API->getPromGauge("MadelineProto", "inflight_requests_count", "Number of in-flight requests", $labels);
        $this->incomingCtr = $this->API->getPromCounter("MadelineProto", "incoming_mtproto_messages_count", "Number of received MTProto messages", $labels);
        $this->outgoingCtr = $this->API->getPromCounter("MadelineProto", "outgoing_mtproto_messages_count", "Number of sent MTProto messages", $labels);
        $this->incomingBytesCtr = $this->API->getPromCounter("MadelineProto", "incoming_bytes_count", "Number of received bytes", $labels);
        $this->outgoingBytesCtr = $this->API->getPromCounter("MadelineProto", "outgoing_bytes_count", "Number of sent bytes", $labels);
        $this->requestResponse = $this->API->getPromCounter("MadelineProto", "request_responses_count", "Received RPC error or success status of requests by method.", $labels);
        $this->requestLatencies = $this->API->getPromHistogram(
            "MadelineProto",
            "request_latencies",
            "Successful request latency in nanoseconds by method",
            $labels,
            [
                5_000_000,
                10_000_000,
                25_000_000,
                50_000_000,
                75_000_000,
                100_000_000,
                250_000_000,
                500_000_000,
                750_000_000,
                1000_000_000,
                2500_000_000,
                5000_000_000,
                7500_000_000,
                10000_000_000,
            ]
        );
        $this->mainPendingOutgoing ??= new LinkedList;
        $this->unencryptedPendingOutgoing ??= new LinkedList;
        $this->uninitedPendingOutgoing ??= new LinkedList;
        if ($this->session_id === null) {
            $this->resetSession("creating initial session");
        }
        $this->abstractionQueueMutex ??= new LocalKeyedMutex;
    }
    /**
     * Backup eventual unsent messages before session deletion.
     *
     * @return array<MTProtoOutgoingMessage>
     *
     * @psalm-mutation-free
     */
    public function backupSession(): array
    {
        $pending = array_merge($this->new_outgoing, $this->unencrypted_new_outgoing);
        foreach ([$this->mainPendingOutgoing ?? null, $this->unencryptedPendingOutgoing ?? null, $this->uninitedPendingOutgoing ?? null] as $k => $list) {
            $message = $list;
            while ($message !== null) {
                $message = $message->prev;
                if (!$message instanceof MTProtoOutgoingMessage) {
                    break;
                }
                $pending []= $message;
            }
        }
        return $pending;
    }
}
