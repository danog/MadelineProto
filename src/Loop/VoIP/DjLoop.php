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
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIPLoop;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Tgcalls\CallInterface;
use danog\MadelineProto\Tgcalls\H264Framing;
use danog\MadelineProto\Tgcalls\VideoCodecObserver;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use SplQueue;
use Throwable;
use Webrtc\Codecs\Codec;

/**
 * The single disc jockey of a call: it plays a playlist of files, streaming both their audio and
 * their video.
 *
 * There is exactly one playback mechanism, and it is the same for audio and for video; only the
 * demuxer differs per file:
 *
 * - A WebM/Matroska file (it begins with the EBML header) is demuxed by {@see Matroska}: its already
 *   encoded video and OPUS audio frames are handed to the RTP senders untouched, keeping the
 *   timestamps they had in the file, which is what keeps the two in sync.
 * - A native MadelineProto OGG OPUS file is demuxed by {@see Ogg} and played as-is; anything else is
 *   converted to OGG OPUS on the fly by ffmpeg.
 *
 * Nothing is decoded or re-encoded for pre-encoded input, so playback needs no codec library (and
 * therefore no FFI). Frames are **streamed**, not buffered whole: the reader keeps only about
 * {@see self::BUFFER_AHEAD_MS} of look-ahead and blocks until the tracks drain it, so memory stays
 * bounded however long the file is. The reader position is a byte offset into the current file, so
 * playback resumes exactly where it left off across a serialize → destruct → unserialize cycle (for
 * a seekable source — a raw stream or an on-the-fly conversion replays the current item instead).
 *
 * The whole playlist API — {@see self::play()}, {@see self::skip()}, {@see self::playOnHold()},
 * pausing, {@see self::getCurrent()} — applies to every file regardless of what media it carries.
 *
 * @internal
 */
