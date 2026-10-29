<?php

declare(strict_types=1);

/**
 * Internal loop trait.
 *
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

namespace danog\MadelineProto\Loop\VoIP;

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use AssertionError;
use danog\MadelineProto\Codecs\AacTranscoder;
use danog\MadelineProto\GroupCall\Rtmp\FlvTags;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIPLoop;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\Mp4;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RestoresDeclaredProperties;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\H264Framing;
use danog\MadelineProto\Tgcalls\VideoCodecObserver;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use SplQueue;
use Throwable;
use Webrtc\Codecs\Codec;

use function Amp\async;

/**
 * The single disc jockey of a call: it plays a playlist of files, streaming both their audio and
 * their video.
 *
 * There is exactly one playback mechanism, and it is the same for audio and for video; only the
 * demuxer differs per file:
 *
 * - A WebM/Matroska file (it begins with the EBML header) is demuxed by {@see Matroska}: its already
 *   encoded video and OPUS audio frames are handed to the RTP senders untouched, keeping the
 *   timestamps they had in the file, which is what keeps the two in sync. So is an MP4 file, by {@see Mp4}.
 *   A call that can only carry OPUS gets the AAC audio of a file without OPUS audio transcoded, by
 *   {@see AacTranscoder}: with ffmpeg and libopus if available, in pure PHP otherwise. An RTMP
 *   livestream carries H.264 and AAC, as they are.
 * - An OGG OPUS file is demuxed by {@see Ogg}: its packets are handed to the tracks untouched, each
 *   paced by its own duration. Anything else is converted to OGG OPUS on the fly by ffmpeg.
 *   The legacy libtgvoip engine can only send 60ms packets, so for it ({@see self::setFixedOggGrid()})
 *   only MadelineProto's own OGG OPUS files are played as-is, and the others are converted too.
 *
 * Nothing is decoded or re-encoded for pre-encoded input (but AAC, in calls), so playback needs no codec
 * library (and therefore no FFI). Frames are **streamed**, not buffered whole: the reader reads only as far ahead
 * of playback as configured ({@see \danog\MadelineProto\Settings\VoIP::setPlaybackLookahead()}, just
 * the next frame by default) and blocks until the tracks drain it, so memory stays bounded however
 * long the file is. The frames read ahead are not saved: playback resumes exactly from the first one
 * that was not played across a serialize → destruct → unserialize cycle (for a seekable source — a
 * raw stream or an on-the-fly conversion replays the current item instead).
 *
 * The whole playlist API — {@see self::play()}, {@see self::skip()}, {@see self::playOnHold()},
 * pausing, {@see self::getCurrent()} — applies to every file regardless of what media it carries.
 *
 * @internal
 */
final class DjLoop extends VoIPLoop
{
    use RestoresDeclaredProperties;
    /** VP8 and every other video codec on the wire use a 90kHz RTP clock. */
    public const VIDEO_CLOCK_RATE = 90000;
    /** OPUS always uses a 48kHz RTP clock. */
    public const AUDIO_CLOCK_RATE = 48000;
    /**
     * How far ahead of the look-ahead the reader may go to find the next frame of a track that has
     * none queued, see {@see self::throttle()}: muxers interleave the frames of the tracks of a file by
     * timestamp, so that's enough for any file but badly interleaved ones.
     */
    private const MAX_INTERLEAVING_MS = 1000;

    /**
     * The video codecs we can transmit, as `Matroska CodecID => SDP encoding name`.
     *
     * This is exactly the set a Telegram group call carries; see {@see \danog\MadelineProto\Tgcalls\GroupSdp}
     * for the payload types the SFU pairs them with.
     */
    public const VIDEO_CODECS = [
        'V_VP8' => 'VP8',
        'V_VP9' => 'VP9',
        'V_AV1' => 'AV1',
        'V_MPEG4/ISO/AVC' => 'H264',
        'V_MPEGH/ISO/HEVC' => 'H265',
    ];

    /** The audio codecs we can transmit, as `Matroska CodecID => SDP encoding name`. */
    public const AUDIO_CODECS = ['A_OPUS' => 'opus'];

    /** The video codecs of an RTMP livestream: the RTMP ingest of Telegram only accepts H.264. */
    public const RTMP_VIDEO_CODECS = ['V_MPEG4/ISO/AVC' => 'H264'];
    /** The audio codecs of an RTMP livestream: the RTMP ingest of Telegram only accepts AAC (any Matroska CodecID of it). */
    public const RTMP_AUDIO_CODECS = [
        'A_AAC' => 'aac',
        'A_AAC/MPEG2/MAIN' => 'aac',
        'A_AAC/MPEG2/LC' => 'aac',
        'A_AAC/MPEG2/LC/SBR' => 'aac',
        'A_AAC/MPEG2/SSR' => 'aac',
        'A_AAC/MPEG4/MAIN' => 'aac',
        'A_AAC/MPEG4/LC' => 'aac',
        'A_AAC/MPEG4/LC/SBR' => 'aac',
        'A_AAC/MPEG4/SSR' => 'aac',
        'A_AAC/MPEG4/LTP' => 'aac',
    ];

    /**
     * How a Matroska/WebM file begins: the EBML header, or — for a headerless file, as some muxers
     * and {@see Matroska} itself tolerate — the Segment element straight away.
     */
    private const MATROSKA_MAGICS = ["\x1A\x45\xDF\xA3", "\x18\x53\x80\x67"];
    /** OGG capture pattern: how an OGG file begins. */
    private const OGG_MAGIC = 'OggS';

    /* -------------------------------------------------------------------- *
     *  Playlist.
     * -------------------------------------------------------------------- */

    /** @var list<LocalFile|RemoteUrl|ReadableStream|StreamSnapshot> */
    private array $inputFiles = [];
    /** @var array<LocalFile|RemoteUrl|ReadableStream|StreamSnapshot> */
    private array $holdFiles = [];
    private int $holdIndex = 0;
    private bool $playingHold = false;
    private bool $pause = false;

    /* -------------------------------------------------------------------- *
     *  Bounded look-ahead queues, drained by the tracks.
     *
     *  They are not serialized: the current file is resumed from the position of its first frame
     *  that was not played yet, instead. Every queued frame carries its `position` in the file
     *  (see {@see self::trackResume()}), null if the file can't be resumed.
     * -------------------------------------------------------------------- */

    /** @var SplQueue<array{data: string, position: ?list{int, int}, file: int}> OPUS packets of an OGG file. */
    private SplQueue $oggQueue;
    /** Total duration of the packets in {@see self::$oggQueue}, in 48kHz samples. */
    private int $oggQueueSamples = 0;
    /** Whether OGG files must be MadelineProto's own 60ms ones, others being converted (for the legacy libtgvoip engine). */
    private bool $fixedOggGrid = false;
    /**
     * @var SplQueue<array{data: string, timestamp: int, position: ?list{int, int}, file: int}> OPUS frames of a WebM file (48kHz ts).
     *
     * The `file` of a queued frame is the {@see self::$generation} of the file it was read from: the
     * tracks hold a frame until its presentation time, so the next file may already be queued when
     * they get to it, and they must re-anchor their clocks right where it begins.
     */
    private SplQueue $webmAudioQueue;
    /** @var SplQueue<array{data: string, timestamp: int, keyframe: bool, position: ?list{int, int}, file: int}> Video frames (90kHz ts). */
    private SplQueue $videoQueue;

