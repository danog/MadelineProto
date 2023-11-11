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

namespace danog\MadelineProto\EventHandler\ChatInvite;

use danog\MadelineProto\EventHandler\ChatInvite;

/**
 * Represents an exported chat invite.
 */
final class ChatInviteExported extends ChatInvite
{
    /** Whether this chat invite was revoked. */
    public readonly bool $revoked;

    /** Whether this chat invite has no expiration. */
    public readonly bool $permanent;

    /** Whether users importing this invite link will have to be [approved to join the channel or group](https://core.telegram.org/api/invites#join-requests). */
    public readonly bool $requestNeeded;

    /** Chat invitation link. */
    public readonly string $link;

    /** ID of the admin that created this chat invite. */
    public readonly int $adminId;

    /** When was this chat invite last modified. */
    public readonly int $date;

    /** When was this chat invite last modified. */
    public readonly ?int $created;

    /** When does this chat invite expire. */
    public readonly ?int $expire;

    /** Maximum number of users that can join using this link. */
    public readonly ?int $limit;

    /** How many users joined using this link. */
    public readonly ?int $used;

    /** Number of users that have already used this link to join. */
    public readonly ?int $requested;

    /** Custom description for the invite link, visible only to admins. */
    public readonly ?string $title;

    /** @internal */
    public function __construct(array $rawChatInvite)
    {
        $this->revoked = $rawChatInvite['revoked'] ?? false;
        $this->permanent = $rawChatInvite['permanent'] ?? false;
        $this->requestNeeded = $rawChatInvite['request_needed'] ?? false;
        $this->link = $rawChatInvite['link'];
        $this->adminId = $rawChatInvite['admin_id'];
        $this->date = $rawChatInvite['date'];
        $this->created = $rawChatInvite['start_date'] ?? null;
        $this->expire = $rawChatInvite['expire_date'] ?? null;
        $this->limit = $rawChatInvite['usage_limit'] ?? null;
        $this->requested = $rawChatInvite['requested'] ?? null;
        $this->title = $rawChatInvite['title'] ?? null;
        $this->used = $rawChatInvite['usage'] ?? null;
    }
}
