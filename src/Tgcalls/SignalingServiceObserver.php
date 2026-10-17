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
 * Notified by {@see EncryptedConnection} when a service packet (ACKs, resends) must be emitted.
 *
 * {@see EncryptedConnection} keeps a reference to its observer as part of its serializable state, so
 * this has to be a real, serializable object rather than a `Closure` (which PHP cannot serialize):
 * the signaling reliability layer survives a serialize/unserialize cycle, and so must its callback.
 *
 * @internal
 * @psalm-mutable
 */
interface SignalingServiceObserver
{
    /**
     * Emit a service packet requested by the reliability layer.
     *
     * @param int $cause One of the `EncryptedConnection::SERVICE_CAUSE_*` constants (0 for an
     *                   immediate flush).
     * @psalm-impure
     */
    public function onServiceRequest(int $cause): void;
}
