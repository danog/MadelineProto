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

use Webrtc\DataChannel\RTCSctpTransportInterface;
use Webrtc\ICE\Enum\IceRole;
use Webrtc\ICE\RTCIceTransportInterface;
use Webrtc\SCTP\RTCSctpDtlsTransportInterface;
use Webrtc\SCTP\RTCSctpTransport;
use Webrtc\SCTP\SignalingSinkInterface;
use Webrtc\Stats\enum\TLSState;

/**
 * Carries the tgcalls signaling channel over an SCTP association.
 *
 * The newest protocol versions (11.0.0 and up) do not send signaling messages as bare datagrams:
 * they run a whole SCTP association whose packets travel inside
 * [phone.sendSignalingData](https://core.telegram.org/method/phone.sendSignalingData), and send
 * the (already encrypted) messages as ordered DATA chunks on stream 0. That gives the signaling
 * channel retransmission and ordering without the ad-hoc framing older versions use.
 *
 * The vendored SCTP stack normally sits on top of DTLS, and only needs four things from whatever
 * is underneath it, so this pretends to be that transport and points it at the signaling channel
 * instead.
 *
 * @internal
 */
final class SignalingSctpTransport implements RTCSctpDtlsTransportInterface, SignalingSinkInterface
{
    /** Both ends of the signaling association use this port, as tgcalls does. */
    public const PORT = 5000;
    /** The single stream every signaling message is sent on. */
    private const STREAM_ID = 0;
    /** WebRTC binary payload protocol identifier. */
    private const PPID_BINARY = 53;

    private ?RTCSctpTransportInterface $sctpReceiver = null;
    private RTCSctpTransport $sctp;

    /** Messages queued until the association is established. @var list<string> */
    private array $pending = [];
    private bool $closed = false;

    /**
     * @param bool                  $outgoing Whether we are the caller, i.e. the side that
     *                                        initiates the association.
     * @param SctpSignalingObserver $owner    Reassembled signaling messages are delivered to
     *                             {@see SctpSignalingObserver::onSignalingMessageData()} and outgoing
     *                             SCTP packets to {@see SctpSignalingObserver::deliverSignalingPacket()}.
     *                             A serializable object rather than `Closure`s, so the association
     *                             survives the serialize/unserialize cycle intact.
     */
    public function __construct(
        private readonly bool $outgoing,
        private readonly SctpSignalingObserver $owner,
    ) {
        $this->sctp = new RTCSctpTransport($this, self::PORT);
        // The signaling sink is part of the transport's serializable state, so it must be a real
        // invokable object; this transport is that object (see self::__invoke()).
        $this->sctp->setSignalingSink($this);
        // The caller drives the association, exactly like tgcalls' SignalingSctpConnection.
        $this->sctp->start(self::PORT);
    }

    /**
     * Deliver one reassembled signaling message to the controller, as {@see SignalingSinkInterface}.
     */
    #[\Override]
    public function __invoke(string $data): void
    {
        if (!$this->closed) {
            $this->owner->onSignalingMessageData($data);
        }
    }

    /**
     * Hand one SCTP packet, received over the signaling channel, to the association.
     */
    public function receive(string $packet): void
    {
        if ($this->closed) {
            return;
        }
        $this->sctpReceiver?->onReceived($packet);
        $this->flush();
    }

    /**
     * Send one signaling message as an ordered DATA chunk.
     */
    public function send(string $message): void
    {
        if ($this->closed) {
            return;
        }
        $this->pending[] = $message;
        $this->flush();
    }

    /**
     * Try to hand everything queued to the association.
     */
    private function flush(): void
    {
        if ($this->pending === [] || $this->closed) {
            return;
        }
        if (!$this->sctp->isEstablished()) {
            return;
        }
        $queued = $this->pending;
        $this->pending = [];
        foreach ($queued as $message) {
            $this->sctp->sendSignaling(self::STREAM_ID, self::PPID_BINARY, $message);
        }
    }

    /**
     * Whether the association is up and messages are flowing.
     */
    public function isEstablished(): bool
    {
        return !$this->closed && $this->sctp->isEstablished();
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->pending = [];
        $this->sctp->stop();
    }

    // ----------------------------------------------- RTCSctpDtlsTransportInterface

    /**
     * The signaling channel is always usable: the API call underneath it is reliable by itself.
     */
    public function getState(): TLSState
    {
        return $this->closed ? TLSState::CLOSED : TLSState::CONNECTED;
    }

    /**
     * SCTP decides which side is the client from the ICE role, so report the call direction.
     */
    public function getIceTransport(): RTCIceTransportInterface
    {
        return new SignalingIceRole($this->outgoing ? IceRole::Controlling : IceRole::Controlled);
    }

    public function setSctpReceiver(?RTCSctpTransportInterface $sctpReceiver = null): void
    {
        $this->sctpReceiver = $sctpReceiver;
    }

    public function removeSctpReceiver(RTCSctpTransport $param): void
    {
        $this->sctpReceiver = null;
    }

    /**
     * Put one SCTP packet on the signaling channel.
     */
    public function sendData(string $data): void
    {
        if (!$this->closed) {
            $this->owner->deliverSignalingPacket($data);
        }
    }
}