    /** Completed by a track when it drains a queue, to release the reader from back-pressure. */
    private ?DeferredFuture $drainDeferred = null;
    /** Awaited by {@see self::pullPacket()} and {@see self::awaitFrame()} until a frame is queued. */
    private ?DeferredFuture $packetDeferred = null;
    /** Whether the current WebM file has audio for the audio track, see {@see self::isStarving()}. */
    private bool $currentHasAudio = false;
    /** Whether the current WebM file has video for the video track, see {@see self::isStarving()}. */
    private bool $currentHasVideo = false;

    /* -------------------------------------------------------------------- *
     *  Current file + its byte-offset resume state.
     * -------------------------------------------------------------------- */

    private LocalFile|RemoteUrl|ReadableStream|StreamSnapshot|null $currentFile = null;
    private LocalFile|RemoteUrl|string|null $currentDesc = null;
    /** 'ogg', 'matroska' or 'mp4', or null when nothing is playing. */
    private ?string $currentKind = null;
    /** Whether the current file can be resumed from a byte offset (a seekable, non-converted source). */
    private bool $currentResumable = false;

    /** Byte offset of the container unit (OGG page / MKV cluster) to resume the current file from. */
    private int $resumeOffset = 0;
    /** How many frames of the resume unit were already produced, and so must be skipped on resume. */
    private int $resumeSkip = 0;
    /** Byte offset of the unit the last produced frame came from, used to keep {@see self::$resumeSkip}. */
    private int $resumeUnitOffset = -1;
    /**
     * Position of the last played audio and video frame of the files whose frames are queued, by
     * {@see self::$generation}, see {@see self::trackResume()}.
     *
     * The audio and video frames of a WebM file are interleaved, but played separately: when resuming
     * from the first frame that was not played, the frames of the other kind that were already played
     * are skipped, see {@see self::$resumePlayed}.
     *
     * @var array<int, array{audio?: list{int, int}, video?: list{int, int}}>
     */
    private array $playedPositions = [];
    /**
     * Position of the last played audio and video frame of the file being resumed.
     *
     * @var array{audio?: list{int, int}, video?: list{int, int}}
     */
    private array $resumePlayed = [];
    /**
     * The files read before the current one, oldest first, while some of their frames may still be
     * queued, to resume them from the first one that was not played: see {@see self::fileState()}.
     *
     * @var list<array<string, mixed>>
     */
    private array $previous = [];
    /** The Matroska track list, needed to resume a WebM file past its (once-only) header. */
    private array $resumeTracks = [];
    /** The Matroska timestamp scale, needed to resume a WebM file. */
    private int $resumeTimestampScale = 0;

    /** Highest audio/video timestamp buffered so far, in milliseconds, for back-pressure. */
    private int $bufferedUntilMs = 0;
    /** How far playback has progressed, in milliseconds, reported by the tracks. */
    private int $playbackMs = 0;
    /** Whether the current file's demuxer has been fully read. */
    private bool $finishedCurrent = true;

    /* -------------------------------------------------------------------- *
     *  Video codec negotiation.
     * -------------------------------------------------------------------- */

    private ?VideoCodecObserver $videoObserver = null;
    private ?string $videoCodec = null;
    /** SDP fmtp parameters (profile/level/etc.) of the current video file, derived from its bitstream. */
    private array $videoParameters = [];
    /** Whether {@see self::$videoParameters} is still a tentative value awaiting the first keyframe. */
    private bool $videoProfileFromFrame = false;
    private ?H264Framing $framing = null;
    /**
     * The track number of the AAC audio of the current file: played as it is in RTMP mode, transcoded to OPUS
     * otherwise (if the file has no OPUS audio).
     */
    private ?int $aacTrack = null;
    /** The AudioSpecificConfig of {@see self::$aacTrack}, for the ADTS headers of its frames. */
    private ?string $aacConfig = null;
    /** Transcodes {@see self::$aacTrack} to OPUS, outside of RTMP mode: not saved, recreated when the file is resumed. */
    private ?AacTranscoder $aacTranscoder = null;
    /** @var ?list{int, int} The resume position of the last AAC frame transcoded, for the packets it completes. */
    private ?array $aacPosition = null;
    /** Whether a video codec was announced and still owes the observer a stop notification. */
    private bool $webmVideoAnnounced = false;

    /* -------------------------------------------------------------------- *
     *  Transient reader control (never serialized).
     * -------------------------------------------------------------------- */

    private bool $readerRunning = false;
    /** Bumped to make the running reader abandon the current file (skip/stop/discard), and for every file. */
    private int $generation = 0;
    private ?DeferredCancellation $readerCancel = null;

    /**
     * @param bool $videoOnly When true, the audio of played files is dropped and only their video is
     *                        queued — used for a presentation/screencast stream, which carries no audio.
     * @param bool $rtmp      When true, the files are played for an RTMP livestream (see
     *                        {@see \danog\MadelineProto\GroupCall\Rtmp\RtmpPublisher}): only H.264 and AAC,
     *                        whose frames are queued with an ADTS header, in MP4 or Matroska files.
     */
    public function __construct(CallControllerInterface $instance, private bool $videoOnly = false, private bool $rtmp = false)
    {
        parent::__construct($instance);
        $this->oggQueue = new SplQueue;
        $this->webmAudioQueue = new SplQueue;
        $this->videoQueue = new SplQueue;
    }

    /**
     * Register the observer notified when the outgoing video codec changes or video playback stops.
     *
     * Set by the WebRTC engine ({@see \danog\MadelineProto\Tgcalls\Controller} or
     * {@see \danog\MadelineProto\Tgcalls\GroupConnection}) once it exists, since this loop is created
     * before it.
     *
     * @psalm-external-mutation-free
     */
    public function setVideoCodecObserver(VideoCodecObserver $observer): void
    {
        $this->videoObserver = $observer;
        // The reader is started by play() and may open the file and detect its video codec before
        // the connection — and hence this observer — exists. When that happens the onVideoCodec
        // announcement in selectTracks() is dropped (the observer was still null), the codec is
        // never preferred, and the peer decodes our pre-encoded frames as whatever codec sits first
        // in the default table (VP8). Replay the announcement here so the codec is preferred in the
        // very first offer. It is queued so it runs after the connection has finished constructing
        // its transceivers, and before the initial offer it queues itself.
        if ($this->videoCodec !== null) {
            $codec = $this->videoCodec;
            $parameters = $this->videoParameters;
            EventLoop::queue(static fn () => $observer->onVideoCodec($codec, $parameters));
        }
    }

    /**
     * The state needed to resume the current file.
     *
     * @return array<string, mixed>
     */
    private function fileState(): array
    {
        return [
            'generation' => $this->generation,
            'file' => $this->currentFile,
            'desc' => $this->currentDesc,
            'kind' => $this->currentKind,
            'hold' => $this->playingHold,
            'resumeOffset' => $this->resumeOffset,
            'resumeSkip' => $this->resumeSkip,
            'resumeUnitOffset' => $this->resumeUnitOffset,
            'resumeTracks' => $this->resumeTracks,
            'resumeTimestampScale' => $this->resumeTimestampScale,
            'videoCodec' => $this->videoCodec,
            'videoParameters' => $this->videoParameters,
            'videoProfileFromFrame' => $this->videoProfileFromFrame,
            'framing' => $this->framing,
            'aacTrack' => $this->aacTrack,
            'aacConfig' => $this->aacConfig,
        ];
    }

