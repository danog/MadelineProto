<?php declare(strict_types=1);

/**
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

// IMPORTANT NOTE: Please keep the above copyright notice intact if copying or rewriting this file in another language.

namespace danog\MadelineProto\Tgcalls;

use danog\MadelineProto\MTProtoTools\Crypt;
use Revolt\EventLoop;

/**
 * Reliable, encrypted, sequenced message layer used by the tgcalls signaling channel.
 *
 * This is a port of tgcalls' `EncryptedConnection` (`Type::Signaling` variant, raw message mode),
 * used to carry the JSON signaling messages of one-to-one calls over
 * [phone.sendSignalingData](https://core.telegram.org/method/phone.sendSignalingData).
 *
 * @internal
 */
final class EncryptedConnection
{
    private const SINGLE_MESSAGE_PACKET_SEQ_BIT = 1 << 31;
    private const MESSAGE_REQUIRES_ACK_SEQ_BIT = 1 << 30;
    private const MAX_ALLOWED_COUNTER = 0x3FFFFFFF;

    private const ACK_SERIALIZED_SIZE = 5;
    private const NOT_ACKED_MESSAGES_LIMIT = 64 * 1024;
    private const MAX_INCOMING_PACKET_SIZE = 128 * 1024;
    private const MAX_SIGNALING_PACKET_SIZE = 16 * 1024;
    private const KEEP_INCOMING_COUNTERS_COUNT = 64;
    private const MAX_MESSAGE_SIZE = 1024 * 1024;

    /** Service packet was requested because we have postponed ACKs to flush. */
    public const SERVICE_CAUSE_ACKS = 1;
    /** Service packet was requested because we have unacked messages to resend. */
    public const SERVICE_CAUSE_RESEND = 2;

    private const ACK_ID = 255;
    private const EMPTY_ID = 254;
    private const CUSTOM_ID = 127;

    private const MIN_DELAY_BEFORE_MESSAGE_RESEND = 3.0;
    private const MAX_DELAY_BEFORE_MESSAGE_RESEND = 5.0;
    private const MAX_DELAY_BEFORE_ACK_RESEND = 5.0;

    private int $counter = 0;

    /**
     * Messages we sent that were not ACKed yet, as `[serialized, lastSentTimestamp]`.
     *
     * @var list<array{string, float}>
     */
    private array $notYetAckedMessages = [];
    /**
     * Sequence numbers of incoming messages whose ACK we still have to send.
     *
     * @var list<int>
     */
    private array $acksToSendSeqs = [];
    /**
     * Sorted list of counters of incoming messages we already ACKed.
     *
     * @var list<int>
     */
    private array $acksSentCounters = [];
    /**
     * Sorted list of the largest incoming counters we've seen, for replay protection.
     *
     * @var list<int>
     */
    private array $largestIncomingCounters = [];

    private bool $sendAcksTimerActive = false;
    private bool $resendTimerActive = false;

    /**
     * @param string                   $authKey  The 256-byte call auth key.
     * @param bool                      $outgoing Whether we are the caller.
     * @param SignalingServiceObserver $owner    Notified via
     *                             {@see SignalingServiceObserver::onServiceRequest()} with a
     *                             `SERVICE_CAUSE_*` constant when a service packet must be emitted.
     *                             A serializable object rather than a `Closure`, so the reliability
     *                             layer survives the serialize/unserialize cycle intact.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $authKey,
        private readonly bool $outgoing,
        private readonly SignalingServiceObserver $owner,
    ) {
    }

    /**
     * The reliability layer is plain serializable state, but its ACK and resend timers are
     * event-loop callbacks that do not survive serialization. Clear the "timer active" flags and,
     * if anything is still waiting to be acked or resent, arm a fresh service packet so delivery
     * resumes on the restored connection.
     */
    public function __wakeup(): void
    {
        $this->sendAcksTimerActive = false;
        $this->resendTimerActive = false;
        if ($this->haveAdditionalMessages()) {
            $this->resendTimerActive = true;
            $this->scheduleService(self::MAX_DELAY_BEFORE_MESSAGE_RESEND, self::SERVICE_CAUSE_RESEND);
        }
    }

