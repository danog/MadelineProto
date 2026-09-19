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
 * Notified when the video codec the {@see \danog\MadelineProto\Loop\VoIP\DjLoop} is transmitting
 * changes, and when video playback stops.
 *
 * The {@see \danog\MadelineProto\Loop\VoIP\DjLoop} keeps a reference to its observer as part of its
 * serializable state, so this has to be a real, serializable object rather than a `Closure` (which
 * PHP cannot serialize): the WebRTC engines survive a serialize/unserialize cycle, and every
 * callback they hold must survive it too.
 *
 * @internal
 * @psalm-mutable
 */
interface VideoCodecObserver
{
    /**
     * Pin the outgoing video m-line to the codec of the file being played.
     *
     * @param string $codec The SDP encoding name (e.g. `VP8`, `H264`) of the video being transmitted.
     * @param array<string, string> $parameters The SDP fmtp parameters (profile/level/tier/…) derived
     *                                           from the file's bitstream, to advertise for this codec.
     * @psalm-impure
     */
    public function onVideoCodec(string $codec, array $parameters = []): void;

    /**
     * React to the demuxed video file finishing: the outgoing video is no longer being transmitted.
     * @psalm-impure
     */
    public function onVideoStopped(): void;
}
