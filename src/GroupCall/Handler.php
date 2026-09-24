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

namespace danog\MadelineProto\GroupCall;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use AssertionError;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCall;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCallParticipant;
use danog\MadelineProto\EventHandler\Calls\ConferenceCall as ConferenceCallUpdate;
use danog\MadelineProto\EventHandler\Calls\ConferenceCallParticipant;
use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\EventHandler\Calls\GroupCallStars;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\EventHandler\Calls\LiveStory;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\TextEntities;
use danog\MadelineProto\Tgcalls\E2E\ConferenceCall;
use Revolt\EventLoop;

/**
 * Manages group calls (video chats, livestreams and live stories).
 *
 * See https://core.telegram.org/api/group-calls for more info.
 *
 * @psalm-import-type StreamMask from \danog\MadelineProto\CallStream
 *
 * @internal
 */
trait Handler
{
    /** @var array<int, GroupCallController> */
    private array $groupCalls = [];
    /** @var array<int, ConferenceCall> End-to-end encrypted conference calls, by call id. */
    private array $conferenceCalls = [];

    /**
     * Register a live end-to-end encrypted conference so its chain-block and encrypted-message
     * updates are routed to it.
     *
     * @internal
     *
     * @psalm-external-mutation-free
     */
    public function registerConferenceCall(int $id, ConferenceCall $call): void
    {
        $this->conferenceCalls[$id] = $call;
    }

    /**
     * @internal
     *
     * @psalm-external-mutation-free
     */
    public function unregisterConferenceCall(int $id): void
    {
        unset($this->conferenceCalls[$id]);
    }

