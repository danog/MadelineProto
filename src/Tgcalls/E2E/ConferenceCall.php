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

namespace danog\MadelineProto\Tgcalls\E2E;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\DeferredFuture;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\ConferenceCall as ConferenceCallUpdate;
use danog\MadelineProto\EventHandler\MultiCall;
use danog\MadelineProto\GroupCall\GroupCallState;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Tgcalls\GroupConnection;
use danog\MadelineProto\Tgcalls\GroupConnectionOwner;
use danog\MadelineProto\Tgcalls\GroupSdp;
use Revolt\EventLoop;
use Throwable;

/**
 * Controller for a Telegram end-to-end encrypted conference call
 * ([end-to-end group calls](https://core.telegram.org/api/end-to-end/group-calls)).
 *
 * It owns the two block subchains ({@see ConferenceChain} + {@see Verification}), turns the accepted
 * blocks into media-encryption epochs, and drives a {@see GroupConnection} whose RTP frames are
 * end-to-end encrypted by a {@see FrameCryptor} — the SFU only ever forwards ciphertext. It is both
 * the connection's owner and the cryptor's key provider.
 *
 * This is the internal controller; the public handle library users receive from
 * {@see \danog\MadelineProto\MTProto::createConferenceCall()} and
 * {@see \danog\MadelineProto\MTProto::joinConferenceCall()} is the {@see ConferenceCallUpdate}
 * returned by {@see self::getPublic()}, which delegates back here by call id. The controller
 * implements the common {@see Call} media interface plus conference-specific controls (verification,
 * encrypted messages).
 */
final class ConferenceCall implements MultiCall, GroupConnectionOwner, E2EKeyProvider
{
    /** Subchain ids: 0 = shared-state chain, 1 = commit-reveal verification broadcasts. */
    private const SUBCHAIN_STATE = 0;
    private const SUBCHAIN_VERIFICATION = 1;
    /** How many recent epochs to keep so media from just before a rekey still decrypts. */
    private const MAX_EPOCHS = 15;

    /** The inputGroupCall of this conference, set once it exists (after create/join). */
    private ?array $inputCall = null;
    private int $selfId;
    /** Our Ed25519 signing seed: signs both chain blocks and media/message packets. */
    private string $selfSeed;
    private ConferenceChain $chain;
    private ?GroupConnection $connection = null;
    private DjLoop $diskJockey;
    private FrameCryptor $frameCryptor;
    /** The separate screen-share connection (phone.joinGroupCallPresentation), while sharing a screen. */
    private ?GroupConnection $presentationConnection = null;
    /** The video-only disk jockey feeding the screen-share connection, if any. */
    private ?DjLoop $presentationDj = null;
    /** Frame cryptor for the screen-share, on its own channel so its seqnos don't collide with camera. */
    private ?FrameCryptor $presentationCryptor = null;

    /**
     * Active media epochs, `blockHash => ['hash' => string, 'secret' => string]`, oldest first.
     *
     * @var array<string, array{hash: string, secret: string}>
     */
    private array $epochs = [];
    /**
     * Next chain offset to request per subchain (`sub_chain_id => next block height`).
     *
     * @var array{0: int, 1: int}
     */
    private array $chainOffset = [0, 0];
    /** SSRC (unsigned) => participant user id, for verifying incoming media senders. */
    private array $ssrcToUser = [];
    /** user id => signed audio source, learned from the participant list, for recording. */
    private array $userToSource = [];
    /** Per-participant recording outputs requested before the user's media source was known. */
    private array $pendingOutputs = [];
    /** Per-participant presentation recording outputs requested before the user's source was known. */
    private array $pendingPresentationOutputs = [];
    /** Directory into which every transmitting participant is recorded, or null if not in folder mode. */
    private ?string $outputDir = null;
    /** @var array<int, true> User ids already wired to a per-participant file in folder mode. */
    private array $folderPeers = [];
    /** @var array<int, true> User ids already wired to a presentation file in folder mode. */
    private array $folderPresentationPeers = [];
    /** user id => revealed verification nonce, for computing the emoji hash. */
    private array $verificationNonces = [];
    /** Our own verification nonce for the current chain head, if a verification is in progress. */
    private ?string $ownNonce = null;

    private bool $joined = false;
    /** Event-loop id of the backstop chain poll, so it can be cancelled on leave. */
    private ?string $pollWatcher = null;

    /** The public event-handler handle for this conference, built lazily once the call exists. */
    private ?ConferenceCallUpdate $public = null;

