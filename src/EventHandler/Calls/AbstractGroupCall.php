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
use danog\MadelineProto\GroupCall\Participant;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;

/**
 * What a [video chat/livestream »](https://core.telegram.org/api/group-calls#video-chats-livestreams)
 * ({@see GroupCall}) and a [live story »](https://core.telegram.org/api/group-calls#live-stories)
 * ({@see LiveStory}) have in common: both are group calls the server mixes (not end-to-end encrypted),
 * with the same joining, participants, playback, recording, in-call messages and settings.
 *
 * See https://core.telegram.org/api/group-calls for more info.
 */
abstract class AbstractGroupCall extends Update implements MultiCall
{
    /** Group call ID. */
    public readonly int $id;
    /** Access hash of the group call. */
    public readonly int $accessHash;
    /** Bot API ID of the peer the call belongs to (the group or channel of a video chat, the poster of a live story), if known. */
    public readonly ?int $peerId;

    /** Number of participants. */
    public int $participantsCount = 0;
    /** Whether new participants join muted. */
    public bool $joinMuted = false;
    /** Whether we may change whether new participants join muted (admins). */
    public bool $canChangeJoinMuted = false;
    /** Whether participants are sorted by join date (ascending) rather than by activity. */
    public bool $joinDateAsc = false;
    /** Whether we may start streaming video (the video limit is not reached yet). */
    public bool $canStartVideo = false;
    /** Whether the media is published by an external RTMP application rather than by a participant. */
    public bool $rtmpStream = false;
    /** Whether the listeners are hidden. */
    public bool $listenersHidden = false;
    /** Whether we created this call. */
    public bool $creator = false;
    /** Whether in-call messages are enabled. */
    public bool $messagesEnabled = false;
    /** Whether we may enable or disable in-call messages (admins). */
    public bool $canChangeMessagesEnabled = false;
    /** The invite link of the call, if any. */
    public ?string $inviteLink = null;
    /** How many participants are transmitting video. */
    public ?int $unmutedVideoCount = null;
    /** How many participants may transmit video at once. */
    public int $unmutedVideoLimit = 0;
    /** The DC to use when downloading media chunks in stream mode. */
    public ?int $streamDcId = null;
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
        ?int $peerId = null,
    ) {
        parent::__construct($API);
        $this->id = (int) $call['id'];
        $this->accessHash = (int) $call['access_hash'];
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
            $this->duration = isset($call['duration']) ? (int) $call['duration'] : null;
            return;
        }
        $this->participantsCount = (int) ($call['participants_count'] ?? 0);
        $this->joinMuted = (bool) ($call['join_muted'] ?? false);
        $this->canChangeJoinMuted = (bool) ($call['can_change_join_muted'] ?? false);
        $this->joinDateAsc = (bool) ($call['join_date_asc'] ?? false);
        $this->canStartVideo = (bool) ($call['can_start_video'] ?? false);
        $this->rtmpStream = (bool) ($call['rtmp_stream'] ?? false);
        $this->listenersHidden = (bool) ($call['listeners_hidden'] ?? false);
        $this->creator = (bool) ($call['creator'] ?? false);
        $this->messagesEnabled = (bool) ($call['messages_enabled'] ?? false);
        $this->canChangeMessagesEnabled = (bool) ($call['can_change_messages_enabled'] ?? false);
        $this->inviteLink = isset($call['invite_link']) ? (string) $call['invite_link'] : null;
        $this->unmutedVideoCount = isset($call['unmuted_video_count']) ? (int) $call['unmuted_video_count'] : null;
        $this->unmutedVideoLimit = (int) ($call['unmuted_video_limit'] ?? 0);
        $this->streamDcId = isset($call['stream_dc_id']) ? (int) $call['stream_dc_id'] : null;
    }

    /**
     * Join the call.
     *
     * @param bool $muted Whether to join muted.
     */
    #[\Override]
    public function join(bool $muted = false): static
    {
        $this->getClient()->joinGroupCallById($this->id, $muted);
        return $this;
    }

    /**
     * Leave the group call, without ending it for the other participants.
     */
    #[\Override]
    public function leave(): static
    {
        $this->getClient()->leaveGroupCall($this->id);
        return $this;
    }

    /**
     * End the group call for all participants.
     */
    #[\Override]
    public function discard(): static
    {
        $this->getClient()->discardGroupCall($this->id);
        return $this;
    }

    /**
     * Whether we are currently in the group call (joined and not left).
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isJoined(): bool
    {
        return $this->getCallState() === GroupCallState::JOINED;
    }

    /**
     * Get the state of the group call.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getCallState(): GroupCallState
    {
        return $this->getClient()->getGroupCallState($this->id);
    }

    /**
     * Get all known participants, indexed by their bot API peer ID.
     *
     * @return array<int, Participant>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getParticipants(): array
    {
        return $this->getClient()->getGroupCallParticipants($this->id);
    }

    /**
     * A participant of the call by their id, username or peer, or null if they are not in it.
     */
    #[\Override]
    public function getParticipant(mixed $participant): ?Participant
    {
        return $this->getClient()->getGroupCallParticipant($this->id, $participant);
    }

    /**
     * Video chats and livestreams are not end-to-end encrypted, so there is no key to verify: always null.
     *
     * @psalm-pure
     */
    #[\Override]
    public function getVisualization(): ?array
    {
        return null;
    }

    /**
     * Mute or unmute our own audio stream.
     */
    #[\Override]
    public function setMuted(bool $muted = true): static
    {
        $this->getClient()->setGroupCallMuted($this->id, $muted);
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
        return $this->getClient()->isGroupCallMuted($this->id);
    }

    /**
     * Send an [in-call message »](https://core.telegram.org/api/group-calls#in-call-messages), shown as
     * an overlay by the participants' clients (there is no chat history), if messages are enabled.
     *
     * @param string         $message   The text; markup in `$parseMode` is converted to entities.
     * @param ParseMode|null $parseMode Whether to parse HTML or Markdown markup in the text.
     * @param int|null       $paidStars Live stories only: Telegram Stars to donate with the message (at least {@see self::$sendPaidMessagesStars}).
     * @param mixed          $sendAs    Live stories only: the peer to send the message as.
     */
    #[\Override]
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, mixed $sendAs = null): static
    {
        $this->getClient()->sendGroupCallMessage($this->id, $message, $parseMode, $paidStars, $sendAs);
        return $this;
    }

    /**
     * Send an [in-call reaction »](https://core.telegram.org/api/group-calls#in-call-reactions).
     *
     * @param string   $emoji         The emoji.
     * @param int|null $customEmojiId The document id of a custom emoji to send instead, `$emoji` being its fallback.
     */
    #[\Override]
    public function sendReaction(string $emoji, ?int $customEmojiId = null): static
    {
        $this->getClient()->sendGroupCallReaction($this->id, $emoji, $customEmojiId);
        return $this;
    }

    /**
     * Delete in-call messages: our own, or anyone's if we are an admin.
     *
     * @param list<int> $ids        IDs of the messages to delete.
     * @param bool      $reportSpam Also report them as spam (admins only).
     */
    public function deleteMessages(array $ids, bool $reportSpam = false): static
    {
        $this->getClient()->deleteGroupCallMessages($this->id, $ids, $reportSpam);
        return $this;
    }

    /**
     * Delete every in-call message of a participant (admins only).
     *
     * @param bool $reportSpam Also report them as spam.
     */
    public function deleteParticipantMessages(mixed $participant, bool $reportSpam = false): static
    {
        $this->getClient()->deleteGroupCallParticipantMessages($this->id, $participant, $reportSpam);
        return $this;
    }

    /**
     * Enable or disable in-call messages (admins only).
     */
    #[\Override]
    public function setMessagesEnabled(bool $enabled): static
    {
        $this->getClient()->toggleGroupCallSettings($this->id, messagesEnabled: $enabled);
        return $this;
    }

    /**
     * Mute or unmute a participant (admins only; a participant muted by an admin may not unmute
     * themselves), or, for a non-admin, mute a participant only for ourselves.
     */
    #[\Override]
    public function muteParticipant(mixed $participant, bool $muted = true): static
    {
        $this->getClient()->editGroupCallParticipant($this->id, $participant, muted: $muted);
        return $this;
    }

    /**
     * Set our local playback volume of a participant.
     *
     * @param int $volume From 1 to 20000, where 10000 is 100%.
     */
    #[\Override]
    public function setParticipantVolume(mixed $participant, int $volume): static
    {
        $this->getClient()->editGroupCallParticipant($this->id, $participant, volume: $volume);
        return $this;
    }

    /**
     * Pause or resume our own video stream, telling the other participants to keep showing the last
     * frame rather than hiding it.
     */
    #[\Override]
    public function setVideoPaused(bool $paused): static
    {
        $this->getClient()->editGroupCallParticipant($this->id, ['_' => 'inputPeerSelf'], videoPaused: $paused);
        return $this;
    }

    /**
     * Whether new participants join muted (admins only).
     */
    #[\Override]
    public function setJoinMuted(bool $joinMuted): static
    {
        $this->getClient()->toggleGroupCallSettings($this->id, joinMuted: $joinMuted);
        return $this;
    }

    /**
     * Invalidate every invite link exported so far (admins only).
     */
    #[\Override]
    public function resetInviteHash(): static
    {
        $this->getClient()->toggleGroupCallSettings($this->id, resetInviteHash: true);
        return $this;
    }

    /**
     * Record group call media, muxed into a Matroska file in pure PHP.
     *
     * Only a {@see LocalDirectory} is accepted: it records every transmitting participant — or only the
     * given `$participant` — as `<dir>/<peerId>.<n>_<streams>.mkv` files, one per combination of the
     * audio, camera video and screen share they send, each on or off at any time (see
     * {@see Call::setOutput()}; participants that start transmitting later are picked up too). Our own
     * media is never recorded.
     *
     * Participants' frames are stored as-is, so the video tracks are whatever codec they send and the
     * audio is OPUS; `$format` picks the {@see RecordingFormat::Mkv} (default) or {@see RecordingFormat::Webm}
     * DocType, autodetected from a `.webm` extension. Audio-only OGG OPUS recordings are not supported,
     * except in [stream mode »](https://core.telegram.org/api/group-calls#stream-mode) ({@see self::isStreamMode()}),
     * where there is a single mixed audio stream rather than one per participant: pass no `$participant`
     * (a {@see LocalDirectory} records it as `<dir>/stream.ogg`), and any {@see RecordingFormat}.
     */
    #[\Override]
    public function setOutput(LocalFile|LocalDirectory|WritableStream $file, mixed $participant = null, ?RecordingFormat $format = null): static
    {
        $this->getClient()->groupCallSetOutput($this->id, $file, $participant, $format);
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
    #[\Override]
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    #[\Override]
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallPlayBlocking($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play file.
     */
    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallSkipPlay($this->id, $dest);
        return $this;
    }

    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallStopPlay($this->id, $dest);
        return $this;
    }

    /**
     * Pauses the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function pause(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallPausePlay($this->id, $dest);
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
        return $this->getClient()->isGroupCallPlayPaused($this->id, $dest);
    }

    /**
     * Resumes the currently playing file.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function resume(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->getClient()->groupCallResumePlay($this->id, $dest);
        return $this;
    }

    /**
     * Files to play on hold.
     */
    #[\Override]
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): static
    {
        $this->getClient()->groupCallPlayOnHold($this->id, $dest, ...$files);
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
        return $this->getClient()->groupCallGetCurrent($this->id, $dest);
    }

    /**
     * Start sharing a screen: a second connection (phone.joinGroupCallPresentation) whose video is
     * transmitted on the {@see MediaDestination::Presentation} stream. Idempotent; requires the call to
     * be joined.
     */
    #[\Override]
    public function enablePresentation(): static
    {
        $this->getClient()->enableGroupCallPresentation($this->id);
        return $this;
    }

    /**
     * Stop sharing the screen.
     */
    #[\Override]
    public function disablePresentation(): static
    {
        $this->getClient()->disableGroupCallPresentation($this->id);
        return $this;
    }

    /**
     * Whether a screen-share is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isSharingScreen(): bool
    {
        return $this->getClient()->isGroupCallSharingScreen($this->id);
    }

    /**
     * Whether the server switched us to [stream mode »](https://core.telegram.org/api/group-calls#stream-mode)
     * (a large livestream, or an RTMP one): the call's media is received by downloading chunks, and
     * there is a single mixed stream to record with {@see self::setOutput()} (pass no participant).
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isStreamMode(): bool
    {
        return $this->getClient()->isGroupCallStreamMode($this->id);
    }

    /**
     * Whether the call's media is published by a single external RTMP publisher.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isRtmpMode(): bool
    {
        return $this->getClient()->isGroupCallRtmpMode($this->id);
    }

    /**
     * Get call representation.
     *
     * @psalm-mutation-free
     */
    abstract public function __toString(): string;
}
