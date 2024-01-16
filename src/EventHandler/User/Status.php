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
use danog\MadelineProto\EventHandler\User\Status\EmptyStatus;
use danog\MadelineProto\EventHandler\User\Status\LastMonth;
use danog\MadelineProto\EventHandler\User\Status\LastWeek;
use danog\MadelineProto\EventHandler\User\Status\Offline;
use danog\MadelineProto\EventHandler\User\Status\Online;
use danog\MadelineProto\EventHandler\User\Status\Recently;
use danog\MadelineProto\MTProto;

/**
 * Contains a status update.
 */
abstract class Status extends Update
{
    /** User identifier */
    public readonly int $userId;

    /** @internal */
    public function __construct(MTProto $API, array $rowUserStatus)
    {
        parent::__construct($API);
        $this->userId = $rowUserStatus['user_id'];
    }

    public static function fromRawStatus(MTProto $API, array $rowUserStatus): ?Status
    {
        return match ($rowUserStatus['status']['_']) {
            'userStatusEmpty' => new EmptyStatus($API, $rowUserStatus),
            'userStatusOnline' => new Online($API, $rowUserStatus),
            'userStatusOffline' => new Offline($API, $rowUserStatus),
            'userStatusRecently' => new Recently($API, $rowUserStatus),
            'userStatusLastWeek' => new LastWeek($API, $rowUserStatus),
            'userStatusLastMonth' => new LastMonth($API, $rowUserStatus),
            default => null
        };
    }
}
