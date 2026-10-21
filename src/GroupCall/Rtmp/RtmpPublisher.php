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

namespace danog\MadelineProto\GroupCall\Rtmp;

use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Closure;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\RPCError\RateLimitError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\CallRecorder;
use danog\MadelineProto\Tgcalls\OpusPlaybackTrack;
use danog\MadelineProto\Tgcalls\VideoPlaybackTrack;
use Revolt\EventLoop;
use SplMinHeap;
use Throwable;
use Webrtc\AVCodec\Frame\FrameInterface;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrackSink;

use function Amp\delay;

/**
 * Publishes what a {@see DjLoop} plays to an RTMP livestream, as an external RTMP encoder would.
 *
 * The frames come from the same playback tracks that feed a group call (so playlists, hold files,
 * pausing and resuming work the same), from a DJ loop in RTMP mode, which only plays H.264 and AAC
 * (the codecs the RTMP ingest of Telegram accepts) and frames AAC with ADTS, see {@see FlvTags}.
 *
 * The connection is reopened if it drops; meanwhile the frames are played (and dropped), so that
 * the stream stays live. The RTMP ingest of Telegram drops a publisher that sends no media for 10
 * seconds, as when nothing is played (an empty playlist without hold files, or paused playback):
 * then the connection is only reopened once something is played again. A server that keeps closing
 * the connection while media flows, or refuses the stream, is retried with an exponential backoff
 * (the key is fetched again each time, which only changes if an admin revoked it: it's never revoked
 * here); a key that can't be fetched anymore (e.g. the admin rights were lost) stops publishing.
 *
 * @internal
 */
final class RtmpPublisher
{
    /**
     * How many frames the decoding timestamps lag the presentation ones by, to fit reordered (B)
     * frames: enough for the B-pyramids of x264, as up to two frames are displayed before the
     * reference frame decoded before them.
     */
    private const REORDER_DEPTH = 2;
    /** How far the timestamps of a track may drift from the wall clock before re-anchoring it, in milliseconds. */
    private const MAX_DRIFT_MS = 1000;
    private const MIN_RECONNECT_DELAY = 1.0;
    private const MAX_RECONNECT_DELAY = 30.0;
    /** How long a connection must stay up to reset the backoff, in seconds. */
    private const STABLE_CONNECTION = 60.0;
    /**
     * For how long nothing must have been played, in seconds, for a closed connection to count as
     * dropped for idleness: well below the 10 seconds after which the ingest of Telegram drops a
     * publisher sending no media.
     */
    private const IDLE = 5.0;

    private readonly OpusPlaybackTrack $audioTrack;
    private readonly VideoPlaybackTrack $videoTrack;
    /** The sinks of the tracks, held here as the tracks only hold them weakly. */
    private readonly MediaStreamTrackSink $audioSink;
    private readonly MediaStreamTrackSink $videoSink;

    private ?RtmpClient $client = null;
    private bool $running = true;
    private readonly DeferredCancellation $stopping;
    /** Wall clock time (milliseconds) at which the current connection started: the zero of its timestamps. */
    private float $epoch = 0.0;

    /** @var array<string, array{wall: float, timestamp: int}|null> The anchor of the timeline of each track, by kind. */
    private array $anchors = ['audio' => null, 'video' => null];
    private ?string $avcConfig = null;
    private ?string $aacConfig = null;
    private bool $sentKeyframe = false;
    /** @var list<array{pts: int, data: string, keyframe: bool}> Video frames held to compute their decoding timestamps. */
    private array $reorder = [];
    /** @var SplMinHeap<int> The presentation timestamps of the video frames not used as decoding timestamps yet. */
    private SplMinHeap $pending;
    /** How many video frames were sent since the last keyframe (included). */
    private int $sentSinceKeyframe = 0;
    private int $lastVideoTimestamp = 0;
    private bool $warnedAudio = false;
    /** The onMetaData sent on this connection, if any. */
    private ?string $metadata = null;
    /** When audio started waiting for the metadata of the first video keyframe (wall clock, seconds). */
    private ?float $awaitingVideoSince = null;
    /** When the tracks last offered a frame to publish (wall clock, seconds), connected or not. */
    private float $lastFrameAt = 0.0;
    /** @var ?DeferredFuture<null> Completed when there is something to publish again, see {@see self::wake()}. */
    private ?DeferredFuture $frameWaiter = null;

