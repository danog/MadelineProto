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
use danog\MadelineProto\Logger;
use danog\MadelineProto\MatroskaWriter;
use danog\MadelineProto\RecordingFormat;
use Revolt\EventLoop;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;

/**
 * Records the incoming media of one party of a call into Matroska (.mkv) files, in pure PHP.
 *
 * The receivers run in raw mode, so the already-encoded OPUS audio and video frames that arrive
 * over RTP are muxed straight into the file by {@see MatroskaWriter} without any decoding, ffmpeg,
 * CLI tool or FFI. The video codec, keyframe flags and picture size are recovered from the frames
 * themselves (see {@see self::describeVideo()}), since RTP does not carry them.
 *
 * A party has up to three streams — its microphone ({@see self::SLOT_AUDIO}), its camera
 * ({@see self::SLOT_VIDEO}) and its screen share ({@see self::SLOT_PRESENTATION}) — and can turn each
 * on and off at any time, in any combination. Matroska fixes the track list in the file header, so
 * the recording is a *sequence of segments*: whenever the set of flowing streams changes (a stream
 * starts or stops, or a video stream comes back with another codec or picture size), the current
 * file is closed and a new one is opened, whose tracks are exactly the streams flowing from then on.
 * Segment files are named `<stem>.<n>_<streams>.mkv` (or `.webm`): `n` counts from 0 and `streams`
 * lists what the file holds — `audio`, `video` (the camera) and `screen`, joined by commas — so
 * `call.mkv` yields `call.0_audio.mkv`, `call.1_audio,video.mkv`, … and a stem ending in `/` (a
 * directory) yields `dir/0_audio.mkv`, …. A stream output cannot be rolled over, so it keeps its
 * initial tracks and drops what does not fit.
 *
 * Which streams to expect is told by whoever routes the media (from the peer's media state, or a
 * group call participant's flags, see {@see self::setExpected()}); the header of a segment is
 * committed once every expected stream has been seen, or after a short grace period.
 *
 * @internal
 */
final class CallRecorder
{
    public const SLOT_AUDIO = 'audio';
    public const SLOT_VIDEO = MatroskaWriter::SLOT_VIDEO;
    public const SLOT_PRESENTATION = MatroskaWriter::SLOT_PRESENTATION;
    private const VIDEO_SLOTS = [self::SLOT_VIDEO, self::SLOT_PRESENTATION];
    private const SLOTS = [self::SLOT_AUDIO, self::SLOT_VIDEO, self::SLOT_PRESENTATION];

    /** Grace period (seconds) to wait for an expected stream before committing a segment without it. */
    private const GRACE = 2.0;

    private ?MatroskaWriter $writer = null;
    private bool $closed = false;
    /** Whether the current segment's header has been written. */
    private bool $started = false;
    /** How many segments were rolled so far (0: still on the base file). */
    private int $segment = 0;
    /** Wall clock of the first frame buffered for the segment being opened, for the grace period. */
    private ?float $waitingSince = null;

    private ?RemoteStreamTrack $audioTrack = null;
    /** @var array<string, RemoteStreamTrack> Video tracks drained by the recorder itself, by slot. */
    private array $videoTracks = [];
    private ?ConcurrentIterator $audioConsumer = null;
    /** @var array<string, ConcurrentIterator> */
    private array $videoConsumers = [];

    /**
     * Which streams the party is currently sending, as far as signaling tells: true expected, false
     * not, null unknown. A stream that stops is dropped from the next segment right away; one that
     * starts joins it once its first (key)frame arrives.
     *
     * @var array<string, ?bool>
     */
    private array $expected = [self::SLOT_AUDIO => null, self::SLOT_VIDEO => null, self::SLOT_PRESENTATION => null];
    /**
     * The video tracks described so far, by slot: what a segment opened now would declare.
     *
     * @var array<string, array{codecId: string, width: int, height: int, private: string}>
     */
    private array $described = [];
    /** @var array<string, int> The source (track) each slot's frames currently come from. */
    private array $sources = [];
    /** @var array<string, ?int> The first RTP timestamp of each slot's current source. */
    private array $baseTs = [];
    /** @var array<string, int> Offset (ms) placing each slot's current source on the recording's clock. */
    private array $offsetMs = [];
    /** @var array<string, int> The last timestamp (ms) written per slot. */
    private array $lastMs = [];
    /** @var array<string, list<array{data: string, ms: int, keyframe: bool}>> Frames buffered until the header is written. */
    private array $buffers = [];
    /** Whether the set of flowing streams changed and the next frame must go to a new segment. */
    private bool $rollPending = false;
    /** Whether the stream output was already told that it cannot follow a stream change. */
    private bool $warnedUnrollable = false;

