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

namespace danog\MadelineProto\GroupCall;

use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\WritableStream;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\MatroskaWriter;
use danog\MadelineProto\Mp4;
use danog\MadelineProto\OggWriter;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RestoresDeclaredProperties;
use danog\MadelineProto\RPCError\RateLimitError;
use danog\MadelineProto\RPCErrorException;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;

/**
 * Receives the media of a group call in [stream mode](https://core.telegram.org/api/group-calls#stream-mode)
 * (a large livestream the server no longer serves over WebRTC, or an RTMP livestream): downloads its
 * media chunks from the stream DC every second and records them.
 *
 * An RTMP livestream is a single unified channel carrying audio and video, recorded together into a
 * Matroska file (or audio only into OGG OPUS). An automatically-scaled livestream serves the mixed
 * audio as OGG OPUS chunks whose metadata lists the publishers (`ENDPOINTS`, `ACTIVE_MASK`), and
 * each publisher's video as separate MP4 chunks: recording into a directory writes the mixed audio to
 * `stream.ogg` and every publisher's video to `video-<endpoint>.mkv`.
 *
 * As official clients do, the stream is followed one 1000 ms segment at a time, in real time, 2 seconds behind the
 * live edge; a chunk that is not ready yet (`TIME_TOO_BIG`) is retried after 100 ms (2 seconds later for an RTMP
 * stream), after a flood wait as long as it says, any
 * other error resynchronizes to the live edge, and being told we are not in the call any more
 * (`GROUPCALL_JOIN_MISSING` / `GROUPCALL_FORBIDDEN`) rejoins it.
 *
 * @internal
 */
final class StreamReceiver
{
    use RestoresDeclaredProperties;

    /** Chunk length: official clients hardcode 1000 ms (the deprecated `scale` is ignored). */
    private const SEGMENT_MS = 1000;
    /** How far behind the live edge playback stays, so chunks are ready when asked for. */
    private const BUFFER_MS = 2000;
    /** Header signature of a unified (RTMP) chunk, which is followed by a container name and events. */
    private const UNIFIED_SIGNATURE = 0xa12e810d;
    private const RETRY_MS = 100;
    /**
     * How often a chunk is downloaded without a recording, in seconds: the server drops a listener that downloads
     * nothing about a minute after it joined, see {@see self::keepAlive()}.
     */
    private const KEEPALIVE_INTERVAL = 20.0;
    /** Cancels the wait between two keep-alive downloads, when a recording is set. */
    private ?DeferredCancellation $keepAliveWait = null;
    /**
     * When the chunks of an RTMP stream are due: its timeline doesn't follow the server clock, so it's paced from the
     * chunk it was resynchronized at and when (hrtime(), in nanoseconds).
     *
     * @var ?array{int, int}
     */
    private ?array $pace = null;
    /** How long to wait before fetching the stream channels again when they have no usable timestamp, in seconds. */
    private const CHANNELS_RETRY = 1.0;
    /** How many audio-only chunks of an RTMP stream are held, waiting for its video, before recording only its audio. */
    private const MAX_AUDIO_ONLY_CHUNKS = 10;

    private bool $running = false;
    private bool $rtmp = false;
    private int $generation = 0;
    private ?int $nextTimestamp = null;
    /** The timestamp of the last chunk consumed: a resynchronization never goes back to it. */
    private ?int $lastTimestamp = null;
    /**
     * @var list<array{list<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}>, array<int, array<string, mixed>>, int}>
     *      The first chunks of an RTMP stream, without video, held until the recording is opened:
     *      their frames, tracks and timestamp.
     */
    private array $audioOnlyChunks = [];