final class DjLoop extends VoIPLoop
{
    /** VP8 and every other video codec on the wire use a 90kHz RTP clock. */
    public const VIDEO_CLOCK_RATE = 90000;
    /** OPUS always uses a 48kHz RTP clock. */
    public const AUDIO_CLOCK_RATE = 48000;

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
    ];

    /** The audio codecs we can transmit, as `Matroska CodecID => SDP encoding name`. */
    public const AUDIO_CODECS = ['A_OPUS' => 'opus'];

    /**
     * How a Matroska/WebM file begins: the EBML header, or — for a headerless file, as some muxers
     * and {@see Matroska} itself tolerate — the Segment element straight away.
     */
    private const MATROSKA_MAGICS = ["\x1A\x45\xDF\xA3", "\x18\x53\x80\x67"];
    /** OGG capture pattern: how an OGG file begins. */
    private const OGG_MAGIC = 'OggS';

    /** How far ahead of playback the reader is allowed to buffer, in milliseconds. */
    private const BUFFER_AHEAD_MS = 2000;

    /** Duration of one MadelineProto OGG OPUS frame, in milliseconds. */
    private const OGG_FRAME_MS = 60;

    /* -------------------------------------------------------------------- *
     *  Playlist.
     * -------------------------------------------------------------------- */

    /** @var list<LocalFile|RemoteUrl|ReadableStream> */
    private array $inputFiles = [];
    /** @var array<LocalFile|RemoteUrl|ReadableStream> */
    private array $holdFiles = [];
    private int $holdIndex = 0;
    private bool $playingHold = false;
    private bool $pause = false;

    /* -------------------------------------------------------------------- *
     *  Bounded look-ahead queues (serializable), drained by the tracks.
     * -------------------------------------------------------------------- */

    /** @var SplQueue<string> OPUS packets of an OGG file, played on the fixed 60ms grid. */
    private SplQueue $oggQueue;
    /** @var SplQueue<array{data: string, timestamp: int}> OPUS frames of a WebM file (48kHz ts). */
    private SplQueue $webmAudioQueue;
    /** @var SplQueue<array{data: string, timestamp: int, keyframe: bool}> Video frames (90kHz ts). */
    private SplQueue $videoQueue;

    /** Completed by a track when it drains a queue, to release the reader from back-pressure. */
    private ?DeferredFuture $drainDeferred = null;
    /** Awaited by the blocking {@see self::pullPacket()} until an OGG packet is available. */
    private ?DeferredFuture $packetDeferred = null;

    /* -------------------------------------------------------------------- *
     *  Current file + its byte-offset resume state.
     * -------------------------------------------------------------------- */

    private LocalFile|RemoteUrl|ReadableStream|null $currentFile = null;
    private LocalFile|RemoteUrl|string|null $currentDesc = null;
    /** 'ogg' or 'matroska', or null when nothing is playing. */
    private ?string $currentKind = null;
    /** Whether the current file can be resumed from a byte offset (a seekable, non-converted source). */
    private bool $currentResumable = false;

    /** Byte offset of the container unit (OGG page / MKV cluster) to resume the current file from. */
    private int $resumeOffset = 0;
    /** How many frames of the resume unit were already produced, and so must be skipped on resume. */
    private int $resumeSkip = 0;
    /** Byte offset of the unit the last produced frame came from, used to keep {@see self::$resumeSkip}. */
    private int $resumeUnitOffset = -1;
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
    /** Whether a video codec was announced and still owes the observer a stop notification. */
    private bool $webmVideoAnnounced = false;

    /* -------------------------------------------------------------------- *
     *  Transient reader control (never serialized).
     * -------------------------------------------------------------------- */

    private bool $readerRunning = false;
    /** Bumped to make the running reader abandon the current file (skip/stop/discard). */
    private int $generation = 0;
    private ?DeferredCancellation $readerCancel = null;

    /**
     * @param bool $videoOnly When true, the audio of played files is dropped and only their video is
     *                        queued — used for a presentation/screencast stream, which carries no audio.
     */
    public function __construct(CallInterface $instance, private bool $videoOnly = false)
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

    public function __serialize(): array
    {
        // Only a seekable, non-converted file can resume mid-stream; a raw stream or an on-the-fly
        // conversion replays its item from the start (files) or is dropped (streams).
        $inputFiles = $this->inputFiles;
        $currentFile = null;
        if ($this->currentFile !== null && !$this->finishedCurrent) {
            if ($this->currentResumable) {
                $currentFile = $this->currentFile;
            } elseif (!$this->currentFile instanceof ReadableStream) {
                array_unshift($inputFiles, $this->currentFile);
            }
        }
        return [
            'instance' => $this->instance,
            'pause' => $this->pause,
            'inputFiles' => array_values(array_filter($inputFiles, static fn ($v) => !$v instanceof ReadableStream)),
            'holdFiles' => array_filter($this->holdFiles, static fn ($v) => !$v instanceof ReadableStream),
            'holdIndex' => $this->holdIndex,
            'playingHold' => $this->playingHold,
            'oggQueue' => $this->oggQueue,
            'webmAudioQueue' => $this->webmAudioQueue,
            'videoQueue' => $this->videoQueue,
            'currentFile' => $currentFile,
            'currentDesc' => $currentFile !== null ? $this->currentDesc : null,
            'currentKind' => $currentFile !== null ? $this->currentKind : null,
            'currentResumable' => $currentFile !== null,
            'finishedCurrent' => $currentFile === null,
            'resumeOffset' => $this->resumeOffset,
            'resumeSkip' => $this->resumeSkip,
            'resumeUnitOffset' => $this->resumeUnitOffset,
            'resumeTracks' => $this->resumeTracks,
            'resumeTimestampScale' => $this->resumeTimestampScale,
            'bufferedUntilMs' => $this->bufferedUntilMs,
            'playbackMs' => $this->playbackMs,
            'videoObserver' => $this->videoObserver,
            'videoCodec' => $this->videoCodec,
            'videoParameters' => $this->videoParameters,
            'videoProfileFromFrame' => $this->videoProfileFromFrame,
            'videoOnly' => $this->videoOnly,
            'framing' => $this->framing,
            'webmVideoAnnounced' => $this->webmVideoAnnounced,
        ];
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->oggQueue ??= new SplQueue;
        $this->webmAudioQueue ??= new SplQueue;
        $this->videoQueue ??= new SplQueue;
        // Sessions serialized before video-only mode existed carry no value for it.
        $this->videoOnly ??= false;
        $this->videoParameters ??= [];
        // Do NOT restart the reader here: its loop reads the call's state, which is only restored once
        // the whole call graph has finished deserializing. Starting it now could run it
        // mid-deserialization (if a nested resume suspends the fiber) and dereference not-yet-restored
        // state. The call's deserializer calls {@see self::resume()} once the graph is whole.
        $this->readerRunning = false;
        $this->generation = 0;
    }

    /**
     * Restart the demuxer/reader task after a serialize/unserialize cycle, resuming the current file
     * (from its byte offset) or the playlist where it left off. Called by the call's deserializer once
     * the whole call graph is restored; idempotent via the readerRunning guard. (Named to avoid the
     * base Loop::resume().)
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
        $this->webmAudioQueue = new SplQueue;
        $this->videoQueue = new SplQueue;
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
        $this->inputFiles[] = $file;
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

    public function isAudioPaused(): bool
    {
        return $this->pause;
    }

    /**
     * Get info about what is currently being played: a description string for a stream, otherwise
     * the related {@see LocalFile} or {@see RemoteUrl}.
     */
    public function getCurrent(): LocalFile|RemoteUrl|string|null
    {
        return $this->currentDesc;
    }

    /**
     * Files to play on hold.
     */
    public function playOnHold(LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->holdFiles = $files;
        $this->startReader();
    }

    /* ==================================================================== *
     *  Track-facing API.
     * ==================================================================== */

    /**
     * Pull the next OGG-grid OPUS packet, waiting for one if the playlist is not exhausted.
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
     * Pull the next OGG-grid OPUS packet if one is immediately available.
     *
     * Used by the WebRTC playback track, which is polled from a callback that must never suspend.
     */
    public function tryPullPacket(): ?string
    {
        if ($this->pause || $this->oggQueue->isEmpty()) {
            return null;
        }
        $packet = $this->oggQueue->dequeue();
        $this->releaseReader();
        return $packet;
    }

    public function hasWebmAudio(): bool
    {
        return !$this->webmAudioQueue->isEmpty();
    }

    /**
     * Pull the next OPUS frame of the WebM file being played, or null if none is buffered.
     *
     * @return array{data: string, timestamp: int}|null
     */
    public function pullWebmAudio(): ?array
    {
        if ($this->webmAudioQueue->isEmpty()) {
            return null;
        }
        /** @var array{data: string, timestamp: int} $frame */
        $frame = $this->webmAudioQueue->dequeue();
        $this->setPlaybackPosition((int) ($frame['timestamp'] * 1000 / self::AUDIO_CLOCK_RATE));
        $this->releaseReader();
        return $frame;
    }

    /**
     * @return array{data: string, timestamp: int, keyframe: bool}|null
     */
    public function pullVideo(): ?array
    {
        if ($this->videoQueue->isEmpty()) {
            return null;
        }
        /** @var array{data: string, timestamp: int, keyframe: bool} $frame */
        $frame = $this->videoQueue->dequeue();
        $this->releaseReader();
        return $frame;
    }

    public function hasVideo(): bool
    {
        return !$this->videoQueue->isEmpty();
    }

    /**
     * Whether a WebM file is currently being played, even if a queue momentarily ran dry because the
     * reader is behind.
     */
    public function isWebmPlaying(): bool
    {
        return $this->currentKind === 'matroska' && !$this->isExhausted();
    }

    /**
     * Whether the WebM file was fully read and both of its queues are drained.
     *
     * Firing the observer's stop notification exactly once here keeps the video-stop plumbing in the
     * one place that knows a file has truly finished.
     */
    public function isExhausted(): bool
    {
        $exhausted = $this->currentKind !== 'matroska'
            || ($this->finishedCurrent && $this->webmAudioQueue->isEmpty() && $this->videoQueue->isEmpty());
        if ($exhausted && $this->webmVideoAnnounced) {
            $this->announceVideoStopped();
        }
        return $exhausted;
    }

    /**
     * Report how far playback has progressed, in milliseconds.
     */
    public function setPlaybackPosition(int $milliseconds): void
    {
        $this->playbackMs = max($this->playbackMs, $milliseconds);
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
        // from __unserialize before VoIPController has restored its state, so it must not touch it.
        EventLoop::queue($this->readerLoop(...));
    }

    private function readerLoop(): void
    {
        try {
            while (!$this->instance->isCallEnded()) {
                if ($this->currentFile === null && !$this->openNextFile()) {
                    // Nothing to play: exit. play()/playOnHold()/skip() restart the reader.
                    return;
                }
                $this->streamCurrentFile();
                $this->currentFile = null;
                $this->currentKind = null;
                $this->currentDesc = null;
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
    private function setCurrent(LocalFile|RemoteUrl|ReadableStream $file, bool $resuming): void
    {
        $this->currentFile = $file;
        $this->currentDesc = $file instanceof ReadableStream ? 'stream '.spl_object_id($file) : $file;
        $this->finishedCurrent = false;
        if (!$resuming) {
            $this->resumeOffset = 0;
            $this->resumeSkip = 0;
            $this->resumeUnitOffset = -1;
            $this->resumeTracks = [];
            $this->resumeTimestampScale = 0;
            $this->bufferedUntilMs = 0;
            $this->playbackMs = 0;
            $this->currentKind = null;
        }
    }

    private function streamCurrentFile(): void
    {
        $generation = ++$this->generation;
        $this->readerCancel = new DeferredCancellation;
        $cancellation = $this->readerCancel->getCancellation();
        $file = $this->currentFile;
        \assert($file !== null);
        $resuming = $this->currentKind !== null; // set only when resuming from __unserialize
        try {
            [$kind, $source, $convert] = $this->prepareSource($file, $cancellation, $resuming);
            $this->currentKind = $kind;
            $this->currentResumable = !$convert && !$source instanceof ReadableStream;
            if ($kind === 'matroska') {
                $this->streamMatroska($source, $file, $generation, $cancellation, $resuming);
            } else {
                $this->streamOgg($source, $file, $convert, $generation, $cancellation, $resuming);
            }
        } catch (CancelledException) {
            // Skipped or discarded: the next file (if any) starts on the next loop turn.
        } catch (Throwable $e) {
            $this->instance->log("Could not play {$this->describe($file)} in {$this}: $e", Logger::ERROR);
        } finally {
            if ($generation === $this->generation) {
                $this->finishedCurrent = true;
                $this->wakePacketWaiters();
            }
        }
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
            if (str_starts_with($first, self::OGG_MAGIC)) {
                return ['ogg', $source, false];
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
            $ogg = new Ogg($file, $cancellation);
            if (\in_array('MADELINE_ENCODER_V=1', $ogg->comments, true)) {
                return ['ogg', $file, false];
            }
        } catch (CancelledException $e) {
            throw $e;
        } catch (Throwable) {
            // Not a native OGG OPUS file.
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
        if ($resuming) {
            $ogg = new Ogg($file, $cancellation, $this->resumeOffset);
        } elseif ($convert) {
            if (!Tools::canConvertOgg()) {
                throw new AssertionError('The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!');
            }
            // Convert to OGG OPUS on the fly; the resulting pipe is not seekable, so it is not resumable.
            $pipe = new Pipe(4096);
            EventLoop::queue(static function () use ($source, $pipe, $cancellation): void {
                try {
                    Ogg::convert($source, $pipe->getSink(), $cancellation);
                } catch (CancelledException) {
                } finally {
                    EventLoop::queue($pipe->getSink()->close(...));
                }
            });
            $ogg = new Ogg($pipe->getSource());
        } else {
            $ogg = new Ogg($source, $cancellation);
        }
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
            $this->oggQueue->enqueue($packet);
            $this->trackResume($unit);
            $this->bufferedUntilMs += self::OGG_FRAME_MS;
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
            if ($this->videoCodec === 'H264' && $this->framing === null) {
                foreach ($this->resumeTracks as $track) {
                    if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO) {
                        $this->framing = new H264Framing($track['private']);
                        break;
                    }
                }
            }
        } else {
            $matroska = new Matroska($source, $cancellation);
            $this->resumeTracks = $matroska->tracks;
            $this->resumeTimestampScale = $matroska->getTimestampScale();
            $this->selectTracks($matroska);
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
            if ($this->pushFrame($frame)) {
                $this->trackResume($unit);
                $this->wakePacketWaiters();
                $this->throttle($generation, $cancellation);
            }
        }
    }

    /**
     * Enqueue one demuxed WebM frame; returns whether it was kept (an untransmittable frame is not).
     *
     * @param array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool} $frame
     */
    private function pushFrame(array $frame): bool
    {
        $timestampMs = $frame['timestamp'];
        $this->bufferedUntilMs = max($this->bufferedUntilMs, $timestampMs);
        if (isset(self::AUDIO_CODECS[$frame['codec']])) {
            // A presentation/screencast stream transmits video only; drop its audio.
            if ($this->videoOnly) {
                return false;
            }
            $this->webmAudioQueue->enqueue([
                'data' => $frame['data'],
                'timestamp' => (int) ($timestampMs * self::AUDIO_CLOCK_RATE / 1000),
            ]);
            return true;
        }
        if ($this->videoCodec === null || (self::VIDEO_CODECS[$frame['codec']] ?? null) !== $this->videoCodec) {
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
        ]);
        return true;
    }

    /**
     * Record the resume point right after the frame that was just produced.
     *
     * @psalm-external-mutation-free
     */
    private function trackResume(int $unit): void
    {
        if (!$this->currentResumable) {
            return;
        }
        if ($unit !== $this->resumeUnitOffset) {
            $this->resumeUnitOffset = $unit;
            $this->resumeSkip = 0;
        }
        $this->resumeSkip++;
        $this->resumeOffset = $unit;
    }

    /**
     * Block the reader while the look-ahead buffer is full, so memory stays bounded.
     */
    private function throttle(int $generation, ?Cancellation $cancellation): void
    {
        while ($this->bufferedAheadMs() >= self::BUFFER_AHEAD_MS) {
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

    private function bufferedAheadMs(): int
    {
        if ($this->currentKind === 'matroska') {
            return $this->bufferedUntilMs - $this->playbackMs;
        }
        return $this->oggQueue->count() * self::OGG_FRAME_MS;
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
            || ($this->currentKind !== 'matroska' && !$this->finishedCurrent)
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
    private function selectTracks(Matroska $matroska): void
    {
        $this->videoCodec = null;
        $this->videoParameters = [];
        $this->videoProfileFromFrame = false;
        $this->framing = null;
        foreach ($matroska->tracks as $track) {
            if ($this->videoCodec !== null || !isset(self::VIDEO_CODECS[$track['codec']])) {
                continue;
            }
            $this->videoCodec = self::VIDEO_CODECS[$track['codec']];
            $private = $track['private'] ?? '';
            if ($this->videoCodec === 'H264') {
                $this->framing = new H264Framing($private);
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
        $this->warnAboutDroppedTracks($matroska, Matroska::TRACK_TYPE_VIDEO, self::VIDEO_CODECS);
        $this->warnAboutDroppedTracks($matroska, Matroska::TRACK_TYPE_AUDIO, self::AUDIO_CODECS);
        if ($this->videoCodec !== null) {
            // The transport has to be renegotiated before the first frame goes out, or the peer
            // would decode it as whatever codec the previous file used.
            $this->webmVideoAnnounced = true;
            $this->videoObserver?->onVideoCodec($this->videoCodec, $this->videoParameters);
        }
    }

    /**
     * @param array<string, string> $supported
     */
    private function warnAboutDroppedTracks(Matroska $matroska, int $kind, array $supported): void
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
     * @psalm-mutation-free
     */
    private function describe(LocalFile|RemoteUrl|ReadableStream $file): string
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
