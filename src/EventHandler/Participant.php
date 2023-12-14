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

namespace danog\MadelineProto\EventHandler;

use AssertionError;
use danog\MadelineProto\EventHandler\Participant\Admin;
use danog\MadelineProto\EventHandler\Participant\Banned;
use danog\MadelineProto\EventHandler\Participant\Creator;
use danog\MadelineProto\EventHandler\Participant\Left;
use danog\MadelineProto\EventHandler\Participant\Member;
use danog\MadelineProto\EventHandler\Participant\MySelf;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Info about a channel participant.
 */
abstract class Participant implements JsonSerializable
{
    public static function fromRawParticipant(array $rawParticipant): self
    {
        return match ($rawParticipant['_']) {
            'channelParticipant'        => new Member($rawParticipant),
            'channelParticipantLeft'    => new Left($rawParticipant),
            'channelParticipantSelf'    => new MySelf($rawParticipant),
            'channelParticipantAdmin'   => new Admin($rawParticipant),
            'channelParticipantBanned'  => new Banned($rawParticipant),
            'channelParticipantCreator' => new Creator($rawParticipant),
            default => throw new AssertionError("undefined Participant type: {$rawParticipant['_']}")
        };
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
