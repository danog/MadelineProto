<?php

declare(strict_types=1);

/**
 * Call state module.
 *
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

namespace danog\MadelineProto\VoIP;

use AssertionError;
use JsonSerializable;

/**
 * Why was the call discarded?
 */
enum DiscardReason implements JsonSerializable
{
    /** We missed the call */
    case MISSED;
    /** The phone call was disconnected */
    case DISCONNECTED;
    /** The phone call ended normally */
    case HANGUP;
    /** The phone call was discarded because the user is busy in another call */
    case BUSY;

    /**
     * @internal
     * @throws AssertionError
     */
    public static function fromString(?string $name): ?DiscardReason
    {
        if ($name === null) {
            return null;
        }
        $newName = \strtoupper(\substr($name, 22));
        foreach (DiscardReason::cases() as $case) {
            if ($case->name === $newName) {
                return $case;
            }
        }
        throw new AssertionError("Undefined case PhoneCallDiscardReason::".$name);
    }

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}