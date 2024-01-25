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

use JsonSerializable;

/** @internal */
enum InlineQueryPeerType: string implements JsonSerializable
{
    /** private chat */
    case PM = 'inlineQueryPeerTypePM';

    /** [chat](https://core.telegram.org/api/channel) */
    case Chat = 'inlineQueryPeerTypeChat';

    /** private chat with a bot. */
    case BotPM = 'inlineQueryPeerTypeBotPM';

    /** [channel](https://core.telegram.org/api/channel) */
    case Broadcast = 'inlineQueryPeerTypeBroadcast';

    /** [supergroup](https://core.telegram.org/api/channel) */
    case Megagroup = 'inlineQueryPeerTypeMegagroup';

    /** private chat with the bot itself */
    case SameBotPM = 'inlineQueryPeerTypeSameBotPM';

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
