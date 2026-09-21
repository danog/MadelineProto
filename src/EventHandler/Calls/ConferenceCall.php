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
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;

/**
 * This update represents a Telegram [end-to-end encrypted conference call »](https://core.telegram.org/api/end-to-end/group-calls).
 *
 * Unlike a plain {@see GroupCall} (a video chat or livestream), a conference call is not associated
 * with any group or channel: it is a standalone, end-to-end encrypted multi-party call, whose media
 * the SFU only ever forwards as ciphertext. On top of the shared {@see MultiCall} surface it exposes
 * the conference-specific behaviour: the [emoji verification »](https://core.telegram.org/api/end-to-end/group-calls#verifying-the-key)
 * of the key, end-to-end encrypted in-call messages, and removing participants by rekeying.
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
    /** Whether in-call messages are enabled. */
    public bool $messagesEnabled = false;
    /** How many participants are transmitting video. */
    public ?int $unmutedVideoCount = null;
    /** How many participants may transmit video at once. */
    public int $unmutedVideoLimit = 0;
    /** Whether the call has ended. */
    public bool $discarded = false;
    /** Duration of the call in seconds, once it has ended. */
    public ?int $duration = null;

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
            $this->duration = isset($call['duration']) ? (int) $call['duration'] : null;
            return;
        }
        $this->participantsCount = (int) ($call['participants_count'] ?? 0);
        $this->creator = (bool) ($call['creator'] ?? false);
        $this->inviteLink = isset($call['invite_link']) ? (string) $call['invite_link'] : null;
        $this->messagesEnabled = (bool) ($call['messages_enabled'] ?? false);
        $this->unmutedVideoCount = isset($call['unmuted_video_count']) ? (int) $call['unmuted_video_count'] : null;
        $this->unmutedVideoLimit = (int) ($call['unmuted_video_limit'] ?? 0);
    }

    /**
     * Join the conference call.
     *
     * @param bool $muted Whether to join muted.
     */
    #[\Override]
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
    #[\Override]
    public function isJoined(): bool
    {
        return $this->getClient()->isConferenceCallJoined($this->id);
    }

    /**
     * Get the state of the conference call.
     *
     * @psalm-mutation-free
     */
    #[\Override]
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
     * End the conference call for everyone, if we created it, and leave it.
     *
     * Only the creator of a conference may end it: for anyone else the server refuses and this simply
     * {@see self::leave()}s the call.
     */
    #[\Override]
    public function discard(): self
    {
        $this->getClient()->discardConferenceCall($this->id);
        return $this;
    }

    /**
     * Get the participants currently in the conference, keyed by their user id, each with their
     * Ed25519 `public_key`, `permissions` bits and protocol `version` from the shared-state chain.
     *
     * @return array<int, array{public_key: string, permissions: int, version: int}>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getParticipants(): array
    {
        return $this->getClient()->getConferenceCallParticipants($this->id);
    }

    /**
     * A participant of the conference by their id, username or peer, with their Ed25519 `public_key`,
     * `permissions` bits and protocol `version` from the shared-state chain, or null if not in it.
     *
     * @return array{public_key: string, permissions: int, version: int}|null
     */
    #[\Override]
    public function getParticipant(mixed $participant): ?array
    {
        return $this->getParticipants()[$this->getClient()->getId($participant)] ?? null;
    }

    /**
     * Remove participants from the conference and rekey for the remaining members, so the removed
     * members can no longer decrypt the call's media. Requires the `remove_users` permission.
     */
    #[\Override]
    public function removeParticipant(mixed ...$participants): self
    {
        $this->getClient()->removeConferenceCallParticipants($this->id, ...$participants);
        return $this;
    }

    /**
     * Invite users to the conference call, ringing them.
     */
    #[\Override]
    public function invite(mixed ...$users): self
    {
        $this->getClient()->inviteToConferenceCall($this->id, ...$users);
        return $this;
    }

    /**
     * The [conference link »](https://core.telegram.org/api/links#conference-links) of this conference
     * call, which anyone can use to join it.
     *
     * @param bool $canSelfUnmute Ignored: a conference has no admins, everyone may speak.
     */
    #[\Override]
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        return $this->getClient()->exportConferenceCallInvite($this->id);
    }

    /**
     * Change the title of the conference call.
     */
    #[\Override]
    public function setTitle(string $title): self
    {
        $this->getClient()->setConferenceCallTitle($this->id, $title);
        return $this;
    }

    /**
     * The four [key verification emojis »](https://core.telegram.org/api/end-to-end/group-calls#verifying-the-key),
     * which every participant can compare to make sure nobody is in the middle.
     *
     * Verification (a commit-reveal exchange between all participants) runs automatically whenever
     * the set of participants changes; this returns null until it completes for the current state.
     *
     * @return list<string>|null
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getVisualization(): ?array
    {
        return $this->getClient()->getConferenceCallVisualization($this->id);
    }

    /**
     * Send an end-to-end encrypted [in-call message »](https://core.telegram.org/api/end-to-end/group-calls#conference-in-call-messages)
     * to every participant of the conference.
     *
     * @param string         $message   The text; markup in `$parseMode` is converted to entities (bold, italic, underline, strikethrough, spoiler and custom emoji are supported).
     * @param ParseMode|null $parseMode Whether to parse HTML or Markdown markup in the text.
     * @param int|null       $paidStars Ignored: conference messages cannot carry donations.
     * @param mixed          $sendAs    Ignored: conference messages are always sent as ourselves.
     */
    #[\Override]
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, mixed $sendAs = null): self
    {
        $this->getClient()->sendConferenceCallMessage($this->id, $message, $parseMode);
        return $this;
    }

    /**
     * Send an end-to-end encrypted [in-call reaction »](https://core.telegram.org/api/end-to-end/group-calls#conference-in-call-reactions).
     *
     * @param string   $emoji         The emoji.
     * @param int|null $customEmojiId The document id of a custom emoji to send instead, `$emoji` being its fallback.
     */
    #[\Override]
    public function sendReaction(string $emoji, ?int $customEmojiId = null): self
    {
        $this->getClient()->sendConferenceCallReaction($this->id, $emoji, $customEmojiId);
        return $this;
    }

    /**
     * Enable or disable in-call messages.
     */
    #[\Override]
    public function setMessagesEnabled(bool $enabled): self
    {
        $this->getClient()->toggleConferenceCallSettings($this->id, messagesEnabled: $enabled);
        return $this;
    }

    /**
     * Mute a participant for ourselves only (a conference has no admins).
     */
    #[\Override]
    public function muteParticipant(mixed $participant, bool $muted = true): self
    {
        $this->getClient()->editConferenceCallParticipant($this->id, $participant, muted: $muted);
        return $this;
    }

    /**
     * Set our local playback volume of a participant.
     *
     * @param int $volume From 1 to 20000, where 10000 is 100%.
     */
    #[\Override]
    public function setParticipantVolume(mixed $participant, int $volume): self
    {
        $this->getClient()->editConferenceCallParticipant($this->id, $participant, volume: $volume);
        return $this;
    }

    /**
     * Pause or resume our own video stream, telling the other participants to keep showing the last
     * frame rather than hiding it.
     */
    #[\Override]
    public function setVideoPaused(bool $paused): self
    {
        $this->getClient()->editConferenceCallParticipant($this->id, ['_' => 'inputPeerSelf'], videoPaused: $paused);
        return $this;
    }

    /**
     * Whether new participants join muted.
     */
    #[\Override]
    public function setJoinMuted(bool $joinMuted): self
    {
        $this->getClient()->toggleConferenceCallSettings($this->id, joinMuted: $joinMuted);
        return $this;
    }

    /**
     * Invalidate the conference link exported so far, so a new one is generated.
     */
    #[\Override]
    public function resetInviteHash(): self
    {
        $this->getClient()->toggleConferenceCallSettings($this->id, resetInviteHash: true);
        return $this;
    }

    /**
     * A conference is always received over WebRTC (its media is end-to-end encrypted): never in stream mode.
     *
     * @psalm-pure
     */
    #[\Override]
    public function isStreamMode(): bool
    {
        return false;
    }

    /**
     * A conference has no RTMP publisher.
     *
     * @psalm-pure
     */
    #[\Override]
    public function isRtmpMode(): bool
    {
        return false;
    }

    /**
     * Whether a screen-share is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isSharingScreen(): bool
    {
        return $this->getClient()->isConferenceCallSharingScreen($this->id);
    }

    /**
     * Start sharing a screen: a second, end-to-end encrypted connection whose video is transmitted on
     * the {@see MediaDestination::Presentation} stream. Idempotent; requires the call to be joined.
     */
    #[\Override]
    public function enablePresentation(): self
    {
        $this->getClient()->enableConferenceCallPresentation($this->id);
        return $this;
    }

    /**
     * Stop sharing the screen.
     */
    #[\Override]
    public function disablePresentation(): self
    {
        $this->getClient()->disableConferenceCallPresentation($this->id);
        return $this;
    }

    /**
     * Record conference call media, muxed into a Matroska file in pure PHP.
     *
     * A {@see LocalFile} or {@see WritableStream} records the given `$participant`'s camera+audio (or,
     * with {@see MediaDestination::Presentation}, their screen-share) into it; a {@see LocalDirectory}
     * records *every* transmitting participant, each into its own `<dir>/<userId>.mkv` file plus a
     * `<dir>/<userId>.presentation.mkv` for anyone screen-sharing (participants that start transmitting
     * later are picked up too). Our own media is never recorded. Every recording is plaintext — the
     * frames are decrypted before they are muxed.
     *
     * Participants' frames are stored as-is, so the video track is whatever codec they send and the
     * audio is OPUS; `$format` picks the {@see RecordingFormat::Mkv} (default) or {@see RecordingFormat::Webm}
     * DocType, autodetected from a `.webm` extension. Audio-only OGG OPUS recordings are not supported.
     */
    #[\Override]
    public function setOutput(LocalFile|LocalDirectory|WritableStream $file, mixed $participant = null, MediaDestination $dest = MediaDestination::Camera, ?RecordingFormat $format = null): self
    {
        $this->getClient()->conferenceCallSetOutput($this->id, $file, $participant, $dest, $format);
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
    #[\Override]
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
