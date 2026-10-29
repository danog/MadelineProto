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

/**
 * A streaming resampler for any rational ratio, in pure PHP: a polyphase Kaiser-windowed sinc filter.
 *
 * @internal
 */
final class Resampler
{
    /** Taps on each side of a phase's filter. */
    private const HALF_TAPS = 12;
    private const KAISER_BETA = 7.0;

    /** Output samples per $down input samples. */
    private readonly int $up;
    private readonly int $down;
    /** @var list<list<float>> The filter of each phase: 2·HALF_TAPS coefficients, for the inputs i-HALF_TAPS+1 … i+HALF_TAPS. */
    private readonly array $phases;

    /** @var list<list<float>> The input samples not consumed yet (with the history the filter needs), by channel. */
    private array $buffer;
    /** The absolute input index of each channel's $buffer[0]. */
    private int $bufferStart = 0;
    /** The absolute index of the next output sample. */
    private int $next = 0;

    public function __construct(public readonly int $from, public readonly int $to, public readonly int $channels)
    {
        $gcd = self::gcd($from, $to);
        $this->up = intdiv($to, $gcd);
        $this->down = intdiv($from, $gcd);
        // Cut off at the lower of the two Nyquist frequencies, a little below to leave room for the transition.
        $cutoff = 0.95 * min(1.0, $to / $from);
        $i0Beta = self::besselI0(self::KAISER_BETA);
        $phases = [];
        for ($p = 0; $p < $this->up; $p++) {
            $taps = [];
            $sum = 0.0;
            for ($j = -self::HALF_TAPS + 1; $j <= self::HALF_TAPS; $j++) {
                // Distance between the input sample and the output instant, in input samples.
                $t = $j - $p / $this->up;
                $x = $cutoff * $t;
                $sinc = abs($x) < 1e-12 ? 1.0 : sin(M_PI * $x) / (M_PI * $x);
                $r = $t / self::HALF_TAPS;
                $window = abs($r) >= 1 ? 0.0 : self::besselI0(self::KAISER_BETA * sqrt(1 - $r * $r)) / $i0Beta;
                $taps[] = $sinc * $window;
                $sum += $sinc * $window;
            }
            // Unity gain at DC for every phase.
            foreach ($taps as $k => $v) {
                $taps[$k] = $v / $sum;
            }
            $phases[] = $taps;
        }
        $this->phases = $phases;
        // The history before the first sample: silence.
        $this->buffer = array_fill(0, $channels, array_fill(0, self::HALF_TAPS - 1, 0.0));
        $this->bufferStart = -(self::HALF_TAPS - 1);
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b) {
            [$a, $b] = [$b, $a % $b];
        }
        return $a;
    }

    private static function besselI0(float $x): float
    {
        $sum = 1.0;
        $term = 1.0;
        for ($k = 1; $k < 50; $k++) {
            $term *= ($x / (2 * $k)) ** 2;
            $sum += $term;
            if ($term < 1e-12 * $sum) {
                break;
            }
        }
        return $sum;
    }

    /**
     * Resample the next samples of each channel; returns the output samples that can be computed so far.
     *
     * @param list<list<float>> $samples By channel, all of the same length.
     * @return list<list<float>>
     */
    public function process(array $samples): array
    {
        if ($this->up === $this->down) {
            return $samples;
        }
        foreach ($samples as $c => $s) {
            array_push($this->buffer[$c], ...$s);
        }
        $available = \count($this->buffer[0]);
        $end = $this->bufferStart + $available;
        $up = $this->up;
        $down = $this->down;
        $phases = $this->phases;
        $halfTaps = self::HALF_TAPS;
        $out = array_fill(0, $this->channels, []);
        $m = $this->next;
        while (true) {
            // Output m is at input position m·down/up: input i, phase p.
            $position = $m * $down;
            $i = intdiv($position, $up);
            if ($i + $halfTaps >= $end) {
                break;
            }
            $taps = $phases[$position % $up];
            $base = $i - $halfTaps + 1 - $this->bufferStart;
            foreach ($this->buffer as $c => $buf) {
                $acc = 0.0;
                foreach ($taps as $k => $h) {
                    $acc += $buf[$base + $k] * $h;
                }
                $out[$c][] = $acc;
            }
            $m++;
        }
        $this->next = $m;
        // Drop what no later output needs.
        $keepFrom = intdiv($m * $down, $up) - $halfTaps + 1;
        $drop = $keepFrom - $this->bufferStart;
        if ($drop > 0) {
            foreach ($this->buffer as $c => $buf) {
                $this->buffer[$c] = \array_slice($buf, $drop);
            }
            $this->bufferStart += $drop;
        }
        return $out;
    }
}
