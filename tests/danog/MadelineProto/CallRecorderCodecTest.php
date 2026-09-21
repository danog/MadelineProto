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

use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\Tgcalls\Av1Bitstream;
use danog\MadelineProto\Tgcalls\CallRecorder;
use danog\MadelineProto\Tgcalls\H264Framing;
use danog\MadelineProto\Tgcalls\HevcBitstream;
use PHPUnit\Framework\TestCase;
use Webrtc\Codecs\Codec;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTPParameter\RTCRtpCodecParameters;

/**
 * End-to-end, offline: the video of a Matroska file is packetized into RTP payloads exactly as we
 * transmit it, depacketized and reassembled exactly as we receive it, recorded by the pure-PHP
 * recorder, and the resulting file demuxed again — for the codecs that carry their own framing
 * (H.265 parameter sets, AV1 OBU size fields).
 */
final class CallRecorderCodecTest extends TestCase
{
    /**
     * @return array{tracks: array, frames: list<array>}
     */
    private static function demux(string $path): array
    {
        $m = new Matroska(new LocalFile($path), null);
        $frames = [];
        foreach ($m->frames as $frame) {
            if ($frame['type'] === Matroska::TRACK_TYPE_VIDEO) {
                $frames[] = $frame;
            }
        }
        return ['tracks' => $m->tracks, 'frames' => $frames];
    }