    private LocalFile|WritableStream|null $outputFile = null;
    /** Directory to record into (folder mode), if any. */
    private ?string $outputDir = null;
    private ?RecordingFormat $outputFormat = null;
    private ?OggWriter $ogg = null;
    private ?MatroskaWriter $mkv = null;
    /** @var array<int, string> The codecs of the mixed stream's open recording, by {@see CallStream} flag. */
    private array $mainCodecs = [];
    private ?WritableStream $out = null;
    /** Milliseconds of audio written so far, for Matroska timestamps. */
    private int $writtenMs = 0;
    /** The timestamp (ms) of the chunk at which the current recording started, for video timestamps. */
    private ?int $recordingStart = null;
    /** Whether the main Matroska recording got its video track before starting. */
    private bool $mainHasVideo = false;
    /**
     * The publishers of an automatically-scaled livestream, from the audio chunks' metadata: the video
     * channel of each (its index plus one) and whether it is currently active.
     *
     * @var array<int, array{endpoint: string, active: bool}>
     */
    private array $publishers = [];
    /**
     * Per-publisher video recorders in folder mode, by video channel.
     *
     * @var array<int, array{endpoint: string, writer: MatroskaWriter, start: int, started: bool, file: string, codec: string}>
     */
    private array $videoWriters = [];
    private bool $unsupportedLogged = false;
    /** The current interval between fetches of the stream channels, see loop(). */
    private float $channelsRetry = self::CHANNELS_RETRY;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly GroupCallController $call,
    ) {
    }

    /**
     * Keep the recording across a restart, as the WebRTC recordings are kept: the stream is followed again from its
     * live edge (what was missed in between is a gap in the recording), and the writers reopen their files (see
     * {@see self::resume()}). A recording into a stream (not a file) can't survive, and ends.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['running'], $vars['generation'], $vars['nextTimestamp'], $vars['pace'], $vars['keepAliveWait'], $vars['audioOnlyChunks'], $vars['out'], $vars['channelsRetry'], $vars['partialRetries']);
        if (!$this->outputFile instanceof LocalFile) {
            unset($vars['outputFile'], $vars['outputDir'], $vars['outputFormat'], $vars['ogg'], $vars['mkv'], $vars['mainCodecs'], $vars['writtenMs'], $vars['recordingStart'], $vars['mainHasVideo'], $vars['videoWriters']);
        }
        return $vars;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        // Synchronous restoration only: the writers reopen their files in resume().
        $this->restoreDeclaredProperties($data);
    }

    /**
     * After a restart: reopen the files of the recording, ending what can't continue (a file deleted meanwhile).
     */
    public function resume(): void
    {
        if (($this->mkv !== null && !$this->mkv->resume()) || ($this->ogg !== null && !$this->ogg->resume())) {
            $this->call->log("Could not continue the stream recording of {$this->call} after a restart: starting a new one", Logger::WARNING);
            $this->ogg = null;
            $this->mkv = null;
            $this->mainHasVideo = false;
            $this->mainCodecs = [];
            $this->recordingStart = null;
        }
        foreach ($this->videoWriters as $channel => $writer) {
            if (!$writer['writer']->resume()) {
                unset($this->videoWriters[$channel]);
            }
        }
    }

    /**
     * Start (or restart) following the stream.
     */
    public function start(bool $rtmp): void
    {
        $this->rtmp = $rtmp;
        if ($this->running) {
            return;
        }
        $this->running = true;
        $generation = ++$this->generation;
        EventLoop::queue(function () use ($generation): void {
            try {
                $this->loop($generation);
            } catch (Throwable $e) {
                $this->call->log("The stream receiver of {$this->call} died: $e", Logger::ERROR);
            } finally {
                if ($generation === $this->generation) {
                    $this->running = false;
                }
            }
        });
    }

    /**
     * Stop following the stream and finish the recording.
     */
    public function stop(): void
    {
        $this->keepAliveWait?->cancel();
        $this->running = false;
        $this->generation++;
        $this->closeWriters();
    }

    /**
     * Record the stream's audio into a file or stream. The format is autodetected from the extension
     * of a {@see LocalFile}; a raw stream defaults to OGG OPUS.
     */
    public function setOutput(LocalFile|WritableStream $file, ?RecordingFormat $format = null): void
    {
        $this->keepAliveWait?->cancel();
        $this->closeWriters();
        $this->outputDir = null;
        $this->outputFile = $file;
        $this->outputFormat = $format ?? ($file instanceof LocalFile ? RecordingFormat::fromFile($file) : RecordingFormat::Mkv);
    }

    /**
     * Record into a directory: the mixed stream (audio, plus video for an RTMP livestream) as
     * `stream.ogg` (or `.mkv`/`.webm` for a Matroska `$format`, the default for RTMP), and, in an
     * automatically-scaled livestream, each publisher's video as `video-<endpoint>.mkv`.
     */
    public function setOutputDirectory(LocalDirectory $directory, ?RecordingFormat $format = null): void
    {
        $this->keepAliveWait?->cancel();
        $dir = $directory->create();
        $format ??= RecordingFormat::Mkv;
        $extension = match ($format) {
            RecordingFormat::Opus => 'ogg',
            RecordingFormat::Webm => 'webm',
            RecordingFormat::Mkv => 'mkv',
        };
        $this->setOutput(new LocalFile("$dir/stream.$extension"), $format);
        $this->outputDir = $dir;
    }

    private function loop(int $generation): void
    {
        while ($this->running && $generation === $this->generation) {
            if ($this->outputFile === null && $this->outputDir === null) {
                if (!$this->keepAlive()) {
                    return;
                }
                continue;
            }
            if ($this->nextTimestamp === null) {
                try {
                    $edge = $this->liveEdge();
                } catch (RateLimitError $e) {
                    // Polled faster than the server allows (FLOOD_WAIT_3 while the livestream has no media): wait
                    // as long as it asks, and poll at that pace from now on, until the stream starts.
                    $this->channelsRetry = max($this->channelsRetry, (float) $e->getWaitTime());
                    $this->call->log("Waiting {$e->getWaitTime()} seconds to fetch the stream channels of {$this->call} again", Logger::ULTRA_VERBOSE);
                    delay((float) $e->getWaitTime());
                    continue;
                } catch (Throwable $e) {
                    $this->call->log("Could not fetch the stream channels of {$this->call}: $e", Logger::WARNING);
                    $edge = null;
                }
                if ($edge === null) {
                    // No usable timestamp yet: the channels are empty while the publisher of the livestream sends
                    // no media, see https://core.telegram.org/api/group-calls (tgcalls retries after a second).
                    delay($this->channelsRetry);
                    continue;
                }
                $this->channelsRetry = self::CHANNELS_RETRY;
                // The live edge may not have moved since the last chunk (e.g. a resynchronization
                // after the next chunk was not ready yet): never consume a chunk twice, unless the
                // edge is far behind it (the timeline restarted).
                if ($this->lastTimestamp !== null && $edge <= $this->lastTimestamp && $this->lastTimestamp - $edge <= self::BUFFER_MS + 2 * self::SEGMENT_MS) {
                    $edge = $this->lastTimestamp + self::SEGMENT_MS;
                }
                $this->nextTimestamp = $edge;
                $this->pace = [$edge, hrtime(true)];
            }
            $timestamp = $this->nextTimestamp;
            // Stay behind the live edge: never ask for a chunk that cannot be complete yet. The
            // timeline of an RTMP stream starts with the stream, not the server clock: it's followed in
            // real time from where it was resynchronized, as tgcalls does (fetched as soon as the server
            // accepts them, its chunks were those of the live edge, cut short while being written).
            if ($this->rtmp) {
                [$paceTimestamp, $paceStart] = $this->pace ?? [$timestamp, hrtime(true)];
                $wait = $paceStart + ($timestamp - $paceTimestamp) * 1_000_000 - hrtime(true);
                if ($wait > 0) {
                    delay($wait / 1_000_000_000);
                    continue;
                }
            } else {
                $due = $timestamp + self::SEGMENT_MS + self::BUFFER_MS;
                $now = self::nowMs();
                if ($due > $now) {
                    delay(($due - $now) / 1000);
                    continue;
                }
            }
            $chunk = null;
            try {
                $chunk = $this->fetch($timestamp);
            } catch (RPCErrorException $e) {
                if ($e->rpc === 'GROUPCALL_JOIN_MISSING' || $e->rpc === 'GROUPCALL_FORBIDDEN') {
                    $this->call->log("Not in {$this->call} any more ({$e->rpc}), rejoining...", Logger::WARNING);
                    $this->running = false;
                    $this->call->rejoin();
                    return;
                }
                if ($e->rpc === 'TIME_TOO_BIG' && $this->rtmp) {
                    // The RTMP stream lags behind real time: we caught up with its live edge. Fall back behind it.
                    $this->pace = [$timestamp, hrtime(true) + self::BUFFER_MS * 1_000_000];
                    continue;
                }
                if ($e instanceof RateLimitError) {
                    // Retried every 100 ms, a stalled stream (TIME_TOO_BIG, then FLOOD_WAIT_3) got a flood wait
                    // about 3 times a second.
                    delay((float) $e->getWaitTime());
                    continue;
                }
                if ($e->rpc === 'TIME_TOO_BIG') {
                    delay(self::RETRY_MS / 1000);
                    continue;
                }
                $this->call->log("Resynchronizing the stream of {$this->call} after {$e->rpc}", Logger::WARNING);
                $this->nextTimestamp = null;
                delay(self::RETRY_MS / 1000);
                continue;
            } catch (Throwable $e) {
                $this->call->log("Could not download a chunk of {$this->call}: $e", Logger::WARNING);
                delay(1.0);
                continue;
            }
            if ($chunk === null) {
                // A CDN redirect: the stream cannot be served from here, resynchronize.
                $this->nextTimestamp = null;
                delay(self::RETRY_MS / 1000);
                continue;
            }
            // Taken as it is even if cut short, as tgcalls (and TDLib) do: the chunks of a stream are flagged so
            // (storage.filePartial) even well behind its live edge, where they never change. Fetched again until
            // they weren't, each chunk took seconds (10 more requests) and the receiver fell a minute behind,
            // until the server no longer had its chunk (TIME_TOO_SMALL). Staying behind the edge keeps the chunks
            // complete.
            ['bytes' => $chunk, 'partial' => $partial] = $chunk;
            $this->call->log("Got the chunk $timestamp of {$this->call} (".\strlen($chunk).' bytes'.($partial ? ', cut short' : '').')', Logger::ULTRA_VERBOSE);
            $this->nextTimestamp = $timestamp + self::SEGMENT_MS;
            $this->lastTimestamp = $timestamp;
            try {
                $this->consume($chunk, $timestamp);
            } catch (Throwable $e) {
                // A chunk cut short ends mid-frame: what's before the cut was recorded.
                $partial
                    ? $this->call->log("Recorded the chunk $timestamp of {$this->call} up to where it was cut short: {$e->getMessage()}", Logger::VERBOSE)
                    : $this->call->log("Could not demux a chunk of {$this->call}: $e", Logger::WARNING);
            }
            if (!$this->rtmp && $this->outputDir !== null) {
                $this->consumePublishersVideo($timestamp);
            }
        }
    }

    /**
     * Without a recording, download a chunk of the live edge every {@see self::KEEPALIVE_INTERVAL} seconds, and
     * throw it away: the server drops a listener that downloads nothing about a minute after it joined (magnaluna,
     * in the RTMP livestreams it plays to, rejoined each of them every minute), while following the whole stream
     * for nothing drew flood waits. The stream is followed from its live edge once a recording is set.
     *
     * @return bool Whether to go on (false: we're not in the call any more, it's rejoined).
     */
    private function keepAlive(): bool
    {
        $this->nextTimestamp = null;
        try {
            $edge = $this->liveEdge();
            if ($edge !== null) {
                $this->fetch($edge);
            }
        } catch (RateLimitError $e) {
            delay((float) $e->getWaitTime());
            return true;
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'GROUPCALL_JOIN_MISSING' || $e->rpc === 'GROUPCALL_FORBIDDEN') {
                $this->call->log("Not in {$this->call} any more ({$e->rpc}), rejoining...", Logger::WARNING);
                $this->running = false;
                $this->call->rejoin();
                return false;
            }
        } catch (Throwable $e) {
            $this->call->log("Could not download a chunk of {$this->call}: $e", Logger::VERBOSE);
        }
        $this->keepAliveWait = new DeferredCancellation;
        try {
            delay(self::KEEPALIVE_INTERVAL, cancellation: $this->keepAliveWait->getCancellation());
        } catch (CancelledException) {
            // A recording was set.
        } finally {
            $this->keepAliveWait = null;
        }
        return true;
    }

    /**
     * The timestamp to start from, the live edge rounded down to a segment boundary minus the playback
     * buffer, see [stream mode](https://core.telegram.org/api/group-calls#stream-mode): of the unified
     * channel of an RTMP stream (null if it has no chunk yet: ask again in a second), else of the
     * server clock.
     */
    private function liveEdge(): ?int
    {
        if (!$this->rtmp) {
            return intdiv(self::nowMs(), self::SEGMENT_MS) * self::SEGMENT_MS - self::BUFFER_MS;
        }
        $edge = null;
        foreach ($this->call->API->getGroupCallStreamChannels($this->call->public->id) as $channel) {
            // The video of an RTMP stream starts a few seconds after its audio, on its unified channel
            // 1 (sometimes listed twice, with different edges: take the one that is surely ready).
            if ($channel['channel'] === 1) {
                $edge = min($edge ?? PHP_INT_MAX, $channel['last_timestamp_ms']);
            }
        }
        if ($edge === null) {
            return null;
        }
        $start = intdiv($edge, self::SEGMENT_MS) * self::SEGMENT_MS - self::BUFFER_MS;
        return $start > 0 ? $start : null;
    }

    /**
     * The local clock in milliseconds: the wall clock, as the timestamps of the chunks.
     */
    private static function nowMs(): int
    {
        return (int) round(microtime(true) * 1000.0);
    }

    /**
     * Download one chunk, or null on a CDN redirect.
     */
    /**
     * @return ?array{bytes: string, partial: bool}
     */
    private function fetch(int $timestamp): ?array
    {
        return $this->call->API->downloadGroupCallStreamChunk(
            $this->call->public->id,
            $timestamp,
            0,
            // An RTMP stream is a single unified channel carrying both audio and video.
            $this->rtmp ? 1 : null,
            $this->rtmp ? 2 : null,
        );
    }

    /**
     * Download one publisher's video chunk of an automatically-scaled livestream, or null if it is
     * not available.
     */
    private function fetchVideo(int $timestamp, int $channel): ?string
    {
        try {
            return $this->call->API->downloadGroupCallStreamChunk($this->call->public->id, $timestamp, 0, $channel, 2)['bytes'] ?? null;
        } catch (Throwable $e) {
            $this->call->log("Could not download video chunk $channel of {$this->call}: $e", Logger::VERBOSE);
            return null;
        }
    }

    private function consume(string $chunk, int $timestamp): void
    {
        $container = 'ogg';
        if ($this->rtmp) {
            $unified = self::parseUnifiedHeader($chunk);
            if ($unified === null) {
                throw new \RuntimeException('Malformed unified chunk header');
            }
            [$container, $chunk] = $unified;
        }
        $this->recordingStart ??= $timestamp;
        if ($container === 'ogg') {
            $this->consumeOgg($chunk);
            return;
        }
        if ($container === 'matroska' || $container === 'webm') {
            $matroska = new Matroska(new ReadableBuffer($chunk));
            $this->consumeFrames($matroska->frames, $matroska->tracks, $timestamp);
            return;
        }
        if ($container === 'mp4' || $container === 'mov' || $container === 'isom') {
            $mp4 = new Mp4(new ReadableBuffer($chunk));
            $this->consumeFrames($mp4->frames, $mp4->tracks, $timestamp);
            return;
        }
        if (!$this->unsupportedLogged) {
            $this->unsupportedLogged = true;
            $this->call->log("The stream of {$this->call} uses the \"$container\" container, which cannot be demuxed: nothing will be recorded.", Logger::WARNING);
        }
    }

    /**
     * Fetch and record the video of every active publisher of an automatically-scaled livestream,
     * each into its own `video-<endpoint>.mkv` in the recording directory.
     */
    private function consumePublishersVideo(int $timestamp): void
    {
        foreach ($this->publishers as $channel => ['endpoint' => $endpoint, 'active' => $active]) {
            if (!$active) {
                continue;
            }
            $chunk = $this->fetchVideo($timestamp, $channel);
            if ($chunk === null) {
                continue;
            }
            try {
                $unified = self::parseUnifiedHeader($chunk);
                if ($unified === null) {
                    continue;
                }
                [$container, $data] = $unified;
                if ($container === 'mp4' || $container === 'mov' || $container === 'isom') {
                    $demuxer = new Mp4(new ReadableBuffer($data));
                } elseif ($container === 'matroska' || $container === 'webm') {
                    $demuxer = new Matroska(new ReadableBuffer($data));
                } else {
                    continue;
                }
                $this->writePublisherVideo($channel, $endpoint, $demuxer->frames, $demuxer->tracks, $timestamp);
            } catch (Throwable $e) {
                $this->call->log("Could not demux the video of $endpoint in {$this->call}: $e", Logger::WARNING);
            }
        }
    }

    /**
     * Strip the header of a unified chunk: `signature:int32 container:string active_mask:int32
     * events:int32 (offset:int32 endpoint:string rotation:int32 extra:int32)*`, strings being
     * TL-style (length byte, or 254 + 3-byte length, then padding to 4 bytes).
     *
     * @return array{0: string, 1: string}|null The container name and the container data.
     */
    private static function parseUnifiedHeader(string $data): ?array
    {
        $offset = 0;
        $int = static function () use ($data, &$offset): ?int {
            /** @var int $offset */
            if ($offset + 4 > \strlen($data)) {
                return null;
            }
            $value = (int) unpack('l', substr($data, $offset, 4))[1];
            $offset += 4;
            return $value;
        };
        $string = static function () use ($data, &$offset): ?string {
            /** @var int $offset */
            if ($offset >= \strlen($data)) {
                return null;
            }
            $first = \ord($data[$offset]);
            if ($first === 254) {
                $length = (int) unpack('V', substr($data, $offset + 1, 3)."\0")[1];
                $offset += 4;
                $padding = (4 - $length % 4) % 4;
            } else {
                $length = $first;
                $offset += 1;
                $padding = (4 - ($length + 1) % 4) % 4;
            }
            if ($offset + $length > \strlen($data)) {
                return null;
            }
            $value = substr($data, $offset, $length);
            $offset += $length + $padding;
            return $value;
        };
        // Unsigned: unpack('l') reads the signature as a negative number.
        if ((($int() ?? 0) & 0xFFFFFFFF) !== self::UNIFIED_SIGNATURE) {
            return null;
        }
        $container = $string();
        if ($container === null || $int() === null) { // active mask
            return null;
        }
        $events = $int();
        if ($events === null) {
            return null;
        }
        for ($i = 0; $i < $events; $i++) {
            if ($int() === null || $string() === null || $int() === null || $int() === null) {
                return null;
            }
        }
        return [$container, substr($data, $offset)];
    }

    private function consumeOgg(string $chunk): void
    {
        foreach (OggDemuxer::demux($chunk) as $stream) {
            if ($stream['packets'] === []) {
                continue;
            }
            if ($stream['tags'] !== null) {
                $this->updatePublishers(OggDemuxer::opusComments($stream['tags']));
            }
            $this->ensureWriters($stream['head'] ?? OggDemuxer::opusHead(2), 'A_OPUS', null);
            foreach ($stream['packets'] as $packet) {
                $this->writeAudio($packet['data'], $packet['samples']);
            }
            // A chunk carries one mixed audio stream; any further logical stream would be a duplicate.
            return;
        }
    }

    /**
     * Track the publishers an audio chunk announces: `ENDPOINTS` lists them (their index plus one is
     * their video channel), `ACTIVE_MASK` which of them are transmitting video.
     *
     * @param array<string, string> $comments
     */
    private function updatePublishers(array $comments): void
    {
        if (!isset($comments['ENDPOINTS'])) {
            return;
        }
        $endpoints = array_values(array_filter(explode(' ', $comments['ENDPOINTS']), static fn (string $e): bool => $e !== ''));
        $mask = (int) ($comments['ACTIVE_MASK'] ?? 0);
        $publishers = [];
        foreach ($endpoints as $index => $endpoint) {
            $publishers[$index + 1] = ['endpoint' => $endpoint, 'active' => ($mask & (1 << $index)) !== 0];
        }
        $this->publishers = $publishers;
    }

    /**
     * Record the audio (and, into a Matroska file, the video) frames of a demuxed unified chunk.
     *
     * @param iterable<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}> $frames
     * @param array<int, array<string, mixed>> $tracks
     */
    private function consumeFrames(iterable $frames, array $tracks, int $timestamp): void
    {
        if ($this->ogg === null && $this->mkv === null && $this->outputFile !== null) {
            // The recording can't get a video track once opened, and the first chunks of an RTMP
            // stream often have none: hold them for a while, waiting for the video.
            if ($this->rtmp && self::videoTrack($tracks) === null && \count($this->audioOnlyChunks) < self::MAX_AUDIO_ONLY_CHUNKS) {
                $this->audioOnlyChunks[] = [iterator_to_array($frames, false), $tracks, $timestamp];
                return;
            }
            $this->openWriters($tracks);
            $held = $this->audioOnlyChunks;
            $this->audioOnlyChunks = [];
            foreach ($held as [$heldFrames, $heldTracks, $heldTimestamp]) {
                $this->writeFrames($heldFrames, $heldTracks, $heldTimestamp);
            }
        }
        $this->writeFrames($frames, $tracks, $timestamp);
    }

    /**
     * Open the main recording, from the tracks of a chunk.
     *
     * @param array<int, array<string, mixed>> $tracks
     */
    private function openWriters(array $tracks): void
    {
        foreach ($tracks as $track) {
            if (($track['type'] ?? 0) !== Matroska::TRACK_TYPE_AUDIO) {
                continue;
            }
            $private = isset($track['private']) && \is_string($track['private']) ? $track['private'] : '';
            if ($track['codec'] === 'A_OPUS') {
                $this->ensureWriters(str_starts_with($private, 'OpusHead') ? $private : OggDemuxer::opusHead(2), 'A_OPUS', self::videoTrack($tracks));
            } elseif ($track['codec'] === 'A_AAC' && ($this->outputFormat ?? RecordingFormat::Opus)->isMatroska()) {
                $this->ensureWriters($private, 'A_AAC', self::videoTrack($tracks), (int) ($track['rate'] ?? 48000), (int) ($track['channels'] ?? 2));
            }
            return;
        }
    }

    /**
     * @param iterable<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}> $frames
     * @param array<int, array<string, mixed>> $tracks
     */
    private function writeFrames(iterable $frames, array $tracks, int $timestamp): void
    {
        $chunkBase = null;
        foreach ($frames as $frame) {
            $chunkBase ??= $frame['timestamp'];
            if ($frame['type'] === Matroska::TRACK_TYPE_AUDIO) {
                if ($frame['codec'] === 'A_OPUS') {
                    $this->writeAudio($frame['data'], OggDemuxer::opusSamples($frame['data']));
                } elseif ($frame['codec'] === 'A_AAC' && ($this->outputFormat ?? RecordingFormat::Opus)->isMatroska()) {
                    $this->writeAudioAt($frame['data'], $timestamp - ($this->recordingStart ?? $timestamp) + ($frame['timestamp'] - $chunkBase));
                }
                continue;
            }
            if ($frame['type'] === Matroska::TRACK_TYPE_VIDEO && $this->mkv !== null && $this->mainHasVideo) {
                $this->mkv->writeVideo($frame['data'], $timestamp - ($this->recordingStart ?? $timestamp) + ($frame['timestamp'] - $chunkBase), $frame['keyframe']);
            }
        }
    }

    /**
     * The first video track of a chunk, if any.
     *
     * @param array<int, array<string, mixed>> $tracks
     *
     * @return array<string, mixed>|null
     *
     * @psalm-pure
     */
    private static function videoTrack(array $tracks): ?array
    {
        foreach ($tracks as $track) {
            if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO) {
                return $track;
            }
        }
        return null;
    }

    /**
     * Open the main recording on the first frame: OGG OPUS, or Matroska with an audio track and, for
     * a unified (RTMP) stream, its video track.
     *
     * @param array<string, mixed>|null $video
     */
    private function ensureWriters(string $audioPrivate, string $audioCodec, ?array $video, int $rate = 48000, int $channels = 0): void
    {
        if ($this->outputFile === null || $this->ogg !== null || $this->mkv !== null) {
            return;
        }
        if ($audioCodec === 'A_OPUS') {
            $rate = 48000;
            $channels = OggDemuxer::opusChannels($audioPrivate);
        }
        // Writing to the file itself (not a stream opened on it), the writers can reopen it after a restart.
        $this->out = $this->outputFile instanceof LocalFile ? null : $this->outputFile;
        $this->writtenMs = 0;
        $format = $this->outputFormat ?? RecordingFormat::Opus;
        if ($format->isMatroska()) {
            $this->mkv = new MatroskaWriter($this->outputFile, $format->docType());
            $this->mkv->setAudioTrack($audioCodec, $rate, max(1, $channels), $audioPrivate);
            $this->mainCodecs = [CallStream::AUDIO => $audioCodec];
            if ($video !== null && (int) ($video['width'] ?? 0) > 0) {
                $this->mkv->setVideoTrack((string) $video['codec'], (int) $video['width'], (int) ($video['height'] ?? 0), (string) ($video['private'] ?? ''));
                $this->mainHasVideo = true;
                $this->mainCodecs[CallStream::VIDEO] = (string) $video['codec'];
            }
            $this->mkv->start();
            $this->reportMain(RecordingEvent::Started);
            return;
        }
        if ($audioCodec !== 'A_OPUS') {
            throw new \RuntimeException("Only OPUS audio can be recorded to OGG; record this $audioCodec stream to a Matroska file instead.");
        }
        $this->ogg = new OggWriter($this->outputFile);
        $this->ogg->writeHeader($channels, 48000, 'livestream', $audioPrivate);
        $this->mainCodecs = [CallStream::AUDIO => $audioCodec];
        $this->reportMain(RecordingEvent::Started);
    }

    /**
     * Report the mixed stream's recording starting or ending as a {@see \danog\MadelineProto\EventHandler\Calls\CallStreams}
     * update of participant 0 (there are no per-participant sources in stream mode).
     */
    private function reportMain(RecordingEvent $event): void
    {
        $streams = CallStream::AUDIO | ($this->mainHasVideo ? CallStream::VIDEO : 0);
        $this->call->onParticipantStreams(0, $streams, $this->mainCodecs, $event, $this->outputFile instanceof LocalFile ? $this->outputFile : null);
    }

    private function writeAudio(string $packet, int $samples): void
    {
        if ($this->ogg !== null) {
            $this->ogg->writeChunk($packet, $samples, false);
        } elseif ($this->mkv !== null) {
            $this->mkv->writeAudio($packet, $this->writtenMs);
        }
        $this->writtenMs += intdiv($samples, 48);
    }

    private function writeAudioAt(string $packet, int $timestampMs): void
    {
        $this->mkv?->writeAudio($packet, max(0, $timestampMs));
    }

    /**
     * Record one publisher's video chunk into its own Matroska file, opened on its first chunk (and
     * reopened if the endpoint behind the channel changes).
     *
     * @param iterable<array{track: int, codec: string, type: int, data: string, timestamp: int, keyframe: bool}> $frames
     * @param array<int, array<string, mixed>> $tracks
     */
    private function writePublisherVideo(int $channel, string $endpoint, iterable $frames, array $tracks, int $timestamp): void
    {
        $dir = $this->outputDir;
        if ($dir === null) {
            return;
        }
        $writer = $this->videoWriters[$channel] ?? null;
        if ($writer !== null && $writer['endpoint'] !== $endpoint) {
            $this->closeVideoWriter($channel);
            $writer = null;
        }
        $chunkBase = null;
        foreach ($frames as $frame) {
            $chunkBase ??= $frame['timestamp'];
            if ($frame['type'] !== Matroska::TRACK_TYPE_VIDEO) {
                continue;
            }
            if ($writer === null) {
                $track = $tracks[$frame['track']] ?? null;
                if ($track === null || (int) ($track['width'] ?? 0) <= 0) {
                    return;
                }
                $file = $dir.'/video-'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $endpoint).'.mkv';
                $mkv = new MatroskaWriter(new LocalFile($file), RecordingFormat::Mkv->docType());
                $mkv->setVideoTrack((string) $track['codec'], (int) $track['width'], (int) ($track['height'] ?? 0), (string) ($track['private'] ?? ''));
                $mkv->start();
                $writer = ['endpoint' => $endpoint, 'writer' => $mkv, 'start' => $timestamp, 'started' => true, 'file' => $file, 'codec' => (string) $track['codec']];
                $this->videoWriters[$channel] = $writer;
                $this->call->onParticipantStreams(0, CallStream::VIDEO, [CallStream::VIDEO => $writer['codec']], RecordingEvent::Started, new LocalFile($file));
            }
            $writer['writer']->writeVideo($frame['data'], $timestamp - $writer['start'] + ($frame['timestamp'] - $chunkBase), $frame['keyframe']);
        }
    }

    private function closeVideoWriter(int $channel): void
    {
        $writer = $this->videoWriters[$channel] ?? null;
        if ($writer === null) {
            return;
        }
        unset($this->videoWriters[$channel]);
        try {
            $writer['writer']->close();
        } catch (Throwable $e) {
            $this->call->log("Could not close the video recording of {$writer['endpoint']} in {$this->call}: $e", Logger::WARNING);
        }
        $this->call->onParticipantStreams(0, CallStream::VIDEO, [CallStream::VIDEO => $writer['codec']], RecordingEvent::Ended, new LocalFile($writer['file']));
    }

    private function closeWriters(): void
    {
        $open = $this->ogg !== null || $this->mkv !== null;
        try {
            if ($this->ogg !== null) {
                $this->ogg->writeChunk('', 0, true);
            } elseif ($this->mkv !== null) {
                $this->mkv->close();
            } elseif ($this->out !== null && $this->outputFile instanceof LocalFile) {
                $this->out->close();
            }
        } catch (Throwable $e) {
            $this->call->log("Could not close the stream recording of {$this->call}: $e", Logger::WARNING);
        }
        $this->ogg = null;
        $this->mkv = null;
        $this->out = null;
        if ($open) {
            $this->reportMain(RecordingEvent::Ended);
        }
        $this->mainHasVideo = false;
        $this->mainCodecs = [];
        $this->recordingStart = null;
        $this->audioOnlyChunks = [];
        foreach (array_keys($this->videoWriters) as $channel) {
            $this->closeVideoWriter($channel);
        }
    }
}