    /**
     * Serialize, frame and encrypt a raw signaling message.
     *
     * @return ?string The full encrypted packet, or null if it could not be prepared.
     */
    public function prepareForSendingRawMessage(string $message, bool $requiresAck): ?string
    {
        // If a message requires an ACK it may later be resent as part of a bigger packet,
        // so it can't be serialized as a single-message packet.
        $singleMessagePacket = !$this->haveAdditionalMessages() && !$requiresAck;
        $seq = $this->computeNextSeq($requiresAck, $singleMessagePacket);
        if ($seq === null) {
            return null;
        }
        $serialized = pack('N', $seq)
            .\chr(self::CUSTOM_ID)
            .pack('N', \strlen($message))
            .$message;

        if (!$this->enoughSpaceInPacket($serialized, 0)) {
            return null;
        }
        if (!$requiresAck) {
            $serialized = $this->appendAdditionalMessages($serialized);
            return $this->encryptPrepared($serialized);
        }
        $sendEnqueued = $this->notYetAckedMessages !== [];
        if (!$sendEnqueued) {
            $withAdditional = $this->appendAdditionalMessages($serialized);
        }
        $this->notYetAckedMessages[] = [$serialized, microtime(true)];
        if (!$sendEnqueued) {
            \assert(isset($withAdditional));
            return $this->encryptPrepared($withAdditional);
        }
        // All messages requiring an ACK must always be sent in order within one packet,
        // starting with the least recent not-yet-acked one: flush them all in a service packet.
        foreach ($this->notYetAckedMessages as &$queued) {
            $queued[1] = 0.0;
        }
        unset($queued);
        return $this->prepareForSendingService(0);
    }

    /**
     * Encrypt a message without the reliability framing.
     *
     * Newer tgcalls protocol versions carry signaling over an already-reliable channel, so the
     * packet is just a sequence number followed by the payload (tgcalls' `encryptRawPacket`).
     */
    public function encryptRawPacket(string $message): string
    {
        return $this->encryptPrepared(pack('N', ++$this->counter).$message);
    }

    /**
     * Decrypt a message produced by {@see self::encryptRawPacket()}.
     *
     * @return ?string The payload, or null if the packet is forged, replayed or malformed.
     */
    public function decryptRawPacket(string $data): ?string
    {
        $size = \strlen($data);
        if ($size < 21 || $size > self::MAX_INCOMING_PACKET_SIZE) {
            return null;
        }
        $x = Crypt::voipX($this->outgoing, true);
        $msgKey = substr($data, 0, 16);
        [$aesKey, $aesIv] = Crypt::voipKdf($msgKey, $this->authKey, $x);
        $decrypted = Crypt::ctrEncrypt(substr($data, 16), $aesKey, $aesIv);

        $msgKeyLarge = hash('sha256', substr($this->authKey, 88 + $x, 32).$decrypted, true);
        if (!hash_equals(substr($msgKeyLarge, 8, 16), $msgKey)) {
            return null;
        }
        if (\strlen($decrypted) < 4) {
            return null;
        }
        if (!$this->registerIncomingCounter(self::counterFromSeq(self::readSeq($decrypted, 0)))) {
            return null;
        }
        return substr($decrypted, 4);
    }

    /**
     * Build a service packet (an empty message carrying pending ACKs and resends).
     */
    public function prepareForSendingService(int $cause): ?string
    {
        if ($cause === self::SERVICE_CAUSE_ACKS) {
            $this->sendAcksTimerActive = false;
        } elseif ($cause === self::SERVICE_CAUSE_RESEND) {
            $this->resendTimerActive = false;
        }
        if (!$this->haveAdditionalMessages()) {
            return null;
        }
        $seq = $this->computeNextSeq(false, false);
        if ($seq === null) {
            return null;
        }
        $serialized = pack('N', $seq).\chr(self::EMPTY_ID);
        $serialized = $this->appendAdditionalMessages($serialized);
        return $this->encryptPrepared($serialized);
    }

