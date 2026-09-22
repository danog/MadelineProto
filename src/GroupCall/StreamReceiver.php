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
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\MatroskaWriter;
use danog\MadelineProto\Mp4;
use danog\MadelineProto\OggWriter;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RPCError\RateLimitError;
use danog\MadelineProto\RPCErrorException;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;
use function Amp\File\openFile;

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
 * As official clients do, the stream is followed one 1000 ms segment at a time, 2 seconds behind the
 * live edge; a chunk that is not ready yet (`TIME_TOO_BIG`, a flood wait) is retried after 100 ms, any
 * other error resynchronizes to the live edge, and being told we are not in the call any more
 * (`GROUPCALL_JOIN_MISSING` / `GROUPCALL_FORBIDDEN`) rejoins it.
 *
 * @internal
 */
final class StreamReceiver
{
    /** Chunk length: official clients hardcode 1000 ms (the deprecated `scale` is ignored). */
    private const SEGMENT_MS = 1000;
    /** How far behind the live edge playback stays, so chunks are ready when asked for. */
    private const BUFFER_MS = 2000;
    /** Header signature of a unified (RTMP) chunk, which is followed by a container name and events. */
    private const UNIFIED_SIGNATURE = 0xa12e810d;
    private const RETRY_MS = 100;

    private bool $running = false;
    private bool $rtmp = false;
    private int $generation = 0;
    private ?int $nextTimestamp = null;

    private LocalFile|WritableStream|null $outputFile = null;
    /** Directory to record into (folder mode), if any. */
    private ?string $outputDir = null;
    private ?RecordingFormat $outputFormat = null;
    private ?OggWriter $ogg = null;
    private ?MatroskaWriter $mkv = null;
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
     * @var array<int, array{endpoint: string, writer: MatroskaWriter, out: WritableStream, start: int, started: bool}>
     */
    private array $videoWriters = [];
    private bool $unsupportedLogged = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly GroupCallController $call,
    ) {
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
    public function setOutputDirectory(string $dir, ?RecordingFormat $format = null): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0o777, true) && !is_dir($dir)) {
            throw new \RuntimeException("Could not create the recording directory $dir");
        }
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
            if ($this->nextTimestamp === null) {
                $this->nextTimestamp = $this->liveEdge();
            }
            $timestamp = $this->nextTimestamp;
            // Stay behind the live edge: never ask for a chunk that cannot be complete yet.
            $due = $timestamp + self::SEGMENT_MS + self::BUFFER_MS;
            $now = self::nowMs();
            if ($due > $now) {
                delay(($due - $now) / 1000);
                continue;
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
                if ($e->rpc === 'TIME_TOO_BIG' || $e instanceof RateLimitError) {
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
            $this->nextTimestamp = $timestamp + self::SEGMENT_MS;
            try {
                $this->consume($chunk, $timestamp);
            } catch (Throwable $e) {
                $this->call->log("Could not demux a chunk of {$this->call}: $e", Logger::WARNING);
            }
            if (!$this->rtmp && $this->outputDir !== null) {
                $this->consumePublishersVideo($timestamp);
            }
        }
    }

    /**
     * The timestamp to start from: the live edge (from phone.getGroupCallStreamChannels where the server
     * offers it, else our clock) rounded down to a segment boundary, minus the playback buffer.
     */
    private function liveEdge(): int
    {
        $edge = null;
        try {
            foreach ($this->call->API->getGroupCallStreamChannels($this->call->public->id) as $channel) {
                $edge = max($edge ?? 0, $channel['last_timestamp_ms']);
            }
        } catch (Throwable $e) {
            $this->call->log("Could not fetch the stream channels of {$this->call}, using the local clock: $e", Logger::VERBOSE);
        }
        $edge ??= self::nowMs();
        return intdiv($edge, self::SEGMENT_MS) * self::SEGMENT_MS - self::BUFFER_MS;
    }

    /**
     * The local clock in milliseconds.
     */
    private static function nowMs(): int
    {
        return (int) round(microtime(true) * 1000.0);
    }

    /**
     * Download one chunk, or null on a CDN redirect.
     */
    private function fetch(int $timestamp): ?string
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
            return $this->call->API->downloadGroupCallStreamChunk($this->call->public->id, $timestamp, 0, $channel, 2);
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
        if ($int() !== self::UNIFIED_SIGNATURE) {
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
        $chunkBase = null;
        foreach ($frames as $frame) {
            $chunkBase ??= $frame['timestamp'];
            $track = $tracks[$frame['track']] ?? null;
            if ($frame['type'] === Matroska::TRACK_TYPE_AUDIO) {
                if ($frame['codec'] === 'A_OPUS') {
                    $private = isset($track['private']) && \is_string($track['private']) ? $track['private'] : '';
                    $this->ensureWriters(str_starts_with($private, 'OpusHead') ? $private : OggDemuxer::opusHead(2), 'A_OPUS', self::videoTrack($tracks));
                    $this->writeAudio($frame['data'], OggDemuxer::opusSamples($frame['data']));
                } elseif ($frame['codec'] === 'A_AAC' && ($this->outputFormat ?? RecordingFormat::Opus)->isMatroska()) {
                    $this->ensureWriters((string) ($track['private'] ?? ''), 'A_AAC', self::videoTrack($tracks), (int) ($track['rate'] ?? 48000), (int) ($track['channels'] ?? 2));
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
        $this->out = $this->outputFile instanceof LocalFile ? openFile($this->outputFile->file, 'w') : $this->outputFile;
        $this->writtenMs = 0;
        $format = $this->outputFormat ?? RecordingFormat::Opus;
        if ($format->isMatroska()) {
            $this->mkv = new MatroskaWriter($this->out, $format->docType());
            $this->mkv->setAudioTrack($audioCodec, $rate, max(1, $channels), $audioPrivate);
            if ($video !== null && (int) ($video['width'] ?? 0) > 0) {
                $this->mkv->setVideoTrack((string) $video['codec'], (int) $video['width'], (int) ($video['height'] ?? 0), (string) ($video['private'] ?? ''));
                $this->mainHasVideo = true;
            }
            $this->mkv->start();
            return;
        }
        if ($audioCodec !== 'A_OPUS') {
            throw new \RuntimeException("Only OPUS audio can be recorded to OGG; record this $audioCodec stream to a Matroska file instead.");
        }
        $this->ogg = new OggWriter($this->out);
        $this->ogg->writeHeader($channels, 48000, 'livestream', $audioPrivate);
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
                $out = openFile($file, 'w');
                $mkv = new MatroskaWriter($out, RecordingFormat::Mkv->docType());
                $mkv->setVideoTrack((string) $track['codec'], (int) $track['width'], (int) ($track['height'] ?? 0), (string) ($track['private'] ?? ''));
                $mkv->start();
                $writer = ['endpoint' => $endpoint, 'writer' => $mkv, 'out' => $out, 'start' => $timestamp, 'started' => true];
                $this->videoWriters[$channel] = $writer;
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
    }

    private function closeWriters(): void
    {
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
        $this->mainHasVideo = false;
        $this->recordingStart = null;
        foreach (array_keys($this->videoWriters) as $channel) {
            $this->closeVideoWriter($channel);
        }
    }
}
