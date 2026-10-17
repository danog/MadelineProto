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

namespace danog\MadelineProto\EventHandler\Calls;

/**
 * A participant of an end-to-end encrypted {@see ConferenceCall}, as reconstructed from the
 * [shared-state chain »](https://core.telegram.org/api/end-to-end/group-calls).
 */
final class ConferenceCallParticipant extends MultiCallParticipant
{
    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        int $peerId,
        /** The participant's Ed25519 public key. */
        public readonly string $publicKey,
        /** The participant's permission bits in the shared-state chain. */
        public readonly int $permissions,
        /** The protocol version of the shared-state block that last updated this participant. */
        public readonly int $version,
    ) {
        parent::__construct($peerId);
    }
}
