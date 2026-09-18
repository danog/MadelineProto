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

// IMPORTANT NOTE: Please keep the above copyright notice intact if copying or rewriting this file in another language.

namespace danog\MadelineProto\Tgcalls;

/**
 * @internal
 *
 * @psalm-pure
 */
final class TgcallsTools
{
    /**
     * Whether realtime transcoding is available.
     *
     * The WebRTC stack itself is pure PHP and always available; the FFI extension (together with
     * libopus, libvpx and ffmpeg) is only needed to convert media on the fly. Without it, calls
     * work normally as long as the audio and video that are played back were already converted to
     * the formats Telegram uses, which MadelineProto can also do offline.
     *
     * @psalm-pure
     */
    public static function canTranscode(): bool
    {
        return \extension_loaded('ffi');
    }

    /**
     * Compress a signaling payload, as tgcalls' `gzipData` does.
     *
     * @psalm-pure
     */
    public static function gzip(string $data): string
    {
        $compressed = gzencode($data, 9);
        return $compressed === false ? $data : $compressed;
    }

    /**
     * Transparently decompress a signaling payload, as tgcalls' `isGzip`/`gunzipData` pair does.
     *
     * @psalm-pure
     */
    public static function gunzip(string $data): string
    {
        if (\strlen($data) < 2) {
            return $data;
        }

        if ($data[0] === \chr(0x1f) && $data[1] === \chr(0x8b)) {
            $result = @gzdecode($data);
            return $result === false ? $data : $result;
        }
        if ($data[0] === \chr(0x78) && $data[1] === \chr(0x9c)) {
            $result = @gzuncompress($data);
            return $result === false ? $data : $result;
        }
        return $data;
    }
}
