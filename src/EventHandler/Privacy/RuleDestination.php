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
 * @copyright 2016-2025 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Privacy;

use danog\MadelineProto\EventHandler\Attributes\HasConstructor;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowAll;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowBots;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowChatParticipants;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowCloseFriends;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowContacts;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowPremium;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\AllowUsers;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\DisallowAll;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\DisallowBots;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\DisallowChatParticipants;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\DisallowContacts;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination\DisallowUsers;
use danog\MadelineProto\EventHandler\Type;

/**
 * To whom does a privacy rule apply?
 */
abstract readonly class RuleDestination implements Type, HasConstructor
{
    public static function fromRawRule(array $rawRule): RuleDestination
    {
        return match ($rawRule['_']) {
            'privacyValueAllowPremium'             => new AllowPremium($rawRule),
            'privacyValueAllowAll'                 => new AllowAll($rawRule),
            'privacyValueDisallowAll'              => new DisallowAll($rawRule),
            'privacyValueAllowContacts'            => new AllowContacts($rawRule),
            'privacyValueDisallowContacts'         => new DisallowContacts($rawRule),
            'privacyValueAllowCloseFriends'        => new AllowCloseFriends($rawRule),
            'privacyValueAllowBots'                => new AllowBots($rawRule),
            'privacyValueDisallowBots'             => new DisallowBots($rawRule),
            'privacyValueAllowUsers'               => new AllowUsers($rawRule),
            'privacyValueDisallowUsers'            => new DisallowUsers($rawRule),
            'privacyValueAllowChatParticipants'    => new AllowChatParticipants($rawRule),
            'privacyValueDisallowChatParticipants' => new DisallowChatParticipants($rawRule),
        };
    }

    /**
     * @internal
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new \ReflectionClass($this);
        foreach ($refl->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
