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
use danog\MadelineProto\CallStream;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use Revolt\EventLoop;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;

/**
 * The incoming media of one party of a call: which streams they send, in which codecs, and the
 * recorder (if any) their frames are routed to.
 *
 * Every remote track of the party is drained here, exactly once (a track's frame queue has a single
 * consumer, and grows without bound if nobody drains it): the frames are sniffed for their codec —
 * the first keyframe of every new video source is described, see {@see CallRecorder::describeVideo()} —
 * and pushed into the attached {@see CallRecorder} (a fixed file or a segment series) and, for a
 * one-to-one call recorded to OGG, the {@see OpusRecorder}. Video may instead be pushed in frame by
 * frame by an owner that routes it between slots itself ({@see self::pushVideoFrame()}).
 *
 * Which streams the party sends is told by signaling ({@see self::setExpected()}: the peer's media
 * state, or a group call participant's flags); the {@see IncomingMediaObserver} is told whenever the
 * available streams or their codecs change, and whenever a recording starts or ends, and turns that
 * into a {@see \danog\MadelineProto\EventHandler\Calls\CallStreams} update.
 *
 * @psalm-import-type StreamMask from \danog\MadelineProto\CallStream
 *
 * @internal
 */
final class IncomingMedia implements RecordingObserver
{
    /** Every slot, as {@see CallStream} flags. */
    private const SLOTS = [CallStream::AUDIO, CallStream::VIDEO, CallStream::SCREEN];
    /** Granule increment assumed for an OPUS frame whose duration cannot be derived, and the longest one there is. */
    private const DEFAULT_FRAME_SAMPLES = 960;
    private const MAX_FRAME_SAMPLES = 5760;

    /**
     * Which streams the party is sending, as far as signaling tells: true on, false off, null unknown.
     *
     * @var array<CallStream::AUDIO|CallStream::VIDEO|CallStream::SCREEN, ?bool> By {@see CallStream} flag.
     */
    private array $expected = [CallStream::AUDIO => null, CallStream::VIDEO => null, CallStream::SCREEN => null];
    /** @var array<int, string> The Matroska codec ID of every slot ({@see CallStream} flag) media was seen for, while it is on. */
    private array $codecs = [];
    /** @var array<int, int> The source (track) each video slot's frames currently come from, for the codec sniffing. */
    private array $sources = [];
    /** The last reported state, to tell the observer only about actual changes. */
    private ?string $reported = null;

    private ?CallRecorder $recorder = null;
    private ?OpusRecorder $opusRecorder = null;
    /** RTP timestamp of the previous audio frame, for the OGG granule increments. */
    private ?int $lastAudioTs = null;

