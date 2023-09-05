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

use danog\MadelineProto\EventHandler\Privacy\Rule\AllowAll;
use danog\MadelineProto\EventHandler\Privacy\Rule\AllowChatParticipants;
use danog\MadelineProto\EventHandler\Privacy\Rule\AllowCloseFriends;
use danog\MadelineProto\EventHandler\Privacy\Rule\AllowContacts;
use danog\MadelineProto\EventHandler\Privacy\Rule\AllowUsers;
use danog\MadelineProto\EventHandler\Privacy\Rule\DisallowAll;
use danog\MadelineProto\EventHandler\Privacy\Rule\DisallowChatParticipants;
use danog\MadelineProto\EventHandler\Privacy\Rule\DisallowContacts;
use danog\MadelineProto\EventHandler\Privacy\Rule\DisallowUsers;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractRule implements JsonSerializable
{
    public static function fromRawRule(array $rawRule): AbstractRule
    {
        return match ($rawRule['_']) {
            'privacyValueAllowAll' => new AllowAll,
            'privacyValueDisallowAll' => new DisallowAll,
            'privacyValueAllowContacts' => new AllowContacts,
            'privacyValueDisallowContacts' => new DisallowContacts,
            'privacyValueAllowCloseFriends' => new AllowCloseFriends,
            'privacyValueAllowUsers' => new AllowUsers($rawRule),
            'privacyValueDisallowUsers' => new DisallowUsers($rawRule),
            'privacyValueAllowChatParticipants' => new AllowChatParticipants($rawRule),
            'privacyValueDisallowChatParticipants' => new DisallowChatParticipants($rawRule),
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