    public function __construct(
        public readonly MTProto $API,
    ) {
        $self = $this->API->getSelf();
        if ($self === false) {
            throw new \RuntimeException('Cannot start a conference call without being logged in.');
        }
        $this->selfId = $self['id'];
        [$this->selfSeed] = Crypto::generateKeyPair();
        $this->chain = new ConferenceChain($this->selfId, $this->selfSeed);
        $this->frameCryptor = new FrameCryptor($this);
        $this->diskJockey = new DjLoop($this);
        $this->diskJockey->start();
    }

    /**
     * Keep everything serializable across a restart: the WebRTC connection, chain, epochs, keys and
     * disk jockey all serialize themselves; only the event-loop poll id cannot and is recreated.
     *
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['pollWatcher']);
        return $vars;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        // Synchronous restoration only — no async work here (that goes in resume()).
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->pollWatcher = null;
        $this->diskJockey->start();
        $this->presentationDj?->start();
        // Bring the rest of the graph back once it is whole, from a single queued task.
        EventLoop::queue($this->resumeAfterRestart(...));
    }

    /**
     * Resume the conference after the process restarted: reopen the media connection, re-register for
     * push updates, restart the backstop poll and catch up on any blocks missed while stopped. Named
     * to avoid clashing with the {@see Call} playback {@see self::resume()}.
     */
    public function resumeAfterRestart(): void
    {
        if (!$this->joined || $this->inputCall === null) {
            return;
        }
        $this->connection?->resume();
        $this->presentationConnection?->resume();
        $this->API->registerConferenceCall($this->inputCall['id'], $this);
        $this->startPolling();
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->syncChain(self::SUBCHAIN_VERIFICATION);
        $this->log("Resumed E2E conference $this after a restart", Logger::NOTICE);
    }

    /**
     * Point this controller at an existing conference (its groupCall), before joining it.
     *
     * @psalm-external-mutation-free
     */
    public function setCall(array $call): void
    {
        $this->inputCall = [
            '_' => 'inputGroupCall',
            'id' => $call['id'],
            'access_hash' => $call['access_hash'],
        ];
    }

    /**
     * @return array The inputGroupCall, once the conference exists.
     *
     * @psalm-mutation-free
     */
    public function getInputCall(): array
    {
        return $this->inputCall ?? throw new \RuntimeException('The conference call does not exist yet.');
    }

    /**
     * The public {@see ConferenceCallUpdate} handle for this conference, built lazily once the call
     * exists. This is the object handed to library users; it delegates every operation back to this
     * controller by call id.
     */
    public function getPublic(): ConferenceCallUpdate
    {
        $call = $this->getInputCall();
        return $this->public ??= new ConferenceCallUpdate($this->API, [
            '_' => 'groupCall',
            'id' => $call['id'],
            'access_hash' => $call['access_hash'],
            'conference' => true,
        ]);
    }

    /** Whether we are currently in the conference (joined and not left/forbidden). */
    public function isJoined(): bool
    {
        return $this->joined;
    }

    /**
     * Get the state of the conference call.
     *
     * @psalm-mutation-free
     */
    public function getCallState(): GroupCallState
    {
        return $this->joined ? GroupCallState::JOINED : GroupCallState::NOT_JOINED;
    }

    /**
     * Whether a screen-share is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    public function isSharingScreen(): bool
    {
        return $this->presentationConnection !== null;
    }

    /* ------------------------------------------------------------------ *
     *  E2EKeyProvider — the live keys the frame cryptor uses.
     * ------------------------------------------------------------------ */

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function activeEpochs(): array
    {
        // Newest first, so the encryptor's primary epoch is the current one.
        return array_reverse(array_values($this->epochs));
    }

