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

use Amp\ByteStream\WritableStream;
use Amp\Pipeline\ConcurrentIterator;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MatroskaWriter;
use Revolt\EventLoop;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;

/**
 * Records the incoming audio and video of a call into a Matroska (.mkv) file, in pure PHP.
 *
 * Both receivers run in raw mode, so the already-encoded OPUS audio and video frames that arrive
 * over RTP are muxed straight into the file by {@see MatroskaWriter} without any decoding, ffmpeg,
 * CLI tool or FFI. The video codec, keyframe flags and picture size are recovered from the frames
 * themselves (see {@see self::describeVideo()}), since RTP does not carry them.
 *
 * The file header lists whatever tracks exist, so a call answered without a camera produces a valid
 * audio-only file. The header is written once the video track has been described from its first
 * frame, or, if no video has arrived, shortly after audio starts.
 *
 * @internal
 */
final class CallRecorder
{
    /** Grace period (seconds) to wait for a first video frame before committing an audio-only file. */
    private const VIDEO_WAIT = 2.0;

    private MatroskaWriter $writer;
    private bool $started = false;
    private bool $closed = false;
    private ?float $firstAudioAt = null;

    private ?RemoteStreamTrack $audioTrack = null;
    private ?RemoteStreamTrack $videoTrack = null;
    private ?ConcurrentIterator $audioConsumer = null;
    private ?ConcurrentIterator $videoConsumer = null;

    /** @var list<array{data: string, ms: int}> Audio buffered until the header is written. */
    private array $audioBuffer = [];
    /** @var list<array{data: string, ms: int, keyframe: bool}> Video buffered until the header is written. */
    private array $videoBuffer = [];

    private ?int $videoBaseTs = null;
    private ?int $audioBaseTs = null;

    /** The peer's advertised video state (from its MediaState): true has video, false none, null unknown. */
    private ?bool $remoteHasVideo = null;

    /** The file being recorded to, or null for a stream (which cannot survive a serialize cycle). */
    public readonly ?LocalFile $file;

    public function __construct(LocalFile|WritableStream $out)
    {
        $this->file = $out instanceof LocalFile ? $out : null;
        $this->writer = new MatroskaWriter($out);
        $this->writer->setAudioTrack('A_OPUS', 48000, 2, self::opusHead(2, 48000));
    }

    /**
     * Drop the live track subscriptions (not serializable); the writer serializes itself and reopens
     * its file. {@see self::__unserialize()} re-subscribes to the resumed tracks and keeps recording.
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['audioConsumer'], $vars['videoConsumer']);
        return $vars;
    }

    public function __unserialize(array $data): void
    {
        // Synchronous state restoration only — no async work here (see resume()).
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->audioConsumer = null;
        $this->videoConsumer = null;
    }

    /**
     * Reopen the file and resume recording after the whole call graph has been deserialized: the peer
     * connection came back with its remote tracks in place, so re-subscribe and resume draining into
     * the reopened, append-mode writer. Called by the controller's resume(); never during unserialize.
     */
    public function resume(): void
    {
        if ($this->closed) {
            return;
        }
        $this->writer->resume();
        if ($this->audioTrack !== null && $this->audioConsumer === null) {
            $this->audioConsumer = $this->audioTrack->getConsumer();
            EventLoop::queue($this->drainAudio(...));
        }
        if ($this->videoTrack !== null && $this->videoConsumer === null) {
            $this->videoConsumer = $this->videoTrack->getConsumer();
            EventLoop::queue($this->drainVideo(...));
        }
    }

    /**
     * Attach an incoming track. Audio and video are handled as they arrive.
     */
    public function setTrack(RemoteStreamTrack $track): void
    {
        if ($this->closed) {
            return;
        }
        \danog\MadelineProto\Logger::log('RECDEBUG CallRecorder::setTrack kind='.$track->getKind()->name, \danog\MadelineProto\Logger::ERROR); // RECDEBUG
        if ($track->getKind() === MediaKind::Video) {
            if ($this->videoTrack === $track) {
                return;
            }
            $this->videoTrack = $track;
            $this->videoConsumer = $track->getConsumer();
            EventLoop::queue($this->drainVideo(...));
        } else {
            if ($this->audioTrack === $track) {
                return;
            }
            $this->audioTrack = $track;
            $this->audioConsumer = $track->getConsumer();
            EventLoop::queue($this->drainAudio(...));
        }
    }