    /** The file the recording was requested to, or null for a stream (which cannot survive a serialize cycle). */
    public readonly ?LocalFile $file;
    /** The segment file stem: the requested file without its extension, or a directory with its trailing `/`. */
    private string $stem = '';
    private WritableStream|LocalFile $out;
    private RecordingFormat $format;

    /**
     * @param LocalFile|WritableStream $out The file to record to (`name.mkv` gives `name.<n>_<streams>.mkv`
     *                                      segments; a path ending in `/` gives `<n>_<streams>.mkv` files in
     *                                      that directory), or a stream.
     */
    public function __construct(LocalFile|WritableStream $out, RecordingFormat $format = RecordingFormat::Mkv)
    {
        $this->file = $out instanceof LocalFile ? $out : null;
        $this->out = $out;
        $this->format = $format;
        if ($out instanceof LocalFile) {
            $path = $out->file;
            if (str_ends_with($path, '/')) {
                $this->stem = $path;
            } else {
                $dot = strrpos(basename($path), '.');
                $this->stem = $dot === false ? $path : substr($path, 0, \strlen($path) - \strlen(basename($path)) + $dot);
            }
        }
    }

    /**
     * Drop the live track subscriptions (not serializable); the writer serializes itself and reopens
     * its file. {@see self::resume()} re-subscribes to the resumed tracks and keeps recording.
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['audioConsumer'], $vars['videoConsumers']);
        if ($this->file === null) {
            unset($vars['out']);
        }
        return $vars;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $data): void
    {
        // Synchronous state restoration only — no async work here (see resume()).
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->audioConsumer = null;
        $this->videoConsumers = [];
        if (!isset($this->out) && $this->file !== null) {
            $this->out = $this->file;
        }
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
        $this->writer?->resume();
        if ($this->audioTrack !== null && $this->audioConsumer === null) {
            $this->audioConsumer = $this->audioTrack->getConsumer();
            EventLoop::queue($this->drainAudio(...));
        }
        foreach ($this->videoTracks as $slot => $track) {
            if (!isset($this->videoConsumers[$slot])) {
                $this->videoConsumers[$slot] = $track->getConsumer();
                EventLoop::queue(fn () => $this->drainVideo($slot));
            }
        }
    }

    /**
     * Attach an incoming track, whose frames are then drained by the recorder itself. Video may
     * alternatively be pushed frame by frame (see {@see self::pushVideoFrame()}) by whoever routes
     * the incoming video between slots and recorders.
     *
     * @param string $slot For a video track, whether it is the camera or the screen share.
     */
    public function setTrack(RemoteStreamTrack $track, string $slot = self::SLOT_VIDEO): void
    {
        if ($this->closed) {
            return;
        }
        if ($track->getKind() === MediaKind::Audio) {
            if ($this->audioTrack === $track) {
                return;
            }
            $this->audioTrack = $track;
            $this->audioConsumer = $track->getConsumer();
            EventLoop::queue($this->drainAudio(...));
            return;
        }
        \assert(\in_array($slot, self::VIDEO_SLOTS, true));
        if (($this->videoTracks[$slot] ?? null) === $track) {
            return;
        }
        $this->videoTracks[$slot] = $track;
        $this->videoConsumers[$slot] = $track->getConsumer();
        EventLoop::queue(fn () => $this->drainVideo($slot));
    }

    /**
     * A stable identifier of a track, to tell its frames apart from those of another one.
     *
     * @psalm-pure
     */
    public static function sourceOf(RemoteStreamTrack $track): int
    {
        return crc32($track->getId());
    }

