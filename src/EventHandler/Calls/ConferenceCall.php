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
use danog\MadelineProto\EventHandler\MultiCall;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\GroupCall\GroupCallState;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RemoteUrl;

/**
 * This update represents a Telegram [end-to-end encrypted conference call »](https://core.telegram.org/api/end-to-end/group-calls).
 *
 * Unlike a plain {@see GroupCall} (a video chat or livestream), a conference call is not associated
 * with any group or channel: it is a standalone, end-to-end encrypted multi-party call, whose media
 * the SFU only ever forwards as ciphertext. On top of the shared {@see MultiCall} surface it exposes
 * the conference-specific controls: [emoji verification »](https://core.telegram.org/api/end-to-end/group-calls#verifying-the-key),
 * end-to-end encrypted in-call messages, and removing participants.
 *
 * This is a thin, serializable handle: every operation is delegated to the live conference controller
 * ({@see \danog\MadelineProto\Tgcalls\E2E\ConferenceCall}) by call ID, so it keeps working across a
 * process restart and over IPC.
 */
final class ConferenceCall extends Update implements MultiCall
{
    /** Conference call ID. */
    public readonly int $id;
    /** Access hash of the conference call. */
    public readonly int $accessHash;

    /** Number of participants. */
    public int $participantsCount = 0;
    /** Whether we created this call. */
    public bool $creator = false;
    /** The invite link of the conference call, if any. */
    public ?string $inviteLink = null;
    /** When the call is scheduled to start, if it is a scheduled call. */
    public ?int $scheduleDate = null;
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
    ) {
        parent::__construct($API);
        $this->id = $call['id'];
        $this->accessHash = $call['access_hash'];
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
        $this->participantsCount = $call['participants_count'] ?? 0;
        $this->creator = $call['creator'] ?? false;
        $this->inviteLink = $call['invite_link'] ?? null;
        $this->scheduleDate = $call['schedule_date'] ?? null;
    }

    /**
     * Join the conference call.
     *
     * @param bool $muted Whether to join muted.
     */
    public function join(bool $muted = false): self
    {
        $this->getClient()->joinConferenceCall([
            '_' => 'inputGroupCall',
            'id' => $this->id,
            'access_hash' => $this->accessHash,
        ], $muted);
        return $this;
    }

    /**
     * Whether we are currently in the conference (joined and not left/removed).
     *
     * @psalm-mutation-free
     */
    public function isJoined(): bool
    {
        return $this->getClient()->isConferenceCallJoined($this->id);
    }

    /**
     * Get the state of the conference call.
     *
     * @psalm-mutation-free
     */
    public function getCallState(): GroupCallState
    {
        return $this->getClient()->getConferenceCallState($this->id);
    }

    /**
     * Leave the conference call, without ending it for the other participants.
     */
    #[\Override]
    public function leave(): self
    {
        $this->getClient()->leaveConferenceCall($this->id);
        return $this;
    }

    /**
     * Leave the conference call.
     *
     * A conference is not owned by anyone, so there is nothing to end for everyone else; this simply
     * {@see self::leave()}s the call.
     */
    #[\Override]
    public function discard(): self
    {
        $this->getClient()->leaveConferenceCall($this->id);
        return $this;
    }

    /**
     * Get the participants currently in the conference, keyed by their user id, each with their
     * Ed25519 `public_key` and `permissions` bits from the shared-state chain.
     *
     * @return array<int, array{public_key: string, permissions: int}>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getParticipants(): array
    {
        return $this->getClient()->getConferenceCallParticipants($this->id);
    }

    /**
     * Remove participants from the conference and rekey for the remaining members, so the removed
     * members can no longer decrypt the call's media. Requires the `remove_users` permission.
     */
    public function removeParticipant(int ...$userIds): self
    {
        $this->getClient()->removeConferenceCallParticipants($this->id, ...$userIds);
        return $this;
    }

    /**
     * Begin (or restart) [emoji verification »](https://core.telegram.org/api/end-to-end/group-calls#verifying-the-key)
     * for the current chain head: broadcast our nonce so all participants converge on the same four
     * emojis, retrievable with {@see self::getEmojis()}.
     */
    public function startVerification(): self
    {
        $this->getClient()->startConferenceCallVerification($this->id);
        return $this;
    }

    /**
     * The four verification emojis, or null until every participant's nonce has been revealed.
     *
     * @return list<string>|null
     *
     * @psalm-mutation-free
     */
    public function getEmojis(): ?array
    {
        return $this->getClient()->getConferenceCallEmojis($this->id);
    }

    /**
     * Send an end-to-end encrypted in-call message to every participant of the conference.
     */
    public function sendMessage(string $message): self
    {
        $this->getClient()->sendConferenceCallMessage($this->id, $message);
        return $this;
    }

    /**
     * Whether a screen-share is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    public function isSharingScreen(): bool
    {
        return $this->getClient()->isConferenceCallSharingScreen($this->id);
    }

    /**
     * Start sharing a screen: a second, end-to-end encrypted connection whose video is transmitted on
     * the {@see MediaDestination::Presentation} stream.
     */
    public function enablePresentation(): self
    {
        $this->getClient()->enableConferenceCallPresentation($this->id);
        return $this;
    }

    /**
     * Stop sharing the screen.
     */
    public function disablePresentation(): self
    {
        $this->getClient()->disableConferenceCallPresentation($this->id);
        return $this;
    }

    /**
     * Record conference call media, writing a Matroska (or OGG OPUS) stream.
     *
     * Call it either way:
     *  - `setOutput($userId, $file)` records that one participant's incoming audio and video to the file
     *    or stream.
     *  - `setOutput(new LocalDirectory($dir))` records *every* transmitting participant, each into its
     *    own `<dir>/<userId>.mkv` file (participants that start transmitting later are picked up too);
     *    our own media is never recorded. Every recording is plaintext — the frames are decrypted before
     *    they are muxed.
     */
    public function setOutput(mixed $participant, LocalFile|WritableStream|null $file = null): self
    {
        $this->getClient()->conferenceCallSetOutput($this->id, $participant, $file);
        return $this;
    }

    /**
     * Mute or unmute our own audio stream.
     */
    #[\Override]
    public function setMuted(bool $muted = true): self
    {
        $this->getClient()->setConferenceCallMuted($this->id, $muted);
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
        return $this->getClient()->isConferenceCallMuted($this->id);
    }

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video, all
     * end-to-end encrypted before it reaches the SFU.
     *
     * A WebM/Matroska file with VP8, VP9 or H.264 video has its video transmitted too; any other
     * file (or a raw audio stream) is played as audio only. Frames are demuxed in pure PHP and sent
     * as-is where possible, so no transcoding (and thus no FFI extension) is required for
     * pre-encoded WebM/OGG-OPUS input.
     */
    #[\Override]
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->conferenceCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->conferenceCallPlayBlocking($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play file.
     */
    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->conferenceCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->conferenceCallSkipPlay($this->id, $dest);
        return $this;
    }

    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->conferenceCallStopPlay($this->id, $dest);
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
        $this->getClient()->conferenceCallPausePlay($this->id, $dest);
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
        return $this->getClient()->isConferenceCallPlayPaused($this->id, $dest);
    }

    /**
     * Resumes the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function resume(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->conferenceCallResumePlay($this->id, $dest);
        return $this;
    }

    /**
     * Files to play on hold.
     */
    #[\Override]
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): self
    {
        $this->getClient()->conferenceCallPlayOnHold($this->id, $dest, ...$files);
        return $this;
    }

    /**
     * Get the file that is currently being played.
     *
     * Will return a string with the object ID of the stream if we're currently playing a stream,
     * otherwise returns the related LocalFile or RemoteUrl.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getClient()->conferenceCallGetCurrent($this->id, $dest);
    }

    /**
     * Get call representation.
     *
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return "E2E conference call {$this->id}";
    }
}
