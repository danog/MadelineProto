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

use AssertionError;
use danog\MadelineProto\Codecs\Mdct;

/**
 * A pure PHP Opus encoder: CELT only, full band, 20 ms frames at 48 kHz, constant bitrate (a port of the CELT encoder
 * of FFmpeg's libavcodec/opus, without its psychoacoustic model: no transients, no postfilter, normal spreading).
 *
 * @internal
 */
final class OpusEncoder
{
    /** Samples per channel of a frame. */
    public const FRAME_SAMPLES = 960;
    private const OVERLAP = 120;
    private const BANDS = 21;
    /** log2 of the frame's MDCT size in short blocks (960 = 120 << 3). */
    private const SIZE = 3;
    private const MAX_FINE_BITS = 8;
    private const FINE_OFFSET = 21;
    private const ALLOC_STEPS = 6;
    private const VECTORS = 11;
    private const ENERGY_SILENCE = -28.0;
    private const SPREAD_NORMAL = 2;
    private const QTHETA_OFFSET = 4;
    private const QTHETA_OFFSET_TWOPHASE = 16;
    private const EMPHASIS = 0.8500061035;
    /** The MDCT scale of FFmpeg's encoder, for its energies to mean what the decoder expects. */
    private const MDCT_SCALE = 68.0;
    private const FLT_EPSILON = 1.1920929e-7;

    private readonly Mdct $mdct;
    /** @var list<float> The 120-sample overlap window, rising. */
    private readonly array $window;
    /** The packet size, TOC included. */
    private readonly int $packetBytes;
    /** Bits of the CELT frame. */
    private readonly int $frameBits;

    /** @var list<float> The pre-emphasis filter's memory, by channel. */
    private array $emphasis = [];
    /** @var list<list<float>> The last 120 pre-emphasized samples of the previous frame, by channel. */
    private array $overlap = [];
    /** @var list<list<float>> The quantized band energies of the previous frame, by channel. */
    private array $lastEnergy = [];
    private int $seed = 0;

    // Per-frame state.
    /** @var list<list<float>> */
    private array $energy = [];
    /** @var list<list<float>> */
    private array $linEnergy = [];
    /** @var list<list<float>> */
    private array $errorEnergy = [];
    /** @var list<list<int>> Collapse masks, by channel and band. */
    private array $collapseMasks = [];
    private int $codedBands = 0;
    private int $intensityStereo = 0;
    private bool $dualStereo = false;
    private int $remaining = 0;
    private int $remaining2 = 0;
    /** @var list<int> */
    private array $caps = [];
    /** @var list<int> */
    private array $fineBits = [];
    /** @var list<int> */
    private array $finePriority = [];
    /** @var list<int> */
    private array $pulses = [];

    /**
     * @param int<1, 2> $channels
     * @param int $bitrate In bits per second, TOC included.
     */
    public function __construct(public readonly int $channels, int $bitrate = 96000)
    {
        if ($channels !== 1 && $channels !== 2) {
            throw new AssertionError("Opus can be encoded in mono or stereo, not $channels channels");
        }
        $this->packetBytes = max(10, min(1275, intdiv($bitrate * 20, 8000)));
        $this->frameBits = ($this->packetBytes - 1) * 8;
        $this->mdct = new Mdct(self::FRAME_SAMPLES);
        $window = [];
        for ($i = 0; $i < self::OVERLAP; $i++) {
            $s = sin(0.5 * M_PI * ($i + 0.5) / self::OVERLAP);
            $window[] = sin(0.5 * M_PI * $s * $s);
        }
        $this->window = $window;
        for ($c = 0; $c < $channels; $c++) {
            $this->emphasis[$c] = 0.0;
            $this->overlap[$c] = array_fill(0, self::OVERLAP, 0.0);
            $this->lastEnergy[$c] = array_fill(0, self::BANDS, 0.0);
        }
    }

    /**
     * Encode 20 ms of audio into an Opus packet.
     *
     * @param list<list<float>> $samples FRAME_SAMPLES samples in [-1, 1], by channel.
     */
    public function encode(array $samples): string
    {
        $channels = $this->channels;
        $coeffs = [];
        $this->energy = $this->linEnergy = $this->errorEnergy = [];
        for ($c = 0; $c < $channels; $c++) {
            // Pre-emphasis.
            $m = $this->emphasis[$c];
            $emphasized = [];
            foreach ($samples[$c] as $sample) {
                $emphasized[] = $sample - $m;
                $m = $sample * self::EMPHASIS;
            }
            $this->emphasis[$c] = $m;
            // The window: the previous frame's overlap rising, a flat top, then this frame's last 120 samples falling.
            $win = array_fill(0, 2 * self::FRAME_SAMPLES, 0.0);
            $lap = (2 * self::FRAME_SAMPLES - self::FRAME_SAMPLES - self::OVERLAP) >> 1;
            $flat = self::FRAME_SAMPLES - self::OVERLAP;
            for ($i = 0; $i < self::OVERLAP; $i++) {
                $win[$lap + $i] = $this->overlap[$c][$i] * $this->window[$i];
            }
            for ($i = 0; $i < $flat; $i++) {
                $win[$lap + self::OVERLAP + $i] = $emphasized[$i];
            }
            for ($i = 0; $i < self::OVERLAP; $i++) {
                $win[$lap + self::FRAME_SAMPLES + $i] = $emphasized[$flat + $i] * $this->window[self::OVERLAP - 1 - $i];
            }
            $this->overlap[$c] = \array_slice($emphasized, $flat);
            $x = $this->mdct->mdct($win);
            // Band energies, and the normalized coefficients.
            $energy = $lin = [];
            for ($i = 0; $i < self::BANDS; $i++) {
                $offset = CeltTables::FREQ_BANDS[$i] << self::SIZE;
                $size = CeltTables::FREQ_RANGE[$i] << self::SIZE;
                $e = 0.0;
                for ($j = 0; $j < $size; $j++) {
                    $x[$offset + $j] *= self::MDCT_SCALE;
                    $e += $x[$offset + $j] * $x[$offset + $j];
                }
                $lin[$i] = sqrt($e) + self::FLT_EPSILON;
                $inv = 1 / $lin[$i];
                for ($j = 0; $j < $size; $j++) {
                    $x[$offset + $j] *= $inv;
                }
                $energy[$i] = max(log($lin[$i], 2) - CeltTables::MEAN_ENERGY[$i], self::ENERGY_SILENCE);
            }
            $coeffs[$c] = $x;
            $this->energy[$c] = $energy;
            $this->linEnergy[$c] = $lin;
            $this->errorEnergy[$c] = array_fill(0, self::BANDS, 0.0);
        }

        $rc = new RangeEncoder;
        // Not silence, no postfilter, not transient.
        $rc->encodeLog(0, 15);
        if ($rc->tell() + 16 <= $this->frameBits) {
            $rc->encodeLog(0, 1);
        }
        if ($rc->tell() + 3 <= $this->frameBits) {
            $rc->encodeLog(0, 3);
        }
        $rc = $this->quantCoarse($rc);
        $this->encodeTf($rc);
        $this->allocate($rc);
        $this->quantFine($rc);
        $this->quantBands($rc, $coeffs);
        $this->quantFinal($rc);
        for ($c = 0; $c < $channels; $c++) {
            for ($i = 0; $i < self::BANDS; $i++) {
                $this->lastEnergy[$c][$i] = $this->energy[$c][$i] + $this->errorEnergy[$c][$i];
            }
        }
        // TOC: CELT-only, full band, 20 ms, one frame.
        $toc = (31 << 3) | (($channels === 2 ? 1 : 0) << 2);
        return \chr($toc).$rc->finish($this->packetBytes - 1);
    }