    /**
     * Tell the recorder whether the peer is transmitting video, from its MediaState. When it is not,
     * and audio is already buffered, the audio-only header is committed immediately rather than after
     * the grace period — replacing the blind wait with a deterministic signal.
     */
    public function setRemoteHasVideo(bool $hasVideo): void
    {
        $this->remoteHasVideo = $hasVideo;
        if (!$hasVideo && !$this->started && !$this->closed && $this->audioBuffer !== []) {
            $this->begin();
        }
    }

    private function drainAudio(): void
    {
        $consumer = $this->audioConsumer;
        if ($consumer === null) {
            return;
        }
        $n = 0; // RECDEBUG
        foreach ($consumer as $frame) {
            if ($this->closed) {
                return;
            }
            if (++$n % 100 === 1) { \danog\MadelineProto\Logger::log("RECDEBUG drainAudio n=$n", \danog\MadelineProto\Logger::ERROR); } // RECDEBUG
            $ts = $frame->getTimestamp();
            $this->audioBaseTs ??= $ts;
            $ms = (int) (($ts - $this->audioBaseTs) * 1000 / 48000);
            $this->firstAudioAt ??= microtime(true);
            if ($this->started) {
                $this->writer->writeAudio($frame->getData(), $ms);
            } else {
                $this->audioBuffer[] = ['data' => $frame->getData(), 'ms' => $ms];
                // Commit the header as soon as we KNOW the peer is not sending video (from its
                // MediaState), instead of blindly waiting; the timed grace period is only a backstop
                // for a peer that never announces its media state.
                if ($this->remoteHasVideo === false
                    || microtime(true) - $this->firstAudioAt > self::VIDEO_WAIT
                ) {
                    $this->begin();
                }
            }
        }
    }

    private function drainVideo(): void
    {
        $consumer = $this->videoConsumer;
        if ($consumer === null) {
            return;
        }
        $n = 0; // RECDEBUG
        foreach ($consumer as $frame) {
            if ($this->closed) {
                return;
            }
            if (++$n % 60 === 1) { \danog\MadelineProto\Logger::log("RECDEBUG drainVideo n=$n", \danog\MadelineProto\Logger::ERROR); } // RECDEBUG
            $data = $frame->getData();
            if ($data === '') {
                continue;
            }
            $ts = $frame->getTimestamp();
            $this->videoBaseTs ??= $ts;
            $ms = (int) (($ts - $this->videoBaseTs) * 1000 / 90000);

            if (!$this->writer->hasVideoTrack()) {
                // Describe the video track from its first frame, then commit the header.
                [$codecId, $width, $height, $private] = self::describeVideo($data);
                $this->writer->setVideoTrack($codecId, $width, $height, $private);
                $this->begin();
            }

            [$out, $keyframe] = self::transformVideo($this->writer, $data);
            if ($out === '') {
                continue;
            }
            if ($this->started) {
                $this->writer->writeVideo($out, $ms, $keyframe);
            } else {
                $this->videoBuffer[] = ['data' => $out, 'ms' => $ms, 'keyframe' => $keyframe];
            }
        }
    }

    /**
     * Write the header and flush everything buffered so far, ordered by timestamp.
     */
    private function begin(): void
    {
        if ($this->started || $this->closed) {
            return;
        }
        $this->started = true;
        $this->writer->start();

        // Merge the two buffers in timestamp order so the first cluster is well formed.
        $merged = [];
        foreach ($this->videoBuffer as $f) {
            $merged[] = ['kind' => 'v', 'ms' => $f['ms'], 'data' => $f['data'], 'keyframe' => $f['keyframe']];
        }
        foreach ($this->audioBuffer as $f) {
            $merged[] = ['kind' => 'a', 'ms' => $f['ms'], 'data' => $f['data'], 'keyframe' => true];
        }
        usort($merged, static fn ($a, $b) => $a['ms'] <=> $b['ms']);
        foreach ($merged as $f) {
            if ($f['kind'] === 'v') {
                $this->writer->writeVideo($f['data'], $f['ms'], $f['keyframe']);
            } else {
                $this->writer->writeAudio($f['data'], $f['ms']);
            }
        }
        $this->audioBuffer = [];
        $this->videoBuffer = [];
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        // Nothing arrived at all: still emit an audio-only header so the file is valid.
        if (!$this->started) {
            $this->begin();
        }
        $this->closed = true;
        $this->audioTrack = null;
        $this->videoTrack = null;
        $this->writer->close();
    }

