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

namespace danog\MadelineProto\EventHandler\Participant\Rights;

use danog\MadelineProto\EventHandler\Participant\Rights;

/**
 * Represents the rights of an admin in a [channel/supergroup](https://core.telegram.org/api/channel).
 */
final class Admin extends Rights
{
    /** If set, allows the admin to modify the description of the [channel/supergroup](https://core.telegram.org/api/channel) */
    public readonly bool $changeInfo;

    /** If set, allows the admin to post messages in the [channel](https://core.telegram.org/api/channel) */
    public readonly bool $postMessages;

    /** If set, allows the admin to also edit messages from other admins in the [channel](https://core.telegram.org/api/channel) */
    public readonly bool $editMessages;

    /** If set, allows the admin to also delete messages from other admins in the [channel](https://core.telegram.org/api/channel) */
    public readonly bool $deleteMessages;

    /** If set, allows the admin to ban users from the [channel/supergroup](https://core.telegram.org/api/channel) */
    public readonly bool $banUsers;

    /** If set, allows the admin to invite users in the [channel/supergroup](https://core.telegram.org/api/channel) */
    public readonly bool $inviteUsers;

    /** If set, allows the admin to pin messages in the [channel/supergroup](https://core.telegram.org/api/channel) */
    public readonly bool $pinMessages;

    /** If set, allows the admin to add other admins with the same (or more limited) permissions in the [channel/supergroup](https://core.telegram.org/api/channel) */
    public readonly bool $addAdmins;

    /** Whether this admin is anonymous */
    public readonly bool $anonymous;

    /** If set, allows the admin to change group call/livestream settings */
    public readonly bool $manageCall;

    /** Set this flag if none of the other flags are set,
     * but you still want the user to be an admin: if this or any of the other flags are set,
     * the admin can get the chat [admin log](https://core.telegram.org/api/recent-actions), get [chat statistics](https://core.telegram.org/api/stats), get [message statistics in channels](https://core.telegram.org/api/stats), get channel members,
     * see anonymous administrators in supergroups and ignore slow mode.
     */
    public readonly bool $other;

    /** If set, allows the admin to create, delete or modify [forum topics »](https://core.telegram.org/api/forum#forum-topics). */
    public readonly bool $manageTopics;

    /** @internal */
    public function __construct(
        array $rawRights
    ) {
        $this->changeInfo = $rawRights['change_info'];
        $this->postMessages = $rawRights['post_messages'];
        $this->editMessages = $rawRights['edit_messages'];
        $this->deleteMessages = $rawRights['delete_messages'];
        $this->banUsers = $rawRights['ban_users'];
        $this->inviteUsers = $rawRights['invite_users'];
        $this->pinMessages = $rawRights['pin_messages'];
        $this->addAdmins = $rawRights['add_admins'];
        $this->anonymous = $rawRights['anonymous'];
        $this->manageCall = $rawRights['manage_call'];
        $this->other = $rawRights['other'];
        $this->manageTopics = $rawRights['manage_topics'];
    }
}