    /** lrintf(): rounding half to even. */
    private static function rint(float $x): int
    {
        $r = round($x);
        if (abs($x - floor($x) - 0.5) < 1e-12 && fmod($r, 2.0) !== 0.0) {
            $r -= $x < 0 ? -1 : 1;
        }
        return (int) $r;
    }

    /**
     * Coarse energy, coded both intra and inter frame (from a checkpoint): the cheaper one is kept.
     *
     * @return RangeEncoder The coder to continue with.
     */
    private function quantCoarse(RangeEncoder $rc): RangeEncoder
    {
        $start = $rc->tellFrac();
        $inter = clone $rc;
        $this->quantCoarseWith($rc, true);
        $intraBits = $rc->tellFrac() - $start;
        $intraError = $this->errorEnergy;
        $this->quantCoarseWith($inter, false);
        if ($inter->tellFrac() - $start > $intraBits) {
            $this->errorEnergy = $intraError;
            return $rc;
        }
        return $inter;
    }

    private function quantCoarseWith(RangeEncoder $rc, bool $intra): void
    {
        if ($rc->tell() + 3 <= $this->frameBits) {
            $rc->encodeLog($intra ? 1 : 0, 3);
        } else {
            $intra = false;
        }
        if ($intra) {
            $alpha = 0.0;
            $beta = 1.0 - 4915.0 / 32768.0;
        } else {
            $alpha = CeltTables::ALPHA_COEF[self::SIZE];
            $beta = CeltTables::BETA_COEF[self::SIZE];
        }
        $dist = CeltTables::COARSE_ENERGY_DIST[self::SIZE][$intra ? 1 : 0];
        $prev = [0.0, 0.0];
        for ($i = 0; $i < self::BANDS; $i++) {
            for ($c = 0; $c < $this->channels; $c++) {
                $left = $this->frameBits - $rc->tell();
                $last = max(-9.0, $this->lastEnergy[$c][$i]);
                $diff = $this->energy[$c][$i] - $prev[$c] - $last * $alpha;
                $q = self::rint($diff);
                if ($left >= 15) {
                    $rc->encodeLaplace($q, $dist[$i << 1] << 7, $dist[($i << 1) + 1] << 6);
                } elseif ($left >= 2) {
                    $q = max(-1, min(1, $q));
                    $rc->encodeCdf(2 * $q + 3 * ($q < 0 ? 1 : 0), CeltTables::MODEL_ENERGY_SMALL);
                } elseif ($left >= 1) {
                    $q = max(-1, min(0, $q));
                    $rc->encodeLog($q & 1, 1);
                } else {
                    $q = -1;
                }
                $this->errorEnergy[$c][$i] = $q - $diff;
                $prev[$c] += $beta * $q;
            }
        }
    }

    private function encodeTf(RangeEncoder $rc): void
    {
        $bits = 4;
        $selectNeeded = $rc->tell() + $bits + 1 <= $this->frameBits ? 1 : 0;
        $changed = 0;
        for ($i = 0; $i < self::BANDS; $i++) {
            if ($rc->tell() + $bits + $selectNeeded <= $this->frameBits) {
                // tf_change 0 everywhere: no change from the previous band.
                $rc->encodeLog(0, $bits);
            }
            $bits = 5;
        }
        if ($selectNeeded && CeltTables::TF_SELECT[self::SIZE][0][0][$changed] !== CeltTables::TF_SELECT[self::SIZE][0][1][$changed]) {
            $rc->encodeLog(0, 1);
        }
        if (CeltTables::TF_SELECT[self::SIZE][0][0][0] !== 0) {
            throw new AssertionError('Unexpected TF resolution');
        }
    }

    /** NORMC(): bits per band and channel, in eighths, scaled to the frame. */
    private function normc(int $bits): int
    {
        return ($bits << ($this->channels - 1) << self::SIZE) >> 2;
    }