    /**
     * @param Closure(bool): array{url: string, key: string} $ingest   Get the URL and key to publish to (true to revoke the key first: never passed by the publisher, as revoking would cut off the other encoders using it).
     * @param ?Closure(Throwable): void                     $onGiveUp Called once publishing stopped for good, see {@see self::giveUp()}.
     * @param float                                         $idle     See {@see self::IDLE}.
     */
    public function __construct(
        private readonly CallControllerInterface $call,
        private readonly DjLoop $dj,
        private readonly Closure $ingest,
        private readonly ?Closure $onGiveUp = null,
        private readonly float $idle = self::IDLE,
    ) {
        $this->stopping = new DeferredCancellation;
        $this->pending = new SplMinHeap;
        $publisher = $this;
        $this->audioSink = new class($publisher) implements MediaStreamTrackSink {
            public function __construct(private readonly RtmpPublisher $publisher)
            {
            }
            public function onFrame(FrameInterface|EncodedPacket $frame): bool
            {
                return $frame instanceof EncodedPacket ? $this->publisher->onAudio($frame) : true;
            }
        };
        $this->videoSink = new class($publisher) implements MediaStreamTrackSink {
            public function __construct(private readonly RtmpPublisher $publisher)
            {
            }
            public function onFrame(FrameInterface|EncodedPacket $frame): bool
            {
                return $frame instanceof EncodedPacket ? $this->publisher->onVideo($frame) : true;
            }
        };
        $this->audioTrack = new OpusPlaybackTrack($dj, $call);
        $this->videoTrack = new VideoPlaybackTrack($dj, $call, 'RTMP');
        $this->audioTrack->setSink($this->audioSink);
        $this->videoTrack->setSink($this->videoSink);
        EventLoop::queue($this->connectionLoop(...));
    }

    /**
     * Stop publishing.
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }
        $this->running = false;
        $this->stopping->cancel();
        $this->frameWaiter = null;
        $this->audioTrack->stop();
        $this->videoTrack->stop();
        $this->flushVideo();
        $this->client?->close();
        $this->client = null;
    }

    /**
     * Whether a connection to the RTMP server is up.
     *
     * @psalm-mutation-free
     */
    public function isConnected(): bool
    {
        return $this->client !== null && !$this->client->isClosed();
    }

    /**
     * Something is about to be played: reconnect now, if the connection was dropped while nothing was.
     */
    public function wake(): void
    {
        $waiter = $this->frameWaiter;
        $this->frameWaiter = null;
        $waiter?->complete();
    }

    /**
     * Wait until something is played, see {@see self::wake()}.
     */
    private function awaitSomethingToPlay(): void
    {
        $this->frameWaiter ??= new DeferredFuture;
        $this->frameWaiter->getFuture()->await($this->stopping->getCancellation());
    }

    /**
     * The tracks offered a frame to publish.
     */
    private function onFrame(): void
    {
        $this->lastFrameAt = microtime(true);
        if ($this->frameWaiter !== null) {
            $this->wake();
        }
    }

    /**
     * Whether retrying can't help: Telegram refused to give a stream key (an RPC error that is not
     * a flood wait or an internal error, e.g. CHAT_ADMIN_REQUIRED after losing the admin rights).
     */
    private static function isDefinitive(Throwable $e): bool
    {
        if ($e instanceof RPCErrorException) {
            $code = $e->getCode();
            return $code >= 400 && $code < 500 && $code !== 420;
        }
        return false;
    }

    /**
     * Stop publishing for good, after an error that retrying can't fix.
     */
    private function giveUp(Throwable $e): void
    {
        $this->call->log("Stopped publishing to the RTMP livestream of {$this->call}: {$e->getMessage()}", Logger::ERROR);
        $this->stop();
        if ($this->onGiveUp !== null) {
            ($this->onGiveUp)($e);
        }
    }

