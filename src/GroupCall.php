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
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\GroupCall\GroupCallState;
use danog\MadelineProto\GroupCall\Participant;

/**
 * This update represents a Telegram group call (a video chat, a livestream or a live story).
 *
 * See https://core.telegram.org/api/group-calls for more info.
 */
final class GroupCall extends Update implements MultiCall
{
    /** Group call ID. */
    public readonly int $id;
    /** Access hash of the group call. */
    public readonly int $accessHash;
    /** Bot API ID of the group/channel the call is associated with, if any. */
    public readonly ?int $peerId;

    /** Title of the call, if it has one. */
    public ?string $title = null;
    /** Number of participants. */
    public int $participantsCount = 0;
    /** Whether new participants join muted. */
    public bool $joinMuted = false;
    /** Whether this is an RTMP livestream. */
    public bool $rtmpStream = false;
    /** Whether this is an E2E-encrypted conference call, not associated with any group. */
    public bool $conference = false;
    /** Whether we created this call. */
    public bool $creator = false;
    /** The invite link of a conference call, if any. */
    public ?string $inviteLink = null;
    /** When the call is scheduled to start, if it is a scheduled call. */
    public ?int $scheduleDate = null;
    /** The DC to use when downloading media chunks in stream mode. */
    public ?int $streamDcId = null;
    /** Whether the call has ended. */
    public bool $discarded = false;

    /**
     * Constructor.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        MTProto $API,
        array $call,
        ?int $peerId = null,
    ) {
        parent::__construct($API);
        $this->id = $call['id'];
        $this->accessHash = $call['access_hash'];
        $this->peerId = $peerId;
        $this->update($call);
    }

    /**
     * @internal
     *
     * @psalm-external-mutation-free
     */
    public function update(array $call): void
    {
        if ($call['_'] === 'groupCallDiscarded') {
            $this->discarded = true;
            return;
        }
        $this->title = $call['title'] ?? null;
        $this->participantsCount = $call['participants_count'] ?? 0;
        $this->joinMuted = $call['join_muted'] ?? false;
        $this->rtmpStream = $call['rtmp_stream'] ?? false;
        $this->conference = $call['conference'] ?? false;
        $this->creator = $call['creator'] ?? false;
        $this->inviteLink = $call['invite_link'] ?? null;
        $this->scheduleDate = $call['schedule_date'] ?? null;
        $this->streamDcId = $call['stream_dc_id'] ?? null;
    }

    /**
     * Join the group call.
     *
     * Note that [conference calls »](https://core.telegram.org/api/group-calls#conference-calls) are
     * end-to-end encrypted and are not supported yet.
     *
     * @param bool        $muted      Whether to join muted.
     * @param mixed       $joinAs     Peer to join as; only [video chats/livestreams »](https://core.telegram.org/api/group-calls#video-chats-livestreams) may use a peer other than ourselves.
     * @param string|null $inviteHash Invite hash from a [video chat invite link »](https://core.telegram.org/api/links#video-chat-livestream-links), if any.
     */
    public function join(bool $muted = false, mixed $joinAs = null, ?string $inviteHash = null): self
    {
        $this->getClient()->joinGroupCallById($this->id, $muted, $joinAs, $inviteHash);
        return $this;
    }

    /**
     * Leave the group call, without ending it for the other participants.
     */
    public function leave(): self
    {
        $this->getClient()->leaveGroupCall($this->id);
        return $this;
    }

    /**
     * End the group call for all participants.
     */
    public function discard(): self
    {
        $this->getClient()->discardGroupCall($this->id);
        return $this;
    }

    /**
     * Get the state of the group call.
     *
     * @psalm-mutation-free
     */
    public function getCallState(): GroupCallState
    {
        return $this->getClient()->getGroupCallState($this->id) ?? GroupCallState::NOT_JOINED;
    }

    /**
     * Get all known participants, indexed by their bot API peer ID.
     *
     * @return array<int, Participant>
     *
     * @psalm-mutation-free
     */
    public function getParticipants(): array
    {
        return $this->getClient()->getGroupCallParticipants($this->id);
    }

    /**
     * Mute or unmute our own audio stream.
     */
    public function setMuted(bool $muted = true): self
    {
        $this->getClient()->setGroupCallMuted($this->id, $muted);
        return $this;
    }

    /**
     * Whether our own audio stream is muted.
     *
     * @psalm-mutation-free
     */
    public function isMuted(): bool
    {
        return $this->getClient()->isGroupCallMuted($this->id);
    }

    /**
     * Change the title of the group call.
     */
    public function setTitle(string $title): self
    {
        $this->getClient()->setGroupCallTitle($this->id, $title);
        return $this;
    }

    /**
     * Invite users to the group call.
     */
    public function invite(mixed ...$users): self
    {
        $this->getClient()->inviteToGroupCall($this->id, ...$users);
        return $this;
    }

    /**
     * Export an invite link for this group call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; admins only.
     */
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        return $this->getClient()->exportGroupCallInvite($this->id, $canSelfUnmute);
    }

    /**
     * Record group call audio, writing an OGG OPUS stream.
     *
     * Call it either way:
     *  - `setOutput($participant, $file)` records that one participant's incoming audio to the file
     *    or stream.
     *  - `setOutput(new LocalDirectory($dir))` records *every* transmitting participant, each into its
     *    own `<dir>/<peerId>.ogg` file (participants that start transmitting later are picked up too);
     *    our own audio is never recorded.
     */
    public function setOutput(mixed $participant, LocalFile|WritableStream|null $file = null): self
    {
        $this->getClient()->groupCallSetOutput($this->id, $participant, $file);
        return $this;
    }

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     *
     * A WebM/Matroska file with VP8, VP9 or H.264 video has its video transmitted too; any other
     * file (or a raw audio stream) is played as audio only. Frames are demuxed in pure PHP and sent
     * as-is where possible, so no transcoding (and thus no FFI extension) is required for
     * pre-encoded WebM/OGG-OPUS input.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPlayBlocking($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play file.
     */
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    public function skip(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallSkipPlay($this->id, $dest);
        return $this;
    }

    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    public function stop(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallStopPlay($this->id, $dest);
        return $this;
    }

    /**
     * Pauses the currently playing file.
     */
    public function pause(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPausePlay($this->id, $dest);
        return $this;
    }

    /**
     * Whether the currently playing file is paused.
     */
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->getClient()->isGroupCallPlayPaused($this->id, $dest);
    }

    /**
     * Resumes the currently playing file.
     */
    public function resume(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallResumePlay($this->id, $dest);
        return $this;
    }

    /**
     * Files to play on hold.
     */
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): self
    {
        $this->getClient()->groupCallPlayOnHold($this->id, $dest, ...$files);
        return $this;
    }

    /**
     * Get the file that is currently being played.
     *
     * Will return a string with the object ID of the stream if we're currently playing a stream,
     * otherwise returns the related LocalFile or RemoteUrl.
     */
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getClient()->groupCallGetCurrent($this->id, $dest);
    }

    /**
     * Get call representation.
     *
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        $title = $this->title !== null ? " \"{$this->title}\"" : '';
        return "group call {$this->id}$title";
    }
}
