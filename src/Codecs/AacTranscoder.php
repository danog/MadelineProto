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

use Closure;
use danog\MadelineProto\Codecs\Aac\BitReader;
use danog\MadelineProto\Tools;
use Throwable;

/**
 * Transcodes the AAC audio of an MP4 or Matroska file to 20 ms OPUS packets at 48 kHz, for the calls that can only
 * carry OPUS, keeping the timestamps of the AAC frames.
 *
 * The packets are handed to a sink as soon as they are encoded, which for ffmpeg is when it outputs them, not when the
 * next AAC frame is pushed: a reader blocked until the packets are played would otherwise wait for them forever.
 *
 * With ffmpeg and libopus ({@see Tools::canConvertOgg()}), ffmpeg decodes and resamples and libopus encodes;
 * otherwise, everything is done in pure PHP ({@see Aac\AacDecoder}, {@see Resampler}, {@see Opus\OpusEncoder}).
 *
 * @internal
 */
abstract class AacTranscoder
{
    /** Samples per OPUS packet, at 48 kHz. */
    public const FRAME_SAMPLES = 960;

    /** The timestamp of the first AAC frame, in milliseconds, until the second one tells whether it is priming. */
    private ?int $firstMs = null;
    /** Where the OPUS packets start, at 48 kHz: the timestamp of the first AAC frame that isn't priming. */
    private ?int $anchor = null;
    /** OPUS packets produced so far. */
    private int $packets = 0;

    /**
     * @param int $sampleRate The AAC stream's (core) sampling rate.
     * @param int<1, 2> $channels The OPUS packets' channels.
     * @param Closure(string, int): void $sink Takes each OPUS packet, with its timestamp at 48 kHz.
     */
    protected function __construct(protected readonly int $sampleRate, public readonly int $channels, private readonly Closure $sink)
    {
    }

    /**
     * A transcoder for the AAC stream of this AudioSpecificConfig: ffmpeg and libopus if available, pure PHP otherwise.
     *
     * @param Closure(string, int): void $sink Takes each OPUS packet, with its timestamp at 48 kHz.
     */
    public static function create(string $config, Closure $sink): self
    {
        [$sampleRate, $channels] = self::parseConfig($config);
        $channels = min(2, max(1, $channels));
        if (Tools::canConvertOgg()) {
            try {
                return new FfmpegAacTranscoder($config, $sampleRate, $channels, $sink);
            } catch (Throwable) {
                // Fall back to PHP.
            }
        }
        return new PhpAacTranscoder($config, $sink);
    }

    /**
     * The sampling rate (of the AAC-LC core) and channel configuration of an AudioSpecificConfig.
     *
     * @return array{int, int}
     */
    private static function parseConfig(string $config): array
    {
        $bits = new BitReader($config);
        if ($bits->read(5) === 31) {
            $bits->read(6);
        }
        $index = $bits->read(4);
        $rate = $index === 15 ? $bits->read(24) : [96000, 88200, 64000, 48000, 44100, 32000, 24000, 22050, 16000, 12000, 11025, 8000, 7350][$index] ?? 48000;
        return [$rate, $bits->read(4)];
    }

    /** What transcodes, for the logs. */
    abstract public function describe(): string;

    /**
     * How much later than the AAC frames fed the OPUS packets come out, in milliseconds: the audio to keep queued so
     * that the next packet is ready when it is due.
     */
    abstract public function latencyMs(): int;

    /** Decode an AAC frame. */
    abstract protected function feed(string $frame): void;

    /** Drop the next $samples decoded samples (per channel, at 48 kHz). */
    abstract protected function skip(int $samples): void;

    /**
     * Encode the complete 20 ms frames decoded so far; at the end, also the last partial one, padded with silence.
     *
     * @return list<string>
     */
    abstract protected function encode(bool $final): array;

    /** Release the resources (the ffmpeg process). */
    abstract public function close(): void;

    /**
     * Transcode an AAC frame.
     *
     * @param int $timestampMs The AAC frame's timestamp, in milliseconds.
     */
    public function push(string $frame, int $timestampMs): void
    {
        if ($this->anchor === null) {
            if ($this->firstMs === null) {
                // Held until the next frame's timestamp says whether this one is the encoder's priming.
                $this->firstMs = $timestampMs;
                $this->feed($frame);
                return;
            }
            // MP4 edit lists cut the 1024 priming samples by shifting the timestamps back by a frame, the first one
            // to (or below) 0: two frames then share the first timestamp, and the first one's output is the priming.
            if (($timestampMs - $this->firstMs) * $this->sampleRate < 512 * 1000) {
                $this->skip(intdiv(1024 * 48000, $this->sampleRate));
                $this->anchor = $timestampMs * 48;
            } else {
                $this->anchor = $this->firstMs * 48;
            }
        }
        $this->feed($frame);
        $this->flush(false);
    }

    /**
     * The end of the stream: the rest of the audio.
     */
    public function finish(): void
    {
        if ($this->firstMs === null) {
            return;
        }
        $this->anchor ??= $this->firstMs * 48;
        $this->flush(true);
    }

    /**
     * Hand the packets encoded so far to the sink, once it is known where they start.
     */
    protected function flush(bool $final): void
    {
        if ($this->anchor === null) {
            return;
        }
        foreach ($this->encode($final) as $packet) {
            ($this->sink)($packet, $this->anchor + self::FRAME_SAMPLES * $this->packets++);
        }
    }
}
