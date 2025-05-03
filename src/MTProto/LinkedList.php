<?php

declare(strict_types=1);

/**
 * Message.
 *
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

namespace danog\MadelineProto\MTProto;

/**
 * @internal
 */
final class LinkedList
{
    public const EMPTY = true;

    public MTProtoOutgoingMessage|LinkedList $next;

    public MTProtoOutgoingMessage|LinkedList $prev;

    public function __construct()
    {
        $this->next = $this;
        $this->prev = $this;
    }

    public function isEmpty(): bool
    {
        return $this->next === $this;
    }

    public function enqueue(MTProtoOutgoingMessage $message): void
    {
        $message->next = $this->next;
        $message->prev = $this;
        $this->next->prev = $message;
        $this->next = $message;
    }
    public function peek(): ?MTProtoOutgoingMessage
    {
        if ($this->prev === $this) {
            return null;
        }
        return $this->prev;
    }

    public function __debugInfo(): array
    {
        if (!isset($this->next)) {
            $next = null;
        } elseif ($this->next instanceof MTProtoOutgoingMessage) {
            $next = $this->next;
        } else {
            $next = 'head|tail';
        }
        if (!isset($this->prev)) {
            $prev = null;
        } elseif ($this->prev instanceof MTProtoOutgoingMessage) {
            $prev = $this->prev;
        } else {
            $prev = 'head|tail';
        }
        return ['prev' => $prev, 'next' => $next];
    }

}
