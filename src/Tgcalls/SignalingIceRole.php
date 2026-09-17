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

namespace danog\MadelineProto\Tgcalls;

use danog\MadelineProto\Exception;
use Webrtc\ICE\Enum\IceRole;
use Webrtc\ICE\Listener\IceTransportDataListener;
use Webrtc\ICE\Listener\IceTransportDisconnectListener;
use Webrtc\ICE\Listener\IceTransportStateChangeListener;
use Webrtc\ICE\RTCIceCandidate;
use Webrtc\ICE\RTCIceConnectionInterface;
use Webrtc\ICE\RTCIceGathererInterface;
use Webrtc\ICE\RTCIceParameters;
use Webrtc\ICE\RTCIceTransportInterface;

/**
 * Reports a fixed ICE role to the SCTP stack, which is all it needs from a transport.
 *
 * The signaling association of {@see SignalingSctpTransport} does not run over ICE at all: its
 * packets travel inside API calls. SCTP only consults the ICE role to decide which side acts as
 * the client, so this supplies that one answer and refuses everything else.
 *
 * @internal
 */
final class SignalingIceRole implements RTCIceTransportInterface
{
    public function __construct(private readonly IceRole $role)
    {
    }

    #[\Override]
    public function getRole(): IceRole
    {
        return $this->role;
    }

    #[\Override]
    public function isRoleSet(): bool
    {
        return true;
    }

    #[\Override]
    public function setRoleSet(bool $roleSet): void
    {
    }

    #[\Override]
    public function send(string $bytes): void
    {
        throw new Exception('The signaling SCTP association does not run over ICE!');
    }

    #[\Override]
    public function addRemoteCandidate(RTCIceCandidate $candidate): void
    {
        throw new Exception('The signaling SCTP association does not run over ICE!');
    }

    #[\Override]
    public function getIceGatherer(): RTCIceGathererInterface
    {
        throw new Exception('The signaling SCTP association does not run over ICE!');
    }

    #[\Override]
    public function getIceConnection(): RTCIceConnectionInterface
    {
        throw new Exception('The signaling SCTP association does not run over ICE!');
    }

    #[\Override]
    public function start(RTCIceParameters $remoteIceParameters): void
    {
        throw new Exception('The signaling SCTP association does not run over ICE!');
    }

    #[\Override]
    public function stop(): void
    {
    }

    /**
     * The signaling association carries its own DATA chunks and never sees application data over ICE,
     * so there is nothing to notify: registering a listener is a no-op.
     */
    #[\Override]
    public function addDataListener(IceTransportDataListener $listener): void
    {
    }

    #[\Override]
    public function removeDataListener(IceTransportDataListener $listener): void
    {
    }

    #[\Override]
    public function addDisconnectListener(IceTransportDisconnectListener $listener): void
    {
    }

    /**
     * The signaling association never changes ICE state (it does not run over ICE at all), so
     * there is nothing to notify: registering a state-change listener is a no-op.
     */
    #[\Override]
    public function addStateChangeListener(IceTransportStateChangeListener $listener): void
    {
    }
}
