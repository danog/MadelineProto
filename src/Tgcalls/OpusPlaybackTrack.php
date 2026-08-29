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

use danog\MadelineProto\Loop\VoIP\DjLoop;
use Revolt\EventLoop;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

use function Amp\delay;

/**
 * An outgoing audio track that emits the pre-encoded OPUS packets produced by a {@see DjLoop}.
 *
 * MadelineProto stores and generates audio as 60ms mono 48kHz OGG OPUS packets, which is exactly
 * what both the tgcalls one-to-one peer and the Telegram group call SFU expect: the packets are
 * therefore passed through to the RTP sender without a decode/encode round trip, which also means
 * that no codec library (and therefore no FFI) is ever needed for playback.
 *
 * The track runs a background producer task that pushes the frames into its own {@see Queue},
 * which the RTP sender drains reactively through {@see parent::getConsumer()}: the old polling
 * contract, in which the sender repeatedly called {@see self::receiveData()}, is gone. Pacing
 * happens inside the producer by returning `null` (i.e. not pushing) until the next 60ms frame is
 * due.
 *
 * @internal
 */
final class OpusPlaybackTrack extends MediaStreamTrack
{
    /** Duration of a single MadelineProto OPUS frame, in seconds. */
    public const FRAME_DURATION = 0.06;
    /** Number of 48kHz samples in a single MadelineProto OPUS frame. */
    public const FRAME_SAMPLES = 2880;
    /** OPUS always uses a 48kHz clock rate on the wire. */
    public const CLOCK_RATE = 48000;

    /**
     * The loudness we report for the frames we transmit, in -dBov.
     *
     * Every OPUS packet has to carry the `urn:ietf:params:rtp-hdrext:ssrc-audio-level` header
     * extension of RFC 6464. The Telegram SFU only forwards the participants it believes are
     * speaking, and it decides that from this extension alone: a stream that never carries one is
     * treated as permanently silent and is never relayed to anybody, however much RTP it sends.
     *
     * The value is a constant rather than a measurement because playback is deliberately a
     * pass-through — decoding OPUS just to compute a level would drag in a codec library, and
     * therefore FFI, for every call. A player is in any case continuously "speaking", so a fixed
     * moderate level describes it accurately.
     */
    private const AUDIO_LEVEL_DBOV = -20;

    /** Wall clock time at which the next frame is due. */
    private ?float $nextFrame = null;
    /** RTP timestamp (in 48kHz samples) of the next frame. */
    private int $timestamp = 0;
    /** Wall clock time at which the producer should next attempt to emit a frame. */
    private ?float $nextDue = null;

    /** Wall clock time corresponding to the first WebM frame we released. */
    private ?float $webmStartedAt = null;
    /** Source timestamp of the first WebM frame, used to rebase onto our own RTP clock. */
    private ?int $webmBaseTimestamp = null;
    /** RTP timestamp the current WebM file was rebased onto. */
    private int $webmTimestampOffset = 0;
    /** @var array{data: string, timestamp: int}|null The WebM frame whose presentation time has not arrived yet. */
    private ?array $webmPending = null;
    /** Source timestamp of the previously released WebM frame, used to measure its cadence. */
    private ?int $webmPrevTimestamp = null;
    /** The current WebM file's real audio frame interval in seconds, from its own timestamps. */
    private ?float $webmFrameInterval = null;

    private bool $muted = true;

