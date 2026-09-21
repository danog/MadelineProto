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
use danog\MadelineProto\GroupCall\GroupCallStars;
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
    /** Whether we may change whether new participants join muted (admins). */
    public bool $canChangeJoinMuted = false;
    /** Whether participants are sorted by join date (ascending) rather than by activity. */
    public bool $joinDateAsc = false;
    /** Whether we subscribed to a notification when this scheduled call starts. */
    public bool $scheduleStartSubscribed = false;
    /** Whether we may start streaming video (the video limit is not reached yet). */
    public bool $canStartVideo = false;
    /** Whether a server-side recording of the video is in progress. */
    public bool $recordVideoActive = false;
    /** When the server-side recording started, if one is in progress. */
    public ?int $recordStartDate = null;
    /** Whether this is an RTMP livestream. */
    public bool $rtmpStream = false;
    /** Whether the listeners of this livestream are hidden. */
    public bool $listenersHidden = false;
    /** Whether this is an E2E-encrypted conference call, not associated with any group. */
    public bool $conference = false;
    /** Whether this is a [live story »](https://core.telegram.org/api/group-calls#live-stories). */
    public bool $liveStory = false;
    /** Whether we created this call. */
    public bool $creator = false;
    /** Whether in-call messages are enabled. */
    public bool $messagesEnabled = false;
    /** Whether we may enable or disable in-call messages (admins). */
    public bool $canChangeMessagesEnabled = false;
    /** The invite link of the call, if any. */
    public ?string $inviteLink = null;
    /** When the call is scheduled to start, if it is a scheduled call. */
    public ?int $scheduleDate = null;
    /** How many participants are transmitting video. */
    public ?int $unmutedVideoCount = null;
    /** How many participants may transmit video at once. */
    public int $unmutedVideoLimit = 0;
    /** Live stories: the minimum Telegram Stars donation required to comment, if any. */
    public ?int $sendPaidMessagesStars = null;
    /** Live stories: bot API ID of the peer we send in-call messages as by default. */
    public ?int $defaultSendAs = null;
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
        bool $liveStory = false,
    ) {
        parent::__construct($API);
        $this->id = $call['id'];
        $this->accessHash = $call['access_hash'];
        $this->peerId = $peerId;
        $this->liveStory = $liveStory;
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
        $this->title = isset($call['title']) ? (string) $call['title'] : null;
        $this->participantsCount = (int) ($call['participants_count'] ?? 0);
        $this->joinMuted = (bool) ($call['join_muted'] ?? false);
        $this->canChangeJoinMuted = (bool) ($call['can_change_join_muted'] ?? false);
        $this->joinDateAsc = (bool) ($call['join_date_asc'] ?? false);
        $this->scheduleStartSubscribed = (bool) ($call['schedule_start_subscribed'] ?? false);
        $this->canStartVideo = (bool) ($call['can_start_video'] ?? false);
        $this->recordVideoActive = (bool) ($call['record_video_active'] ?? false);
        $this->recordStartDate = isset($call['record_start_date']) ? (int) $call['record_start_date'] : null;
        $this->rtmpStream = (bool) ($call['rtmp_stream'] ?? false);
        $this->listenersHidden = (bool) ($call['listeners_hidden'] ?? false);
        $this->conference = (bool) ($call['conference'] ?? false);
        $this->creator = (bool) ($call['creator'] ?? false);
        $this->messagesEnabled = (bool) ($call['messages_enabled'] ?? false);
        $this->canChangeMessagesEnabled = (bool) ($call['can_change_messages_enabled'] ?? false);
        $this->inviteLink = isset($call['invite_link']) ? (string) $call['invite_link'] : null;
        $this->scheduleDate = isset($call['schedule_date']) ? (int) $call['schedule_date'] : null;
        $this->unmutedVideoCount = isset($call['unmuted_video_count']) ? (int) $call['unmuted_video_count'] : null;
        $this->unmutedVideoLimit = (int) ($call['unmuted_video_limit'] ?? 0);
        $this->sendPaidMessagesStars = isset($call['send_paid_messages_stars']) ? (int) $call['send_paid_messages_stars'] : null;
        $this->defaultSendAs = isset($call['default_send_as']) ? $this->getClient()->getIdInternal($call['default_send_as']) : null;
        $this->streamDcId = isset($call['stream_dc_id']) ? (int) $call['stream_dc_id'] : null;
    }

    /**
     * Join the group call.
     *
     * @param bool        $muted      Whether to join muted.
     * @param mixed       $joinAs     Peer to join as; only [video chats/livestreams »](https://core.telegram.org/api/group-calls#video-chats-livestreams) may use a peer other than ourselves.
     * @param string|null $inviteHash Invite hash from a [video chat invite link »](https://core.telegram.org/api/links#video-chat-livestream-links), if any.
     */
    #[\Override]
    public function join(bool $muted = false, mixed $joinAs = null, ?string $inviteHash = null): self
    {
        $this->getClient()->joinGroupCallById($this->id, $muted, $joinAs, $inviteHash);
        return $this;
    }

    /**
     * Leave the group call, without ending it for the other participants.
     */
    #[\Override]
    public function leave(): self
    {
        $this->getClient()->leaveGroupCall($this->id);
        return $this;
    }

    /**
     * End the group call for all participants.
     */
    #[\Override]
    public function discard(): self
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
    #[\Override]
    public function isMuted(): bool
    {
        return $this->getClient()->isGroupCallMuted($this->id);
    }

    /**
     * Change the title of the group call.
     */
    #[\Override]
    public function setTitle(string $title): self
    {
        $this->getClient()->setGroupCallTitle($this->id, $title);
        return $this;
    }

    /**
     * Invite users to the group call.
     */
    #[\Override]
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
    #[\Override]
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        return $this->getClient()->exportGroupCallInvite($this->id, $canSelfUnmute);
    }

    /**
     * Remove participants from the group call.
     *
     * A video chat has no notion of removing someone from just the call: as official clients do, this
     * kicks them from the group or channel the call belongs to (removal from a basic group, a ban from
     * a supergroup or channel), which also drops them from the call. Requires the `ban_users` admin right.
     */
    #[\Override]
    public function removeParticipant(mixed ...$participants): self
    {
        $this->getClient()->removeGroupCallParticipants($this->id, ...$participants);
        return $this;
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
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, mixed $sendAs = null): self
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
    public function sendReaction(string $emoji, ?int $customEmojiId = null): self
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
    #[\Override]
    public function deleteMessages(array $ids, bool $reportSpam = false): self
    {
        $this->getClient()->deleteGroupCallMessages($this->id, $ids, $reportSpam);
        return $this;
    }

    /**
     * Delete every in-call message of a participant (admins only).
     *
     * @param bool $reportSpam Also report them as spam.
     */
    #[\Override]
    public function deleteParticipantMessages(mixed $participant, bool $reportSpam = false): self
    {
        $this->getClient()->deleteGroupCallParticipantMessages($this->id, $participant, $reportSpam);
        return $this;
    }

    /**
     * Enable or disable in-call messages (admins only).
     */
    #[\Override]
    public function setMessagesEnabled(bool $enabled): self
    {
        $this->getClient()->toggleGroupCallSettings($this->id, messagesEnabled: $enabled);
        return $this;
    }

    /**
     * Mute or unmute a participant (admins only; a participant muted by an admin may not unmute
     * themselves), or, for a non-admin, mute a participant only for ourselves.
     */
    #[\Override]
    public function muteParticipant(mixed $participant, bool $muted = true): self
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
    public function setParticipantVolume(mixed $participant, int $volume): self
    {
        $this->getClient()->editGroupCallParticipant($this->id, $participant, volume: $volume);
        return $this;
    }

    /**
     * Raise or lower our hand, asking the admins to let us speak when muted by them.
     */
    #[\Override]
    public function raiseHand(bool $raised = true): self
    {
        $this->getClient()->editGroupCallParticipant($this->id, ['_' => 'inputPeerSelf'], raiseHand: $raised);
        return $this;
    }

    /**
     * Pause or resume our own video stream, telling the other participants to keep showing the last
     * frame rather than hiding it.
     */
    #[\Override]
    public function setVideoPaused(bool $paused): self
    {
        $this->getClient()->editGroupCallParticipant($this->id, ['_' => 'inputPeerSelf'], videoPaused: $paused);
        return $this;
    }

    /**
     * Whether new participants join muted (admins only).
     */
    #[\Override]
    public function setJoinMuted(bool $joinMuted): self
    {
        $this->getClient()->toggleGroupCallSettings($this->id, joinMuted: $joinMuted);
        return $this;
    }

    /**
     * Invalidate every invite link exported so far (admins only).
     */
    #[\Override]
    public function resetInviteHash(): self
    {
        $this->getClient()->toggleGroupCallSettings($this->id, resetInviteHash: true);
        return $this;
    }

    /**
     * Live stories only: the minimum Telegram Stars donation required to comment, or null to let
     * everyone comment for free.
     */
    #[\Override]
    public function setPaidMessagesStars(?int $stars): self
    {
        $this->getClient()->toggleGroupCallSettings($this->id, sendPaidMessagesStars: $stars ?? 0);
        return $this;
    }

    /**
     * Start a server-side recording of the call (admins only), sent to the admin's Saved Messages
     * once stopped.
     *
     * @param string|null $title    Title of the recording.
     * @param bool        $video    Whether to record video as well as audio.
     * @param bool        $portrait Whether the video is recorded in portrait (true) or landscape (false) orientation.
     */
    #[\Override]
    public function startRecording(?string $title = null, bool $video = false, bool $portrait = false): self
    {
        $this->getClient()->toggleGroupCallRecord($this->id, true, $title, $video, $portrait);
        return $this;
    }

    /**
     * Stop the server-side recording of the call (admins only).
     */
    #[\Override]
    public function stopRecording(): self
    {
        $this->getClient()->toggleGroupCallRecord($this->id, false);
        return $this;
    }

    /**
     * Start this scheduled call now (admins only).
     */
    #[\Override]
    public function startScheduled(): self
    {
        $this->getClient()->startScheduledGroupCall($this->id);
        return $this;
    }

    /**
     * Subscribe to (or unsubscribe from) a notification from the Telegram service account when this
     * scheduled call starts.
     */
    #[\Override]
    public function setStartSubscription(bool $subscribed): self
    {
        $this->getClient()->toggleGroupCallStartSubscription($this->id, $subscribed);
        return $this;
    }

    /**
     * Live stories only: donate Telegram Stars to the streamer, without a message.
     */
    #[\Override]
    public function donate(int $stars): self
    {
        $this->getClient()->sendGroupCallMessage($this->id, '', null, $stars);
        return $this;
    }

    /**
     * Live stories only: the Telegram Stars donated so far and the top donors.
     */
    #[\Override]
    public function getStars(): GroupCallStars
    {
        return $this->getClient()->getGroupCallStars($this->id);
    }

    /**
     * Live stories only: the peer we send in-call messages as by default.
     */
    #[\Override]
    public function setDefaultSendAs(mixed $peer): self
    {
        $this->getClient()->saveDefaultGroupCallSendAs($this->id, $peer);
        return $this;
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
     * Record group call media, muxed into a Matroska file in pure PHP.
     *
     * A {@see LocalFile} or {@see WritableStream} records the given `$participant`'s camera+audio (or,
     * with {@see MediaDestination::Presentation}, their screen-share) into it; a {@see LocalDirectory}
     * records *every* transmitting participant, each into its own `<dir>/<peerId>.mkv` file plus a
     * `<dir>/<peerId>.presentation.mkv` for anyone screen-sharing (participants that start transmitting
     * later are picked up too). Our own media is never recorded.
     *
     * Participants' frames are stored as-is, so the video track is whatever codec they send and the
     * audio is OPUS; `$format` picks the {@see RecordingFormat::Mkv} (default) or {@see RecordingFormat::Webm}
     * DocType, autodetected from a `.webm` extension. Audio-only OGG OPUS recordings are not supported,
     * except in [stream mode »](https://core.telegram.org/api/group-calls#stream-mode) ({@see self::isStreamMode()}),
     * where there is a single mixed audio stream rather than one per participant: pass no `$participant`
     * (a {@see LocalDirectory} records it as `<dir>/stream.ogg`), and any {@see RecordingFormat}.
     */
    #[\Override]
    public function setOutput(LocalFile|LocalDirectory|WritableStream $file, mixed $participant = null, MediaDestination $dest = MediaDestination::Camera, ?RecordingFormat $format = null): self
    {
        $this->getClient()->groupCallSetOutput($this->id, $file, $participant, $dest, $format);
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
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    #[\Override]
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPlayBlocking($this->id, $file, $dest);
        return $this;
    }

    /**
     * Play file.
     */
    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallPlay($this->id, $file, $dest);
        return $this;
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallSkipPlay($this->id, $dest);
        return $this;
    }

    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): self
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
    public function pause(MediaDestination $dest = MediaDestination::Camera): self
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
    public function resume(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->getClient()->groupCallResumePlay($this->id, $dest);
        return $this;
    }

    /**
     * Files to play on hold.
     */
    #[\Override]
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
    public function enablePresentation(): self
    {
        $this->getClient()->enableGroupCallPresentation($this->id);
        return $this;
    }

    /**
     * Stop sharing the screen.
     */
    #[\Override]
    public function disablePresentation(): self
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
