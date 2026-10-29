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

namespace danog\MadelineProto\Codecs;

use AssertionError;

/**
 * A forward complex FFT of any size made of the factors 2, 3, 4 and 5 (mixed-radix, decimation in time), in pure PHP.
 *
 * @internal
 */
final class Fft
{
    /** @var list<int> The radixes, outermost first. */
    private readonly array $factors;
    /** @var list<float> cos(2πk/n). */
    private readonly array $cos;
    /** @var list<float> -sin(2πk/n), so that cos + i·sin is the forward twiddle e^(-2πik/n). */
    private readonly array $sin;
    /** @var list<int> Where each input ends up after the digit reversal of the radixes. */
    private readonly array $order;

    public function __construct(public readonly int $size)
    {
        $factors = [];
        $n = $size;
        foreach ([4, 2, 3, 5] as $p) {
            while ($n % $p === 0) {
                $factors[] = $p;
                $n = intdiv($n, $p);
            }
        }
        if ($n !== 1 || $size < 1) {
            throw new AssertionError("An FFT of size $size is not supported: only products of 2, 3 and 5 are");
        }
        $this->factors = $factors;
        $cos = $sin = [];
        for ($k = 0; $k < $size; $k++) {
            $cos[] = cos(2 * M_PI * $k / $size);
            $sin[] = -sin(2 * M_PI * $k / $size);
        }
        $this->cos = $cos;
        $this->sin = $sin;
        $this->order = self::reorder($factors, $size);
    }

    /**
     * The input permutation of a decimation-in-time FFT whose outermost radix is the first factor: the inputs are split
     * by it into interleaved subsequences, each transformed (recursively) by the other radixes.
     *
     * @param list<int> $factors
     * @return list<int>
     */
    private static function reorder(array $factors, int $size): array
    {
        if ($factors === []) {
            return [0];
        }
        $p = array_shift($factors);
        $inner = self::reorder($factors, intdiv($size, $p));
        $order = [];
        for ($r = 0; $r < $p; $r++) {
            foreach ($inner as $idx) {
                $order[] = $r + $p * $idx;
            }
        }
        return $order;
    }

    /**
     * Transform in place: X[k] = Σ x[n]·e^(-2πikn/size).
     *
     * @param list<float> $re
     * @param list<float> $im
     */
    public function transform(array &$re, array &$im): void
    {
        $n = $this->size;
        $xr = $xi = [];
        foreach ($this->order as $j => $i) {
            $xr[$j] = $re[$i];
            $xi[$j] = $im[$i];
        }
        $cos = $this->cos;
        $sin = $this->sin;
        $len = 1;
        // Innermost radix first: the reversal above put each butterfly's inputs next to each other.
        foreach (array_reverse($this->factors) as $p) {
            $span = $len * $p;
            $step = intdiv($n, $span);
            for ($base = 0; $base < $n; $base += $span) {
                for ($k = 0; $k < $len; $k++) {
                    // Twiddled inputs of this butterfly.
                    $ar = $ai = [];
                    for ($r = 0; $r < $p; $r++) {
                        $idx = $base + $r * $len + $k;
                        $t = ($r * $k * $step) % $n;
                        $c = $cos[$t];
                        $s = $sin[$t];
                        $vr = $xr[$idx];
                        $vi = $xi[$idx];
                        $ar[$r] = $vr * $c - $vi * $s;
                        $ai[$r] = $vr * $s + $vi * $c;
                    }
                    if ($p === 2) {
                        $xr[$base + $k] = $ar[0] + $ar[1];
                        $xi[$base + $k] = $ai[0] + $ai[1];
                        $xr[$base + $len + $k] = $ar[0] - $ar[1];
                        $xi[$base + $len + $k] = $ai[0] - $ai[1];
                    } elseif ($p === 4) {
                        $t0r = $ar[0] + $ar[2];
                        $t0i = $ai[0] + $ai[2];
                        $t1r = $ar[0] - $ar[2];
                        $t1i = $ai[0] - $ai[2];
                        $t2r = $ar[1] + $ar[3];
                        $t2i = $ai[1] + $ai[3];
                        // -i·(a1 - a3)
                        $t3r = $ai[1] - $ai[3];
                        $t3i = $ar[3] - $ar[1];
                        $xr[$base + $k] = $t0r + $t2r;
                        $xi[$base + $k] = $t0i + $t2i;
                        $xr[$base + $len + $k] = $t1r + $t3r;
                        $xi[$base + $len + $k] = $t1i + $t3i;
                        $xr[$base + 2 * $len + $k] = $t0r - $t2r;
                        $xi[$base + 2 * $len + $k] = $t0i - $t2i;
                        $xr[$base + 3 * $len + $k] = $t1r - $t3r;
                        $xi[$base + 3 * $len + $k] = $t1i - $t3i;
                    } else {
                        // A plain DFT of the p twiddled inputs.
                        $w = intdiv($n, $p);
                        for ($q = 0; $q < $p; $q++) {
                            $sr = $si = 0.0;
                            for ($r = 0; $r < $p; $r++) {
                                $t = ($r * $q * $w) % $n;
                                $sr += $ar[$r] * $cos[$t] - $ai[$r] * $sin[$t];
                                $si += $ar[$r] * $sin[$t] + $ai[$r] * $cos[$t];
                            }
                            $xr[$base + $q * $len + $k] = $sr;
                            $xi[$base + $q * $len + $k] = $si;
                        }
                    }
                }
            }
            $len = $span;
        }
        $re = $xr;
        $im = $xi;
    }
}
