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
 * expects is settled by {@see GroupSdp}, from {@see WebmSource::getVideoCodec()}.
 *
 * As with {@see OpusPlaybackTrack}, a background producer task drains the source and pushes the
 * frames into the track's {@see Queue}, releasing them according to their own timestamps rather
 * than by sleeping.
 *
 * @internal
 */
final class VideoPlaybackTrack extends MediaStreamTrack
{
    /**
     * How often to re-check the source while no file's cadence is yet known — i.e. before the
     * first two frames of a file have been seen, or while idle between files. This is a poll for
     * *work*, never a pace for *media*: once a file is flowing, every wait is derived from that
     * file's own frame timestamps (see {@see self::$frameInterval}), so playback speed never
     * depends on a hardcoded duration.
     */
    private const IDLE_POLL = 0.02;

    /** Wall clock time corresponding to the first frame we released. */
    private ?float $startedAt = null;
    /** Source timestamp of the first frame, used to rebase onto our own RTP clock. */
    private ?int $baseTimestamp = null;
    /** RTP timestamp offset, so restarting a file does not rewind the clock. */
    private int $timestampOffset = 0;
    /** @var array{data: string, timestamp: int, keyframe: bool}|null The frame whose presentation time has not arrived yet. */
    private ?array $pending = null;
    /** RTP timestamp of the last frame we released. */
    private int $lastTimestamp = 0;
    /** Source timestamp of the previously released frame, used to measure the file's cadence. */
    private ?int $prevSourceTimestamp = null;
    /** The current file's real inter-frame interval in seconds, measured from its own timestamps. */
    private ?float $frameInterval = null;
    /** Wall clock time at which the producer should next attempt to emit a frame. */
    private ?float $nextDue = null;

    private bool $playing = false;
    /** Whether signaling has selected the codec that the current file contains. */
    private bool $transportReady = true;

    public function __construct(
        private readonly WebmSource $source,
        private readonly CallInterface $call,
    ) {
        parent::__construct(MediaKind::Video);
        EventLoop::queue(function (): void {
            while (!$this->isEnded() && !$this->call->isCallEnded()) {
                $packet = $this->produce();
                if ($packet !== null) {
                    $this->frameQueue->push($packet);
                }
                // Sleep reactively until the next frame is due (its media cadence), rather than
                // polling for work at a fixed interval.
                $wait = ($this->nextDue ?? microtime(true)) - microtime(true);
                if ($wait > 0) {
                    delay($wait);
                }
            }
        });
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
        $this->transportReady = $transportReady;
        if (!$transportReady) {
            $this->nextDue = microtime(true) + self::IDLE_POLL;
        }
    }

    /**
     * Produce the next video frame, if its presentation time has arrived.
     */
    private function produce(): ?EncodedPacket
    {
        if (!$this->transportReady) {
            $this->nextDue = microtime(true) + self::IDLE_POLL;
            return null;
        }
        $frame = $this->pending ?? $this->source->pullVideo();
        $this->pending = null;
        if ($frame === null) {
            if ($this->playing && $this->source->isExhausted()) {
                $this->playing = false;
                $this->call->log("Finished playing video in {$this->call}");
                // Keep the clock advancing so a new file starts after, not on top of, this one.
                $this->timestampOffset = $this->lastTimestamp;
                $this->startedAt = null;
                $this->baseTimestamp = null;
                $this->prevSourceTimestamp = null;
                $this->frameInterval = null;
            }
            // The queue momentarily ran dry. If a file is mid-flight, wait exactly one of its own
            // measured frame intervals so the retry tracks the file's real emission rate rather
            // than a hardcoded cadence; if we are still buffering the first frames or sitting idle
            // between files, there is no media rate to honor yet, so fall back to a plain poll.
            $this->nextDue = microtime(true) + ($this->frameInterval ?? self::IDLE_POLL);
            return null;
        }

        $now = microtime(true);
        if ($this->startedAt === null || $this->baseTimestamp === null) {
            $this->startedAt = $now;
            $this->baseTimestamp = $frame['timestamp'];
            $this->playing = true;
            $this->call->log("Started playing video in {$this->call}");
        }

        // Release the frame only once its presentation time has arrived.
        $elapsed = (float) ($frame['timestamp'] - $this->baseTimestamp) / (float) WebmSource::VIDEO_CLOCK_RATE;
        $due = $this->startedAt + $elapsed;
        if ($now < $due) {
            $this->pending = $frame;
            $this->nextDue = $due;
            return null;
        }

        $timestamp = $this->timestampOffset + ($frame['timestamp'] - $this->baseTimestamp);
        $this->lastTimestamp = $timestamp;
        // Learn this file's real frame cadence from the gap between consecutive source timestamps,
        // so an underrun retry can honor the file's own emission rate instead of a fixed interval.
        if ($this->prevSourceTimestamp !== null) {
            $delta = $frame['timestamp'] - $this->prevSourceTimestamp;
            if ($delta > 0) {
                $this->frameInterval = $delta / WebmSource::VIDEO_CLOCK_RATE;
            }
        }
        $this->prevSourceTimestamp = $frame['timestamp'];
        $this->source->setPlaybackPosition(
            (int) (($frame['timestamp'] - $this->baseTimestamp) * 1000 / WebmSource::VIDEO_CLOCK_RATE)
        );
        // Pull and hold the next frame right away so its own presentation time paces us.
        $this->nextDue = $now;

        return new EncodedPacket($frame['data'], $timestamp, $frame['keyframe']);
    }
}
