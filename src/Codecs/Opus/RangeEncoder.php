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

namespace danog\MadelineProto\Codecs\Opus;

/**
 * The range encoder of Opus (RFC 6716, section 5.1), with its raw bits written backwards from the end of the frame.
 *
 * Cloning it makes a checkpoint to roll back to.
 *
 * @internal
 */
final class RangeEncoder
{
    private const SYM = 8;
    private const CEIL = 0xFF;
    private const TOP = 0x80000000;
    private const BOT = self::TOP >> self::SYM;
    private const SHIFT = 32 - self::SYM - 1;

    private int $value = 0;
    private int $range = self::TOP;
    /** Bits used, as reported by tell(), plus the 33 the coder starts with. */
    private int $totalBits = 33;
    /** The byte awaiting a carry, -1 if none. */
    private int $rem = -1;
    /** How many 0xFF bytes await a carry. */
    private int $ext = 0;
    /** @var list<int> The range coded bytes. */
    private array $bytes = [];
    /** @var list<int> The raw bits' bytes, from the last one of the frame backwards. */
    private array $raw = [];
    private int $rawCache = 0;
    private int $rawCacheLength = 0;

    /** Bits used so far, rounded up. */
    public function tell(): int
    {
        return $this->totalBits - self::ilog($this->range);
    }

    /** Bits used so far, in eighths of a bit. */
    public function tellFrac(): int
    {
        $totalBits = $this->totalBits << 3;
        $bits = self::ilog($this->range);
        $range = $this->range >> ($bits - 16);
        for ($i = 0; $i < 3; $i++) {
            $range = ($range * $range) >> 15;
            $bit = $range >> 16;
            $bits = ($bits << 1) | $bit;
            $range >>= $bit;
        }
        return $totalBits - $bits;
    }

    /** The number of bits of $x (0 for 0). */
    public static function ilog(int $x): int
    {
        return $x <= 0 ? 0 : \strlen(decbin($x));
    }

    private function carryOut(int $cbuf): void
    {
        if ($cbuf === self::CEIL) {
            $this->ext++;
            return;
        }
        $carry = $cbuf >> self::SYM;
        if ($this->rem >= 0) {
            $this->bytes[] = ($this->rem + $carry) & 0xFF;
        }
        if ($this->ext > 0) {
            $fill = (self::CEIL + $carry) & self::CEIL;
            for (; $this->ext > 0; $this->ext--) {
                $this->bytes[] = $fill;
            }
        }
        $this->rem = $cbuf & self::CEIL;
    }

    private function update(int $low, int $high, int $total, bool $powerOfTwo): void
    {
        $scaled = $powerOfTwo ? $this->range >> (self::ilog($total) - 1) : intdiv($this->range, $total);
        if ($low) {
            $this->value += $this->range - $scaled * ($total - $low);
            $this->range = $scaled * ($high - $low);
        } else {
            $this->range -= $scaled * ($total - $high);
        }
        while ($this->range <= self::BOT) {
            $this->carryOut($this->value >> self::SHIFT);
            $this->value = ($this->value << self::SYM) & (self::TOP - 1);
            $this->range <<= self::SYM;
            $this->totalBits += self::SYM;
        }
    }

    /** @param list<int> $cdf Total, then the cumulative frequencies. */
    public function encodeCdf(int $value, array $cdf): void
    {
        $this->update($value ? $cdf[$value] : 0, $cdf[$value + 1], $cdf[0], true);
    }

    /** A bit with probability 1/2^$bits of being set. */
    public function encodeLog(int $value, int $bits): void
    {
        $bits = (1 << $bits) - 1;
        $this->update($value ? $bits : 0, $bits + ($value ? 1 : 0), $bits + 1, true);
    }

    /** A uniformly distributed integer in [0, $size). */
    public function encodeUint(int $value, int $size): void
    {
        $ps = max(self::ilog($size - 1) - 8, 0);
        $this->update($value >> $ps, ($value >> $ps) + 1, (($size - 1) >> $ps) + 1, false);
        $this->putRaw($value, $ps);
    }

