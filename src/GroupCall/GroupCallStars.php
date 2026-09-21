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

namespace danog\MadelineProto\GroupCall;

use JsonSerializable;

/**
 * The [Telegram Stars donations](https://core.telegram.org/api/group-calls#paid-live-story-donations)
 * received by a live story, from [phone.groupCallStars](https://core.telegram.org/constructor/phone.groupCallStars).
 *
 * @psalm-immutable
 */
final class GroupCallStars implements JsonSerializable
{
    /**
     * @internal
     *
     * @param list<GroupCallDonor> $topDonors
     *
     * @psalm-mutation-free
     */
    public function __construct(
        /** Total Telegram Stars donated so far. */
        public readonly int $totalStars,
        /**
         * The top donors.
         *
         * @var list<GroupCallDonor>
         */
        public readonly array $topDonors,
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
