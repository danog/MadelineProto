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
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\ConferenceCall as ConferenceCallUpdate;
use danog\MadelineProto\GroupCall\GroupCallState;
use danog\MadelineProto\GroupCall\Participant;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\TextEntities;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\GroupConnection;
use danog\MadelineProto\Tgcalls\GroupConnectionOwner;
use danog\MadelineProto\Tgcalls\GroupMediaTrait;
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
final class ConferenceCall implements GroupConnectionOwner, E2EKeyProvider, CallControllerInterface
{
    use GroupMediaTrait;

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
    private FrameCryptor $frameCryptor;
    /** Packet counters shared by the camera and screen-share cryptors (serialized with the call). */
    private FrameCryptorState $cryptorState;
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
    /** @var array<int, int> SSRC (unsigned) => participant user id, for verifying incoming media senders. */
    private array $ssrcToUser = [];
    /** @var array<int, int> user id => signed audio source, learned from the participant list, for recording. */
    private array $userToSource = [];
    /**
     * The commit-reveal verification of the current chain head, see {@see self::restartVerification()}:
     * the head it is for, its phase, our own commit/reveal broadcasts (and whether the commit was
     * sent), the members (user id => public key) that must take part, what they committed/revealed,
     * and the resulting emojis once everyone revealed.
     *
     * @var array{height: int, hash: string, state: 'commit'|'reveal'|'end', sent: bool, commit: array<string, mixed>, reveal: array<string, mixed>, participants: array<int, string>, committed: array<int, string>, revealed: array<int, string>, emojis: list<string>|null}|null
     */
    private ?array $verification = null;
    /** @var array<int, list<string>> Verification broadcasts for chain heights we have not reached yet, by height. */
    private array $delayedBroadcasts = [];
    /**
     * The participants of the underlying group call (the RTC side: sources, video, screen-share), by
     * user id, from phone.getGroupCall and updateGroupCallParticipants.
     *
     * @var array<int, Participant>
     */
    private array $rtcParticipants = [];
    /** Version of the underlying groupCall, for ordering participant updates. */
    private int $callVersion = 0;
    /** The raw groupCall constructor of the conference, as last seen. */
    private ?array $rawCall = null;
    /** Whether we are leaving on purpose, so that being forbidden is not answered with a rejoin. */
    private bool $leaving = false;
    /** Whether an automatic rejoin is already scheduled. */
    private bool $rejoinScheduled = false;
    /** Counts backstop poll ticks, to poll every 5 seconds outside of key verification. */
    private int $pollTick = 0;
    /** @var array<string, true> Recently seen in-call message ids (`from:random_id`), for deduplication. */
    private array $seenMessages = [];

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
        $this->cryptorState = new FrameCryptorState();
        $this->frameCryptor = new FrameCryptor($this, $this->cryptorState);
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
        // Sessions serialized before the packet counters were shared carry no state object.
        $this->cryptorState ??= new FrameCryptorState();
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
        // Restart the demuxers/readers (DjLoop::__unserialize() leaves them dormant), or nothing is
        // transmitted after a restart.
        $this->diskJockey->resumeReader();
        $this->presentationDj?->resumeReader();
        $this->connection?->resume();
        $this->presentationConnection?->resume();
        $this->API->registerConferenceCall($this->inputCall['id'], $this);
        $this->startPolling();
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->syncChain(self::SUBCHAIN_VERIFICATION);
        $this->refetchParticipants();
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
    #[\Override]
    public function getInputCall(): array
    {
        return $this->inputCall ?? throw new \RuntimeException('The conference call does not exist yet.');
    }

    /**
     * The public {@see ConferenceCallUpdate} handle for this conference, built lazily once the call
     * exists. This is the object handed to library users; it delegates every operation back to this
     * controller by call id.
     *
     * @psalm-external-mutation-free
     */
    public function getPublic(): ConferenceCallUpdate
    {
        $call = $this->getInputCall();
        return $this->public ??= new ConferenceCallUpdate($this->API, $this->rawCall ?? [
            '_' => 'groupCall',
            'id' => $call['id'],
            'access_hash' => $call['access_hash'],
            'conference' => true,
        ]);
    }

    /**
     * Whether we are currently in the conference (joined and not left/forbidden).
     *
     * @psalm-mutation-free
     */
    public function isJoined(): bool
    {
        return $this->joined;
    }

