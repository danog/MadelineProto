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
use danog\DialogId\DialogId;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCall;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCallParticipant;
use danog\MadelineProto\EventHandler\Calls\ConferenceCall as ConferenceCallUpdate;
use danog\MadelineProto\EventHandler\Calls\ConferenceCallParticipant;
use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\EventHandler\Calls\GroupCallStars;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\EventHandler\Calls\LiveStory;
use danog\MadelineProto\EventHandler\Calls\RtmpStream;
use danog\MadelineProto\GroupCall\Rtmp\RtmpAddressException;
use danog\MadelineProto\GroupCall\Rtmp\RtmpClient;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\RtmpServers;
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
    /** How many untracked group calls to remember, see {@see self::$seenGroupCalls}. */
    private const SEEN_GROUP_CALLS_MAX = 1000;

    /**
     * The group calls this session joined, created, or looked up (never the ones it only gets updates
     * about: those may be joined by another session of the same account).
     *
     * @var array<int, GroupCallController>
     */
    private array $groupCalls = [];
    /**
     * What we publish to RTMP servers, see {@see self::startRtmpStream()}.
     *
     * @var array<int, RtmpStreamController>
     */
    private array $rtmpStreams = [];
    /**
     * The group calls we got updates about but do not track: ID => [access hash, peer ID, live story].
     *
     * @var array<int, array{int, ?int, bool}>
     */
    private array $seenGroupCalls = [];
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
        // Start tracking the new call from the updateGroupCall in the response (handleGroupCallUpdate
        // never starts tracking a call by itself, see there).
        $inputCall = null;
        $peerId = null;
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCall' && $update['call']['_'] === 'groupCall') {
                $inputCall = [
                    '_' => 'inputGroupCall',
                    'id' => $update['call']['id'],
                    'access_hash' => $update['call']['access_hash'],
                ];
                // Not necessarily $peer: starting an RTMP livestream migrates a basic group to a supergroup.
                $peerId = isset($update['peer']) ? $this->getIdInternal($update['peer']) : null;
                break;
            }
        }
        $controller = $inputCall !== null
            ? $this->getGroupCallByInput($inputCall, $peerId ?? $this->migratedPeer($peer))
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
     * Uses the `sodium` and `openssl` PHP extensions if available, for speed.
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
     * Uses the `sodium` and `openssl` PHP extensions if available, for speed.
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
        return ($this->conferenceCalls[$id] ?? null)?->isJoined() ?? false;
    }

    /**
     * Leave a conference call, without ending it for the other participants.
     *
     * @internal
     */
    public function leaveConferenceCall(int $id): void
    {
        // Already left (and forgotten), if unknown.
        ($this->conferenceCalls[$id] ?? null)?->leave();
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
        $call = $this->currentGroupCall($peer)?->public;
        return $call instanceof GroupCall ? $call : null;
    }

    /**
     * The group call currently active in a group or channel.
     *
     * The call of the full info of the chat is cached, and it may have ended since: then the full
     * info is fetched again. A basic group migrated to a supergroup (e.g. by starting an RTMP
     * livestream) is followed to the supergroup.
     */
    private function currentGroupCall(string|int $peer): ?GroupCallController
    {
        $peer = $this->migratedPeer($peer);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $inputCall = $this->getFullInfo($peer)['full']['call'] ?? null;
            if ($inputCall === null) {
                return null;
            }
            try {
                $controller = $this->getGroupCallByInput($inputCall, $peer);
                if ($controller !== null && !$controller->public->discarded) {
                    return $controller;
                }
            } catch (RPCErrorException $e) {
                // GROUPCALL_FORBIDDEN: the call has already ended.
                if ($e->rpc !== 'GROUPCALL_FORBIDDEN' && $e->rpc !== 'GROUPCALL_INVALID') {
                    throw $e;
                }
            }
            $this->refreshFullPeerCache($peer);
        }
        return null;
    }

    /**
     * The supergroup a basic group was migrated to, or the peer itself.
     */
    private function migratedPeer(string|int $peer): int
    {
        $id = $this->getInfo($peer, \danog\MadelineProto\API::INFO_TYPE_ID);
        if (DialogId::isChat($id)) {
            $migratedTo = $this->getInfo($id)['Chat']['migrated_to']['channel_id'] ?? null;
            if ($migratedTo !== null) {
                return (int) $migratedTo;
            }
        }
        return $id;
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
        $controller = $this->currentGroupCall($peer);
        if ($controller === null) {
            throw new AssertionError('There is no active group call in this chat!');
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
        $this->getGroupCallController($id)->join($muted, $joinAs, $inviteHash);
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
        $this->getGroupCallController($id)->leave();
    }

    /**
     * End a group call for all participants.
     */
    public function discardGroupCall(int $id): void
    {
        $this->getGroupCallController($id)->discard();
    }

    /**
     * Get the state of a group call.
     *
     * @psalm-mutation-free
     */
    public function getGroupCallState(int $id): GroupCallState
    {
        if (isset($this->groupCalls[$id])) {
            return $this->groupCalls[$id]->getCallState();
        }
        return isset($this->seenGroupCalls[$id]) ? GroupCallState::NOT_JOINED : GroupCallState::LEFT;
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
     * Bot API ID of the peer a group call we are tracking belongs to, if known.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getGroupCallPeerId(int $id): ?int
    {
        return ($this->groupCalls[$id] ?? null)?->public->peerId;
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
        return $this->getGroupCallController($id)->getParticipants();
    }

    /**
     * Mute or unmute our own audio stream in a group call.
     */
    public function setGroupCallMuted(int $id, bool $muted = true): void
    {
        $this->getGroupCallController($id)->setMuted($muted);
    }

    /**
     * Whether our own audio stream is muted in a group call.
     *
     * @psalm-mutation-free
     */
    public function isGroupCallMuted(int $id): bool
    {
        return $this->getGroupCallController($id)->isMuted();
    }

    /**
     * Change the title of a group call.
     */
    public function setGroupCallTitle(int $id, string $title): void
    {
        $this->getGroupCallController($id)->setTitle($title);
    }

    /**
     * Invite users to a group call.
     */
    public function inviteToGroupCall(int $id, string|int ...$users): void
    {
        $this->getGroupCallController($id)->invite(...$users);
    }

    /**
     * Export an invite link for a group call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; admins only.
     */
    public function exportGroupCallInvite(int $id, bool $canSelfUnmute = false): string
    {
        return $this->getGroupCallController($id)->exportInvite($canSelfUnmute);
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
        $controller = $this->getGroupCallController($id);
        // A flood wait is thrown, not waited for here: the stream receiver polls this while the livestream has no
        // media yet, and waits as long as the server asks itself.
        $result = $this->methodCallAsyncRead('phone.getGroupCallStreamChannels', ['call' => $controller->getInputCall(), 'floodWaitLimit' => 0], $this->groupCallStreamDc($controller));
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
     * A partial chunk (storage.filePartial) is cut short: still being written (at the live edge), or for good (a gap in
     * what the publisher sent, which official clients play as it is).
     *
     * @return ?array{bytes: string, partial: bool}
     *
     * @internal
     */
    public function downloadGroupCallStreamChunk(int $id, int $timeMs, int $scale = 0, ?int $videoChannel = null, ?int $videoQuality = null): ?array
    {
        $controller = $this->getGroupCallController($id);
        $location = ['_' => 'inputGroupCallStream', 'call' => $controller->getInputCall(), 'time_ms' => $timeMs, 'scale' => $scale];
        if ($videoChannel !== null) {
            $location['video_channel'] = $videoChannel;
            $location['video_quality'] = $videoQuality ?? 0;
        }
        $dc = $this->groupCallStreamDc($controller);
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
        return ['bytes' => (string) $result['bytes'], 'partial' => ($result['type']['_'] ?? null) === 'storage.filePartial'];
    }

    /**
     * The DC serving the chunks of a group call in stream mode (its stream_dc_id).
     */
    private function groupCallStreamDc(GroupCallController $controller): int
    {
        // The bare DC ID, as tdesktop takes it (BareDcId), whether the server sent it shifted or not,
        // and whether it is the authorized DC, already in our numbering of the test DCs (10001-10003).
        $dc = ($controller->public->streamDcId ?? $this->loginState->getState()->authorizedDc) % 10_000;
        if ($this->isTestMode()) {
            $dc += 10_000;
        }
        if ($this->datacenter->has(-$dc)) {
            // Its media-only connection: chunks are downloaded like files.
            $dc = -$dc;
        }
        return $dc;
    }

    /**
     * Resolve the controller of a group call we are tracking.
     *
     * @internal
     */
    private function getGroupCallController(int $id): GroupCallController
    {
        if (isset($this->groupCalls[$id])) {
            return $this->groupCalls[$id];
        }
        // A call we only saw in an update (or forgot on restart, see dropUnjoinedGroupCalls()):
        // start tracking it now that it is used, from its access hash.
        $seen = $this->seenGroupCalls[$id] ?? throw new AssertionError('Unknown group call!');
        $controller = $this->getGroupCallByInput(
            ['_' => 'inputGroupCall', 'id' => $id, 'access_hash' => $seen[0]],
            $seen[1],
            $seen[2],
        );
        if ($controller === null) {
            unset($this->seenGroupCalls[$id]);
            throw new AssertionError('The group call has ended!');
        }
        return $controller;
    }

    /**
     * The live story of a story (its messageMediaVideoStream media): the tracked one, or one built from the story alone,
     * without any request (a story update must not fetch its call), whose call is fetched and tracked once used.
     *
     * @internal
     */
    public function wrapLiveStoryMedia(array $media, ?int $peerId): LiveStory
    {
        $input = $media['call'];
        if ($input['_'] !== 'inputGroupCall') {
            $controller = $this->getGroupCallByInput($input, $peerId, liveStory: true);
            \assert($controller !== null && $controller->public instanceof LiveStory, 'The live story has ended');
            return $controller->public;
        }
        $tracked = ($this->groupCalls[$input['id']] ?? null)?->public;
        if ($tracked instanceof LiveStory) {
            return $tracked;
        }
        $this->rememberGroupCall($input['id'], $input['access_hash'], $peerId, true);
        // What a story says about its call: the rest are the defaults until it's fetched.
        return new LiveStory($this, [
            '_' => 'groupCall',
            'id' => $input['id'],
            'access_hash' => $input['access_hash'],
            'participants_count' => 0,
            'join_muted' => false,
            'can_change_join_muted' => false,
            'join_date_asc' => false,
            'can_start_video' => false,
            'rtmp_stream' => $media['rtmp_stream'] ?? false,
            'listeners_hidden' => false,
            'creator' => false,
            'messages_enabled' => false,
            'can_change_messages_enabled' => false,
            'unmuted_video_limit' => 0,
        ], $peerId);
    }

    /**
     * Remember the access hash of a group call we saw but do not track, see {@see self::getGroupCallController()}.
     */
    private function rememberGroupCall(int $id, int $accessHash, ?int $peerId, bool $liveStory): void
    {
        unset($this->seenGroupCalls[$id]);
        $this->seenGroupCalls[$id] = [$accessHash, $peerId, $liveStory];
        if (\count($this->seenGroupCalls) > self::SEEN_GROUP_CALLS_MAX) {
            // Calls whose end we missed (it is only notified to running sessions): forget the oldest.
            unset($this->seenGroupCalls[array_key_first($this->seenGroupCalls)]);
        }
    }

    /**
     * Stop tracking the group calls we are not in (nor publishing to), when resuming a session.
     *
     * A session only keeps (and resumes) the group calls it joined: trackers of calls it merely
     * looked up, or that older versions created from every updateGroupCall (even for calls joined by
     * another session of the same account), are dropped without any request. They are tracked again
     * on first use, see {@see self::getGroupCallController()}.
     *
     * @internal
     */
    public function dropUnjoinedGroupCalls(): void
    {
        foreach ($this->groupCalls as $id => $groupCall) {
            if ($groupCall->forgetIfUnjoined()) {
                $public = $groupCall->public;
                if (!$public->discarded) {
                    $this->rememberGroupCall($id, $public->accessHash, $public->peerId, $public instanceof LiveStory);
                }
            }
        }
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
            // The server returns the story (whose media is the call), maybe without an updateGroupCall.
            $inputCall = match (true) {
                $update['_'] === 'updateGroupCall' && $update['call']['_'] === 'groupCall' => ['_' => 'inputGroupCall', 'id' => $update['call']['id'], 'access_hash' => $update['call']['access_hash']],
                $update['_'] === 'updateStory' && ($update['story']['media']['_'] ?? null) === 'messageMediaVideoStream' => $update['story']['media']['call'],
                default => null,
            };
            if ($inputCall === null) {
                continue;
            }
            // getId(), not getIdInternal(), which doesn't resolve 'me' (the live story then had no peer).
            $controller = $this->getGroupCallByInput($inputCall, $this->getId($peer), liveStory: true);
            if ($controller !== null && $controller->public instanceof LiveStory) {
                return $controller->public;
            }
        }
        throw new AssertionError('The server did not return the started live story!');
    }

    /**
     * Publish what is played to an RTMP server, as an RTMP encoder would (OBS, ffmpeg...).
     *
     * To the RTMP livestream or live story of any group, channel or user that gave us its URL and stream key (shown
     * by the Telegram apps when starting one "with another app"), to YouTube, Twitch, or any other RTMP(S) server.
     *
     * Only H.264 video and AAC audio can be published, in MP4 or Matroska (WebM) files. The stream keeps
     * publishing (reconnecting if needed, and across restarts) until {@see RtmpStream::close()}d; publishing
     * again to the same URL and key returns the same stream.
     *
     * @param string  $url        The URL of the RTMP application, e.g. `rtmps://dc4-1.rtmp.t.me/s/`, or, without `$key`, the URL followed by the stream key, e.g. `rtmps://dc4-1.rtmp.t.me/s/123456:AbCdEf`.
     * @param ?string $key        The stream key.
     * @param RtmpServers $servers Which servers it may connect to, checked at each connection: when whoever chooses the URL must not make us connect anywhere (into our network, say). Otherwise, the stream is closed.
     *
     * @throws RtmpAddressException If the server isn't one of $servers.
     */
    public function startRtmpStream(string $url, #[\SensitiveParameter] ?string $key = null, RtmpServers $servers = RtmpServers::Any): RtmpStream
    {
        if ($key === null) {
            [$url, $key] = self::splitRtmpUrl($url);
        }
        $parts = parse_url($url);
        if (!isset($parts['scheme'], $parts['host']) || !\in_array($parts['scheme'], ['rtmp', 'rtmps'], true)) {
            throw new AssertionError("Not an RTMP URL: $url");
        }
        if ($key === '') {
            throw new AssertionError("No stream key given for $url");
        }
        // At once, to tell the caller; checked again at each connection, see RtmpClient::publish().
        RtmpClient::checkServer($parts['host'], $servers);
        $this->forgetClosedRtmpStreams();
        foreach ($this->rtmpStreams as $stream) {
            if ($stream->publishesTo($url, $key)) {
                if (!$stream->getServers()->isWithin($servers)) {
                    // It may connect to other servers: not to be handed to whoever may only stream to those.
                    throw new RtmpAddressException("Already publishing to $url, allowed to connect to other servers");
                }
                return $stream->public;
            }
        }
        do {
            $id = random_int(1, PHP_INT_MAX);
        } while (isset($this->rtmpStreams[$id]));
        $stream = new RtmpStreamController($this, $id, $url, $key, $servers);
        $this->rtmpStreams[$id] = $stream;
        return $stream->public;
    }

    /**
     * What we publish to an RTMP server with a URL and stream key, if anything.
     *
     * See {@see self::startRtmpStream()}.
     *
     * @param string  $url The URL of the RTMP application, or, without `$key`, the URL followed by the stream key.
     * @param ?string $key The stream key.
     */
    public function getRtmpStream(string $url, #[\SensitiveParameter] ?string $key = null): ?RtmpStream
    {
        if ($key === null) {
            [$url, $key] = self::splitRtmpUrl($url);
        }
        $this->forgetClosedRtmpStreams();
        foreach ($this->rtmpStreams as $stream) {
            if ($stream->publishesTo($url, $key)) {
                return $stream->public;
            }
        }
        return null;
    }

    /**
     * Forget the RTMP streams that closed by themselves (their server isn't one of those allowed).
     */
    private function forgetClosedRtmpStreams(): void
    {
        foreach ($this->rtmpStreams as $id => $stream) {
            if ($stream->isClosed()) {
                unset($this->rtmpStreams[$id]);
            }
        }
    }

    /**
     * Split an RTMP URL followed by its stream key (the last segment of its path) into both.
     *
     * @return array{string, string}
     */
    private static function splitRtmpUrl(#[\SensitiveParameter] string $url): array
    {
        // The key follows the last slash, after the application: rtmps://host/app/key
        $scheme = strpos($url, '://');
        $hostEnd = $scheme === false ? false : strpos($url, '/', $scheme + 3);
        $slash = strrpos($url, '/');
        if ($hostEnd === false || $slash === $hostEnd || $slash === \strlen($url) - 1) {
            throw new AssertionError('Not an RTMP URL followed by a stream key, e.g. rtmps://dc4-1.rtmp.t.me/s/123456:AbCdEf');
        }
        return [substr($url, 0, $slash + 1), substr($url, $slash + 1)];
    }

    /**
     * What we publish to RTMP servers.
     *
     * See {@see self::startRtmpStream()}.
     *
     * @return array<int, RtmpStream>
     */
    public function getRtmpStreams(): array
    {
        $this->forgetClosedRtmpStreams();
        return array_map(static fn (RtmpStreamController $c): RtmpStream => $c->public, $this->rtmpStreams);
    }

    private function getRtmpStreamController(int $id): RtmpStreamController
    {
        return $this->rtmpStreams[$id] ?? throw new AssertionError("RTMP stream $id was closed, or never started");
    }

    /**
     * Play a file to an RTMP stream.
     *
     * See {@see RtmpStream::play()}.
     */
    public function rtmpStreamPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getRtmpStreamController($id)->play($file, $dest);
    }

    /**
     * Play a file to an RTMP stream, blocking until it has finished playing if a stream was passed.
     *
     * See {@see RtmpStream::playBlocking()}.
     */
    public function rtmpStreamPlayBlocking(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getRtmpStreamController($id)->playBlocking($file, $dest);
    }

    /**
     * Set the files to play on loop to an RTMP stream while its playlist is empty.
     *
     * See {@see RtmpStream::playOnHold()}.
     */
    public function rtmpStreamPlayOnHold(int $id, MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->getRtmpStreamController($id)->playOnHold($dest, ...$files);
    }

    /**
     * Skip to the next file played to an RTMP stream.
     *
     * See {@see RtmpStream::skip()}.
     */
    public function rtmpStreamSkipPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getRtmpStreamController($id)->skip($dest);
    }

    /**
     * Stop playing all files to an RTMP stream, clearing its playlists.
     *
     * See {@see RtmpStream::stop()}.
     */
    public function rtmpStreamStopPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getRtmpStreamController($id)->stop($dest);
    }

    /**
     * Pause the file played to an RTMP stream.
     *
     * See {@see RtmpStream::pause()}.
     */
    public function rtmpStreamPausePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getRtmpStreamController($id)->pause($dest);
    }

    /**
     * Resume the file played to an RTMP stream.
     *
     * See {@see RtmpStream::resume()}.
     */
    public function rtmpStreamResumePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getRtmpStreamController($id)->resume($dest);
    }

    /**
     * Whether the file played to an RTMP stream is paused.
     *
     * See {@see RtmpStream::isPaused()}.
     */
    public function isRtmpStreamPlayPaused(int $id, MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->getRtmpStreamController($id)->isPaused($dest);
    }

    /**
     * The file played to an RTMP stream.
     *
     * See {@see RtmpStream::getCurrent()}.
     */
    public function rtmpStreamGetCurrent(int $id, MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getRtmpStreamController($id)->getCurrent($dest);
    }

    /**
     * Whether we are connected to the server of an RTMP stream right now.
     *
     * See {@see RtmpStream::isConnected()}.
     */
    public function isRtmpStreamConnected(int $id): bool
    {
        return $this->getRtmpStreamController($id)->isConnected();
    }

    /**
     * Stop publishing to an RTMP stream for good.
     *
     * See {@see RtmpStream::close()}.
     */
    public function closeRtmpStream(int $id): void
    {
        $stream = $this->rtmpStreams[$id] ?? null;
        unset($this->rtmpStreams[$id]);
        $stream?->close();
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
        return $this->getGroupCallController($id)->setOutput($file, $participant, $format, $streams);
    }

    /**
     * Record group call media into a directory: every transmitting participant (or only the given
     * one) as its own numbered series of files, see
     * {@see \danog\MadelineProto\EventHandler\Calls\AbstractGroupCall::setOutputFolder()}.
     */
    public function groupCallSetOutputFolder(int $id, LocalDirectory $dir, string|int|null $participant = null, ?RecordingFormat $format = null): void
    {
        $this->getGroupCallController($id)->setOutputFolder($dir, $participant, $format);
    }

    /**
     * Remove participants from a group call, by kicking them from its group or channel.
     *
     * @internal
     */
    public function removeGroupCallParticipants(int $id, string|int ...$participants): void
    {
        $this->getGroupCallController($id)->removeParticipant(...$participants);
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
        $this->getGroupCallController($id)->enablePresentation();
    }

    /**
     * Stop sharing the screen in a group call.
     *
     * @internal
     */
    public function disableGroupCallPresentation(int $id): void
    {
        $this->getGroupCallController($id)->disablePresentation();
    }

    /**
     * Play a file in a group call.
     */
    public function groupCallPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getGroupCallController($id)->play($file, $dest);
    }

    /**
     * Play a file in a group call, blocking until the file has finished playing if a stream is provided.
     *
     * @internal
     */
    public function groupCallPlayBlocking(int $id, LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getGroupCallController($id)->playBlocking($file, $dest);
    }

    /**
     * Files to play on hold in a group call.
     */
    public function groupCallPlayOnHold(int $id, MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->getGroupCallController($id)->playOnHold($dest, ...$files);
    }

    /**
     * Skip to the next file in the playlist of a group call.
     */
    public function groupCallSkipPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getGroupCallController($id)->skip($dest);
    }

    /**
     * Stop playing all files in a group call, clearing the main and the hold playlist.
     */
    public function groupCallStopPlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getGroupCallController($id)->stop($dest);
    }

    /**
     * Pause playback of the current audio file in a group call.
     *
     * @psalm-external-mutation-free
     */
    public function groupCallPausePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getGroupCallController($id)->pause($dest);
    }

    /**
     * Resume playback of the current audio file in a group call.
     *
     * @psalm-external-mutation-free
     */
    public function groupCallResumePlay(int $id, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->getGroupCallController($id)->resume($dest);
    }

    /**
     * Whether the currently playing audio file of a group call is paused.
     *
     * @psalm-mutation-free
     */
    public function isGroupCallPlayPaused(int $id, MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->getGroupCallController($id)->isPaused($dest);
    }

    /**
     * Get the file that is currently being played in a group call.
     *
     * @psalm-mutation-free
     */
    public function groupCallGetCurrent(int $id, MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null
    {
        return $this->getGroupCallController($id)->getCurrent($dest);
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
                    // Every member of a chat gets the updates of its group call, whether it is in it or
                    // not, and so does every session of an account in it: never start tracking (let
                    // alone resume) a call from its updates, or this session would own a controller
                    // of a call joined by another session. Only remember how to track it on first use.
                    $peerId = isset($update['peer']) ? $this->getIdInternal($update['peer']) : null;
                    if ($call['_'] !== 'groupCall' || ($call['conference'] ?? false)) {
                        if ($call['_'] === 'groupCallDiscarded') {
                            $peerId ??= $this->seenGroupCalls[$id][1] ?? null;
                            $this->logger->logger("Group call $id of chat ".($peerId ?? 'unknown').', which we are not in, ended', Logger::VERBOSE);
                        }
                        unset($this->seenGroupCalls[$id]);
                    } else {
                        if (!isset($this->seenGroupCalls[$id])) {
                            $title = isset($call['title']) ? " \"{$call['title']}\"" : '';
                            $this->logger->logger("Group call $id$title of chat ".($peerId ?? 'unknown').' is running, we are not in it', Logger::VERBOSE);
                        }
                        $this->rememberGroupCall(
                            $id,
                            $call['access_hash'],
                            $peerId,
                            (bool) ($update['live_story'] ?? false),
                        );
                    }
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
                    $this->logger->logger("Ignoring participants update for untracked group call $id of chat ".($this->seenGroupCalls[$id][1] ?? 'unknown'), Logger::VERBOSE);
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
                    $message = ($this->conferenceCalls[$id] ?? null)?->onEncryptedMessage($fromId, $encrypted);
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