    private function allocate(RangeEncoder $rc): void
    {
        $channels = $this->channels;
        $start = 0;
        $end = self::BANDS;
        $range = CeltTables::FREQ_RANGE;
        $bands = CeltTables::FREQ_BANDS;
        $skipStart = $start;
        $skipBit = 0;
        $isBit = 0;
        $dsBit = 0;
        $extrabits = 0;
        $boost = array_fill(0, self::BANDS, 0);
        $trimOffset = $threshold = $bits1 = $bits2 = array_fill(0, self::BANDS, 0);

        // Spread.
        if ($rc->tell() + 4 <= $this->frameBits) {
            $rc->encodeCdf(self::SPREAD_NORMAL, CeltTables::MODEL_SPREAD);
        }
        $caps = [];
        for ($i = 0; $i < self::BANDS; $i++) {
            $caps[$i] = $this->normc((CeltTables::STATIC_CAPS[self::SIZE][$channels - 1][$i] + 64) * $range[$i]);
        }
        $this->caps = $caps;

        // No band boosts.
        $tbits = $this->frameBits << 3;
        $dynalloc = 6;
        for ($i = $start; $i < $end; $i++) {
            if ($rc->tellFrac() + ($dynalloc << 3) < $tbits && 0 < $caps[$i]) {
                $rc->encodeLog(0, $dynalloc);
            }
        }

        // Allocation trim.
        $allocTrim = 5;
        if ($rc->tellFrac() + (6 << 3) <= $tbits) {
            $rc->encodeCdf($allocTrim, CeltTables::MODEL_ALLOC_TRIM);
        }

        // Anti-collapse (transients only), skip and stereo reservations.
        $tbits = ($this->frameBits << 3) - $rc->tellFrac() - 1;
        if ($tbits >= 1 << 3) {
            $skipBit = 1 << 3;
        }
        $tbits -= $skipBit;
        if ($channels === 2) {
            $isBit = CeltTables::LOG2_FRAC[$end - $start];
            if ($isBit <= $tbits) {
                $tbits -= $isBit;
                if ($tbits >= 1 << 3) {
                    $dsBit = 1 << 3;
                    $tbits -= 1 << 3;
                }
            } else {
                $isBit = 0;
            }
        }

        for ($i = $start; $i < $end; $i++) {
            $trim = $allocTrim - 5 - self::SIZE;
            $band = $range[$i] * ($end - $i - 1);
            $duration = self::SIZE + 3;
            $scale = $duration + $channels - 1;
            $threshold[$i] = max((3 * $range[$i] << $duration) >> 4, $channels << 3);
            $trimOffset[$i] = ($trim * ($band << $scale)) >> 6;
            if ($range[$i] << self::SIZE === 1) {
                $trimOffset[$i] -= $channels << 3;
            }
        }

        $low = 1;
        $high = self::VECTORS - 1;
        while ($low <= $high) {
            $center = ($low + $high) >> 1;
            $done = false;
            $total = 0;
            for ($i = $end - 1; $i >= $start; $i--) {
                $bandbits = $this->normc($range[$i] * CeltTables::STATIC_ALLOC[$center][$i]);
                if ($bandbits) {
                    $bandbits = max($bandbits + $trimOffset[$i], 0);
                }
                $bandbits += $boost[$i];
                if ($bandbits >= $threshold[$i] || $done) {
                    $done = true;
                    $total += min($bandbits, $caps[$i]);
                } elseif ($bandbits >= $channels << 3) {
                    $total += $channels << 3;
                }
            }
            if ($total > $tbits) {
                $high = $center - 1;
            } else {
                $low = $center + 1;
            }
        }
        $high = $low--;

        for ($i = $start; $i < $end; $i++) {
            $bits1[$i] = $this->normc($range[$i] * CeltTables::STATIC_ALLOC[$low][$i]);
            $bits2[$i] = $high >= self::VECTORS ? $caps[$i] : $this->normc($range[$i] * CeltTables::STATIC_ALLOC[$high][$i]);
            if ($bits1[$i]) {
                $bits1[$i] = max($bits1[$i] + $trimOffset[$i], 0);
            }
            if ($bits2[$i]) {
                $bits2[$i] = max($bits2[$i] + $trimOffset[$i], 0);
            }
            if ($low) {
                $bits1[$i] += $boost[$i];
            }
            $bits2[$i] += $boost[$i];
            $bits2[$i] = max($bits2[$i] - $bits1[$i], 0);
        }

        $low = 0;
        $high = 1 << self::ALLOC_STEPS;
        for ($step = 0; $step < self::ALLOC_STEPS; $step++) {
            $center = ($low + $high) >> 1;
            $done = false;
            $total = 0;
            for ($j = $end - 1; $j >= $start; $j--) {
                $bandbits = $bits1[$j] + (($center * $bits2[$j]) >> self::ALLOC_STEPS);
                if ($bandbits >= $threshold[$j] || $done) {
                    $done = true;
                    $total += min($bandbits, $caps[$j]);
                } elseif ($bandbits >= $channels << 3) {
                    $total += $channels << 3;
                }
            }
            if ($total > $tbits) {
                $high = $center;
            } else {
                $low = $center;
            }
        }

        $done = false;
        $total = 0;
        $pulses = array_fill(0, self::BANDS, 0);
        for ($i = $end - 1; $i >= $start; $i--) {
            $bandbits = $bits1[$i] + (($low * $bits2[$i]) >> self::ALLOC_STEPS);
            if ($bandbits >= $threshold[$i] || $done) {
                $done = true;
            } else {
                $bandbits = $bandbits >= $channels << 3 ? $channels << 3 : 0;
            }
            $bandbits = min($bandbits, $caps[$i]);
            $pulses[$i] = $bandbits;
            $total += $bandbits;
        }

        // Band skipping: we never skip bands ourselves (skip_band_floor is the end band).
        for ($coded = $end; ; $coded--) {
            $j = $coded - 1;
            if ($j === $skipStart) {
                $tbits += $skipBit;
                break;
            }
            $remaining = $tbits - $total;
            $span = $bands[$j + 1] - $bands[$start];
            $bandbits = intdiv($remaining, $span);
            $remaining -= $bandbits * $span;
            $allocation = $pulses[$j] + $bandbits * $range[$j];
            $allocation += max($remaining - ($bands[$j] - $bands[$start]), 0);
            if ($allocation >= max($threshold[$j], ($channels + 1) << 3)) {
                $rc->encodeLog(1, 1);
                break;
            }
            $total -= $pulses[$j];
            if ($isBit) {
                $total -= $isBit;
                $isBit = CeltTables::LOG2_FRAC[$j - $start];
                $total += $isBit;
            }
            $pulses[$j] = $allocation >= $channels << 3 ? $channels << 3 : 0;
            $total += $pulses[$j];
        }
        $this->codedBands = $coded;

        // Intensity stereo from the end band (off), and no dual stereo.
        $this->intensityStereo = $end;
        $this->dualStereo = false;
        if ($isBit) {
            $this->intensityStereo = min($this->intensityStereo, $coded);
            $rc->encodeUint($this->intensityStereo, $coded + 1 - $start);
        }
        if ($this->intensityStereo <= $start) {
            $tbits += $dsBit;
        } elseif ($dsBit) {
            $rc->encodeLog(0, 1);
        }

        $remaining = $tbits - $total;
        $span = $bands[$coded] - $bands[$start];
        $bandbits = intdiv($remaining, $span);
        $remaining -= $bandbits * $span;
        for ($i = $start; $i < $coded; $i++) {
            $bits = min($remaining, $range[$i]);
            $pulses[$i] += $bits + $bandbits * $range[$i];
            $remaining -= $bits;
        }

        $fineBits = $finePriority = array_fill(0, self::BANDS, 0);
        for ($i = $start; $i < $coded; $i++) {
            $n = $range[$i] << self::SIZE;
            $prevExtra = $extrabits;
            $pulses[$i] += $extrabits;
            if ($n > 1) {
                $extrabits = max($pulses[$i] - $caps[$i], 0);
                $pulses[$i] -= $extrabits;
                $dof = $n * $channels + ($channels === 2 && $n > 2 && !$this->dualStereo && $i < $this->intensityStereo ? 1 : 0);
                $temp = $dof * (CeltTables::LOG_FREQ_RANGE[$i] + (self::SIZE << 3));
                $offset = ($temp >> 1) - $dof * self::FINE_OFFSET;
                if ($n === 2) {
                    $offset += $dof << 1;
                }
                if ($pulses[$i] + $offset < 2 * ($dof << 3)) {
                    $offset += $temp >> 2;
                } elseif ($pulses[$i] + $offset < 3 * ($dof << 3)) {
                    $offset += $temp >> 3;
                }
                $fine = intdiv($pulses[$i] + $offset + ($dof << 2), $dof << 3);
                $maxBits = max(min(($pulses[$i] >> 3) >> ($channels - 1), self::MAX_FINE_BITS), 0);
                $fineBits[$i] = max(0, min($fine, $maxBits));
                $finePriority[$i] = $fineBits[$i] * ($dof << 3) >= $pulses[$i] + $offset ? 1 : 0;
                $pulses[$i] -= $fineBits[$i] << ($channels - 1) << 3;
            } else {
                $extrabits = max($pulses[$i] - ($channels << 3), 0);
                $pulses[$i] -= $extrabits;
                $fineBits[$i] = 0;
                $finePriority[$i] = 1;
            }
            if ($extrabits > 0) {
                $fineExtra = min($extrabits >> ($channels + 2), self::MAX_FINE_BITS - $fineBits[$i]);
                $fineBits[$i] += $fineExtra;
                $fineExtra <<= $channels + 2;
                $finePriority[$i] = $fineExtra >= $extrabits - $prevExtra ? 1 : 0;
                $extrabits -= $fineExtra;
            }
        }
        $this->remaining = $extrabits;
        for (; $i < $end; $i++) {
            $fineBits[$i] = $pulses[$i] >> ($channels - 1) >> 3;
            $pulses[$i] = 0;
            $finePriority[$i] = $fineBits[$i] < 1 ? 1 : 0;
        }
        $this->pulses = $pulses;
        $this->fineBits = $fineBits;
        $this->finePriority = $finePriority;
    }

