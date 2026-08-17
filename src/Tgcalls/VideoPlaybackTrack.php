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

use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

/**
 * An outgoing video track that emits the pre-encoded frames of a Matroska or WebM file.
 *
 * Nothing is decoded or re-encoded: the frames are packetized straight into RTP by the pure-PHP
 * payloader of whichever codec the file holds — VP8, VP9 or H.264, the three a Telegram call
 * carries — so video works without the FFI extension just like audio does. Which one the peer
 * expects is settled by {@see GroupSdp}, from {@see WebmSource::getVideoCodec()}.
 *
 * As with {@see OpusPlaybackTrack}, {@see self::receiveData()} must never suspend, so frames are
 * released according to their own timestamps rather than by sleeping.
 *
 * @internal
 */
final class VideoPlaybackTrack extends MediaStreamTrack
{
    protected MediaKind $kind = MediaKind::Video;

    /** Wall clock time corresponding to the first frame we released. */
    private ?float $startedAt = null;
    /** Source timestamp of the first frame, used to rebase onto our own RTP clock. */
    private ?int $baseTimestamp = null;
    /** RTP timestamp offset, so restarting a file does not rewind the clock. */
    private int $timestampOffset = 0;
    /** @var array{data: string, timestamp: int, keyframe: bool}|null The frame whose presentation time has not arrived yet. */
    private ?array $pending = null;

    private bool $playing = false;

    public function __construct(
        private readonly WebmSource $source,
        private readonly CallInterface $call,
    ) {
        parent::__construct();
    }

    /**
     * Whether any video is currently being transmitted.
     */
    public function isPlaying(): bool
    {
        return $this->playing;
    }

    #[\Override]
    public function receiveData(): ?EncodedPacket
    {
        if ($this->ended || $this->call->isCallEnded()) {
            return null;
        }

        $frame = $this->pending ?? $this->source->pullVideo();
        $this->pending = null;
        if ($frame === null) {
            if ($this->playing && $this->source->isExhausted()) {
                $this->playing = false;
                $this->call->log("Finished playing video in {$this->call}");
                // Keep the clock advancing so a new file starts after, not on top of, this one.
                $this->timestampOffset = $this->lastTimestamp();
                $this->startedAt = null;
                $this->baseTimestamp = null;
            }
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
            return null;
        }

        $timestamp = $this->timestampOffset + ($frame['timestamp'] - $this->baseTimestamp);
        $this->lastTimestamp = $timestamp;
        $this->source->setPlaybackPosition(
            (int) (($frame['timestamp'] - $this->baseTimestamp) * 1000 / WebmSource::VIDEO_CLOCK_RATE)
        );

        return new EncodedPacket($frame['data'], $timestamp, $frame['keyframe']);
    }

    /** RTP timestamp of the last frame we released. */
    private int $lastTimestamp = 0;

    private function lastTimestamp(): int
    {
        return $this->lastTimestamp;
    }
}
