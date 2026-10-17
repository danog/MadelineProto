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
 * Reason why a call or group call cannot be started in a chat, the value is a human-readable description.
 */
enum CallDenialReason: string
{
    /** We are a bot, and bots cannot make one-to-one calls. */
    case BOTS_CANNOT_CALL = 'Bots cannot make calls!';
    /** The other user of the private chat is ourselves. */
    case CANNOT_CALL_SELF = 'Cannot call ourselves!';
    /** The other user cannot be called at all. */
    case CALLS_UNAVAILABLE = 'This user cannot be called!';
    /** The privacy settings of the other user do not allow us to call them. */
    case PRIVACY_SETTINGS = "This user's privacy settings do not allow us to call them!";
    /** The other user cannot be video called. */
    case VIDEO_CALLS_UNAVAILABLE = 'This user cannot be video called!';

    /** We are a bot, and bots cannot create group calls. */
    case BOTS_CANNOT_CREATE_GROUP_CALLS = 'Bots cannot create group calls!';
    /** We are not a member of the group or channel. */
    case NOT_A_MEMBER = 'We are not a member of this chat!';
    /** We lack the `manage_call` admin right in the group or channel. */
    case MANAGE_CALL_RIGHT_REQUIRED = 'The manage_call admin right is required to create a group call in this chat!';
}
