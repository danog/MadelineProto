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

/**
 * Builds and reads the shared-state chain blocks of an end-to-end encrypted conference call
 * (subchain 0), following TDLib's tde2e Blockchain/Call logic and
 * [the protocol](https://core.telegram.org/api/end-to-end/group-calls).
 *
 * The client only ever *builds* blocks (the genesis block and membership changes) and reads state
 * back from the blocks the server echoes; the heavy validation (permissions, state proofs, the
 * key-value trie) is enforced server-side, and clients apply only server-returned blocks. This class
 * therefore focuses on constructing well-formed, correctly signed blocks and on recovering the group
 * state and shared key from a chain.
 *
 * @internal
 */
final class ConferenceChain
{
    /** Permission bits of e2e.chain.groupParticipant. */
    public const PERMISSION_ADD_USERS = 1;
    public const PERMISSION_REMOVE_USERS = 2;
    public const PERMISSION_SET_VALUE = 4;
    /**
     * The kv_hash of the empty key-value trie: tde2e hashes the TL serialization of its empty root
     * node (the int32 node type 0), not 32 zero bytes. It is what every block of a call that never
     * sets a value must carry in its state proof.
     */
    public const EMPTY_KV_HASH = "\xdf\x3f\x61\x98\x04\xa9\x2f\xdb\x40\x57\x19\x2d\xc4\x3d\xd7\x48\xea\x77\x8a\xdc\x52\xbc\x49\x8c\xe8\x05\x24\xc0\x14\xb8\x11\x19";
    /**
     * The permissions we take for ourselves when creating or joining a call, and grant to whoever joins
     * a call we created (its `external_permissions`): tde2e gives every member add+remove, so anyone
     * can join, and any member can prune the members that left.
     */
    public const PERMISSIONS_MEMBER = self::PERMISSION_ADD_USERS | self::PERMISSION_REMOVE_USERS;
    /** The highest protocol version we support, announced in our e2e.chain.groupParticipant. */
    public const PROTOCOL_VERSION = 1;

    private BlockCodec $codec;

    /** Our own user id. */
    private int $selfUserId;
    /** Our Ed25519 signing seed (private key). */
    private string $selfSeed;
    /** Our Ed25519 public key. */
    private string $selfPublicKey;

    /** Height of the last applied block, -1 before the genesis block. */
    private int $height = -1;
    /** Hash of the last applied block, 32 zero bytes before the genesis block. */
    private string $lastBlockHash;
    /**
     * Current participants, `user_id => ['public_key' => string, 'permissions' => int, 'version' => int]`.
     *
     * @var array<int, array{public_key: string, permissions: int, version: int}>
     */
    private array $participants = [];
    /** The current raw (pre-derivation) group shared key, or null if none is set. */
    private ?string $rawSharedKey = null;
    /**
     * The root hash of the key-value trie after the last applied block. We never set values, so it
     * only changes if another participant does: the state proof of every block we build must repeat it.
     */
    private string $kvHash = self::EMPTY_KV_HASH;

    public function __construct(int $selfUserId, string $selfSeed)
    {
        $this->codec = new BlockCodec();
        $this->selfUserId = $selfUserId;
        $this->selfSeed = $selfSeed;
        $this->selfPublicKey = Crypto::publicKey($selfSeed);
        $this->lastBlockHash = str_repeat("\0", 32);
    }

    public function getSelfPublicKey(): string
    {
        return $this->selfPublicKey;
    }

