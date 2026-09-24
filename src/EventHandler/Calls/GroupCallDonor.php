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

use JsonSerializable;

/**
 * A donor of a [live story](https://core.telegram.org/api/group-calls#live-stories), from
 * [groupCallDonor](https://core.telegram.org/constructor/groupCallDonor).
 *
 * @psalm-immutable
 */
final class GroupCallDonor implements JsonSerializable
{
    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        /** Bot API ID of the donor, or null for an anonymous donor. */
        public readonly ?int $peerId,
        /** How many Telegram Stars they donated. */
        public readonly int $stars,
        /** Whether this is one of the top donors. */
        public readonly bool $top,
        /** Whether this is us. */
        public readonly bool $my,
    ) {
    }

    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