    private function quantFine(RangeEncoder $rc): void
    {
        for ($i = 0; $i < self::BANDS; $i++) {
            $bits = $this->fineBits[$i];
            if (!$bits) {
                continue;
            }
            $lim = 1 << $bits;
            for ($c = 0; $c < $this->channels; $c++) {
                $diff = 0.5 - $this->errorEnergy[$c][$i];
                $quant = (int) max(0, min($lim - 1, floor($diff * $lim)));
                $rc->putRaw($quant, $bits);
                $offset = 0.5 - (($quant + 0.5) * (1 << (14 - $bits)) / 16384.0);
                $this->errorEnergy[$c][$i] -= $offset;
            }
        }
    }

    private function quantFinal(RangeEncoder $rc): void
    {
        for ($priority = 0; $priority < 2; $priority++) {
            for ($i = 0; $i < self::BANDS && $this->frameBits - $rc->tell() >= $this->channels; $i++) {
                if ($this->finePriority[$i] !== $priority || $this->fineBits[$i] >= self::MAX_FINE_BITS) {
                    continue;
                }
                for ($c = 0; $c < $this->channels; $c++) {
                    $err = $this->errorEnergy[$c][$i];
                    $offset = 0.5 * (1 << (14 - $this->fineBits[$i] - 1)) / 16384.0;
                    $sign = abs($err + $offset) < abs($err - $offset) ? 1 : 0;
                    $rc->putRaw($sign, 1);
                    $this->errorEnergy[$c][$i] -= $offset * (1 - 2 * $sign);
                }
            }
        }
    }

