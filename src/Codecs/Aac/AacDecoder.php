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
use danog\MadelineProto\Codecs\Mdct;

/**
 * A pure PHP AAC-LC decoder (ISO/IEC 14496-3), for the raw access units of an MP4 or Matroska track.
 *
 * HE-AAC (SBR, parametric stereo) is decoded from its AAC-LC core only, at the core's sampling rate: the SBR and PS
 * extension payloads are skipped. More than two channels are mixed down to stereo.
 *
 * @internal
 */
final class AacDecoder
{
    private const ONLY_LONG = 0;
    private const LONG_START = 1;
    private const EIGHT_SHORT = 2;
    private const LONG_STOP = 3;

    private const ZERO_BT = 0;
    private const NOISE_BT = 13;
    private const INTENSITY_BT2 = 14;
    private const INTENSITY_BT = 15;

    private const SAMPLE_RATES = [96000, 88200, 64000, 48000, 44100, 32000, 24000, 22050, 16000, 12000, 11025, 8000, 7350];

    /** The sampling rate of the decoded samples (the AAC-LC core's, for HE-AAC). */
    public readonly int $sampleRate;
    /** The channels of the decoded samples: 1 or 2. */
    public readonly int $channels;
    private readonly int $rateIndex;
    /** The channels of the stream's configuration (8 for 7.1): a frame can't have more. */
    private readonly int $streamChannels;

    /** @var array<int, array<int, list<array{int, int}>>> Huffman decoding maps by codebook (0: scalefactors), by code length: code => symbol. */
    private static array $huffman = [];
    /** @var list<float> |q|^(4/3). */
    private static array $pow43 = [];
    /** @var array<string, list<float>> The rising halves of the windows. */
    private static array $windows = [];

    private readonly Mdct $long;
    private readonly Mdct $short;

    /** @var array<int, list<float>> The second half of the previous frame's output, by channel of the stream. */
    private array $overlap = [];
    /** @var array<int, int> The previous frame's window shape (0 sine, 1 KBD), by channel of the stream. */
    private array $previousShape = [];
    private int $random = 0x1f2e3d4c;

    /** @param string $config The AudioSpecificConfig (MP4 esds DecoderSpecificInfo, Matroska CodecPrivate). */
    public function __construct(string $config)
    {
        self::init();
        $bits = new BitReader($config);
        $objectType = self::objectType($bits);
        $rateIndex = $bits->read(4);
        if ($rateIndex === 15) {
            throw new AssertionError('AAC streams with an explicit sampling rate are not supported');
        }
        $channels = $bits->read(4);
        if ($objectType === 5 || $objectType === 29) {
            // Explicit HE-AAC: the extension (SBR) rate, then the core's object type.
            $extension = $bits->read(4);
            if ($extension === 15) {
                $bits->read(24);
            }
            $objectType = self::objectType($bits);
        }
        if ($objectType !== 2) {
            throw new AssertionError("Only AAC-LC (and the AAC-LC core of HE-AAC) can be decoded, not audio object type $objectType");
        }
        if ($bits->read(1)) {
            throw new AssertionError('AAC streams with 960-sample frames are not supported');
        }
        if ($channels < 1 || $channels > 7) {
            throw new AssertionError("AAC streams with channel configuration $channels are not supported");
        }
        if (!isset(self::SAMPLE_RATES[$rateIndex])) {
            throw new AssertionError("Invalid AAC sampling frequency index $rateIndex");
        }
        $this->rateIndex = $rateIndex;
        $this->sampleRate = self::SAMPLE_RATES[$rateIndex];
        $this->channels = $channels === 1 ? 1 : 2;
        $this->streamChannels = $channels === 7 ? 8 : $channels;
        $this->long = new Mdct(1024);
        $this->short = new Mdct(128);
    }

    private static function objectType(BitReader $bits): int
    {
        $type = $bits->read(5);
        return $type === 31 ? 32 + $bits->read(6) : $type;
    }

