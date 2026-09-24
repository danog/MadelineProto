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

// IMPORTANT NOTE: Please keep the above copyright notice intact if copying or rewriting this file in another language.

namespace danog\MadelineProto\GroupCall;

use Amp\ByteStream\WritableStream;
use Amp\Sync\LocalMutex;
use danog\DialogId\DialogId;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCall;
use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\EventHandler\Calls\LiveStory;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\TextEntities;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\GroupConnection;
use danog\MadelineProto\Tgcalls\GroupConnectionOwner;
use danog\MadelineProto\Tgcalls\GroupMediaTrait;
use danog\MadelineProto\Tgcalls\GroupSdp;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * Manages a single non-E2E group call (video chat, livestream or live story).
 *
 * See https://core.telegram.org/api/group-calls for the protocol description.
 *
 * @internal
 */
final class GroupCallController implements CallControllerInterface, GroupConnectionOwner
{
    use GroupMediaTrait;

    /** How often [phone.checkGroupCall](https://core.telegram.org/method/phone.checkGroupCall) is polled while reconnecting. */
    private const CHECK_INTERVAL = 4.0;
    /** How long we wait for a missing `version` before refetching the whole call. */
    private const VERSION_GAP_TIMEOUT = 1.0;
    /** How many participants are fetched per page, and at most, when (re)loading the participant list. */
    private const PARTICIPANTS_PAGE = 100;
    private const PARTICIPANTS_MAX = 5000;

    private GroupCallState $callState = GroupCallState::NOT_JOINED;

    /** The [inputGroupCall](https://core.telegram.org/constructor/inputGroupCall) of this call. */
    private array $inputCall;
    /** The last known [groupCall](https://core.telegram.org/constructor/groupCall). */
    private array $call;

    private LocalMutex $joinMutex;

    /** @var array<int, Participant> Participants indexed by their bot API peer ID. */
    private array $participants = [];
    /** @var array<int, int> Signed source ID => bot API peer ID. */
    private array $sourceToPeer = [];

    /** Our own audio source ID, in the signed form used by the API. */
    private int $source = 0;
    private bool $streamMode = false;
    private bool $rtmpMode = false;
    /** Downloads and records the call's media chunks in stream mode. */
    private ?StreamReceiver $streamReceiver = null;
    /** Whether the SFU transport parameters of the current join were already applied. */
    private bool $connectionParamsApplied = false;

    private ?string $checkWatcher = null;
    private ?string $gapWatcher = null;

    /** @var array<int, int> Every signed source ever seen => bot API peer ID, to attribute the last events of a participant that left. */
    private array $sourcePeers = [];

    public readonly AbstractGroupCall $public;

    /**
     * @internal
     */
    public function __construct(
        public readonly MTProto $API,
        array $call,
        ?int $peerId = null,
        bool $liveStory = false,
    ) {
        $this->call = $call;
        $this->inputCall = [
            '_' => 'inputGroupCall',
            'id' => $call['id'],
            'access_hash' => $call['access_hash'],
        ];
        $this->public = $liveStory ? new LiveStory($API, $call, $peerId) : new GroupCall($API, $call, $peerId);
        $this->diskJockey = new DjLoop($this);
        Assert::true($this->diskJockey->start());
        $this->joinMutex = new LocalMutex;
    }

    /**
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $result = get_object_vars($this);
        // The WebRTC connection now serializes itself and resumes its SFU transport on wakeup, so it
        // is kept. The mutex and the two event-loop watcher IDs cannot survive and are recreated.
        unset($result['joinMutex'], $result['checkWatcher'], $result['gapWatcher'], $result['streamReceiver']);
        return $result;
    }

    public function __unserialize(array $data): void
    {
        $this->joinMutex = new LocalMutex;
        $this->checkWatcher = null;
        $this->gapWatcher = null;
        $this->streamReceiver = null;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->diskJockey ??= new DjLoop($this);
        Assert::true($this->diskJockey->start());
        if ($this->presentationDj !== null) {
            Assert::true($this->presentationDj->start());
        }
        EventLoop::queue(function (): void {
            if ($this->callState !== GroupCallState::JOINED && $this->callState !== GroupCallState::JOINING) {
                return;
            }
            // The WebRTC connection restored itself and its transport to the SFU resumes on its own;
            // now that the whole graph is back, resume the recorders (reopen their files, re-subscribe
            // to their live tracks) and re-poll the participant list so received sources are in sync.
            $this->log("Resumed $this after a restart of the process.");
            // Restart the demuxers/readers too (DjLoop::__unserialize() deliberately leaves them dormant),
            // or nothing is transmitted after a restart.
            $this->diskJockey->resumeReader();
            $this->presentationDj?->resumeReader();
            $this->connection?->resume();
            $this->presentationConnection?->resume();
            if ($this->streamMode) {
                // The stream receiver does not survive a restart (its recording does not either):
                // follow the stream again from the live edge.
                $this->streamReceiver = new StreamReceiver($this);
                $this->streamReceiver->start($this->rtmpMode);
            }
            EventLoop::queue($this->refetch(...));
        });
    }

    /**
     * The [inputGroupCall](https://core.telegram.org/constructor/inputGroupCall) of this call.
     *
     * @internal
     */
    #[\Override]
    public function getInputCall(): array
    {
        return $this->inputCall;
    }