    /**
     * @param list<list<float>> $coeffs The normalized MDCT coefficients, by channel.
     */
    private function quantBands(RangeEncoder $rc, array $coeffs): void
    {
        $channels = $this->channels;
        $norm1 = $norm2 = array_fill(0, 8 * 100, 0.0);
        $totalBits = $this->frameBits << 3;
        $updateLowband = true;
        $lowbandOffset = 0;
        $this->collapseMasks = [array_fill(0, self::BANDS, 0), array_fill(0, self::BANDS, 0)];
        for ($i = 0; $i < self::BANDS; $i++) {
            $cm0 = $cm1 = 1;
            $bandOffset = CeltTables::FREQ_BANDS[$i] << self::SIZE;
            $bandSize = CeltTables::FREQ_RANGE[$i] << self::SIZE;
            $x = \array_slice($coeffs[0], $bandOffset, $bandSize);
            $y = $channels === 2 ? \array_slice($coeffs[1], $bandOffset, $bandSize) : [];
            $consumed = $rc->tellFrac();
            $effectiveLowband = -1;
            $b = 0;
            if ($i !== 0) {
                $this->remaining -= $consumed;
            }
            $this->remaining2 = $totalBits - $consumed - 1;
            if ($i <= $this->codedBands - 1) {
                $balance = intdiv($this->remaining, min(3, $this->codedBands - $i));
                $b = max(0, min((1 << 14) - 1, min($this->remaining2 + 1, $this->pulses[$i] + $balance)));
            }
            if ((CeltTables::FREQ_BANDS[$i] - CeltTables::FREQ_RANGE[$i] >= CeltTables::FREQ_BANDS[0] || $i === 1)
                && ($updateLowband || $lowbandOffset === 0)) {
                $lowbandOffset = $i;
            }
            if ($i === 1) {
                // Special hybrid folding (RFC 8251, section 9).
                $count = (CeltTables::FREQ_RANGE[$i] - CeltTables::FREQ_RANGE[$i - 1]) << self::SIZE;
                for ($k = 0; $k < $count; $k++) {
                    $norm1[$bandOffset + $k] = $norm1[$bandOffset - $count + $k];
                    $norm2[$bandOffset + $k] = $norm2[$bandOffset - $count + $k];
                }
            }
            if ($lowbandOffset !== 0) {
                // Not aggressive spreading: fold from the bands below.
                $effectiveLowband = max(
                    CeltTables::FREQ_BANDS[0],
                    CeltTables::FREQ_BANDS[$lowbandOffset] - CeltTables::FREQ_RANGE[$i],
                );
                $foldStart = $lowbandOffset;
                while (CeltTables::FREQ_BANDS[--$foldStart] > $effectiveLowband);
                $foldEnd = $lowbandOffset - 1;
                while (++$foldEnd < $i && CeltTables::FREQ_BANDS[$foldEnd] < $effectiveLowband + CeltTables::FREQ_RANGE[$i]);
                $cm0 = $cm1 = 0;
                for ($j = $foldStart; $j < $foldEnd; $j++) {
                    $cm0 |= $this->collapseMasks[0][$j];
                    $cm1 |= $this->collapseMasks[$channels - 1][$j];
                }
            }
            $lowband = $effectiveLowband !== -1 ? \array_slice($norm1, $effectiveLowband << self::SIZE, $bandSize) : null;
            $lowbandOut = [];
            $cm = $this->quantBand($rc, $i, $x, $y, $channels === 2, $bandSize, $b, 1, $lowband, self::SIZE, $lowbandOut, true, 0, 1.0, $cm0 | $cm1);
            foreach ($lowbandOut as $k => $v) {
                $norm1[$bandOffset + $k] = $v;
            }
            $this->collapseMasks[0][$i] = $cm & 0xFF;
            $this->collapseMasks[$channels - 1][$i] = $cm & 0xFF;
            $this->remaining += $this->pulses[$i] + $consumed;
            $updateLowband = $b > $bandSize << 3;
        }
    }

    private static function int16(int $x): int
    {
        $x &= 0xFFFF;
        return $x >= 0x8000 ? $x - 0x10000 : $x;
    }

    private static function roundMul16(int $a, int $b): int
    {
        return (self::int16($a) * self::int16($b) + 16384) >> 15;
    }

    private static function celtCos(int $x): int
    {
        $x = self::int16((self::int16($x) * self::int16($x) + 4096) >> 13);
        $x = self::int16((32767 - $x) + self::roundMul16($x, -7651 + self::roundMul16($x, 8277 + self::roundMul16(-626, $x))));
        return $x + 1;
    }

    private static function log2tan(int $isin, int $icos): int
    {
        $lc = RangeEncoder::ilog($icos);
        $ls = RangeEncoder::ilog($isin);
        $icos <<= 15 - $lc;
        $isin <<= 15 - $ls;
        return ($ls << 11) - ($lc << 11)
            + self::roundMul16($isin, self::roundMul16($isin, -2597) + 7932)
            - self::roundMul16($icos, self::roundMul16($icos, -2597) + 7932);
    }

    private static function pvqU(int $n, int $k): int
    {
        return CeltTables::PVQ_U[CeltTables::PVQ_U_ROW[min($n, $k)] + max($n, $k)];
    }

    private static function bitsToPulses(int $cache, int $bits): int
    {
        $c = CeltTables::CACHE_BITS;
        $low = 0;
        $high = $c[$cache];
        $bits--;
        for ($i = 0; $i < 6; $i++) {
            $center = ($low + $high + 1) >> 1;
            if ($c[$cache + $center] >= $bits) {
                $high = $center;
            } else {
                $low = $center;
            }
        }
        return ($bits - ($low === 0 ? -1 : $c[$cache + $low]) <= $c[$cache + $high] - $bits) ? $low : $high;
    }

    private static function pulsesToBits(int $cache, int $pulses): int
    {
        return $pulses === 0 ? 0 : CeltTables::CACHE_BITS[$cache + $pulses] + 1;
    }

    private function rng(): int
    {
        $this->seed = (1664525 * $this->seed + 1013904223) & 0xFFFFFFFF;
        return $this->seed;
    }

