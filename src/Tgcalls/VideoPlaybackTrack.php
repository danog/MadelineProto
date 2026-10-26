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

use Amp\Pipeline\DisposedException;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MediaClock;
use Revolt\EventLoop;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

use function Amp\delay;

/**
 * An outgoing video track that emits the pre-encoded frames of a Matroska or WebM file.
 *
 * Nothing is decoded or re-encoded: the frames are packetized straight into RTP by the pure-PHP
 * payloader of whichever codec the file holds — VP8, VP9 or H.264, the three a Telegram call
 * carries — so video works without the FFI extension just like audio does. Which one the peer
 * expects is settled by {@see GroupSdp}, from {@see DjLoop::getVideoCodec()}.
 *
 * As with {@see OpusPlaybackTrack}, a background producer task drains the source and pushes the
 * frames to the RTP sender, releasing them according to their own timestamps.
 *
 * @internal
 */
final class VideoPlaybackTrack extends MediaStreamTrack
{
    /**
     * How often to re-check the source while no file's cadence is yet known — i.e. before the
     * first two frames of a file have been seen, or while idle between files. This is a poll for
     * *work*, never a pace for *media*: once a file is flowing, every wait is derived from that
     * file's own frame timestamps (see {@see self::$frameTicks}), so playback speed never
     * depends on a hardcoded duration.
     */
    private const IDLE_POLL_NS = 20_000_000;
    /** The frame interval assumed when a file ends before its cadence was measured, in ticks of the RTP clock (30 fps). */
    private const DEFAULT_FRAME_TICKS = DjLoop::VIDEO_CLOCK_RATE / 30;
    /** How late a frame may be released, in nanoseconds, before the track resynchronizes instead of catching up. */
    private const MAX_LATENESS_NS = 1_000_000_000;

    /** When the first frame was released (hrtime, in nanoseconds). */
    private ?int $startedAtNs = null;
    /** Source timestamp of the first frame, used to rebase onto our own RTP clock. */
    private ?int $baseTimestamp = null;
    /** RTP timestamp offset, so restarting a file does not rewind the clock. */
    private int $timestampOffset = 0;
    /** @var array{data: string, timestamp: int, keyframe: bool, file: int}|null The frame whose presentation time has not arrived yet. */
    private ?array $pending = null;
    /** RTP timestamp of the last frame we released. */
    private int $lastTimestamp = 0;
    /** Source timestamp of the previously released frame, used to measure the file's cadence. */
    private ?int $prevSourceTimestamp = null;
    /** The current file's real inter-frame interval in ticks of the RTP clock, measured from its own timestamps. */
    private ?int $frameTicks = null;
    /** The DJ loop's ID of the file whose frames are being played, see {@see DjLoop::$videoQueue}. */
    private ?int $file = null;
    /** When the producer should next attempt to emit a frame (hrtime, in nanoseconds). */
    private ?int $nextDueNs = null;
    /** Whether the frame to produce next isn't queued yet: the producer waits for it to be. */
    private bool $starved = false;

    private bool $playing = false;
    /** Whether signaling has selected the codec that the current file contains. */
    private bool $transportReady = true;
    /**
     * Whether the frames are consumed at their pace but discarded instead of transmitted: set while
     * a screencast replaces the camera video, so the camera playlist keeps advancing (its audio is
     * still transmitted) without queueing frames nobody sends.
     */
    private bool $suppressed = false;
    /**
     * Whether to hold non-keyframes until the next keyframe: after the transport was (re)negotiated
     * the peer sets up a fresh decoder, which can only start on a keyframe.
     */
    private bool $needKeyframe = false;
    /** Whether the producer task is running, to keep {@see self::startProducing()} idempotent. */
    private bool $producing = false;

    /** Frames released so far, for the diagnostics log. */
    private int $released = 0;

    public function __construct(
        private readonly DjLoop $source,
        private readonly CallControllerInterface $call,
        /** What this track carries, for the logs: the camera or the screencast. */
        private readonly string $label = 'camera',
    ) {
        parent::__construct(MediaKind::Video);
        $this->startProducing();
    }

    /**
     * @param array<string, mixed> $data
     */
    #[\Override]
    public function __unserialize(array $data): void
    {
        // Does NOT restart the producer (which reads the call's state, restored only once the whole
        // call graph has deserialized): starting it here could run it mid-deserialization if a nested
        // resume suspends the fiber. The call's deserializer calls {@see self::resume()} when ready.
        parent::__unserialize($data);
        // hrtime readings, meaningless after a reboot: the next frame starts the pacing again
        $this->startedAtNs = null;
        $this->nextDueNs = null;
        $this->producing = false;
    }

    /**
     * Restart the frame producer after a serialize/unserialize cycle. Called by the call's deserializer
     * once the whole call graph is restored; idempotent.
     */
    public function resume(): void
    {
        $this->startProducing();
    }

    /**
     * Drive the frame producer as a single long-lived, self-pacing event-loop task.
     */
    private function startProducing(): void
    {
        if ($this->producing) {
            return;
        }
        $this->producing = true;
        EventLoop::queue(function (): void {
            try {
                $this->produceLoop();
            } finally {
                $this->producing = false;
            }
        });
    }