    /**
     * Decrypt an incoming packet and extract all raw signaling messages it carries.
     *
     * @return list<string> The decrypted raw messages, in order (may be empty).
     */
    public function handleIncomingRawPacket(string $data): array
    {
        $size = \strlen($data);
        if ($size < 21 || $size > self::MAX_INCOMING_PACKET_SIZE) {
            return [];
        }
        $x = Crypt::voipX($this->outgoing, true);
        $msgKey = substr($data, 0, 16);
        [$aesKey, $aesIv] = Crypt::voipKdf($msgKey, $this->authKey, $x);
        $decrypted = Crypt::ctrEncrypt(substr($data, 16), $aesKey, $aesIv);

        $msgKeyLarge = hash('sha256', substr($this->authKey, 88 + $x, 32).$decrypted, true);
        if (!hash_equals(substr($msgKeyLarge, 8, 16), $msgKey)) {
            return [];
        }
        if (\strlen($decrypted) < 5) {
            return [];
        }

        $packetSeq = self::readSeq($decrypted, 0);
        $packetCounter = self::counterFromSeq($packetSeq);
        if (!$this->registerIncomingCounter($packetCounter)) {
            // Already handled.
            return [];
        }

        $result = [];
        $offset = 4;
        $currentSeq = $packetSeq;
        $additionalMessage = false;
        $firstMessageRequiringAck = true;
        $newRequiringAckReceived = false;
        while (true) {
            $currentCounter = self::counterFromSeq($currentSeq);
            $singleMessagePacket = ($currentSeq & self::SINGLE_MESSAGE_PACKET_SEQ_BIT) !== 0;
            if ($singleMessagePacket && $additionalMessage) {
                return $result;
            }
            $type = \ord($decrypted[$offset]);
            $offset++;
            if ($type === self::EMPTY_ID) {
                if ($additionalMessage) {
                    return $result;
                }
            } elseif ($type === self::ACK_ID) {
                if (!$additionalMessage) {
                    return $result;
                }
                $this->ackMyMessage($currentSeq);
            } elseif ($type === self::CUSTOM_ID) {
                if (\strlen($decrypted) - $offset < 4) {
                    return $result;
                }
                $length = self::readSeq($decrypted, $offset);
                $offset += 4;
                if ($length > self::MAX_MESSAGE_SIZE || \strlen($decrypted) - $offset < $length) {
                    return $result;
                }
                $message = substr($decrypted, $offset, $length);
                $offset += $length;

                $requiresAck = ($currentSeq & self::MESSAGE_REQUIRES_ACK_SEQ_BIT) !== 0;
                if ($requiresAck) {
                    $skip = !$this->registerSentAck($currentCounter, $firstMessageRequiringAck);
                    $firstMessageRequiringAck = false;
                    if (!$skip) {
                        $newRequiringAckReceived = true;
                    }
                    $this->sendAckPostponed($currentSeq);
                } else {
                    $skip = $additionalMessage
                        && ($currentCounter > $packetCounter || !$this->registerIncomingCounter($currentCounter));
                }
                if (!$skip) {
                    $result[] = $message;
                }
            } else {
                return $result;
            }
            $remaining = \strlen($decrypted) - $offset;
            if ($remaining === 0) {
                break;
            }
            if ($singleMessagePacket || $remaining < 5) {
                break;
            }
            $currentSeq = self::readSeq($decrypted, $offset);
            $offset += 4;
            $additionalMessage = true;
        }

        if ($this->acksToSendSeqs !== []) {
            if ($newRequiringAckReceived) {
                $this->scheduleService(0.0, 0);
            } elseif (!$this->sendAcksTimerActive) {
                $this->sendAcksTimerActive = true;
                $this->scheduleService(self::MAX_DELAY_BEFORE_ACK_RESEND, self::SERVICE_CAUSE_ACKS);
            }
        }

        return $result;
    }

    private function scheduleService(float $delay, int $cause): void
    {
        if ($delay <= 0.0) {
            EventLoop::queue($this->owner->onServiceRequest(...), $cause);
            return;
        }
        EventLoop::delay($delay, fn () => $this->owner->onServiceRequest($cause));
    }

    /**
     * @psalm-mutation-free
     */
    private function haveAdditionalMessages(): bool
    {
        return $this->notYetAckedMessages !== [] || $this->acksToSendSeqs !== [];
    }

    /**
     * @psalm-external-mutation-free
     */
    private function computeNextSeq(bool $requiresAck, bool $singleMessagePacket): ?int
    {
        if ($requiresAck && \count($this->notYetAckedMessages) >= self::NOT_ACKED_MESSAGES_LIMIT) {
            return null;
        }
        if ($this->counter === self::MAX_ALLOWED_COUNTER) {
            return null;
        }
        return (++$this->counter)
            | ($singleMessagePacket ? self::SINGLE_MESSAGE_PACKET_SEQ_BIT : 0)
            | ($requiresAck ? self::MESSAGE_REQUIRES_ACK_SEQ_BIT : 0);
    }

