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

namespace danog\MadelineProto\EventHandler\Privacy\RuleDestination;

use danog\MadelineProto\EventHandler\Attributes\ConstructorAssertion;
use danog\MadelineProto\EventHandler\Privacy\RuleDestination;
use Webmozart\Assert\Assert;

/**
 * Disallow only certain users.
 */
final readonly class DisallowUsers extends RuleDestination
{
    /** Allowed users */
    public readonly array $users;

    /**
     * @internal
     */
    public function __construct(array $rawRule)
    {
        ConstructorAssertion::assert($rawRule, 'privacyValueDisallowUsers');
        $this->users = $rawRule['users'];
    }

    public static function new(array $users): self
    {
        Assert::true(\array_is_list($users));
        return new static(['_' => 'privacyValueDisallowUsers', 'users' => $users]);
    }

    /**
     * @internal
     */
    #[\Override]
    public function getInputConstructor(): array
    {
        return ['_' => 'inputPrivacyValueDisallowUsers', 'users' => $this->users];
    }
}
