<?php

declare(strict_types=1);

/**
 * Outgoing message.
 *
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

namespace danog\MadelineProto\MTProto;

/**
 * Calls of MadelineProto's own session mechanics.
 *
 * Calls with a special method type are made as the account the session is authorized as: when logged in through a
 * business connection, they're the bot's, and are never sent through the connection.
 */
enum SpecialMethodType
{
    case UNAUTHED_METHOD;
    case USER_RELATED;
    case FILE_RELATED;
    case FILEREF_RELATED;
    /** Session mechanics with no special handling: peer database, config, update state. */
    case SESSION_RELATED;
}
