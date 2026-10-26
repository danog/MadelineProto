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
use Amp\CancelledException;
use Amp\Sync\LocalMutex;
use Amp\TimeoutCancellation;
use danog\DialogId\DialogId;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCall;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCallParticipant;
use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\EventHandler\Calls\GroupCallDonor;
use danog\MadelineProto\EventHandler\Calls\GroupCallParticipant;
use danog\MadelineProto\EventHandler\Calls\GroupCallStars;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\EventHandler\Calls\LiveStory;
use danog\MadelineProto\EventHandler\Calls\LiveStoryParticipant;
use danog\MadelineProto\Exception;
use danog\MadelineProto\GroupCall\Rtmp\RtmpPublisher;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RestoresDeclaredProperties;
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
 * @psalm-import-type StreamMask from CallStream
 * @internal
 */
final class GroupCallController implements CallControllerInterface, GroupConnectionOwner
{
    use RestoresDeclaredProperties;
    use GroupMediaTrait;

    /**
     * How often [phone.checkGroupCall](https://core.telegram.org/method/phone.checkGroupCall) is polled while
     * joined, as TDLib does: the WebRTC connection can't tell whether we're still in the call, as the SFU keeps
     * the endpoint of a call that ended (or that dropped us) for as long as we send it media.
     */
    private const CHECK_INTERVAL = 10.0;
    /** How long a failed rejoin waits before trying again, at first and at most (doubling in between). */
    private const REJOIN_RETRY_MIN = 5.0;
    private const REJOIN_RETRY_MAX = 60.0;
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

    /** @var array<int, AbstractGroupCallParticipant> Participants indexed by their bot API peer ID. */
    private array $participants = [];
    /** @var array<int, int> Signed source ID => bot API peer ID. */
    private array $sourceToPeer = [];

    /** Our own audio source ID, in the signed form used by the API. */
    private int $source = 0;
    private bool $streamMode = false;
    private bool $rtmpMode = false;
    /** Downloads and records the call's media chunks in stream mode. */
    private ?StreamReceiver $streamReceiver = null;
    /** Publishes what we play to the RTMP livestream, see {@see self::beforePlay()}. */
    private ?RtmpPublisher $rtmpPublisher = null;
    /** Whether we publish what we play to the RTMP livestream (as one of its admins). */
    private bool $publishing = false;
    /** What the WebRTC connection of a join of an RTMP livestream transmits: nothing, see {@see self::connectionDj()}. */
    private ?DjLoop $silentDj = null;
    /** Whether the SFU transport parameters of the current join were already applied. */
    private bool $connectionParamsApplied = false;