    public function encodeUintStep(int $value, int $k0): void
    {
        $a = $value <= $k0 ? 1 : 0;
        $b = 2 * $a + 1;
        $k0 = ($k0 + 1) << 1;
        $value = $b * ($value + $k0) - 3 * $a * $k0;
        $this->update($value, $value + $b, ($k0 << 1) - 1, false);
    }

    public function encodeUintTri(int $k, int $qn): void
    {
        $total = (($qn >> 1) + 1) * (($qn >> 1) + 1);
        if ($k <= $qn >> 1) {
            $low = ($k * ($k + 1)) >> 1;
            $symbol = $k + 1;
        } else {
            $low = $total - ((($qn + 1 - $k) * ($qn + 2 - $k)) >> 1);
            $symbol = $qn + 1 - $k;
        }
        $this->update($low, $low + $symbol, $total, false);
    }

    /**
     * A Laplace-distributed integer: the value actually coded may be clamped.
     */
    public function encodeLaplace(int &$value, int $symbol, int $decay): void
    {
        $low = $symbol;
        $val = abs($value);
        $pos = $value > 0 ? 1 : 0;
        if (!$val) {
            $this->update(0, $symbol, 1 << 15, true);
            return;
        }
        $symbol = ((32768 - 32 - $symbol) * (16384 - $decay)) >> 15;
        for ($i = 1; $i < $val && $symbol; $i++) {
            $low += ($symbol << 1) + 2;
            $symbol = ($symbol * $decay) >> 14;
        }
        if ($symbol) {
            $low += (++$symbol) * $pos;
        } else {
            $distance = min($val - $i, (((32768 - $low) - ($pos ? 0 : 1)) >> 1) - 1);
            $low += $pos + ($distance << 1);
            $symbol = min(1, 32768 - $low);
            $value = ($value < 0 ? -1 : 1) * ($distance + $i);
        }
        $this->update($low, $low + $symbol, 1 << 15, true);
    }

    /** Raw bits, LSB first, at the end of the frame. */
    public function putRaw(int $value, int $count): void
    {
        if ($count === 0) {
            return;
        }
        $this->totalBits += $count;
        $value &= (1 << $count) - 1;
        $this->rawCache |= $value << $this->rawCacheLength;
        $this->rawCacheLength += $count;
        while ($this->rawCacheLength >= 8) {
            $this->raw[] = $this->rawCache & 0xFF;
            $this->rawCache >>= 8;
            $this->rawCacheLength -= 8;
        }
    }

    /**
     * Finish the frame: $size bytes, the range coded ones at the start and the raw bits at the end.
     */
    public function finish(int $size): string
    {
        $bits = 32 - self::ilog($this->range);
        $mask = (self::TOP - 1) >> $bits;
        $end = ($this->value + $mask) & ~$mask & 0xFFFFFFFF;
        if (($end | $mask) >= $this->value + $this->range) {
            $bits++;
            $mask >>= 1;
            $end = ($this->value + $mask) & ~$mask & 0xFFFFFFFF;
        }
        while ($bits > 0) {
            $this->carryOut($end >> self::SHIFT);
            $end = ($end << self::SYM) & (self::TOP - 1);
            $bits -= self::SYM;
        }
        if ($this->rem >= 0 || $this->ext > 0) {
            $this->carryOut(0);
        }
        $raw = $this->raw;
        if ($this->rawCacheLength > 0) {
            $raw[] = $this->rawCache & 0xFF;
        }
        $out = array_fill(0, $size, 0);
        foreach ($this->bytes as $i => $byte) {
            if ($i < $size) {
                $out[$i] = $byte;
            }
        }
        foreach ($raw as $i => $byte) {
            if ($size - 1 - $i >= 0) {
                $out[$size - 1 - $i] |= $byte;
            }
        }
        return pack('C*', ...$out);
    }
}
