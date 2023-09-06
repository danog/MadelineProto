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

namespace danog\MadelineProto\EventHandler\User;

use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;

/**
 * Changes were made to the user’s first name, last name or username.
 */
final class Username extends Update
{
    /** @var int User identifier */
    public readonly int $userId;

    /** @var string New first name. Corresponds to the new value of `real_first_name` field of the [userFull](https://docs.madelineproto.xyz/API_docs/constructors/userFull.html) constructor. */
    public readonly string $firstName;

    /** @var string New last name. Corresponds to the new value of `real_last_name` field of the [userFull](https://docs.madelineproto.xyz/API_docs/constructors/userFull.html) constructor. */
    public readonly string $lastName;

    /** @var list<UsernameInfo> */
    public readonly array $usernames;

    /** @internal */
    public function __construct(MTProto $API, array $rawUserName)
    {
        parent::__construct($API);
        $this->userId = $rawUserName['user_id'];
        $this->firstName = $rawUserName['first_name'];
        $this->lastName = $rawUserName['last_name'];
        $this->usernames = \array_map(
            fn (array $username) => new UsernameInfo($username),
            $rawUserName['usernames']
        );
    }
}