    /**
     * Quantize a band (or, recursively, part of it): the encoding half of FFmpeg's quant_band_template().
     *
     * @param list<float> $x The (normalized) band, quantized in place.
     * @param list<float> $y The second channel's band, if $stereo.
     * @param ?list<float> $lowband The folding source.
     * @param list<float> $lowbandOut Where the result is written for later folding, if $wantLowbandOut.
     */
    private function quantBand(RangeEncoder $rc, int $band, array &$x, array &$y, bool $stereo, int $n, int $b, int $blocks, ?array $lowband, int $duration, array &$lowbandOut, bool $wantLowbandOut, int $level, float $gain, int $fill): int
    {
        $n0 = $n;
        $b0 = $blocks;
        $split = $stereo;
        $inv = false;
        $mid = $side = 0.0;
        $cm = 0;

        if ($n === 1) {
            for ($ch = 0; $ch <= ($stereo ? 1 : 0); $ch++) {
                $sign = 0;
                $v = $ch ? $y[0] : $x[0];
                if ($this->remaining2 >= 1 << 3) {
                    $sign = $v < 0 ? 1 : 0;
                    $rc->putRaw($sign, 1);
                    $this->remaining2 -= 1 << 3;
                }
                if ($ch) {
                    $y[0] = 1.0 - 2.0 * $sign;
                } else {
                    $x[0] = 1.0 - 2.0 * $sign;
                }
            }
            if ($wantLowbandOut) {
                $lowbandOut = [$x[0]];
            }
            return 1;
        }

        $cache = CeltTables::CACHE_INDEX[($duration + 1) * self::BANDS + $band];
        $second = [];
        if (!$stereo && $duration >= 0 && $b > CeltTables::CACHE_BITS[$cache + CeltTables::CACHE_BITS[$cache]] + 12 && $n > 2) {
            // Split the band in two halves, coded as a mid and a side.
            $n >>= 1;
            $second = \array_slice($x, $n);
            $x = \array_slice($x, 0, $n);
            $split = true;
            $duration--;
            if ($blocks === 1) {
                $fill = ($fill & 1) | ($fill << 1);
            }
            $blocks = ($blocks + 1) >> 1;
        }

        if ($split) {
            if ($stereo) {
                $other = &$y;
            } else {
                $other = &$second;
            }
            $itheta = $this->calcTheta($x, $other, $stereo, $n);
            $pulseCap = CeltTables::LOG_FREQ_RANGE[$band] + $duration * 8;
            $offset = ($pulseCap >> 1) - ($stereo && $n === 2 ? self::QTHETA_OFFSET_TWOPHASE : self::QTHETA_OFFSET);
            $qn = ($stereo && $band >= $this->intensityStereo) ? 1 : self::computeQn($n, $b, $offset, $pulseCap, $stereo);
            $tell = $rc->tellFrac();
            if ($qn !== 1) {
                $itheta = ($itheta * $qn + 8192) >> 14;
                if ($stereo && $n > 2) {
                    $rc->encodeUintStep($itheta, intdiv($qn, 2));
                } elseif ($stereo || $b0 > 1) {
                    $rc->encodeUint($itheta, $qn + 1);
                } else {
                    $rc->encodeUintTri($itheta, $qn);
                }
                $itheta = intdiv($itheta * 16384, $qn);
                if ($stereo) {
                    if ($itheta === 0) {
                        $this->intensityDecouple($x, $other, $band, $n);
                    } else {
                        for ($i = 0; $i < $n; $i++) {
                            $l = $x[$i];
                            $x[$i] = ($l + $other[$i]) * M_SQRT1_2;
                            $other[$i] = ($other[$i] - $l) * M_SQRT1_2;
                        }
                    }
                }
            } elseif ($stereo) {
                $inv = $itheta > 8192;
                if ($inv) {
                    for ($i = 0; $i < $n; $i++) {
                        $other[$i] = -$other[$i];
                    }
                }
                $this->intensityDecouple($x, $other, $band, $n);
                if ($b > 2 << 3 && $this->remaining2 > 2 << 3) {
                    $rc->encodeLog($inv ? 1 : 0, 2);
                } else {
                    $inv = false;
                }
                $itheta = 0;
            }
            $qalloc = $rc->tellFrac() - $tell;
            $b -= $qalloc;

            $origFill = $fill;
            if ($itheta === 0) {
                $imid = 32767;
                $iside = 0;
                $fill &= (1 << $blocks) - 1;
                $delta = -16384;
            } elseif ($itheta === 16384) {
                $imid = 0;
                $iside = 32767;
                $fill &= ((1 << $blocks) - 1) << $blocks;
                $delta = 16384;
            } else {
                $imid = self::celtCos($itheta);
                $iside = self::celtCos(16384 - $itheta);
                $delta = self::roundMul16(($n - 1) << 7, self::log2tan($iside, $imid));
            }
            $mid = $imid / 32768.0;
            $side = $iside / 32768.0;

            $none = [];
            $noOut = [];
            if ($n === 2 && $stereo) {
                $mbits = $b;
                $sbits = ($itheta !== 0 && $itheta !== 16384) ? 1 << 3 : 0;
                $mbits -= $sbits;
                $c = $itheta > 8192;
                $this->remaining2 -= $qalloc + $sbits;
                if ($c) {
                    $x2 = &$other;
                    $y2 = &$x;
                } else {
                    $x2 = &$x;
                    $y2 = &$other;
                }
                $sign = 0;
                if ($sbits) {
                    $sign = $x2[0] * $y2[1] - $x2[1] * $y2[0] < 0 ? 1 : 0;
                    $rc->putRaw($sign, 1);
                }
                $sign = 1 - 2 * $sign;
                $cm = $this->quantBand($rc, $band, $x2, $none, false, $n, $mbits, $blocks, $lowband, $duration, $lowbandOut, $wantLowbandOut, $level, $gain, $origFill);
                $y2[0] = -$sign * $x2[1];
                $y2[1] = $sign * $x2[0];
                unset($x2, $y2);
                $x[0] *= $mid;
                $x[1] *= $mid;
                $other[0] *= $side;
                $other[1] *= $side;
                $tmp = $x[0];
                $x[0] = $tmp - $other[0];
                $other[0] = $tmp + $other[0];
                $tmp = $x[1];
                $x[1] = $tmp - $other[1];
                $other[1] = $tmp + $other[1];
            } else {
                $mbits = max(0, min($b, intdiv($b - $delta, 2)));
                $sbits = $b - $mbits;
                $this->remaining2 -= $qalloc;
                $nextLowband2 = $lowband !== null && !$stereo ? \array_slice($lowband, $n) : null;
                $nextLevel = $stereo ? 0 : $level + 1;
                $rebalance = $this->remaining2;
                $shift = $stereo ? 0 : ($b0 >> 1);
                if ($mbits >= $sbits) {
                    $cm = $this->quantBand($rc, $band, $x, $none, false, $n, $mbits, $blocks, $lowband, $duration, $lowbandOut, $stereo && $wantLowbandOut, $nextLevel, $stereo ? 1.0 : $gain * $mid, $fill);
                    $rebalance = $mbits - ($rebalance - $this->remaining2);
                    if ($rebalance > 3 << 3 && $itheta !== 0) {
                        $sbits += $rebalance - (3 << 3);
                    }
                    $cm |= $this->quantBand($rc, $band, $other, $none, false, $n, $sbits, $blocks, $nextLowband2, $duration, $noOut, false, $nextLevel, $gain * $side, $fill >> $blocks) << $shift;
                } else {
                    $cm = $this->quantBand($rc, $band, $other, $none, false, $n, $sbits, $blocks, $nextLowband2, $duration, $noOut, false, $nextLevel, $gain * $side, $fill >> $blocks) << $shift;
                    $rebalance = $sbits - ($rebalance - $this->remaining2);
                    if ($rebalance > 3 << 3 && $itheta !== 16384) {
                        $mbits += $rebalance - (3 << 3);
                    }
                    $cm |= $this->quantBand($rc, $band, $x, $none, false, $n, $mbits, $blocks, $lowband, $duration, $lowbandOut, $stereo && $wantLowbandOut, $nextLevel, $stereo ? 1.0 : $gain * $mid, $fill);
                }
            }
            unset($other);
            if (!$stereo) {
                // The two halves back together.
                array_push($x, ...$second);
                $n = $n0;
            }
        } else {
            $q = self::bitsToPulses($cache, $b);
            $currBits = self::pulsesToBits($cache, $q);
            $this->remaining2 -= $currBits;
            while ($this->remaining2 < 0 && $q > 0) {
                $this->remaining2 += $currBits;
                $currBits = self::pulsesToBits($cache, --$q);
                $this->remaining2 -= $currBits;
            }
            if ($q !== 0) {
                $k = $q < 8 ? $q : (8 + ($q & 7)) << (($q >> 3) - 1);
                $cm = $this->algQuant($rc, $x, $n, $k, $blocks, $gain);
            } else {
                $mask = (1 << $blocks) - 1;
                $fill &= $mask;
                if ($fill) {
                    if ($lowband === null) {
                        for ($i = 0; $i < $n; $i++) {
                            $r = $this->rng();
                            $x[$i] = (float) (($r >= 0x80000000 ? $r - 0x100000000 : $r) >> 20);
                        }
                        $cm = $mask;
                    } else {
                        for ($i = 0; $i < $n; $i++) {
                            $x[$i] = $lowband[$i] + (($this->rng() & 0x8000) ? 1.0 / 256 : -1.0 / 256);
                        }
                        $cm = $fill;
                    }
                    self::renormalize($x, $n, $gain);
                } else {
                    $x = array_fill(0, $n, 0.0);
                }
            }
        }

        if ($stereo) {
            if ($n > 2) {
                self::stereoMerge($x, $y, $mid, $n);
            }
            if ($inv) {
                for ($i = 0; $i < $n; $i++) {
                    $y[$i] = -$y[$i];
                }
            }
        } elseif ($level === 0) {
            if ($wantLowbandOut) {
                $scale = sqrt($n0);
                $lowbandOut = [];
                for ($i = 0; $i < $n0; $i++) {
                    $lowbandOut[] = $scale * $x[$i];
                }
            }
            $cm &= (1 << $blocks) - 1;
        }
        return $cm;
    }