    /**
     * Tell the recorder which streams the party is sending, as signaling reports it (null leaves a
     * stream's expectation unchanged). A stream reported off is dropped from the next segment at
     * once; one reported on is waited for before a segment is committed.
     */
    public function setExpected(?bool $audio = null, ?bool $video = null, ?bool $presentation = null): void
    {
        if ($this->closed) {
            return;
        }
        $changed = false;
        foreach ([self::SLOT_AUDIO => $audio, self::SLOT_VIDEO => $video, self::SLOT_PRESENTATION => $presentation] as $slot => $value) {
            if ($value === null || $this->expected[$slot] === $value) {
                continue;
            }
            $this->expected[$slot] = $value;
            $changed = true;
            if (!$value && $slot !== self::SLOT_AUDIO) {
                // The stream is gone; when it comes back it is a new track, to be described afresh.
                unset($this->described[$slot], $this->sources[$slot], $this->baseTs[$slot], $this->buffers[$slot]);
            } elseif (!$value) {
                unset($this->buffers[$slot]);
            }
        }
        if (!$changed) {
            return;
        }
        if ($this->started) {
            // Something stopped or started: re-evaluate the segment as soon as the state is stable.
            $this->rollPending = true;
            $this->waitingSince = microtime(true);
            $this->maybeRoll();
        } else {
            $this->maybeBegin();
        }
    }

    /**
     * Compatibility shim: whether the party is transmitting camera video.
     */
    public function setRemoteHasVideo(bool $hasVideo): void
    {
        $this->setExpected(video: $hasVideo);
    }

    private function drainAudio(): void
    {
        $consumer = $this->audioConsumer;
        $track = $this->audioTrack;
        if ($consumer === null || $track === null) {
            return;
        }
        $source = self::sourceOf($track);
        foreach ($consumer as $frame) {
            if ($this->closed) {
                return;
            }
            if ($frame instanceof EncodedPacket) {
                $this->pushAudioFrame($frame->getData(), $frame->getTimestamp(), $source);
            }
        }
    }

    private function drainVideo(string $slot): void
    {
        $consumer = $this->videoConsumers[$slot] ?? null;
        $track = $this->videoTracks[$slot] ?? null;
        if ($consumer === null || $track === null) {
            return;
        }
        $source = self::sourceOf($track);
        foreach ($consumer as $frame) {
            if ($this->closed || ($this->videoTracks[$slot] ?? null) !== $track) {
                return;
            }
            if ($frame instanceof EncodedPacket) {
                $this->pushVideoFrame($frame->getData(), $frame->getTimestamp(), $source, $slot);
            }
        }
    }

    /**
     * Record one incoming OPUS frame.
     *
     * @param int $rtpTimestamp The frame's RTP timestamp (48 kHz clock).
     * @param int $source       Identifies the track the frame belongs to.
     */
    public function pushAudioFrame(string $data, int $rtpTimestamp, int $source = 0): void
    {
        if ($this->closed || $data === '') {
            return;
        }
        $ms = $this->placeOnClock(self::SLOT_AUDIO, $source, $rtpTimestamp, 48000);
        $this->handleFrame(self::SLOT_AUDIO, $data, $ms, true);
    }

