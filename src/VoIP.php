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
use Amp\ByteStream\WritableStream;
use danog\MadelineProto\EventHandler\SimpleFilters;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIP\MediaState;

/**
 * This update represents a VoIP Telegram call.
 */
final class VoIP extends Update implements SimpleFilters, Call
{
    /** Phone call ID */
    public readonly int $callID;
    /** Whether the call is an outgoing call */
    public readonly bool $outgoing;
    /** Whether this is a video call. */
    public bool $video = false;
    /** ID of the other user in the call */
    public readonly int $otherID;
    /** When was the call created */
    public readonly int $date;
    /** Why the call was discarded, if it was. */
    public ?DiscardReason $discardReason = null;
    /**
     * If the call was upgraded to a [conference call »](https://core.telegram.org/api/group-calls#conference-calls),
     * the [conference deep link »](https://core.telegram.org/api/links#conference-links) slug of the new conference.
     *
     * Conference calls are end-to-end encrypted and cannot be joined by MadelineProto yet.
     */
    public ?string $conferenceSlug = null;

    /**
     * Constructor.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        MTProto $API,
        array $call
    ) {
        parent::__construct($API);
        $call['_'] = 'inputPhoneCall';
        $this->date = $call['date'];
        $this->callID = $call['id'];
        $this->video = $call['video'] ?? false;
        if ($call['admin_id'] === $API->getSelf()['id']) {
            $this->outgoing = true;
            $this->otherID = $call['participant_id'];
        } else {
            $this->outgoing = false;
            $this->otherID = $call['admin_id'];
        }
    }

    /**
     * Accept call.
     */
    public function accept(): self
    {
        $this->getClient()->acceptCall($this->callID);
        return $this;
    }
    /**
     * Discard call.
     *
     * @param int<1, 5> $rating  Call rating in stars
     * @param string    $comment Additional comment on call quality.
     */
    public function discard(DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null): self
    {
        $this->getClient()->discardCall($this->callID, $reason, $rating, $comment);
        return $this;
    }

    /**
     * Get call emojis (will return null if the call is not inited yet).
     *
     * @return ?list{string, string, string, string}
     *
     * @psalm-mutation-free
     */
    public function getVisualization(): ?array
    {
        return $this->getClient()->getCallVisualization($this->callID);
    }

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     *
     * A WebM/Matroska file with VP8, VP9 or H.264 video has its video transmitted too; any other
     * file (or a raw audio stream) is played as audio only. The file is demuxed in pure PHP and its
     * frames are sent as-is where possible, so no transcoding (and thus no FFI extension) is
     * required for pre-encoded WebM/OGG-OPUS input.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file): self
    {
        $this->getClient()->callPlay($this->callID, $file);

        return $this;
    }

    /**
     * Set the output file or stream for the incoming media.
     *
     * A `.mkv` or `.webm` file records both the incoming audio and video, muxed into a Matroska file
     * in pure PHP (the peer's frames are stored as-is, so the video track is whatever codec the peer
     * sends — VP8/VP9/H.264/AV1 — and the audio is OPUS). Any other file or a raw stream keeps the
     * audio-only behaviour and receives an OGG OPUS stream.
     */
    public function setOutput(LocalFile|WritableStream $file): self
    {
        $this->getClient()->callSetOutput($this->callID, $file);

        return $this;
    }


    /**
     * Play file.
     */
    public function then(LocalFile|RemoteUrl|ReadableStream $file): self
    {
        $this->getClient()->callPlay($this->callID, $file);

        return $this;
    }
    /**
     * When called, skips to the next file in the playlist.
     */
    public function skip(): self
    {
        $this->getClient()->skipPlay($this->callID);

        return $this;
    }
    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    public function stop(): self
    {
        $this->getClient()->stopPlay($this->callID);

        return $this;
    }

    /**
     * Pauses the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    public function pause(): self
    {
        $this->getClient()->pausePlay($this->callID);

        return $this;
    }

    /**
     * Whether the currently playing file is paused.
     *
     * @return boolean
     *
     * @psalm-mutation-free
     */
    public function isPaused(): bool
    {
        return $this->getClient()->isPlayPaused($this->callID);
    }

    /**
     * Resumes the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    public function resume(): self
    {
        $this->getClient()->resumePlay($this->callID);

        return $this;
    }

    /**
     * Files to play on hold.
     */
    public function playOnHold(LocalFile|RemoteUrl|ReadableStream ...$files): self
    {
        $this->getClient()->callPlayOnHold($this->callID, ...$files);

        return $this;
    }

    /**
     * Get the file that is currently being played.
     *
     * Will return a string with the object ID of the stream if we're currently playing a stream, otherwise returns the related LocalFile or RemoteUrl.
     *
     * @psalm-mutation-free
     */
    public function getCurrent(): RemoteUrl|LocalFile|string|null
    {
        return $this->getClient()->callGetCurrent($this->callID);
    }

    /**
     * Mute or unmute our own audio stream.
     */
    public function setMuted(bool $muted = true): self
    {
        $this->getClient()->setCallMuted($this->callID, $muted);

        return $this;
    }

    /**
     * Whether our own audio stream is muted.
     *
     * @psalm-mutation-free
     */
    public function isMuted(): bool
    {
        return $this->getClient()->isCallMuted($this->callID);
    }

    /**
     * Get the media state of the other party, as reported by their client.
     *
     * Will return null if the call is not connected yet.
     *
     * @psalm-mutation-free
     */
    public function getRemoteMediaState(): ?MediaState
    {
        return $this->getClient()->getCallRemoteMediaState($this->callID);
    }

    /**
     * Get call state.
     *
     * @psalm-mutation-free
     */
    public function getCallState(): CallState
    {
        return $this->getClient()->getCallState($this->callID) ?? CallState::ENDED;
    }
    /**
     * Get call representation.
     *
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return "call {$this->callID} with {$this->otherID}";
    }
}
