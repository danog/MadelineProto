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

use Amp\ByteStream\ReadableStream;
use Closure;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\RemoteUrl;
use Revolt\EventLoop;
use SplQueue;
use Throwable;

/**
 * Plays the video and OPUS audio of a Matroska or WebM file, without decoding either.
 *
 * Telegram calls carry exactly the codecs these containers store, so the frames are demuxed by
 * {@see Matroska} and handed to the RTP senders untouched. Audio and video keep the timestamps
 * they had in the file, which is what keeps them in sync.
 *
 * Every video codec a group call can carry is playable: VP8 and VP9 are stored exactly as RTP
 * wants them, and H.264 only has to be reframed by {@see H264Framing}.
 *
 * @internal
 */
final class WebmSource
{
    /** VP8 and every other video codec on the wire use a 90kHz RTP clock. */
    public const VIDEO_CLOCK_RATE = 90000;
    /** OPUS always uses a 48kHz RTP clock. */
    public const AUDIO_CLOCK_RATE = 48000;

    /** How far ahead of playback we buffer, in milliseconds. */
    private const BUFFER_AHEAD_MS = 2000;

    /**
     * The video codecs we can transmit, as `Matroska CodecID => SDP encoding name`.
     *
     * This is exactly the set a Telegram group call carries; see {@see GroupSdp} for the payload
     * types the SFU pairs them with.
     */
    public const VIDEO_CODECS = [
        'V_VP8' => 'VP8',
        'V_VP9' => 'VP9',
        'V_MPEG4/ISO/AVC' => 'H264',
    ];

    /** The audio codecs we can transmit, as `Matroska CodecID => SDP encoding name`. */
    public const AUDIO_CODECS = ['A_OPUS' => 'opus'];

    /** Pending video frames, as `['data' => string, 'timestamp' => int, 'keyframe' => bool]`. */
    private SplQueue $video;
    /** Pending OPUS frames, as `['data' => string, 'timestamp' => int]`. */
    private SplQueue $audio;

    /**
     * The SDP encoding name of the video track being played, or null if the file has none we can
     * transmit.
     */
    private ?string $videoCodec = null;
    /** Reframes H.264, and is left null for the codecs that need no rewriting. */
    private ?H264Framing $framing = null;

    private bool $finished = false;
    private bool $stopped = false;
    private bool $reading = false;
    private bool $playing = false;

    /** Highest source timestamp pushed so far, in milliseconds. */
    private int $bufferedUntilMs = 0;
    /** Playback position, in milliseconds, used to throttle the demuxer. */
    private int $playbackMs = 0;

    /**
     * @param ?Closure(string): void $onVideoCodec Called with the SDP encoding name of the video
     *                                             track as soon as a file's track list is known, so
     *                                             that the transport can be renegotiated for it.
     */
    public function __construct(
        private readonly CallInterface $call,
        private readonly ?Closure $onVideoCodec = null,
    ) {
        $this->video = new SplQueue;
        $this->audio = new SplQueue;
    }

    /**
     * The SDP encoding name of the video we are currently transmitting, if any.
     */
    public function getVideoCodec(): ?string
    {
        return $this->videoCodec;
    }

    /**
     * Start demuxing a file into the playback queues.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        $this->stopped = false;
        $this->finished = false;
        $this->playing = true;
        $this->video = new SplQueue;
        $this->audio = new SplQueue;
        $this->bufferedUntilMs = 0;
        $this->playbackMs = 0;
        $this->videoCodec = null;
        $this->framing = null;

        EventLoop::queue(function () use ($file): void {
            $this->reading = true;
            try {
                $matroska = new Matroska($file);
                $this->selectTracks($matroska);
                foreach ($matroska->frames as $frame) {
                    if ($this->stopped) {
                        break;
                    }
                    $this->push($frame);
                }
            } catch (Throwable $e) {
                $this->call->log("Could not play the file in {$this->call}: $e", Logger::ERROR);
            } finally {
                $this->reading = false;
                $this->finished = true;
            }
        });
    }

    /**
     * Work out what of a file we can actually transmit, and warn about the rest.
     */
    private function selectTracks(Matroska $matroska): void
    {
        foreach ($matroska->tracks as $track) {
            if ($this->videoCodec !== null || !isset(self::VIDEO_CODECS[$track['codec']])) {
                continue;
            }
            $this->videoCodec = self::VIDEO_CODECS[$track['codec']];
            if ($this->videoCodec === 'H264') {
                $this->framing = new H264Framing($track['private']);
            }
        }

        // Anything we cannot transmit is dropped by push(), which on its own would just look like
        // a file that plays silently or without a picture, so say exactly what was left out.
        $this->warnAboutDroppedTracks($matroska, Matroska::TRACK_TYPE_VIDEO, self::VIDEO_CODECS);
        $this->warnAboutDroppedTracks($matroska, Matroska::TRACK_TYPE_AUDIO, self::AUDIO_CODECS);

        if ($this->videoCodec !== null) {
            // The transport has to be renegotiated before the first frame goes out, or the peer
            // would decode it as whatever codec the previous file used.
            ($this->onVideoCodec ?? static fn (string $codec) => null)($this->videoCodec);
        }
    }

