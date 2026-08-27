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
use Amp\DeferredFuture;
use AssertionError;
use danog\MadelineProto\GroupCall;
use danog\MadelineProto\GroupCallController;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Tools;

/**
 * Manages group calls (video chats, livestreams and live stories).
 *
 * See https://core.telegram.org/api/group-calls for more info.
 *
 * @internal
 */
trait Handler
{
    /** @var array<int, GroupCallController> */
    private array $groupCalls = [];

    /**
     * Create a group call (video chat or livestream) in the specified group or channel.
     *
     * Requires the `manage_call` admin right, see
     * [video chats/livestreams »](https://core.telegram.org/api/group-calls#video-chats-livestreams).
     *
     * @param mixed       $peer         The group or channel where the call should be created.
     * @param string|null $title        Custom title, defaults to the group/channel name.
     * @param int|null    $scheduleDate If set, creates a scheduled call for the specified UNIX timestamp.
     * @param bool        $rtmpStream   Whether the call's media is published by an external RTMP application.
     */
    public function createGroupCall(mixed $peer, ?string $title = null, ?int $scheduleDate = null, bool $rtmpStream = false): GroupCall
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
        $this->handleUpdates($updates);
        $call = $this->extractGroupCall($updates);
        if ($call === null) {
            throw new AssertionError('The server did not return the created group call!');
        }
        return $call->public;
    }

    /**
     * Get the group call (video chat or livestream) currently active in a group or channel.
     */
    public function getGroupCall(mixed $peer): ?GroupCall
    {
        $full = $this->getFullInfo($peer);
        $inputCall = $full['full']['call'] ?? null;
        if ($inputCall === null) {
            return null;
        }
        return $this->getGroupCallByInput($inputCall, $this->getIdInternal($peer))?->public;
    }

    /**
     * Get a group call from its
     * [conference deep link »](https://core.telegram.org/api/links#conference-links) slug.
     */
    public function getGroupCallBySlug(string $slug): ?GroupCall
    {
        return $this->getGroupCallByInput(['_' => 'inputGroupCallSlug', 'slug' => $slug])?->public;
    }

    /**
     * Join the group call currently active in a group or channel.
     *
     * @param mixed       $peer       The group or channel whose call should be joined.
     * @param bool        $muted      Whether to join muted.
     * @param mixed       $joinAs     Peer to join as, defaults to ourselves.
     * @param string|null $inviteHash Invite hash from a video chat invite link, if any.
     */
    public function joinGroupCall(mixed $peer, bool $muted = false, mixed $joinAs = null, ?string $inviteHash = null): GroupCall
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
        return $controller->public;
    }

    /**
     * Join a group call we already have a {@see GroupCall} object for.
     *
     * @internal
     */
    public function joinGroupCallById(int $id, bool $muted = false, mixed $joinAs = null, ?string $inviteHash = null): void
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
    public function getGroupCallByInput(array $inputCall, ?int $peerId = null): ?GroupCallController
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
        $controller = new GroupCallController($this, $call, $peerId);
        $this->groupCalls[$call['id']] = $controller;
        // We already have the full call and its first page of participants: no need to refetch.
        $controller->applyGroupCall($result);
        return $controller;
    }

    /**
     * Extract and cache the group call contained in an Updates constructor.
     *
     * @internal
     */
    public function extractGroupCall(array $updates, ?int $peerId = null): ?GroupCallController
    {
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] !== 'updateGroupCall' || $update['call']['_'] !== 'groupCall') {
                continue;
            }
            $call = $update['call'];
            if (isset($this->groupCalls[$call['id']])) {
                return $this->groupCalls[$call['id']];
            }
            $controller = new GroupCallController(
                $this,
                $call,
                $peerId ?? (isset($update['peer']) ? $this->getIdInternal($update['peer']) : null)
            );
            $this->groupCalls[$call['id']] = $controller;
            return $controller;
        }
        return null;
    }

    /** @internal */
    public function cleanupGroupCall(int $id): void
    {
        unset($this->groupCalls[$id]);
    }

    /**
     * Get all group calls we're currently tracking, indexed by their ID.
     *
     * @return array<int, GroupCall>
     */
    public function getAllGroupCalls(): array
    {
        return array_map(static fn (GroupCallController $c): GroupCall => $c->public, $this->groupCalls);
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
     */
    public function getGroupCallState(int $id): GroupCallState
    {
        return ($this->groupCalls[$id] ?? null)?->getCallState() ?? GroupCallState::LEFT;
    }

    /**
     * Get the participants of a group call, indexed by their bot API peer ID.
     *
     * @return array<int, Participant>
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
        $call = $this->groupCalls[$id] ?? null;
        if ($call === null) {
            return;
        }
        $this->handleUpdates($this->methodCallAsyncRead('phone.editGroupCallTitle', [
            'call' => $call->getInputCall(),
            'title' => $title,
        ]));
    }

    /**
     * Invite users to a group call.
     */
    public function inviteToGroupCall(int $id, mixed ...$users): void
    {
        $call = $this->groupCalls[$id] ?? null;
        if ($call === null) {
            return;
        }
        $this->handleUpdates($this->methodCallAsyncRead('phone.inviteToGroupCall', [
            'call' => $call->getInputCall(),
            'users' => $users,
        ]));
    }

    /**
     * Export an invite link for a group call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; admins only.
     */
    public function exportGroupCallInvite(int $id, bool $canSelfUnmute = false): string
    {
        $call = $this->groupCalls[$id] ?? null;
        if ($call === null) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->methodCallAsyncRead('phone.exportGroupCallInvite', [
            'call' => $call->getInputCall(),
            'can_self_unmute' => $canSelfUnmute,
        ])['link'];
    }

    /**
     * Set the output file or stream for the incoming audio of a group call participant.
     */
    public function groupCallSetOutput(int $id, mixed $participant, LocalFile|WritableStream $file): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->setOutput($participant, $file);
    }

    /**
     * Play a file in a group call.
     */
    public function groupCallPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file): void
    {
        self::validateCallAudio($file);
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->play($file);
    }

    /**
     * Play a file in a group call, blocking until the file has finished playing if a stream is provided.
     *
     * @internal
     */
    public function groupCallPlayBlocking(int $id, LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (!isset($this->groupCalls[$id])) {
            return;
        }
        $this->groupCallPlay($id, $file);
        if ($file instanceof ReadableStream) {
            $deferred = new DeferredFuture;
            $file->onClose($deferred->complete(...));
            $deferred->getFuture()->await();
        }
    }

    /**
     * Files to play on hold in a group call.
     */
    public function groupCallPlayOnHold(int $id, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        foreach ($files as $file) {
            self::validateCallAudio($file);
        }
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->playOnHold(...$files);
    }

    /**
     * Files to play on hold in a group call, blocking until they have finished playing.
     *
     * @internal
     */
    public function groupCallPlayOnHoldBlocking(int $id, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        if (!isset($this->groupCalls[$id])) {
            return;
        }
        $this->groupCallPlayOnHold($id, ...$files);
        foreach ($files as $file) {
            if ($file instanceof ReadableStream) {
                $deferred = new DeferredFuture;
                $file->onClose($deferred->complete(...));
                $deferred->getFuture()->await();
            }
        }
    }

    /**
     * Play the VP8 video and OPUS audio of a WebM file in a group call.
     *
     * The file is demuxed in pure PHP and its frames are sent as-is, so no transcoding (and thus
     * no FFI extension) is involved; convert your media to WebM with VP8 video and OPUS audio
     * beforehand.
     */
    public function groupCallPlayVideo(int $id, LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->playVideo($file);
    }

    /**
     * Stop transmitting video in a group call.
     */
    public function groupCallStopVideo(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->stopVideo();
    }

    /**
     * Skip to the next file in the playlist of a group call.
     */
    public function groupCallSkipPlay(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->skip();
    }

    /**
     * Stop playing all files in a group call, clearing the main and the hold playlist.
     */
    public function groupCallStopPlay(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->stop();
    }

    /**
     * Pause playback of the current audio file in a group call.
     */
    public function groupCallPausePlay(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->pause();
    }

    /**
     * Resume playback of the current audio file in a group call.
     */
    public function groupCallResumePlay(int $id): void
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        $this->groupCalls[$id]->resume();
    }

    /**
     * Whether the currently playing audio file of a group call is paused.
     */
    public function isGroupCallPlayPaused(int $id): bool
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->isPaused();
    }

    /**
     * Get the file that is currently being played in a group call.
     */
    public function groupCallGetCurrent(int $id): RemoteUrl|LocalFile|string|null
    {
        if (!isset($this->groupCalls[$id])) {
            throw new AssertionError('Unknown group call!');
        }
        return $this->groupCalls[$id]->getCurrent();
    }

    /**
     * @internal
     */
    private static function validateCallAudio(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (Tools::canConvertOgg()) {
            return;
        }
        if ($file instanceof LocalFile || $file instanceof RemoteUrl) {
            Ogg::validateOgg($file);
            return;
        }
        throw new AssertionError('The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!');
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
                $id = $update['call']['id'];
                if (!isset($this->groupCalls[$id])) {
                    $this->logger->logger("Ignoring update for unknown group call $id");
                    return;
                }
                $this->groupCalls[$id]->onGroupCallUpdate($update['call']);
                break;
            case 'updateGroupCallParticipants':
                $id = $update['call']['id'];
                if (!isset($this->groupCalls[$id])) {
                    $this->logger->logger("Ignoring update for unknown group call $id");
                    return;
                }
                $this->groupCalls[$id]->onParticipantsUpdate(
                    $update['participants'],
                    $update['version']
                );
                break;
        }
    }
}
