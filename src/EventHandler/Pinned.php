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

use danog\MadelineProto\MTProto;

/**
 * Indicates that some messages were pinned/unpinned.
 */
abstract class Pinned extends Update
{
    /** Whether the messages were pinned or unpinned. */
    public readonly bool $pinned;

    /** @var list<int> List of identifiers of pinned messages. */
    public readonly array $ids;

    /** ID of the chat where the messages were pinned. */
    public readonly int $chatId;

    /** @internal */
    public function __construct(MTProto $API, array $rawPinned)
    {
        parent::__construct($API);
        $this->pinned = $rawPinned['pinned'];
        $this->ids = $rawPinned['messages'];
        $this->chatId = $API->getIdInternal($rawPinned);
    }
}