    /**
     * Warn when a file carries tracks of one kind but none of them in a codec we can transmit.
     *
     * @param array<string, string> $supported The codec table for that kind.
     */
    private function warnAboutDroppedTracks(Matroska $matroska, int $kind, array $supported): void
    {
        $found = [];
        foreach ($matroska->tracks as $track) {
            if ($track['type'] === $kind && !isset($supported[$track['codec']])) {
                $found[] = $track['codec'];
            } elseif ($track['type'] === $kind) {
                // At least one track of this kind is playable, nothing to report.
                return;
            }
        }
        if ($found === []) {
            return;
        }

        $name = $kind === Matroska::TRACK_TYPE_VIDEO ? 'video' : 'audio';
        $this->call->log(
            "The $name of the file played in {$this->call} will not be transmitted: it is ".
            implode(', ', $found).', and frames are fed to RTP exactly as the container stores them, '.
            'so a Telegram call can only carry '.implode(', ', array_keys($supported)).'.',
            Logger::WARNING
        );
    }

    /**
     * @param array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool} $frame
     */
    private function push(array $frame): void
    {
        $timestampMs = $frame['timestamp'];
        $this->bufferedUntilMs = max($this->bufferedUntilMs, $timestampMs);

        if (isset(self::AUDIO_CODECS[$frame['codec']])) {
            $this->audio->enqueue([
                'data' => $frame['data'],
                'timestamp' => (int) ($timestampMs * self::AUDIO_CLOCK_RATE / 1000),
            ]);
            return;
        }

        // A second video track in another codec is ignored: only the one we negotiated is sent,
        // and a codec we cannot transmit at all never reaches the queue.
        if ($this->videoCodec === null || (self::VIDEO_CODECS[$frame['codec']] ?? null) !== $this->videoCodec) {
            return;
        }
        $this->video->enqueue([
            'data' => $this->framing?->convert($frame['data'], $frame['keyframe']) ?? $frame['data'],
            'timestamp' => (int) ($timestampMs * self::VIDEO_CLOCK_RATE / 1000),
            'keyframe' => $frame['keyframe'],
        ]);
    }

    /**
     * Whether the demuxer should pause because playback is far enough behind.
     *
     * The generator is driven synchronously, so this is checked by the tracks rather than the
     * reader itself; it simply keeps memory bounded for long files.
     */
    public function shouldThrottle(): bool
    {
        return $this->bufferedUntilMs - $this->playbackMs > self::BUFFER_AHEAD_MS;
    }

    /**
     * Report how far playback has progressed, in milliseconds.
     */
    public function setPlaybackPosition(int $milliseconds): void
    {
        $this->playbackMs = max($this->playbackMs, $milliseconds);
    }

    /**
     * @return array{data: string, timestamp: int, keyframe: bool}|null
     */
    public function pullVideo(): ?array
    {
        if ($this->video->isEmpty()) {
            return null;
        }
        /** @var array{data: string, timestamp: int, keyframe: bool} */
        return $this->video->dequeue();
    }

    /**
     * @return array{data: string, timestamp: int}|null
     */
    public function pullAudio(): ?array
    {
        if ($this->audio->isEmpty()) {
            return null;
        }
        /** @var array{data: string, timestamp: int} */
        return $this->audio->dequeue();
    }

    public function hasVideo(): bool
    {
        return !$this->video->isEmpty();
    }

    public function hasAudio(): bool
    {
        return !$this->audio->isEmpty();
    }

    /**
     * Whether the file was fully read and both queues are drained.
     */
    public function isExhausted(): bool
    {
        return $this->finished && $this->video->isEmpty() && $this->audio->isEmpty();
    }

    /**
     * Whether a file is still being played, even if a queue momentarily ran dry because the
     * demuxer is behind.
     */
    public function isPlaying(): bool
    {
        if ($this->playing && $this->isExhausted()) {
            $this->playing = false;
        }
        return $this->playing;
    }

    /**
     * Stop playback and drop everything still buffered.
     */
    public function stop(): void
    {
        $this->stopped = true;
        $this->playing = false;
        $this->video = new SplQueue;
        $this->audio = new SplQueue;
    }
}