    private ?RemoteStreamTrack $audioTrack = null;
    /**
     * Video tracks drained here, keyed by `<slot>:<source>`. A slot may have several: a group call
     * participant's camera is simulcast on several SSRCs and the SFU forwards whichever layer it
     * picks, so all of them feed the slot, and a switch between them is just a change of source.
     *
     * @var array<string, RemoteStreamTrack>
     */
    private array $videoTracks = [];
    /** @var array<string, int> The slot of each video track, by the same key. */
    private array $videoSlots = [];
    private ?ConcurrentIterator $audioConsumer = null;
    /** @var array<string, ConcurrentIterator> */
    private array $videoConsumers = [];
    private bool $closed = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(private ?IncomingMediaObserver $observer = null)
    {
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
     * Drop the live track subscriptions (not serializable) and a stream-backed recorder (which
     * cannot be reopened); {@see self::resume()} re-subscribes and keeps recording into a file.
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['audioConsumer'], $vars['videoConsumers']);
        if ($this->recorder?->file === null) {
            unset($vars['recorder']);
        }
        if ($this->opusRecorder?->file === null) {
            unset($vars['opusRecorder']);
        }
        return $vars;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $data): void
    {
        // Synchronous state restoration only — no async work here (see resume()).
        $this->recorder = null;
        $this->opusRecorder = null;
        /** @var mixed $value */
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->audioConsumer = null;
        $this->videoConsumers = [];
    }

    /**
     * @psalm-external-mutation-free
     */
    public function setObserver(?IncomingMediaObserver $observer): void
    {
        $this->observer = $observer;
    }

    /**
     * Reopen the recorders' files and re-subscribe to the resumed tracks, after the whole call graph
     * has been deserialized. Never during unserialize (reopening a file suspends the fiber).
     */
    public function resume(): void
    {
        if ($this->closed) {
            return;
        }
        $this->recorder?->resume();
        $this->opusRecorder?->resume();
        if ($this->recorder?->isClosed()) {
            $this->recorder = null;
        }
        if ($this->audioTrack !== null && $this->audioConsumer === null) {
            $this->audioConsumer = $this->audioTrack->getConsumer();
            EventLoop::queue($this->drainAudio(...));
        }
        foreach ($this->videoTracks as $key => $track) {
            if (!isset($this->videoConsumers[$key])) {
                $this->videoConsumers[$key] = $track->getConsumer();
                EventLoop::queue(fn () => $this->drainVideo($key));
            }
        }
    }

    /**
     * Stop draining the tracks (of a connection about to be closed), keeping the recorders so they
     * carry on with the tracks of the next connection (see {@see self::setTrack()}).
     *
     * @psalm-external-mutation-free
     */
    public function detachTracks(): void
    {
        $this->audioTrack = null;
        $this->audioConsumer = null;
        $this->videoTracks = [];
        $this->videoSlots = [];
        $this->videoConsumers = [];
    }

    /**
     * Tell which streams the party is sending, as signaling reports it (null leaves a stream's
     * state unchanged).
     */
    public function setExpected(?bool $audio = null, ?bool $video = null, ?bool $presentation = null): void
    {
        if ($this->closed) {
            return;
        }
        foreach ([CallStream::AUDIO => $audio, CallStream::VIDEO => $video, CallStream::SCREEN => $presentation] as $slot => $value) {
            if ($value === null || $this->expected[$slot] === $value) {
                continue;
            }
            $this->expected[$slot] = $value;
            if (!$value) {
                // The stream is gone; when it comes back it is a new track, to be described afresh.
                unset($this->codecs[$slot], $this->sources[$slot]);
            }
        }
        $this->recorder?->setExpected($audio, $video, $presentation);
        $this->report(null, null);
    }

    /**
     * Whether signaling has reported anything about the party's streams yet.
     *
     * @psalm-mutation-free
     */
    public function isKnown(): bool
    {
        foreach ($this->expected as $value) {
            if ($value !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * The streams the party currently sends, as {@see CallStream} flags.
     *
     * @psalm-mutation-free
     *
     * @return StreamMask
     */
    public function getAvailable(): int
    {
        $streams = 0;
        foreach ($this->expected as $slot => $value) {
            if ($value === true) {
                $streams |= $slot;
            }
        }
        return $streams;
    }

    /**
     * The codec of every stream media was seen for, keyed by {@see CallStream} flag.
     *
     * @return array<int, string>
     *
     * @psalm-mutation-free
     */
    public function getCodecs(): array
    {
        $codecs = [];
        foreach (self::SLOTS as $slot) {
            if (isset($this->codecs[$slot]) && $this->expected[$slot] !== false) {
                $codecs[$slot] = $this->codecs[$slot];
            }
        }
        return $codecs;
    }

    /**
     * Record the party into one file (or stream) with a fixed set of tracks (see
     * {@see CallRecorder::fixed()}), finishing the previous recording (if any).
     *
     * @param ?StreamMask $streams The {@see CallStream} flags to record, or null for every stream the peer currently sends.
     */
    public function recordFixed(LocalFile|WritableStream $out, RecordingFormat $format, ?int $streams): void
    {
        $this->stopRecording();
        if ($this->closed) {
            return;
        }
        // The recorder reports to us from its construction on: a file with no video track to
        // describe is opened (and reported) right away.
        $recorder = CallRecorder::fixed($out, $format, $streams, $this);
        $this->recorder = $recorder;
        $recorder->setExpected(...array_values($this->expected));
    }

    /**
     * Record the party as a numbered series of segment files (see {@see CallRecorder::series()}),
     * finishing the previous recording (if any).
     */
    public function recordSeries(LocalFile $stem, RecordingFormat $format): void
    {
        $this->stopRecording();
        if ($this->closed) {
            return;
        }
        $recorder = CallRecorder::series($stem, $format, $this);
        $this->recorder = $recorder;
        $recorder->setExpected(...array_values($this->expected));
    }

    /**
     * Finish the recording in progress, if any.
     */
    public function stopRecording(): void
    {
        $previous = $this->recorder;
        $this->recorder = null;
        $previous?->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function getRecorder(): ?CallRecorder
    {
        return $this->recorder;
    }

    /**
     * Attach an OGG OPUS recorder for the party's audio, finishing the previous one (if any).
     */
    public function setOpusRecorder(?OpusRecorder $recorder): void
    {
        $previous = $this->opusRecorder;
        $this->opusRecorder = null;
        if ($previous !== null) {
            $previous->close();
            $this->report(RecordingEvent::Ended, $previous->file);
        }
        if ($recorder === null || $this->closed) {
            $recorder?->close();
            return;
        }
        $this->opusRecorder = $recorder;
        $this->lastAudioTs = null;
        $this->report(RecordingEvent::Started, $recorder->file);
    }

    /**
     * Attach an incoming track of the party, whose frames are then drained here.
     *
     * @param int $slot For a video track, whether it is the camera ({@see CallStream::VIDEO}) or the screen share ({@see CallStream::SCREEN}).
     */
    public function setTrack(RemoteStreamTrack $track, int $slot = CallStream::VIDEO): void
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
        $key = $slot.':'.self::sourceOf($track);
        if (isset($this->videoTracks[$key])) {
            return;
        }
        $this->videoTracks[$key] = $track;
        $this->videoSlots[$key] = $slot;
        $this->videoConsumers[$key] = $track->getConsumer();
        EventLoop::queue(fn () => $this->drainVideo($key));
    }

    private function drainAudio(): void
    {
        $consumer = $this->audioConsumer;
        $track = $this->audioTrack;
        if ($consumer === null || $track === null) {
            return;
        }
        $source = self::sourceOf($track);
        /** @var mixed $frame */
        foreach ($consumer as $frame) {
            if ($this->closed || $this->audioTrack !== $track) {
                return;
            }
            if ($frame instanceof EncodedPacket) {
                $this->pushAudioFrame($frame->getData(), $frame->getTimestamp(), $source);
            }
        }
    }

    private function drainVideo(string $key): void
    {
        $consumer = $this->videoConsumers[$key] ?? null;
        $track = $this->videoTracks[$key] ?? null;
        $slot = $this->videoSlots[$key] ?? null;
        if ($consumer === null || $track === null || $slot === null) {
            return;
        }
        $source = self::sourceOf($track);
        /** @var mixed $frame */
        foreach ($consumer as $frame) {
            if ($this->closed || ($this->videoTracks[$key] ?? null) !== $track) {
                return;
            }
            if ($frame instanceof EncodedPacket) {
                $this->pushVideoFrame($frame->getData(), $frame->getTimestamp(), $source, $slot);
            }
        }
    }

    /**
     * Route one incoming OPUS frame.
     *
     * @param int $rtpTimestamp The frame's RTP timestamp (48 kHz clock).
     * @param int $source       Identifies the track the frame belongs to.
     */
    public function pushAudioFrame(string $data, int $rtpTimestamp, int $source = 0): void
    {
        if ($this->closed || $data === '') {
            return;
        }
        if (!isset($this->codecs[CallStream::AUDIO]) && $this->expected[CallStream::AUDIO] !== false) {
            $this->codecs[CallStream::AUDIO] = 'A_OPUS';
            $this->report(null, null);
        }
        $this->recorder?->pushAudioFrame($data, $rtpTimestamp, $source);
        if ($this->opusRecorder !== null) {
            // The OGG granule position must advance by the duration of each frame, which the RTP
            // timestamps give us directly; the first frame has no predecessor to measure against.
            $granule = self::DEFAULT_FRAME_SAMPLES;
            if ($this->lastAudioTs !== null) {
                $delta = ($rtpTimestamp - $this->lastAudioTs) & 0xFFFFFFFF;
                if ($delta > 0 && $delta <= self::MAX_FRAME_SAMPLES) {
                    $granule = $delta;
                }
            }
            $this->lastAudioTs = $rtpTimestamp;
            $this->opusRecorder->writeOpus($data, $granule);
        }
    }

    /**
     * Route one incoming video frame (still encoded, as it came off RTP).
     *
     * @param int    $rtpTimestamp The frame's RTP timestamp (90 kHz clock).
     * @param int    $source       Identifies the track the frame belongs to.
     * @param int    $slot         Whether this is the camera ({@see CallStream::VIDEO}) or the screen share ({@see CallStream::SCREEN}).
     */
    public function pushVideoFrame(string $data, int $rtpTimestamp, int $source = 0, int $slot = CallStream::VIDEO): void
    {
        if ($this->closed || $data === '' || $this->expected[$slot] === false) {
            return;
        }
        if (($this->sources[$slot] ?? null) !== $source) {
            // A new source: its codec is read off its first keyframe, like the recorder does.
            [, $keyframe] = CallRecorder::transformVideo($data, null);
            if ($keyframe) {
                [$codecId] = CallRecorder::describeVideo($data);
                $this->sources[$slot] = $source;
                if (($this->codecs[$slot] ?? null) !== $codecId) {
                    $this->codecs[$slot] = $codecId;
                    $this->report(null, null);
                }
            }
        }
        $this->recorder?->pushVideoFrame($data, $rtpTimestamp, $source, $slot);
    }

    /**
     * Tell the observer about the current state, if it changed since last time or a recording
     * event is to be reported.
     */
    private function report(?RecordingEvent $recording, ?LocalFile $file): void
    {
        $state = $this->getAvailable().':'.json_encode($this->getCodecs());
        if ($recording === null && $state === $this->reported) {
            return;
        }
        $this->reported = $state;
        $this->observer?->onIncomingMediaChanged($this, $recording, $file);
    }

    #[\Override]
    public function onRecordingStarted(CallRecorder $recorder, LocalFile|WritableStream $out): void
    {
        $this->report(RecordingEvent::Started, $out instanceof LocalFile ? $out : null);
    }

    #[\Override]
    public function onRecordingEnded(CallRecorder $recorder, LocalFile|WritableStream $out): void
    {
        if ($recorder === $this->recorder && $recorder->isClosed()) {
            $this->recorder = null;
        }
        $this->report(RecordingEvent::Ended, $out instanceof LocalFile ? $out : null);
    }

    /**
     * Finish every recording and stop draining.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->stopRecording();
        $this->setOpusRecorder(null);
        $this->closed = true;
        $this->detachTracks();
    }
}