    private static function videoTrack(array $tracks): array
    {
        foreach ($tracks as $track) {
            if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO) {
                return $track;
            }
        }
        self::fail('No video track');
    }

    /**
     * Send a frame through the RTP payloader and depayloader, as a call would.
     */
    private static function roundTrip(RTCRtpCodecParameters $codec, string $frame): string
    {
        [$payloads] = Codec::getEncoder($codec)->pack(new EncodedPacket($frame, 0));
        $received = '';
        foreach ($payloads as $payload) {
            self::assertLessThanOrEqual(1200, \strlen($payload));
            [, $chunk] = Codec::depayload($codec, $payload);
            $received .= $chunk;
        }
        return Codec::finalizeFrame($codec, $received);
    }

    public function testHevcRecordsThroughRtp(): void
    {
        $source = self::demux(__DIR__.'/fixtures/hevc.mkv');
        $track = self::videoTrack($source['tracks']);
        $this->assertSame('V_MPEGH/ISO/HEVC', $track['codec']);
        $framing = new H264Framing($track['private'] ?? '', hevc: true);
        $this->assertTrue($framing->isLengthPrefixed());
        $codec = new RTCRtpCodecParameters('video/H265', 90000, null, 98);

        $out = tempnam(sys_get_temp_dir(), 'rec').'.mkv';
        $recorder = new CallRecorder(new LocalFile($out));
        $sent = [];
        foreach ($source['frames'] as $i => $frame) {
            $annexB = $framing->convert($frame['data'], $frame['keyframe']);
            $this->assertTrue(HevcBitstream::isHevc($annexB) || !$frame['keyframe']);
            $received = self::roundTrip($codec, $annexB);
            $this->assertSame($annexB, $received, "Frame $i survives packetization");
            $recorder->pushVideoFrame($received, $i * 3600, 1);
            $sent[] = $frame['data'];
        }
        $recorder->close();

        $recorded = self::demux(substr($out, 0, -4).'.0_video.mkv');
        unlink(substr($out, 0, -4).'.0_video.mkv');
        $recordedTrack = self::videoTrack($recorded['tracks']);
        $this->assertSame('V_MPEGH/ISO/HEVC', $recordedTrack['codec']);
        [$width, $height] = HevcBitstream::describe($framing->convert($source['frames'][0]['data'], true));
        $this->assertSame([320, 180], [$width, $height]);
        // A usable hvcC: version 1, three parameter set arrays, and the same SPS as the source.
        $hvcc = $recordedTrack['private'] ?? '';
        $this->assertSame(1, \ord($hvcc[0]));
        $this->assertSame(3, \ord($hvcc[22]));
        $this->assertStringContainsString(self::firstNal($track['private'], 33), $hvcc);
        $this->assertCount(\count($sent), $recorded['frames']);
        foreach ($recorded['frames'] as $i => $frame) {
            // The picture data is stored untouched; the recording additionally keeps the in-band
            // parameter sets of keyframes, so compare the VCL NAL units only.
            $this->assertSame(self::vclNals($sent[$i]), self::vclNals($frame['data']), "Recorded frame $i");
            $this->assertSame((bool) $source['frames'][$i]['keyframe'], (bool) $frame['keyframe'], "Keyframe flag of frame $i");
        }
    }

    public function testAv1RecordsThroughRtp(): void
    {
        $source = self::demux(__DIR__.'/fixtures/av1.webm');
        $track = self::videoTrack($source['tracks']);
        $this->assertSame('V_AV1', $track['codec']);
        $codec = new RTCRtpCodecParameters('video/AV1', 90000, null, 99);

        $out = tempnam(sys_get_temp_dir(), 'rec').'.mkv';
        $recorder = new CallRecorder(new LocalFile($out));
        foreach ($source['frames'] as $i => $frame) {
            $received = self::roundTrip($codec, $frame['data']);
            $this->assertSame($frame['data'], $received, "Temporal unit $i survives packetization");
            $this->assertSame((bool) $frame['keyframe'], Av1Bitstream::isKeyframe($received));
            $recorder->pushVideoFrame($received, $i * 3600, 1);
        }
        $recorder->close();

        $recorded = self::demux(substr($out, 0, -4).'.0_video.mkv');
        unlink(substr($out, 0, -4).'.0_video.mkv');
        $recordedTrack = self::videoTrack($recorded['tracks']);
        $this->assertSame('V_AV1', $recordedTrack['codec']);
        [$width, $height] = Av1Bitstream::describe($source['frames'][0]['data']);
        $this->assertSame(320, $width);
        $this->assertContains($height, [180, 184]);
        // av1C: marker+version, then the sequence header OBU of the stream.
        $av1c = $recordedTrack['private'] ?? '';
        $this->assertSame(0x81, \ord($av1c[0]));
        $this->assertSame(Av1Bitstream::sequenceHeader($source['frames'][0]['data']), substr($av1c, 4));
        $this->assertCount(\count($source['frames']), $recorded['frames']);
        foreach ($recorded['frames'] as $i => $frame) {
            $this->assertSame($source['frames'][$i]['data'], $frame['data'], "Recorded temporal unit $i");
        }
    }

    /** The first NAL unit of the given type in an hvcC record. */
    private static function firstNal(string $hvcc, int $type): string
    {
        $offset = 23;
        $arrays = \ord($hvcc[22]);
        for ($a = 0; $a < $arrays; $a++) {
            $arrayType = \ord($hvcc[$offset]) & 0x3F;
            $count = unpack('n', substr($hvcc, $offset + 1, 2))[1];
            $offset += 3;
            for ($i = 0; $i < $count; $i++) {
                $size = unpack('n', substr($hvcc, $offset, 2))[1];
                $nal = substr($hvcc, $offset + 2, $size);
                $offset += 2 + $size;
                if ($arrayType === $type) {
                    return $nal;
                }
            }
        }
        return '';
    }

    /**
     * The VCL NAL units (types 0-31) of a 4-byte length-prefixed H.265 sample.
     *
     * @return list<string>
     */
    private static function vclNals(string $sample): array
    {
        $result = [];
        $offset = 0;
        while ($offset + 4 <= \strlen($sample)) {
            $size = unpack('N', substr($sample, $offset, 4))[1];
            $nal = substr($sample, $offset + 4, $size);
            $offset += 4 + $size;
            if ($nal !== '' && HevcBitstream::nalType($nal) < 32) {
                $result[] = $nal;
            }
        }
        return $result;
    }
}
