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

namespace danog\MadelineProto;

/**
 * Indicates a local directory to write output into.
 *
 * Used with {@see EventHandler\Call::setOutput()} to record a call into a directory: every recorded participant
 * gets a numbered series of `<n>_<streams>.mkv` files there (`<peerId>.<n>_<streams>.mkv` in multi-party calls),
 * one per combination of audio, camera video and screen share they send.
 */
final class LocalDirectory
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $dir
    ) {
    }
}
