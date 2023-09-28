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

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * The Time-To-Live of messages in this chat was changed.
 */
final class DialogSetTTL extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** @var int New Time-To-Live of all messages sent in this chat; if 0, autodeletion was disabled. */
        public readonly int $period,

        /** @var ?int If set, the chat TTL setting was set not due to a manual change by one of participants, but automatically because one of the participants has the [default TTL settings enabled »](https://docs.madelineproto.xyz/API_docs/methods/messages.setDefaultHistoryTTL.html). For example, when a user writes to us for the first time and we have set a default messages TTL of 1 week, this service message (with auto_setting_from=our_userid) will be emitted before our first message. */
        public readonly ?int $autoSettingFrom
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