    /** @param list<float> $x @param list<float> $y */
    private function calcTheta(array $x, array $y, bool $coupling, int $n): int
    {
        $e0 = $e1 = 0.0;
        if ($coupling) {
            for ($i = 0; $i < $n; $i++) {
                $e0 += ($x[$i] + $y[$i]) ** 2;
                $e1 += ($x[$i] - $y[$i]) ** 2;
            }
        } else {
            for ($i = 0; $i < $n; $i++) {
                $e0 += $x[$i] * $x[$i];
                $e1 += $y[$i] * $y[$i];
            }
        }
        return self::rint(32768.0 * atan2(sqrt($e1), sqrt($e0)) / M_PI);
    }

    private static function computeQn(int $n, int $b, int $offset, int $pulseCap, bool $stereo): int
    {
        $n2 = 2 * $n - 1;
        if ($stereo && $n === 2) {
            $n2--;
        }
        $qb = min($b - $pulseCap - (4 << 3), intdiv($b + $n2 * $offset, $n2), 8 << 3);
        return $qb < (1 << 3 >> 1) ? 1 : ((((CeltTables::QN_EXP2[$qb & 0x7] >> (14 - ($qb >> 3))) + 1) >> 1) << 1);
    }

    /** @param list<float> $x @param list<float> $y */
    private function intensityDecouple(array &$x, array $y, int $band, int $n): void
    {
        $l = $this->linEnergy[0][$band];
        $r = $this->linEnergy[1][$band];
        $norm = 1.0 / (sqrt($l * $l + $r * $r) + self::FLT_EPSILON);
        $l *= $norm;
        $r *= $norm;
        for ($i = 0; $i < $n; $i++) {
            $x[$i] = $l * $x[$i] + $r * $y[$i];
        }
    }