    private static function init(): void
    {
        if (self::$pow43 !== []) {
            return;
        }
        for ($i = 0; $i < 8192; $i++) {
            self::$pow43[] = $i ** (4 / 3);
        }
        self::$huffman[0] = self::huffmanMap(AacTables::SCALEFACTOR_CODES, AacTables::SCALEFACTOR_BITS);
        foreach (AacTables::SPECTRAL_CODES as $i => $codes) {
            self::$huffman[$i + 1] = self::huffmanMap($codes, AacTables::SPECTRAL_BITS[$i]);
        }
        self::$windows = [
            'sine1024' => self::sineWindow(2048),
            'sine128' => self::sineWindow(256),
            'kbd1024' => self::kbdWindow(2048, 4),
            'kbd128' => self::kbdWindow(256, 6),
        ];
    }

    /**
     * @param list<int> $codes
     * @param list<int> $lengths
     * @return array<int, array<int, int>>
     */
    private static function huffmanMap(array $codes, array $lengths): array
    {
        $map = [];
        foreach ($codes as $symbol => $code) {
            $map[$lengths[$symbol]][$code] = $symbol;
        }
        ksort($map);
        return $map;
    }

    /** @return list<float> The rising half of a sine window of $n samples. */
    private static function sineWindow(int $n): array
    {
        $w = [];
        for ($i = 0; $i < $n >> 1; $i++) {
            $w[] = sin(M_PI / $n * ($i + 0.5));
        }
        return $w;
    }

