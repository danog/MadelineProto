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
 * media chunks from the stream DC every second and records the mixed audio into an OGG OPUS or a
 * Matroska file. Video chunks (per-publisher MP4 segments) are not demuxed.
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
    private ?RecordingFormat $outputFormat = null;
    private ?OggWriter $ogg = null;
    private ?MatroskaWriter $mkv = null;
    private ?WritableStream $out = null;
    /** Milliseconds of audio written so far, for Matroska timestamps. */
    private int $writtenMs = 0;
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
        $this->outputFile = $file;
        $this->outputFormat = $format ?? ($file instanceof LocalFile ? RecordingFormat::fromFile($file) : RecordingFormat::Opus);
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
                $this->consume($chunk);
            } catch (Throwable $e) {
                $this->call->log("Could not demux a chunk of {$this->call}: $e", Logger::WARNING);
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
            $this->rtmp ? 0 : null,
        );
    }

    private function consume(string $chunk): void
    {
        $container = 'ogg';
        if ($this->rtmp) {
            $unified = self::parseUnifiedHeader($chunk);
            if ($unified === null) {
                throw new \RuntimeException('Malformed unified chunk header');
            }
            [$container, $chunk] = $unified;
        }
        if ($container === 'ogg') {
            $this->consumeOgg($chunk);
            return;
        }
        if ($container === 'matroska' || $container === 'webm') {
            $this->consumeMatroska($chunk);
            return;
        }
        if (!$this->unsupportedLogged) {
            $this->unsupportedLogged = true;
            $this->call->log("The stream of {$this->call} uses the \"$container\" container, which cannot be demuxed: nothing will be recorded.", Logger::WARNING);
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
            $this->ensureWriters($stream['head'] ?? OggDemuxer::opusHead(2));
            foreach ($stream['packets'] as $packet) {
                $this->writeAudio($packet['data'], $packet['samples']);
            }
            // A chunk carries one mixed audio stream; any further logical stream would be a duplicate.
            return;
        }
    }

    private function consumeMatroska(string $chunk): void
    {
        $matroska = new Matroska(new ReadableBuffer($chunk));
        foreach ($matroska->frames as $frame) {
            if ($frame['type'] !== Matroska::TRACK_TYPE_AUDIO || $frame['codec'] !== 'A_OPUS') {
                continue;
            }
            $private = $matroska->tracks[$frame['track']]['private'] ?? null;
            $this->ensureWriters(\is_string($private) && str_starts_with($private, 'OpusHead') ? $private : OggDemuxer::opusHead(2));
            $this->writeAudio($frame['data'], OggDemuxer::opusSamples($frame['data']));
        }
    }

    private function ensureWriters(string $opusHead): void
    {
        if ($this->outputFile === null || $this->ogg !== null || $this->mkv !== null) {
            return;
        }
        $channels = OggDemuxer::opusChannels($opusHead);
        $this->out = $this->outputFile instanceof LocalFile ? openFile($this->outputFile->file, 'w') : $this->outputFile;
        $this->writtenMs = 0;
        $format = $this->outputFormat ?? RecordingFormat::Opus;
        if ($format->isMatroska()) {
            $this->mkv = new MatroskaWriter($this->out, $format->docType());
            $this->mkv->setAudioTrack('A_OPUS', 48000, $channels, $opusHead);
            $this->mkv->start();
            return;
        }
        $this->ogg = new OggWriter($this->out);
        $this->ogg->writeHeader($channels, 48000, 'livestream', $opusHead);
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
    }
}
