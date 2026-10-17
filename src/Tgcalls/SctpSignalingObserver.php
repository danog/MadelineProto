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

/**
 * The two endpoints a {@see SignalingSctpTransport} needs: where to put its outgoing SCTP packets,
 * and where to deliver the reassembled signaling messages it receives.
 *
 * {@see SignalingSctpTransport} keeps a reference to its observer as part of its serializable state,
 * so this has to be a real, serializable object rather than `Closure`s (which PHP cannot serialize):
 * the association survives a serialize/unserialize cycle, and so must both of its callbacks.
 *
 * @internal
 * @psalm-mutable
 */
interface SctpSignalingObserver
{
    /**
     * Put one SCTP packet of the signaling association on the wire.
     * @psalm-impure
     */
    public function deliverSignalingPacket(string $packet): void;

    /**
     * Handle one reassembled signaling message received over the association.
     * @psalm-impure
     */
    public function onSignalingMessageData(string $message): void;
}
