<?php

declare(strict_types=1);

/**
 * Secret chat module.
 *
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

namespace danog\MadelineProto\SecretChats;

use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;

/**
 * Represents a secret chat.
 */
final class SecretChat extends IpcCapable
{
    /** Creation date */
    public readonly int $created;
    /** @internal */
    public function __construct(
        MTProto $API,
        /** Secret chat ID */
        public readonly int $chatId,
        /** Whether we created this chat */
        public readonly bool $creator,
        /** User ID of the other peer in the chat */
        public readonly int $otherID,
    ) {
        parent::__construct($API);
        $this->created = time();
    }

    /**
     * Gets a secret chat message.
     *
     * @param integer $randomId Secret chat message ID.
     */
    public function getMessage(int $randomId): \danog\MadelineProto\EventHandler\Message\SecretMessage
    {
        return $this->getClient()->getSecretMessage($this->chatId, $randomId);
    }
}
