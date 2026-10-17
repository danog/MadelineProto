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

use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;

/**
 * (De)serializer for the TL blocks of an end-to-end encrypted conference call
 * ([end-to-end group calls](https://core.telegram.org/api/end-to-end/group-calls)).
 *
 * The chain blocks travel as opaque `bytes` in the MTProto API, serialized with a small dedicated TL
 * schema ({@see e2e_conference.tl}). The block hash is the SHA-256 of that serialization and a block
 * signature is computed over the serialization with the signature field zeroed, so the byte layout
 * must match TDLib exactly — it does, since both compute the constructor ids as the CRC32 of the same
 * definitions (verified in the test-suite).
 *
 * The server hands blocks back with their constructor id incremented by one, to make it impossible to
 * resubmit a server block as if it were a fresh client block. We accept both ids on the way in and
 * always emit the canonical id on the way out.
 *
 * @internal
 */
final class BlockCodec
{
    /** A 64-byte all-zero signature, as used while hashing/signing a block. */
    public const ZERO_SIGNATURE = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    /**
     * Canonical constructor ids the server returns incremented by one, mapped to the canonical id, so
     * a server block can be parsed with the schema's canonical constructors.
     *
     * @var array<int, int>
     */
    private const SERVER_ID_FIXUPS = [
        0x639a3db7 => 0x639a3db6, // e2e.chain.block
        0xd1512ae8 => 0xd1512ae7, // e2e.chain.groupBroadcastNonceCommit
        0x83f4f9d9 => 0x83f4f9d8, // e2e.chain.groupBroadcastNonceReveal
    ];

    private TL $tl;

    public function __construct()
    {
        $this->tl = new TL();
        $this->tl->init(
            (new TLSchema())
                ->setAPISchema(__DIR__.'/e2e_conference.tl')
                ->setMTProtoSchema('')
                ->setSecretSchema('')
                ->setOther([])
        );
    }

    /**
     * Serialize a chain object (`['_' => 'e2e.chain.block', ...]`, a change, a broadcast, …) to bytes,
     * with the canonical constructor id.
     *
     * @param array<string, mixed> $object
     */
    public function serialize(array $object): string
    {
        $type = self::typeOf($object['_'] ?? '');
        return (string) $this->tl->serializeObject(['type' => $type], $object, $object['_'] ?? 'e2e');
    }

    /**
     * Deserialize a chain object from bytes, accepting the id the server increments by one.
     *
     * @return array<string, mixed>
     */
    public function deserialize(string $bytes): array
    {
        if (\strlen($bytes) >= 4) {
            $id = unpack('V', substr($bytes, 0, 4))[1];
            if (isset(self::SERVER_ID_FIXUPS[$id])) {
                $bytes = pack('V', self::SERVER_ID_FIXUPS[$id]).substr($bytes, 4);
            }
        }
        $stream = fopen('php://memory', 'rw+b');
        \assert($stream !== false);
        fwrite($stream, $bytes);
        rewind($stream);
        /** @var array<string, mixed> */
        return $this->tl->deserialize($stream, ['type' => '', 'connection' => null, 'encrypted' => false]);
    }

    /**
     * The SHA-256 hash of a serialized block, used as its identity and as `prev_block_hash` of the
     * next block.
     *
     * @psalm-pure
     */
    public function blockHash(string $serializedBlock): string
    {
        return hash('sha256', $serializedBlock, true);
    }

    /**
     * The exact bytes a block signature is computed over: the block serialized with its signature
     * field zeroed.
     *
     * @param array<string, mixed> $block An `e2e.chain.block` object; its `signature` is ignored.
     */
    public function signingPayload(array $block): string
    {
        $block['signature'] = self::ZERO_SIGNATURE;
        return $this->serialize($block);
    }

    /**
     * The boxed `e2e.chain.Type` a predicate belongs to, for {@see TL::serializeObject()}.
     *
     * @psalm-pure
     */
    private static function typeOf(string $predicate): string
    {
        return match ($predicate) {
            'e2e.chain.block' => 'e2e.chain.Block',
            'e2e.chain.stateProof' => 'e2e.chain.StateProof',
            'e2e.chain.groupState' => 'e2e.chain.GroupState',
            'e2e.chain.groupParticipant' => 'e2e.chain.GroupParticipant',
            'e2e.chain.sharedKey' => 'e2e.chain.SharedKey',
            'e2e.chain.changeNoop',
            'e2e.chain.changeSetValue',
            'e2e.chain.changeSetGroupState',
            'e2e.chain.changeSetSharedKey' => 'e2e.chain.Change',
            'e2e.chain.groupBroadcastNonceCommit',
            'e2e.chain.groupBroadcastNonceReveal' => 'e2e.chain.GroupBroadcast',
            default => throw new \InvalidArgumentException("Unknown e2e chain predicate: $predicate"),
        };
    }
}
