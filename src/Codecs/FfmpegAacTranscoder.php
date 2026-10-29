<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Codecs;

use Amp\Future;
use Amp\Process\Process;
use AssertionError;
use Closure;
use danog\MadelineProto\GroupCall\Rtmp\FlvTags;
use danog\MadelineProto\Ogg;
use FFI;
use FFI\CData;

use function Amp\async;
use function Amp\ByteStream\buffer;

/**
 * Transcodes AAC to OPUS with an ffmpeg process (fed ADTS, decoding and resampling to 48 kHz) and libopus.
 *
 * @internal
 */
final class FfmpegAacTranscoder extends AacTranscoder
{
    private const OPUS_APPLICATION_AUDIO = 2049;
    private const OPUS_SET_BITRATE_REQUEST = 4002;
    private const OPUS_SET_COMPLEXITY_REQUEST = 4010;
    private const OPUS_SET_SIGNAL_REQUEST = 4024;
    private const OPUS_SIGNAL_MUSIC = 3002;
    /** The OPUS bitrate, per channel. */
    private const BITRATE_PER_CHANNEL = 64000;
    private const MAX_PACKET = 1275;

    private readonly Process $process;
    private readonly FFI $opus;
    private readonly CData $encoder;
    /** The output buffer of libopus, and a pointer to it. */
    private readonly CData $buffer;
    private readonly CData $bufferPointer;
    /** Decoded samples not encoded yet: interleaved signed 16-bit little endian. */
    private string $pending = '';
    /** Decoded bytes still to drop. */
    private int $toSkip = 0;
    /** @var Future<null> The reader of ffmpeg's output. */
    private readonly Future $reader;
    /** @var Future<string> ffmpeg's error output. */
    private readonly Future $errors;
    private bool $closed = false;
    /** Whether finish() is draining ffmpeg: it encodes the rest itself. */
    private bool $finishing = false;

    /**
     * @param int<1, 2> $channels
     * @param Closure(string, int): void $sink
     */
    public function __construct(private readonly string $config, int $sampleRate, int $channels, Closure $sink)
    {
        parent::__construct($sampleRate, $channels, $sink);
        // Fails right away if ADTS can't carry the configuration.
        FlvTags::adtsHeader($config, 0);
        $this->opus = Ogg::libopus();
        $error = $this->opus->new('int');
        $encoder = $this->opus->opus_encoder_create(48000, $channels, self::OPUS_APPLICATION_AUDIO, FFI::addr($error));
        if ($error->cdata < 0 || $encoder === null) {
            throw new AssertionError('Could not create the OPUS encoder: '.$this->opus->opus_strerror($error->cdata));
        }
        $this->encoder = $encoder;
        $this->opus->opus_encoder_ctl($encoder, self::OPUS_SET_BITRATE_REQUEST, self::BITRATE_PER_CHANNEL * $channels);
        $this->opus->opus_encoder_ctl($encoder, self::OPUS_SET_COMPLEXITY_REQUEST, 10);
        $this->opus->opus_encoder_ctl($encoder, self::OPUS_SET_SIGNAL_REQUEST, self::OPUS_SIGNAL_MUSIC);
        $this->buffer = $this->opus->new('char['.self::MAX_PACKET.']');
        $this->bufferPointer = $this->opus->cast($this->opus->type('char*'), FFI::addr($this->buffer));
        $this->process = Process::start([
            'ffmpeg', '-hide_banner', '-loglevel', 'error',
            // Output each frame as soon as it's decoded: no probing ahead, no buffering of the input or output.
            '-probesize', '32', '-analyzeduration', '0', '-fflags', '+nobuffer', '-flags', 'low_delay',
            '-f', 'aac', '-i', 'pipe:0',
            '-f', 's16le', '-ar', '48000', '-ac', (string) $channels, '-flush_packets', '1', 'pipe:1',
        ]);
        $stdout = $this->process->getStdout();
        $this->reader = async(function () use ($stdout): void {
            while (($chunk = $stdout->read()) !== null) {
                $skip = min($this->toSkip, \strlen($chunk));
                $this->toSkip -= $skip;
                $this->pending .= $skip ? substr($chunk, $skip) : $chunk;
                if (!$this->closed && !$this->finishing) {
                    // As soon as ffmpeg outputs them: the reader may be waiting for these packets to be played.
                    $this->flush(false);
                }
            }
        });
        // Awaited at the end of the stream: not reported if the process is killed before.
        $this->reader->ignore();
        $this->errors = async(buffer(...), $this->process->getStderr());
        $this->errors->ignore();
    }

    #[\Override]
    public function describe(): string
    {
        return 'ffmpeg and libopus';
    }

    #[\Override]
    public function latencyMs(): int
    {
        // ffmpeg decodes a frame once it sees the next one's header, then resamples: 50-60 ms, measured.
        return 120;
    }

    #[\Override]
    protected function feed(string $frame): void
    {
        $this->process->getStdin()->write(FlvTags::adtsHeader($this->config, \strlen($frame)).$frame);
    }

    #[\Override]
    protected function skip(int $samples): void
    {
        $bytes = 2 * $this->channels * $samples;
        $drop = min($bytes, \strlen($this->pending));
        $this->pending = substr($this->pending, $drop);
        $this->toSkip += $bytes - $drop;
    }

    #[\Override]
    protected function encode(bool $final): array
    {
        if ($final) {
            // Everything ffmpeg has yet to output.
            $this->finishing = true;
            $this->process->getStdin()->close();
            $this->reader->await();
            $code = $this->process->join();
            if ($code !== 0) {
                throw new AssertionError("ffmpeg could not decode the AAC audio (exit code $code): ".trim($this->errors->await()));
            }
        }
        $frameBytes = 2 * $this->channels * self::FRAME_SAMPLES;
        $packets = [];
        while (\strlen($this->pending) >= $frameBytes || ($final && $this->pending !== '')) {
            $pcm = str_pad(substr($this->pending, 0, $frameBytes), $frameBytes, "\0");
            $this->pending = (string) substr($this->pending, $frameBytes);
            $length = $this->opus->opus_encode($this->encoder, $pcm, self::FRAME_SAMPLES, $this->bufferPointer, self::MAX_PACKET);
            if ($length < 0) {
                throw new AssertionError('libopus returned: '.$this->opus->opus_strerror($length));
            }
            $packets[] = FFI::string($this->bufferPointer, $length);
        }
        return $packets;
    }

    #[\Override]
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        if ($this->process->isRunning()) {
            $this->process->kill();
        }
        $this->opus->opus_encoder_destroy($this->encoder);
    }

    public function __destruct()
    {
        $this->close();
    }
}