    /**
     * Record one incoming video frame (still encoded, as it came off RTP).
     *
     * @param int    $rtpTimestamp The frame's RTP timestamp (90 kHz clock).
     * @param int    $source       Identifies the track the frame belongs to; a new source is placed on
     *                             the recording's clock where it currently is.
     * @param string $slot         Whether this is the camera or the screen share.
     */
    public function pushVideoFrame(string $data, int $rtpTimestamp, int $source = 0, string $slot = self::SLOT_VIDEO): void
    {
        if ($this->closed || $data === '') {
            return;
        }
        \assert(\in_array($slot, self::VIDEO_SLOTS, true));
        if ($this->expected[$slot] === false) {
            return; // Signaling says this stream is off: a straggler.
        }
        $newSource = ($this->sources[$slot] ?? null) !== $source;
        $ms = $this->placeOnClock($slot, $source, $rtpTimestamp, 90000);

        [$out, $keyframe] = self::transformVideo($data, $newSource ? null : ($this->described[$slot]['codecId'] ?? null));
        if ($out === '') {
            return;
        }
        if (!isset($this->described[$slot]) || $newSource) {
            // Describe the track from its first keyframe: only a keyframe carries the picture size
            // (and the parameter sets), and a decoder can only start on one anyway. A new source
            // (the peer's camera turned off and on again, a screen share) is described afresh, and
            // its frames before that keyframe are useless.
            if (!$keyframe) {
                unset($this->sources[$slot]);
                return;
            }
            [$codecId, $width, $height, $private] = self::describeVideo($data);
            $description = ['codecId' => $codecId, 'width' => $width, 'height' => $height, 'private' => $private];
            Logger::log("Incoming $slot track $source recorded as $codecId {$width}x{$height}", Logger::VERBOSE);
            if ($description !== ($this->described[$slot] ?? null)) {
                $this->described[$slot] = $description;
                [$out, $keyframe] = self::transformVideo($data, $codecId);
                if ($this->started && $this->writer?->getVideoTrack($slot) !== $description) {
                    // Another codec or size than the open segment declares: it needs a new segment.
                    $this->rollPending = true;
                }
            }
        }
        $this->sources[$slot] = $source;
        $this->handleFrame($slot, $out, $ms, $keyframe);
    }

    /**
     * Map a frame's RTP timestamp onto the recording's continuous millisecond clock.
     */
    private function placeOnClock(string $slot, int $source, int $rtpTimestamp, int $clockRate): int
    {
        if (($this->sources[$slot] ?? null) !== $source || !isset($this->baseTs[$slot])) {
            // A new source starts where the recording currently is, not at its own random RTP time.
            $this->baseTs[$slot] = $rtpTimestamp;
            $this->offsetMs[$slot] = $this->positionMs();
            $this->sources[$slot] = $source;
        }
        $ms = $this->offsetMs[$slot] + (int) ((($rtpTimestamp - $this->baseTs[$slot]) & 0xFFFFFFFF) * 1000 / $clockRate);
        $this->lastMs[$slot] = $ms;
        return $ms;
    }

    /**
     * How far the recording has got, in milliseconds: the latest timestamp of any flowing stream.
     */
    private function positionMs(): int
    {
        return $this->lastMs === [] ? 0 : max($this->lastMs);
    }

    /**
     * Write a frame of a slot into the current segment, opening or rolling segments as needed.
     */
    private function handleFrame(string $slot, string $data, int $ms, bool $keyframe): void
    {
        if ($this->expected[$slot] === false) {
            // Signaling says this stream is off: a straggler, or a stream that came back before
            // its state did — either way it is not part of any segment until the state says so.
            return;
        }
        if ($this->started) {
            if ($this->rollPending) {
                $this->maybeRoll();
            }
            if ($this->writer !== null && $this->inSegment($slot)) {
                $this->write($slot, $data, $ms, $keyframe);
                return;
            }
            if (!$this->rollPending) {
                // The segment lacks this stream: it just (re)appeared, so a new segment is due —
                // unless the output is a stream, which cannot roll.
                $this->rollPending = true;
                $this->waitingSince = microtime(true);
                $this->maybeRoll();
                if ($this->started && $this->writer !== null && $this->inSegment($slot)) {
                    $this->write($slot, $data, $ms, $keyframe);
                    return;
                }
            }
            // Keep it for the segment about to open.
            $this->buffers[$slot][] = ['data' => $data, 'ms' => $ms, 'keyframe' => $keyframe];
            return;
        }
        $this->buffers[$slot][] = ['data' => $data, 'ms' => $ms, 'keyframe' => $keyframe];
        $this->waitingSince ??= microtime(true);
        $this->maybeBegin();
    }

    /**
     * Whether the current segment declares a track for a slot.
     */
    private function inSegment(string $slot): bool
    {
        if ($this->writer === null) {
            return false;
        }
        return $slot === self::SLOT_AUDIO ? $this->writer->hasAudioTrack() : $this->writer->hasVideoTrack($slot);
    }

