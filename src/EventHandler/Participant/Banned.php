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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Participant;

use danog\MadelineProto\EventHandler\Participant;
use danog\MadelineProto\EventHandler\Participant\Rights\Banned as BannedRights;

/**
 * Banned/kicked user.
 */
final class Banned extends Participant
{
    /** Whether the user has left the group */
    public readonly bool $left;

    /** The banned peer */
    public readonly int $peer;

    /** User was kicked by the specified admin */
    public readonly int $kickedBy;

    /** When did the user join the group */
    public readonly int $date;

    /** Banned [rights](https://core.telegram.org/api/rights) */
    public readonly BannedRights $bannedRights;

    /** @internal */
    public function __construct(array $rawParticipant)
    {
        $this->left = $rawParticipant['left'];
        $this->kickedBy = $rawParticipant['kicked_by'];
        $this->date = $rawParticipant['date'];
        $this->bannedRights = new BannedRights($rawParticipant['banned_rights']);
        $this->peer = $rawParticipant['peer'];
    }
}
