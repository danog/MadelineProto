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

namespace danog\MadelineProto\EventHandler\Calls;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\SimpleFilters;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIP\MediaState;
use InvalidArgumentException;

/**
 * This update represents a private (one-to-one) VoIP Telegram call.
 *
 * The old name {@see \danog\MadelineProto\VoIP} is kept as an alias for backwards compatibility.
 */
final class PrivateCall extends Update implements SimpleFilters, Call
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
     * Accept the incoming call.
     *
     * @param bool $muted Whether to accept with our own audio muted.
     */
    #[\Override]
    public function join(bool $muted = false): self
    {
        $this->getClient()->acceptCall($this->callID);
        if ($muted) {
            $this->getClient()->setCallMuted($this->callID, true);
        }
        return $this;
    }
    /**
     * Discard call.
     *
     * @param int<1, 5> $rating  Call rating in stars
     * @param string    $comment Additional comment on call quality.
     */
    #[\Override]
    public function discard(DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null): self
    {
        $this->getClient()->discardCall($this->callID, $reason, $rating, $comment);
        return $this;
    }

    /**
     * Whether the call is running (accepted by both parties and connected).
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isJoined(): bool
    {
        return $this->getCallState() === CallState::RUNNING;
    }

    /**
     * Get call state.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getCallState(): CallState
    {
        return $this->getClient()->getCallState($this->callID) ?? CallState::ENDED;
    }

    /**
     * The other party of the call, keyed by their user ID, with their media state (mute, camera and
     * screencast status) as reported by their client.
     *
     * Empty until the call is connected and the other party has reported their media state.
     *
     * @return array<int, MediaState>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getParticipants(): array
    {
        $state = $this->getClient()->getCallRemoteMediaState($this->callID);
        return $state === null ? [] : [$this->otherID => $state];
    }

    /**
     * The media state of the other party, if `$participant` is them and the call is connected.
     */
    #[\Override]
    public function getParticipant(mixed $participant): ?MediaState
    {
        return $this->getParticipants()[$this->getClient()->getId($participant)] ?? null;
    }

    /**
     * Get the key verification emojis (will return null if the call is not inited yet).
     *
     * @return ?list{string, string, string, string}
     *
     * @psalm-mutation-free
     */
    #[\Override]
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
    #[\Override]
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->callPlay($this->callID, $file, $dest);

        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    #[\Override]
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->callPlayBlocking($this->callID, $file, $dest);

        return $this;
    }

    /**
     * Record the incoming media of the call.
     *
     * A {@see LocalFile} or {@see WritableStream} records the other party's camera+audio (or, with
     * {@see MediaDestination::Presentation}, their screencast) into it; a {@see LocalDirectory} records
     * them into `<dir>/<userId>.mkv` instead. `$participant` may only be the other party, and is
     * therefore optional.
     *
     * A {@see RecordingFormat::Webm} or {@see RecordingFormat::Mkv} target records both the incoming
     * audio and video, muxed into a Matroska file in pure PHP (the peer's frames are stored as-is, so
     * the video track is whatever codec the peer sends — VP8/VP9/H.264/AV1 — and the audio is OPUS).
     * {@see RecordingFormat::Opus} keeps the audio-only behaviour and receives an OGG OPUS stream.
     *
     * When `$format` is null it is autodetected from the extension of `$file`, but only if a
     * {@see LocalFile} was passed; a raw stream, whose extension is unknown, defaults to OGG OPUS.
     */
    #[\Override]
    public function setOutput(LocalFile|LocalDirectory|WritableStream $file, mixed $participant = null, MediaDestination $dest = MediaDestination::Camera, ?RecordingFormat $format = null): self
    {
        if ($participant !== null && $this->getClient()->getId($participant) !== $this->otherID) {
            throw new InvalidArgumentException("Only the other party ({$this->otherID}) of a one-to-one call can be recorded.");
        }
        if ($file instanceof LocalDirectory) {
            $file = new LocalFile($file->dir.'/'.$this->otherID.($dest === MediaDestination::Presentation ? '.presentation' : '').'.mkv');
        }
        $this->getClient()->callSetOutput($this->callID, $file, $dest, $format);

        return $this;
    }

    /**
     * Play file.
     */
    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->callPlay($this->callID, $file, $dest);

        return $this;
    }
    /**
     * When called, skips to the next file in the playlist.
     */
    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->skipPlay($this->callID, $dest);

        return $this;
    }
    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->stopPlay($this->callID, $dest);

        return $this;
    }

    /**
     * Pauses the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function pause(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->pausePlay($this->callID, $dest);

        return $this;
    }

    /**
     * Whether the currently playing file is paused.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->getClient()->isPlayPaused($this->callID, $dest);
    }

    /**
     * Resumes the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function resume(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->resumePlay($this->callID, $dest);

        return $this;
    }

    /**
     * Files to play on hold.
     */
    #[\Override]
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): self
    {
        $this->getClient()->callPlayOnHold($this->callID, $dest, ...$files);

        return $this;
    }

    /**
     * Get the file that is currently being played.
     *
     * Will return a string with the object ID of the stream if we're currently playing a stream, otherwise returns the related LocalFile or RemoteUrl.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getClient()->callGetCurrent($this->callID, $dest);
    }

    /**
     * Mute or unmute our own audio stream.
     */
    #[\Override]
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
    #[\Override]
    public function isMuted(): bool
    {
        return $this->getClient()->isCallMuted($this->callID);
    }

    /**
     * Start sharing a screen: bring up the presentation (screencast) video stream, so that files played
     * on {@see MediaDestination::Presentation} are transmitted as a screencast. The other party is told
     * the screencast is active as soon as a file with video plays on it.
     */
    #[\Override]
    public function enablePresentation(): self
    {
        $this->getClient()->enableCallPresentation($this->callID);

        return $this;
    }

    /**
     * Stop sharing the screen: stop the presentation playlist and tell the other party the screencast
     * is inactive.
     */
    #[\Override]
    public function disablePresentation(): self
    {
        $this->getClient()->disableCallPresentation($this->callID);

        return $this;
    }

    /**
     * Whether a screencast is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isSharingScreen(): bool
    {
        return $this->getClient()->isCallSharingScreen($this->callID);
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
