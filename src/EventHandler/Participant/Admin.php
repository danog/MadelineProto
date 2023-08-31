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
use danog\MadelineProto\EventHandler\Participant\Rights\Admin as AdminRights;

/**
 * Admin.
 */
final class Admin extends Participant
{
    /** Can this admin promote other admins with the same permissions? */
    public readonly bool $canEdit;

    /** Is this the current user */
    public readonly bool $self;

    /** Admin user ID */
    public readonly int $userId;

    /** User that invited the admin to the channel/group */
    public readonly ?int $inviterId;

    /** User that promoted the user to admin */
    public readonly int $promotedBy;

    /** When did the user join */
    public readonly int $date;

    /** Admin [rights](https://core.telegram.org/api/rights) */
    public readonly AdminRights $adminRights;

    /** The role (rank) of the admin in the group: just an arbitrary string, `admin` by default */
    public readonly string $rank;

    /** @internal */
    public function __construct(
        array $rawParticipant
    ) {
        $this->canEdit = $rawParticipant['can_edit'];
        $this->self = $rawParticipant['self'];
        $this->userId = $rawParticipant['user_id'];
        $this->inviterId = $rawParticipant['inviter_id'] ?? null;
        $this->promotedBy = $rawParticipant['promoted_by'];
        $this->date = $rawParticipant['date'];
        $this->rank = $rawParticipant['rank'] ?? 'admin';
        $this->adminRights = new AdminRights($rawParticipant['admin_rights']);
    }
}