    /* ----------------------------------------------------------------- *
     *  Codec inspection (pure PHP, from the frame bytes).
     * ----------------------------------------------------------------- */

    /**
     * Identify the video codec of a frame and its picture size and codec-private data.
     *
     * @return array{string, int, int, string} [Matroska CodecID, width, height, CodecPrivate]
     */
    private static function describeVideo(string $frame): array
    {
        // H.264 Annex-B always begins with a start code.
        if (str_starts_with($frame, "\x00\x00\x00\x01") || str_starts_with($frame, "\x00\x00\x01")) {
            [$w, $h, $avcc] = self::h264Config($frame);
            return ['V_MPEG4/ISO/AVC', $w, $h, $avcc];
        }
        $first = \ord($frame[0]);
        // AV1: an OBU header has the forbidden bit clear and a small type; a temporal unit usually
        // starts with a temporal delimiter (type 2) or sequence header (type 1).
        $obuType = ($first >> 3) & 0x0F;
        if (($first & 0x80) === 0 && ($obuType === 1 || $obuType === 2 || $obuType === 6)) {
            [$w, $h] = self::av1Size($frame);
            return ['V_AV1', $w, $h, ''];
        }
        // VP8 keyframe carries the 0x9d 0x01 0x2a start code and the dimensions.
        if (\strlen($frame) > 9 && substr($frame, 3, 3) === "\x9d\x01\x2a") {
            $w = (\ord($frame[6]) | (\ord($frame[7]) << 8)) & 0x3FFF;
            $h = (\ord($frame[8]) | (\ord($frame[9]) << 8)) & 0x3FFF;
            return ['V_VP8', $w, $h, ''];
        }
        // VP9: the uncompressed header begins with the frame marker 0b10.
        if (($first >> 6) === 0x02) {
            [$w, $h] = self::vp9Size($frame);
            return ['V_VP9', $w, $h, ''];
        }
        // Unknown: default to VP8 with a common size so the file is still valid.
        return ['V_VP8', 1280, 720, ''];
    }

    /**
     * Prepare a frame for muxing and report whether it is a keyframe.
     *
     * @return array{string, bool} [frame bytes to store, is keyframe]
     */
    private static function transformVideo(MatroskaWriter $writer, string $frame): array
    {
        $first = \ord($frame[0]);
        if (str_starts_with($frame, "\x00\x00\x00\x01") || str_starts_with($frame, "\x00\x00\x01")) {
            return self::h264ToAvcc($frame); // [avcc bytes, keyframe]
        }
        // VP8 keyframe = low bit of the first byte clear.
        if (\strlen($frame) > 9 && substr($frame, 3, 3) === "\x9d\x01\x2a") {
            return [$frame, true];
        }
        if (($first & 0x01) === 0 && ($first >> 6) !== 0x02) {
            // Heuristic VP8 P/keyframe bit when not obviously VP9/AV1.
        }
        // AV1: a temporal unit that carries a sequence header (type 1) starts a new sequence.
        $obuType = ($first >> 3) & 0x0F;
        if (($first & 0x80) === 0 && ($obuType === 1 || $obuType === 2)) {
            return [$frame, self::av1HasSequenceHeader($frame)];
        }
        // VP9 keyframe: frame_type bit is 0 (parsed in vp9IsKeyframe).
        if (($first >> 6) === 0x02) {
            return [$frame, self::vp9IsKeyframe($frame)];
        }
        // VP8 fallback.
        return [$frame, (\ord($frame[0]) & 0x01) === 0];
    }

    /* ---- H.264 ---- */

