<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2023 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\Message\Service\DialogScreenshotTaken;
use danog\MadelineProto\MTProto;

/**
 * Represents a private or secret chat message.
 */
abstract class AbstractPrivateMessage extends Message
{
    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info, bool $scheduled)
    {
        parent::__construct($API, $rawMessage, $info, $scheduled);
    }

    /**
     * Notify the other user in a private chat that a screenshot of the chat was taken.
     *
     */
    abstract public function screenShot(): DialogScreenshotTaken;
}
