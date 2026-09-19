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

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\DeferredFuture;
use Amp\Sync\LocalMutex;
use danog\MadelineProto\GroupCall\GroupCallState;
use danog\MadelineProto\GroupCall\Participant;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Tgcalls\CallInterface;
use danog\MadelineProto\Tgcalls\GroupConnection;
use danog\MadelineProto\Tgcalls\GroupSdp;
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
final class GroupCallController implements CallInterface
{
    /** How often [phone.checkGroupCall](https://core.telegram.org/method/phone.checkGroupCall) is polled while reconnecting. */
    private const CHECK_INTERVAL = 4.0;
    /** How long we wait for a missing `version` before refetching the whole call. */
    private const VERSION_GAP_TIMEOUT = 1.0;

    private GroupCallState $callState = GroupCallState::NOT_JOINED;

    /** The [inputGroupCall](https://core.telegram.org/constructor/inputGroupCall) of this call. */
    private array $inputCall;
    /** The last known [groupCall](https://core.telegram.org/constructor/groupCall). */
    private array $call;

    private ?GroupConnection $connection = null;
    private DjLoop $diskJockey;
    private LocalMutex $joinMutex;

    /** @var array<int, Participant> Participants indexed by their bot API peer ID. */
    private array $participants = [];
    /** @var array<int, int> Signed source ID => bot API peer ID. */
    private array $sourceToPeer = [];

    /** Our own audio source ID, in the signed form used by the API. */
    private int $source = 0;
    private bool $muted = false;
    private bool $streamMode = false;
    private bool $rtmpMode = false;
    /** Whether the SFU transport parameters of the current join were already applied. */
    private bool $connectionParamsApplied = false;

    private ?string $checkWatcher = null;
    private ?string $gapWatcher = null;

    /** Output files/streams requested per participant peer ID. */
    private array $pendingOutputs = [];
    /** Presentation (screen-share) output files/streams requested per participant peer ID. */
    private array $pendingPresentationOutputs = [];
    /** Directory into which every transmitting participant is recorded, or null if not in folder mode. */
    private ?string $outputDir = null;
    /** @var array<int, true> Peer IDs already wired to a per-participant file in folder mode. */
    private array $folderPeers = [];
    /** @var array<int, true> Peer IDs already wired to a presentation file in folder mode. */
    private array $folderPresentationPeers = [];

    public readonly GroupCall $public;

    /**
     * @internal
     */
    public function __construct(
        public readonly MTProto $API,
        array $call,
        ?int $peerId = null,
    ) {
        $this->call = $call;
        $this->inputCall = [
            '_' => 'inputGroupCall',
            'id' => $call['id'],
            'access_hash' => $call['access_hash'],
        ];
        $this->public = new GroupCall($API, $call, $peerId);
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
        unset($result['joinMutex'], $result['checkWatcher'], $result['gapWatcher']);
        return $result;
    }

    public function __unserialize(array $data): void
    {
        $this->joinMutex = new LocalMutex;
        $this->checkWatcher = null;
        $this->gapWatcher = null;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->diskJockey ??= new DjLoop($this);
        Assert::true($this->diskJockey->start());
        EventLoop::queue(function (): void {
            if ($this->callState !== GroupCallState::JOINED && $this->callState !== GroupCallState::JOINING) {
                return;
            }
            // The WebRTC connection restored itself and its transport to the SFU resumes on its own;
            // now that the whole graph is back, resume the recorders (reopen their files, re-subscribe
            // to their live tracks) and re-poll the participant list so received sources are in sync.
            $this->log("Resumed $this after a restart of the process.");
            $this->connection?->resume();
            EventLoop::queue($this->refetch(...));
        });
    }

    /**
     * The [inputGroupCall](https://core.telegram.org/constructor/inputGroupCall) of this call.
     *
     * @internal
     */
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
                $this->connection?->close();
                $this->connection = new GroupConnection($this, $this->diskJockey);
                $params = $this->connection->buildJoinPayload();
                $this->source = $this->connection->getAudioSource();
                $this->log("Join payload of $this: ".json_encode($params), Logger::VERBOSE);

