<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\MTProtoTools\FileRef;

/**
 * What the file reference map needs from the client: peer IDs and input peers.
 *
 * @internal
 */
interface FileRefEnvironment
{
    /**
     * The ID of the logged in user (for inputPeerSelf and inputUserSelf), null if unknown.
     */
    public function getSelfId(): ?int;

    /**
     * The bot API ID of a peer passed by the user as a method argument (an InputPeer, InputUser or InputChannel
     * constructor or a bot API ID), null if it cannot be determined without network requests.
     *
     * Called while handling responses: it must not wait for other responses.
     */
    public function getPeerId(mixed $peer): ?int;

    /**
     * The InputPeer of a bot API peer ID.
     */
    public function getInputPeerById(int $id): array;

    /**
     * The InputUser of a user ID.
     */
    public function getInputUserById(int $id): array;

    /**
     * The InputChannel of a bot API channel ID.
     */
    public function getInputChannelById(int $id): array;

    /**
     * The theming engines supported by the client (account.getTheme's format).
     */
    public function getThemeFormat(): string;
}