    /**
     * @psalm-pure
     */
    private function enoughSpaceInPacket(string $buffer, int $amount): bool
    {
        return $amount < self::MAX_SIGNALING_PACKET_SIZE
            && 16 + \strlen($buffer) + $amount <= self::MAX_SIGNALING_PACKET_SIZE;
    }

    private function appendAdditionalMessages(string $buffer): string
    {
        // ACKs first.
        $sent = 0;
        foreach ($this->acksToSendSeqs as $seq) {
            if (!$this->enoughSpaceInPacket($buffer, self::ACK_SERIALIZED_SIZE)) {
                break;
            }
            $buffer .= pack('N', $seq).\chr(self::ACK_ID);
            $sent++;
        }
        $this->acksToSendSeqs = \array_slice($this->acksToSendSeqs, $sent);

        if ($this->notYetAckedMessages === []) {
            return $buffer;
        }

        $now = microtime(true);
        foreach ($this->notYetAckedMessages as &$resending) {
            [$data, $lastSent] = $resending;
            $when = $lastSent > 0.0 ? $lastSent + self::MIN_DELAY_BEFORE_MESSAGE_RESEND : 0.0;
            if ($when > $now) {
                break;
            }
            if (!$this->enoughSpaceInPacket($buffer, \strlen($data))) {
                break;
            }
            $buffer .= $data;
            $resending[1] = $now;
        }
        unset($resending);

        if (!$this->resendTimerActive) {
            $this->resendTimerActive = true;
            $this->scheduleService(self::MAX_DELAY_BEFORE_MESSAGE_RESEND, self::SERVICE_CAUSE_RESEND);
        }
        return $buffer;
    }

    private function encryptPrepared(string $buffer): string
    {
        $x = Crypt::voipX(!$this->outgoing, true);
        $msgKeyLarge = hash('sha256', substr($this->authKey, 88 + $x, 32).$buffer, true);
        $msgKey = substr($msgKeyLarge, 8, 16);
        [$aesKey, $aesIv] = Crypt::voipKdf($msgKey, $this->authKey, $x);
        return $msgKey.Crypt::ctrEncrypt($buffer, $aesKey, $aesIv);
    }

    private function registerIncomingCounter(int $incomingCounter): bool
    {
        $list = $this->largestIncomingCounters;
        $largest = $list === [] ? 0 : $list[\count($list) - 1];
        if (\in_array($incomingCounter, $list, true)) {
            return false;
        }
        if ($incomingCounter + self::KEEP_INCOMING_COUNTERS_COUNT <= $largest) {
            // Too old.
            return false;
        }
        $list = array_values(array_filter(
            $list,
            static fn (int $counter): bool => $counter + self::KEEP_INCOMING_COUNTERS_COUNT > $incomingCounter
        ));
        $list[] = $incomingCounter;
        sort($list);
        $this->largestIncomingCounters = $list;
        return true;
    }

    private function registerSentAck(int $counter, bool $firstInPacket): bool
    {
        $list = $this->acksSentCounters;
        $already = \in_array($counter, $list, true);
        if ($firstInPacket) {
            $list = array_values(array_filter($list, static fn (int $v): bool => $v >= $counter));
        }
        if (!$already) {
            $list[] = $counter;
            sort($list);
        }
        $this->acksSentCounters = $list;
        return !$already;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function sendAckPostponed(int $incomingSeq): void
    {
        if (!\in_array($incomingSeq, $this->acksToSendSeqs, true)) {
            $this->acksToSendSeqs[] = $incomingSeq;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    private function ackMyMessage(int $seq): void
    {
        foreach ($this->notYetAckedMessages as $k => [$data]) {
            if (self::readSeq($data, 0) === $seq) {
                unset($this->notYetAckedMessages[$k]);
                $this->notYetAckedMessages = array_values($this->notYetAckedMessages);
                return;
            }
        }
    }

    /**
     * @psalm-pure
     */
    private static function counterFromSeq(int $seq): int
    {
        return $seq & ~self::SINGLE_MESSAGE_PACKET_SEQ_BIT & ~self::MESSAGE_REQUIRES_ACK_SEQ_BIT;
    }

    /**
     * @psalm-pure
     */
    private static function readSeq(string $data, int $offset): int
    {
        /** @var array{1: int} */
        $res = unpack('N', substr($data, $offset, 4));
        return $res[1];
    }
}
