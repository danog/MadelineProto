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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\Codecs\Aac\AacDecoder;
use danog\MadelineProto\Codecs\Fft;
use danog\MadelineProto\Codecs\Mdct;
use danog\MadelineProto\Codecs\Opus\OpusEncoder;
use danog\MadelineProto\Codecs\PhpAacTranscoder;
use danog\MadelineProto\Codecs\Resampler;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Mp4;
use danog\MadelineProto\OggWriter;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\OpusPlaybackTrack;
use PHPUnit\Framework\TestCase;
use Webrtc\AVCodec\Frame\FrameInterface;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrackSink;

use function Amp\delay;

/**
 * The pure PHP AAC decoder, resampler and OPUS encoder that play the AAC audio of MP4 files in calls without ffmpeg,
 * checked against their definitions and against ffmpeg and libopus.
 */
final class PureCodecsTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function tempFile(string $extension): string
    {
        return $this->files[] = tempnam(sys_get_temp_dir(), 'codec').".$extension";
    }

    private static function requireFfmpeg(string $decoder = 'aac'): void
    {
        exec('ffmpeg -hide_banner -decoders 2>/dev/null', $decoders, $code);
        if ($code !== 0 || !preg_match('/\s'.preg_quote($decoder, '/').'\s/', implode("\n", $decoders))) {
            self::markTestSkipped("ffmpeg with the $decoder decoder is needed");
        }
    }

    /** @param list<string> $args */
    private static function ffmpeg(array $args): string
    {
        exec(implode(' ', array_map(escapeshellarg(...), ['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', ...$args])).' 2>&1', $output, $code);
        self::assertSame(0, $code, implode("\n", $output));
        return implode("\n", $output);
    }

    /** A test signal: tones, beeps (transients) and pink noise, in stereo. */
    private function source(int $rate, float $seconds): string
    {
        $wav = $this->tempFile('wav');
        self::ffmpeg([
            '-f', 'lavfi', '-i', "sine=f=440:r=$rate:d=$seconds",
            '-f', 'lavfi', '-i', "anoisesrc=r=$rate:d=$seconds:a=0.05:c=pink:seed=42",
            '-f', 'lavfi', '-i', "sine=f=1250:r=$rate:d=$seconds:beep_factor=8",
            '-filter_complex', '[0][1][2]amerge=inputs=3,pan=stereo|c0=0.6*c0+c1|c1=0.3*c0+c1+0.5*c2[a]',
            '-map', '[a]', $wav,
        ]);
        return $wav;
    }

    /**
     * @return list<list<float>> The samples of a file decoded by ffmpeg, by channel.
     */
    private function ffmpegDecode(string $file, int $channels, array $input = [], int $rate = 0): array
    {
        $raw = $this->tempFile('f32');
        self::ffmpeg([...$input, '-i', $file, '-map', '0:a', ...($rate ? ['-ar', (string) $rate] : []), '-f', 'f32le', '-ac', (string) $channels, $raw]);
        $out = array_fill(0, $channels, []);
        foreach (array_values(unpack('g*', (string) file_get_contents($raw))) as $i => $v) {
            $out[$i % $channels][] = $v;
        }
        return $out;
    }

    /**
     * The SNR of $actual against $expected, in dB, once aligned (the best of the lags up to $maxLag).
     *
     * @param list<float> $expected
     * @param list<float> $actual
     * @return array{float, int, float} SNR, lag, and the level of $actual relative to $expected (dB).
     */
    private static function compare(array $expected, array $actual, int $maxLag, int $skip = 2048): array
    {
        $best = [-INF, 0];
        for ($lag = -$maxLag; $lag <= $maxLag; $lag++) {
            $c = 0.0;
            for ($i = $skip; $i < $skip + 4000 && isset($expected[$i], $actual[$i + $lag]); $i++) {
                $c += $expected[$i] * $actual[$i + $lag];
            }
            if ($c > $best[0]) {
                $best = [$c, $lag];
            }
        }
        $lag = $best[1];
        $signal = $error = $level = 0.0;
        for ($i = $skip; isset($expected[$i], $actual[$i + $lag]) && $i < \count($expected) - 2048; $i++) {
            $signal += $expected[$i] ** 2;
            $error += ($expected[$i] - $actual[$i + $lag]) ** 2;
            $level += $actual[$i + $lag] ** 2;
        }
        return [10 * log10($signal / max($error, 1e-30)), $lag, 10 * log10($level / $signal)];
    }

    public function testFftAndMdctMatchTheirDefinitions(): void
    {
        foreach ([2, 3, 5, 12, 60, 64, 120] as $n) {
            $re = $im = [];
            for ($i = 0; $i < $n; $i++) {
                $re[] = sin($i * 1.3);
                $im[] = cos($i * 0.7);
            }
            $xr = $re;
            $xi = $im;
            (new Fft($n))->transform($xr, $xi);
            for ($k = 0; $k < $n; $k++) {
                $sr = $si = 0.0;
                for ($j = 0; $j < $n; $j++) {
                    $a = -2 * M_PI * $k * $j / $n;
                    $sr += $re[$j] * cos($a) - $im[$j] * sin($a);
                    $si += $re[$j] * sin($a) + $im[$j] * cos($a);
                }
                self::assertEqualsWithDelta($sr, $xr[$k], 1e-9, "FFT $n, bin $k");
                self::assertEqualsWithDelta($si, $xi[$k], 1e-9, "FFT $n, bin $k");
            }
        }
        foreach ([8, 120] as $m) {
            $n0 = ($m + 1) / 2;
            $coefficients = $samples = [];
            for ($k = 0; $k < $m; $k++) {
                $coefficients[] = sin($k * 0.3) / ($k + 1);
            }
            for ($n = 0; $n < 2 * $m; $n++) {
                $samples[] = cos($n * 0.11) + ($n % 5) * 0.05;
            }
            $mdct = new Mdct($m);
            $inverse = $mdct->imdct($coefficients);
            $forward = $mdct->mdct($samples);
            for ($n = 0; $n < 2 * $m; $n++) {
                $s = 0.0;
                for ($k = 0; $k < $m; $k++) {
                    $s += $coefficients[$k] * cos(M_PI / $m * ($n + $n0) * ($k + 0.5));
                }
                self::assertEqualsWithDelta($s, $inverse[$n], 1e-9, "IMDCT $m, sample $n");
            }
            for ($k = 0; $k < $m; $k++) {
                $s = 0.0;
                for ($n = 0; $n < 2 * $m; $n++) {
                    $s += $samples[$n] * cos(M_PI / $m * ($n + $n0) * ($k + 0.5));
                }
                self::assertEqualsWithDelta($s, $forward[$k], 1e-9, "MDCT $m, coefficient $k");
            }
        }
    }

    public function testResamplerKeepsTones(): void
    {
        foreach ([[44100, 1000], [44100, 15000], [24000, 9000], [96000, 1000]] as [$from, $frequency]) {
            $resampler = new Resampler($from, 48000, 1);
            $in = [];
            for ($n = 0; $n < $from; $n++) {
                $in[] = 0.5 * sin(2 * M_PI * $frequency * $n / $from);
            }
            $out = [];
            foreach (array_chunk($in, 1000) as $chunk) {
                array_push($out, ...$resampler->process([$chunk])[0]);
            }
            self::assertEqualsWithDelta(48000, \count($out), 30, "$from Hz: the samples out");
            $signal = $error = 0.0;
            for ($m = 1000; $m < \count($out) - 100; $m++) {
                $expected = 0.5 * sin(2 * M_PI * $frequency * $m / 48000);
                $signal += $expected ** 2;
                $error += ($out[$m] - $expected) ** 2;
            }
            self::assertGreaterThan(60, 10 * log10($signal / $error), "$from Hz to 48 kHz, a $frequency Hz tone");
        }
    }

    /**
     * @return iterable<string, array{list<string>, int}>
     */
    public static function aacFiles(): iterable
    {
        // Mid/side, intensity stereo, noise substitution, TNS, and short windows on the beeps.
        yield 'stereo, every tool' => [['-c:a', 'aac', '-b:a', '48k', '-aac_is', '1', '-aac_pns', '1', '-aac_ms', '1', '-aac_tns', '1'], 2];
        yield 'stereo, 128 kbps' => [['-c:a', 'aac', '-b:a', '128k'], 2];
        yield 'mono, 22.05 kHz' => [['-ac', '1', '-ar', '22050', '-c:a', 'aac', '-b:a', '40k'], 1];
    }

    /**
     * @dataProvider aacFiles
     *
     * @param list<string> $options
     */
    public function testAacDecoderMatchesFfmpeg(array $options, int $channels): void
    {
        self::requireFfmpeg();
        $file = $this->tempFile('m4a');
        self::ffmpeg(['-i', $this->source(44100, 2), ...$options, $file]);
        $mp4 = new Mp4(new LocalFile($file));
        $decoder = null;
        foreach ($mp4->tracks as $track) {
            if ($track['codec'] === 'A_AAC') {
                $decoder = new AacDecoder((string) $track['private']);
            }
        }
        self::assertNotNull($decoder);
        self::assertSame($channels, $decoder->channels);
        $decoded = array_fill(0, $channels, []);
        foreach ($mp4->frames as $frame) {
            if ($frame['codec'] === 'A_AAC') {
                foreach ($decoder->decode($frame['data']) as $c => $samples) {
                    array_push($decoded[$c], ...$samples);
                }
            }
        }
        // ffmpeg's decoder, without cutting the priming samples (neither does ours: the timestamps tell).
        $reference = $this->ffmpegDecode($file, $channels, ['-flags2', '+skip_manual', '-ignore_editlist', '1']);
        foreach ($reference as $c => $samples) {
            [$snr, $lag] = self::compare($samples, $decoded[$c], 4);
            self::assertSame(0, $lag, "channel $c: no delay");
            // Equal, but for float rounding.
            self::assertGreaterThan(120, $snr, "channel $c");
        }
    }

    /**
     * @return iterable<string, array{int, int, float}>
     */
    public static function opusSettings(): iterable
    {
        // Waveform SNRs, low for noise (whose band energy is coded, not its waveform): libopus gets 8 and 6 dB on the
        // channels of this signal at 32 kbps.
        yield 'stereo, 96 kbps' => [2, 96000, 12.0];
        yield 'stereo, 32 kbps' => [2, 32000, 5.0];
        yield 'mono, 48 kbps' => [1, 48000, 12.0];
    }

    /**
     * @dataProvider opusSettings
     */
    public function testOpusEncoderIsDecodedByLibopus(int $channels, int $bitrate, float $minSnr): void
    {
        self::requireFfmpeg('libopus');
        $source = $this->ffmpegDecode($this->source(48000, 2), $channels);
        $encoder = new OpusEncoder($channels, $bitrate);
        $ogg = $this->tempFile('opus');
        $writer = new OggWriter(new LocalFile($ogg));
        $writer->writeHeader($channels, 48000, 'test');
        $frames = intdiv(\count($source[0]), OpusEncoder::FRAME_SAMPLES);
        for ($f = 0; $f < $frames; $f++) {
            $frame = [];
            foreach ($source as $c => $samples) {
                $frame[$c] = \array_slice($samples, $f * OpusEncoder::FRAME_SAMPLES, OpusEncoder::FRAME_SAMPLES);
            }
            $packet = $encoder->encode($frame);
            self::assertSame(intdiv($bitrate * 20, 8000), \strlen($packet), 'constant bitrate');
            $writer->writeChunk($packet, OpusEncoder::FRAME_SAMPLES, $f === $frames - 1);
        }
        // libopus, the reference decoder, must decode every packet without complaining.
        self::assertSame('', self::ffmpeg(['-c:a', 'libopus', '-i', $ogg, '-f', 'null', '-']));
        $decoded = $this->ffmpegDecode($ogg, $channels, ['-c:a', 'libopus']);
        foreach ($source as $c => $samples) {
            [$snr, , $level] = self::compare($samples, $decoded[$c], 1000, 4800);
            self::assertEqualsWithDelta(0.0, $level, 1.0, "channel $c: the level, in dB");
            self::assertGreaterThan($minSnr, $snr, "channel $c");
        }
    }

    public function testAacTranscoderCutsThePrimingOfEditLists(): void
    {
        self::requireFfmpeg('libopus');
        $source = $this->source(44100, 2);
        $lags = [];
        // The 1024 priming samples of the AAC encoder are cut by an edit list, or not at all.
        foreach (['edit list' => '1', 'no edit list' => '0'] as $what => $editList) {
            $file = $this->tempFile('m4a');
            self::ffmpeg(['-i', $source, '-c:a', 'aac', '-b:a', '128k', '-use_editlist', $editList, $file]);
            $mp4 = new Mp4(new LocalFile($file));
            $transcoder = null;
            $packets = [];
            $sink = static function (string $packet, int $timestamp) use (&$packets): void {
                $packets[] = [$packet, $timestamp];
            };
            foreach ($mp4->tracks as $track) {
                if ($track['codec'] === 'A_AAC') {
                    $transcoder = new PhpAacTranscoder((string) $track['private'], $sink);
                }
            }
            self::assertNotNull($transcoder);
            foreach ($mp4->frames as $frame) {
                if ($frame['codec'] === 'A_AAC') {
                    $transcoder->push($frame['data'], $frame['timestamp']);
                }
            }
            $transcoder->finish();
            foreach ($packets as $i => [, $timestamp]) {
                self::assertSame($packets[0][1] + 960 * $i, $timestamp, "$what: packet $i follows the previous one");
            }
            $ogg = $this->tempFile('opus');
            $writer = new OggWriter(new LocalFile($ogg));
            $writer->writeHeader(2, 48000, 'test');
            foreach ($packets as $i => [$packet]) {
                $writer->writeChunk($packet, 960, $i === \count($packets) - 1);
            }
            // Against ffmpeg's decode of the file, which applies its edit list: the delay of the OPUS encoder only.
            $reference = $this->ffmpegDecode($file, 2, rate: 48000);
            [, $lags[$what]] = self::compare($reference[0], $this->ffmpegDecode($ogg, 2, ['-c:a', 'libopus'])[0], 1500, 4800);
        }
        self::assertEqualsWithDelta($lags['no edit list'], $lags['edit list'], 2, 'The same delay, with or without the priming');
    }

    public function testAacAudioIsPlayedInCalls(): void
    {
        self::requireFfmpeg();
        Magic::start(light: true);
        $file = $this->tempFile('m4a');
        self::ffmpeg(['-i', $this->source(44100, 2), '-c:a', 'aac', '-b:a', '96k', $file]);
        $call = new class implements CallControllerInterface {
            public bool $ended = false;
            public function log(string $message, int $level = Logger::NOTICE): void
            {
            }
            public function isCallEnded(): bool
            {
                return $this->ended;
            }
            public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
            {
                // Each frame read only once the previous one was played.
                return (new \danog\MadelineProto\Settings\VoIP)->setPlaybackLookahead(0);
            }
            public function __toString(): string
            {
                return 'test call';
            }
        };
        $sink = new class implements MediaStreamTrackSink {
            /** @var list<array{float, int}> */
            public array $packets = [];
            public function onFrame(FrameInterface|EncodedPacket $frame): bool
            {
                \assert($frame instanceof EncodedPacket);
                $this->packets[] = [hrtime(true) / 1e9, $frame->getTimestamp()];
                return true;
            }
        };
        $dj = new DjLoop($call);
        $audio = new OpusPlaybackTrack($dj, $call);
        $audio->setSink($sink);
        $dj->play(new LocalFile($file));
        delay(2.6);
        $call->ended = true;
        $audio->stop();
        $dj->discard();

        // 2 seconds of 20 ms packets, each one played on time.
        self::assertEqualsWithDelta(100, \count($sink->packets), 1);
        [$start, $first] = $sink->packets[0];
        foreach ($sink->packets as $i => [$time, $timestamp]) {
            self::assertLessThan(0.03, $time - $start - ($timestamp - $first) / 48000, "packet $i: how late it was played");
        }
    }
}