    #[\Override]
    public function selfSeed(): string
    {
        return $this->selfSeed;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function publicKeyForSsrc(int $ssrc): ?string
    {
        $userId = $this->ssrcToUser[$ssrc] ?? null;
        if ($userId === null) {
            return null;
        }
        return $this->chain->getParticipants()[$userId]['public_key'] ?? null;
    }

    /* ------------------------------------------------------------------ *
     *  Lifecycle: create / join.
     * ------------------------------------------------------------------ */

    /**
     * Create a brand-new conference call with ourselves as the sole participant and join it. Returns
     * the raw `phone.groupCall` this controller now drives.
     */
    public function create(bool $muted = false): void
    {
        $genesis = $this->chain->buildGenesis();
        $this->connection = new GroupConnection($this, $this->diskJockey);
        $this->connection->setFrameCryptor($this->frameCryptor);
        $params = $this->connection->buildJoinPayload();

        $updates = $this->API->methodCallAsyncRead('phone.createConferenceCall', [
            'muted' => $muted,
            'video_stopped' => true,
            'join' => true,
            'random_id' => random_int(-2 ** 31, 2 ** 31 - 1),
            'public_key' => $this->chain->getSelfPublicKey(),
            'block' => $genesis['serialized'],
            'params' => $params,
        ]);
        $this->extractCall($updates);
        // Our genesis block is authoritative once the server accepted it.
        $this->chain->applyServerBlock($genesis['serialized']);
        $this->refreshEpoch();
        $this->chainOffset = [$this->chain->getHeight() + 1, 0];
        $this->consumeUpdates($updates);
        $this->joined = true;
        $this->API->registerConferenceCall($this->getInputCall()['id'], $this);
        $this->startPolling();
        $this->log("Created and joined E2E conference $this", Logger::NOTICE);
    }

    /**
     * Join an existing conference call: fetch the chain, add ourselves in a new block, and join with
     * that block.
     */
    public function join(bool $muted = false): void
    {
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->connection = new GroupConnection($this, $this->diskJockey);
        $this->connection->setFrameCryptor($this->frameCryptor);
        $params = $this->connection->buildJoinPayload();

        // If another member's block took our height while we were building, rebuild the self-add
        // block on the new head and re-join; the connection/join-payload are reused.
        $updates = $this->submitWithChainRetry(fn (): array => $this->API->methodCallAsyncRead('phone.joinGroupCall', [
            'muted' => $muted,
            'video_stopped' => true,
            'call' => $this->inputCall,
            'join_as' => ['_' => 'inputPeerSelf'],
            'public_key' => $this->chain->getSelfPublicKey(),
            'block' => $this->buildSelfAddBlock()['serialized'],
            'params' => $params,
        ]));
        $this->consumeUpdates($updates);
        $this->joined = true;
        $this->API->registerConferenceCall($this->getInputCall()['id'], $this);
        // Re-sync so our own accepted block (and any concurrent ones) are applied in server order.
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->startPolling();
        $this->log("Joined E2E conference $this", Logger::NOTICE);
    }

    /**
     * Build a block that adds us to the current group, carrying a fresh shared key for the new member
     * set. Requires the chain to already reflect the current members.
     *
     * @return array{block: array<string, mixed>, serialized: string, hash: string}
     */
    private function buildSelfAddBlock(): array
    {
        $participants = [];
        $recipients = [];
        foreach ($this->chain->getParticipants() as $userId => $info) {
            $participants[] = [$userId, $info['public_key'], $info['permissions']];
            $recipients[] = [$userId, $info['public_key']];
        }
        $participants[] = [$this->selfId, $this->chain->getSelfPublicKey(), 0];
        $recipients[] = [$this->selfId, $this->chain->getSelfPublicKey()];

        $raw = random_bytes(32);
        $changes = [
            $this->chain->groupStateChange($participants),
            ['_' => 'e2e.chain.changeSetSharedKey', 'shared_key' => $this->chain->buildSharedKey($raw, $recipients)],
            ['_' => 'e2e.chain.changeNoop', 'nonce' => random_bytes(32)],
        ];
        return $this->chain->buildBlock($this->chain->getHeight() + 1, $this->chain->getLastBlockHash(), $changes);
    }

    /**
     * Remove participants from the conference: build a block dropping them and rekeying for the
     * remaining members, then submit it with phone.deleteConferenceCallParticipants. Requires the
     * `remove_users` permission. The removed members can no longer decrypt media once the new epoch
     * takes over.
     */
    public function removeParticipant(int ...$userIds): void
    {
        $remove = array_flip($userIds);
        $updates = $this->submitWithChainRetry(function () use ($remove, $userIds): array {
            // Built from the current chain state each attempt, so a retry rekeys on the fresh head.
            $participants = [];
            $recipients = [];
            foreach ($this->chain->getParticipants() as $userId => $info) {
                if (isset($remove[$userId])) {
                    continue;
                }
                $participants[] = [$userId, $info['public_key'], $info['permissions']];
                $recipients[] = [$userId, $info['public_key']];
            }
            if ($recipients === []) {
                throw new \RuntimeException('Cannot remove every participant from the conference.');
            }
            $raw = random_bytes(32);
            $block = $this->chain->buildBlock($this->chain->getHeight() + 1, $this->chain->getLastBlockHash(), [
                $this->chain->groupStateChange($participants),
                ['_' => 'e2e.chain.changeSetSharedKey', 'shared_key' => $this->chain->buildSharedKey($raw, $recipients)],
                ['_' => 'e2e.chain.changeNoop', 'nonce' => random_bytes(32)],
            ]);
            return $this->API->methodCallAsyncRead('phone.deleteConferenceCallParticipants', [
                'kick' => true,
                'call' => $this->getInputCall(),
                'ids' => $userIds,
                'block' => $block['serialized'],
            ]);
        });
        $this->consumeUpdates($updates);
        // Apply our own accepted block (and anything concurrent) in server order.
        $this->syncChain(self::SUBCHAIN_STATE);
    }

    /**
     * How many times a chain-mutating block is rebuilt on the latest head before giving up.
     */
    private const MAX_CHAIN_RETRIES = 5;

    /**
     * Submit a chain-mutating RPC, rebuilding its block on the latest chain head when the server
     * rejects it as stale (`CONF_WRITE_CHAIN_INVALID`) — a concurrent block took our height — and
     * treating `GROUPCALL_FORBIDDEN` as a reset. `$attempt` must build its block from the *current*
     * chain state and return the RPC's Updates, so a retry naturally rebuilds on the fresh head.
     *
     * @param callable(): array $attempt
     *
     * @return array The RPC's Updates.
     */
    private function submitWithChainRetry(callable $attempt): array
    {
        for ($i = 0; $i < self::MAX_CHAIN_RETRIES; $i++) {
            try {
                return $attempt();
            } catch (RPCErrorException $e) {
                if ($e->rpc === 'CONF_WRITE_CHAIN_INVALID') {
                    $this->log("Block rejected as stale on $this; refetching the chain and rebuilding", Logger::WARNING);
                    $this->syncChain(self::SUBCHAIN_STATE);
                    continue;
                }
                if ($e->rpc === 'GROUPCALL_FORBIDDEN') {
                    $this->handleForbidden();
                }
                throw $e;
            }
        }
        throw new \RuntimeException("The conference chain kept rejecting our block as invalid ($this)");
    }

    /**
     * We were forbidden from the call (removed, or the chain reset): stop participating so we neither
     * poll nor apply further blocks. The caller decides whether to rejoin.
     */
    private function handleForbidden(): void
    {
        $this->log("Forbidden from $this; resetting conference state", Logger::WARNING);
        $this->joined = false;
        if ($this->pollWatcher !== null) {
            EventLoop::cancel($this->pollWatcher);
            $this->pollWatcher = null;
        }
        if ($this->inputCall !== null) {
            $this->API->unregisterConferenceCall($this->inputCall['id']);
        }
        $this->chainOffset = [0, 0];
        $this->connection?->close();
        $this->connection = null;
    }

    /* ------------------------------------------------------------------ *
     *  Chain polling and application.
     * ------------------------------------------------------------------ */

    /**
     * Fetch and apply every block of a subchain from our current offset until caught up.
     */
    public function syncChain(int $subChainId): void
    {
        do {
            try {
                $updates = $this->API->methodCallAsyncRead('phone.getGroupCallChainBlocks', [
                    'call' => $this->inputCall,
                    'sub_chain_id' => $subChainId,
                    'offset' => $this->chainOffset[$subChainId],
                    'limit' => 50,
                ]);
            } catch (RPCErrorException $e) {
                if ($e->rpc === 'GROUPCALL_FORBIDDEN') {
                    $this->handleForbidden();
                    return;
                }
                $this->log("Could not fetch chain $subChainId of $this: $e", Logger::WARNING);
                return;
            }
            $blocks = $this->extractBlocks($updates);
            foreach ($blocks as $block) {
                $this->applyBlock($subChainId, $block);
            }
            $this->consumeUpdates($updates);
        } while (\count($blocks) === 50);
    }

    /**
     * @internal Handle an [updateGroupCallChainBlocks](https://core.telegram.org/constructor/updateGroupCallChainBlocks).
     *
     * @param list<string> $blocks
     */
    public function onChainBlocks(int $subChainId, array $blocks, int $nextOffset): void
    {
        foreach ($blocks as $block) {
            $this->applyBlock($subChainId, $block);
        }
        $this->chainOffset[$subChainId] = max($this->chainOffset[$subChainId], $nextOffset);
    }

    private function applyBlock(int $subChainId, string $serialized): void
    {
        try {
            if ($subChainId === self::SUBCHAIN_STATE) {
                // Idempotent + ordered: the same block may arrive from both the push update and the
                // polling backstop; only a newly applied block advances the epoch and offset.
                if ($this->chain->applyServerBlock($serialized)) {
                    $this->refreshEpoch();
                    $this->refreshVerification();
                    $this->chainOffset[self::SUBCHAIN_STATE] = $this->chain->getHeight() + 1;
                }
            } else {
                $this->applyBroadcast($serialized);
                $this->chainOffset[self::SUBCHAIN_VERIFICATION]++;
            }
        } catch (Throwable $e) {
            $this->log("Could not apply a block on chain $subChainId of $this: $e", Logger::WARNING);
        }
    }

    /**
     * Snapshot the current group key as a media epoch, keeping the most recent ones.
     *
     * @psalm-external-mutation-free
     */
    private function refreshEpoch(): void
    {
        $key = $this->chain->getGroupKey();
        if ($key === null) {
            return;
        }
        $hash = $this->chain->getLastBlockHash();
        $this->epochs[$hash] = ['hash' => $hash, 'secret' => $key];
        while (\count($this->epochs) > self::MAX_EPOCHS) {
            array_shift($this->epochs);
        }
    }

    private function startPolling(): void
    {
        // The primary delivery path is the push updates (updateGroupCallChainBlocks /
        // updateGroupCallEncryptedMessage) routed here by the update dispatcher; this timer is only a
        // backstop that catches anything missed while the update seq was gapped or the process slept.
        $this->pollWatcher = EventLoop::repeat(5.0, function (): void {
            if (!$this->joined) {
                return;
            }
            $this->syncChain(self::SUBCHAIN_STATE);
            $this->syncChain(self::SUBCHAIN_VERIFICATION);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Screen-share: a second, end-to-end encrypted connection.
     * ------------------------------------------------------------------ */

    /**
     * Start sharing a screen: a second WebRTC connection (phone.joinGroupCallPresentation) whose
     * video is end-to-end encrypted with the same conference keys, on its own packet channel so its
     * sequence numbers never collide with the camera's. Idempotent.
     */
    public function enablePresentation(): void
    {
        if ($this->presentationConnection !== null) {
            return;
        }
        if (!$this->joined) {
            throw new \RuntimeException('Cannot share a screen before joining the conference.');
        }
        $this->presentationDj ??= new DjLoop($this, videoOnly: true);
        $this->presentationDj->start();
        // Distinct channels (screen video = 3) so replay windows don't clash with the camera (video = 2).
        $this->presentationCryptor ??= new FrameCryptor($this, audioChannel: 4, videoChannel: 3);
        $connection = new GroupConnection($this, $this->presentationDj, screencast: true);
        $connection->setFrameCryptor($this->presentationCryptor);
        $params = $connection->buildJoinPayload();
        $this->presentationConnection = $connection;
        try {
            $updates = $this->API->methodCallAsyncRead('phone.joinGroupCallPresentation', [
                'call' => $this->getInputCall(),
                'params' => $params,
            ]);
        } catch (Throwable $e) {
            $this->presentationConnection = null;
            $connection->close();
            throw $e;
        }
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCallConnection' && ($update['presentation'] ?? false)) {
                $parsed = GroupSdp::parseJoinResponse($update['params']);
                if ($parsed['transport'] !== null) {
                    $connection->setTransport($parsed['transport'], $parsed['video']);
                }
            }
        }
    }

    /**
     * Stop sharing the screen: tear down the presentation connection and tell the server.
     */
    public function disablePresentation(): void
    {
        if ($this->presentationConnection === null) {
            return;
        }
        $this->presentationConnection->close();
        $this->presentationConnection = null;
        $this->presentationDj?->discard();
        $this->presentationDj = null;
        if ($this->joined) {
            try {
                $this->API->methodCallAsyncRead('phone.leaveGroupCallPresentation', ['call' => $this->getInputCall()]);
            } catch (Throwable $e) {
                $this->log("Could not leave the presentation of $this: $e", Logger::WARNING);
            }
        }
    }

    /**
     * The disk jockey feeding a destination, starting the screen-share on first use of Presentation.
     */
    private function dj(MediaDestination $dest): DjLoop
    {
        if ($dest === MediaDestination::Camera) {
            return $this->diskJockey;
        }
        $this->enablePresentation();
        \assert($this->presentationDj !== null);
        return $this->presentationDj;
    }

    /**
     * The disk jockey for a destination without starting a screen-share that is not running.
     *
     * @psalm-mutation-free
     */
    private function djOrNull(MediaDestination $dest): ?DjLoop
    {
        return $dest === MediaDestination::Camera ? $this->diskJockey : $this->presentationDj;
    }

    /* ------------------------------------------------------------------ *
     *  Media playback — the common {@see Call} interface. Every frame is end-to-end
     *  encrypted (camera and screen-share alike) before it reaches the SFU.
     * ------------------------------------------------------------------ */

    private bool $muted = false;

    #[\Override]
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->dj($dest)->play($file);
        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream is provided.
     */
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->play($file, $dest);
        self::awaitStream($file);
        return $this;
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

    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->dj($dest)->play($file);
        return $this;
    }

    #[\Override]
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): self
    {
        $this->dj($dest)->playOnHold(...$files);
        return $this;
    }

    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->djOrNull($dest)?->skip();
        return $this;
    }

    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): self
    {
        if ($dest === MediaDestination::Presentation) {
            $this->disablePresentation();
            return $this;
        }
        $this->diskJockey->stopPlaying();
        return $this;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function pause(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->djOrNull($dest)?->pausePlaying();
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->djOrNull($dest)?->isAudioPaused() ?? false;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function resume(MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->djOrNull($dest)?->resumePlaying();
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): LocalFile|RemoteUrl|string|null
    {
        return $this->djOrNull($dest)?->getCurrent();
    }

    #[\Override]
    public function setMuted(bool $muted = true): self
    {
        $this->muted = $muted;
        if ($muted) {
            $this->diskJockey->pausePlaying();
        } else {
            $this->diskJockey->resumePlaying();
        }
        if ($this->joined && $this->inputCall !== null) {
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

    #[\Override]
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * Discard (leave) the conference call.
     */
    #[\Override]
    public function discard(): self
    {
        $this->leave();
        return $this;
    }

    /**
     * Leave the conference, keeping it running for the other participants: stop the backstop poll and
     * stop receiving its updates.
     */
    #[\Override]
    public function leave(): self
    {
        $this->joined = false;
        if ($this->pollWatcher !== null) {
            EventLoop::cancel($this->pollWatcher);
            $this->pollWatcher = null;
        }
        $this->presentationConnection?->close();
        $this->presentationConnection = null;
        $this->presentationDj?->discard();
        $this->presentationDj = null;
        if ($this->inputCall === null) {
            return $this;
        }
        $this->API->unregisterConferenceCall($this->inputCall['id']);
        $source = $this->connection?->getAudioSource() ?? 0;
        $this->connection?->close();
        $this->connection = null;
        try {
            $this->API->methodCallAsyncRead('phone.leaveGroupCall', ['call' => $this->inputCall, 'source' => $source]);
        } catch (Throwable $e) {
            $this->log("Could not leave $this: $e", Logger::WARNING);
        }
        return $this;
    }

    /**
     * The participants currently in the conference, keyed by user id, each with their Ed25519
     * `public_key` and `permissions` bits from the shared-state chain.
     *
     * @return array<int, array{public_key: string, permissions: int}>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getParticipants(): array
    {
        return $this->chain->getParticipants();
    }

    /* ------------------------------------------------------------------ *
     *  Emoji verification (subchain 1).
     * ------------------------------------------------------------------ */

    /**
     * Begin (or restart) verification for the current chain head: broadcast our nonce commit, then
     * its reveal. All participants then converge on the same four emojis.
     */
    public function startVerification(): void
    {
        $this->verificationNonces = [];
        $made = Verification::makeNonce($this->selfId, $this->chain->getHeight(), $this->chain->getLastBlockHash(), $this->selfSeed());
        $this->ownNonce = $made['nonce'];
        $codec = new BlockCodec();
        try {
            $this->API->methodCallAsyncRead('phone.sendConferenceCallBroadcast', ['call' => $this->inputCall, 'block' => $codec->serialize($made['commit'])]);
            $this->API->methodCallAsyncRead('phone.sendConferenceCallBroadcast', ['call' => $this->inputCall, 'block' => $codec->serialize($made['reveal'])]);
        } catch (Throwable $e) {
            $this->log("Could not broadcast verification for $this: $e", Logger::WARNING);
        }
    }

    private function applyBroadcast(string $serialized): void
    {
        $broadcast = (new BlockCodec())->deserialize($serialized);
        if (($broadcast['_'] ?? '') === 'e2e.chain.groupBroadcastNonceReveal') {
            $this->verificationNonces[(int) $broadcast['user_id']] = (string) $broadcast['nonce'];
        }
    }

    /**
     * Drop verification state when the chain (and thus the block hash) changes.
     *
     * @psalm-external-mutation-free
     */
    private function refreshVerification(): void
    {
        $this->verificationNonces = [];
        $this->ownNonce = null;
    }

    /**
     * The four verification emojis, or null until every participant's nonce has been revealed.
     *
     * @return list<string>|null
     */
    public function getEmojis(): ?array
    {
        $expected = \count($this->chain->getParticipants());
        if ($expected === 0 || \count($this->verificationNonces) < $expected) {
            return null;
        }
        $hash = Verification::emojiHash(array_values($this->verificationNonces), $this->chain->getLastBlockHash());
        return Verification::emojis($hash);
    }

    /* ------------------------------------------------------------------ *
     *  Encrypted in-call messages (channel 0).
     * ------------------------------------------------------------------ */

    /**
     * Send an end-to-end encrypted in-call message to every participant (channel 0), encrypted with
     * {@see CallPacket} for the current epochs.
     */
    public function sendMessage(string $message): void
    {
        $epochs = $this->activeEpochs();
        if ($epochs === []) {
            throw new \RuntimeException('No conference key yet');
        }
        $packet = CallPacket::encrypt(0, $this->nextMessageSeqno(), $message, $epochs, $this->selfSeed());
        $this->API->methodCallAsyncRead('phone.sendGroupCallEncryptedMessage', ['call' => $this->inputCall, 'encrypted_message' => $packet]);
    }

    private int $messageSeqno = 0;

    /**
     * @psalm-external-mutation-free
     */
    private function nextMessageSeqno(): int
    {
        return ++$this->messageSeqno;
    }

    /**
     * @internal Decrypt an [updateGroupCallEncryptedMessage](https://core.telegram.org/constructor/updateGroupCallEncryptedMessage).
     */
    public function onEncryptedMessage(int $fromUserId, string $encrypted): ?string
    {
        $senderKey = $this->chain->getParticipants()[$fromUserId]['public_key'] ?? null;
        if ($senderKey === null) {
            return null;
        }
        $secrets = [];
        foreach ($this->activeEpochs() as $epoch) {
            $secrets[$epoch['hash']] = $epoch['secret'];
        }
        try {
            return CallPacket::decrypt($encrypted, $secrets, $senderKey)['payload'];
        } catch (Throwable $e) {
            $this->log("Could not decrypt an in-call message in $this: $e", Logger::WARNING);
            return null;
        }
    }

    /* ------------------------------------------------------------------ *
     *  Participant / media bookkeeping.
     * ------------------------------------------------------------------ */

    /**
     * Record conference call media (all end-to-end encrypted; the SFU only ever sees ciphertext, but
     * incoming frames are decrypted before they are muxed, so recordings are plaintext).
     *
     * Two modes:
     *  - per participant: `setOutput($userId, $file)` records that one participant's incoming audio and
     *    (if they transmit a camera) video into the given file or stream.
     *  - folder (all participants): `setOutput(new LocalDirectory($dir))` records every *transmitting*
     *    participant into its own `<dir>/<userId>.mkv` Matroska file, including participants that start
     *    transmitting later, plus a `<dir>/<userId>.presentation.mkv` for anyone screen-sharing. Our own
     *    media is never recorded.
     */
    public function setOutput(mixed $participant, LocalFile|WritableStream|null $file = null): self
    {
        if ($participant instanceof LocalDirectory) {
            $dir = $participant->dir;
            if (!is_dir($dir) && !mkdir($dir, 0o777, true) && !is_dir($dir)) {
                throw new \RuntimeException("Could not create the recording directory $dir");
            }
            $this->outputDir = $dir;
            foreach ($this->userToSource as $userId => $source) {
                if ($userId !== $this->selfId) {
                    $this->wireFolderOutput($userId, $source);
                }
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
    private function wireOutput(int $userId, LocalFile|WritableStream $file): void
    {
        $source = $this->userToSource[$userId] ?? 0;
        if ($source !== 0 && $this->connection !== null) {
            $this->connection->setOutput($source, $file);
            return;
        }
        $this->pendingOutputs[$userId] = $file;
    }

    /**
     * In folder mode, give a transmitting (non-self) participant its own `<dir>/<userId>.mkv` file, once each.
     */
    private function wireFolderOutput(int $userId, int $source): void
    {
        if ($this->outputDir === null
            || $source === 0
            || $userId === $this->selfId
            || isset($this->folderPeers[$userId])
            || isset($this->pendingOutputs[$userId]) // an explicit per-participant output takes precedence
        ) {
            return;
        }
        $this->folderPeers[$userId] = true;
        $this->wireOutput($userId, new LocalFile($this->outputDir.'/'.$userId.'.mkv'));
    }

    /**
     * In folder mode, give a participant that is screen-sharing its own `<dir>/<userId>.presentation.mkv`
     * file, once each.
     */
    private function wireFolderPresentationOutput(int $userId, int $source, bool $hasPresentation): void
    {
        if ($this->outputDir === null
            || $source === 0
            || $userId === $this->selfId
            || !$hasPresentation
            || isset($this->folderPresentationPeers[$userId])
            || isset($this->pendingPresentationOutputs[$userId]) // an explicit output takes precedence
        ) {
            return;
        }
        $this->folderPresentationPeers[$userId] = true;
        $file = new LocalFile($this->outputDir.'/'.$userId.'.presentation.mkv');
        if ($this->connection !== null) {
            $this->connection->setPresentationOutput($source, $file);
        } else {
            $this->pendingPresentationOutputs[$userId] = $file;
        }
    }

    /**
     * Update the SSRC -> user id map from the group call participant list, so incoming media can be
     * attributed to a sender's public key, and tell the connection which sources to receive.
     *
     * @param list<array{user_id: int, source: int, video: list<int>, presentation: list<int>, videoEndpoint: ?string, presentationEndpoint: ?string}> $participants
     */
    public function setParticipants(array $participants): void
    {
        $sources = [];
        foreach ($participants as $participant) {
            if ($participant['source'] === 0) {
                continue;
            }
            $userId = $participant['user_id'];
            $source = $participant['source'];
            $this->ssrcToUser[GroupSdp::toUnsignedSsrc($source)] = $userId;
            $this->userToSource[$userId] = $source;
            if ($userId !== $this->selfId) {
                $sources[] = [
                    'audio' => $source,
                    'video' => $participant['video'],
                    'presentation' => $participant['presentation'],
                    'videoEndpoint' => $participant['videoEndpoint'],
                    'presentationEndpoint' => $participant['presentationEndpoint'],
                ];
                // The source is now known: attach any recording deferred until it was, then apply
                // folder-mode recording to a participant that has just begun transmitting.
                if (isset($this->pendingOutputs[$userId])) {
                    $file = $this->pendingOutputs[$userId];
                    unset($this->pendingOutputs[$userId]);
                    $this->connection?->setOutput($source, $file);
                }
                if (isset($this->pendingPresentationOutputs[$userId])) {
                    $file = $this->pendingPresentationOutputs[$userId];
                    unset($this->pendingPresentationOutputs[$userId]);
                    $this->connection?->setPresentationOutput($source, $file);
                }
                $this->wireFolderOutput($userId, $source);
                $this->wireFolderPresentationOutput($userId, $source, $participant['presentation'] !== []);
            }
        }
        $this->connection?->setRemoteSources($sources);
    }

    /**
     * Set our inputGroupCall from the groupCall an update carries (the create/join response).
     */
    private function extractCall(array $updates): void
    {
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCall' && ($update['call']['_'] ?? '') === 'groupCall') {
                $this->setCall($update['call']);
                return;
            }
        }
    }

    private function consumeUpdates(array $updates): void
    {
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCallConnection' && !($update['presentation'] ?? false)) {
                $parsed = GroupSdp::parseJoinResponse($update['params']);
                if ($parsed['transport'] !== null) {
                    $this->connection?->setTransport($parsed['transport'], $parsed['video']);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $updates
     *
     * @return list<string>
     */
    private function extractBlocks(array $updates): array
    {
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCallChainBlocks') {
                return array_map('strval', $update['blocks']);
            }
        }
        return [];
    }

    public function getChain(): ConferenceChain
    {
        return $this->chain;
    }

    /* ------------------------------------------------------------------ *
     *  GroupConnectionOwner.
     * ------------------------------------------------------------------ */

    #[\Override]
    public function log(string $message, int $level = Logger::NOTICE): void
    {
        $this->API->logger->logger($message, $level);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function onIncomingSource(int $source): void
    {
        // Media from a participant started; nothing extra to do — the frame cryptor decrypts it.
    }

    #[\Override]
    public function onConnectionFailed(): void
    {
        $this->log("The WebRTC connection of $this failed", Logger::WARNING);
    }

    #[\Override]
    public function setVideoStopped(bool $stopped): void
    {
        if (!$this->joined) {
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

    #[\Override]
    public function setPresentationPaused(bool $paused): void
    {
        if (!$this->joined || $this->inputCall === null) {
            return;
        }
        try {
            $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', [
                'call' => $this->inputCall,
                'participant' => ['_' => 'inputPeerSelf'],
                'presentation_paused' => $paused,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not change the presentation state of $this: $e", Logger::WARNING);
        }
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return 'E2E conference '.($this->inputCall['id'] ?? '(new)');
    }
}