    /** @return list<string> NAL units (without start codes). */
    private static function h264Nals(string $frame): array
    {
        $nals = [];
        $len = \strlen($frame);
        $i = 0;
        $start = -1;
        while ($i < $len) {
            // Find a start code.
            if ($i + 3 <= $len && $frame[$i] === "\x00" && $frame[$i + 1] === "\x00" && $frame[$i + 2] === "\x01") {
                if ($start >= 0) {
                    $end = $i;
                    // Trim a trailing zero belonging to the next 4-byte start code.
                    if ($end > $start && $frame[$end - 1] === "\x00") {
                        $end--;
                    }
                    $nals[] = substr($frame, $start, $end - $start);
                }
                $i += 3;
                $start = $i;
                continue;
            }
            $i++;
        }
        if ($start >= 0 && $start < $len) {
            $nals[] = substr($frame, $start);
        }
        return $nals;
    }

    /**
     * @return array{int, int, string} [width, height, AVCDecoderConfigurationRecord]
     */
    private static function h264Config(string $frame): array
    {
        $sps = '';
        $pps = '';
        foreach (self::h264Nals($frame) as $nal) {
            if ($nal === '') {
                continue;
            }
            $type = \ord($nal[0]) & 0x1F;
            if ($type === 7 && $sps === '') {
                $sps = $nal;
            } elseif ($type === 8 && $pps === '') {
                $pps = $nal;
            }
        }
        [$w, $h] = $sps !== '' ? self::h264SpsSize($sps) : [1280, 720];
        $avcc = '';
        if ($sps !== '' && \strlen($sps) >= 4) {
            $avcc = "\x01".$sps[1].$sps[2].$sps[3]."\xFF"
                ."\xE1".pack('n', \strlen($sps)).$sps
                ."\x01".pack('n', \strlen($pps)).$pps;
        }
        return [$w, $h, $avcc];
    }

    /**
     * @return array{string, bool} [AVCC length-prefixed frame, keyframe]
     */
    private static function h264ToAvcc(string $frame): array
    {
        $out = '';
        $keyframe = false;
        foreach (self::h264Nals($frame) as $nal) {
            if ($nal === '') {
                continue;
            }
            $type = \ord($nal[0]) & 0x1F;
            if ($type === 5 || $type === 7) {
                $keyframe = true;
            }
            if ($type === 9) {
                continue; // Access unit delimiters are not stored.
            }
            $out .= pack('N', \strlen($nal)).$nal;
        }
        return [$out, $keyframe];
    }

    /**
     * Parse width/height out of an H.264 SPS via Exp-Golomb decoding.
     *
     * @return array{int, int}
     */
    private static function h264SpsSize(string $sps): array
    {
        $rbsp = self::stripEmulationPrevention(substr($sps, 1)); // drop the NAL header byte
        $br = new BitReader($rbsp);
        $profileIdc = $br->bits(8);
        $br->bits(8); // constraint flags + reserved
        $br->bits(8); // level_idc
        $br->ue();    // seq_parameter_set_id
        if (in_array($profileIdc, [100, 110, 122, 244, 44, 83, 86, 118, 128, 138, 139, 134, 135], true)) {
            $chroma = $br->ue();
            if ($chroma === 3) {
                $br->bits(1); // separate_colour_plane_flag
            }
            $br->ue(); // bit_depth_luma_minus8
            $br->ue(); // bit_depth_chroma_minus8
            $br->bits(1); // qpprime_y_zero_transform_bypass_flag
            if ($br->bits(1)) { // seq_scaling_matrix_present_flag
                $count = $chroma !== 3 ? 8 : 12;
                for ($i = 0; $i < $count; $i++) {
                    if ($br->bits(1)) {
                        $size = $i < 6 ? 16 : 64;
                        $last = 8;
                        $next = 8;
                        for ($j = 0; $j < $size; $j++) {
                            if ($next !== 0) {
                                $delta = $br->se();
                                $next = ($last + $delta + 256) % 256;
                            }
                            $last = $next === 0 ? $last : $next;
                        }
                    }
                }
            }
        }
        $br->ue(); // log2_max_frame_num_minus4
        $picOrderCnt = $br->ue();
        if ($picOrderCnt === 0) {
            $br->ue(); // log2_max_pic_order_cnt_lsb_minus4
        } elseif ($picOrderCnt === 1) {
            $br->bits(1);
            $br->se();
            $br->se();
            $num = $br->ue();
            for ($i = 0; $i < $num; $i++) {
                $br->se();
            }
        }
        $br->ue(); // max_num_ref_frames
        $br->bits(1); // gaps_in_frame_num_value_allowed_flag
        $widthMbs = $br->ue() + 1;
        $heightMapUnits = $br->ue() + 1;
        $frameMbsOnly = $br->bits(1);
        if (!$frameMbsOnly) {
            $br->bits(1); // mb_adaptive_frame_field_flag
        }
        $br->bits(1); // direct_8x8_inference_flag
        $cropLeft = $cropRight = $cropTop = $cropBottom = 0;
        if ($br->bits(1)) { // frame_cropping_flag
            $cropLeft = $br->ue();
            $cropRight = $br->ue();
            $cropTop = $br->ue();
            $cropBottom = $br->ue();
        }
        $width = $widthMbs * 16 - ($cropLeft + $cropRight) * 2;
        $height = (2 - $frameMbsOnly) * $heightMapUnits * 16 - ($cropTop + $cropBottom) * 2;
        return [max(1, $width), max(1, $height)];
    }