    public function getLastBlockHash(): string
    {
        return $this->lastBlockHash;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * The group shared key actually used to encrypt media, derived from the raw key and the current
     * block hash (protocol version >= 1), or null if no shared key is set.
     *
     * @psalm-mutation-free
     */
    public function getGroupKey(): ?string
    {
        if ($this->rawSharedKey === null) {
            return null;
        }
        // Protocol version 0 (what tde2e-based clients announce) uses the raw key as is; the hashed
        // key is only used once every participant supports version 1, as tde2e's Call does.
        return $this->getVersion() >= 1 ? Crypto::deriveGroupKey($this->rawSharedKey, $this->lastBlockHash) : $this->rawSharedKey;
    }

    /**
     * The protocol version in effect: the smallest version announced by a participant, clamped to
     * 0..255 (0 for an empty group), as tde2e's GroupState::version().
     *
     * @psalm-mutation-free
     */
    public function getVersion(): int
    {
        if ($this->participants === []) {
            return 0;
        }
        $version = PHP_INT_MAX;
        foreach ($this->participants as $participant) {
            $version = min($version, $participant['version']);
        }
        return max(0, min(255, $version));
    }

    /**
     * Build and sign the genesis (height 0) block that creates the call with ourselves as the sole
     * participant and installs the first shared key: exactly the two changes tde2e's
     * Call::create_zero_block() emits.
     *
     * @param int $externalPermissions What a non-member may do to the chain: tde2e grants add+remove
     *                                 users, which is what lets anyone add themselves by joining.
     *
     * @return array{block: array<string, mixed>, serialized: string, hash: string, raw_shared_key: string}
     */
    public function buildGenesis(int $externalPermissions = self::PERMISSIONS_MEMBER): array
    {
        $participants = [$this->participantChange($this->selfUserId, $this->selfPublicKey, self::PERMISSIONS_MEMBER)];
        $raw = random_bytes(32);
        $changes = [
            [
                '_' => 'e2e.chain.changeSetGroupState',
                'group_state' => [
                    '_' => 'e2e.chain.groupState',
                    'participants' => $participants,
                    'external_permissions' => $externalPermissions,
                ],
            ],
            ['_' => 'e2e.chain.changeSetSharedKey', 'shared_key' => $this->buildSharedKey($raw, [[$this->selfUserId, $this->selfPublicKey]])],
        ];
        $block = $this->buildBlock(0, str_repeat("\0", 32), $changes);
        return [
            'block' => $block['block'],
            'serialized' => $block['serialized'],
            'hash' => $block['hash'],
            'raw_shared_key' => $raw,
        ];
    }

    /**
     * Build, sign, hash and serialize a block at the given height with the given changes, using our
     * key. Does not apply it — the caller submits it and applies only the server's echo.
     *
     * @param list<array<string, mixed>> $changes
     *
     * @return array{block: array<string, mixed>, serialized: string, hash: string}
     */
    public function buildBlock(int $height, string $prevBlockHash, array $changes): array
    {
        $block = [
            '_' => 'e2e.chain.block',
            'signature' => BlockCodec::ZERO_SIGNATURE,
            'prev_block_hash' => $prevBlockHash,
            'changes' => $changes,
            'height' => $height,
            // A block that changes the group state (every block we build) omits the group state and
            // the shared key from its proof; only the key-value root hash is carried (tde2e build_block).
            'state_proof' => ['_' => 'e2e.chain.stateProof', 'kv_hash' => $this->kvHash],
            'signature_public_key' => $this->selfPublicKey,
        ];
        $signed = $this->sign($block);
        $serialized = $this->codec->serialize($signed);
        return ['block' => $signed, 'serialized' => $serialized, 'hash' => $this->codec->blockHash($serialized)];
    }

    /**
     * Sign a block in place: zero its signature, serialize, Ed25519-sign that payload with our seed,
     * and store the signature. The signer public key is kept explicit here (the "omit if equal to the
     * first participant" optimization is left to a later pass, as it only shrinks the block).
     *
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>
     */
    public function sign(array $block): array
    {
        $block['signature'] = Crypto::sign($this->selfSeed, $this->codec->signingPayload($block));
        return $block;
    }

    /**
     * Verify a block's Ed25519 signature against its declared (or inherited) signer public key.
     *
     * @param array<string, mixed> $block
     */
    public function verify(array $block): bool
    {
        $signerKey = $block['signature_public_key'] ?? ($this->participants[array_key_first($this->participants) ?? -1]['public_key'] ?? null);
        if (!\is_string($signerKey)) {
            return false;
        }
        return Crypto::verify($block['signature'], $this->codec->signingPayload($block), $signerKey);
    }

    /**
     * Apply a block the server echoed back, updating our view of the group state and shared key. The
     * server has already validated it; we recover state and, when a shared key targets us, decrypt it.
     *
     * @param string $serialized The block bytes exactly as the server returned them (server id).
     *
     * @return bool Whether the block was the next one and was applied (false = duplicate/out of order).
     */
    public function applyServerBlock(string $serialized): bool
    {
        $block = $this->codec->deserialize($serialized);
        // Apply strictly in order and exactly once: the same block may reach us from both the push
        // update and the polling backstop. A block that is not the next one is ignored (the server
        // enforces the ordering, so this is not an error, just a duplicate/out-of-order delivery).
        if ((int) $block['height'] !== $this->height + 1) {
            return false;
        }
        // Hash the block in its canonical (local-id) form, as tde2e does.
        $canonical = $this->codec->serialize($block);
        foreach ($block['changes'] ?? [] as $change) {
            $this->applyChange($change);
        }
        $this->height = (int) $block['height'];
        $this->lastBlockHash = $this->codec->blockHash($canonical);
        $kvHash = (string) ($block['state_proof']['kv_hash'] ?? '');
        if (\strlen($kvHash) === 32) {
            $this->kvHash = $kvHash;
        }
        return true;
    }

    /**
     * @param array<string, mixed> $change
     */
    private function applyChange(array $change): void
    {
        switch ($change['_'] ?? '') {
            case 'e2e.chain.changeSetGroupState':
                $this->participants = [];
                foreach ($change['group_state']['participants'] ?? [] as $participant) {
                    $permissions = 0;
                    $permissions |= ($participant['add_users'] ?? false) ? self::PERMISSION_ADD_USERS : 0;
                    $permissions |= ($participant['remove_users'] ?? false) ? self::PERMISSION_REMOVE_USERS : 0;
                    $permissions |= ($participant['set_value'] ?? false) ? self::PERMISSION_SET_VALUE : 0;
                    $this->participants[(int) $participant['user_id']] = [
                        'public_key' => (string) $participant['public_key'],
                        'permissions' => $permissions,
                        'version' => (int) ($participant['version'] ?? 0),
                    ];
                }
                // A group-state change clears the shared key, as in tde2e.
                $this->rawSharedKey = null;
                break;
            case 'e2e.chain.changeSetSharedKey':
                $this->rawSharedKey = $this->decryptSharedKey($change['shared_key']);
                break;
        }
    }

    /**
     * Build an e2e.chain.sharedKey distributing a raw group key to each participant: an ephemeral key
     * agrees a secret with every participant, which wraps the one-time secret that in turn encrypts
     * the raw key.
     *
     * @param string                       $raw          The 32-byte raw group key.
     * @param list<array{0: int, 1: string}> $recipients `[user_id, public_key]` for every participant.
     *
     * @return array<string, mixed>
     */
    public function buildSharedKey(string $raw, array $recipients): array
    {
        [$ephemeralSeed, $ephemeralPublic] = Crypto::generateKeyPair();
        $oneTime = random_bytes(32);
        [$encryptedGroupKey] = Crypto::encryptData($raw, $oneTime);
        $destUserIds = [];
        $destHeaders = [];
        foreach ($recipients as [$userId, $publicKey]) {
            $shared = Crypto::computeSharedSecret($ephemeralSeed, $publicKey);
            $destUserIds[] = $userId;
            $destHeaders[] = Crypto::encryptHeader($oneTime, $encryptedGroupKey, $shared);
        }
        return [
            '_' => 'e2e.chain.sharedKey',
            'ek' => $ephemeralPublic,
            'encrypted_shared_key' => $encryptedGroupKey,
            'dest_user_id' => $destUserIds,
            'dest_header' => $destHeaders,
        ];
    }

    /**
     * Recover the raw group key from an e2e.chain.sharedKey addressed to us, or null if we are not a
     * recipient.
     *
     * @param array<string, mixed> $sharedKey
     */
    public function decryptSharedKey(array $sharedKey): ?string
    {
        $index = array_search($this->selfUserId, $sharedKey['dest_user_id'] ?? [], true);
        if ($index === false) {
            return null;
        }
        // Deserialized `bytes`/`string` fields may arrive as TL Bytes objects; coerce to raw strings.
        $ek = (string) $sharedKey['ek'];
        $encryptedGroupKey = (string) $sharedKey['encrypted_shared_key'];
        $header = (string) $sharedKey['dest_header'][$index];
        $shared = Crypto::computeSharedSecret($this->selfSeed, $ek);
        $oneTime = Crypto::decryptHeader($header, $encryptedGroupKey, $shared);
        $raw = Crypto::decryptData($encryptedGroupKey, $oneTime);
        return \strlen($raw) === 32 ? $raw : null;
    }

    /**
     * Build an e2e.chain.changeSetGroupState from a participant list.
     *
     * @param list<array{0: int, 1: string, 2: int, 3?: int}> $participants `[user_id, public_key, permissions, version]`; the version defaults to ours.
     *
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    public function groupStateChange(array $participants, int $externalPermissions = self::PERMISSIONS_MEMBER): array
    {
        $list = [];
        foreach ($participants as $participant) {
            [$userId, $publicKey, $permissions] = $participant;
            $list[] = $this->participantChange($userId, $publicKey, $permissions, $participant[3] ?? self::PROTOCOL_VERSION);
        }
        return [
            '_' => 'e2e.chain.changeSetGroupState',
            'group_state' => [
                '_' => 'e2e.chain.groupState',
                'participants' => $list,
                'external_permissions' => $externalPermissions,
            ],
        ];
    }

    /**
     * @return array<string, mixed> An e2e.chain.groupParticipant.
     *
     * @psalm-pure
     */
    private function participantChange(int $userId, string $publicKey, int $permissions, int $version = self::PROTOCOL_VERSION): array
    {
        return [
            '_' => 'e2e.chain.groupParticipant',
            'user_id' => $userId,
            'public_key' => $publicKey,
            'add_users' => ($permissions & self::PERMISSION_ADD_USERS) !== 0,
            'remove_users' => ($permissions & self::PERMISSION_REMOVE_USERS) !== 0,
            'set_value' => ($permissions & self::PERMISSION_SET_VALUE) !== 0,
            'version' => $version,
        ];
    }

    /**
     * The current participants as `[user_id, public_key, permissions, version]` tuples, ready to be
     * carried over unchanged into the next group state.
     *
     * @return list<array{0: int, 1: string, 2: int, 3: int}>
     *
     * @psalm-mutation-free
     */
    public function getParticipantTuples(): array
    {
        $tuples = [];
        foreach ($this->participants as $userId => $info) {
            $tuples[] = [$userId, $info['public_key'], $info['permissions'], $info['version']];
        }
        return $tuples;
    }

    /**
     * Our own permission bits in the current group state, or 0 if we are not (yet) a member.
     *
     * @psalm-mutation-free
     */
    public function getSelfPermissions(): int
    {
        return $this->participants[$this->selfUserId]['permissions'] ?? 0;
    }

    /**
     * @return array<int, array{public_key: string, permissions: int, version: int}>
     */
    public function getParticipants(): array
    {
        return $this->participants;
    }
}
