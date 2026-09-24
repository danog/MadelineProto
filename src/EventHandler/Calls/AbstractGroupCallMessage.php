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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Calls;

use danog\MadelineProto\MTProto;

/**
 * What an in-call message of a video chat ({@see GroupCallMessage}) and of a live story
 * ({@see LiveStoryMessage}) have in common: both are server-side (not end-to-end encrypted) messages
 * of a group call, with a server-assigned {@see self::$id} and moderation.
 */
abstract class AbstractGroupCallMessage extends MultiCallMessage
{
    /** ID of the message in the call. */
    public readonly int $id;
    /** Whether the sender is an admin of the call. */
    public readonly bool $fromAdmin;

    /**
     * @internal
     *
     * @param array{call: array{id: int}, message: array{id: int, from_id: mixed, date: int, from_admin?: bool, message: array{text: string, entities?: list<array<array-key, mixed>>}}} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public function __construct(MTProto $API, array $rawUpdate)
    {
        parent::__construct($API, $rawUpdate);
        $message = $rawUpdate['message'];
        $this->id = $message['id'];
        $this->fromAdmin = $message['from_admin'] ?? false;
    }

    /**
     * Delete this message from the call (our own, or anyone's if we are an admin).
     *
     * @param bool $reportSpam Also report it as spam (admins only).
     */
    public function delete(bool $reportSpam = false): void
    {
        $this->getClient()->deleteGroupCallMessages($this->callId, [$this->id], $reportSpam);
    }
}
