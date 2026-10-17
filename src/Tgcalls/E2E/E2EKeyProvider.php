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

namespace danog\MadelineProto\Tgcalls\E2E;

/**
 * Supplies the live encryption keys of an end-to-end encrypted conference to the {@see FrameCryptor}.
 *
 * Implemented as an interface (not closures) so the frame cryptor stays serializable with the rest of
 * the call graph.
 *
 * @internal
 */
interface E2EKeyProvider
{
    /**
     * The currently active epochs, each an `['hash' => 32-byte block hash, 'secret' => 32-byte group
     * key]`, most-recent first. A packet is encrypted for all of them and decrypted with whichever the
     * receiver holds.
     *
     * @return list<array{hash: string, secret: string}>
     */
    public function activeEpochs(): array;

    /** Our own Ed25519 signing seed, for signing outgoing packets. */
    public function selfSeed(): string;

    /**
     * The Ed25519 public key of the participant sending media on a given SSRC, to verify incoming
     * packets, or null if the sender is unknown.
     */
    public function publicKeyForSsrc(int $ssrc): ?string;
}