    /**
     * Create a group call (video chat or livestream) in the specified group or channel.
     *
     * Requires the `manage_call` admin right, see
     * [video chats/livestreams »](https://core.telegram.org/api/group-calls#video-chats-livestreams).
     *
     * @param string|int       $peer         The group or channel where the call should be created.
     * @param string|null $title        Custom title, defaults to the group/channel name.
     * @param int|null    $scheduleDate If set, creates a scheduled call for the specified UNIX timestamp.
     * @param bool        $rtmpStream   Whether the call's media is published by an external RTMP application.
     */
    public function createGroupCall(string|int $peer, ?string $title = null, ?int $scheduleDate = null, bool $rtmpStream = false): GroupCall
    {
        $params = [
            'peer' => $peer,
            'random_id' => random_int(-(2**31), (2**31)-1),
            'rtmp_stream' => $rtmpStream,
        ];
        if ($title !== null) {
            $params['title'] = $title;
        }
        if ($scheduleDate !== null) {
            $params['schedule_date'] = $scheduleDate;
        }
        $updates = $this->methodCallAsyncRead('phone.createGroupCall', $params);
        // The updateGroupCall in the response is dispatched to handleGroupCallUpdate through
        // saveUpdate, which starts tracking the new call. Look it up (fetching it if that update has
        // not landed yet) to return its handle.
        $inputCall = null;
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCall' && $update['call']['_'] === 'groupCall') {
                $inputCall = [
                    '_' => 'inputGroupCall',
                    'id' => $update['call']['id'],
                    'access_hash' => $update['call']['access_hash'],
                ];
                break;
            }
        }
        $controller = $inputCall !== null
            ? $this->getGroupCallByInput($inputCall, $this->getIdInternal($peer))
            : null;
        if ($controller === null) {
            throw new AssertionError('The server did not return the created group call!');
        }
        \assert($controller->public instanceof GroupCall);
        return $controller->public;
    }

    /**
     * Create and join a new end-to-end encrypted conference call, with ourselves as the only
     * participant. Others join it with {@see self::joinConferenceCall()} using the returned call id.
     *
     * See [end-to-end encrypted group calls »](https://core.telegram.org/api/end-to-end/group-calls).
     * Requires the `sodium` and `openssl` PHP extensions.
     *
     * @param bool $muted Whether to join muted.
     */
    public function createConferenceCall(bool $muted = false): ConferenceCallUpdate
    {
        $conference = new ConferenceCall($this);
        $conference->create($muted);
        return $conference->getPublic();
    }

    /**
     * Join an existing end-to-end encrypted conference call.
     *
     * See [end-to-end encrypted group calls »](https://core.telegram.org/api/end-to-end/group-calls).
     * Requires the `sodium` and `openssl` PHP extensions.
     *
     * @param array $call  The `groupCall` (or `inputGroupCall`) of the conference to join.
     * @param bool  $muted Whether to join muted.
     */
    public function joinConferenceCall(array $call, bool $muted = false): ConferenceCallUpdate
    {
        $conference = new ConferenceCall($this);
        $conference->setCall($call);
        $conference->join($muted);
        return $conference->getPublic();
    }

    /**
     * Get a live end-to-end encrypted conference call this session is in, by its call id.
     *
     * @psalm-mutation-free
     */
    public function getConferenceCall(int $id): ?ConferenceCallUpdate
    {
        return ($this->conferenceCalls[$id] ?? null)?->getPublic();
    }

    /**
     * Resolve the live controller of a conference call we are tracking.
     *
     * @internal
     *
     * @psalm-external-mutation-free
     */
    private function getConferenceCallController(int $id): ConferenceCall
    {
        return $this->conferenceCalls[$id] ?? throw new AssertionError('Unknown conference call!');
    }

    /**
     * Whether we are currently in a conference call.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isConferenceCallJoined(int $id): bool
    {
        return $this->conferenceCalls[$id]?->isJoined() ?? false;
    }

    /**
     * Leave a conference call, without ending it for the other participants.
     *
     * @internal
     */
    public function leaveConferenceCall(int $id): void
    {
        $this->getConferenceCallController($id)->leave();
    }

    /**
     * End a conference call for everyone (creator only) and leave it.
     *
     * @internal
     */
    public function discardConferenceCall(int $id): void
    {
        $this->getConferenceCallController($id)->discard();
    }

    /**
     * Get the participants of a conference call, keyed by their user id.
     *
     * @return array<int, ConferenceCallParticipant>
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getConferenceCallParticipants(int $id): array
    {
        return $this->getConferenceCallController($id)->getParticipants();
    }

    /**
     * Remove participants from a conference call, rekeying for the remaining members.
     *
     * @internal
     */
    public function removeConferenceCallParticipants(int $id, string|int ...$participants): void
    {
        $this->getConferenceCallController($id)->removeParticipant(...$participants);
    }

    /**
     * Change the title of a conference call.
     *
     * @internal
     */
    public function setConferenceCallTitle(int $id, string $title): void
    {
        $this->getConferenceCallController($id)->setTitle($title);
    }

    /**
     * Invite users to a conference call.
     *
     * @internal
     */
    public function inviteToConferenceCall(int $id, string|int ...$users): void
    {
        $this->getConferenceCallController($id)->invite(...$users);
    }

    /**
     * Export a conference link to a conference call.
     *
     * @internal
     */
    public function exportConferenceCallInvite(int $id): string
    {
        return $this->getConferenceCallController($id)->exportInvite();
    }

    /**
     * The four verification emojis of a conference call, or null until every nonce has been revealed.
     *
     * @return list<string>|null
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getConferenceCallVisualization(int $id): ?array
    {
        return $this->getConferenceCallController($id)->getVisualization();
    }

    /**
     * Send an end-to-end encrypted in-call message to every participant of a conference call.
     *
     * @internal
     */
    public function sendConferenceCallMessage(int $id, string $message, ?ParseMode $parseMode = null): void
    {
        $this->getConferenceCallController($id)->sendMessage($message, $parseMode);
    }

    /**
     * Send an end-to-end encrypted in-call reaction to every participant of a conference call.
     *
     * @internal
     */
    public function sendConferenceCallReaction(int $id, string $emoji, ?int $customEmojiId = null): void
    {
        $this->getConferenceCallController($id)->sendReaction($emoji, $customEmojiId);
    }

    /**
     * Change a participant's state in a conference call (phone.editGroupCallParticipant).
     *
     * @internal
     */
    public function editConferenceCallParticipant(int $id, string|int|array $participant, ?bool $muted = null, ?int $volume = null, ?bool $videoPaused = null): void
    {
        $this->getConferenceCallController($id)->editParticipant($participant, $muted, $volume, $videoPaused);
    }

    /**
     * Change the settings of a conference call (phone.toggleGroupCallSettings).
     *
     * @internal
     */
    public function toggleConferenceCallSettings(int $id, ?bool $joinMuted = null, bool $resetInviteHash = false, ?bool $messagesEnabled = null): void
    {
        $this->getConferenceCallController($id)->toggleSettings($joinMuted, $resetInviteHash, $messagesEnabled);
    }

    /**
     * Get an [end-to-end encrypted conference call »](https://core.telegram.org/api/group-calls#conference-calls)
     * from its [conference link »](https://core.telegram.org/api/links#conference-links) slug, or null if
     * the link is not valid any more.
     */
    public function getConferenceCallBySlug(string $slug): ?ConferenceCallUpdate
    {
        return $this->getConferenceCallByInput(['_' => 'inputGroupCallSlug', 'slug' => $slug]);
    }

    /**
     * Join an end-to-end encrypted conference call from its conference link slug.
     *
     * @param bool $muted Whether to join muted.
     */
    public function joinConferenceCallBySlug(string $slug, bool $muted = false): ConferenceCallUpdate
    {
        $call = $this->getConferenceCallBySlug($slug) ?? throw new AssertionError('This conference link is not valid any more!');
        return $call->join($muted);
    }

    /**
     * Join an end-to-end encrypted conference call from the service message that invited us to it
     * (a `messageActionConferenceCall`, see {@see \danog\MadelineProto\EventHandler\Message\Service\DialogConferenceCall}).
     *
     * @param int  $msgId ID of the invitation message.
     * @param bool $muted Whether to join muted.
     */
    public function joinConferenceCallByInviteMessage(int $msgId, bool $muted = false): ConferenceCallUpdate
    {
        $call = $this->getConferenceCallByInput(['_' => 'inputGroupCallInviteMessage', 'msg_id' => $msgId])
            ?? throw new AssertionError('This conference call is not active any more!');
        return $call->join($muted);
    }

    /**
     * Decline an invitation to a conference call.
     *
     * @param int $msgId ID of the invitation message (a `messageActionConferenceCall`).
     */
    public function declineConferenceCallInvite(int $msgId): void
    {
        $this->methodCallAsyncRead('phone.declineConferenceCallInvite', ['msg_id' => $msgId]);
    }

    /**
     * Resolve a conference call from any InputGroupCall: the live one we are in, or a handle to one we
     * are not in (yet).
     */
    private function getConferenceCallByInput(array $inputCall): ?ConferenceCallUpdate
    {
        $result = $this->methodCallAsyncRead('phone.getGroupCall', ['call' => $inputCall, 'limit' => 1]);
        $call = $result['call'];
        if ($call['_'] === 'groupCallDiscarded' || !($call['conference'] ?? false)) {
            return null;
        }
        if (isset($this->conferenceCalls[$call['id']])) {
            return $this->conferenceCalls[$call['id']]->getPublic();
        }
        return new ConferenceCallUpdate($this, $call);
    }

    /**
     * Whether a screen-share is currently being transmitted in a conference call.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isConferenceCallSharingScreen(int $id): bool
    {
        return $this->getConferenceCallController($id)->isSharingScreen();
    }

    /**
     * Start sharing a screen in a conference call.
     *
     * @internal
     */
    public function enableConferenceCallPresentation(int $id): void
    {
        $this->getConferenceCallController($id)->enablePresentation();
    }

    /**
     * Stop sharing the screen in a conference call.
     *
     * @internal
     */
    public function disableConferenceCallPresentation(int $id): void
    {
        $this->getConferenceCallController($id)->disablePresentation();
    }

    /**
     * Mute or unmute our own audio stream in a conference call.
     *
     * @internal
     */
    public function setConferenceCallMuted(int $id, bool $muted = true): void
    {
        $this->getConferenceCallController($id)->setMuted($muted);
    }

    /**
     * Whether our own audio stream is muted in a conference call.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isConferenceCallMuted(int $id): bool
    {
        return $this->getConferenceCallController($id)->isMuted();
    }

    /**
     * Play a file in a conference call.
     *
     * @internal
     */
    public function conferenceCallPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getConferenceCallController($id)->play($file, $dest);
    }

    /**
     * Play a file in a conference call, blocking until it has finished playing if a stream is provided.
     *
     * @internal
     */
    public function conferenceCallPlayBlocking(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getConferenceCallController($id)->playBlocking($file, $dest);
    }

    /**
     * Record one participant of a conference call into a single file (or stream) with a fixed set of
     * tracks, see {@see \danog\MadelineProto\EventHandler\Calls\ConferenceCall::setOutput()}.
     *
     * @internal
     *
     * @param ?StreamMask $streams The streams to record, as a bitmask of {@see CallStream} flags, or null for every available one.
     *
     * @return int The streams the participant currently sends, as a bitmask of {@see CallStream} flags.
     */
    public function conferenceCallSetOutput(int $id, LocalFile|WritableStream $file, string|int|null $participant = null, ?RecordingFormat $format = null, ?int $streams = null): int
    {
        return $this->getConferenceCallController($id)->setOutput($file, $participant, $format, $streams);
    }

    /**
     * Record conference call media into a directory: every transmitting participant (or only the
     * given one) as its own numbered series of files, see
     * {@see \danog\MadelineProto\EventHandler\Calls\ConferenceCall::setOutputFolder()}.
     *
     * @internal
     */
    public function conferenceCallSetOutputFolder(int $id, LocalDirectory $dir, string|int|null $participant = null, ?RecordingFormat $format = null): void
    {
        $this->getConferenceCallController($id)->setOutputFolder($dir, $participant, $format);
    }

    /**
     * Get the state of a conference call.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getConferenceCallState(int $id): GroupCallState
    {
        return ($this->conferenceCalls[$id] ?? null)?->getCallState() ?? GroupCallState::NOT_JOINED;
    }

    /**
     * Files to play on hold in a conference call.
     *
     * @internal
     */
    public function conferenceCallPlayOnHold(int $id, MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->getConferenceCallController($id)->playOnHold($dest, ...$files);
    }

    /**
     * Skip to the next file in the playlist of a conference call.
     *
     * @internal
     */
    public function conferenceCallSkipPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getConferenceCallController($id)->skip($dest);
    }

    /**
     * Stop playing all files in a conference call, clearing the main and the hold playlist.
     *
     * @internal
     */
    public function conferenceCallStopPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getConferenceCallController($id)->stop($dest);
    }

    /**
     * Pause playback of the current file in a conference call.
     *
     * @internal
     *
     * @psalm-external-mutation-free
     */
    public function conferenceCallPausePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getConferenceCallController($id)->pause($dest);
    }

    /**
     * Resume playback of the current file in a conference call.
     *
     * @internal
     *
     * @psalm-external-mutation-free
     */
    public function conferenceCallResumePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getConferenceCallController($id)->resume($dest);
    }

    /**
     * Whether the currently playing file of a conference call is paused.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isConferenceCallPlayPaused(int $id, MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->getConferenceCallController($id)->isPaused($dest);
    }

    /**
     * Get the file that is currently being played in a conference call.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function conferenceCallGetCurrent(int $id, MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getConferenceCallController($id)->getCurrent($dest);
    }

    /**
     * Get the group call (video chat or livestream) currently active in a group or channel.
     */
    public function getGroupCall(string|int $peer): ?GroupCall
    {
        $full = $this->getFullInfo($peer);
        $inputCall = $full['full']['call'] ?? null;
        if ($inputCall === null) {
            return null;
        }
        $call = $this->getGroupCallByInput($inputCall, $this->getIdInternal($peer))?->public;
        return $call instanceof GroupCall ? $call : null;
    }

    /**
     * Get a group call from its
     * [conference deep link »](https://core.telegram.org/api/links#conference-links) slug.
     */
    public function getGroupCallBySlug(string $slug): ?GroupCall
    {
        $call = $this->getGroupCallByInput(['_' => 'inputGroupCallSlug', 'slug' => $slug])?->public;
        return $call instanceof GroupCall ? $call : null;
    }

    /**
     * Join the group call currently active in a group or channel.
     *
     * @param string|int       $peer       The group or channel whose call should be joined.
     * @param bool        $muted      Whether to join muted.
     * @param string|int|null  $joinAs     Peer to join as, defaults to ourselves.
     * @param string|null $inviteHash Invite hash from a video chat invite link, if any.
     */
    public function joinGroupCall(string|int $peer, bool $muted = false, string|int|null $joinAs = null, ?string $inviteHash = null): GroupCall
    {
        $full = $this->getFullInfo($peer);
        $inputCall = $full['full']['call'] ?? null;
        if ($inputCall === null) {
            throw new AssertionError('There is no active group call in this chat!');
        }
        $controller = $this->getGroupCallByInput($inputCall, $this->getIdInternal($peer));
        if ($controller === null) {
            throw new AssertionError('Could not fetch the group call!');
        }
        $controller->join($muted, $joinAs, $inviteHash);
        \assert($controller->public instanceof GroupCall);
        return $controller->public;
    }

    /**
     * Join a group call we already have a {@see GroupCall} object for.
     *
     * @internal
     */
    public function joinGroupCallById(int $id, bool $muted = false, string|int|null $joinAs = null, ?string $inviteHash = null): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->join($muted, $joinAs, $inviteHash);
    }

    /**
     * Fetch (and cache) a group call from any subtype of
     * [InputGroupCall](https://core.telegram.org/type/InputGroupCall).
     *
     * @internal
     */
    public function getGroupCallByInput(array $inputCall, ?int $peerId = null, bool $liveStory = false): ?GroupCallController
    {
        if ($inputCall['_'] === 'inputGroupCall' && isset($this->groupCalls[$inputCall['id']])) {
            return $this->groupCalls[$inputCall['id']];
        }
        $result = $this->methodCallAsyncRead('phone.getGroupCall', ['call' => $inputCall, 'limit' => 100]);
        $call = $result['call'];
        if ($call['_'] === 'groupCallDiscarded') {
            return null;
        }
        if (isset($this->groupCalls[$call['id']])) {
            return $this->groupCalls[$call['id']];
        }
        $controller = new GroupCallController($this, $call, $peerId, $liveStory);
        $this->groupCalls[$call['id']] = $controller;
        // We already have the full call and its first page of participants: no need to refetch.
        $controller->applyGroupCall($result);
        return $controller;
    }

    /**
     * @internal
     *
     * @psalm-external-mutation-free
     */
    public function cleanupGroupCall(int $id): void
    {
        unset($this->groupCalls[$id]);
    }

    /**
     * Get all group calls (video chats, livestreams and live stories) we're currently tracking, indexed by their ID.
     *
     * @return array<int, AbstractGroupCall>
     */
    public function getAllGroupCalls(): array
    {
        return array_map(static fn (GroupCallController $c): AbstractGroupCall => $c->public, $this->groupCalls);
    }

    /**
     * Leave a group call, without ending it for the other participants.
     */
    public function leaveGroupCall(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->leave();
    }

    /**
     * End a group call for all participants.
     */
    public function discardGroupCall(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->discard();
    }

    /**
     * Get the state of a group call.
     *
     * @psalm-mutation-free
     */
    public function getGroupCallState(int $id): GroupCallState
    {
        return ($this->groupCalls[$id] ?? null)?->getCallState() ?? GroupCallState::LEFT;
    }

    /**
     * Whether a group call we are tracking is a live story (and thus has {@see LiveStoryParticipant}
     * participants), false for a video chat/livestream or a call we do not track.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isGroupCallLiveStory(int $id): bool
    {
        return ($this->groupCalls[$id] ?? null)?->public instanceof LiveStory;
    }

    /**
     * Get the participants of a group call, indexed by their bot API peer ID.
     *
     * @return array<int, AbstractGroupCallParticipant>
     *
     * @psalm-mutation-free
     */
    public function getGroupCallParticipants(int $id): array
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->getParticipants();
    }

    /**
     * Mute or unmute our own audio stream in a group call.
     */
    public function setGroupCallMuted(int $id, bool $muted = true): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->setMuted($muted);
    }

    /**
     * Whether our own audio stream is muted in a group call.
     *
     * @psalm-mutation-free
     */
    public function isGroupCallMuted(int $id): bool
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->isMuted();
    }

    /**
     * Change the title of a group call.
     */
    public function setGroupCallTitle(int $id, string $title): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->setTitle($title);
    }

    /**
     * Invite users to a group call.
     */
    public function inviteToGroupCall(int $id, string|int ...$users): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->invite(...$users);
    }

    /**
     * Export an invite link for a group call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; admins only.
     */
    public function exportGroupCallInvite(int $id, bool $canSelfUnmute = false): string
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->exportInvite($canSelfUnmute);
    }

    /**
     * Change a participant's state in a group call (phone.editGroupCallParticipant): mute/unmute them,
     * set our playback volume of them, raise/lower our own hand, pause/resume our own video.
     *
     * @internal
     */
    public function editGroupCallParticipant(int $id, string|int|array $participant, ?bool $muted = null, ?int $volume = null, ?bool $raiseHand = null, ?bool $videoStopped = null, ?bool $videoPaused = null, ?bool $presentationPaused = null): void
    {
        $this->getGroupCallController($id)->editParticipant($participant, $muted, $volume, $raiseHand, $videoStopped, $videoPaused, $presentationPaused);
    }

    /**
     * Change the settings of a group call (phone.toggleGroupCallSettings, admins only).
     *
     * @internal
     */
    public function toggleGroupCallSettings(int $id, ?bool $joinMuted = null, bool $resetInviteHash = false, ?bool $messagesEnabled = null, ?int $sendPaidMessagesStars = null): void
    {
        $this->getGroupCallController($id)->toggleSettings($joinMuted, $resetInviteHash, $messagesEnabled, $sendPaidMessagesStars);
    }

    /**
     * Start or stop a server-side recording of a group call (phone.toggleGroupCallRecord, admins only).
     *
     * @internal
     */
    public function toggleGroupCallRecord(int $id, bool $start, ?string $title = null, bool $video = false, bool $portrait = false): void
    {
        $this->getGroupCallController($id)->toggleRecord($start, $title, $video, $portrait);
    }

    /**
     * Start a scheduled group call now (admins only).
     *
     * @internal
     */
    public function startScheduledGroupCall(int $id): void
    {
        $this->getGroupCallController($id)->startScheduled();
    }

    /**
     * Subscribe to (or unsubscribe from) a notification when a scheduled group call starts.
     *
     * @internal
     */
    public function toggleGroupCallStartSubscription(int $id, bool $subscribed): void
    {
        $this->getGroupCallController($id)->setStartSubscription($subscribed);
    }

    /**
     * Send an in-call message (or, in a live story, a donation) in a group call.
     *
     * @internal
     */
    public function sendGroupCallMessage(int $id, string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, string|int|null $sendAs = null): void
    {
        $this->getGroupCallController($id)->sendMessage($message, $parseMode, $paidStars, $sendAs);
    }

    /**
     * Send an in-call reaction in a group call.
     *
     * @internal
     */
    public function sendGroupCallReaction(int $id, string $emoji, ?int $customEmojiId = null): void
    {
        $this->getGroupCallController($id)->sendReaction($emoji, $customEmojiId);
    }

    /**
     * Delete in-call messages of a group call.
     *
     * @param list<int> $ids
     *
     * @internal
     */
    public function deleteGroupCallMessages(int $id, array $ids, bool $reportSpam = false): void
    {
        $this->getGroupCallController($id)->deleteMessages($ids, $reportSpam);
    }

    /**
     * Delete every in-call message of a participant of a group call (admins only).
     *
     * @internal
     */
    public function deleteGroupCallParticipantMessages(int $id, string|int $participant, bool $reportSpam = false): void
    {
        $this->getGroupCallController($id)->deleteParticipantMessages($participant, $reportSpam);
    }

    /**
     * The Telegram Stars donated to a live story so far, and its top donors.
     *
     * @internal
     */
    public function getGroupCallStars(int $id): GroupCallStars
    {
        return $this->getGroupCallController($id)->getStars();
    }

    /**
     * The peer we send in-call messages of a live story as by default.
     *
     * @internal
     */
    public function saveDefaultGroupCallSendAs(int $id, string|int $peer): void
    {
        $this->getGroupCallController($id)->saveDefaultSendAs($peer);
    }

    /**
     * A participant of a group call by their id, username or peer, or null if they are not in it.
     *
     * @internal
     */
    public function getGroupCallParticipant(int $id, string|int $participant): ?AbstractGroupCallParticipant
    {
        return $this->getGroupCallController($id)->getParticipant($participant);
    }

    /**
     * Whether a group call is in stream mode (its media is received by downloading chunks).
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isGroupCallStreamMode(int $id): bool
    {
        return ($this->groupCalls[$id] ?? null)?->isStreamMode() ?? false;
    }

    /**
     * Whether a group call is an RTMP livestream.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isGroupCallRtmpMode(int $id): bool
    {
        return ($this->groupCalls[$id] ?? null)?->isRtmpMode() ?? false;
    }

    /**
     * The stream channels of an RTMP livestream (phone.getGroupCallStreamChannels): their ids, and the
     * timestamp of the live edge.
     *
     * @return list<array{channel: int, scale: int, last_timestamp_ms: int}>
     *
     * @internal
     */
    public function getGroupCallStreamChannels(int $id): array
    {
        $result = $this->methodCallAsyncRead('phone.getGroupCallStreamChannels', ['call' => $this->getGroupCallController($id)->getInputCall()]);
        $channels = [];
        foreach ($result['channels'] as $channel) {
            $channels[] = ['channel' => (int) $channel['channel'], 'scale' => (int) $channel['scale'], 'last_timestamp_ms' => (int) $channel['last_timestamp_ms']];
        }
        return $channels;
    }

    /**
     * Download one media chunk of a group call in stream mode (upload.getFile with an
     * inputGroupCallStream location) from its stream DC, or null on a CDN redirect.
     *
     * @internal
     */
    public function downloadGroupCallStreamChunk(int $id, int $timeMs, int $scale = 0, ?int $videoChannel = null, ?int $videoQuality = null): ?string
    {
        $controller = $this->getGroupCallController($id);
        $location = ['_' => 'inputGroupCallStream', 'call' => $controller->getInputCall(), 'time_ms' => $timeMs, 'scale' => $scale];
        if ($videoChannel !== null) {
            $location['video_channel'] = $videoChannel;
            $location['video_quality'] = $videoQuality ?? 0;
        }
        $dc = $controller->public->streamDcId ?? $this->loginState->getState()->authorizedDc;
        if ($this->isTestMode() && $dc < 10_000) {
            $dc += 10_000;
        }
        if ($this->datacenter->has(-$dc)) {
            $dc = -$dc;
        }
        $result = $this->methodCallAsyncRead('upload.getFile', [
            'location' => $location,
            'offset' => 0,
            'limit' => 1024 * 1024,
            'floodWaitLimit' => 0,
            'specialMethodType' => SpecialMethodType::FILE_RELATED,
        ], $dc);
        if ($result['_'] !== 'upload.file') {
            return null;
        }
        return (string) $result['bytes'];
    }

    /**
     * Resolve the controller of a group call we are tracking.
     *
     * @internal
     */
    private function getGroupCallController(int $id): GroupCallController
    {
        return $this->groupCalls[$id] ?? throw new AssertionError('Unknown group call!');
    }

    /**
     * The peers we may join the video chats and livestreams of a group or channel as: ourselves, the
     * channels we own, and (for anonymous admins) the group itself. Bot API IDs.
     *
     * See [joining a group call on behalf of owned channels »](https://core.telegram.org/api/group-calls#joining-a-group-call-on-behalf-of-owned-channels).
     *
     * @return list<int>
     */
    public function getGroupCallJoinAs(string|int $peer): array
    {
        $result = $this->methodCallAsyncRead('phone.getGroupCallJoinAs', ['peer' => $peer]);
        $ids = [];
        foreach ($result['peers'] as $joinAs) {
            $id = $this->getIdInternal($joinAs);
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * Save the peer we join the video chats and livestreams of a group or channel as by default.
     *
     * @param string|int $peer   The group or channel.
     * @param string|int $joinAs The peer to join as (one of {@see self::getGroupCallJoinAs()}).
     */
    public function saveDefaultGroupCallJoinAs(string|int $peer, string|int $joinAs): void
    {
        $this->methodCallAsyncRead('phone.saveDefaultGroupCallJoinAs', ['peer' => $peer, 'join_as' => $joinAs]);
    }

    /**
     * Get the RTMP URL and stream key to publish an [RTMP livestream »](https://core.telegram.org/api/group-calls#creating-and-publishing-an-rtmp-livestream)
     * to, in a group or channel (create the call with `rtmpStream` afterwards, see {@see self::createGroupCall()}),
     * or as a live story (see {@see self::startLive()}).
     *
     * @param string|int $peer      The group or channel (or, for a live story, the user, group or channel it is posted as).
     * @param bool  $revoke    Whether to generate a new stream key, invalidating the previous one.
     * @param bool  $liveStory Whether the key is for a live story rather than a video chat/livestream.
     *
     * @return array{url: string, key: string}
     */
    public function getGroupCallStreamRtmpUrl(string|int $peer, bool $revoke = false, bool $liveStory = false): array
    {
        $result = $this->methodCallAsyncRead('phone.getGroupCallStreamRtmpUrl', ['peer' => $peer, 'revoke' => $revoke, 'live_story' => $liveStory]);
        return ['url' => (string) $result['url'], 'key' => (string) $result['key']];
    }

    /**
     * Start a [live story »](https://core.telegram.org/api/group-calls#live-stories): a livestream posted
     * as a story, of which we are the single publisher (everyone else joins as a listener).
     *
     * @param string|int                $peer                  Who to post the live story as: ourselves, or a group or channel we administer.
     * @param string|null               $caption               Caption of the story.
     * @param ParseMode|null            $parseMode             Whether to parse HTML or Markdown markup in the caption.
     * @param list<array<string, mixed>> $privacyRules          Who may see the story, as [InputPrivacyRule](https://core.telegram.org/type/InputPrivacyRule)s; everyone by default.
     * @param bool                      $pinned                Whether to pin the story to the profile.
     * @param bool                      $noForwards            Whether to forbid forwarding and screenshots.
     * @param bool                      $rtmpStream            Whether the media is published by an external RTMP application (see {@see self::getGroupCallStreamRtmpUrl()}) rather than by us.
     * @param bool|null                 $messagesEnabled       Whether viewers may comment with in-call messages.
     * @param int|null                  $sendPaidMessagesStars The minimum Telegram Stars donation required to comment, if any.
     */
    public function startLive(string|int $peer, ?string $caption = null, ?ParseMode $parseMode = null, array $privacyRules = [['_' => 'inputPrivacyValueAllowAll']], bool $pinned = false, bool $noForwards = false, bool $rtmpStream = false, ?bool $messagesEnabled = null, ?int $sendPaidMessagesStars = null): LiveStory
    {
        $params = [
            'peer' => $peer,
            'privacy_rules' => $privacyRules,
            'random_id' => random_int(PHP_INT_MIN, PHP_INT_MAX),
            'pinned' => $pinned,
            'noforwards' => $noForwards,
            'rtmp_stream' => $rtmpStream,
        ];
        if ($caption !== null) {
            $entities = [];
            if ($parseMode === ParseMode::MARKDOWN) {
                $parsed = TextEntities::fromMarkdown($caption);
                [$caption, $entities] = [$parsed->message, $parsed->entities];
            } elseif ($parseMode === ParseMode::HTML) {
                $parsed = TextEntities::fromHtml($caption);
                [$caption, $entities] = [$parsed->message, $parsed->entities];
            }
            $params['caption'] = $caption;
            $params['entities'] = $entities;
        }
        if ($messagesEnabled !== null) {
            $params['messages_enabled'] = $messagesEnabled;
        }
        if ($sendPaidMessagesStars !== null) {
            $params['send_paid_messages_stars'] = $sendPaidMessagesStars;
        }
        $updates = $this->methodCallAsyncRead('stories.startLive', $params);
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCall' && $update['call']['_'] === 'groupCall') {
                $inputCall = ['_' => 'inputGroupCall', 'id' => $update['call']['id'], 'access_hash' => $update['call']['access_hash']];
                $controller = $this->getGroupCallByInput($inputCall, $this->getIdInternal($peer), liveStory: true);
                if ($controller !== null && $controller->public instanceof LiveStory) {
                    return $controller->public;
                }
            }
        }
        throw new AssertionError('The server did not return the started live story!');
    }

    /**
     * Record one participant of a group call (or, in stream mode, its mixed stream) into a single file
     * (or stream) with a fixed set of tracks, see {@see \danog\MadelineProto\EventHandler\Calls\AbstractGroupCall::setOutput()}.
     *
     * @param ?StreamMask $streams The streams to record, as a bitmask of {@see CallStream} flags, or null for every available one.
     *
     * @return StreamMask The streams the participant currently sends, as a bitmask of {@see CallStream} flags.
     */
    public function groupCallSetOutput(int $id, LocalFile|WritableStream $file, string|int|null $participant = null, ?RecordingFormat $format = null, ?int $streams = null): int
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->setOutput($file, $participant, $format, $streams);
    }

    /**
     * Record group call media into a directory: every transmitting participant (or only the given
     * one) as its own numbered series of files, see
     * {@see \danog\MadelineProto\EventHandler\Calls\AbstractGroupCall::setOutputFolder()}.
     */
    public function groupCallSetOutputFolder(int $id, LocalDirectory $dir, string|int|null $participant = null, ?RecordingFormat $format = null): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->setOutputFolder($dir, $participant, $format);
    }

    /**
     * Remove participants from a group call, by kicking them from its group or channel.
     *
     * @internal
     */
    public function removeGroupCallParticipants(int $id, string|int ...$participants): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->removeParticipant(...$participants);
    }

    /**
     * Whether a screen-share is currently being transmitted in a group call.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function isGroupCallSharingScreen(int $id): bool
    {
        return ($this->groupCalls[$id] ?? null)?->isSharingScreen() ?? false;
    }

    /**
     * Start sharing a screen in a group call.
     *
     * @internal
     */
    public function enableGroupCallPresentation(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->enablePresentation();
    }

    /**
     * Stop sharing the screen in a group call.
     *
     * @internal
     */
    public function disableGroupCallPresentation(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->disablePresentation();
    }

    /**
     * Play a file in a group call.
     */
    public function groupCallPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->play($file, $dest);
    }

    /**
     * Play a file in a group call, blocking until the file has finished playing if a stream is provided.
     *
     * @internal
     */
    public function groupCallPlayBlocking(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->playBlocking($file, $dest);
    }

    /**
     * Files to play on hold in a group call.
     */
    public function groupCallPlayOnHold(int $id, MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->playOnHold($dest, ...$files);
    }

    /**
     * Skip to the next file in the playlist of a group call.
     */
    public function groupCallSkipPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->skip($dest);
    }

    /**
     * Stop playing all files in a group call, clearing the main and the hold playlist.
     */
    public function groupCallStopPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->stop($dest);
    }

    /**
     * Pause playback of the current audio file in a group call.
     *
     * @psalm-external-mutation-free
     */
    public function groupCallPausePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->pause($dest);
    }

    /**
     * Resume playback of the current audio file in a group call.
     *
     * @psalm-external-mutation-free
     */
    public function groupCallResumePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->resume($dest);
    }

    /**
     * Whether the currently playing audio file of a group call is paused.
     *
     * @psalm-mutation-free
     */
    public function isGroupCallPlayPaused(int $id, MediaDestination $dest = MediaDestination::Camera): bool
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->isPaused($dest);
    }

    /**
     * Get the file that is currently being played in a group call.
     *
     * @psalm-mutation-free
     */
    public function groupCallGetCurrent(int $id, MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->getCurrent($dest);
    }

    /**
     * Handle an incoming group call update.
     *
     * @internal
     */
    public function handleGroupCallUpdate(array $update): void
    {
        switch ($update['_']) {
            case 'updateGroupCall':
                $call = $update['call'];
                $id = $call['id'];
                if (isset($this->conferenceCalls[$id])) {
                    EventLoop::queue($this->conferenceCalls[$id]->onGroupCallUpdate(...), $call);
                    return;
                }
                if (!isset($this->groupCalls[$id])) {
                    if ($call['_'] !== 'groupCall') {
                        // A call we never tracked was just discarded: nothing to do.
                        return;
                    }
                    // Start tracking any group call we become aware of. The controller is built from
                    // the full groupCall the update carries, so there is nothing to apply over it.
                    $this->groupCalls[$id] = new GroupCallController(
                        $this,
                        $call,
                        isset($update['peer']) ? $this->getIdInternal($update['peer']) : null,
                        (bool) ($update['live_story'] ?? false),
                    );
                    return;
                }
                // Deferred: applying an update may refetch the call or renegotiate the WebRTC
                // session, neither of which should run inline in the update loop. Queueing keeps
                // them ordered.
                EventLoop::queue($this->groupCalls[$id]->onGroupCallUpdate(...), $call);
                break;
            case 'updateGroupCallParticipants':
                $id = $update['call']['id'];
                if (isset($this->conferenceCalls[$id])) {
                    EventLoop::queue(
                        $this->conferenceCalls[$id]->onParticipantsUpdate(...),
                        $update['participants'],
                        $update['version']
                    );
                    return;
                }
                if (!isset($this->groupCalls[$id])) {
                    $this->logger->logger("Ignoring participants update for unknown group call $id");
                    return;
                }
                EventLoop::queue(
                    $this->groupCalls[$id]->onParticipantsUpdate(...),
                    $update['participants'],
                    $update['version']
                );
                break;
            case 'updateGroupCallChainBlocks':
                $id = $update['call']['id'];
                if (!isset($this->conferenceCalls[$id])) {
                    return;
                }
                EventLoop::queue(
                    $this->conferenceCalls[$id]->onChainBlocks(...),
                    $update['sub_chain_id'],
                    array_map('strval', $update['blocks']),
                    $update['next_offset']
                );
                break;
            case 'updateGroupCallEncryptedMessage':
                $id = $update['call']['id'];
                if (!isset($this->conferenceCalls[$id])) {
                    return;
                }
                $fromId = $this->getIdInternal($update['from_id']) ?? 0;
                $encrypted = (string) $update['encrypted_message'];
                EventLoop::queue(function () use ($id, $fromId, $encrypted, $update): void {
                    $message = $this->conferenceCalls[$id]?->onEncryptedMessage($fromId, $encrypted);
                    if ($message === null) {
                        return;
                    }
                    // Surface it exactly like a plain in-call message, flagged as end-to-end encrypted.
                    $this->saveUpdate([
                        '_' => 'updateGroupCallMessage',
                        'call' => $update['call'],
                        'encrypted' => true,
                        'message' => [
                            '_' => 'groupCallMessage',
                            'id' => 0,
                            'from_id' => $update['from_id'],
                            'date' => time(),
                            'message' => ['_' => 'textWithEntities', 'text' => $message['text'], 'entities' => $message['entities']],
                        ],
                    ]);
                });
                break;
        }
    }
}
