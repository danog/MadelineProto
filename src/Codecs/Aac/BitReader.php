<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Codecs\Aac;

use AssertionError;

/**
 * An MSB-first bit reader.
 *
 * @internal
 */
final class BitReader
{
    private readonly string $data;
    private readonly int $length;
    private int $position = 0;

    public function __construct(string $data)
    {
        $this->length = 8 * \strlen($data);
        // Padded, so that a peek near the end reads zeros.
        $this->data = $data."\0\0\0\0\0\0\0\0";
    }

    /** The next 32 bits, without consuming them. */
    public function peek32(): int
    {
        $byte = $this->position >> 3;
        /** @var int $word */
        $word = unpack('J', $this->data, $byte)[1];
        // The 32 bits after the first ($position & 7) of the 64 read: a logical shift, as the word may be negative.
        return ($word >> (32 - ($this->position & 7))) & 0xFFFFFFFF;
    }

    /** @param int<0, 32> $count */
    public function read(int $count): int
    {
        if ($count === 0) {
            return 0;
        }
        $value = $this->peek32() >> (32 - $count);
        $this->skip($count);
        return $value;
    }

    public function skip(int $count): void
    {
        $this->position += $count;
        if ($this->position > $this->length) {
            throw new AssertionError('Unexpected end of the AAC frame');
        }
    }

    public function align(): void
    {
        $this->position = ($this->position + 7) & ~7;
    }
}
