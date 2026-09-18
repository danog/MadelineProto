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

namespace danog\MadelineProto\Tgcalls;

/**
 * A big-endian bit reader with Exp-Golomb support, used to parse the few codec headers
 * ({@see CallRecorder} reads H.264 SPS and VP9 uncompressed headers) needed to describe a recorded
 * video track. Pure PHP, no dependencies.
 *
 * @internal
 */
final class BitReader
{
    private int $bitPos = 0;
    private int $length;

    public function __construct(private readonly string $data)
    {
        $this->length = \strlen($data) * 8;
    }

    /**
     * Read `$count` bits (0-32) as an unsigned integer.
     */
    public function bits(int $count): int
    {
        $value = 0;
        for ($i = 0; $i < $count; $i++) {
            $value = ($value << 1) | $this->bit();
        }
        return $value;
    }

    private function bit(): int
    {
        if ($this->bitPos >= $this->length) {
            return 0;
        }
        $byte = \ord($this->data[$this->bitPos >> 3]);
        $bit = ($byte >> (7 - ($this->bitPos & 7))) & 1;
        $this->bitPos++;
        return $bit;
    }

    /**
     * Read an unsigned Exp-Golomb coded value (ue(v)).
     */
    public function ue(): int
    {
        $zeros = 0;
        while ($this->bitPos < $this->length && $this->bit() === 0) {
            $zeros++;
        }
        if ($zeros === 0) {
            return 0;
        }
        return (1 << $zeros) - 1 + $this->bits($zeros);
    }

    /**
     * Read a signed Exp-Golomb coded value (se(v)).
     */
    public function se(): int
    {
        $ue = $this->ue();
        $sign = ($ue & 1) ? 1 : -1;
        return $sign * intdiv($ue + 1, 2);
    }
}