    /**
     * Join the group call.
     *
     * @param bool  $muted    Whether to join muted.
     * @param mixed $joinAs   Peer to join as, only for video chats/livestreams.
     * @param string|null $inviteHash Invite hash from a video chat invite link, if any.
     */
    public function join(bool $muted = false, mixed $joinAs = null, ?string $inviteHash = null): self
    {
        $lock = $this->joinMutex->acquire();
        try {
            if ($this->callState === GroupCallState::JOINED || $this->callState === GroupCallState::JOINING) {
                return $this;
            }
            $this->callState = GroupCallState::JOINING;
            $this->connectionParamsApplied = false;
            $this->muted = $muted;
            $this->log("Joining $this...", Logger::VERBOSE);

            $updates = null;
            for ($attempt = 0; $attempt < 5 && $updates === null; $attempt++) {
                $this->connection = $this->replaceConnection();
                $params = $this->connection->buildJoinPayload();
                $this->source = $this->connection->getAudioSource();
                $this->log("Join payload of $this: ".json_encode($params), Logger::VERBOSE);

                $request = [
                    'call' => $this->inputCall,
                    'join_as' => $joinAs ?? ['_' => 'inputPeerSelf'],
                    'muted' => $muted,
                    // Announce the video we are already playing (e.g. when re-joining after the transport
                    // failed): the other clients only request our video if the server says we send some.
                    'video_stopped' => $this->diskJockey->getVideoCodec() === null,
                    // DataJSON arguments are encoded by the TL serializer, pass the decoded payload.
                    'params' => $params,
                ];
                if ($inviteHash !== null) {
                    $request['invite_hash'] = $inviteHash;
                }
                try {
                    $updates = $this->API->methodCallAsyncRead('phone.joinGroupCall', $request);
                } catch (RPCErrorException $e) {
                    if ($e->rpc === 'GROUPCALL_SSRC_DUPLICATE_MUCH') {
                        // The server asks us to retry with a fresh SSRC.
                        $this->log("Retrying to join $this with a new SSRC...", Logger::WARNING);
                    } elseif ($e->getCode() === 500 && $attempt < 4) {
                        // Transient server-side failures (e.g. GROUPCALL_ADD_PARTICIPANTS_FAILED right
                        // after we were dropped from the call): back off and try again.
                        $this->log("Could not join $this ({$e->rpc}), retrying...", Logger::WARNING);
                        Tools::sleep(1.0 + (float) $attempt);
                    } else {
                        throw $e;
                    }
                }
            }
            if ($updates === null) {
                throw new Exception('Could not join the group call, the server kept rejecting our SSRC!');
            }
            foreach ($updates['updates'] as $update) {
                if ($update['_'] === 'updateGroupCallConnection' && !($update['presentation'] ?? false)) {
                    $this->applyConnectionParams($update['params']);
                }
            }
            $this->callState = GroupCallState::JOINED;
            $this->log("Joined $this!", Logger::NOTICE);
            // A (re-)join always starts from a fresh WebRTC connection: hand it every recording that
            // was requested, so recordings resume (in new segments) instead of silently stopping.
            $this->rewireOutputs();
            EventLoop::queue($this->refetch(...));
            return $this;
        } catch (Throwable $e) {
            $this->callState = GroupCallState::NOT_JOINED;
            $this->connection?->close();
            $this->connection = null;
            throw $e;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    /**
     * Apply the `params` of an
     * [updateGroupCallConnection](https://core.telegram.org/constructor/updateGroupCallConnection),
     * already decoded by the TL deserializer.
     */
    private function applyConnectionParams(array $params): void
    {
        if ($this->connectionParamsApplied) {
            // Reset to false at the start of every join(): apply the first updateGroupCallConnection
            // that reaches us for this join and ignore any redelivery of it.
            return;
        }
        $this->connectionParamsApplied = true;
        $this->log("Join response of $this: ".json_encode($params), Logger::VERBOSE);
        $parsed = GroupSdp::parseJoinResponse($params);
        $this->streamMode = $parsed['stream'];
        $this->rtmpMode = $parsed['rtmp'];
        if ($parsed['stream']) {
            // The server switched us to stream mode: there is no WebRTC session to set up, media is
            // downloaded in chunks instead. See https://core.telegram.org/api/group-calls#stream-mode.
            $this->log(
                "$this is in ".($parsed['rtmp'] ? 'RTMP' : 'stream')." mode: media is received by downloading chunks, and cannot be transmitted.",
                Logger::NOTICE
            );
            $this->connection?->close();
            $this->connection = null;
            $this->streamReceiver ??= new StreamReceiver($this);
            $this->streamReceiver->start($parsed['rtmp']);
            return;
        }
        if ($parsed['transport'] === null) {
            throw new Exception('Missing transport parameters in the group call join response!');
        }
        $this->connection?->setTransport($parsed['transport'], $parsed['video']);
    }

    /**
     * Fetch the full call info and participant list.
     *
     * @internal
     */
    public function refetch(): void
    {
        try {
            $result = $this->API->methodCallAsyncRead('phone.getGroupCall', [
                'call' => $this->inputCall,
                'limit' => self::PARTICIPANTS_PAGE,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not refetch $this: $e", Logger::WARNING);
            return;
        }
        $this->applyGroupCall($result);
    }

    /**
     * Page through the rest of the participant list (phone.getGroupParticipants) after the first page
     * a phone.groupCall carries, up to a sane maximum.
     */
    private function fetchRemainingParticipants(string $offset): void
    {
        while ($offset !== '' && \count($this->participants) < self::PARTICIPANTS_MAX) {
            try {
                $page = $this->API->methodCallAsyncRead('phone.getGroupParticipants', [
                    'call' => $this->inputCall,
                    'ids' => [],
                    'sources' => [],
                    'offset' => $offset,
                    'limit' => self::PARTICIPANTS_PAGE,
                ]);
            } catch (Throwable $e) {
                $this->log("Could not fetch the participants of $this: $e", Logger::WARNING);
                return;
            }
            \assert(\is_array($page) && \is_array($page['participants']));
            foreach ($page['participants'] as $participant) {
                \assert(\is_array($participant));
                $this->applyParticipant($participant);
            }
            $offset = (string) ($page['next_offset'] ?? '');
            if ($page['participants'] === []) {
                return;
            }
        }
    }

    /**
     * A participant by their id, username or peer: from the cached list, or else looked up with
     * phone.getGroupParticipants. Null if they are not in the call.
     */
    public function getParticipant(mixed $participant): ?Participant
    {
        $peerId = $this->API->getId($participant);
        if (isset($this->participants[$peerId])) {
            return $this->participants[$peerId];
        }
        try {
            $page = $this->API->methodCallAsyncRead('phone.getGroupParticipants', [
                'call' => $this->inputCall,
                'ids' => [$peerId],
                'sources' => [],
                'offset' => '',
                'limit' => 1,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not look up participant $peerId of $this: $e", Logger::WARNING);
            return null;
        }
        \assert(\is_array($page) && \is_array($page['participants']));
        foreach ($page['participants'] as $raw) {
            \assert(\is_array($raw));
            $this->applyParticipant($raw);
        }
        /** @var array<int, Participant> $participants */
        $participants = $this->participants;
        return $participants[$peerId] ?? null;
    }

    /**
     * Apply a [phone.groupCall](https://core.telegram.org/constructor/phone.groupCall).
     *
     * @internal
     */
    public function applyGroupCall(array $result): void
    {
        \assert(\is_array($result['call']));
        $this->call = $result['call'];
        $this->public->update($result['call']);
        if ($result['call']['_'] === 'groupCallDiscarded') {
            $this->onDiscarded();
            return;
        }
        $this->participants = [];
        $this->sourceToPeer = [];
        foreach ($result['participants'] as $participant) {
            $this->applyParticipant($participant);
        }
        $this->fetchRemainingParticipants((string) ($result['participants_next_offset'] ?? ''));
        $this->syncSources();
    }

    /**
     * Apply an [updateGroupCall](https://core.telegram.org/constructor/updateGroupCall).
     *
     * @internal
     */
    public function onGroupCallUpdate(array $call): void
    {
        if ($call['_'] === 'groupCallDiscarded') {
            $this->call = $call;
            $this->public->update($call);
            $this->onDiscarded();
            return;
        }
        $cached = $this->call['version'] ?? 0;
        $version = $call['version'] ?? 0;
        if ($version < $cached + 1) {
            return;
        }
        if ($version > $cached + 1) {
            $this->scheduleGapRefetch();
            return;
        }
        if (($call['min'] ?? false) && isset($this->call['version'])) {
            // A specific set of fields of a min groupCall cannot be applied over the cached one,
            // see https://core.telegram.org/constructor/groupCall.
            $call = array_merge($this->call, array_diff_key($call, array_flip([
                'join_muted', 'can_change_join_muted', 'schedule_start_subscribed', 'can_start_video',
                'creator', 'can_change_messages_enabled', 'unmuted_video_count', 'unmuted_video_limit',
                'stream_dc_id', 'invite_link', 'default_send_as', 'join_date_asc',
            ])));
        }
        $this->cancelGapRefetch();
        $this->call = $call;
        $this->public->update($call);
    }

    /**
     * Apply an [updateGroupCallParticipants](https://core.telegram.org/constructor/updateGroupCallParticipants).
     *
     * @internal
     */
    public function onParticipantsUpdate(array $participants, int $version): void
    {
        $versioned = false;
        foreach ($participants as $participant) {
            if (($participant['versioned'] ?? false)
                || ($participant['left'] ?? false)
                || ($participant['just_joined'] ?? false)
            ) {
                $versioned = true;
                break;
            }
        }
        if ($versioned) {
            $cached = $this->call['version'] ?? 0;
            if ($version < $cached) {
                return;
            }
            if ($version > $cached + 1) {
                $this->scheduleGapRefetch();
                return;
            }
            $this->cancelGapRefetch();
            $this->call['version'] = $version;
        }
        foreach ($participants as $participant) {
            $this->applyParticipant($participant);
        }
        $this->syncSources();
    }

    private function applyParticipant(array $participant): void
    {
        $peerId = $this->API->getIdInternal($participant['peer']);
        if ($peerId === null) {
            return;
        }
        if ($participant['left'] ?? false) {
            $old = $this->participants[$peerId] ?? null;
            if ($old !== null) {
                unset($this->sourceToPeer[$old->source], $this->participants[$peerId]);
            }
            if (($participant['self'] ?? false)
                && $this->callState === GroupCallState::JOINED
                && (int) ($participant['source'] ?? 0) === $this->source
            ) {
                // The server dropped our own source: we were removed from the call, rejoin (as
                // official clients do).
                $this->log("We were removed from $this, rejoining...", Logger::WARNING);
                EventLoop::queue($this->rejoin(...));
            }
            return;
        }
        $parsed = Participant::fromRaw($participant, $peerId, $this->participants[$peerId] ?? null);
        $this->participants[$peerId] = $parsed;
        if ($parsed->source !== 0) {
            $this->sourceToPeer[$parsed->source] = $peerId;
            $this->sourcePeers[$parsed->source] = $peerId;
            if (isset($this->explicitOutputs[$peerId])) {
                $this->wireOutput($peerId);
            }
            // Folder mode: start recording a participant that has just begun transmitting.
            $this->wireFolderOutput($peerId);
        }
    }

    /**
     * Tell the WebRTC engine which participants we want to receive audio and video from.
     */
    private function syncSources(): void
    {
        if ($this->connection === null) {
            return;
        }
        $sources = [];
        foreach ($this->participants as $participant) {
            if ($participant->source !== 0 && $participant->source !== $this->source) {
                $sources[] = [
                    'audio' => $participant->source,
                    'muted' => $participant->muted,
                    'video' => $participant->videoSources,
                    'presentation' => $participant->presentationSources,
                    'videoEndpoint' => $participant->videoEndpoint,
                    'presentationEndpoint' => $participant->presentationEndpoint,
                ];
            }
        }
        $this->connection->setRemoteSources($sources);
    }

    private function scheduleGapRefetch(): void
    {
        if ($this->gapWatcher !== null) {
            return;
        }
        $this->gapWatcher = EventLoop::delay(self::VERSION_GAP_TIMEOUT, function (): void {
            $this->gapWatcher = null;
            $this->log("Filling a version gap in $this by refetching it.", Logger::VERBOSE);
            $this->refetch();
        });
    }

    private function cancelGapRefetch(): void
    {
        if ($this->gapWatcher !== null) {
            EventLoop::cancel($this->gapWatcher);
            $this->gapWatcher = null;
        }
    }

    /**
     * Called by the WebRTC engine when the connection is broken.
     *
     * @internal
     */
    #[\Override]
    public function onConnectionFailed(): void
    {
        if ($this->callState !== GroupCallState::JOINED || $this->checkWatcher !== null) {
            return;
        }
        $this->log("The WebRTC connection of $this failed, checking whether we are still joined...", Logger::WARNING);
        $this->checkWatcher = EventLoop::repeat(self::CHECK_INTERVAL, function (): void {
            if ($this->callState !== GroupCallState::JOINED) {
                $this->stopChecking();
                return;
            }
            $sources = [$this->source];
            $presentationSource = $this->presentationConnection?->getAudioSource();
            if ($presentationSource !== null) {
                $sources[] = $presentationSource;
            }
            try {
                $alive = $this->API->methodCallAsyncRead('phone.checkGroupCall', [
                    'call' => $this->inputCall,
                    'sources' => $sources,
                ]);
            } catch (Throwable) {
                $alive = [];
            }
            if (\in_array($this->source, $alive, true)) {
                if ($presentationSource !== null && !\in_array($presentationSource, $alive, true)) {
                    // Only the screen-share was dropped: rejoin just the presentation.
                    $this->log("Our screen-share was dropped from $this, rejoining it...", Logger::WARNING);
                    $this->disablePresentation();
                    try {
                        $this->enablePresentation();
                    } catch (Throwable $e) {
                        $this->log("Could not rejoin the presentation of $this: $e", Logger::ERROR);
                    }
                }
                return;
            }
            $this->stopChecking();
            $this->log("We were dropped from $this, rejoining...", Logger::WARNING);
            $this->rejoin();
        });
    }

    /**
     * Join again from scratch with a new payload, keeping the playlists.
     *
     * @internal
     */
    public function rejoin(): void
    {
        $this->stopChecking();
        $muted = $this->muted;
        $this->dropPresentation();
        // Recordings in progress carry on with the connection of the new join.
        $this->detachConnection();
        $this->callState = GroupCallState::NOT_JOINED;
        try {
            $this->join($muted);
        } catch (Throwable $e) {
            $this->log("Could not rejoin $this: $e", Logger::ERROR);
        }
    }

    private function stopChecking(): void
    {
        if ($this->checkWatcher !== null) {
            EventLoop::cancel($this->checkWatcher);
            $this->checkWatcher = null;
        }
    }

    /**
     * Called by the WebRTC engine when audio starts flowing from a source.
     *
     * @internal
     */
    #[\Override]
    public function onIncomingSource(int $source): void
    {
        $this->log("Audio of source $source is now flowing in $this", Logger::VERBOSE);
    }

    /**
     * Leave the group call without ending it for the other participants.
     */
    public function leave(): self
    {
        if ($this->callState === GroupCallState::LEFT || $this->callState === GroupCallState::NOT_JOINED) {
            $this->cleanup();
            return $this;
        }
        $source = $this->source;
        $this->cleanup();
        try {
            $this->API->methodCallAsyncRead('phone.leaveGroupCall', [
                'call' => $this->inputCall,
                'source' => $source,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not leave $this: $e", Logger::WARNING);
        }
        return $this;
    }

    /**
     * End the group call for all participants.
     */
    public function discard(): self
    {
        $this->cleanup();
        try {
            $this->API->methodCallAsyncRead('phone.discardGroupCall', ['call' => $this->inputCall]);
        } catch (Throwable $e) {
            $this->log("Could not discard $this: $e", Logger::WARNING);
        }
        return $this;
    }

    private function onDiscarded(): void
    {
        $this->log("$this was discarded!", Logger::NOTICE);
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if ($this->callState === GroupCallState::LEFT) {
            return;
        }
        $this->callState = GroupCallState::LEFT;
        $this->stopChecking();
        $this->cancelGapRefetch();
        $this->diskJockey->discard();
        $this->dropPresentation();
        $this->closeConnection();
        $this->streamReceiver?->stop();
        $this->streamReceiver = null;
        $this->API->cleanupGroupCall($this->public->id);
    }

    /**
     * Whether the server switched us to chunk-download playback.
     */
    public function isStreamMode(): bool
    {
        return $this->streamMode;
    }

    /**
     * Whether all media of this call is published by a single external RTMP publisher.
     */
    public function isRtmpMode(): bool
    {
        return $this->rtmpMode;
    }

    /**
     * Record one participant's media into a single file (or stream) with a fixed set of tracks (see
     * {@see GroupMediaTrait::recordParticipant()}), or, in stream mode, the call's single mixed stream
     * (OGG OPUS is supported there only).
     *
     * @param ?int $streams The {@see CallStream} flags to record, or null for every available one.
     *
     * @return int The streams currently sent, as {@see CallStream} flags.
     */
    public function setOutput(LocalFile|WritableStream $file, mixed $participant = null, ?RecordingFormat $format = null, ?int $streams = null): int
    {
        if (!$this->streamMode) {
            return $this->recordParticipant($file, $participant, $format, $streams);
        }
        // A single mixed stream, downloaded in chunks: there are no per-participant sources.
        if ($participant !== null) {
            throw new \InvalidArgumentException('In stream mode the call is a single mixed stream: record it without specifying a participant.');
        }
        if ($streams !== null) {
            CallStream::validate($streams);
        }
        $available = CallStream::AUDIO | ($this->rtmpMode ? CallStream::VIDEO : 0);
        CallStream::checkAvailable($streams, $available, 'The stream');
        $this->streamReceiver ??= new StreamReceiver($this);
        $this->streamReceiver->setOutput($file, $format);
        return $available;
    }

    /**
     * Record the call into a directory, as numbered series of Matroska files, one per participant
     * (see {@see GroupMediaTrait::recordFolder()}); in stream mode the mixed stream is recorded as
     * `<dir>/stream.<ext>` (see {@see StreamReceiver::setOutputDirectory()}).
     */
    public function setOutputFolder(LocalDirectory $dir, mixed $participant = null, ?RecordingFormat $format = null): self
    {
        if (!$this->streamMode) {
            $this->recordFolder($dir, $participant, $format);
            return $this;
        }
        if ($participant !== null) {
            throw new \InvalidArgumentException('In stream mode the call is a single mixed stream: record it without specifying a participant.');
        }
        $this->streamReceiver ??= new StreamReceiver($this);
        $this->streamReceiver->setOutputDirectory($dir, $format);
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function callObject(): Call
    {
        return $this->public;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function configureConnection(GroupConnection $connection, bool $screencast): void
    {
        // An ordinary group call trusts the SFU: nothing to set up.
    }

    #[\Override]
    private function onDroppedByServer(): void
    {
        EventLoop::queue($this->rejoin(...));
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function participantOf(int $peerId): ?Participant
    {
        return $this->participants[$peerId] ?? null;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function isOurself(int $peerId, Participant $participant): bool
    {
        return $participant->self || $participant->source === $this->source;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function peerOfSource(int $source): ?int
    {
        return $this->sourceToPeer[$source] ?? $this->sourcePeers[$source] ?? null;
    }

    /**
     * @return list<int>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    private function knownPeers(): array
    {
        return array_keys($this->participants);
    }

    /**
     * Get all known participants, indexed by their bot API peer ID.
     *
     * @return array<int, Participant>
     */
    public function getParticipants(): array
    {
        return $this->participants;
    }

    /**
     * Invite users to the call.
     */
    public function invite(mixed ...$users): void
    {
        $this->API->methodCallAsyncRead('phone.inviteToGroupCall', [
            'call' => $this->inputCall,
            'users' => $users,
        ]);
    }

    /**
     * Export an invite link for the call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; admins only.
     */
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        return $this->API->methodCallAsyncRead('phone.exportGroupCallInvite', [
            'call' => $this->inputCall,
            'can_self_unmute' => $canSelfUnmute,
        ])['link'];
    }

    /**
     * Change a participant's state with phone.editGroupCallParticipant: mute/unmute them (admins; a
     * non-admin mutes them only for themselves), set our playback volume of them, raise/lower our own
     * hand, or pause/resume our own video or screen-share.
     */
    public function editParticipant(mixed $participant, ?bool $muted = null, ?int $volume = null, ?bool $raiseHand = null, ?bool $videoStopped = null, ?bool $videoPaused = null, ?bool $presentationPaused = null): void
    {
        $params = ['call' => $this->inputCall, 'participant' => $participant];
        foreach (['muted' => $muted, 'volume' => $volume, 'raise_hand' => $raiseHand, 'video_stopped' => $videoStopped, 'video_paused' => $videoPaused, 'presentation_paused' => $presentationPaused] as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            }
        }
        if ($volume !== null && ($volume < 1 || $volume > 20000)) {
            throw new \InvalidArgumentException('The volume must be between 1 and 20000 (10000 = 100%).');
        }
        $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', $params);
    }

    /**
     * Change the call's settings with phone.toggleGroupCallSettings (admins only).
     *
     * @param int|null $sendPaidMessagesStars Live stories: minimum donation to comment, 0 for none.
     */
    public function toggleSettings(?bool $joinMuted = null, bool $resetInviteHash = false, ?bool $messagesEnabled = null, ?int $sendPaidMessagesStars = null): void
    {
        $params = ['call' => $this->inputCall, 'reset_invite_hash' => $resetInviteHash];
        if ($joinMuted !== null) {
            $params['join_muted'] = $joinMuted;
        }
        if ($messagesEnabled !== null) {
            $params['messages_enabled'] = $messagesEnabled;
        }
        if ($sendPaidMessagesStars !== null) {
            $params['send_paid_messages_stars'] = $sendPaidMessagesStars;
        }
        $this->API->methodCallAsyncRead('phone.toggleGroupCallSettings', $params);
    }

    /**
     * Start or stop a server-side recording (phone.toggleGroupCallRecord, admins only).
     */
    public function toggleRecord(bool $start, ?string $title = null, bool $video = false, bool $portrait = false): void
    {
        $params = ['call' => $this->inputCall, 'start' => $start];
        if ($start) {
            if ($title !== null) {
                $params['title'] = $title;
            }
            if ($video) {
                $params['video'] = true;
                $params['video_portrait'] = $portrait;
            }
        }
        $this->API->methodCallAsyncRead('phone.toggleGroupCallRecord', $params);
    }

    /**
     * Start this scheduled call now (phone.startScheduledGroupCall, admins only).
     */
    public function startScheduled(): void
    {
        $this->API->methodCallAsyncRead('phone.startScheduledGroupCall', ['call' => $this->inputCall]);
    }

    /**
     * Subscribe to (or unsubscribe from) a notification when this scheduled call starts.
     */
    public function setStartSubscription(bool $subscribed): void
    {
        $this->API->methodCallAsyncRead('phone.toggleGroupCallStartSubscription', ['call' => $this->inputCall, 'subscribed' => $subscribed]);
    }

    /**
     * Send an in-call message (phone.sendGroupCallMessage): text with entities, or, in a live story, a
     * donation of `$paidStars` (with an empty text for a standalone donation).
     */
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, mixed $sendAs = null): void
    {
        $entities = [];
        if ($parseMode === ParseMode::MARKDOWN) {
            $parsed = TextEntities::fromMarkdown($message);
            [$message, $entities] = [$parsed->message, $parsed->entities];
        } elseif ($parseMode === ParseMode::HTML) {
            $parsed = TextEntities::fromHtml($message);
            [$message, $entities] = [$parsed->message, $parsed->entities];
        }
        $this->sendTextWithEntities($message, $entities, $paidStars, $sendAs);
    }

    /**
     * Send an in-call reaction: a single emoji, or a custom emoji with `$emoji` as its fallback.
     */
    public function sendReaction(string $emoji, ?int $customEmojiId = null): void
    {
        $entities = [];
        if ($customEmojiId !== null) {
            $entities[] = ['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => self::utf16Length($emoji), 'document_id' => $customEmojiId];
        }
        $this->sendTextWithEntities($emoji, $entities, null, null);
    }

    /**
     * @param list<mixed> $entities
     */
    private function sendTextWithEntities(string $text, array $entities, ?int $paidStars, mixed $sendAs): void
    {
        $params = [
            'call' => $this->inputCall,
            'random_id' => random_int(PHP_INT_MIN, PHP_INT_MAX),
            'message' => ['_' => 'textWithEntities', 'text' => $text, 'entities' => $entities],
            ...($paidStars !== null ? ['allow_paid_stars' => $paidStars] : []),
            ...($sendAs !== null ? ['send_as' => $sendAs] : []),
        ];
        $this->API->methodCallAsyncRead('phone.sendGroupCallMessage', $params);
    }

    /**
     * The length of a string in UTF-16 code units, as entities count it.
     *
     * @psalm-pure
     */
    public static function utf16Length(string $text): int
    {
        return (int) (\strlen(mb_convert_encoding($text, 'UTF-16', 'UTF-8')) / 2);
    }

    /**
     * Delete in-call messages (our own, or anyone's for admins).
     *
     * @param list<int> $ids
     */
    public function deleteMessages(array $ids, bool $reportSpam = false): void
    {
        $this->API->methodCallAsyncRead('phone.deleteGroupCallMessages', [
            'call' => $this->inputCall,
            'messages' => $ids,
            'report_spam' => $reportSpam,
        ]);
    }

    /**
     * Delete every in-call message of a participant (admins only).
     */
    public function deleteParticipantMessages(mixed $participant, bool $reportSpam = false): void
    {
        $this->API->methodCallAsyncRead('phone.deleteGroupCallParticipantMessages', [
            'call' => $this->inputCall,
            'participant' => $participant,
            'report_spam' => $reportSpam,
        ]);
    }

    /**
     * The Telegram Stars donated to this live story so far, and its top donors.
     */
    public function getStars(): GroupCallStars
    {
        $result = $this->API->methodCallAsyncRead('phone.getGroupCallStars', ['call' => $this->inputCall]);
        \assert(\is_array($result));
        $donors = [];
        foreach ((array) ($result['top_donors'] ?? []) as $donor) {
            \assert(\is_array($donor));
            $donors[] = new GroupCallDonor(
                isset($donor['peer_id']) ? $this->API->getIdInternal($donor['peer_id']) : null,
                (int) $donor['stars'],
                (bool) ($donor['top'] ?? false),
                (bool) ($donor['my'] ?? false),
            );
        }
        return new GroupCallStars((int) ($result['total_stars'] ?? 0), $donors);
    }

    /**
     * The peer we send in-call messages of this live story as by default (phone.saveDefaultSendAs).
     */
    public function saveDefaultSendAs(mixed $peer): void
    {
        $this->API->methodCallAsyncRead('phone.saveDefaultSendAs', ['call' => $this->inputCall, 'send_as' => $peer]);
    }

    /**
     * Remove participants from the video chat by kicking them from the group or channel it belongs to,
     * which also drops them from the call: a video chat has no notion of removing someone from just
     * the call, and this is exactly what official clients do (Telegram Desktop's
     * `Panel::kickParticipantSure`): in a basic group they are removed with messages.deleteChatUser, in
     * a supergroup or channel they are banned with channels.editBanned using the "kicked" rights (for a
     * user, all the send restrictions on top of `view_messages`; for a channel participant just
     * `view_messages`). Requires the `ban_users` admin right.
     */
    public function removeParticipant(mixed ...$participants): void
    {
        $peerId = $this->public->peerId;
        if ($peerId === null) {
            throw new \RuntimeException("The group or channel of $this is unknown, cannot remove participants from it.");
        }
        foreach ($participants as $participant) {
            $participantId = $this->API->getId($participant);
            if (!DialogId::isSupergroupOrChannel($peerId)) {
                $this->API->methodCallAsyncRead('messages.deleteChatUser', [
                    'chat_id' => $peerId,
                    'user_id' => $participantId,
                ]);
                continue;
            }
            $rights = ['_' => 'chatBannedRights', 'view_messages' => true, 'until_date' => 0];
            if (DialogId::isUser($participantId)) {
                $rights += [
                    'send_stickers' => true,
                    'send_gifs' => true,
                    'send_games' => true,
                    'send_inline' => true,
                    'send_photos' => true,
                    'send_videos' => true,
                    'send_roundvideos' => true,
                    'send_audios' => true,
                    'send_voices' => true,
                    'send_docs' => true,
                    'send_plain' => true,
                    'embed_links' => true,
                ];
            }
            $this->API->methodCallAsyncRead('channels.editBanned', [
                'channel' => $peerId,
                'participant' => $participantId,
                'banned_rights' => $rights,
            ]);
        }
    }

    /**
     * Our own audio source ID, in the signed form used by the API.
     */
    public function getSource(): int
    {
        return $this->source;
    }

    #[\Override]
    public function getCallState(): GroupCallState
    {
        return $this->callState;
    }

    /**
     * The last known [groupCall](https://core.telegram.org/constructor/groupCall).
     *
     * @internal
     */
    public function getRawCall(): array
    {
        return $this->call;
    }

    #[\Override]
    public function log(string $message, int $level = Logger::NOTICE): void
    {
        $this->API->logger->logger($message, $level);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function isCallEnded(): bool
    {
        return $this->callState === GroupCallState::LEFT;
    }

    // Playback API, mirroring the one-to-one call API.

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->public->__toString();
    }
}