    private function connectionLoop(): void
    {
        $delay = self::MIN_RECONNECT_DELAY;
        while ($this->running) {
            try {
                // Fetched every time (never revoking it): the same key unless an admin revoked it meanwhile.
                ['url' => $url, 'key' => $key] = ($this->ingest)(false);
                $client = RtmpClient::publish($url, $key, $this->stopping->getCancellation());
                if (!$this->running) {
                    $client->close();
                    return;
                }
                // Each connection starts a fresh timeline, with its own sequence headers.
                $this->epoch = microtime(true) * 1000.0;
                $this->anchors = ['audio' => null, 'video' => null];
                $this->avcConfig = null;
                $this->aacConfig = null;
                $this->sentKeyframe = false;
                $this->reorder = [];
                $this->pending = new SplMinHeap;
                $this->sentSinceKeyframe = 0;
                $this->lastVideoTimestamp = 0;
                // Sent before the first media, once the dimensions of the video are known.
                $this->metadata = null;
                $this->awaitingVideoSince = null;
                $this->client = $client;
                $this->call->log("Publishing to the RTMP livestream of {$this->call}", Logger::NOTICE);
                $connectedAt = microtime(true);
                $client->awaitClose($this->stopping->getCancellation());
                if (!$this->running) {
                    return;
                }
                $this->client = null;
                $reason = ($client->getError()?->getMessage() ?? 'by the server').' ('.$client->describe().')';
                if (microtime(true) - $this->lastFrameAt >= $this->idle) {
                    // The ingest drops a publisher that sends no media: reconnecting now would only get
                    // dropped again 10 seconds later, in a loop, until something is played.
                    $this->call->log("The RTMP connection of {$this->call} was closed while nothing was played: $reason, reconnecting once something is played...", Logger::NOTICE);
                    $delay = self::MIN_RECONNECT_DELAY;
                    $this->awaitSomethingToPlay();
                    continue;
                }
                if (microtime(true) - $connectedAt >= self::STABLE_CONNECTION) {
                    $this->call->log("The RTMP connection of {$this->call} was closed: $reason, reconnecting...", Logger::WARNING);
                    $delay = self::MIN_RECONNECT_DELAY;
                    continue;
                }
                // Closed soon after connecting, while media flows: back off instead of reconnecting in a loop.
                $this->call->log("The RTMP connection of {$this->call} was closed: $reason, reconnecting in {$delay} seconds...", Logger::WARNING);
            } catch (Throwable $e) {
                // Including the cancellation of what we were waiting for, when stopping.
                if (!$this->running) {
                    return;
                }
                if (self::isDefinitive($e)) {
                    $this->giveUp($e);
                    return;
                }
                if ($e instanceof RateLimitError) {
                    // A flood wait longer than the flood timeout of the call: wait as long as the server asks.
                    $delay = max($delay, (float) $e->getWaitTime());
                }
                // Including a refused stream (RtmpRefusedException): the key Telegram gives is the same until an
                // admin revokes it, so a refusal of it isn't proof that it can't work (e.g. the ingest still
                // holding our previous session after a network drop), and the key is fetched again each time.
                $this->call->log("Could not publish to the RTMP livestream of {$this->call}: {$e->getMessage()}, retrying in {$delay} seconds...", Logger::ERROR);
            } finally {
                $this->client = null;
            }
            try {
                delay($delay, cancellation: $this->stopping->getCancellation());
            } catch (CancelledException) {
                return;
            }
            $delay = min($delay * 2, self::MAX_RECONNECT_DELAY);
        }
    }

    /**
     * The timestamp of a frame on the timeline of the connection, in milliseconds.
     *
     * Each track is paced by the clock of the file it plays, from when its first frame was played: its
     * timestamps are anchored to the wall clock, and anchored again when they drift away from it
     * (after a pause, or a gap between files).
     *
     * @param 'audio'|'video' $kind
     */
    private function timestamp(string $kind, int $timestamp, int $clockRate): int
    {
        $now = microtime(true) * 1000.0;
        $anchor = $this->anchors[$kind];
        if ($anchor !== null) {
            $media = $anchor['wall'] + ($timestamp - $anchor['timestamp']) * 1000.0 / $clockRate;
            if (abs($media - $now) <= self::MAX_DRIFT_MS) {
                return max(0, (int) round($media - $this->epoch));
            }
        }
        $this->anchors[$kind] = ['wall' => $now, 'timestamp' => $timestamp];
        return max(0, (int) round($now - $this->epoch));
    }

    /**
     * @internal
     */
    public function onAudio(EncodedPacket $packet): bool
    {
        if (!$this->running) {
            return false;
        }
        $this->onFrame();
        $client = $this->client;
        if ($client === null || $client->isClosed()) {
            return true;
        }
        $adts = FlvTags::parseAdts($packet->getData());
        if ($adts === null) {
            if (!$this->warnedAudio) {
                $this->warnedAudio = true;
                $this->call->log("Dropping audio that is not AAC in {$this->call}: an RTMP livestream can only carry AAC audio", Logger::ERROR);
            }
            return true;
        }
        [$config, $raw] = $adts;
        try {
            if ($this->metadata === null) {
                // The ingest sizes the livestream from the onMetaData (16:9 without dimensions), so it
                // waits for the first keyframe of a file with video, for at most a second.
                $this->awaitingVideoSince ??= microtime(true);
                if ($this->dj->getVideoCodec() !== null && microtime(true) - $this->awaitingVideoSince < 1) {
                    return true;
                }
                $this->sendMetadata($client, null, null);
            }
            $timestamp = $this->timestamp('audio', $packet->getTimestamp(), DjLoop::AUDIO_CLOCK_RATE);
            if ($config !== $this->aacConfig) {
                $client->sendMedia(RtmpClient::TYPE_AUDIO, $timestamp, FlvTags::aacSequenceHeader($config));
                $this->aacConfig = $config;
            }
            $client->sendMedia(RtmpClient::TYPE_AUDIO, $timestamp, FlvTags::aacFrame($raw));
        } catch (Throwable $e) {
            // The connection loop reconnects.
            $this->call->log("Could not send audio to the RTMP livestream of {$this->call}: {$e->getMessage()}", Logger::VERBOSE);
        }
        return true;
    }

