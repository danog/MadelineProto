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

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableStream;
use Stringable;

/**
 * Common interface implemented by every call type: one-to-one {@see VoIP} calls and {@see GroupCall}
 * group/conference calls.
 *
 * It covers the media surface shared by all call types — the playlist/DJ playback controls and the
 * mute and discard controls — so code can drive any call uniformly. Every playlist control takes a
 * {@see MediaDestination} selecting the stream it acts on: the main camera+mic stream (default) or a
 * separate presentation (screen-share) stream. Type-specific operations (a one-to-one call's
 * {@see VoIP::accept()}, a group call's {@see GroupCall::join()} or per-participant recording) live on
 * the concrete classes.
 */
interface Call extends Stringable
{
    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     *
     * A WebM/Matroska file with VP8, VP9, H.264 or AV1 video has its video transmitted too; any other
     * file (or a raw audio stream) is played as audio only. Frames are demuxed in pure PHP and sent
     * as-is where possible, so no transcoding (and thus no FFI extension) is required for pre-encoded
     * WebM/OGG-OPUS input. `$dest` selects the camera or the presentation (screencast) stream.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Add a file to the playlist, to be played once the current one finishes.
     */
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Set the files to play, on loop, while the given stream's main playlist is empty.
     */
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): static;

    /**
     * Skip to the next file in the playlist.
     */
    public function skip(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Stop playing all files, clearing the main and the hold playlist.
     */
    public function stop(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Pause playback of the current file.
     */
    public function pause(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Whether playback of the current file is paused.
     */
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool;

    /**
     * Resume playback of the current file.
     */
    public function resume(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * The file or stream currently being played, if any.
     */
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null;

    /**
     * Mute or unmute our own outgoing audio.
     */
    public function setMuted(bool $muted = true): static;

    /**
     * Whether our own outgoing audio is muted.
     */
    public function isMuted(): bool;

    /**
     * Discard (hang up / leave) the call.
     */
    public function discard(): static;
}
