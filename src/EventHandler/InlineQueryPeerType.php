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

namespace danog\MadelineProto\EventHandler;

use AssertionError;
use JsonSerializable;

/** @internal */
enum InlineQueryPeerType implements JsonSerializable
{
    /** private chat */
    case PM;
    /** [chat](https://core.telegram.org/api/channel) */
    case Chat;
    /** private chat with a bot. */
    case BotPM;
    /** [channel](https://core.telegram.org/api/channel) */
    case Broadcast;
    /** [supergroup](https://core.telegram.org/api/channel) */
    case Megagroup;
    /** private chat with the bot itself */
    case SameBotPM;

    /**
     * Get InlineQueryPeerType from update.
     *
     * @param string Type of the chat from which the inline query was sent.
     * @throws AssertionError
     */
    public static function fromString(string $name): InlineQueryPeerType
    {
        $newName = \substr($name, 19);
        foreach (InlineQueryPeerType::cases() as $case) {
            if ($case->name === $newName) {
                return $case;
            }
        }
        throw new AssertionError("Undefined case InlineQueryPeerType::".$name);
    }

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