                $request = [
                    'call' => $this->inputCall,
                    'join_as' => $joinAs ?? ['_' => 'inputPeerSelf'],
                    'muted' => $muted,
                    'video_stopped' => true,
                    // DataJSON arguments are encoded by the TL serializer, pass the decoded payload.
                    'params' => $params,
                ];
                if ($inviteHash !== null) {
                    $request['invite_hash'] = $inviteHash;
                }
                try {
                    $updates = $this->API->methodCallAsyncRead('phone.joinGroupCall', $request);
                } catch (RPCErrorException $e) {
                    if ($e->rpc !== 'GROUPCALL_SSRC_DUPLICATE_MUCH') {
                        throw $e;
                    }
                    // The server asks us to retry with a fresh SSRC.
                    $this->log("Retrying to join $this with a new SSRC...", Logger::WARNING);
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
                "$this is in ".($parsed['rtmp'] ? 'RTMP' : 'stream')." mode, WebRTC playback is not available.",
                Logger::WARNING
            );
            $this->connection?->close();
            $this->connection = null;
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
                'limit' => 100,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not refetch $this: $e", Logger::WARNING);
            return;
        }
        $this->applyGroupCall($result);
    }

    /**
     * Apply a [phone.groupCall](https://core.telegram.org/constructor/phone.groupCall).
     *
     * @internal
     */
    public function applyGroupCall(array $result): void
    {
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
            return;
        }
        $parsed = Participant::fromRaw($participant, $peerId, $this->participants[$peerId] ?? null);
        $this->participants[$peerId] = $parsed;
        if ($parsed->source !== 0) {
            $this->sourceToPeer[$parsed->source] = $peerId;
            if (isset($this->pendingOutputs[$peerId])) {
                $file = $this->pendingOutputs[$peerId];
                unset($this->pendingOutputs[$peerId]);
                $this->connection?->setOutput($parsed->source, $file);
            }
            if (isset($this->pendingPresentationOutputs[$peerId])) {
                $file = $this->pendingPresentationOutputs[$peerId];
                unset($this->pendingPresentationOutputs[$peerId]);
                $this->connection?->setPresentationOutput($parsed->source, $file);
            }
            // Folder mode: start recording a participant that has just begun transmitting.
            $this->wireFolderOutput($peerId, $parsed);
            $this->wireFolderPresentationOutput($peerId, $parsed);
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
                    'video' => $participant->videoSources,
                    'presentation' => $participant->presentationSources,
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
            try {
                $alive = $this->API->methodCallAsyncRead('phone.checkGroupCall', [
                    'call' => $this->inputCall,
                    'sources' => [$this->source],
                ]);
            } catch (Throwable) {
                $alive = [];
            }
            if (\in_array($this->source, $alive, true)) {
                return;
            }
            $this->stopChecking();
            $this->log("We were dropped from $this, rejoining...", Logger::WARNING);
            $muted = $this->muted;
            $this->connection?->close();
            $this->connection = null;
            $this->callState = GroupCallState::NOT_JOINED;
            try {
                $this->join($muted);
            } catch (Throwable $e) {
                $this->log("Could not rejoin $this: $e", Logger::ERROR);
            }
        });
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
        $this->connection?->close();
        $this->connection = null;
        $this->API->cleanupGroupCall($this->public->id);
    }

    /**
     * Mute or unmute ourselves.
     */
    public function setMuted(bool $muted): self
    {
        $this->muted = $muted;
        if ($muted) {
            $this->diskJockey->pausePlaying();
        } else {
            $this->diskJockey->resumePlaying();
        }
        if ($this->callState === GroupCallState::JOINED) {
            try {
                $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', [
                    'call' => $this->inputCall,
                    'participant' => ['_' => 'inputPeerSelf'],
                    'muted' => $muted,
                ]);
            } catch (Throwable $e) {
                $this->log("Could not change the mute state of $this: $e", Logger::WARNING);
            }
        }
        return $this;
    }

    public function isMuted(): bool
    {
        return $this->muted;
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
     * Record group call media.
     *
     * Two modes:
     *  - per participant: `setOutput($participant, $file)` records that one participant's incoming
     *    audio and (if they transmit a camera) video into the given file or stream.
     *  - folder (all participants): `setOutput(new LocalDirectory($dir))` records every *transmitting*
     *    participant into its own `<dir>/<peerId>.mkv` Matroska file, including participants that start
     *    transmitting later. Each file holds the participant's audio and, once they turn a camera on,
     *    their video. Our own media is never recorded.
     */
    public function setOutput(mixed $participant, LocalFile|WritableStream|null $file = null): self
    {
        if ($participant instanceof LocalDirectory) {
            $dir = $participant->dir;
            if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException("Could not create the recording directory $dir");
            }
            $this->outputDir = $dir;
            foreach ($this->participants as $peerId => $known) {
                $this->wireFolderOutput($peerId, $known);
                $this->wireFolderPresentationOutput($peerId, $known);
            }
            return $this;
        }
        if ($file === null) {
            throw new \InvalidArgumentException('setOutput() requires a file/stream to record a participant into, or a LocalDirectory to record every participant.');
        }
        $this->wireOutput($this->API->getId($participant), $file);
        return $this;
    }

    /**
     * Route one participant's output to the connection now, or defer it until their source is known.
     */
    private function wireOutput(int $peerId, LocalFile|WritableStream $file): void
    {
        $known = $this->participants[$peerId] ?? null;
        if ($known !== null && $known->source !== 0 && $this->connection !== null) {
            $this->connection->setOutput($known->source, $file);
            return;
        }
        $this->pendingOutputs[$peerId] = $file;
    }

    /**
     * In folder mode, give a transmitting (non-self) participant its own `<dir>/<peerId>.mkv` file,
     * once each.
     */
    private function wireFolderOutput(int $peerId, Participant $participant): void
    {
        if ($this->outputDir === null
            || $participant->source === 0
            || $participant->self
            || $participant->source === $this->source
            || isset($this->folderPeers[$peerId])
            || isset($this->pendingOutputs[$peerId]) // an explicit per-participant output takes precedence
        ) {
            return;
        }
        $this->folderPeers[$peerId] = true;
        $this->wireOutput($peerId, new LocalFile($this->outputDir.'/'.$peerId.'.mkv'));
    }

    /**
     * In folder mode, give a participant that is screen-sharing its own `<dir>/<peerId>.presentation.mkv`
     * file, once each. Called whenever a participant's presentation state changes.
     */
    private function wireFolderPresentationOutput(int $peerId, Participant $participant): void
    {
        if ($this->outputDir === null
            || $participant->source === 0
            || $participant->self
            || $participant->source === $this->source
            || $participant->presentationSources === []
            || isset($this->folderPresentationPeers[$peerId])
            || isset($this->pendingPresentationOutputs[$peerId]) // an explicit output takes precedence
        ) {
            return;
        }
        $this->folderPresentationPeers[$peerId] = true;
        $file = new LocalFile($this->outputDir.'/'.$peerId.'.presentation.mkv');
        if ($this->connection !== null) {
            $this->connection->setPresentationOutput($participant->source, $file);
        } else {
            $this->pendingPresentationOutputs[$peerId] = $file;
        }
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
     * Change the title of the call.
     */
    public function setTitle(string $title): void
    {
        $this->API->methodCallAsyncRead('phone.editGroupCallTitle', [
            'call' => $this->inputCall,
            'title' => $title,
        ]);
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
     * Our own audio source ID, in the signed form used by the API.
     */
    public function getSource(): int
    {
        return $this->source;
    }

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
     * Group-call presentation (screen-share) is not wired up yet — it needs the separate
     * presentation connection (phone.joinGroupCallPresentation), which lands with full group video.
     */
    private static function requireCamera(MediaDestination $dest): void
    {
        if ($dest !== MediaDestination::Camera) {
            throw new \RuntimeException('Group call presentation (screen-share) is not supported yet; it lands with full group video support.');
        }
    }

    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        self::requireCamera($dest);
        self::validateCallAudio($file);
        $this->diskJockey->play($file);
    }
    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->play($file, $dest);
        self::awaitStream($file);
    }
    /**
     * Tell the server whether we are currently publishing video, so that the other participants
     * know they should display our video stream.
     *
     * Driven by the {@see \danog\MadelineProto\Loop\VoIP\DjLoop} through the WebRTC engine as the
     * demuxed file's video starts ({@see GroupConnection::onVideoCodec()}) and stops
     * ({@see GroupConnection::onVideoStopped()}).
     *
     * @internal
     */
    public function setVideoStopped(bool $stopped): void
    {
        if ($this->callState !== GroupCallState::JOINED) {
            return;
        }
        try {
            $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', [
                'call' => $this->inputCall,
                'participant' => ['_' => 'inputPeerSelf'],
                'video_stopped' => $stopped,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not change the video state of $this: $e", Logger::WARNING);
        }
    }
    public function skip(MediaDestination $dest = MediaDestination::Camera): void
    {
        self::requireCamera($dest);
        $this->diskJockey->skip();
    }
    public function stop(MediaDestination $dest = MediaDestination::Camera): void
    {
        self::requireCamera($dest);
        $this->diskJockey->stopPlaying();
    }
    public function pause(MediaDestination $dest = MediaDestination::Camera): void
    {
        self::requireCamera($dest);
        $this->diskJockey->pausePlaying();
    }
    public function resume(MediaDestination $dest = MediaDestination::Camera): void
    {
        self::requireCamera($dest);
        $this->diskJockey->resumePlaying();
    }
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        self::requireCamera($dest);
        return $this->diskJockey->isAudioPaused();
    }
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        self::requireCamera($dest);
        foreach ($files as $file) {
            self::validateCallAudio($file);
        }
        $this->diskJockey->playOnHold(...$files);
    }
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): LocalFile|RemoteUrl|string|null
    {
        self::requireCamera($dest);
        return $this->diskJockey->getCurrent();
    }

    /**
     * Reject audio that was not encoded as OGG OPUS, unless realtime conversion is available.
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
        throw new \AssertionError('The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!');
    }

    /**
     * Block until a played stream has finished; a no-op for files and URLs.
     */
    private static function awaitStream(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (!$file instanceof ReadableStream) {
            return;
        }
        $deferred = new DeferredFuture;
        $file->onClose($deferred->complete(...));
        $deferred->getFuture()->await();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->public->__toString();
    }
}
