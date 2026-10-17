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

/**
 * A minimal, in-memory OGG demuxer for the OPUS audio chunks of a group call in
 * [stream mode](https://core.telegram.org/api/group-calls#stream-mode): splits a chunk into its
 * logical streams, each with its OpusHead/OpusTags headers and its data packets (with their duration).
 *
 * @internal
 */
final class OggDemuxer
{
    /**
     * @return array<int, array{head: ?string, tags: ?string, packets: list<array{data: string, samples: int}>}> By bitstream serial number.
     *
     * @psalm-pure
     */
    public static function demux(string $data): array
    {
        $streams = [];
        $partial = [];
        $offset = 0;
        $length = \strlen($data);
        while ($offset + 27 <= $length) {
            if (substr($data, $offset, 4) !== 'OggS') {
                throw new \RuntimeException('Bad OGG capture pattern at offset '.$offset);
            }
            $header = unpack('Cversion/Ctype/Pgranule/Vserial/Vseq/Vcrc/Csegments', substr($data, $offset + 4, 23));
            $offset += 27;
            $serial = (int) $header['serial'];
            $segmentCount = (int) $header['segments'];
            $segments = array_map('intval', array_values(unpack('C*', substr($data, $offset, $segmentCount))));
            $offset += $segmentCount;
            $streams[$serial] ??= ['head' => null, 'tags' => null, 'packets' => []];
            $partial[$serial] ??= '';
            foreach ($segments as $size) {
                $partial[$serial] .= substr($data, $offset, $size);
                $offset += $size;
                if ($size === 255) {
                    continue; // the packet continues in the next segment (or page)
                }
                $packet = $partial[$serial];
                $partial[$serial] = '';
                if ($packet === '') {
                    continue;
                }
                if ($streams[$serial]['head'] === null && str_starts_with($packet, 'OpusHead')) {
                    $streams[$serial]['head'] = $packet;
                } elseif ($streams[$serial]['tags'] === null && str_starts_with($packet, 'OpusTags')) {
                    $streams[$serial]['tags'] = $packet;
                } else {
                    $streams[$serial]['packets'][] = ['data' => $packet, 'samples' => self::opusSamples($packet)];
                }
            }
        }
        return $streams;
    }

    /**
     * The number of 48kHz samples an OPUS packet lasts, from its TOC byte.
     *
     * @psalm-pure
     */
    public static function opusSamples(string $packet): int
    {
        if ($packet === '') {
            return 0;
        }
        $toc = \ord($packet[0]);
        $config = $toc >> 3;
        if ($config < 12) {
            $frameUs = ($config % 4) === 0 ? 10_000 : ($config % 4) * 20_000;
        } elseif ($config < 16) {
            $frameUs = (1 << ($config % 2)) * 10_000;
        } else {
            $frameUs = (1 << ($config % 4)) * 2_500;
        }
        $frames = match ($toc & 3) {
            0 => 1,
            1, 2 => 2,
            default => isset($packet[1]) ? (\ord($packet[1]) & 0x3F) : 1,
        };
        return intdiv($frames * $frameUs * 48, 1000);
    }

    /**
     * A family-0 OpusHead packet for a 48kHz stream with the given channels.
     *
     * @psalm-pure
     */
    public static function opusHead(int $channels): string
    {
        return 'OpusHead'.pack('CCvVvC', 1, $channels, 312, 48000, 0, 0);
    }

    /**
     * The channel count declared by an OpusHead packet.
     *
     * @psalm-pure
     */
    public static function opusChannels(string $opusHead): int
    {
        return \strlen($opusHead) >= 10 ? \ord($opusHead[9]) : 2;
    }

    /**
     * The comments (`KEY=value`) of an OpusTags packet.
     *
     * @return array<string, string>
     *
     * @psalm-pure
     */
    public static function opusComments(string $opusTags): array
    {
        $comments = [];
        $length = \strlen($opusTags);
        if ($length < 16) {
            return $comments;
        }
        $offset = 8;
        $vendorLength = (int) unpack('V', substr($opusTags, $offset, 4))[1];
        $offset += 4 + $vendorLength;
        if ($offset + 4 > $length) {
            return $comments;
        }
        $count = (int) unpack('V', substr($opusTags, $offset, 4))[1];
        $offset += 4;
        for ($i = 0; $i < $count && $offset + 4 <= $length; $i++) {
            $commentLength = (int) unpack('V', substr($opusTags, $offset, 4))[1];
            $offset += 4;
            $comment = substr($opusTags, $offset, $commentLength);
            $offset += $commentLength;
            $equals = strpos($comment, '=');
            if ($equals !== false) {
                $comments[substr($comment, 0, $equals)] = substr($comment, $equals + 1);
            }
        }
        return $comments;
    }
}
