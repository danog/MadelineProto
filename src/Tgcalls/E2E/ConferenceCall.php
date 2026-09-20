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
use danog\MadelineProto\Call;
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
 * This is the public object returned by {@see \danog\MadelineProto\MTProto::createConferenceCall()}
 * and {@see \danog\MadelineProto\MTProto::joinConferenceCall()}; it implements the common
 * {@see Call} media interface plus conference-specific controls (verification, encrypted messages).
 */
final class ConferenceCall implements Call, GroupConnectionOwner, E2EKeyProvider
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
    /** user id => revealed verification nonce, for computing the emoji hash. */
    private array $verificationNonces = [];
    /** Our own verification nonce for the current chain head, if a verification is in progress. */
    private ?string $ownNonce = null;

    private bool $joined = false;
    /** Event-loop id of the backstop chain poll, so it can be cancelled on leave. */
    private ?string $pollWatcher = null;

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
        $this->API->registerConferenceCall($this->inputCall['id'], $this);
        $this->startPolling();
        $this->syncChain(self::SUBCHAIN_STATE);
        $this->syncChain(self::SUBCHAIN_VERIFICATION);
        $this->log("Resumed E2E conference $this after a restart", Logger::NOTICE);
    }

    /** Point this controller at an existing conference (its groupCall), before joining it. */
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
     */
    public function getInputCall(): array
    {
        return $this->inputCall ?? throw new \RuntimeException('The conference call does not exist yet.');
    }

    /* ------------------------------------------------------------------ *
     *  E2EKeyProvider — the live keys the frame cryptor uses.
     * ------------------------------------------------------------------ */

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
        if (!Crypto::available()) {
            throw new \RuntimeException('End-to-end encrypted conference calls require the sodium and openssl extensions.');
        }
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
        if (!Crypto::available()) {
            throw new \RuntimeException('End-to-end encrypted conference calls require the sodium and openssl extensions.');
        }
        $this->syncChain(self::SUBCHAIN_STATE);
        $selfAdd = $this->buildSelfAddBlock();
        $this->connection = new GroupConnection($this, $this->diskJockey);
        $this->connection->setFrameCryptor($this->frameCryptor);
        $params = $this->connection->buildJoinPayload();

        $updates = $this->API->methodCallAsyncRead('phone.joinGroupCall', [
            'muted' => $muted,
            'video_stopped' => true,
            'call' => $this->inputCall,
            'join_as' => ['_' => 'inputPeerSelf'],
            'public_key' => $this->chain->getSelfPublicKey(),
            'block' => $selfAdd['serialized'],
            'params' => $params,
        ]);
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
        $updates = $this->API->methodCallAsyncRead('phone.deleteConferenceCallParticipants', [
            'kick' => true,
            'call' => $this->getInputCall(),
            'ids' => $userIds,
            'block' => $block['serialized'],
        ]);
        $this->consumeUpdates($updates);
        // Apply our own accepted block (and anything concurrent) in server order.
        $this->syncChain(self::SUBCHAIN_STATE);
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
     *  Media playback — the common {@see Call} interface. Every frame is end-to-end
     *  encrypted before it reaches the SFU. Screen-share (Presentation) is not wired yet.
     * ------------------------------------------------------------------ */

    private bool $muted = false;

    /** Conference screen-share would need a second connection; reject it until that lands. */
    private static function requireCamera(MediaDestination $dest): void
    {
        if ($dest !== MediaDestination::Camera) {
            throw new \RuntimeException('Screen-share is not yet supported in end-to-end conference calls.');
        }
    }

    #[\Override]
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        self::requireCamera($dest);
        $this->diskJockey->play($file);
        return $this;
    }

    #[\Override]
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): self
    {
        self::requireCamera($dest);
        $this->diskJockey->play($file);
        return $this;
    }

    #[\Override]
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): self
    {
        self::requireCamera($dest);
        $this->diskJockey->playOnHold(...$files);
        return $this;
    }

    #[\Override]
    public function skip(MediaDestination $dest = MediaDestination::Camera): self
    {
        self::requireCamera($dest);
        $this->diskJockey->skip();
        return $this;
    }

    #[\Override]
    public function stop(MediaDestination $dest = MediaDestination::Camera): self
    {
        self::requireCamera($dest);
        $this->diskJockey->stopPlaying();
        return $this;
    }

    #[\Override]
    public function pause(MediaDestination $dest = MediaDestination::Camera): self
    {
        self::requireCamera($dest);
        $this->diskJockey->pausePlaying();
        return $this;
    }

    #[\Override]
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        self::requireCamera($dest);
        return $this->diskJockey->isAudioPaused();
    }

    #[\Override]
    public function resume(MediaDestination $dest = MediaDestination::Camera): self
    {
        self::requireCamera($dest);
        $this->diskJockey->resumePlaying();
        return $this;
    }

    #[\Override]
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): LocalFile|RemoteUrl|string|null
    {
        self::requireCamera($dest);
        return $this->diskJockey->getCurrent();
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
     * Leave the conference: stop the backstop poll and stop receiving its updates.
     */
    public function leave(): void
    {
        $this->joined = false;
        if ($this->pollWatcher !== null) {
            EventLoop::cancel($this->pollWatcher);
            $this->pollWatcher = null;
        }
        if ($this->inputCall === null) {
            return;
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

    /** Drop verification state when the chain (and thus the block hash) changes. */
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
            $this->ssrcToUser[GroupSdp::toUnsignedSsrc($participant['source'])] = $participant['user_id'];
            if ($participant['user_id'] !== $this->selfId) {
                $sources[] = [
                    'audio' => $participant['source'],
                    'video' => $participant['video'],
                    'presentation' => $participant['presentation'],
                    'videoEndpoint' => $participant['videoEndpoint'],
                    'presentationEndpoint' => $participant['presentationEndpoint'],
                ];
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
        // Screen-share in a conference would be a second connection; not wired yet.
    }

    #[\Override]
    public function __toString(): string
    {
        return 'E2E conference '.($this->inputCall['id'] ?? '(new)');
    }
}