    private function write(string $slot, string $data, int $ms, bool $keyframe): void
    {
        \assert($this->writer !== null);
        if ($slot === self::SLOT_AUDIO) {
            $this->writer->writeAudio($data, $ms);
        } else {
            $this->writer->writeVideo($data, $ms, $keyframe, $slot);
        }
    }

    /**
     * The streams a segment opened now would carry: the ones not reported off, that have delivered
     * something (audio) or been described (video).
     *
     * @return list<string>
     */
    private function flowingSlots(): array
    {
        $slots = [];
        foreach (self::SLOTS as $slot) {
            if ($this->expected[$slot] === false) {
                continue;
            }
            $seen = $slot === self::SLOT_AUDIO ? (isset($this->sources[$slot]) || ($this->buffers[$slot] ?? []) !== []) : isset($this->described[$slot]);
            if ($seen) {
                $slots[] = $slot;
            }
        }
        return $slots;
    }

    /**
     * Whether some stream reported on has not delivered its first (key)frame yet.
     */
    private function awaitingExpected(): bool
    {
        foreach (self::SLOTS as $slot) {
            if ($this->expected[$slot] !== true) {
                continue;
            }
            $seen = $slot === self::SLOT_AUDIO ? (isset($this->sources[$slot]) || ($this->buffers[$slot] ?? []) !== []) : isset($this->described[$slot]);
            if (!$seen) {
                return true;
            }
        }
        return false;
    }

    private function graceExpired(): bool
    {
        return $this->waitingSince !== null && microtime(true) - $this->waitingSince > self::GRACE;
    }

    /**
     * Commit the header of the first segment once every expected stream is in, or the grace period
     * for the missing ones has passed.
     */
    private function maybeBegin(): void
    {
        if ($this->started || $this->closed) {
            return;
        }
        if ($this->awaitingExpected() && !$this->graceExpired()) {
            return;
        }
        $slots = $this->flowingSlots();
        if ($slots === []) {
            return;
        }
        $this->openSegment($slots);
    }

    /**
     * Move to a new segment if the flowing streams no longer match the open one — once every stream
     * that was reported on has arrived, or the grace period for it has passed, so that a stream
     * swapped for another (the camera for a screen share) yields one new segment, not two.
     */
    private function maybeRoll(): void
    {
        if (!$this->started || $this->closed || $this->writer === null) {
            return;
        }
        if ($this->awaitingExpected() && !$this->graceExpired()) {
            return;
        }
        $wanted = $this->flowingSlots();
        $current = [];
        foreach (self::SLOTS as $slot) {
            if ($this->inSegment($slot)) {
                $current[] = $slot;
            }
        }
        $same = $wanted === $current;
        foreach (self::VIDEO_SLOTS as $slot) {
            if ($same && isset($this->described[$slot]) && \in_array($slot, $wanted, true)
                && $this->writer->getVideoTrack($slot) !== $this->described[$slot]
            ) {
                $same = false;
            }
        }
        $this->rollPending = false;
        if ($same) {
            return;
        }
        if ($this->file === null) {
            if (!$this->warnedUnrollable) {
                $this->warnedUnrollable = true;
                Logger::log('The streams of a recording changed, but a stream output cannot be rolled over to a new file: keeping its initial tracks', Logger::WARNING);
            }
            return;
        }
        if ($wanted === []) {
            // Everything stopped: close the segment; the next one opens when something arrives.
            $this->writer->close();
            $this->writer = null;
            $this->started = false;
            $this->waitingSince = null;
            return;
        }
        $this->openSegment($wanted);
    }