    private static function stripEmulationPrevention(string $data): string
    {
        // Remove 0x03 in 0x00 0x00 0x03 sequences (RBSP anti-emulation).
        return preg_replace('/\x00\x00\x03/', "\x00\x00", $data) ?? $data;
    }

    /* ---- VP9 ---- */

    /** @return array{int, int} */
    private static function vp9Size(string $frame): array
    {
        $br = new BitReader($frame);
        $br->bits(2); // frame_marker
        $profile = $br->bits(1) | ($br->bits(1) << 1);
        if ($profile === 3) {
            $br->bits(1);
        }
        if ($br->bits(1)) { // show_existing_frame
            return [1280, 720];
        }
        $frameType = $br->bits(1);
        $br->bits(1); // show_frame
        $br->bits(1); // error_resilient_mode
        if ($frameType !== 0) {
            return [1280, 720]; // inter frame carries no size here
        }
        $br->bits(24); // sync code
        // color_config (profile 0, 8-bit): color_space(3); if != CS_RGB: color_range(1), subsampling handled by profile
        $br->bits(3); // color_space
        $br->bits(1); // color_range
        $width = $br->bits(16) + 1;
        $height = $br->bits(16) + 1;
        return [$width, $height];
    }

    private static function vp9IsKeyframe(string $frame): bool
    {
        $br = new BitReader($frame);
        $br->bits(2);
        $profile = $br->bits(1) | ($br->bits(1) << 1);
        if ($profile === 3) {
            $br->bits(1);
        }
        if ($br->bits(1)) {
            return false; // show_existing_frame
        }
        return $br->bits(1) === 0; // frame_type 0 = key
    }

    /* ---- AV1 ---- */

    private static function av1HasSequenceHeader(string $tu): bool
    {
        $o = 0;
        $n = \strlen($tu);
        while ($o < $n) {
            $h = \ord($tu[$o]);
            $type = ($h >> 3) & 0x0F;
            $ext = ($h >> 2) & 1;
            $hasSize = ($h >> 1) & 1;
            $p = $o + 1 + $ext;
            if ($hasSize) {
                $size = 0;
                $shift = 0;
                do {
                    $b = \ord($tu[$p++]);
                    $size |= ($b & 0x7F) << $shift;
                    $shift += 7;
                } while ($b & 0x80);
            } else {
                $size = $n - $p;
            }
            if ($type === 1) {
                return true;
            }
            $o = $p + $size;
        }
        return false;
    }

    /** @return array{int, int} */
    private static function av1Size(string $tu): array
    {
        // Parsing the sequence header for the exact size is involved; a sensible default keeps the
        // file valid, and incoming AV1 is not something current Telegram clients actually send.
        return [1280, 720];
    }

    /* ---- OPUS ---- */

    private static function opusHead(int $channels, int $sampleRate): string
    {
        return 'OpusHead'.pack('CCvVvC', 1, $channels, 312, $sampleRate, 0, 0);
    }
}
