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

namespace danog\MadelineProto\Test;

use Amp\Process\Process;
use danog\MadelineProto\Exception;
use danog\MadelineProto\GroupCall\Rtmp\Amf0;
use danog\MadelineProto\GroupCall\Rtmp\Amf0EcmaArray;
use danog\MadelineProto\GroupCall\Rtmp\FlvTags;
use danog\MadelineProto\GroupCall\Rtmp\RtmpPublisher;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;

/**
 * RTMP publishing of livestreams: AMF0, the FLV tags, and a whole stream published to a local RTMP
 * server (ffmpeg), checked with ffprobe.
 */
final class RtmpTest extends TestCase
{
    public function testAmf0RoundTrips(): void
    {
        $values = ['connect', 1.0, ['app' => 's', 'flag' => true, 'nothing' => null, 'list' => ['a', 2.5]], null];
        self::assertSame($values, Amf0::decode(Amf0::encode(...$values)));
        self::assertSame([['duration' => 0.0]], Amf0::decode(Amf0::encode(new Amf0EcmaArray(['duration' => 0.0]))));
        $long = str_repeat('x', 70000);
        self::assertSame([$long], Amf0::decode(Amf0::encode($long)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function audioSpecificConfigs(): iterable
    {
        // AAC-LC, 48 kHz, stereo.
        yield 'AAC-LC' => ["\x11\x90", "\x11\x90"];
        // AAC-LC, 44.1 kHz, mono.
        yield 'AAC-LC, 44.1 kHz' => ["\x12\x08", "\x12\x08"];
        // HE-AAC (SBR) signalled explicitly, core 22.05 kHz, stereo: ADTS carries the core AAC-LC.
        yield 'HE-AAC' => ["\x2B\x92\x08\x00", "\x13\x90"];
    }

    /**
     * @dataProvider audioSpecificConfigs
     */
    public function testAdtsCarriesTheAudioSpecificConfig(string $config, string $expected): void
    {
        $frame = FlvTags::adtsHeader($config, 5).'12345';
        self::assertSame([$expected, '12345'], FlvTags::parseAdts($frame));
        self::assertNull(FlvTags::parseAdts('12345678'));
    }

    public function testAnExplicitSamplingFrequencyIsRefused(): void
    {
        $this->expectException(Exception::class);
        // Sampling frequency index 15: an explicit 24-bit frequency follows.
        FlvTags::adtsHeader("\x17\x80\x00\x00\x08", 0);
    }

    public function testTheCompositionTimeIsSigned(): void
    {
        self::assertSame("\x17\x01\x00\x00\x21", substr(FlvTags::avcFrame('', true, 33), 0, 5));
        self::assertSame("\x27\x01\xFF\xFF\xDF", substr(FlvTags::avcFrame('', false, -33), 0, 5));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function containers(): iterable
    {
        yield 'MP4' => ['mp4'];
        yield 'Matroska' => ['mkv'];
    }

    /**
     * A file with B-frames, published to a local RTMP server: what it receives is H.264 and AAC, with
     * monotonic decoding timestamps, presentation timestamps never before them, and every frame.
     *
     * @dataProvider containers
     */
    public function testAFileIsPublishedToAnRtmpServer(string $container): void
    {
        if (!self::hasFfmpeg()) {
            self::markTestSkipped('ffmpeg with libx264 and aac is needed');
        }
        $dir = sys_get_temp_dir().'/madeline-rtmp-'.bin2hex(random_bytes(4));
        mkdir($dir);
        try {
            $input = "$dir/input.$container";
            self::ffmpeg([
                '-f', 'lavfi', '-i', 'testsrc2=size=320x240:rate=30',
                '-f', 'lavfi', '-i', 'sine=frequency=440:sample_rate=48000',
                '-t', '4', '-c:v', 'libx264', '-bf', '2', '-g', '30', '-pix_fmt', 'yuv420p',
                '-c:a', 'aac', '-ac', '2', ...($container === 'mp4' ? ['-movflags', '+faststart'] : []), $input,
            ]);

            $port = self::freePort();
            $output = "$dir/output.flv";
            $server = Process::start(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-listen', '1', '-i', "rtmp://127.0.0.1:$port/s/key", '-c', 'copy', '-y', $output]);
            $serverOutput = async(static fn () => buffer($server->getStderr()));

            $call = self::call();
            $dj = new DjLoop($call, rtmp: true);
            self::assertTrue($dj->start());
            $publisher = new RtmpPublisher($call, $dj, static fn (bool $revoke): array => ['url' => "rtmp://127.0.0.1:$port/s/", 'key' => 'key']);
            for ($i = 0; $i < 100 && !$publisher->isConnected(); $i++) {
                delay(0.1);
            }
            self::assertTrue($publisher->isConnected(), 'Connected to the RTMP server: '.implode("\n", $call->logs));

            $dj->play(new LocalFile($input));
            // Opening the file can take a while (amphp/file may spawn its worker processes first).
            for ($i = 0; $i < 100 && $dj->getCurrent() === null; $i++) {
                delay(0.05);
            }
            self::assertNotNull($dj->getCurrent(), 'Started playing: '.implode("\n", $call->logs));
            for ($i = 0; $i < 200 && $dj->getCurrent() !== null; $i++) {
                delay(0.05);
            }
            self::assertNull($dj->getCurrent(), 'Finished playing');
            // The frames of the last half second are still being paced out; those held for their
            // decoding timestamps are sent when stopping.
            delay(0.5);
            $publisher->stop();
            $dj->discard();
            self::assertSame(0, $server->join(), $serverOutput->await());

            $streams = self::probe($output, '-show_entries', 'stream=codec_name,codec_type');
            self::assertSame(['h264', 'aac'], array_column($streams['streams'], 'codec_name'));

            $video = self::probe($output, '-select_streams', 'v', '-show_entries', 'packet=pts,dts');
            $source = self::probe($input, '-select_streams', 'v', '-show_entries', 'packet=pts_time');
            self::assertCount(\count($source['packets']), $video['packets'], 'Every video frame was published');
            $lastDts = PHP_INT_MIN;
            $reordered = 0;
            foreach ($video['packets'] as $packet) {
                self::assertGreaterThanOrEqual($lastDts, (int) $packet['dts']);
                self::assertGreaterThanOrEqual((int) $packet['dts'], (int) $packet['pts']);
                $lastDts = (int) $packet['dts'];
                $reordered += (int) ($packet['pts'] < ($previousPts ?? PHP_INT_MIN));
                $previousPts = (int) $packet['pts'];
            }
            self::assertGreaterThan(0, $reordered, 'The B-frames are published with their presentation order');
            // The presentation timestamps are those of the file (in milliseconds), shifted.
            $published = array_map('intval', array_column($video['packets'], 'pts'));
            $original = array_map(static fn (array $p): int => (int) round((float) $p['pts_time'] * 1000), $source['packets']);
            $shift = $published[0] - $original[0];
            foreach ($published as $k => $pts) {
                self::assertEqualsWithDelta($original[$k] + $shift, $pts, 2, "The presentation timestamp of frame $k");
            }

            $audio = self::probe($output, '-select_streams', 'a', '-show_entries', 'packet=pts');
            $sourceAudio = self::probe($input, '-select_streams', 'a', '-show_entries', 'packet=pts');
            self::assertCount(\count($sourceAudio['packets']), $audio['packets'], 'Every audio frame was published');
        } finally {
            exec('rm -rf '.escapeshellarg($dir));
        }
    }

    /**
     * Files an RTMP livestream can't carry are refused, with a message saying how to convert them.
     */
    public function testAFileWithOtherCodecsIsRefused(): void
    {
        if (!self::hasFfmpeg()) {
            self::markTestSkipped('ffmpeg is needed');
        }
        $dir = sys_get_temp_dir().'/madeline-rtmp-'.bin2hex(random_bytes(4));
        mkdir($dir);
        try {
            $input = "$dir/input.webm";
            self::ffmpeg(['-f', 'lavfi', '-i', 'testsrc2=size=160x120:rate=10', '-f', 'lavfi', '-i', 'sine', '-t', '1', '-c:v', 'libvpx', '-c:a', 'libopus', $input]);
            $call = self::call();
            $dj = new DjLoop($call, rtmp: true);
            self::assertTrue($dj->start());
            $dj->play(new LocalFile($input));
            for ($i = 0; $i < 50 && !str_contains(implode("\n", $call->logs), 'can only play H.264'); $i++) {
                delay(0.1);
            }
            $dj->discard();
            self::assertStringContainsString('An RTMP livestream can only play H.264 video and AAC audio', implode("\n", $call->logs));
            self::assertStringContainsString('V_VP8', implode("\n", $call->logs));
        } finally {
            exec('rm -rf '.escapeshellarg($dir));
        }
    }

    private static function hasFfmpeg(): bool
    {
        exec('ffmpeg -hide_banner -encoders 2>/dev/null', $encoders, $code);
        $encoders = implode("\n", $encoders);
        return $code === 0 && str_contains($encoders, 'libx264') && preg_match('/\saac\s/', $encoders) && str_contains($encoders, 'libvpx') && str_contains($encoders, 'libopus');
    }

    /**
     * @param list<string> $arguments
     */
    private static function ffmpeg(array $arguments): void
    {
        $process = Process::start(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', ...$arguments]);
        $error = async(static fn () => buffer($process->getStderr()));
        self::assertSame(0, $process->join(), $error->await());
    }

    /**
     * @return array<string, list<array<string, string>>>
     */
    private static function probe(string $file, string ...$arguments): array
    {
        $process = Process::start(['ffprobe', '-v', 'error', ...$arguments, '-of', 'json', $file]);
        $output = async(static fn () => buffer($process->getStdout()));
        self::assertSame(0, $process->join());
        return json_decode($output->await(), true, flags: JSON_THROW_ON_ERROR);
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($socket);
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);
        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private static function call(): object
    {
        return new class implements CallControllerInterface {
            /** @var list<string> */
            public array $logs = [];

            public function log(string $message, int $level = Logger::NOTICE): void
            {
                $this->logs[] = $message;
            }

            public function isCallEnded(): bool
            {
                return false;
            }

            public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
            {
                return new \danog\MadelineProto\Settings\VoIP;
            }

            public function __toString(): string
            {
                return 'test livestream';
            }
        };
    }
}