    /**
     * Whether we left (or discarded) the conference for good: the playback machinery stops then, but
     * not while we are merely between a drop and the automatic re-join.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isCallEnded(): bool
    {
        return $this->leaving;
    }

    /**
     * Get the state of the conference call.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function getCallState(): GroupCallState
    {
        return $this->joined ? GroupCallState::JOINED : GroupCallState::NOT_JOINED;
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
        $this->connection = $this->replaceConnection();
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
        $this->leaving = false;
        $this->API->registerConferenceCall($this->getInputCall()['id'], $this);
        $this->startPolling();
        $this->restartVerification();
        $this->refetchParticipants();
        $this->log("Created and joined E2E conference $this", Logger::NOTICE);
    }

    /**
     * Join an existing conference call: fetch the chain, add ourselves in a new block, and join with
     * that block.
     */
    public function join(bool $muted = false): self
    {
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->connection = $this->replaceConnection();
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
        $this->leaving = false;
        $this->API->registerConferenceCall($this->getInputCall()['id'], $this);
        // Re-sync so our own accepted block (and any concurrent ones) are applied in server order.
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->startPolling();
        // Verification of the current head starts as blocks are applied; make sure our commit went out
        // for a head that was applied before we were joined.
        $this->restartVerification();
        $this->ensureVerificationBroadcast();
        $this->refetchParticipants();
        $this->log("Joined E2E conference $this", Logger::NOTICE);
        return $this;
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
        foreach ($this->chain->getParticipantTuples() as $tuple) {
            if ($tuple[0] === $this->selfId) {
                continue; // a stale entry of ours (an earlier key) is replaced, as tde2e does
            }
            $participants[] = $tuple;
            $recipients[] = [$tuple[0], $tuple[1]];
        }
        // The same permissions tde2e takes for itself: they let us prune members that left.
        $participants[] = [$this->selfId, $this->chain->getSelfPublicKey(), ConferenceChain::PERMISSIONS_MEMBER, ConferenceChain::PROTOCOL_VERSION];
        $recipients[] = [$this->selfId, $this->chain->getSelfPublicKey()];

        $raw = random_bytes(32);
        $changes = [
            $this->chain->groupStateChange($participants),
            ['_' => 'e2e.chain.changeSetSharedKey', 'shared_key' => $this->chain->buildSharedKey($raw, $recipients)],
        ];
        return $this->chain->buildBlock($this->chain->getHeight() + 1, $this->chain->getLastBlockHash(), $changes);
    }

    /**
     * Change a participant's state (phone.editGroupCallParticipant): mute them for ourselves, set our
     * playback volume of them, or pause/resume our own video.
     */
    public function editParticipant(mixed $participant, ?bool $muted = null, ?int $volume = null, ?bool $videoPaused = null): void
    {
        $params = ['call' => $this->getInputCall(), 'participant' => $participant];
        if ($muted !== null) {
            $params['muted'] = $muted;
        }
        if ($volume !== null) {
            if ($volume < 1 || $volume > 20000) {
                throw new \InvalidArgumentException('The volume must be between 1 and 20000 (10000 = 100%).');
            }
            $params['volume'] = $volume;
        }
        if ($videoPaused !== null) {
            $params['video_paused'] = $videoPaused;
        }
        $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', $params);
    }

    /**
     * Change the conference's settings (phone.toggleGroupCallSettings): whether new members join
     * muted, whether in-call messages are enabled, or invalidate its conference link.
     */
    public function toggleSettings(?bool $joinMuted = null, bool $resetInviteHash = false, ?bool $messagesEnabled = null): void
    {
        $params = ['call' => $this->getInputCall(), 'reset_invite_hash' => $resetInviteHash];
        if ($joinMuted !== null) {
            $params['join_muted'] = $joinMuted;
        }
        if ($messagesEnabled !== null) {
            $params['messages_enabled'] = $messagesEnabled;
        }
        $updates = $this->API->methodCallAsyncRead('phone.toggleGroupCallSettings', $params);
        if ($resetInviteHash) {
            // The new link comes back in the updateGroupCall of the response, or on the next fetch.
            $this->extractCall($updates);
        }
    }

    /**
     * Invite users to the conference call (phone.inviteConferenceCallParticipant), ringing them; once
     * they accept they add themselves to the chain with their own self-join block.
     */
    public function invite(mixed ...$users): self
    {
        /** @var mixed $user */
        foreach ($users as $user) {
            $this->API->methodCallAsyncRead('phone.inviteConferenceCallParticipant', [
                'call' => $this->getInputCall(),
                'user_id' => $user,
            ]);
        }
        return $this;
    }