    public function __serialize(): array
    {
        // The queued frames are not saved: a seekable, non-converted file is resumed from the first of its
        // frames that was not played. A raw stream or an on-the-fly conversion replays its item from the
        // start (files) or is dropped (streams).
        $inputFiles = $this->inputFiles;
        $holdIndex = $this->holdIndex;
        $state = null;
        $position = null;
        foreach ($this->previous as $k => $previous) {
            if (($position = $this->firstUnplayedPosition($previous['generation'])) !== null) {
                // The end of a previous file was not played yet: resume it, followed by the files after
                // it, whose frames come after its own.
                $state = $previous;
                $next = array_column(\array_slice($this->previous, $k + 1), 'file');
                if ($this->currentFile !== null) {
                    if ($this->playingHold) {
                        $holdIndex--;
                    } else {
                        $next[] = $this->currentFile;
                    }
                }
                array_unshift($inputFiles, ...$next);
                break;
            }
        }
        if ($state === null && $this->currentFile !== null && !$this->finishedCurrent) {
            if ($this->currentResumable) {
                $state = $this->fileState();
                $position = $this->firstUnplayedPosition($this->generation);
            } elseif (!$this->currentFile instanceof ReadableStream) {
                array_unshift($inputFiles, $this->currentFile);
            }
        }
        // Without a position, every frame read so far was played: resume right after the last one.
        if ($state !== null && $position !== null) {
            [$state['resumeOffset'], $state['resumeSkip']] = $position;
            $state['resumeUnitOffset'] = $position[0];
        }
        return [
            'instance' => $this->instance,
            'pause' => $this->pause,
            'inputFiles' => array_values(array_filter($inputFiles, static fn ($v) => !$v instanceof ReadableStream)),
            'holdFiles' => array_values(array_filter($this->holdFiles, static fn ($v) => !$v instanceof ReadableStream)),
            'holdIndex' => $holdIndex,
            'playingHold' => $state['hold'] ?? false,
            'fixedOggGrid' => $this->fixedOggGrid,
            'currentFile' => $state['file'] ?? null,
            'currentDesc' => $state['desc'] ?? null,
            'currentKind' => $state['kind'] ?? null,
            'currentResumable' => $state !== null,
            'finishedCurrent' => $state === null,
            'resumeOffset' => $state['resumeOffset'] ?? 0,
            'resumeSkip' => $state['resumeSkip'] ?? 0,
            'resumeUnitOffset' => $state['resumeUnitOffset'] ?? -1,
            'resumePlayed' => $state !== null ? $this->playedPositions[$state['generation']] ?? [] : [],
            'resumeTracks' => $state['resumeTracks'] ?? [],
            'resumeTimestampScale' => $state['resumeTimestampScale'] ?? 0,
            // Nothing is buffered once resumed: playback restarts from the first frame read again.
            'bufferedUntilMs' => 0,
            'playbackMs' => 0,
            'videoObserver' => $this->videoObserver,
            'videoCodec' => $state !== null ? $state['videoCodec'] : $this->videoCodec,
            'videoParameters' => $state !== null ? $state['videoParameters'] : $this->videoParameters,
            'videoProfileFromFrame' => $state !== null ? $state['videoProfileFromFrame'] : $this->videoProfileFromFrame,
            'videoOnly' => $this->videoOnly,
            'framing' => $state !== null ? $state['framing'] : $this->framing,
            'aacTrack' => $state !== null ? $state['aacTrack'] : $this->aacTrack,
            'aacConfig' => $state !== null ? $state['aacConfig'] : $this->aacConfig,
            'rtmp' => $this->rtmp,
            'webmVideoAnnounced' => $this->webmVideoAnnounced,
            // Kept (not reset) so a file opened after a restart never reuses the ID of one whose
            // frames the tracks still remember: see the `file` of the queued frames.
            'generation' => $this->generation,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->restoreDeclaredProperties($data);
        // Older versions saved the queued frames: they are dropped (along with at most a couple of seconds of playback).
        $this->oggQueue = new SplQueue;
        $this->oggQueueSamples = 0;
        $this->webmAudioQueue = new SplQueue;
        $this->videoQueue = new SplQueue;
        // Not saved by older versions.
        $this->videoOnly ??= false;
        $this->rtmp ??= false;
        // Do NOT restart the reader here: its loop reads the call's state, which is only restored once
        // the whole call graph has finished deserializing. Starting it now could run it
        // mid-deserialization (if a nested resume suspends the fiber) and dereference not-yet-restored
        // state. The call's deserializer calls {@see self::resume()} once the graph is whole.
        $this->readerRunning = false;
    }

    /**
     * Restart the demuxer/reader task after a serialize/unserialize cycle, resuming the current file
     * (from its byte offset) or the playlist where it left off. Called by the call's deserializer once
     * the whole call graph is restored; idempotent via the readerRunning guard. (Named to avoid the
     * base Loop::resume().).
     */
    public function resumeReader(): void
    {
        $this->startReader();
    }

    public function discard(): void
    {
        $this->generation++;
        $this->readerCancel?->cancel();
        $this->oggQueue = new SplQueue;
        $this->oggQueueSamples = 0;
        $this->webmAudioQueue = new SplQueue;
        $this->videoQueue = new SplQueue;
        $this->previous = [];
        $this->packetDeferred?->complete(false);
        $this->packetDeferred = null;
    }

    #[\Override]
    protected function loop(): ?float
    {
        // The playlist is driven by a dedicated reader task (see startReader()); this loop only
        // needs to exist for the base class and to stop with the call.
        if ($this->instance->isCallEnded()) {
            return self::STOP;
        }
        return self::PAUSE;
    }

    /* ==================================================================== *
     *  Playlist API.
     * ==================================================================== */

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        $this->inputFiles[] = self::snapshot($file);
        if ($this->playingHold) {
            $this->playingHold = false;
            $this->skip();
        }
        $this->startReader();
    }

    /**
     * Skip to the next file in the playlist.
     */
    public function skip(): void
    {
        $this->generation++;
        $this->readerCancel?->cancel();
        $this->finishedCurrent = true;
        $this->startReader();
    }

    /**
     * Stop playing all files, clear the main and the hold playlist, and stop any video.
     */
    public function stopPlaying(): void
    {
        $this->inputFiles = [];
        $this->holdFiles = [];
        $this->playingHold = false;
        $this->previous = [];
        $this->skip();
        $this->announceVideoStopped();
    }

