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
use danog\MadelineProto\EventHandler\Privacy\AllowUsers;
use danog\MadelineProto\EventHandler\Privacy\DisallowUsers;
use danog\MadelineProto\EventHandler\Privacy\AllowChatParticipants;
use danog\MadelineProto\EventHandler\Privacy\DisallowChatParticipants;

/** @internal */
enum Privacy implements JsonSerializable
{
    /** Allow all contacts */
    case AllowContacts;
    /** Allow all users */
    case AllowAll;
    /** Disallow only contacts */
    case DisallowContacts;
    /** Disallow all users */
    case DisallowAll;
    /**  */
    case AllowCloseFriends;

    /**
     *
     * @param string $name
     * 
     * @return array
     * @throws AssertionError
     */
    public static function fromRawPrivacy(array $privacies): array
    {
        $create = function (array $data): Privacy|AbstractPrivacy
        {
            $newName = \substr($data['_'], 12);
            foreach (Privacy::cases() as $case) {
                if (\in_array($newName, ['AllowUsers', 'DisallowUsers', 'AllowChatParticipants', 'DisallowChatParticipants',]))
                    return new $newName($data);
                if ($case->name === $newName) {
                    return $case;
                }
            }
            throw new AssertionError("Undefined case Privacy::".$data['_']);
        };

        return \array_map(fn (array $privacy) => $create($privacy), $privacies);
    }

    /** @internal */
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
