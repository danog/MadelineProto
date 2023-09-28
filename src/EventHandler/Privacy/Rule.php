<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Privacy;

use JsonSerializable;

/** Represents a privacy rule. */
enum Rule: string implements JsonSerializable
{
    /** Whether we can see the last online timestamp of this user */
    case STATUS_TIMESTAMP = 'privacyKeyStatusTimestamp';
    /** Whether the user can be invited to chats */
    case CHAT_INVITE = 'privacyKeyChatInvite';
    /** Whether the user accepts phone calls */
    case PHONE_CALL = 'privacyKeyPhoneCall';
    /** Whether P2P connections in phone calls with this user are allowed */
    case PHONE_P2P = 'privacyKeyPhoneP2P';
    /** Whether messages forwarded from the user will be [anonymously forwarded](https://telegram.org/blog/unsend-privacy-emoji#anonymous-forwarding) */
    case FORWARDS = 'privacyKeyForwards';
    /** Whether the profile picture of the user is visible */
    case PROFILE_PHOTO = 'privacyKeyProfilePhoto';
    /** Whether the user allows us to see his phone number */
    case PHONE_NUMBER = 'privacyKeyPhoneNumber';
    /** Whether this user can be added to our contact list by their phone number */
    case ADDED_BY_PHONE = 'privacyKeyAddedByPhone';
    /** Whether the user accepts voice messages */
    case VOICE_MESSAGES = 'privacyKeyVoiceMessages';
    /** Whether the user can see our bio. */
    case ABOUT = 'privacyKeyAbout';

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