    /**
     * @psalm-external-mutation-free
     */
    public function pausePlaying(): void
    {
        $this->pause = true;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function resumePlaying(): void
    {
        $this->pause = false;
    }

    /**
     * Whether playback is paused.
     *
     * @psalm-mutation-free
     */
    public function isAudioPaused(): bool
    {
        return $this->pause;
    }

    /**
     * Get info about what is currently being played: a description string for a stream, otherwise
     * the related {@see LocalFile} or {@see RemoteUrl}.
     *
     * @psalm-mutation-free
     */
    public function getCurrent(): LocalFile|RemoteUrl|string|null
    {
        return $this->currentDesc;
    }

    /**
     * Files to play on hold: they replace the hold file being played (if any) right away.
     */
    public function playOnHold(LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->holdFiles = array_map(self::snapshot(...), $files);
        $this->holdIndex = 0;
        if ($this->playingHold) {
            $this->playingHold = false;
            $this->skip();
            return;
        }
        $this->startReader();
    }

    /* ==================================================================== *
     *  Track-facing API.
     * ==================================================================== */

    /**
     * Only play MadelineProto's own OGG OPUS files as-is, converting the others: for the legacy
     * libtgvoip engine, which can only send 60ms packets.
     */
    public function setFixedOggGrid(): void
    {
        $this->fixedOggGrid = true;
    }

    /**
     * Pull the next OPUS packet of an OGG file, waiting for one if the playlist is not exhausted.
     */
    public function pullPacket(): ?string
    {
        while (true) {
            $packet = $this->tryPullPacket();
            if ($packet !== null) {
                return $packet;
            }
            if ($this->instance->isCallEnded() || !$this->hasPendingOggWork()) {
                return null;
            }
            $this->packetDeferred ??= new DeferredFuture;
            if (!$this->packetDeferred->getFuture()->await()) {
                return null;
            }
        }
    }

    /**
     * Wait until a frame is queued, or for at most $timeout seconds: for a track whose queue ran dry.
     */
    public function awaitFrame(float $timeout): void
    {
        $this->packetDeferred ??= new DeferredFuture;
        $future = $this->packetDeferred->getFuture();
        $timeoutCancellation = new DeferredCancellation;
        $timer = EventLoop::delay($timeout, static fn () => $timeoutCancellation->cancel());
        try {
            $future->await($timeoutCancellation->getCancellation());
        } catch (CancelledException) {
        } finally {
            EventLoop::cancel($timer);
        }
    }

    /**
     * Pull the next OPUS packet of an OGG file if one is immediately available.
     *
     * Used by the WebRTC playback track, which is polled from a callback that must never suspend.
     */
    public function tryPullPacket(): ?string
    {
        if ($this->pause || $this->oggQueue->isEmpty()) {
            return null;
        }
        /** @var array{data: string, position: ?list{int, int}, file: int} $packet */
        $packet = $this->oggQueue->dequeue();
        $this->oggQueueSamples -= Ogg::packetDuration($packet['data']);
        $this->releaseReader();
        return $packet['data'];
    }

    public function hasWebmAudio(): bool
    {
        return !$this->webmAudioQueue->isEmpty();
    }

    /**
     * Pull the next OPUS frame of the WebM file being played, or null if none is buffered.
     *
     * @return array{data: string, timestamp: int, file: int}|null
     */
    public function pullWebmAudio(): ?array
    {
        if ($this->webmAudioQueue->isEmpty()) {
            return null;
        }
        /** @var array{data: string, timestamp: int, position: ?list{int, int}, file: int} $frame */
        $frame = $this->webmAudioQueue->dequeue();
        $this->markPlayed('audio', $frame);
        $this->setPlaybackPosition((int) ($frame['timestamp'] * 1000 / self::AUDIO_CLOCK_RATE), $frame['file']);
        $this->releaseReader();
        return $frame;
    }

    /**
     * @return array{data: string, timestamp: int, keyframe: bool, file: int}|null
     */
    public function pullVideo(): ?array
    {
        if ($this->videoQueue->isEmpty()) {
            return null;
        }
        /** @var array{data: string, timestamp: int, keyframe: bool, position: ?list{int, int}, file: int} $frame */
        $frame = $this->videoQueue->dequeue();
        $this->markPlayed('video', $frame);
        $this->releaseReader();
        return $frame;
    }

    public function hasVideo(): bool
    {
        return !$this->videoQueue->isEmpty();
    }

    /**
     * Record that a frame of the current file was played.
     *
     * @param 'audio'|'video' $kind
     * @param array{position: ?list{int, int}, file: int, ...} $frame
     */
    private function markPlayed(string $kind, array $frame): void
    {
        if ($frame['position'] !== null) {
            $this->playedPositions[$frame['file']][$kind] = $frame['position'];
        }
    }

    /**
     * The position of the first queued frame of a file, i.e. the first one that was not played yet.
     *
     * @param int $generation The {@see self::$generation} of the file.
     *
     * @return ?list{int, int}
     */
    private function firstUnplayedPosition(int $generation): ?array
    {
        $first = null;
        foreach ([$this->oggQueue, $this->webmAudioQueue, $this->videoQueue] as $queue) {
            foreach ($queue as $frame) {
                if ($frame['position'] !== null && $frame['file'] === $generation) {
                    if ($first === null || $frame['position'] < $first) {
                        $first = $frame['position'];
                    }
                    // The rest of the queue comes after it.
                    break;
                }
            }
        }
        return $first;
    }

    /**
     * Whether a WebM file is currently being played, even if a queue momentarily ran dry because the
     * reader is behind.
     */
    public function isWebmPlaying(): bool
    {
        return $this->playsFrames() && !$this->isExhausted();
    }

    /**
     * Whether the WebM file was fully read and both of its queues are drained.
     *
     * Firing the observer's stop notification exactly once here keeps the video-stop plumbing in the
     * one place that knows a file has truly finished. Not when another file follows, though: the
     * stop would make the other participants drop our video, only to pick it up again (or not) a
     * moment later; the next file announces its own video, or the lack of it, when it starts.
     */
    public function isExhausted(): bool
    {
        $exhausted = !$this->playsFrames()
            || ($this->finishedCurrent && $this->webmAudioQueue->isEmpty() && $this->videoQueue->isEmpty());
        if ($exhausted && $this->webmVideoAnnounced && $this->inputFiles === [] && $this->holdFiles === []) {
            $this->announceVideoStopped();
        }
        return $exhausted;
    }

    /**
     * Report how far playback has progressed, in milliseconds.
     *
     * @param ?int $file The `file` of the frame that was played: the playback of the frames of a
     *                   previous file doesn't count for the current one.
     */
    public function setPlaybackPosition(int $milliseconds, ?int $file = null): void
    {
        if ($file === null || $file === $this->generation) {
            $this->playbackMs = max($this->playbackMs, $milliseconds);
        }
        $this->releaseReader();
    }

    /**
     * The SDP encoding name of the video we are currently transmitting, if any.
     */
    public function getVideoCodec(): ?string
    {
        return $this->videoCodec;
    }

    /**
     * The SDP fmtp parameters (profile/level/tier/…) of the current video, derived from its bitstream.
     *
     * @return array<string, string>
     */
    public function getVideoParameters(): array
    {
        return $this->videoParameters;
    }

    /* ==================================================================== *
     *  Reader: streams the current file into the bounded queues.
     * ==================================================================== */

    private function startReader(): void
    {
        if ($this->readerRunning) {
            return;
        }
        $this->readerRunning = true;
        // The call-ended check lives in the (deferred) reader loop, not here: startReader() can run
        // from __unserialize before PrivateCallController has restored its state, so it must not touch it.
        EventLoop::queue($this->readerLoop(...));
    }

    private function readerLoop(): void
    {
        try {
            while (!$this->instance->isCallEnded()) {
                if ($this->currentFile === null && !$this->openNextFile()) {
                    if ($this->holdFiles === []) {
                        // Nothing to play: exit. play()/playOnHold()/skip() restart the reader.
                        return;
                    }
                    // Hold files only start once the queued frames of the last file have played out
                    // (openNextFile() refuses to open one on top of them): wait for the tracks to drain
                    // the queues and try again, rather than exiting and never playing the hold files.
                    Tools::sleep(0.1);
                    continue;
                }
                $generation = $this->generation + 1;
                $played = $this->streamCurrentFile();
                // Keep the files while their frames are queued, to resume them if the session is saved before they are played.
                $this->previous = array_values(array_filter(
                    $this->previous,
                    fn (array $previous): bool => $this->firstUnplayedPosition($previous['generation']) !== null,
                ));
                if ($this->currentResumable && $this->generation === $generation) {
                    $this->previous[] = $this->fileState();
                }
                // Only the played positions of those files are still needed.
                $this->playedPositions = array_intersect_key($this->playedPositions, array_flip(array_column($this->previous, 'generation')));
                $this->currentFile = null;
                $this->currentKind = null;
                $this->currentDesc = null;
                if (!$played && $this->playingHold) {
                    // Hold files are played in a loop: don't retry one that keeps failing in a tight loop.
                    Tools::sleep(1);
                }
            }
        } catch (Throwable $e) {
            $this->instance->log("DJ reader stopped in {$this}: $e", Logger::ERROR);
        } finally {
            $this->readerRunning = false;
        }
    }

    /**
     * Pick the next file to play from the playlist, or a hold file when the playlist is drained.
     */
    private function openNextFile(): bool
    {
        if ($this->inputFiles) {
            $this->setCurrent(array_shift($this->inputFiles), resuming: false);
            return true;
        }
        if ($this->holdFiles !== [] && $this->oggQueue->isEmpty() && $this->webmAudioQueue->isEmpty() && $this->videoQueue->isEmpty()) {
            $this->playingHold = true;
            $this->setCurrent($this->holdFiles[($this->holdIndex++) % \count($this->holdFiles)], resuming: false);
            return true;
        }
        return false;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function setCurrent(LocalFile|RemoteUrl|ReadableStream|StreamSnapshot $file, bool $resuming): void
    {
        $this->currentFile = $file;
        $this->currentDesc = $file instanceof ReadableStream || $file instanceof StreamSnapshot ? 'stream '.spl_object_id($file) : $file;
        $this->finishedCurrent = false;
        if (!$resuming) {
            // Until the new file is probed, it can't be resumed.
            $this->currentResumable = false;
            $this->resumeOffset = 0;
            $this->resumeSkip = 0;
            $this->resumeUnitOffset = -1;
            $this->resumeTracks = [];
            $this->resumeTimestampScale = 0;
            $this->resumePlayed = [];
            $this->bufferedUntilMs = 0;
            $this->playbackMs = 0;
            $this->currentKind = null;
        }
    }

    /**
     * @return bool Whether the file could be played (even if it was skipped).
     */
    private function streamCurrentFile(): bool
    {
        $generation = ++$this->generation;
        $this->readerCancel = new DeferredCancellation;
        $cancellation = $this->readerCancel->getCancellation();
        $item = $this->currentFile;
        \assert($item !== null);
        $resuming = $this->currentKind !== null; // set only when resuming from __unserialize
        $file = null;
        try {
            // A snapshotted resumable stream is reopened from the start of the item on every play.
            $file = $item instanceof StreamSnapshot ? $item->open() : $item;
            [$kind, $source, $convert] = $this->prepareSource($file, $cancellation, $resuming);
            $this->currentKind = $kind;
            if ($this->rtmp && $kind === 'ogg') {
                throw new AssertionError(
                    'An RTMP livestream can only play H.264 video and AAC audio, in an MP4 or Matroska file: '
                    .$this->describe($item).' is not one, please convert it, e.g. with ffmpeg -i input -c:v libx264 -c:a aac -movflags +faststart output.mp4'
                );
            }
            if ($kind === 'ogg') {
                // An OGG file has no video: stop the video a previous file left announced.
                $this->announceVideoStopped();
            }
            // A resumable stream can be reopened and skipped forward, like a file; MP4 files are only
            // played from their start.
            $this->currentResumable = $kind !== 'mp4' && !$convert && (!$source instanceof ReadableStream || $item instanceof StreamSnapshot);
            if ($kind === 'matroska') {
                $this->streamMatroska($source, $file, $generation, $cancellation, $resuming);
            } elseif ($kind === 'mp4') {
                $this->streamMp4($source, $generation, $cancellation);
            } else {
                $this->streamOgg($source, $file, $convert, $generation, $cancellation, $resuming);
            }
        } catch (CancelledException) {
            // Skipped or discarded: the next file (if any) starts on the next loop turn.
        } catch (Throwable $e) {
            $this->instance->log("Could not play {$this->describe($item)} in {$this}: $e", Logger::ERROR);
            return false;
        } finally {
            $this->aacTranscoder?->close();
            $this->aacTranscoder = null;
            if ($item instanceof StreamSnapshot && $file instanceof ReadableStream) {
                // Stop the download, if the stream was skipped before its end.
                $file->close();
            }
            if ($generation === $this->generation) {
                $this->finishedCurrent = true;
                $this->wakePacketWaiters();
            }
        }
        return true;
    }

    /**
     * Decide which demuxer a file needs and hand back a source to read it from.
     *
     * A seekable file is probed and re-opened; a raw stream is peeked at (its first chunk is put
     * back) so it can be classified without a rewind it does not support.
     *
     * @return array{string, LocalFile|RemoteUrl|ReadableStream, bool} kind, source, needsConversion
     */
    private function prepareSource(LocalFile|RemoteUrl|ReadableStream $file, ?Cancellation $cancellation, bool $resuming): array
    {
        if ($resuming) {
            return [(string) $this->currentKind, $file, false];
        }
        if ($file instanceof ReadableStream) {
            $first = $file->read($cancellation) ?? '';
            $source = new ReadableIterableStream((static function () use ($first, $file, $cancellation) {
                if ($first !== '') {
                    yield $first;
                }
                while (($chunk = $file->read($cancellation)) !== null) {
                    yield $chunk;
                }
            })());
            foreach (self::MATROSKA_MAGICS as $magic) {
                if (str_starts_with($first, $magic)) {
                    return ['matroska', $source, false];
                }
            }
            // The first box of an MP4 file is its ftyp (or styp, or straight its moov).
            if (\in_array(substr($first, 4, 4), ['ftyp', 'styp', 'moov'], true)) {
                return ['mp4', $source, false];
            }
            if (str_starts_with($first, self::OGG_MAGIC)) {
                // A stream can't be probed for MadelineProto's own encoding.
                return ['ogg', $source, $this->fixedOggGrid];
            }
            return ['ogg', $source, true];
        }
        try {
            $probe = new Matroska($file, $cancellation);
            if ($probe->tracks !== []) {
                return ['matroska', $file, false];
            }
        } catch (CancelledException $e) {
            throw $e;
        } catch (Throwable) {
            // Not a Matroska container.
        }
        try {
            $probe = new Mp4($file, $cancellation);
            if ($probe->tracks !== []) {
                return ['mp4', $file, false];
            }
        } catch (CancelledException $e) {
            throw $e;
        } catch (Throwable $e) {
            if ($e->getMessage() === Mp4::MOOV_AT_END) {
                throw $e;
            }
            // Not an MP4 file.
        }
        try {
            $ogg = new Ogg($file, $cancellation);
            if (!$this->fixedOggGrid || \in_array('MADELINE_ENCODER_V=1', $ogg->comments, true)) {
                return ['ogg', $file, false];
            }
        } catch (CancelledException $e) {
            throw $e;
        } catch (Throwable) {
            // Not an OGG OPUS file.
        }
        return ['ogg', $file, true];
    }

    private function streamOgg(
        LocalFile|RemoteUrl|ReadableStream $source,
        LocalFile|RemoteUrl|ReadableStream $file,
        bool $convert,
        int $generation,
        ?Cancellation $cancellation,
        bool $resuming
    ): void {
        if ($convert && !$resuming) {
            if (!Tools::canConvertOgg()) {
                throw new AssertionError($this->fixedOggGrid
                    ? 'The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!'
                    : 'The passed file is not a WebM/Matroska or OGG OPUS file, please convert it (see https://docs.madelineproto.xyz/docs/CALLS.html#supported-formats) or install FFI and ffmpeg to perform realtime conversion!');
            }
            // Convert to OGG OPUS on the fly; the resulting pipe is not seekable, so it is not resumable.
            $pipe = new Pipe(4096);
            // Its error is the one to report: what's read from the pipe then just ends early.
            $conversion = async(static function () use ($source, $pipe, $cancellation): void {
                try {
                    Ogg::convert($source, $pipe->getSink(), $cancellation);
                } finally {
                    EventLoop::queue($pipe->getSink()->close(...));
                }
            });
            // Awaited below, unless reading stopped first.
            $conversion->ignore();
            try {
                $this->streamOggPackets(new Ogg($pipe->getSource()), $generation, $cancellation, $resuming);
            } catch (Throwable $e) {
                if ($conversion->isComplete()) {
                    // The cause, if it failed.
                    $conversion->await();
                } else {
                    // Nobody reads the pipe anymore: stop the conversion writing to it.
                    $pipe->getSource()->close();
                }
                throw $e;
            }
            if ($generation !== $this->generation) {
                // Skipped: as above.
                $pipe->getSource()->close();
                return;
            }
            // Read to its end: done, or failed midway.
            $conversion->await();
            return;
        }
        $this->streamOggPackets($resuming ? new Ogg($file, $cancellation, $this->resumeOffset) : new Ogg($source, $cancellation), $generation, $cancellation, $resuming);
    }

    private function streamOggPackets(Ogg $ogg, int $generation, ?Cancellation $cancellation, bool $resuming): void
    {
        $skip = $resuming ? $this->resumeSkip : 0;
        foreach ($ogg->opusPackets as $packet) {
            if ($generation !== $this->generation) {
                return;
            }
            $unit = $ogg->pageOffset;
            if ($skip > 0 && $unit === $this->resumeUnitOffset) {
                $skip--;
                continue;
            }
            $this->oggQueue->enqueue([
                'data' => $packet,
                'position' => $this->trackResume($unit),
                'file' => $generation,
            ]);
            $samples = Ogg::packetDuration($packet);
            $this->oggQueueSamples += $samples;
            $this->bufferedUntilMs += intdiv($samples, 48);
            $this->wakePacketWaiters();
            $this->throttle($generation, $cancellation);
        }
    }

    private function streamMatroska(
        LocalFile|RemoteUrl|ReadableStream $source,
        LocalFile|RemoteUrl|ReadableStream $file,
        int $generation,
        ?Cancellation $cancellation,
        bool $resuming
    ): void {
        if ($resuming) {
            $matroska = new Matroska($file, $cancellation, $this->resumeOffset, $this->resumeTracks, $this->resumeTimestampScale);
            if (($this->videoCodec === 'H264' || $this->videoCodec === 'H265') && $this->framing === null) {
                foreach ($this->resumeTracks as $track) {
                    if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO) {
                        $this->framing = new H264Framing($track['private'], $this->videoCodec === 'H265');
                        break;
                    }
                }
            }
            if (!$this->rtmp && !$this->videoOnly && $this->aacConfig !== null) {
                $this->startTranscoder();
            }
        } else {
            $matroska = new Matroska($source, $cancellation);
            $this->resumeTracks = $matroska->tracks;
            $this->resumeTimestampScale = $matroska->getTimestampScale();
            $this->selectTracks($matroska);
        }
        $this->currentHasVideo = $this->videoCodec !== null;
        $this->currentHasAudio = false;
        if (!$this->videoOnly) {
            foreach ($matroska->tracks as $track) {
                if (isset($this->audioCodecs()[$track['codec']])) {
                    $this->currentHasAudio = true;
                }
            }
            if ($this->aacTranscoder !== null) {
                $this->currentHasAudio = true;
            }
        }
        $skip = $resuming ? $this->resumeSkip : 0;
        foreach ($matroska->frames as $frame) {
            if ($generation !== $this->generation) {
                return;
            }
            $unit = $matroska->unitOffset;
            if ($skip > 0 && $unit === $this->resumeUnitOffset) {
                $skip--;
                continue;
            }
            // Dropped frames are counted too, as the skip above counts all frames of the unit.
            $position = $this->trackResume($unit);
            $played = $this->resumePlayed[isset($this->audioCodecs()[$frame['codec']]) ? 'audio' : 'video'] ?? null;
            if ($resuming && $position !== null && $played !== null && $position <= $played) {
                // Played before the restart, while frames of the other kind before it weren't.
                continue;
            }
            if ($this->pushFrame($frame, $position)) {
                $this->wakePacketWaiters();
                $this->throttle($generation, $cancellation);
            }
        }
        $this->flushTranscoder($generation);
    }

    /**
     * Read an MP4 file, from its start: unlike a Matroska one, it's not resumed from where it was.
     */
    private function streamMp4(LocalFile|RemoteUrl|ReadableStream $source, int $generation, ?Cancellation $cancellation): void
    {
        $mp4 = new Mp4($source, $cancellation);
        $this->resumeTracks = [];
        $this->selectTracks($mp4);
        $this->currentHasVideo = $this->videoCodec !== null;
        $this->currentHasAudio = false;
        if (!$this->videoOnly) {
            foreach ($mp4->tracks as $track) {
                if (isset($this->audioCodecs()[$track['codec']])) {
                    $this->currentHasAudio = true;
                }
            }
            if ($this->aacTranscoder !== null) {
                $this->currentHasAudio = true;
            }
        }
        foreach ($mp4->frames as $frame) {
            if ($generation !== $this->generation) {
                return;
            }
            if ($this->pushFrame($frame, null)) {
                $this->wakePacketWaiters();
                $this->throttle($generation, $cancellation);
            }
        }
        $this->flushTranscoder($generation);
    }

    /**
     * Enqueue one demuxed WebM frame; returns whether it was kept (an untransmittable frame is not).
     *
     * @param array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool} $frame
     * @param ?list{int, int} $position
     */
    private function pushFrame(array $frame, ?array $position): bool
    {
        $timestampMs = $frame['timestamp'];
        $this->bufferedUntilMs = max($this->bufferedUntilMs, $timestampMs);
        if ($this->aacTranscoder !== null && $frame['track'] === $this->aacTrack) {
            // Its packets come out (into the queue) later, see startTranscoder().
            $this->aacPosition = $position;
            $this->aacTranscoder->push($frame['data'], $timestampMs);
            return true;
        }
        if (isset($this->audioCodecs()[$frame['codec']])) {
            // A presentation/screencast stream transmits video only; drop its audio.
            if ($this->videoOnly) {
                return false;
            }
            $data = $frame['data'];
            if ($this->rtmp) {
                // The audio track picked in selectTracks(), self-describing with an ADTS header.
                if ($frame['track'] !== $this->aacTrack || $this->aacConfig === null) {
                    return false;
                }
                $data = FlvTags::adtsHeader($this->aacConfig, \strlen($data)).$data;
            }
            $this->webmAudioQueue->enqueue([
                'data' => $data,
                'timestamp' => (int) ($timestampMs * self::AUDIO_CLOCK_RATE / 1000),
                'position' => $position,
                'file' => $this->generation,
            ]);
            return true;
        }
        if ($this->videoCodec === null || ($this->videoCodecs()[$frame['codec']] ?? null) !== $this->videoCodec) {
            return false;
        }
        // Confirm the VP9 profile from the first real keyframe (no configuration record was stored);
        // re-announce only if it differs from the tentative profile 0 advertised in selectTracks().
        if ($this->videoProfileFromFrame && $frame['keyframe']) {
            $this->videoProfileFromFrame = false;
            $parameters = Codec::fmtpFromBitstream('video/'.$this->videoCodec, '', $frame['data']);
            if ($parameters !== [] && $parameters !== $this->videoParameters) {
                $this->videoParameters = $parameters;
                $this->videoObserver?->onVideoCodec($this->videoCodec, $parameters);
            }
        }
        $this->videoQueue->enqueue([
            'data' => $this->framing?->convert($frame['data'], $frame['keyframe']) ?? $frame['data'],
            'timestamp' => (int) ($timestampMs * self::VIDEO_CLOCK_RATE / 1000),
            'keyframe' => $frame['keyframe'],
            'position' => $position,
            'file' => $this->generation,
        ]);
        return true;
    }

    /**
     * Record the resume point right after the frame that was just produced.
     *
     * @return ?list{int, int} The position of the frame: the byte offset of its unit (OGG page / MKV
     *                         cluster) and how many frames of the unit come before it, i.e. the resume
     *                         point right before it. Null if the file can't be resumed.
     *
     * @psalm-external-mutation-free
     */
    private function trackResume(int $unit): ?array
    {
        if (!$this->currentResumable) {
            return null;
        }
        if ($unit !== $this->resumeUnitOffset) {
            $this->resumeUnitOffset = $unit;
            $this->resumeSkip = 0;
        }
        $position = [$unit, $this->resumeSkip];
        $this->resumeSkip++;
        $this->resumeOffset = $unit;
        return $position;
    }

    /**
     * Block the reader while the look-ahead buffer is full, so memory stays bounded.
     *
     * Never while a track has nothing to play, though: the next frame of every track must be read
     * before it's due, whatever the look-ahead. Unless the file is badly interleaved (or one of its
     * tracks ended early): then up to {@see self::MAX_INTERLEAVING_MS} more are read.
     */
    private function throttle(int $generation, ?Cancellation $cancellation): void
    {
        $lookahead = $this->instance->getVoIPSettings()->getPlaybackLookahead();
        while (($ahead = $this->bufferedAheadMs()) > $lookahead
            && ($ahead > $lookahead + self::MAX_INTERLEAVING_MS || !$this->isStarving())
        ) {
            if ($generation !== $this->generation || $this->instance->isCallEnded()) {
                return;
            }
            $this->drainDeferred ??= new DeferredFuture;
            try {
                $this->drainDeferred->getFuture()->await($cancellation);
            } catch (CancelledException) {
                return;
            }
        }
    }

    /**
     * Whether a track has no frame queued, while the current file has frames for it.
     */
    private function isStarving(): bool
    {
        if ($this->playsFrames()) {
            return ($this->currentHasAudio && $this->queuedAudioMs() < ($this->aacTranscoder?->latencyMs() ?? 1))
                || ($this->currentHasVideo && $this->videoQueue->isEmpty());
        }
        return $this->oggQueue->isEmpty();
    }

    /**
     * The audio queued, in milliseconds: transcoded audio comes out of its transcoder later than the frames read, so
     * it starves while less than the transcoder's latency is queued, not just once its queue is empty.
     */
    private function queuedAudioMs(): int
    {
        if ($this->webmAudioQueue->isEmpty()) {
            return 0;
        }
        /** @var array{timestamp: int, ...} $first */
        $first = $this->webmAudioQueue->bottom();
        /** @var array{timestamp: int, ...} $last */
        $last = $this->webmAudioQueue->top();
        return intdiv($last['timestamp'] - $first['timestamp'], 48) + 20;
    }

    private function bufferedAheadMs(): int
    {
        if ($this->playsFrames()) {
            return $this->bufferedUntilMs - $this->playbackMs;
        }
        return intdiv($this->oggQueueSamples, 48);
    }

    /** Release the reader from back-pressure once a track has drained a queue. */
    private function releaseReader(): void
    {
        if ($this->drainDeferred !== null) {
            $deferred = $this->drainDeferred;
            $this->drainDeferred = null;
            $deferred->complete();
        }
    }

    /** Wake anything blocked in {@see self::pullPacket()}. */
    private function wakePacketWaiters(): void
    {
        if ($this->packetDeferred !== null) {
            $deferred = $this->packetDeferred;
            $this->packetDeferred = null;
            $deferred->complete(true);
        }
    }

    private function hasPendingOggWork(): bool
    {
        return !$this->oggQueue->isEmpty()
            || (!$this->playsFrames() && !$this->finishedCurrent)
            || $this->inputFiles !== []
            || (!$this->playingHold && $this->holdFiles !== []);
    }

    private function announceVideoStopped(): void
    {
        if ($this->webmVideoAnnounced) {
            $this->webmVideoAnnounced = false;
            $this->videoObserver?->onVideoStopped();
        }
    }

    /**
     * Work out what of a WebM file we can transmit, pin the video codec and warn about the rest.
     */
    private function selectTracks(Matroska|Mp4 $matroska): void
    {
        $this->videoCodec = null;
        $this->videoParameters = [];
        $this->videoProfileFromFrame = false;
        $this->framing = null;
        $this->aacTrack = null;
        $this->aacConfig = null;
        $this->aacTranscoder?->close();
        $this->aacTranscoder = null;
        if ($this->rtmp) {
            $this->selectRtmpAudio($matroska);
        } elseif (!$this->videoOnly) {
            $this->selectTranscodedAudio($matroska);
        }
        foreach ($matroska->tracks as $track) {
            if ($this->videoCodec !== null || !isset($this->videoCodecs()[$track['codec']])) {
                continue;
            }
            $this->videoCodec = $this->videoCodecs()[$track['codec']];
            $private = $track['private'] ?? '';
            if ($this->videoCodec === 'H264' || $this->videoCodec === 'H265') {
                $this->framing = new H264Framing($private, $this->videoCodec === 'H265');
            }
            // Advertise the file's real profile/level/tier instead of the generic fallback.
            $this->videoParameters = Codec::fmtpFromBitstream('video/'.$this->videoCodec, $private);
            // VP9 in WebM usually carries no configuration record; its profile is in every frame.
            // Advertise the common profile 0 for now and confirm it from the first keyframe.
            if ($this->videoParameters === [] && $this->videoCodec === 'VP9') {
                $this->videoParameters = ['profile-id' => '0'];
                $this->videoProfileFromFrame = true;
            }
        }
        $this->warnAboutDroppedTracks($matroska, Matroska::TRACK_TYPE_VIDEO, $this->videoCodecs());
        $this->warnAboutDroppedTracks(
            $matroska,
            Matroska::TRACK_TYPE_AUDIO,
            $this->aacTranscoder !== null ? $this->audioCodecs() + self::RTMP_AUDIO_CODECS : $this->audioCodecs(),
        );
        if ($this->videoCodec !== null) {
            // The transport has to be renegotiated before the first frame goes out, or the peer
            // would decode it as whatever codec the previous file used.
            $this->webmVideoAnnounced = true;
            $this->videoObserver?->onVideoCodec($this->videoCodec, $this->videoParameters);
        } else {
            // No video in this file: stop the video a previous file left announced.
            $this->announceVideoStopped();
        }
    }

    /**
     * Pick the AAC track of a file without OPUS audio to transcode to OPUS, for a call that can only carry OPUS.
     */
    private function selectTranscodedAudio(Matroska|Mp4 $demuxer): void
    {
        foreach ($demuxer->tracks as $track) {
            if (isset(self::AUDIO_CODECS[$track['codec']])) {
                return;
            }
        }
        foreach ($demuxer->tracks as $number => $track) {
            if ($track['type'] === Matroska::TRACK_TYPE_AUDIO && isset(self::RTMP_AUDIO_CODECS[$track['codec']]) && ($track['private'] ?? '') !== '') {
                $this->aacTrack = $number;
                $this->aacConfig = $track['private'];
                $this->startTranscoder();
                return;
            }
        }
    }

    /**
     * Start transcoding the AAC track to OPUS; if it can't be decoded, its audio is not transmitted.
     */
    private function startTranscoder(): void
    {
        \assert($this->aacConfig !== null);
        $generation = $this->generation;
        $this->aacPosition = null;
        try {
            $this->aacTranscoder = AacTranscoder::create($this->aacConfig, function (string $packet, int $timestamp) use ($generation): void {
                if ($generation !== $this->generation) {
                    // Skipped meanwhile.
                    return;
                }
                $this->webmAudioQueue->enqueue([
                    'data' => $packet,
                    'timestamp' => $timestamp,
                    'position' => $this->aacPosition,
                    'file' => $generation,
                ]);
                $this->wakePacketWaiters();
            });
            $this->instance->log("Transcoding the AAC audio of the file played in {$this} to OPUS with {$this->aacTranscoder->describe()}");
        } catch (AssertionError $e) {
            $this->aacTranscoder = null;
            $this->instance->log("The AAC audio of the file played in {$this} will not be transmitted: {$e->getMessage()}", Logger::WARNING);
        }
    }

    /**
     * Enqueue the rest of the transcoded audio, at the end of a file.
     */
    private function flushTranscoder(int $generation): void
    {
        if ($this->aacTranscoder === null || $generation !== $this->generation) {
            return;
        }
        try {
            $this->aacTranscoder->finish();
        } catch (AssertionError $e) {
            $this->instance->log("Could not transcode the end of the AAC audio of the file played in {$this}: {$e->getMessage()}", Logger::WARNING);
        }
    }

    /**
     * Pick the AAC track of a file to play for an RTMP livestream, refusing files with tracks it can't carry.
     *
     * @throws AssertionError If the file has audio or video an RTMP livestream can't carry, or neither.
     */
    private function selectRtmpAudio(Matroska|Mp4 $demuxer): void
    {
        $unsupported = [];
        $hasVideo = false;
        $hasH264 = false;
        $hasAudio = false;
        foreach ($demuxer->tracks as $number => $track) {
            if ($track['type'] === Matroska::TRACK_TYPE_VIDEO) {
                $hasVideo = true;
                if (isset(self::RTMP_VIDEO_CODECS[$track['codec']])) {
                    $hasH264 = true;
                } else {
                    $unsupported[] = $track['codec'];
                }
            } elseif ($track['type'] === Matroska::TRACK_TYPE_AUDIO) {
                $hasAudio = true;
                if (!isset(self::RTMP_AUDIO_CODECS[$track['codec']])) {
                    $unsupported[] = $track['codec'];
                } elseif ($this->aacTrack === null) {
                    if (($track['private'] ?? '') === '') {
                        throw new AssertionError("The AAC audio of the file played in $this has no AudioSpecificConfig (CodecPrivate), please remux it, e.g. with ffmpeg -i input -c copy output.mp4");
                    }
                    // Fails right away if ADTS can't carry its configuration.
                    FlvTags::adtsHeader($track['private'], 0);
                    $this->aacTrack = $number;
                    $this->aacConfig = $track['private'];
                }
            }
        }
        // Other tracks next to the ones we can play are only dropped, see warnAboutDroppedTracks().
        if (($hasVideo && !$hasH264) || ($hasAudio && $this->aacTrack === null) || (!$hasH264 && $this->aacTrack === null)) {
            throw new AssertionError(
                "An RTMP livestream can only play H.264 video and AAC audio, but the file played in $this has "
                .($unsupported !== [] ? implode(', ', $unsupported) : 'neither')
                .', please convert it, e.g. with ffmpeg -i input -c:v libx264 -c:a aac -movflags +faststart output.mp4'
            );
        }
    }

    /**
     * Whether the current file is a container of audio and video frames (rather than an OGG file).
     *
     * @psalm-mutation-free
     */
    private function playsFrames(): bool
    {
        return $this->currentKind === 'matroska' || $this->currentKind === 'mp4';
    }

    /**
     * The video codecs that can be played, as `Matroska CodecID => SDP encoding name`.
     *
     * @return array<string, string>
     *
     * @psalm-mutation-free
     */
    private function videoCodecs(): array
    {
        return $this->rtmp ? self::RTMP_VIDEO_CODECS : self::VIDEO_CODECS;
    }

    /**
     * The audio codecs that can be played, as `Matroska CodecID => SDP encoding name`.
     *
     * @return array<string, string>
     *
     * @psalm-mutation-free
     */
    private function audioCodecs(): array
    {
        return $this->rtmp ? self::RTMP_AUDIO_CODECS : self::AUDIO_CODECS;
    }

    /**
     * @param array<string, string> $supported
     */
    private function warnAboutDroppedTracks(Matroska|Mp4 $matroska, int $kind, array $supported): void
    {
        $found = [];
        foreach ($matroska->tracks as $track) {
            if ($track['type'] === $kind && !isset($supported[$track['codec']])) {
                $found[] = $track['codec'];
            } elseif ($track['type'] === $kind) {
                return;
            }
        }
        if ($found === []) {
            return;
        }
        $name = $kind === Matroska::TRACK_TYPE_VIDEO ? 'video' : 'audio';
        $this->instance->log(
            "The $name of the file played in {$this} will not be transmitted: it is ".
            implode(', ', $found).', and frames are fed to RTP exactly as the container stores them, '.
            'so a Telegram call can only carry '.implode(', ', array_keys($supported)).'.',
            Logger::WARNING
        );
    }

    /**
     * Resumable streams are snapshotted as soon as they are queued, so they survive a restart and can
     * be replayed (hold files loop); other streams can only be read once, and don't survive a restart.
     */
    private static function snapshot(LocalFile|RemoteUrl|ReadableStream $file): LocalFile|RemoteUrl|ReadableStream|StreamSnapshot
    {
        return $file instanceof ReadableStream && $file instanceof ResumableStream ? new StreamSnapshot($file) : $file;
    }

    /**
     * @psalm-pure
     */
    private function describe(LocalFile|RemoteUrl|ReadableStream|StreamSnapshot $file): string
    {
        return match (true) {
            $file instanceof LocalFile => $file->file,
            $file instanceof RemoteUrl => $file->url,
            default => 'stream '.spl_object_id($file),
        };
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return "DJ loop {$this->instance}";
    }
}