    private ?string $checkWatcher = null;
    /** Whether a {@see self::checkJoined()} is running. */
    private bool $checking = false;
    /**
     * Whether we were dropped from the call and could not rejoin it yet (e.g. the request timed out):
     * the rejoin is retried (see {@see self::rejoin()}), also after a restart of the process.
     */
    private bool $rejoining = false;
    /** How many rejoins failed in a row. */
    private int $rejoinFailures = 0;
    /** The timer of the next rejoin attempt. */
    private ?string $rejoinWatcher = null;
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
        // What is played to an RTMP livestream is published as an RTMP encoder would: H.264 and AAC only.
        $this->diskJockey = new DjLoop($this, rtmp: (bool) ($call['rtmp_stream'] ?? false));
        Assert::true($this->diskJockey->start());
        $this->joinMutex = new LocalMutex;
    }

    /** Whether the call was restored from a session, and is yet to be resumed by {@see self::resumeAfterRestart()}. */
    private bool $resumePending = false;

    /**
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $result = get_object_vars($this);
        // The WebRTC connection now serializes itself and resumes its SFU transport on wakeup, so it
        // is kept. The mutex and the two event-loop watcher IDs cannot survive and are recreated.
        unset($result['joinMutex'], $result['checkWatcher'], $result['checking'], $result['gapWatcher'], $result['rejoinWatcher'], $result['streamReceiver'], $result['rtmpPublisher'], $result['resumePending']);
        return $result;
    }

    public function __unserialize(array $data): void
    {
        $this->joinMutex = new LocalMutex;
        $this->checkWatcher = null;
        $this->checking = false;
        $this->gapWatcher = null;
        $this->rejoinWatcher = null;
        $this->streamReceiver = null;
        $this->rtmpPublisher = null;
        $this->restoreDeclaredProperties($data);
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->diskJockey ??= new DjLoop($this);
        // Resumed by MTProto::wakeup() once the MTProto session is reset and connected, see resumeAfterRestart().
        $this->resumePending = true;
        if (!$this->isInUse()) {
            // Dropped by MTProto::wakeup() (see forgetIfUnjoined()): start nothing.
            return;
        }
        Assert::true($this->diskJockey->start());
        if ($this->presentationDj !== null) {
            Assert::true($this->presentationDj->start());
        }
        if ($this->silentDj !== null) {
            Assert::true($this->silentDj->start());
        }
    }

    /**
     * Whether we are in the call (or joining it, or trying to rejoin it), or publishing to its RTMP
     * livestream: the only group calls a session keeps across restarts.
     *
     * @psalm-mutation-free
     */
    private function isInUse(): bool
    {
        return $this->callState === GroupCallState::JOINED
            || $this->callState === GroupCallState::JOINING
            || $this->rejoining
            || $this->publishing;
    }

    /**
     * Stop tracking the call if we are not in it, without making any request, see
     * {@see \danog\MadelineProto\GroupCall\Handler::dropUnjoinedGroupCalls()}.
     *
     * @internal
     *
     * @return bool Whether the call is no longer tracked.
     */
    public function forgetIfUnjoined(): bool
    {
        if ($this->isInUse()) {
            return false;
        }
        $this->log("Not resuming $this after a restart of the process: we were not in it (state {$this->callState->name}).", Logger::NOTICE);
        $this->cleanup();
        $this->API->cleanupGroupCall($this->public->id);
        return true;
    }

    /**
     * Resume the call after the process restarted.
     *
     * Called by {@see MTProto::wakeup()} once the MTProto session is reset and connected, never from
     * __unserialize(): resuming makes requests, and those sent before the session is reset are lost.
     *
     * @internal
     */
    public function resumeAfterRestart(): void
    {
        if (!$this->resumePending) {
            return;
        }
        $this->resumePending = false;
        if ($this->publishing) {
            // Publishing does not depend on being joined (as a viewer).
            $this->log("Resumed publishing to $this after a restart of the process.");
            $this->rtmpPublisher = $this->createPublisher(null);
            EventLoop::queue(function (): void {
                $this->awaitPublisher();
                $this->diskJockey->resumeReader();
            });
        }
        if ($this->callState === GroupCallState::JOINING) {
            // The process died in the middle of join(): its phone.joinGroupCall and the payload it
            // carried are gone with it, and join() itself is a no-op while JOINING, so start over.
            $this->log("Resumed $this in the middle of a join, joining again...", Logger::WARNING);
            // What was played resumes with the new join (see below for why the reader is restarted).
            $this->resumeDiskJockey();
            $this->rejoin();
            return;
        }
        if ($this->rejoining) {
            $this->log("Resumed $this, which we were trying to rejoin, rejoining...", Logger::WARNING);
            $this->resumeDiskJockey();
            $this->rejoin();
            return;
        }
        if ($this->callState !== GroupCallState::JOINED) {
            return;
        }
        // The WebRTC connection restored itself and its transport to the SFU resumes on its own;
        // now that the whole graph is back, resume the recorders (reopen their files, re-subscribe
        // to their live tracks) and re-poll the participant list so received sources are in sync.
        $this->log("Resumed $this after a restart of the process.");
        $this->startChecking();
        // Restart the demuxers/readers too (DjLoop::__unserialize() deliberately leaves them dormant),
        // or nothing is transmitted after a restart.
        $this->resumeDiskJockey();
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
     * @param string|int|null $joinAs   Peer to join as, only for video chats/livestreams.
     * @param string|null $inviteHash Invite hash from a video chat invite link, if any.
     */
    public function join(bool $muted = false, string|int|null $joinAs = null, ?string $inviteHash = null): self
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
            $this->rejoining = false;
            $this->rejoinFailures = 0;
            $this->cancelRejoin();
            $this->log("Joined $this!", Logger::NOTICE);
            $this->startChecking();
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
        // The stream key of an RTMP livestream lets anyone publish to it: keep it out of the logs.
        $this->log("Join response of $this: ".json_encode(isset($params['rtmp_stream_key']) ? ['rtmp_stream_key' => '<redacted>'] + $params : $params), Logger::VERBOSE);
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
    public function getParticipant(string|int $participant): ?AbstractGroupCallParticipant
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
        /** @var array<int, AbstractGroupCallParticipant> $participants */
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
        $parsed = $this->public instanceof LiveStory
            ? LiveStoryParticipant::fromRaw($participant, $peerId, $this->participants[$peerId] ?? null)
            : GroupCallParticipant::fromRaw($participant, $peerId, $this->participants[$peerId] ?? null);
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
        if ($this->callState !== GroupCallState::JOINED) {
            return;
        }
        $this->log("The WebRTC connection of $this failed, checking whether we are still joined...", Logger::WARNING);
        EventLoop::queue($this->checkJoined(...));
    }

    /**
     * Check whether we're still in the call every {@see self::CHECK_INTERVAL} seconds, while joined.
     */
    private function startChecking(): void
    {
        if ($this->checkWatcher === null) {
            $this->checkWatcher = EventLoop::repeat(self::CHECK_INTERVAL, $this->checkJoined(...));
        }
    }

    /**
     * Check whether we're still in the call, like TDLib: rejoin it if the server dropped us, leave it if it
     * ended or we may not be in it anymore (we may have missed its groupCallDiscarded, e.g. if it ended while
     * the process was down), and just try again later on other errors.
     */
    private function checkJoined(): void
    {
        if ($this->callState !== GroupCallState::JOINED) {
            $this->stopChecking();
            return;
        }
        if ($this->checking) {
            return;
        }
        $this->checking = true;
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
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'GROUPCALL_FORBIDDEN' || $e->rpc === 'GROUPCALL_INVALID') {
                $this->onEnded($e->rpc);
                return;
            }
            if ($e->rpc !== 'GROUPCALL_JOIN_MISSING') {
                $this->log("Could not check whether we are still in $this: $e", Logger::WARNING);
                return;
            }
            // The server dropped us, like when it leaves our source out of the result.
            $alive = [];
        } catch (Throwable $e) {
            $this->log("Could not check whether we are still in $this: $e", Logger::WARNING);
            return;
        } finally {
            $this->checking = false;
        }
        if ($this->callState !== GroupCallState::JOINED) {
            // Left, or rejoined, while checking.
            return;
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
        $this->log("We were dropped from $this, rejoining...", Logger::WARNING);
        $this->rejoin();
    }

    /**
     * The call ended, or we may not be in it anymore (GROUPCALL_FORBIDDEN or GROUPCALL_INVALID): leave it, as
     * TDLib does.
     */
    private function onEnded(string $rpc): void
    {
        $this->log("$this ended, or we may not be in it anymore ($rpc): leaving it.", Logger::NOTICE);
        $this->cleanup();
    }

    /**
     * Join again from scratch with a new payload, keeping the playlists.
     *
     * A rejoin that fails for a reason that may not last (a timeout, a network or server error, a flood
     * wait) is retried with a backoff until it succeeds, or the call ends or is left: giving up would
     * leave us out of the call for good, tracked but not joined, and forgotten on the next restart. Other
     * errors (we may not join the call anymore, e.g. we were banned) leave it.
     *
     * @internal
     */
    public function rejoin(): void
    {
        $this->cancelRejoin();
        if ($this->callState === GroupCallState::LEFT) {
            // Left, or ended, while the rejoin was queued.
            return;
        }
        $this->stopChecking();
        $muted = $this->muted;
        $this->dropPresentation();
        // Recordings in progress carry on with the connection of the new join.
        $this->detachConnection();
        $this->callState = GroupCallState::NOT_JOINED;
        $this->rejoining = true;
        try {
            $this->join($muted);
            return;
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'GROUPCALL_FORBIDDEN' || $e->rpc === 'GROUPCALL_INVALID') {
                $this->onEnded($e->rpc);
                return;
            }
            $error = $e;
            if ($e->getCode() >= 400 && $e->getCode() < 500 && $e->getCode() !== 420) {
                $this->log("Could not rejoin $this, leaving it: $e", Logger::ERROR);
                $this->cleanup();
                return;
            }
        } catch (Throwable $e) {
            $error = $e;
        }
        if ($this->callState !== GroupCallState::NOT_JOINED || !$this->rejoining) {
            // Joined or left meanwhile.
            return;
        }
        $delay = min(self::REJOIN_RETRY_MAX, self::REJOIN_RETRY_MIN * 2 ** $this->rejoinFailures);
        $this->rejoinFailures++;
        $this->log("Could not rejoin $this (attempt {$this->rejoinFailures}), retrying in $delay seconds: $error", Logger::ERROR);
        $this->rejoinWatcher = EventLoop::delay($delay, $this->retryRejoin(...));
    }

    private function retryRejoin(): void
    {
        $this->rejoinWatcher = null;
        if ($this->rejoining && $this->callState === GroupCallState::NOT_JOINED) {
            $this->rejoin();
        }
    }

    private function cancelRejoin(): void
    {
        if ($this->rejoinWatcher !== null) {
            EventLoop::cancel($this->rejoinWatcher);
            $this->rejoinWatcher = null;
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
        if ($this->callState === GroupCallState::LEFT) {
            return $this;
        }
        if ($this->callState === GroupCallState::NOT_JOINED) {
            $this->log("Leaving $this, which we were not in".($this->rejoining ? ' (we were trying to rejoin it)' : '').self::requestedBy(), Logger::NOTICE);
            $this->cleanup();
            return $this;
        }
        $this->log("Leaving $this".self::requestedBy(), Logger::NOTICE);
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
        $this->log("Ending $this for everyone".self::requestedBy(), Logger::NOTICE);
        $this->cleanup();
        try {
            $this->API->methodCallAsyncRead('phone.discardGroupCall', ['call' => $this->inputCall]);
        } catch (Throwable $e) {
            $this->log("Could not discard $this: $e", Logger::WARNING);
        }
        return $this;
    }

    /**
     * Where a leave or discard was requested from, for the logs: the first caller outside MadelineProto.
     */
    private static function requestedBy(): string
    {
        $src = \dirname(__DIR__);
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 16) as $frame) {
            if (isset($frame['file']) && !str_starts_with($frame['file'], $src)) {
                return ", as requested by {$frame['file']}:".($frame['line'] ?? '?');
            }
        }
        return '';
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
        $this->rejoining = false;
        $this->cancelRejoin();
        $this->stopChecking();
        $this->cancelGapRefetch();
        $this->diskJockey->discard();
        $this->silentDj?->discard();
        $this->dropPresentation();
        $this->closeConnection();
        $this->streamReceiver?->stop();
        $this->streamReceiver = null;
        $this->rtmpPublisher?->stop();
        $this->rtmpPublisher = null;
        $this->publishing = false;
        $this->API->cleanupGroupCall($this->public->id);
    }

    /**
     * The disk jockey whose playback the WebRTC connection transmits. Joining an RTMP livestream
     * switches to stream mode, where the connection is closed, but its tracks must never take the
     * frames of the RTMP publisher meanwhile: they get a disk jockey that plays nothing.
     */
    private function connectionDj(): DjLoop
    {
        if (!($this->call['rtmp_stream'] ?? false)) {
            return $this->diskJockey;
        }
        if ($this->silentDj === null) {
            $this->silentDj = new DjLoop($this);
            Assert::true($this->silentDj->start());
        }
        return $this->silentDj;
    }

    /**
     * What is played to an RTMP livestream is published to it, as an RTMP encoder would: to the URL
     * and with the key of [phone.getGroupCallStreamRtmpUrl](https://core.telegram.org/method/phone.getGroupCallStreamRtmpUrl),
     * which only the admins of the group or channel can get. Joining the livestream only watches it.
     */
    private function beforePlay(): void
    {
        if (!($this->call['rtmp_stream'] ?? false)) {
            return;
        }
        if ($this->publishing) {
            // Reconnect right away, if the ingest dropped the connection while nothing was played.
            $this->rtmpPublisher?->wake();
            $this->awaitPublisher();
            return;
        }
        if ($this->callState === GroupCallState::LEFT) {
            // Nothing would stop the publisher.
            throw new Exception("Cannot publish to $this: it was left");
        }
        if ($this->public->peerId === null) {
            throw new Exception("Cannot publish to $this: unknown group or channel");
        }
        // Set first: a concurrent play() must not start a second publisher.
        $this->publishing = true;
        try {
            $ingest = $this->API->getGroupCallStreamRtmpUrl($this->public->peerId, false, $this->public instanceof LiveStory);
        } catch (RPCErrorException $e) {
            $this->publishing = false;
            throw new Exception("Cannot publish to the RTMP livestream $this ({$e->rpc}): only the admins of a group or channel can", 0, $e);
        }
        if ($this->publishing && $this->rtmpPublisher === null) {
            $this->log("Publishing what is played to the RTMP livestream $this", Logger::NOTICE);
            $this->rtmpPublisher = $this->createPublisher($ingest);
        }
        $this->awaitPublisher();
    }

    /**
     * Restart reading what is played, after a restart: when publishing it, only once connected to the RTMP ingest,
     * see {@see self::resumeAfterRestart()}.
     */
    private function resumeDiskJockey(): void
    {
        if (!$this->publishing) {
            $this->diskJockey->resumeReader();
        }
    }

    /** How long play() waits for the connection to the RTMP ingest, in seconds. */
    private const PUBLISHER_CONNECTION_TIMEOUT = 10;

    /**
     * Wait for the connection to the RTMP ingest: what is played before is dropped, with it the first keyframe,
     * and the video until the next one.
     */
    private function awaitPublisher(): void
    {
        try {
            $this->rtmpPublisher?->awaitConnection(new TimeoutCancellation(self::PUBLISHER_CONNECTION_TIMEOUT));
        } catch (CancelledException) {
            $this->log("Not connected to the RTMP ingest of $this after ".self::PUBLISHER_CONNECTION_TIMEOUT." seconds: playing anyway, published once connected", Logger::WARNING);
        }
    }

    /**
     * @param ?array{url: string, key: string} $ingest Where to publish the first time, if already known.
     */
    private function createPublisher(?array $ingest): RtmpPublisher
    {
        $peer = $this->public->peerId;
        \assert($peer !== null);
        $liveStory = $this->public instanceof LiveStory;
        $publisher = null;
        $publisher = new RtmpPublisher($this, $this->diskJockey, function (bool $revoke) use (&$ingest, $peer, $liveStory): array {
            if ($ingest !== null && !$revoke) {
                $first = $ingest;
                $ingest = null;
                return $first;
            }
            return $this->API->getGroupCallStreamRtmpUrl($peer, $revoke, $liveStory);
        }, function () use (&$publisher): void {
            // The key was refused, or can't be fetched anymore: the next play() starts over, and
            // reports why it can't publish (e.g. the admin rights were lost).
            if ($this->rtmpPublisher === $publisher) {
                $this->rtmpPublisher = null;
                $this->publishing = false;
            }
        });
        return $publisher;
    }

    /**
     * Whether we publish what we play to the RTMP livestream (as one of its admins).
     */
    public function isRtmpPublisher(): bool
    {
        return $this->publishing;
    }

    /**
     * An RTMP livestream can't carry a screen share.
     */
    private function assertCanPresent(): void
    {
        if ($this->call['rtmp_stream'] ?? false) {
            throw new Exception("$this is an RTMP livestream, which has no screen sharing: play the screen as the video of its files, instead");
        }
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
     * @param ?StreamMask $streams The {@see CallStream} flags to record, or null for every available one.
     *
     * @return int The streams currently sent, as {@see CallStream} flags.
     */
    public function setOutput(LocalFile|WritableStream $file, string|int|null $participant = null, ?RecordingFormat $format = null, ?int $streams = null): int
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
    public function setOutputFolder(LocalDirectory $dir, string|int|null $participant = null, ?RecordingFormat $format = null): self
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
    private function participantOf(int $peerId): ?AbstractGroupCallParticipant
    {
        return $this->participants[$peerId] ?? null;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function isOurself(int $peerId, AbstractGroupCallParticipant $participant): bool
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
     * @return array<int, AbstractGroupCallParticipant>
     */
    public function getParticipants(): array
    {
        return $this->participants;
    }

    /**
     * Invite users to the call.
     */
    public function invite(string|int ...$users): void
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
    public function editParticipant(string|int|array $participant, ?bool $muted = null, ?int $volume = null, ?bool $raiseHand = null, ?bool $videoStopped = null, ?bool $videoPaused = null, ?bool $presentationPaused = null): void
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
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, string|int|null $sendAs = null): void
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
    private function sendTextWithEntities(string $text, array $entities, ?int $paidStars, string|int|null $sendAs): void
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
    public function deleteParticipantMessages(string|int $participant, bool $reportSpam = false): void
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
        foreach ($result['top_donors'] as $donor) {
            \assert(\is_array($donor));
            $donors[] = new GroupCallDonor(
                isset($donor['peer_id']) ? $this->API->getIdInternal($donor['peer_id']) : null,
                $donor['stars'],
                $donor['top'],
                $donor['my'],
            );
        }
        return new GroupCallStars($result['total_stars'], $donors);
    }

    /**
     * The peer we send in-call messages of this live story as by default (phone.saveDefaultSendAs).
     */
    public function saveDefaultSendAs(string|int $peer): void
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
    public function removeParticipant(string|int ...$participants): void
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

    #[\Override]
    public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
    {
        return $this->API->getSettings()->getVoip();
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
