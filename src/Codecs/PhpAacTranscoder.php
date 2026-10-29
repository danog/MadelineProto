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

use AssertionError;
use Closure;
use danog\MadelineProto\Codecs\Aac\AacDecoder;
use danog\MadelineProto\Codecs\Opus\OpusEncoder;

use function Amp\delay;

/**
 * Transcodes AAC to OPUS in pure PHP: {@see AacDecoder}, {@see Resampler} to 48 kHz, {@see OpusEncoder}.
 *
 * @internal
 */
final class PhpAacTranscoder extends AacTranscoder
{
    /** The OPUS bitrate, per channel. */
    private const BITRATE_PER_CHANNEL = 48000;

    private readonly AacDecoder $decoder;
    private readonly Resampler $resampler;
    private readonly OpusEncoder $encoder;
    /** @var list<list<float>> Decoded samples at 48 kHz not encoded yet, by channel. */
    private array $pending;
    private int $toSkip = 0;

    /** @param Closure(string, int): void $sink */
    public function __construct(string $config, Closure $sink)
    {
        $this->decoder = new AacDecoder($config);
        parent::__construct($this->decoder->sampleRate, $this->decoder->channels, $sink);
        $this->resampler = new Resampler($this->decoder->sampleRate, 48000, $this->channels);
        $this->encoder = new OpusEncoder($this->channels, self::BITRATE_PER_CHANNEL * $this->channels);
        $this->pending = array_fill(0, $this->channels, []);
    }

    #[\Override]
    public function describe(): string
    {
        return "MadelineProto's pure PHP AAC decoder and OPUS encoder";
    }

    #[\Override]
    public function latencyMs(): int
    {
        // The AAC frame decoded after the one a packet ends in (the first one is held), and the packet itself.
        return intdiv(2 * 1024 * 1000, $this->sampleRate) + 20;
    }

    #[\Override]
    protected function feed(string $frame): void
    {
        try {
            $decoded = $this->decoder->decode($frame);
        } catch (AssertionError) {
            // A frame that can't be decoded is a frame of silence: the rest of the file is still played, in time.
            $decoded = array_fill(0, $this->channels, array_fill(0, 1024, 0.0));
        }
        $samples = $this->resampler->process($decoded);
        $count = \count($samples[0]);
        $skip = min($this->toSkip, $count);
        $this->toSkip -= $skip;
        foreach ($samples as $c => $s) {
            array_push($this->pending[$c], ...($skip ? \array_slice($s, $skip) : $s));
        }
    }

    #[\Override]
    protected function skip(int $samples): void
    {
        // Whatever was already decoded first, then the rest.
        $drop = min($samples, \count($this->pending[0]));
        foreach ($this->pending as $c => $s) {
            $this->pending[$c] = \array_slice($s, $drop);
        }
        $this->toSkip += $samples - $drop;
    }

    #[\Override]
    protected function encode(bool $final): array
    {
        $packets = [];
        while (\count($this->pending[0]) >= self::FRAME_SAMPLES || ($final && $this->pending[0] !== [])) {
            // A packet takes milliseconds of CPU: let the tracks release the frames due meanwhile (decoding and
            // each packet one at a time, not one burst).
            delay(0);
            $frame = [];
            foreach ($this->pending as $c => $s) {
                $frame[$c] = array_pad(\array_slice($s, 0, self::FRAME_SAMPLES), self::FRAME_SAMPLES, 0.0);
                $this->pending[$c] = \array_slice($s, self::FRAME_SAMPLES);
            }
            $packets[] = $this->encoder->encode($frame);
        }
        return $packets;
    }

    #[\Override]
    public function close(): void
    {
    }
}