    public function __construct(
        private readonly DjLoop $dj,
        private readonly CallInterface $call,
        private readonly ?WebmSource $webm = null,
    ) {
        parent::__construct(MediaKind::Audio);
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
     * Whether we're currently not transmitting any audio.
     */
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * Produce the next audio frame, if one is due.
     *
     * Returns `null` (and pushes nothing) to pace the stream until the next frame's presentation
     * time arrives.
     */
    private function produce(): ?EncodedPacket
    {
        // Audio coming from a WebM file wins: it has to stay in sync with that file's video.
        if ($this->webmPending !== null
            || ($this->webm?->hasAudio() ?? false)
            || ($this->webmStartedAt !== null && ($this->webm?->isPlaying() ?? false))
        ) {
            return $this->receiveWebm();
        }
        if ($this->webmStartedAt !== null) {
            // The file is over: hand the frame grid back to the DJ loop, starting now so that the
            // first DJ frame is not immediately overdue.
            $this->webmStartedAt = null;
            $this->webmBaseTimestamp = null;
            $this->webmPrevTimestamp = null;
            $this->webmFrameInterval = null;
            $this->nextFrame = null;
        }

        $now = microtime(true);
        if ($this->nextFrame === null) {
            $this->nextFrame = $now;
        } elseif ($now < $this->nextFrame) {
            $this->nextDue = $this->nextFrame;
            return null;
        } elseif ($now - $this->nextFrame > 1.0) {
            // We fell way behind (the process was suspended, or the call was just resumed):
            // resynchronize instead of bursting out a second of audio.
            $this->nextFrame = $now;
        }

        $opus = $this->dj->tryPullPacket();
        if ($opus === null) {
            if (!$this->muted) {
                $this->muted = true;
                $this->call->log("Muting outgoing audio in {$this->call}");
            }
            // Keep the frame grid aligned while silent, so that playback resumes in sync.
            $this->nextFrame += self::FRAME_DURATION;
            $this->timestamp += self::FRAME_SAMPLES;
            $this->nextDue = $this->nextFrame;
            return null;
        }
        if ($this->muted) {
            $this->muted = false;
            $this->call->log("Unmuting outgoing audio in {$this->call}");
        }

        $packet = new EncodedPacket($opus, $this->timestamp, audioLevel: self::AUDIO_LEVEL_DBOV);

        $this->nextFrame += self::FRAME_DURATION;
        $this->timestamp += self::FRAME_SAMPLES;
        $this->nextDue = $this->nextFrame;

        return $packet;
    }

    /**
     * Produce the next OPUS frame of the WebM file being played.
     *
     * The frames of a file are not on our own 60ms grid (20ms is what most encoders emit), so they
     * are paced and timestamped by the clock of the file itself, exactly like its video frames.
     */
    private function receiveWebm(): ?EncodedPacket
    {
        $frame = $this->webmPending ?? $this->webm?->pullAudio();
        $this->webmPending = null;
        if ($frame === null) {
            // The file's audio queue momentarily ran dry. Once we have measured the file's own
            // frame interval, retry at exactly that cadence so pacing follows the file's real
            // emission rate; until the first two frames are seen, fall back to our 60ms frame grid.
            $this->nextDue = microtime(true) + ($this->webmFrameInterval ?? self::FRAME_DURATION);
            return null;
        }

        $now = microtime(true);
        if ($this->webmStartedAt === null || $this->webmBaseTimestamp === null) {
            $this->webmStartedAt = $now;
            $this->webmBaseTimestamp = $frame['timestamp'];
            // Carry on from the DJ loop's clock, so that the RTP timestamps never rewind.
            $this->webmTimestampOffset = $this->timestamp;
        }

        // Release the frame only once its presentation time has arrived.
        $elapsed = (float) ($frame['timestamp'] - $this->webmBaseTimestamp) / (float) self::CLOCK_RATE;
        if ($now < $this->webmStartedAt + $elapsed) {
            $this->webmPending = $frame;
            $this->nextDue = $this->webmStartedAt + $elapsed;
            return null;
        }

        if ($this->muted) {
            $this->muted = false;
            $this->call->log("Unmuting outgoing audio in {$this->call}");
        }

        $timestamp = $this->webmTimestampOffset + ($frame['timestamp'] - $this->webmBaseTimestamp);
        // Where the DJ loop would resume, were the file to end right after this frame.
        $this->timestamp = $timestamp + self::FRAME_SAMPLES;
        // Learn this file's real audio frame interval from consecutive source timestamps, so an
        // underrun retry can honor the file's own cadence rather than the fixed 60ms DJ grid.
        if ($this->webmPrevTimestamp !== null) {
            $delta = $frame['timestamp'] - $this->webmPrevTimestamp;
            if ($delta > 0) {
                $this->webmFrameInterval = $delta / (float) self::CLOCK_RATE;
            }
        }
        $this->webmPrevTimestamp = $frame['timestamp'];
        // Pull and hold the next WebM frame right away so its own presentation time paces us.
        $this->nextDue = $now;

        return new EncodedPacket($frame['data'], $timestamp, audioLevel: self::AUDIO_LEVEL_DBOV);
    }
}