    /**
     * Open a segment declaring the given slots, and flush what was buffered for them.
     *
     * @param list<string> $slots
     */
    private function openSegment(array $slots): void
    {
        if ($this->writer !== null) {
            $this->writer->close();
            $this->writer = null;
        }
        $out = $this->out;
        if ($this->file !== null) {
            $out = new LocalFile($this->segmentPath($slots));
            $this->segment++;
        }
        $this->started = true;
        $this->waitingSince = null;
        $this->rollPending = false;
        $this->writer = new MatroskaWriter($out, $this->format->docType());
        if (\in_array(self::SLOT_AUDIO, $slots, true)) {
            $this->writer->setAudioTrack('A_OPUS', 48000, 2, self::opusHead(2, 48000));
        }
        foreach (self::VIDEO_SLOTS as $slot) {
            if (\in_array($slot, $slots, true) && isset($this->described[$slot])) {
                $d = $this->described[$slot];
                $this->writer->setVideoTrack($d['codecId'], $d['width'], $d['height'], $d['private'], $slot);
            }
        }
        Logger::log('Recording '.($out instanceof LocalFile ? $out->file : 'stream').' with '.implode('+', $slots), Logger::VERBOSE);
        $this->writer->start();

        // Merge the buffers in timestamp order so the first cluster is well formed; buffered frames
        // of a stream not in this segment are dropped (it is gone, or waits for the next segment).
        $merged = [];
        foreach ($this->buffers as $slot => $frames) {
            if (!\in_array($slot, $slots, true)) {
                continue;
            }
            foreach ($frames as $f) {
                $merged[] = ['slot' => $slot, 'ms' => $f['ms'], 'data' => $f['data'], 'keyframe' => $f['keyframe']];
            }
        }
        usort($merged, static fn ($a, $b) => $a['ms'] <=> $b['ms']);
        foreach ($merged as $f) {
            $this->write($f['slot'], $f['data'], $f['ms'], $f['keyframe']);
        }
        $this->buffers = [];
    }

    /**
     * The path of the next segment: `<stem>.<n>_<streams>.<ext>`, or `<stem><n>_<streams>.<ext>`
     * for a directory stem.
     *
     * @param list<string> $slots
     */
    private function segmentPath(array $slots): string
    {
        $names = [];
        foreach (self::SLOTS as $slot) {
            if (\in_array($slot, $slots, true)) {
                $names[] = $slot === self::SLOT_PRESENTATION ? 'screen' : $slot;
            }
        }
        $separator = str_ends_with($this->stem, '/') ? '' : '.';
        $extension = $this->format === RecordingFormat::Webm ? 'webm' : 'mkv';
        return $this->stem.$separator.$this->segment.'_'.implode(',', $names).'.'.$extension;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->audioTrack = null;
        $this->videoTracks = [];
        $this->writer?->close();
        $this->writer = null;
    }

    /* ----------------------------------------------------------------- *
     *  Codec inspection (pure PHP, from the frame bytes).
     * ----------------------------------------------------------------- */