    private function produceLoop(): void
    {
        {
            while (!$this->isEnded() && !$this->call->isCallEnded()) {
                $packet = $this->produce();
                if ($packet !== null) {
                    try {
                        $this->push($packet);
                    } catch (DisposedException|\Error $e) {
                        // The consumer (the RTP sender) went away — its connection was closed or
                        // replaced — while a frame was waiting to be taken: this track will never be
                        // read again, so stop producing instead of crashing the event loop.
                        $this->call->log("The outgoing video track of {$this->call} lost its consumer: {$e->getMessage()}", Logger::VERBOSE);
                        return;
                    }
                }
                // Sleep reactively until the next frame is due (its media cadence), rather than
                // polling for work at a fixed interval.
                $now = hrtime(true);
                $wait = ($this->nextDueNs ?? $now) - $now;
                if ($wait > 0) {
                    if ($this->starved) {
                        // Played as soon as it's queued, if it's late.
                        $this->source->awaitFrame($wait / 1e9);
                    } else {
                        delay($wait / 1e9);
                    }
                }
            }
        }
    }

    /**
     * Whether any video is currently being transmitted.
     */
    public function isPlaying(): bool
    {
        return $this->playing;
    }

    public function setTransportReady(bool $transportReady): void
    {
        if (!$transportReady && $this->transportReady) {
            $this->needKeyframe = true;
        }
        $this->transportReady = $transportReady;
        if (!$transportReady) {
            $this->nextDueNs = hrtime(true) + self::IDLE_POLL_NS;
        }
    }

    /**
     * Discard (true) or transmit (false) the frames; see {@see self::$suppressed}. Transmission
     * resumes on a keyframe.
     *
     * @psalm-external-mutation-free
     */
    public function setSuppressed(bool $suppressed): void
    {
        if ($this->suppressed && !$suppressed) {
            $this->needKeyframe = true;
        }
        $this->suppressed = $suppressed;
    }

    /**
     * The file being played is over: the next one starts on a fresh clock, right after this one.
     */
    private function finishFile(): void
    {
        $this->playing = false;
        $this->call->log("Finished playing video in {$this->call}");
        // Keep the clock advancing so a new file starts one frame after, not on top of, this one:
        // receivers assemble frames by RTP timestamp.
        $this->timestampOffset = $this->lastTimestamp + ($this->frameTicks ?? self::DEFAULT_FRAME_TICKS);
        $this->startedAtNs = null;
        $this->baseTimestamp = null;
        $this->prevSourceTimestamp = null;
        $this->frameTicks = null;
    }

    /**
     * Produce the next video frame, if its presentation time has arrived.
     */
    private function produce(): ?EncodedPacket
    {
        $this->starved = false;
        if (!$this->transportReady) {
            $this->nextDueNs = hrtime(true) + self::IDLE_POLL_NS;
            return null;
        }
        $frame = $this->pending ?? $this->source->pullVideo();
        $this->pending = null;
        if ($frame === null) {
            if ($this->playing && $this->source->isExhausted()) {
                $this->finishFile();
            }
            // The queue momentarily ran dry: wait for the next frame to be queued. For at most one of
            // the file's own frame intervals if a file is mid-flight, to notice that it ended; if
            // we are still buffering the first frames or sitting idle between files, there is no
            // media rate to honor yet, so fall back to a plain poll.
            $this->starved = true;
            $this->nextDueNs = hrtime(true) + ($this->frameTicks === null ? self::IDLE_POLL_NS : MediaClock::ticksToNs($this->frameTicks, DjLoop::VIDEO_CLOCK_RATE));
            return null;
        }

        if ($this->playing && $frame['file'] !== $this->file) {
            // The next file was queued right behind this one, without the queue ever running dry.
            $this->finishFile();
        }
        $now = hrtime(true);
        if ($this->startedAtNs === null || $this->baseTimestamp === null) {
            $this->file = $frame['file'];
            $this->startedAtNs = $now;
            $this->baseTimestamp = $frame['timestamp'];
            $this->playing = true;
            $this->call->log("Started playing video in {$this->call}");
        }

        // Release the frame only once its presentation time has arrived.
        $due = $this->startedAtNs + MediaClock::ticksToNs($frame['timestamp'] - $this->baseTimestamp, DjLoop::VIDEO_CLOCK_RATE);
        if ($now < $due) {
            $this->pending = $frame;
            $this->nextDueNs = $due;
            return null;
        }
        if ($now - $due > self::MAX_LATENESS_NS) {
            // We fell way behind (nobody took the frames until the call connected, or the process was
            // suspended): resynchronize instead of bursting out all of the video we're late with.
            $this->startedAtNs += $now - $due;
        }

        $timestamp = $this->timestampOffset + ($frame['timestamp'] - $this->baseTimestamp);
        $this->lastTimestamp = $timestamp;
        // Learn this file's real frame cadence from the gap between consecutive source timestamps,
        // so an underrun retry can honor the file's own emission rate instead of a fixed interval.
        if ($this->prevSourceTimestamp !== null) {
            $delta = $frame['timestamp'] - $this->prevSourceTimestamp;
            if ($delta > 0) {
                $this->frameTicks = $delta;
            }
        }
        $this->prevSourceTimestamp = $frame['timestamp'];
        $this->source->setPlaybackPosition(
            (int) ($frame['timestamp'] * 1000 / DjLoop::VIDEO_CLOCK_RATE),
            $frame['file'],
        );
        // Pull and hold the next frame right away so its own presentation time paces us.
        $this->nextDueNs = $now;

        if ($this->suppressed) {
            return null;
        }
        if ($this->needKeyframe) {
            if (!$frame['keyframe']) {
                return null;
            }
            $this->needKeyframe = false;
        }
        if ($this->released++ % 250 === 0) {
            $this->call->log("Releasing {$this->label} frame {$this->released} (".($frame['keyframe'] ? 'key' : 'inter').") in {$this->call}", Logger::VERBOSE);
        }

        return new EncodedPacket($frame['data'], $timestamp, $frame['keyframe']);
    }
}