    /**
     * @internal
     */
    public function onVideo(EncodedPacket $packet): bool
    {
        if (!$this->running) {
            return false;
        }
        $this->onFrame();
        $client = $this->client;
        if ($client === null || $client->isClosed()) {
            return true;
        }
        $annexB = $packet->getData();
        [$nalUnits, $keyframe] = CallRecorder::transformVideo($annexB, 'V_MPEG4/ISO/AVC');
        $keyframe = $keyframe || $packet->isKeyframe();
        if (!$this->sentKeyframe && !$keyframe) {
            // A decoder can only start on a keyframe.
            return true;
        }
        try {
            if ($keyframe) {
                $this->flushVideo();
                [, $width, $height, $avcc] = CallRecorder::describeVideo($annexB);
                if ($avcc !== '' && $avcc !== $this->avcConfig) {
                    $this->sendMetadata($client, $width, $height);
                    $client->sendMedia(RtmpClient::TYPE_VIDEO, $this->timestamp('video', $packet->getTimestamp(), DjLoop::VIDEO_CLOCK_RATE), FlvTags::avcSequenceHeader($avcc));
                    $this->avcConfig = $avcc;
                }
                if ($this->avcConfig === null) {
                    return true;
                }
                $this->sentKeyframe = true;
            }
            $pts = $this->timestamp('video', $packet->getTimestamp(), DjLoop::VIDEO_CLOCK_RATE);
            $this->reorder[] = ['pts' => $pts, 'data' => $nalUnits, 'keyframe' => $keyframe];
            $this->pending->insert($pts);
            if (\count($this->reorder) > self::REORDER_DEPTH) {
                $this->sendVideo($client);
            }
        } catch (Throwable $e) {
            $this->call->log("Could not send video to the RTMP livestream of {$this->call}: {$e->getMessage()}", Logger::VERBOSE);
        }
        return true;
    }

    /**
     * Send the onMetaData of the stream, if it changed: the dimensions of its video, if known.
     */
    private function sendMetadata(RtmpClient $client, ?int $width, ?int $height): void
    {
        $metadata = FlvTags::metadata($width, $height, true, true);
        if ($metadata !== $this->metadata) {
            if ($width !== null && $height !== null && $width * 9 !== $height * 16) {
                // Whatever the onMetaData and the SPS say, sample aspect ratio included.
                $this->call->log(
                    "Telegram scales RTMP livestreams to 1280x720, without keeping the aspect ratio: the {$width}x{$height} video played in {$this->call} looks stretched. "
                    ."Pad it to 16:9 first, e.g. with ffmpeg -i input -vf 'scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2' -c:v libx264 -c:a aac -movflags +faststart output.mp4",
                    Logger::WARNING
                );
            }
            $client->sendMetadata($metadata);
            $this->metadata = $metadata;
        }
    }

    /**
     * Send the oldest held video frame.
     *
     * Frames come in decoding order with their presentation timestamps, while RTMP wants them with
     * their decoding timestamps (and the difference as the composition time). The decoding
     * timestamps are the presentation ones, sorted and lagging by {@see self::REORDER_DEPTH} frames,
     * as a frame displayed before a reference frame is decoded after it: frame `i` (from the last
     * keyframe) is decoded at the `i - REORDER_DEPTH`-th presentation timestamp, the first ones
     * just before the first presentation timestamp.
     */
    private function sendVideo(RtmpClient $client): void
    {
        $frame = array_shift($this->reorder);
        if ($frame === null) {
            return;
        }
        if ($this->sentSinceKeyframe < self::REORDER_DEPTH) {
            $dts = $this->pending->top() - (self::REORDER_DEPTH - $this->sentSinceKeyframe);
        } else {
            $dts = $this->pending->extract();
        }
        $this->sentSinceKeyframe++;
        $dts = max($this->lastVideoTimestamp, $dts);
        $this->lastVideoTimestamp = $dts;
        // Negative (a deeper reordering than expected) is allowed: the composition time is signed.
        $client->sendMedia(RtmpClient::TYPE_VIDEO, $dts, FlvTags::avcFrame($frame['data'], $frame['keyframe'], $frame['pts'] - $dts));
    }

    /**
     * Send every held video frame: before a keyframe, which nothing after it references across.
     */
    private function flushVideo(): void
    {
        $client = $this->client;
        if ($client === null || $client->isClosed()) {
            $this->reorder = [];
            return;
        }
        try {
            while ($this->reorder !== []) {
                $this->sendVideo($client);
            }
        } catch (Throwable) {
            $this->reorder = [];
        }
        // What is left are the presentation timestamps of the last frames: not needed anymore.
        $this->pending = new SplMinHeap;
        $this->sentSinceKeyframe = 0;
    }
}