    /**
     * Identify the video codec of a keyframe and its picture size and codec-private data.
     *
     * @return array{string, int, int, string} [Matroska CodecID, width, height, CodecPrivate]
     *
     * @psalm-mutation-free
     */
    private static function describeVideo(string $frame): array
    {
        // H.264 and H.265 Annex-B always begin with a start code; the parameter sets tell them apart.
        if (str_starts_with($frame, "\x00\x00\x00\x01") || str_starts_with($frame, "\x00\x00\x01")) {
            if (HevcBitstream::isHevc($frame)) {
                [$w, $h, $hvcc] = HevcBitstream::describe($frame);
                return ['V_MPEGH/ISO/HEVC', $w, $h, $hvcc];
            }
            [$w, $h, $avcc] = self::h264Config($frame);
            return ['V_MPEG4/ISO/AVC', $w, $h, $avcc];
        }
        $first = \ord($frame[0]);
        // VP8 keyframe carries the 0x9d 0x01 0x2a start code and the dimensions (checked before
        // AV1: the first byte of a VP8 keyframe can look like an AV1 OBU header).
        if (\strlen($frame) > 9 && substr($frame, 3, 3) === "\x9d\x01\x2a") {
            $w = (\ord($frame[6]) | (\ord($frame[7]) << 8)) & 0x3FFF;
            $h = (\ord($frame[8]) | (\ord($frame[9]) << 8)) & 0x3FFF;
            return ['V_VP8', $w, $h, ''];
        }
        // AV1: an OBU header has the forbidden bit clear and a small type; a temporal unit usually
        // starts with a temporal delimiter (type 2) or sequence header (type 1).
        $obuType = ($first >> 3) & 0x0F;
        if (($first & 0x80) === 0 && ($obuType === 1 || $obuType === 2 || $obuType === 6)) {
            [$w, $h, $av1c] = Av1Bitstream::describe($frame);
            return ['V_AV1', $w, $h, $av1c];
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
     * @param ?string $codecId The Matroska codec of the track, when already known.
     * @return array{string, bool} [frame bytes to store, is keyframe]
     *
     * @psalm-mutation-free
     */
    private static function transformVideo(string $frame, ?string $codecId): array
    {
        $first = \ord($frame[0]);
        // Once the track is described, its codec is known; the bitstream sniffing below is only
        // needed for the first keyframe (an inter frame of one codec can look like another's).
        switch ($codecId) {
            case 'V_MPEGH/ISO/HEVC':
                return [HevcBitstream::toLengthPrefixed($frame), HevcBitstream::isKeyframe($frame)];
            case 'V_MPEG4/ISO/AVC':
                return self::h264ToAvcc($frame);
            case 'V_AV1':
                return [$frame, Av1Bitstream::isKeyframe($frame)];
            case 'V_VP9':
                return [$frame, self::vp9IsKeyframe($frame)];
            case 'V_VP8':
                return [$frame, ($first & 0x01) === 0];
        }
        if (str_starts_with($frame, "\x00\x00\x00\x01") || str_starts_with($frame, "\x00\x00\x01")) {
            if (HevcBitstream::isHevc($frame)) {
                return [HevcBitstream::toLengthPrefixed($frame), HevcBitstream::isKeyframe($frame)];
            }
            return self::h264ToAvcc($frame); // [avcc bytes, keyframe]
        }
        // VP8 keyframe = low bit of the first byte clear.
        if (\strlen($frame) > 9 && substr($frame, 3, 3) === "\x9d\x01\x2a") {
            return [$frame, true];
        }
        // AV1: a temporal unit that carries a sequence header (type 1) starts a new sequence.
        $obuType = ($first >> 3) & 0x0F;
        if (($first & 0x80) === 0 && ($obuType === 1 || $obuType === 2 || $obuType === 6)) {
            return [$frame, Av1Bitstream::isKeyframe($frame)];
        }
        // VP9 keyframe: frame_type bit is 0 (parsed in vp9IsKeyframe).
        if (($first >> 6) === 0x02) {
            return [$frame, self::vp9IsKeyframe($frame)];
        }
        // VP8 fallback.
        return [$frame, (\ord($frame[0]) & 0x01) === 0];
    }

    /* ---- H.264 ---- */

    /**
     * @return list<string> NAL units (without start codes).
     *
     * @psalm-pure
     */
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
     *
     * @psalm-mutation-free
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
     *
     * @psalm-pure
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
     *
     * @psalm-mutation-free
     */
    private static function h264SpsSize(string $sps): array
    {
        $rbsp = self::stripEmulationPrevention(substr($sps, 1)); // drop the NAL header byte
        $br = new BitReader($rbsp);
        $profileIdc = $br->bits(8);
        $br->bits(8); // constraint flags + reserved
        $br->bits(8); // level_idc
        $br->ue();    // seq_parameter_set_id
        if (\in_array($profileIdc, [100, 110, 122, 244, 44, 83, 86, 118, 128, 138, 139, 134, 135], true)) {
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

    /**
     * @psalm-pure
     */
    private static function stripEmulationPrevention(string $data): string
    {
        // Remove 0x03 in 0x00 0x00 0x03 sequences (RBSP anti-emulation).
        return preg_replace('/\x00\x00\x03/', "\x00\x00", $data) ?? $data;
    }

    /* ---- VP9 ---- */

    /**
     * @return array{int, int}
     *
     * @psalm-mutation-free
     */
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

    /**
     * @psalm-mutation-free
     */
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

    /* ---- OPUS ---- */

    /**
     * @psalm-pure
     */
    private static function opusHead(int $channels, int $sampleRate): string
    {
        return 'OpusHead'.pack('CCvVvC', 1, $channels, 312, $sampleRate, 0, 0);
    }
}