    /** @return list<float> The rising half of a Kaiser-Bessel-derived window of $n samples. */
    private static function kbdWindow(int $n, float $alpha): array
    {
        $half = $n >> 1;
        $kaiser = [];
        $sum = 0.0;
        for ($j = 0; $j <= $half; $j++) {
            $t = ($j - $half / 2) / ($half / 2);
            $kaiser[$j] = self::besselI0(M_PI * $alpha * sqrt(max(0.0, 1 - $t * $t)));
            $sum += $kaiser[$j];
        }
        $w = [];
        $acc = 0.0;
        for ($i = 0; $i < $half; $i++) {
            $acc += $kaiser[$i];
            $w[] = sqrt($acc / $sum);
        }
        return $w;
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
     * Decode one access unit (raw_data_block) into 1024 samples per output channel, in [-1, 1].
     *
     * @return list<list<float>> The samples, by channel.
     */
    public function decode(string $frame): array
    {
        $bits = new BitReader($frame);
        /** @var list<array{0: string, 1: list<float>, 2?: list<float>}> $elements The decoded elements: type, then the samples of their channels. */
        $elements = [];
        $channel = 0;
        while (true) {
            $id = $bits->read(3);
            if ($id === 7) {
                break;
            }
            if (($id === 0 || $id === 3) && $channel >= $this->streamChannels || $id === 1 && $channel + 2 > $this->streamChannels) {
                // Each channel costs an inverse MDCT: a frame can't make us decode more than the configuration has.
                throw new AssertionError('The AAC frame has more channels than its configuration');
            }
            switch ($id) {
                case 0: // SCE
                case 3: // LFE
                    $bits->read(4);
                    $ics = $this->decodeIcs($bits, null);
                    $elements[] = [$id === 0 ? 'sce' : 'lfe', $this->synthesize($ics, $channel++)];
                    break;
                case 1: // CPE
                    $bits->read(4);
                    $common = $bits->read(1) === 1;
                    $info = null;
                    $msMask = [];
                    if ($common) {
                        $info = $this->decodeIcsInfo($bits);
                        $msPresent = $bits->read(2);
                        $count = $info['groups'] * $info['maxSfb'];
                        if ($msPresent === 1) {
                            for ($i = 0; $i < $count; $i++) {
                                $msMask[$i] = $bits->read(1);
                            }
                        } elseif ($msPresent === 2) {
                            $msMask = array_fill(0, $count, 1);
                        }
                    }
                    $left = $this->decodeIcs($bits, $info);
                    $right = $this->decodeIcs($bits, $info);
                    if ($common) {
                        $this->applyStereo($left, $right, $msMask);
                    }
                    $elements[] = ['cpe', $this->synthesize($left, $channel), $this->synthesize($right, $channel + 1)];
                    $channel += 2;
                    break;
                case 2:
                    throw new AssertionError('AAC coupling channel elements are not supported');
                case 4: // DSE
                    $bits->read(4);
                    $align = $bits->read(1);
                    $count = $bits->read(8);
                    if ($count === 255) {
                        $count += $bits->read(8);
                    }
                    if ($align) {
                        $bits->align();
                    }
                    $bits->skip(8 * $count);
                    break;
                case 5: // PCE
                    $this->skipProgramConfig($bits);
                    break;
                case 6: // FIL: SBR and PS payloads, among others, all skipped
                    $count = $bits->read(4);
                    if ($count === 15) {
                        $count += $bits->read(8) - 1;
                    }
                    $bits->skip(8 * $count);
                    break;
            }
        }
        return $this->mixDown($elements);
    }

    /**
     * @param list<array{0: string, 1: list<float>, 2?: list<float>}> $elements
     * @return list<list<float>>
     */
    private function mixDown(array $elements): array
    {
        if ($this->channels === 1) {
            return [$elements[0][1] ?? array_fill(0, 1024, 0.0)];
        }
        if (\count($elements) === 1 && $elements[0][0] === 'cpe') {
            return [$elements[0][1], $elements[0][2] ?? $elements[0][1]];
        }
        // Center, front pair, then surround pairs or back center (ISO/IEC 14496-3 channel configurations 3-7).
        $left = $right = array_fill(0, 1024, 0.0);
        $front = true;
        $norm = 0.0;
        foreach ($elements as $i => $element) {
            if ($element[0] === 'lfe') {
                continue;
            }
            if ($element[0] === 'sce') {
                $gain = $i === 0 ? M_SQRT1_2 : 0.5;
                $norm += $gain;
                for ($n = 0; $n < 1024; $n++) {
                    $left[$n] += $gain * $element[1][$n];
                    $right[$n] += $gain * $element[1][$n];
                }
                continue;
            }
            $gain = $front ? 1.0 : M_SQRT1_2;
            $front = false;
            $norm += $gain;
            $r = $element[2] ?? $element[1];
            for ($n = 0; $n < 1024; $n++) {
                $left[$n] += $gain * $element[1][$n];
                $right[$n] += $gain * $r[$n];
            }
        }
        if ($norm > 1.0) {
            for ($n = 0; $n < 1024; $n++) {
                $left[$n] /= $norm;
                $right[$n] /= $norm;
            }
        }
        return [$left, $right];
    }

    /**
     * @return array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int}
     */
    private function decodeIcsInfo(BitReader $bits): array
    {
        $bits->read(1);
        $sequence = $bits->read(2);
        $shape = $bits->read(1);
        if ($sequence === self::EIGHT_SHORT) {
            $maxSfb = $bits->read(4);
            $grouping = $bits->read(7);
            $groupLen = [1];
            for ($i = 6; $i >= 0; $i--) {
                if (($grouping >> $i) & 1) {
                    $groupLen[\count($groupLen) - 1]++;
                } else {
                    $groupLen[] = 1;
                }
            }
            $swb = AacTables::SWB_OFFSET_128[$this->rateIndex];
            $numSwb = AacTables::NUM_SWB_128[$this->rateIndex];
            $windows = 8;
        } else {
            $maxSfb = $bits->read(6);
            if ($bits->read(1)) {
                throw new AssertionError('AAC prediction is not part of AAC-LC');
            }
            $groupLen = [1];
            $swb = AacTables::SWB_OFFSET_1024[$this->rateIndex];
            $numSwb = AacTables::NUM_SWB_1024[$this->rateIndex];
            $windows = 1;
        }
        if ($maxSfb > $numSwb) {
            throw new AssertionError("Invalid AAC max_sfb $maxSfb");
        }
        return [
            'sequence' => $sequence,
            'shape' => $shape,
            'maxSfb' => $maxSfb,
            'groups' => \count($groupLen),
            'groupLen' => $groupLen,
            'windows' => $windows,
            'swb' => $swb,
            'numSwb' => $numSwb,
        ];
    }

    /**
     * Decode an individual_channel_stream: its dequantized spectrum, with noise filled and TNS applied later.
     *
     * @param ?array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int} $info The common ics_info of a CPE.
     * @return array{info: array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int}, spec: list<float>, bandTypes: list<int>, scales: list<float>, tns: ?list<list<array{int, int, int, list<float>}>>}
     */
    private function decodeIcs(BitReader $bits, ?array $info): array
    {
        $globalGain = $bits->read(8);
        $info ??= $this->decodeIcsInfo($bits);
        $maxSfb = $info['maxSfb'];
        $short = $info['sequence'] === self::EIGHT_SHORT;

        // Section data.
        $bandTypes = [];
        $sectionBits = $short ? 3 : 5;
        $escape = (1 << $sectionBits) - 1;
        for ($g = 0; $g < $info['groups']; $g++) {
            $k = 0;
            while ($k < $maxSfb) {
                $type = $bits->read(4);
                if ($type === 12) {
                    throw new AssertionError('Invalid AAC band type 12');
                }
                $len = 0;
                do {
                    $increment = $bits->read($sectionBits);
                    $len += $increment;
                } while ($increment === $escape);
                if ($k + $len > $maxSfb) {
                    throw new AssertionError('Invalid AAC section data');
                }
                for ($i = 0; $i < $len; $i++) {
                    $bandTypes[$g * $maxSfb + $k++] = $type;
                }
            }
        }

        // Scalefactors, as gains.
        $scales = [];
        $offset = $globalGain;
        $noise = $globalGain - 90;
        $intensity = 0;
        $firstNoise = true;
        $count = $info['groups'] * $maxSfb;
        for ($idx = 0; $idx < $count; $idx++) {
            $type = $bandTypes[$idx];
            if ($type === self::ZERO_BT) {
                $scales[$idx] = 0.0;
            } elseif ($type === self::INTENSITY_BT || $type === self::INTENSITY_BT2) {
                $intensity += $this->huffman($bits, 0) - 60;
                $scales[$idx] = 2 ** (-max(-155, min(100, $intensity)) / 4);
            } elseif ($type === self::NOISE_BT) {
                if ($firstNoise) {
                    $firstNoise = false;
                    $noise += $bits->read(9) - 256;
                } else {
                    $noise += $this->huffman($bits, 0) - 60;
                }
                $scales[$idx] = 2 ** (max(-100, min(155, $noise)) / 4);
            } else {
                $offset += $this->huffman($bits, 0) - 60;
                if ($offset < 0 || $offset > 255) {
                    throw new AssertionError("AAC scalefactor $offset out of range");
                }
                $scales[$idx] = 2 ** (($offset - 100) / 4);
            }
        }

        // Pulse data (long windows only).
        $pulses = [];
        if ($bits->read(1)) {
            if ($short) {
                throw new AssertionError('AAC pulse data in short windows');
            }
            $num = $bits->read(2) + 1;
            $startSfb = $bits->read(6);
            if ($startSfb >= $info['numSwb']) {
                throw new AssertionError('Invalid AAC pulse data');
            }
            $pos = $info['swb'][$startSfb];
            for ($i = 0; $i < $num; $i++) {
                $pos += $bits->read(5);
                $pulses[$pos] = $bits->read(4);
            }
        }

        // TNS data.
        $tns = null;
        if ($bits->read(1)) {
            $tns = [];
            for ($w = 0; $w < $info['windows']; $w++) {
                $filters = [];
                $nFilt = $bits->read($short ? 1 : 2);
                if ($nFilt) {
                    $coefRes = $bits->read(1);
                    for ($f = 0; $f < $nFilt; $f++) {
                        $length = $bits->read($short ? 4 : 6);
                        $order = $bits->read($short ? 3 : 5);
                        if ($order > ($short ? 7 : 12)) {
                            throw new AssertionError("Invalid AAC TNS order $order");
                        }
                        $direction = 0;
                        $coefs = [];
                        if ($order) {
                            $direction = $bits->read(1);
                            $compress = $bits->read(1);
                            $table = AacTables::TNS_COEFS[2 * $compress + $coefRes];
                            $coefBits = $coefRes + 3 - $compress;
                            for ($i = 0; $i < $order; $i++) {
                                $coefs[] = $table[$bits->read($coefBits)];
                            }
                        }
                        $filters[] = [$length, $order, $direction, $coefs];
                    }
                }
                $tns[] = $filters;
            }
        }

        if ($bits->read(1)) {
            throw new AssertionError('AAC gain control is not part of AAC-LC');
        }

        // Spectral data: band by band, and within a band window by window of its group.
        $spec = array_fill(0, 1024, 0.0);
        $swb = $info['swb'];
        $pow43 = self::$pow43;
        $window = 0;
        $idx = 0;
        foreach ($info['groupLen'] as $g => $groupLen) {
            for ($sfb = 0; $sfb < $maxSfb; $sfb++, $idx++) {
                $type = $bandTypes[$idx];
                $start = $swb[$sfb];
                $width = $swb[$sfb + 1] - $start;
                if ($type === self::ZERO_BT || $type === self::INTENSITY_BT || $type === self::INTENSITY_BT2) {
                    continue;
                }
                if ($type === self::NOISE_BT) {
                    for ($w = 0; $w < $groupLen; $w++) {
                        $base = ($window + $w) * 128 + $start;
                        $energy = 0.0;
                        for ($i = 0; $i < $width; $i++) {
                            $this->random = ($this->random * 1664525 + 1013904223) & 0xFFFFFFFF;
                            $v = (float) ($this->random >= 0x80000000 ? $this->random - 0x100000000 : $this->random);
                            $spec[$base + $i] = $v;
                            $energy += $v * $v;
                        }
                        $scale = $energy > 0 ? $scales[$idx] / sqrt($energy) : 0.0;
                        for ($i = 0; $i < $width; $i++) {
                            $spec[$base + $i] *= $scale;
                        }
                    }
                    continue;
                }
                $scale = $scales[$idx];
                $signed = $type === 1 || $type === 2 || $type === 5 || $type === 6;
                $quad = $type <= 4;
                $mod = match ($type) {
                    1, 2, 3, 4 => 3,
                    5, 6 => 9,
                    7, 8 => 8,
                    9, 10 => 13,
                    default => 17,
                };
                $off = $signed ? ($quad ? 1 : 4) : 0;
                for ($w = 0; $w < $groupLen; $w++) {
                    $base = ($window + $w) * 128 + $start;
                    for ($i = 0; $i < $width; $i += $quad ? 4 : 2) {
                        $symbol = $this->huffman($bits, $type);
                        if ($quad) {
                            $values = [
                                intdiv($symbol, 27) - $off,
                                intdiv($symbol, 9) % 3 - $off,
                                intdiv($symbol, 3) % 3 - $off,
                                $symbol % 3 - $off,
                            ];
                        } else {
                            $values = [intdiv($symbol, $mod) - $off, $symbol % $mod - $off];
                        }
                        if (!$signed) {
                            foreach ($values as $j => $v) {
                                if ($v !== 0 && $bits->read(1)) {
                                    $values[$j] = -$v;
                                }
                            }
                            if ($type === 11) {
                                foreach ($values as $j => $v) {
                                    if ($v === 16 || $v === -16) {
                                        $n = 0;
                                        while ($bits->read(1)) {
                                            if (++$n > 8) {
                                                throw new AssertionError('Invalid AAC escape sequence');
                                            }
                                        }
                                        $esc = (1 << ($n + 4)) + $bits->read($n + 4);
                                        $values[$j] = $v < 0 ? -$esc : $esc;
                                    }
                                }
                            }
                        }
                        foreach ($values as $j => $v) {
                            $pos = $base + $i + $j;
                            if (isset($pulses[$pos])) {
                                $v += $v > 0 ? $pulses[$pos] : -$pulses[$pos];
                            }
                            if ($v !== 0) {
                                $spec[$pos] = $v > 0 ? $pow43[$v] * $scale : -$pow43[-$v] * $scale;
                            }
                        }
                    }
                }
            }
            $window += $groupLen;
        }
        return ['info' => $info, 'spec' => $spec, 'bandTypes' => $bandTypes, 'scales' => $scales, 'tns' => $tns];
    }

    /**
     * Mid/side and intensity stereo of a CPE with a common window.
     *
     * @param array{info: array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int}, spec: list<float>, bandTypes: list<int>, scales: list<float>, tns: ?list<list<array{int, int, int, list<float>}>>} $left
     * @param array{info: array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int}, spec: list<float>, bandTypes: list<int>, scales: list<float>, tns: ?list<list<array{int, int, int, list<float>}>>} $right
     * @param array<int, int> $msMask
     */
    private function applyStereo(array &$left, array &$right, array $msMask): void
    {
        $info = $left['info'];
        $swb = $info['swb'];
        $window = 0;
        $idx = 0;
        foreach ($info['groupLen'] as $groupLen) {
            for ($sfb = 0; $sfb < $info['maxSfb']; $sfb++, $idx++) {
                $start = $swb[$sfb];
                $end = $swb[$sfb + 1];
                $typeR = $right['bandTypes'][$idx];
                $ms = ($msMask[$idx] ?? 0) === 1;
                if ($typeR === self::INTENSITY_BT || $typeR === self::INTENSITY_BT2) {
                    $scale = ($typeR === self::INTENSITY_BT ? 1 : -1) * ($ms ? -1 : 1) * $right['scales'][$idx];
                    for ($w = 0; $w < $groupLen; $w++) {
                        $base = ($window + $w) * 128;
                        for ($i = $start; $i < $end; $i++) {
                            $right['spec'][$base + $i] = $left['spec'][$base + $i] * $scale;
                        }
                    }
                } elseif ($ms && $left['bandTypes'][$idx] < self::NOISE_BT && $typeR < self::NOISE_BT) {
                    for ($w = 0; $w < $groupLen; $w++) {
                        $base = ($window + $w) * 128;
                        for ($i = $start; $i < $end; $i++) {
                            $m = $left['spec'][$base + $i];
                            $s = $right['spec'][$base + $i];
                            $left['spec'][$base + $i] = $m + $s;
                            $right['spec'][$base + $i] = $m - $s;
                        }
                    }
                }
            }
            $window += $groupLen;
        }
    }

    /**
     * TNS, the inverse filterbank and the overlap-add of a channel.
     *
     * @param array{info: array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int}, spec: list<float>, bandTypes: list<int>, scales: list<float>, tns: ?list<list<array{int, int, int, list<float>}>>} $ics
     * @return list<float> 1024 samples.
     */
    private function synthesize(array $ics, int $channel): array
    {
        $info = $ics['info'];
        $spec = $ics['spec'];
        if ($ics['tns'] !== null) {
            $this->applyTns($spec, $ics['tns'], $info);
        }
        $sequence = $info['sequence'];
        $shape = $info['shape'];
        $previous = $this->previousShape[$channel] ?? 0;
        $this->previousShape[$channel] = $shape;
        $longPrev = self::$windows[$previous ? 'kbd1024' : 'sine1024'];
        $longCur = self::$windows[$shape ? 'kbd1024' : 'sine1024'];
        $shortPrev = self::$windows[$previous ? 'kbd128' : 'sine128'];
        $shortCur = self::$windows[$shape ? 'kbd128' : 'sine128'];
        // 2/N of the inverse MDCT, and the scale of 16-bit samples.
        if ($sequence === self::EIGHT_SHORT) {
            $out = array_fill(0, 2048, 0.0);
            $scale = 1 / (128 * 32768);
            for ($w = 0; $w < 8; $w++) {
                $x = $this->short->imdct(\array_slice($spec, $w * 128, 128));
                $rise = $w === 0 ? $shortPrev : $shortCur;
                $base = 448 + 128 * $w;
                for ($n = 0; $n < 128; $n++) {
                    $out[$base + $n] += $x[$n] * $rise[$n] * $scale;
                    $out[$base + 128 + $n] += $x[128 + $n] * $shortCur[127 - $n] * $scale;
                }
            }
        } else {
            $x = $this->long->imdct($spec);
            $scale = 1 / (1024 * 32768);
            $out = [];
            if ($sequence === self::LONG_STOP) {
                for ($n = 0; $n < 448; $n++) {
                    $out[] = 0.0;
                }
                for ($n = 0; $n < 128; $n++) {
                    $out[] = $x[448 + $n] * $shortPrev[$n] * $scale;
                }
                for ($n = 576; $n < 1024; $n++) {
                    $out[] = $x[$n] * $scale;
                }
            } else {
                for ($n = 0; $n < 1024; $n++) {
                    $out[] = $x[$n] * $longPrev[$n] * $scale;
                }
            }
            if ($sequence === self::LONG_START) {
                for ($n = 1024; $n < 1472; $n++) {
                    $out[] = $x[$n] * $scale;
                }
                for ($n = 0; $n < 128; $n++) {
                    $out[] = $x[1472 + $n] * $shortCur[127 - $n] * $scale;
                }
                for ($n = 1600; $n < 2048; $n++) {
                    $out[] = 0.0;
                }
            } else {
                for ($n = 0; $n < 1024; $n++) {
                    $out[] = $x[1024 + $n] * $longCur[1023 - $n] * $scale;
                }
            }
        }
        $overlap = $this->overlap[$channel] ?? array_fill(0, 1024, 0.0);
        $samples = [];
        for ($n = 0; $n < 1024; $n++) {
            $samples[] = $out[$n] + $overlap[$n];
        }
        $this->overlap[$channel] = \array_slice($out, 1024);
        return $samples;
    }

    /**
     * The all-pole TNS filters, in place.
     *
     * @param list<float> $spec
     * @param list<list<array{int, int, int, list<float>}>> $tns
     * @param array{sequence: int, shape: int, maxSfb: int, groups: int, groupLen: list<int>, windows: int, swb: list<int>, numSwb: int} $info
     */
    private function applyTns(array &$spec, array $tns, array $info): void
    {
        $short = $info['sequence'] === self::EIGHT_SHORT;
        $maxBands = min($short ? AacTables::TNS_MAX_BANDS_128[$this->rateIndex] : AacTables::TNS_MAX_BANDS_1024[$this->rateIndex], $info['maxSfb']);
        if ($maxBands === 0) {
            return;
        }
        $swb = $info['swb'];
        foreach ($tns as $w => $filters) {
            $bottom = $info['numSwb'];
            foreach ($filters as [$length, $order, $direction, $coefs]) {
                $top = $bottom;
                $bottom = max(0, $top - $length);
                if ($order === 0) {
                    continue;
                }
                // Reflection coefficients to LPC (step-up recursion).
                $lpc = [];
                for ($i = 0; $i < $order; $i++) {
                    $r = -$coefs[$i];
                    $lpc[$i] = $r;
                    for ($j = 0; $j < ($i + 1) >> 1; $j++) {
                        $f = $lpc[$j];
                        $b = $lpc[$i - 1 - $j];
                        $lpc[$j] = $f + $r * $b;
                        $lpc[$i - 1 - $j] = $b + $r * $f;
                    }
                }
                $start = $swb[min($bottom, $maxBands)];
                $end = $swb[min($top, $maxBands)];
                $size = $end - $start;
                if ($size <= 0) {
                    continue;
                }
                if ($direction) {
                    $inc = -1;
                    $pos = $end - 1;
                } else {
                    $inc = 1;
                    $pos = $start;
                }
                $pos += $w * 128;
                for ($m = 0; $m < $size; $m++, $pos += $inc) {
                    $limit = min($m, $order);
                    for ($i = 1; $i <= $limit; $i++) {
                        $spec[$pos] -= $spec[$pos - $i * $inc] * $lpc[$i - 1];
                    }
                }
            }
        }
    }

    private function huffman(BitReader $bits, int $codebook): int
    {
        $peek = $bits->peek32();
        foreach (self::$huffman[$codebook] as $length => $codes) {
            $code = $peek >> (32 - $length);
            if (isset($codes[$code])) {
                $bits->skip($length);
                return $codes[$code];
            }
        }
        throw new AssertionError('Invalid AAC Huffman code');
    }

    private function skipProgramConfig(BitReader $bits): void
    {
        $bits->read(10);
        $front = $bits->read(4);
        $side = $bits->read(4);
        $back = $bits->read(4);
        $lfe = $bits->read(2);
        $assoc = $bits->read(3);
        $cc = $bits->read(4);
        if ($bits->read(1)) {
            $bits->read(4);
        }
        if ($bits->read(1)) {
            $bits->read(4);
        }
        if ($bits->read(1)) {
            $bits->read(3);
        }
        $bits->skip(5 * ($front + $side + $back) + 4 * $lfe + 4 * $assoc + 5 * $cc);
        $bits->align();
        $bits->skip(8 * $bits->read(8));
    }
}
