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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\Tgcalls\E2E\BlockCodec;
use danog\MadelineProto\Tgcalls\E2E\Crypto;
use PHPUnit\Framework\TestCase;

/**
 * Tests the end-to-end encrypted conference-call foundation: the chain block TL codec and the tde2e
 * cryptographic primitives. The block ids and byte layouts are effectively wire protocol — a block
 * whose serialization differs from TDLib's by a single byte hashes differently and is rejected by
 * the server and every peer.
 *
 * @internal
 */
final class E2EConferenceTest extends TestCase
{
    /**
     * The chain constructor ids MadelineProto computes must match TDLib's (CRC32 of the same
     * definitions), or block hashing and signing break.
     */
    public function testConstructorIds(): void
    {
        $codec = new BlockCodec();
        // A block serializes to bytes whose first int32 is the canonical id 0x639a3db6 (LE).
        $block = self::sampleBlock();
        $serialized = $codec->serialize($block);
        $this->assertSame(0x639a3db6, unpack('V', substr($serialized, 0, 4))[1]);
    }

    /**
     * A block survives a serialize/deserialize round-trip byte-for-byte, so the hash is stable.
     */
    public function testBlockRoundTrip(): void
    {
        $codec = new BlockCodec();
        $block = self::sampleBlock();
        $serialized = $codec->serialize($block);
        $decoded = $codec->deserialize($serialized);
        $this->assertSame('e2e.chain.block', $decoded['_']);
        $this->assertSame($serialized, $codec->serialize($decoded));
        $this->assertSame(32, \strlen($codec->blockHash($serialized)));
    }

    /**
     * The server returns blocks with the constructor id incremented by one; the codec accepts them.
     */
    public function testServerIncrementedId(): void
    {
        $codec = new BlockCodec();
        $serialized = $codec->serialize(self::sampleBlock());
        $fromServer = pack('V', 0x639a3db7).substr($serialized, 4);
        $this->assertSame('e2e.chain.block', $codec->deserialize($fromServer)['_']);
    }

    /**
     * The signing payload zeroes the 64-byte signature field.
     */
    public function testSigningPayloadZeroesSignature(): void
    {
        $codec = new BlockCodec();
        $block = self::sampleBlock();
        $block['signature'] = str_repeat("\x7f", 64);
        $this->assertSame(BlockCodec::ZERO_SIGNATURE, substr($codec->signingPayload($block), 4, 64));
    }

    public function testEncryptDataRoundTrip(): void
    {
        $this->requireCrypto();
        $secret = random_bytes(32);
        [$enc] = Crypto::encryptData('a payload of some length', $secret, 'authenticated extra');
        $this->assertSame('a payload of some length', Crypto::decryptData($enc, $secret, 'authenticated extra'));
    }

    public function testEncryptDataRejectsTamperedExtra(): void
    {
        $this->requireCrypto();
        $secret = random_bytes(32);
        [$enc] = Crypto::encryptData('payload', $secret, 'extra');
        $this->expectException(\RuntimeException::class);
        Crypto::decryptData($enc, $secret, 'different-extra');
    }

    public function testEncryptHeaderRoundTrip(): void
    {
        $this->requireCrypto();
        $secret = random_bytes(32);
        [$enc] = Crypto::encryptData('payload', $secret);
        $header = random_bytes(32);
        $wrapped = Crypto::encryptHeader($header, $enc, $secret);
        $this->assertSame(32, \strlen($wrapped));
        $this->assertSame($header, Crypto::decryptHeader($wrapped, $enc, $secret));
    }

    public function testEd25519SignVerify(): void
    {
        $this->requireCrypto();
        [$seed, $public] = Crypto::generateKeyPair();
        $signature = Crypto::sign($seed, 'message');
        $this->assertTrue(Crypto::verify($signature, 'message', $public));
        $this->assertFalse(Crypto::verify($signature, 'tampered', $public));
    }

    public function testDiffieHellmanAgreement(): void
    {
        $this->requireCrypto();
        [$seedA, $publicA] = Crypto::generateKeyPair();
        [$seedB, $publicB] = Crypto::generateKeyPair();
        $this->assertSame(
            Crypto::computeSharedSecret($seedA, $publicB),
            Crypto::computeSharedSecret($seedB, $publicA)
        );
    }

    /**
     * The full shared-key distribution flow: distribute the raw group key to a participant with an
     * ephemeral key, and recover it from the participant's side — exactly what a SetSharedKey change
     * carries.
     */
    public function testSharedKeyDistributionRecovery(): void
    {
        $this->requireCrypto();
        [$participantSeed, $participantPublic] = Crypto::generateKeyPair();
        $raw = random_bytes(32);
        $oneTime = random_bytes(32);
        [$ephemeralSeed, $ephemeralPublic] = Crypto::generateKeyPair();

        [$encryptedGroupKey] = Crypto::encryptData($raw, $oneTime);
        $toParticipant = Crypto::computeSharedSecret($ephemeralSeed, $participantPublic);
        $header = Crypto::encryptHeader($oneTime, $encryptedGroupKey, $toParticipant);

        // Participant side.
        $fromEphemeral = Crypto::computeSharedSecret($participantSeed, $ephemeralPublic);
        $recoveredOneTime = Crypto::decryptHeader($header, $encryptedGroupKey, $fromEphemeral);
        $recoveredRaw = Crypto::decryptData($encryptedGroupKey, $recoveredOneTime);
        $this->assertSame($raw, $recoveredRaw);
        $this->assertSame(32, \strlen(Crypto::deriveGroupKey($recoveredRaw, str_repeat("\x01", 32))));
    }

    private function requireCrypto(): void
    {
        if (!Crypto::available()) {
            $this->markTestSkipped('The sodium and openssl extensions are required for E2E conference cryptography.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function sampleBlock(): array
    {
        $zero = str_repeat("\0", 32);
        return [
            '_' => 'e2e.chain.block',
            'signature' => BlockCodec::ZERO_SIGNATURE,
            'prev_block_hash' => $zero,
            'changes' => [
                [
                    '_' => 'e2e.chain.changeSetGroupState',
                    'group_state' => [
                        '_' => 'e2e.chain.groupState',
                        'participants' => [[
                            '_' => 'e2e.chain.groupParticipant',
                            'user_id' => 42,
                            'public_key' => str_repeat("\x11", 32),
                            'add_users' => true,
                            'remove_users' => true,
                            'version' => 1,
                        ]],
                        'external_permissions' => 3,
                    ],
                ],
                ['_' => 'e2e.chain.changeNoop', 'nonce' => str_repeat("\x22", 32)],
            ],
            'height' => 0,
            'state_proof' => ['_' => 'e2e.chain.stateProof', 'kv_hash' => $zero],
        ];
    }
}
