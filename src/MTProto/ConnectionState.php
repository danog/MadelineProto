<?php

declare(strict_types=1);

/**
 * MTProto Auth key.
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

/** @internal */
enum ConnectionState
{
    case UNENCRYPTED_MEDIA_WAITING_MAIN;
    case UNENCRYPTED_NO_PERMANENT;
    case UNENCRYPTED;
    case ENCRYPTED_NOT_BOUND;
    case ENCRYPTED_NOT_INITED;
    case ENCRYPTED_NOT_AUTHED_NO_LOGIN;
    case ENCRYPTED_NOT_AUTHED;
    case ENCRYPTED;

    /**
     * @psalm-mutation-free
     */
    public function isEncrypted(): bool
    {
        return match ($this) {
            self::UNENCRYPTED_MEDIA_WAITING_MAIN,
            self::UNENCRYPTED_NO_PERMANENT,
            self::UNENCRYPTED => false,
            self::ENCRYPTED_NOT_INITED,
            self::ENCRYPTED_NOT_BOUND,
            self::ENCRYPTED_NOT_AUTHED_NO_LOGIN,
            self::ENCRYPTED_NOT_AUTHED,
            self::ENCRYPTED => true,
        };
    }
}
