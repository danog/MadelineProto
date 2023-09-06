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

namespace danog\MadelineProto\EventHandler\Topic;

use JsonSerializable;

/**
 * Specifies the color of the fallback topic icon (RGB) if no custom emoji icon is specified.
 */
enum IconColor: int implements JsonSerializable
{
    case NONE = 0;
    case BLUE = 0x6fb9f0;
    case YELLOW = 0xffd67e;
    case PURPLE = 0xcb86db;
    case GREEN = 0x8eee98;
    case PINK = 0xff93b2;
    case RED = 0xfb6f5f;

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
