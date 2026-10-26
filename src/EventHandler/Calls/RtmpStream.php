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

namespace danog\MadelineProto\EventHandler\Calls;

use Amp\ByteStream\ReadableStream;
use danog\MadelineProto\EventHandler\MediaPlayer;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RemoteUrl;

/**
 * What is played, published to an RTMP server as an RTMP encoder would (OBS, ffmpeg...): to the RTMP
 * livestream or live story of any group, channel or user that gave us its URL and stream key, to YouTube,
 * Twitch, or any other RTMP(S) server.
 *
 * Only H.264 video and AAC audio can be published, in MP4 or Matroska (WebM) files, see
 * {@see \danog\MadelineProto\GroupCall\Rtmp\RtmpPublisher}. Started by {@see \danog\MadelineProto\API::startRtmpStream()},
 * it keeps publishing (reconnecting if needed, and across restarts) until {@see self::close()}d. Only the
 * {@see MediaDestination::Camera} stream exists.
 */
final class RtmpStream extends IpcCapable implements MediaPlayer
{
    /**
     * @internal
     *
     * @param int    $id  The ID of the stream in this session.
     * @param string $url The URL of the RTMP application, without the stream key, e.g. `rtmps://dc4-1.rtmp.t.me/s/`.
     */
    public function __construct(
        MTProto|Client $API,
        /** The ID of the stream in this session. */
        public readonly int $id,
        /** The URL of the RTMP application, without the stream key, e.g. `rtmps://dc4-1.rtmp.t.me/s/`. */
        public readonly string $url,
    ) {
        parent::__construct($API);
    }

    #[\Override]
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamPlay($this->id, $file, $dest);
        return $this;
    }

    #[\Override]
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamPlayBlocking($this->id, $file, $dest);
        return $this;
    }

    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamPlay($this->id, $file, $dest);
        return $this;
    }

    #[\Override]
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): static
    {
        $this->getClient()->rtmpStreamPlayOnHold($this->id, $dest, ...$files);
        return $this;
    }

    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamSkipPlay($this->id, $dest);
        return $this;
    }

    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamStopPlay($this->id, $dest);
        return $this;
    }

    #[\Override]
    public function pause(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamPausePlay($this->id, $dest);
        return $this;
    }

    #[\Override]
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->getClient()->isRtmpStreamPlayPaused($this->id, $dest);
    }

    #[\Override]
    public function resume(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->rtmpStreamResumePlay($this->id, $dest);
        return $this;
    }

    #[\Override]
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getClient()->rtmpStreamGetCurrent($this->id, $dest);
    }

    /**
     * Whether we are connected to the RTMP server right now (we connect again when something is played,
     * if the server dropped us while nothing was).
     */
    public function isConnected(): bool
    {
        return $this->getClient()->isRtmpStreamConnected($this->id);
    }

    /**
     * Stop publishing for good, and forget the stream.
     */
    public function close(): void
    {
        $this->getClient()->closeRtmpStream($this->id);
    }

    /**
     * The URL of the stream, without its key.
     */
    #[\Override]
    public function __toString(): string
    {
        return "the RTMP stream to {$this->url}";
    }
}