    /**
     * The [conference link »](https://core.telegram.org/api/links#conference-links) of the call: it is
     * created with the call and carried by its groupCall (`invite_link`), so, like official clients, we
     * read it from there rather than exporting one.
     */
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        $link = (string) ($this->rawCall['invite_link'] ?? '');
        if ($link === '') {
            $this->refetchParticipants();
            $link = (string) ($this->rawCall['invite_link'] ?? '');
        }
        if ($link === '') {
            throw new \RuntimeException("The server did not provide an invite link for $this.");
        }
        return $link;
    }

    /**
     * Remove participants from the conference: build a block dropping them and rekeying for the
     * remaining members, then submit it with phone.deleteConferenceCallParticipants. Requires the
     * `remove_users` permission. The removed members can no longer decrypt media once the new epoch
     * takes over.
     */
    public function removeParticipant(mixed ...$participants): self
    {
        $this->submitRemoval(array_values(array_map($this->API->getId(...), $participants)), kick: true);
        return $this;
    }

    /**
     * Drop members from the chain, rekeying for the remaining ones, with
     * phone.deleteConferenceCallParticipants: `kick` forcibly removes active members (requires the
     * `remove_users` permission), otherwise the `only_left` flag prunes members that already left the
     * call. Members not in the chain (and ourselves) are ignored.
     *
     * @param list<int> $userIds
     */
    private function submitRemoval(array $userIds, bool $kick): void
    {
        $remove = [];
        foreach ($userIds as $userId) {
            if ($userId !== $this->selfId && isset($this->chain->getParticipants()[$userId])) {
                $remove[$userId] = true;
            }
        }
        if ($remove === []) {
            return;
        }
        $updates = $this->submitWithChainRetry(function () use ($remove, $kick): array {
            // Built from the current chain state each attempt, so a retry rekeys on the fresh head.
            $participants = [];
            $recipients = [];
            foreach ($this->chain->getParticipantTuples() as $tuple) {
                if (isset($remove[$tuple[0]])) {
                    continue;
                }
                $participants[] = $tuple;
                $recipients[] = [$tuple[0], $tuple[1]];
            }
            if ($recipients === []) {
                throw new \RuntimeException('Cannot remove every participant from the conference.');
            }
            $raw = random_bytes(32);
            $block = $this->chain->buildBlock($this->chain->getHeight() + 1, $this->chain->getLastBlockHash(), [
                $this->chain->groupStateChange($participants),
                ['_' => 'e2e.chain.changeSetSharedKey', 'shared_key' => $this->chain->buildSharedKey($raw, $recipients)],
            ]);
            return $this->API->methodCallAsyncRead('phone.deleteConferenceCallParticipants', [
                'kick' => $kick,
                'only_left' => !$kick,
                'call' => $this->getInputCall(),
                'ids' => array_keys($remove),
                'block' => $block['serialized'],
            ]);
        });
        $this->consumeUpdates($updates);
        // Apply our own accepted block (and anything concurrent) in server order.
        $this->syncChain(self::SUBCHAIN_STATE);
    }

    /**
     * Prune members of the chain that are no longer in the call (they left, or are missing from a
     * complete participant list), so the key is rotated away from them and verification no longer
     * waits for them. Requires the `remove_users` permission, which every member normally holds.
     *
     * @param list<int> $userIds
     */
    private function pruneStale(array $userIds): void
    {
        if (!$this->joined || ($this->chain->getSelfPermissions() & ConferenceChain::PERMISSION_REMOVE_USERS) === 0) {
            return;
        }
        try {
            $this->submitRemoval($userIds, kick: false);
        } catch (Throwable $e) {
            $this->log("Could not prune the members that left $this: $e", Logger::WARNING);
        }
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
     * We were forbidden from the call (removed from the chain, or its state was reset): stop
     * participating so we neither poll nor apply further blocks, then — as official clients do — rejoin
     * transparently with a fresh key, unless we were leaving anyway.
     */
    private function handleForbidden(): void
    {
        $wasJoined = $this->joined;
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
        $this->dropPresentation();
        if ($wasJoined && !$this->leaving) {
            // Recordings in progress carry on with the connection of the rejoin.
            $this->detachConnection();
            $this->scheduleRejoin();
        } else {
            $this->closeConnection();
        }
    }

    /**
     * Rejoin shortly, once, from the event loop (never from inside the failing request).
     */
    private function scheduleRejoin(): void
    {
        if ($this->rejoinScheduled) {
            return;
        }
        $this->rejoinScheduled = true;
        EventLoop::delay(1.0, function (): void {
            $this->rejoinScheduled = false;
            if ($this->leaving || $this->joined || $this->inputCall === null) {
                return;
            }
            try {
                $this->rejoin();
            } catch (Throwable $e) {
                $this->log("Could not rejoin $this: $e", Logger::ERROR);
            }
        });
    }

    /**
     * Join again from scratch with a fresh key pair and an empty local chain, keeping the playlists.
     */
    private function rejoin(): void
    {
        $this->log("Rejoining $this with a fresh key...", Logger::NOTICE);
        [$this->selfSeed] = Crypto::generateKeyPair();
        $this->chain = new ConferenceChain($this->selfId, $this->selfSeed);
        $this->epochs = [];
        $this->chainOffset = [0, 0];
        $this->verification = null;
        $this->delayedBroadcasts = [];
        $this->rtcParticipants = [];
        $this->callVersion = 0;
        $this->join($this->muted);
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
            $chunk = $this->extractBlocks($updates);
            $blocks = $chunk['blocks'] ?? [];
            if ($chunk !== null) {
                $this->applyChainBlocks($subChainId, $blocks, $chunk['next'], fromPoll: true);
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
        $this->applyChainBlocks($subChainId, $blocks, $nextOffset, fromPoll: false);
    }

    /**
     * Apply a run of consecutive blocks of a subchain ending right before `$nextOffset`, in order and
     * exactly once: blocks before our offset were already applied (the same block may reach us from
     * both the push update and the polling backstop), and a run starting past our offset means we
     * missed some, which are fetched first (and then include these).
     *
     * @param list<string> $blocks
     */
    private function applyChainBlocks(int $subChainId, array $blocks, int $nextOffset, bool $fromPoll): void
    {
        $first = $nextOffset - \count($blocks);
        if ($first > $this->chainOffset[$subChainId]) {
            if (!$fromPoll) {
                $this->syncChain($subChainId);
                return;
            }
            // We asked from our offset and the server started later: whatever lies in between no
            // longer exists server-side, so there is nothing else to fetch; carry on from here.
            $this->log("The server skipped blocks {$this->chainOffset[$subChainId]}..$first of chain $subChainId of $this", Logger::WARNING);
            $this->chainOffset[$subChainId] = $first;
        }
        foreach ($blocks as $i => $block) {
            $index = $first + $i;
            if ($index < $this->chainOffset[$subChainId]) {
                continue;
            }
            $this->applyBlock($subChainId, $index, $block);
        }
    }

    private function applyBlock(int $subChainId, int $index, string $serialized): void
    {
        try {
            if ($subChainId === self::SUBCHAIN_STATE) {
                // Only a newly applied block advances the epoch, the offset and the verification.
                if ($this->chain->applyServerBlock($serialized)) {
                    $this->chainOffset[self::SUBCHAIN_STATE] = $this->chain->getHeight() + 1;
                    $this->refreshEpoch();
                    $this->restartVerification();
                }
            } else {
                $this->chainOffset[self::SUBCHAIN_VERIFICATION] = $index + 1;
                $this->applyBroadcast($serialized);
            }
        } catch (Throwable $e) {
            $this->log("Could not apply block $index of chain $subChainId of $this: $e", Logger::WARNING);
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
        // As the protocol asks, it ticks every second while a key verification is in progress and
        // every 5 seconds otherwise.
        $this->pollWatcher = EventLoop::repeat(1.0, function (): void {
            if (!$this->joined) {
                return;
            }
            $verifying = $this->verification !== null && $this->verification['state'] !== 'end';
            if (!$verifying && ++$this->pollTick % 5 !== 0) {
                return;
            }
            $this->syncChain(self::SUBCHAIN_STATE);
            $this->syncChain(self::SUBCHAIN_VERIFICATION);
        });
    }

    /* ------------------------------------------------------------------ *
     *  Screen-share: a second, end-to-end encrypted connection.
     * ------------------------------------------------------------------ */

    /* ------------------------------------------------------------------ *
     *  Media playback — the common {@see Call} interface. Every frame is end-to-end
     *  encrypted (camera and screen-share alike) before it reaches the SFU.
     * ------------------------------------------------------------------ */

    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        $this->dj($dest)->play($file);
        return $this;
    }

    /**
     * End the conference for everyone (phone.discardGroupCall, allowed to its creator only) and leave
     * it; if the server refuses, just leave.
     */
    public function discard(): self
    {
        if ($this->inputCall !== null && $this->joined) {
            try {
                $this->API->methodCallAsyncRead('phone.discardGroupCall', ['call' => $this->inputCall]);
            } catch (Throwable $e) {
                $this->log("Could not discard $this, leaving it instead: $e", Logger::WARNING);
            }
        }
        return $this->leave();
    }

    /**
     * Leave the conference, keeping it running for the other participants: stop the backstop poll and
     * stop receiving its updates.
     */
    public function leave(): self
    {
        $this->leaving = true;
        $this->joined = false;
        if ($this->pollWatcher !== null) {
            EventLoop::cancel($this->pollWatcher);
            $this->pollWatcher = null;
        }
        $this->dropPresentation();
        if ($this->inputCall === null) {
            $this->closeConnection();
            return $this;
        }
        $this->API->unregisterConferenceCall($this->inputCall['id']);
        $source = $this->connection?->getAudioSource() ?? 0;
        $this->closeConnection();
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
     * @return array<int, array{public_key: string, permissions: int, version: int}>
     *
     * @psalm-mutation-free
     */
    public function getParticipants(): array
    {
        return $this->chain->getParticipants();
    }

    /* ------------------------------------------------------------------ *
     *  Emoji verification (subchain 1), mirroring tde2e's CallVerificationChain.
     * ------------------------------------------------------------------ */

    /**
     * (Re)start the commit-reveal verification for the current chain head, as every participant does
     * whenever a main-chain block is applied: pick a fresh nonce, broadcast its commit (once joined),
     * then reveal it once every member has committed; the emojis follow once everyone revealed.
     * A no-op if the current head is already being verified.
     */
    private function restartVerification(): void
    {
        $height = $this->chain->getHeight();
        $hash = $this->chain->getLastBlockHash();
        if ($height < 0) {
            return;
        }
        if ($this->verification !== null && $this->verification['height'] === $height && $this->verification['hash'] === $hash) {
            return;
        }
        $participants = [];
        foreach ($this->chain->getParticipants() as $userId => $info) {
            $participants[$userId] = $info['public_key'];
        }
        $made = Verification::makeNonce($this->selfId, $height, $hash, $this->selfSeed);
        $this->verification = [
            'height' => $height,
            'hash' => $hash,
            'state' => 'commit',
            'sent' => false,
            'commit' => $made['commit'],
            'reveal' => $made['reveal'],
            'participants' => $participants,
            'committed' => [],
            'revealed' => [],
            'emojis' => null,
        ];
        $this->ensureVerificationBroadcast();
        // Broadcasts for this head that arrived before its block did; older ones are moot.
        $delayed = $this->delayedBroadcasts[$height] ?? [];
        foreach ($this->delayedBroadcasts as $h => $_) {
            if ($h <= $height) {
                unset($this->delayedBroadcasts[$h]);
            }
        }
        foreach ($delayed as $serialized) {
            $this->applyBroadcast($serialized);
        }
    }

    /**
     * Broadcast our nonce commit for the head under verification, if we are in the call and have not
     * done so yet.
     */
    private function ensureVerificationBroadcast(): void
    {
        if ($this->verification === null || $this->verification['sent'] || !$this->joined) {
            return;
        }
        $this->verification['sent'] = true;
        $this->broadcast($this->verification['commit']);
    }

    /**
     * Submit a verification broadcast (phone.sendConferenceCallBroadcast); it reaches everyone, us
     * included, through subchain 1.
     *
     * @param array<string, mixed> $broadcast
     */
    private function broadcast(array $broadcast): void
    {
        try {
            $this->API->methodCallAsyncRead('phone.sendConferenceCallBroadcast', [
                'call' => $this->getInputCall(),
                'block' => (new BlockCodec())->serialize($broadcast),
            ]);
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'GROUPCALL_FORBIDDEN') {
                $this->handleForbidden();
                return;
            }
            $this->log("Could not send a verification broadcast for $this: $e", Logger::WARNING);
        } catch (Throwable $e) {
            $this->log("Could not send a verification broadcast for $this: $e", Logger::WARNING);
        }
    }

    /**
     * Apply a subchain 1 broadcast: a commit is accepted while collecting commits, a reveal while
     * collecting reveals (and must match its commit); both must be for the head under verification
     * (later ones wait for their block, earlier ones are dropped), from a member, and signed by them.
     */
    private function applyBroadcast(string $serialized): void
    {
        $broadcast = (new BlockCodec())->deserialize($serialized);
        $type = (string) ($broadcast['_'] ?? '');
        if ($type !== 'e2e.chain.groupBroadcastNonceCommit' && $type !== 'e2e.chain.groupBroadcastNonceReveal') {
            return;
        }
        $height = (int) $broadcast['chain_height'];
        if ($this->verification === null || $height > $this->verification['height']) {
            $this->delayedBroadcasts[$height][] = $serialized;
            return;
        }
        if ($height < $this->verification['height']) {
            return;
        }
        $userId = (int) $broadcast['user_id'];
        if ((string) $broadcast['chain_hash'] !== $this->verification['hash']) {
            $this->log("Ignoring a verification broadcast of $userId for another chain head of $this", Logger::WARNING);
            return;
        }
        $publicKey = $this->verification['participants'][$userId] ?? null;
        if ($publicKey === null) {
            $this->log("Ignoring a verification broadcast of $userId, who is not in $this", Logger::WARNING);
            return;
        }
        if (!Verification::verify($broadcast, $publicKey)) {
            $this->log("Ignoring a verification broadcast of $userId with a bad signature in $this", Logger::WARNING);
            return;
        }
        $members = \count($this->verification['participants']);
        if ($type === 'e2e.chain.groupBroadcastNonceCommit') {
            if ($this->verification['state'] !== 'commit' || isset($this->verification['committed'][$userId])) {
                return;
            }
            $this->verification['committed'][$userId] = (string) $broadcast['nonce_hash'];
            if (\count($this->verification['committed']) === $members) {
                $this->verification['state'] = 'reveal';
                if ($this->joined) {
                    $this->broadcast($this->verification['reveal']);
                }
            }
            return;
        }
        if ($this->verification['state'] !== 'reveal' || isset($this->verification['revealed'][$userId])) {
            return;
        }
        $nonce = (string) $broadcast['nonce'];
        if (!Verification::checkReveal($this->verification['committed'][$userId] ?? '', $nonce)) {
            $this->log("Ignoring a nonce of $userId that does not match their commit in $this", Logger::WARNING);
            return;
        }
        $this->verification['revealed'][$userId] = $nonce;
        if (\count($this->verification['revealed']) === $members) {
            $this->verification['state'] = 'end';
            $hash = Verification::emojiHash(array_values($this->verification['revealed']), $this->verification['hash']);
            $this->verification['emojis'] = Verification::emojis($hash);
            $this->log("Verified $this: ".implode(' ', $this->verification['emojis']), Logger::NOTICE);
        }
    }

    /**
     * The four verification emojis of the current chain head, or null until every member has committed
     * and revealed their nonce for it.
     *
     * @return list<string>|null
     *
     * @psalm-mutation-free
     */
    public function getVisualization(): ?array
    {
        return $this->verification['emojis'] ?? null;
    }

    /* ------------------------------------------------------------------ *
     *  Encrypted in-call messages (channel 0).
     * ------------------------------------------------------------------ */

    /**
     * Send an end-to-end encrypted in-call message to every participant: a `groupCallMessage` JSON
     * document, as [the protocol](https://core.telegram.org/api/end-to-end/group-calls#in-call-messages)
     * defines, encrypted with {@see CallPacket} on channel 0 for the current epochs.
     */
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, mixed $sendAs = null): self
    {
        $entities = [];
        if ($parseMode === ParseMode::MARKDOWN) {
            $parsed = TextEntities::fromMarkdown($message);
            [$message, $entities] = [$parsed->message, $parsed->entities];
        } elseif ($parseMode === ParseMode::HTML) {
            $parsed = TextEntities::fromHtml($message);
            [$message, $entities] = [$parsed->message, $parsed->entities];
        }
        $raw = [];
        foreach ($entities as $entity) {
            $raw[] = $entity->toMTProto();
        }
        $this->sendTextWithEntities($message, self::filterConferenceEntities($raw));
        return $this;
    }

    /**
     * Send an end-to-end encrypted in-call reaction: a single emoji, or a custom emoji with `$emoji`
     * as its fallback.
     */
    public function sendReaction(string $emoji, ?int $customEmojiId = null): self
    {
        $entities = [];
        if ($customEmojiId !== null) {
            $length = (int) (\strlen(mb_convert_encoding($emoji, 'UTF-16', 'UTF-8')) / 2);
            $entities[] = ['_' => 'messageEntityCustomEmoji', 'offset' => 0, 'length' => $length, 'document_id' => $customEmojiId];
        }
        $this->sendTextWithEntities($emoji, $entities);
        return $this;
    }

    /**
     * The entity types conference messages may carry, per the protocol.
     */
    private const CONFERENCE_ENTITIES = ['messageEntityBold', 'messageEntityItalic', 'messageEntityUnderline', 'messageEntityStrike', 'messageEntitySpoiler', 'messageEntityCustomEmoji'];

    /**
     * Keep only the entities the conference message format supports, in their JSON form.
     *
     * @param list<array<array-key, mixed>> $entities
     *
     * @return list<array<string, mixed>>
     *
     * @psalm-pure
     */
    private static function filterConferenceEntities(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $type = (string) ($entity['_'] ?? '');
            if (!\in_array($type, self::CONFERENCE_ENTITIES, true)) {
                continue;
            }
            $json = ['_' => $type, 'offset' => (int) $entity['offset'], 'length' => (int) $entity['length']];
            if ($type === 'messageEntityCustomEmoji') {
                $json['document_id'] = (string) $entity['document_id'];
            }
            $result[] = $json;
        }
        return $result;
    }

    /**
     * Encrypt and send a `groupCallMessage` JSON document on channel 0, as
     * [the protocol](https://core.telegram.org/api/end-to-end/group-calls#conference-in-call-messages) defines.
     *
     * @param list<array<string, mixed>> $entities
     */
    private function sendTextWithEntities(string $text, array $entities): void
    {
        $epochs = $this->activeEpochs();
        if ($epochs === []) {
            throw new \RuntimeException('No conference key yet');
        }
        $json = json_encode([
            '_' => 'groupCallMessage',
            'random_id' => (string) random_int(1, PHP_INT_MAX),
            'message' => ['_' => 'textWithEntities', 'text' => $text, 'entities' => $entities],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $packet = CallPacket::encrypt(0, $this->nextMessageSeqno(), $json, $epochs, $this->selfSeed());
        $this->API->methodCallAsyncRead('phone.sendGroupCallEncryptedMessage', ['call' => $this->getInputCall(), 'encrypted_message' => $packet]);
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
     * @internal Decrypt an [updateGroupCallEncryptedMessage](https://core.telegram.org/constructor/updateGroupCallEncryptedMessage),
     * returning the text and (validated, TL-shaped) entities of the `groupCallMessage` it carries, or
     * null if it could not be decrypted, is not a message, or is a redelivery (deduplicated by sender
     * and `random_id`).
     *
     * @return array{text: string, entities: list<array<string, mixed>>, random_id: string}|null
     */
    public function onEncryptedMessage(int $fromUserId, string $encrypted): ?array
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
            $payload = CallPacket::decrypt($encrypted, $secrets, $senderKey)['payload'];
            $message = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->log("Could not decrypt an in-call message in $this: $e", Logger::WARNING);
            return null;
        }
        if (!\is_array($message) || ($message['_'] ?? null) !== 'groupCallMessage' || !\is_array($message['message'] ?? null)) {
            return null;
        }
        $randomId = (string) ($message['random_id'] ?? '');
        $text = $message['message']['text'] ?? null;
        if ($randomId === '' || !\is_string($text)) {
            return null;
        }
        $key = "$fromUserId:$randomId";
        if (isset($this->seenMessages[$key])) {
            return null;
        }
        $this->seenMessages[$key] = true;
        if (\count($this->seenMessages) > 1024) {
            array_shift($this->seenMessages);
        }
        $length = (int) (\strlen(mb_convert_encoding($text, 'UTF-16', 'UTF-8')) / 2);
        $entities = [];
        foreach ($message['message']['entities'] ?? [] as $entity) {
            if (!\is_array($entity)) {
                continue;
            }
            $type = (string) ($entity['_'] ?? '');
            $offset = (int) ($entity['offset'] ?? -1);
            $span = (int) ($entity['length'] ?? 0);
            if (!\in_array($type, self::CONFERENCE_ENTITIES, true) || $offset < 0 || $span <= 0 || $offset + $span > $length) {
                continue;
            }
            $parsed = ['_' => $type, 'offset' => $offset, 'length' => $span];
            if ($type === 'messageEntityCustomEmoji') {
                $documentId = (int) ($entity['document_id'] ?? 0);
                if ($documentId === 0) {
                    continue;
                }
                $parsed['document_id'] = $documentId;
            }
            $entities[] = $parsed;
        }
        return ['text' => $text, 'entities' => $entities, 'random_id' => $randomId];
    }

    /* ------------------------------------------------------------------ *
     *  Participant / media bookkeeping.
     * ------------------------------------------------------------------ */

    /**
     * Record one participant's (decrypted) media into a single file (or stream) with a fixed set of
     * tracks, see {@see GroupMediaTrait::recordParticipant()}.
     *
     * @param ?int $streams The {@see CallStream} flags to record, or null for every available one.
     *
     * @return int The streams the participant currently sends, as {@see CallStream} flags.
     */
    public function setOutput(LocalFile|WritableStream $file, mixed $participant = null, ?RecordingFormat $format = null, ?int $streams = null): int
    {
        return $this->recordParticipant($file, $participant, $format, $streams);
    }

    /**
     * Record the conference (decrypted) into a directory, as numbered series of Matroska files, one
     * per participant, see {@see GroupMediaTrait::recordFolder()}.
     */
    public function setOutputFolder(LocalDirectory $dir, mixed $participant = null, ?RecordingFormat $format = null): self
    {
        $this->recordFolder($dir, $participant, $format);
        return $this;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[\Override]
    private function callObject(): Call
    {
        return $this->getPublic();
    }

    /**
     * Every connection encrypts its frames end-to-end with the conference keys; the screen share on
     * its own packet channel (screen video = 3), so its sequence numbers never collide with the
     * camera's (video = 2).
     */
    #[\Override]
    private function configureConnection(GroupConnection $connection, bool $screencast): void
    {
        if (!$screencast) {
            $connection->setFrameCryptor($this->frameCryptor);
            return;
        }
        $this->presentationCryptor ??= new FrameCryptor($this, $this->cryptorState);
        $connection->setFrameCryptor($this->presentationCryptor);
    }

    #[\Override]
    private function onDroppedByServer(): void
    {
        // Exactly what being forbidden from the call means: reset and rejoin with a fresh key.
        $this->handleForbidden();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function participantOf(int $peerId): ?Participant
    {
        return $this->rtcParticipants[$peerId] ?? null;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function isOurself(int $peerId, Participant $participant): bool
    {
        return $peerId === $this->selfId;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    private function peerOfSource(int $source): ?int
    {
        return $this->ssrcToUser[GroupSdp::toUnsignedSsrc($source)] ?? null;
    }

    /**
     * @return list<int>
     *
     * @psalm-mutation-free
     */
    #[\Override]
    private function knownPeers(): array
    {
        return array_keys($this->rtcParticipants);
    }

    /**
     * @internal Apply the participants of an
     * [updateGroupCallParticipants](https://core.telegram.org/constructor/updateGroupCallParticipants)
     * of the conference's group call, in `version` order.
     */
    public function onParticipantsUpdate(array $participants, int $version): void
    {
        $versioned = false;
        /** @var array $participant */
        foreach ($participants as $participant) {
            if (($participant['versioned'] ?? false) || ($participant['left'] ?? false) || ($participant['just_joined'] ?? false)) {
                $versioned = true;
                break;
            }
        }
        if ($versioned && $this->callVersion !== 0) {
            if ($version < $this->callVersion + 1) {
                return;
            }
            if ($version > $this->callVersion + 1) {
                // A gap: rather than wait for it to fill, resync the whole list.
                $this->refetchParticipants();
                return;
            }
        }
        if ($versioned) {
            $this->callVersion = $version;
        }
        /** @var array $participant */
        foreach ($participants as $participant) {
            $this->applyRtcParticipant($participant);
        }
        $this->syncSources();
    }

    /**
     * @internal Apply an [updateGroupCall](https://core.telegram.org/constructor/updateGroupCall)
     * carrying the conference's groupCall.
     */
    public function onGroupCallUpdate(array $call): void
    {
        $this->applyRawCall($call);
    }

    private function applyRawCall(array $call): void
    {
        if (($call['_'] ?? '') === 'groupCallDiscarded') {
            $this->rawCall = $call;
            $this->public?->update($call);
            if ($this->joined) {
                $this->log("$this was discarded", Logger::NOTICE);
                $this->leave();
            }
            return;
        }
        $this->rawCall = $call;
        $this->callVersion = max($this->callVersion, (int) ($call['version'] ?? 0));
        $this->public?->update($call);
    }

    /**
     * Fetch the conference's groupCall and its full participant list (phone.getGroupCall), replacing
     * what we know, and prune from the chain the members that are not in the call any more.
     */
    private function refetchParticipants(): void
    {
        if ($this->inputCall === null) {
            return;
        }
        // Whoever was in the chain before we asked but is missing from the answer has left.
        $chainMembers = array_keys($this->chain->getParticipants());
        try {
            $result = $this->API->methodCallAsyncRead('phone.getGroupCall', ['call' => $this->inputCall, 'limit' => 100]);
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'GROUPCALL_FORBIDDEN') {
                $this->handleForbidden();
                return;
            }
            $this->log("Could not fetch the participants of $this: $e", Logger::WARNING);
            return;
        } catch (Throwable $e) {
            $this->log("Could not fetch the participants of $this: $e", Logger::WARNING);
            return;
        }
        \assert(\is_array($result) && \is_array($result['call']) && \is_array($result['participants']));
        $this->applyRawCall($result['call']);
        if (!$this->joined) {
            return;
        }
        $this->rtcParticipants = [];
        /** @var array $participant */
        foreach ($result['participants'] as $participant) {
            $this->applyRtcParticipant($participant);
        }
        $this->syncSources();
        $complete = (int) ($result['call']['participants_count'] ?? PHP_INT_MAX) <= \count($result['participants']);
        if ($complete) {
            $this->pruneStale(array_values(array_filter($chainMembers, fn (int $id): bool => !isset($this->rtcParticipants[$id]))));
        }
    }

    private function applyRtcParticipant(array $participant): void
    {
        $userId = $this->API->getIdInternal($participant['peer']);
        if ($userId === null) {
            return;
        }
        if ($participant['left'] ?? false) {
            unset($this->rtcParticipants[$userId]);
            if ($userId === $this->selfId) {
                // Our own source is gone from the call: we were removed, rejoin (as official clients do).
                if ($this->joined && (int) ($participant['source'] ?? 0) === ($this->connection?->getAudioSource() ?? 0)) {
                    $this->log("We were removed from $this, rejoining...", Logger::WARNING);
                    $this->handleForbidden();
                }
                return;
            }
            $this->pruneStale([$userId]);
            return;
        }
        $this->rtcParticipants[$userId] = Participant::fromRaw($participant, $userId, $this->rtcParticipants[$userId] ?? null);
    }

    /**
     * Rebuild the SSRC -> user id map from the participant list, so every incoming packet (audio,
     * camera or screen-share) can be attributed to its sender's public key, tell the connection which
     * sources to receive, and attach the recordings that waited for a participant's sources.
     */
    private function syncSources(): void
    {
        $sources = [];
        foreach ($this->rtcParticipants as $userId => $participant) {
            if ($participant->source === 0) {
                continue;
            }
            foreach ([$participant->source, ...$participant->videoSources, ...$participant->presentationSources] as $ssrc) {
                $this->ssrcToUser[GroupSdp::toUnsignedSsrc($ssrc)] = $userId;
            }
            $this->userToSource[$userId] = $participant->source;
            if ($userId === $this->selfId) {
                continue;
            }
            $sources[] = [
                'audio' => $participant->source,
                'muted' => $participant->muted,
                'video' => $participant->videoSources,
                'presentation' => $participant->presentationSources,
                'videoEndpoint' => $participant->videoEndpoint,
                'presentationEndpoint' => $participant->presentationEndpoint,
            ];
            // The source is now known: attach any recording deferred until it was, then apply
            // folder-mode recording to a participant that has just begun transmitting.
            if (isset($this->explicitOutputs[$userId])) {
                $this->wireOutput($userId);
            }
            $this->wireFolderOutput($userId);
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
                $this->applyRawCall((array) $update['call']);
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
     * The chain blocks and next offset carried by an updateGroupCallChainBlocks, if any.
     *
     * @param array<string, mixed> $updates
     *
     * @return array{blocks: list<string>, next: int}|null
     */
    private function extractBlocks(array $updates): ?array
    {
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCallChainBlocks') {
                return ['blocks' => array_values(array_map('strval', $update['blocks'])), 'next' => (int) $update['next_offset']];
            }
        }
        return null;
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

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return 'E2E conference '.($this->inputCall['id'] ?? '(new)');
    }
}