    /** @param list<float> $x @param list<float> $y */
    private static function stereoMerge(array &$x, array &$y, float $mid, int $n): void
    {
        $xp = $side = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $xp += $x[$i] * $y[$i];
            $side += $y[$i] * $y[$i];
        }
        $xp *= $mid;
        $e0 = $mid * $mid + $side - 2 * $xp;
        $e1 = $mid * $mid + $side + 2 * $xp;
        if ($e0 < 6e-4 || $e1 < 6e-4) {
            $y = $x;
            return;
        }
        $g0 = 1.0 / sqrt($e0);
        $g1 = 1.0 / sqrt($e1);
        for ($i = 0; $i < $n; $i++) {
            $v0 = $mid * $x[$i];
            $v1 = $y[$i];
            $x[$i] = $g0 * ($v0 - $v1);
            $y[$i] = $g1 * ($v0 + $v1);
        }
    }

    /** @param list<float> $x */
    private static function renormalize(array &$x, int $n, float $gain): void
    {
        $g = 1e-15;
        for ($i = 0; $i < $n; $i++) {
            $g += $x[$i] * $x[$i];
        }
        $g = $gain / sqrt($g);
        for ($i = 0; $i < $n; $i++) {
            $x[$i] *= $g;
        }
    }

    /**
     * PVQ: the pulses closest to the (rotated) band, coded as an index.
     *
     * @param list<float> $x
     */
    private function algQuant(RangeEncoder $rc, array &$x, int $n, int $k, int $blocks, float $gain): int
    {
        self::expRotation($x, $n, $blocks, $k, true);
        [$y, $yNorm] = self::pvqSearch($x, $k, $n);
        $gain /= sqrt($yNorm);
        // celt_icwrsi(): the index of the vector.
        $idx = 0;
        $sum = 0;
        for ($i = $n - 1; $i >= 0; $i--) {
            $abs = abs($y[$i]);
            $is = self::pvqU($n - $i, $sum + $abs + 1);
            $idx += self::pvqU($n - $i, $sum) + ($y[$i] < 0 ? $is : 0);
            $sum += $abs;
        }
        $rc->encodeUint($idx, self::pvqU($n, $k) + self::pvqU($n, $k + 1));
        for ($i = 0; $i < $n; $i++) {
            $x[$i] = $gain * $y[$i];
        }
        self::expRotation($x, $n, $blocks, $k, false);
        if ($blocks <= 1) {
            return 1;
        }
        $mask = 0;
        $n0 = intdiv($n, $blocks);
        for ($i = 0; $i < $blocks; $i++) {
            for ($j = 0; $j < $n0; $j++) {
                if ($y[$i * $n0 + $j]) {
                    $mask |= 1 << $i;
                }
            }
        }
        return $mask;
    }

    /**
     * FFmpeg's PVQ search: start from the scaled vector, then add or remove one pulse at a time where it helps most.
     *
     * @param list<float> $x
     * @return array{list<int>, float}
     */
    private static function pvqSearch(array $x, int $k, int $n): array
    {
        $res = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $res += abs($x[$i]);
        }
        $res = $k / ($res + self::FLT_EPSILON);
        $y = [];
        $yNorm = 0;
        $xyNorm = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $y[$i] = self::rint($res * $x[$i]);
            $yNorm += $y[$i] * $y[$i];
            $xyNorm += $y[$i] * $x[$i];
            $k -= abs($y[$i]);
        }
        while ($k) {
            $maxIdx = 0;
            $phase = $k > 0 ? 1 : -1;
            $maxNum = 0.0;
            $maxDen = 1.0;
            $yNorm += 1;
            for ($i = 0; $i < $n; $i++) {
                $ca = !($y[$i] === 0 && $phase < 0);
                $yNew = $yNorm + 2 * $phase * abs($y[$i]);
                $xyNew = $xyNorm + $phase * abs($x[$i]);
                $xyNew *= $xyNew;
                if ($ca && $maxDen * $xyNew > $yNew * $maxNum) {
                    $maxDen = $yNew;
                    $maxNum = $xyNew;
                    $maxIdx = $i;
                }
            }
            $k -= $phase;
            $phase *= $x[$maxIdx] > 0 ? 1 : -1;
            $xyNorm += $phase * $x[$maxIdx];
            $yNorm += 2 * $phase * $y[$maxIdx];
            $y[$maxIdx] += $phase;
        }
        return [$y, (float) $yNorm];
    }

    /** The spreading rotation (normal spreading), forward when encoding and backward to resynthesize. */
    private static function expRotation(array &$x, int $len, int $stride, int $k, bool $encode): void
    {
        if (2 * $k >= $len) {
            return;
        }
        $gain = $len / ($len + (20 - 5 * self::SPREAD_NORMAL) * $k);
        $theta = M_PI * $gain * $gain / 4;
        $c = cos($theta);
        $s = sin($theta);
        $stride2 = 0;
        if ($len >= $stride << 3) {
            $stride2 = 1;
            while (($stride2 * $stride2 + $stride2) * $stride + ($stride >> 2) < $len) {
                $stride2++;
            }
        }
        $sub = intdiv($len, $stride);
        for ($i = 0; $i < $stride; $i++) {
            $base = $i * $sub;
            if ($encode) {
                self::rotate($x, $base, $sub, 1, $c, -$s);
                if ($stride2) {
                    self::rotate($x, $base, $sub, $stride2, $s, -$c);
                }
            } else {
                if ($stride2) {
                    self::rotate($x, $base, $sub, $stride2, $s, $c);
                }
                self::rotate($x, $base, $sub, 1, $c, $s);
            }
        }
    }

    private static function rotate(array &$x, int $base, int $len, int $stride, float $c, float $s): void
    {
        for ($i = 0; $i < $len - $stride; $i++) {
            $x1 = $x[$base + $i];
            $x2 = $x[$base + $i + $stride];
            $x[$base + $i + $stride] = $c * $x2 + $s * $x1;
            $x[$base + $i] = $c * $x1 - $s * $x2;
        }
        for ($i = $len - 2 * $stride - 1; $i >= 0; $i--) {
            $x1 = $x[$base + $i];
            $x2 = $x[$base + $i + $stride];
            $x[$base + $i + $stride] = $c * $x2 + $s * $x1;
            $x[$base + $i] = $c * $x1 - $s * $x2;
        }
    }
}
