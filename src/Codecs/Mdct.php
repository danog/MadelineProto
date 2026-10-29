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
 * The MDCT of M coefficients (2M samples) and its inverse, unscaled, through a DCT-IV computed with an M/2-point FFT.
 *
 * mdct():  X[k] = Σ_{n<2M} x[n]·cos(π/M·(n + n0)·(k + ½))
 * imdct(): y[n] = Σ_{k<M}  X[k]·cos(π/M·(n + n0)·(k + ½))
 * with n0 = (M + 1)/2.
 *
 * @internal
 */
final class Mdct
{
    private readonly Fft $fft;
    /** @var list<float> Pre-twiddle e^(-iπ(4k+1)/4M). */
    private readonly array $preCos;
    /** @var list<float> */
    private readonly array $preSin;
    /** @var list<float> Post-twiddle e^(-iπj/M). */
    private readonly array $postCos;
    /** @var list<float> */
    private readonly array $postSin;

    /** @param int $coefficients M, even. */
    public function __construct(public readonly int $coefficients)
    {
        if ($coefficients < 2 || $coefficients % 2) {
            throw new AssertionError("Unsupported MDCT size $coefficients");
        }
        $half = $coefficients >> 1;
        $this->fft = new Fft($half);
        $preCos = $preSin = $postCos = $postSin = [];
        for ($k = 0; $k < $half; $k++) {
            $t = -M_PI * (4 * $k + 1) / (4 * $coefficients);
            $preCos[] = cos($t);
            $preSin[] = sin($t);
            $t = -M_PI * $k / $coefficients;
            $postCos[] = cos($t);
            $postSin[] = sin($t);
        }
        $this->preCos = $preCos;
        $this->preSin = $preSin;
        $this->postCos = $postCos;
        $this->postSin = $postSin;
    }

    /**
     * u[n] = Σ_k x[k]·cos(π/M·(n + ½)·(k + ½)).
     *
     * @param list<float> $x M values.
     * @return list<float>
     */
    public function dct4(array $x): array
    {
        $m = $this->coefficients;
        $half = $m >> 1;
        $re = $im = [];
        $preCos = $this->preCos;
        $preSin = $this->preSin;
        for ($k = 0; $k < $half; $k++) {
            $a = $x[2 * $k];
            $b = $x[$m - 1 - 2 * $k];
            $re[$k] = $a * $preCos[$k] - $b * $preSin[$k];
            $im[$k] = $a * $preSin[$k] + $b * $preCos[$k];
        }
        $this->fft->transform($re, $im);
        $u = array_fill(0, $m, 0.0);
        $postCos = $this->postCos;
        $postSin = $this->postSin;
        for ($j = 0; $j < $half; $j++) {
            $u[2 * $j] = $re[$j] * $postCos[$j] - $im[$j] * $postSin[$j];
            $u[$m - 1 - 2 * $j] = -($re[$j] * $postSin[$j] + $im[$j] * $postCos[$j]);
        }
        return $u;
    }

    /**
     * The inverse MDCT: 2M samples, to be windowed and overlap-added.
     *
     * @param list<float> $x M coefficients.
     * @return list<float>
     */
    public function imdct(array $x): array
    {
        $m = $this->coefficients;
        $u = $this->dct4($x);
        // y[n] = U(n + M/2), with U(j) = u[j], -u[2M-1-j] and -u[j-2M] on [0,M), [M,2M) and [2M,3M).
        $y = [];
        $half = $m >> 1;
        for ($n = 0; $n < $half; $n++) {
            $y[] = $u[$n + $half];
        }
        for ($j = $m; $j < 2 * $m; $j++) {
            $y[] = -$u[2 * $m - 1 - $j];
        }
        for ($j = 2 * $m; $j < 2 * $m + $half; $j++) {
            $y[] = -$u[$j - 2 * $m];
        }
        return $y;
    }

    /**
     * The forward MDCT of 2M (windowed) samples.
     *
     * @param list<float> $x 2M samples.
     * @return list<float>
     */
    public function mdct(array $x): array
    {
        $m = $this->coefficients;
        $half = $m >> 1;
        $threeHalves = 3 * $half;
        // Fold the 2M samples into M, so that the MDCT is the DCT-IV of the result.
        $v = [];
        for ($n = 0; $n < $half; $n++) {
            $v[$n] = -$x[$threeHalves - 1 - $n] - $x[$threeHalves + $n];
        }
        for ($n = $half; $n < $m; $n++) {
            $v[$n] = $x[$n - $half] - $x[$threeHalves - 1 - $n];
        }
        return $this->dct4($v);
    }
}
