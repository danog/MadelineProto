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

use danog\MadelineProto\Magic;

/**
 * The commit-reveal emoji verification of an end-to-end encrypted conference call (subchain 1).
 *
 * Each participant commits to a random 32-byte nonce (publishing only its SHA-256), then reveals the
 * nonce. Once every nonce is known, all participants derive the same four verification emojis from
 * `HMAC-SHA512(sorted-concatenated-nonces, last_block_hash)`; matching emojis prove no man in the
 * middle. See {@see \danog\MadelineProto\Tgcalls\E2E\Crypto} and TDLib's tde2e CallVerificationChain.
 *
 * @internal
 */
final class Verification
{
    /**
     * Build the commit-reveal pair for our nonce over the current chain head. The commit is broadcast
     * first (revealing only the hash); the reveal is broadcast once everyone has committed.
     *
     * @return array{nonce: string, commit: array<string, mixed>, reveal: array<string, mixed>}
     */
    public static function makeNonce(int $userId, int $chainHeight, string $chainHash, string $seed): array
    {
        $nonce = random_bytes(32);
        $commit = [
            '_' => 'e2e.chain.groupBroadcastNonceCommit',
            'signature' => BlockCodec::ZERO_SIGNATURE,
            'user_id' => $userId,
            'chain_height' => $chainHeight,
            'chain_hash' => $chainHash,
            'nonce_hash' => Crypto::sha256($nonce),
        ];
        $reveal = [
            '_' => 'e2e.chain.groupBroadcastNonceReveal',
            'signature' => BlockCodec::ZERO_SIGNATURE,
            'user_id' => $userId,
            'chain_height' => $chainHeight,
            'chain_hash' => $chainHash,
            'nonce' => $nonce,
        ];
        return [
            'nonce' => $nonce,
            'commit' => self::sign($commit, $seed),
            'reveal' => self::sign($reveal, $seed),
        ];
    }

    /**
     * Ed25519-sign a broadcast (commit or reveal) over its serialization with the signature zeroed.
     *
     * @param array<string, mixed> $broadcast
     *
     * @return array<string, mixed>
     */
    private static function sign(array $broadcast, string $seed): array
    {
        $codec = new BlockCodec();
        $payload = $broadcast;
        $payload['signature'] = BlockCodec::ZERO_SIGNATURE;
        $broadcast['signature'] = Crypto::sign($seed, $codec->serialize($payload));
        return $broadcast;
    }

    /**
     * Whether a revealed nonce matches a previously committed hash.
     *
     * @psalm-pure
     */
    public static function checkReveal(string $committedHash, string $revealedNonce): bool
    {
        return hash_equals($committedHash, Crypto::sha256($revealedNonce));
    }

    /**
     * The 64-byte emoji hash once every participant's nonce is revealed: the nonces are sorted as raw
     * byte strings, concatenated, and used as the HMAC-SHA512 key over the last block hash.
     *
     * @param list<string> $nonces Every participant's revealed 32-byte nonce.
     *
     * @psalm-pure
     */
    public static function emojiHash(array $nonces, string $lastBlockHash): string
    {
        sort($nonces, SORT_STRING);
        return hash_hmac('sha512', $lastBlockHash, implode('', $nonces), true);
    }

    /**
     * The four verification emojis derived from an emoji hash: each is one of the 333 call emojis,
     * indexed by a big-endian unsigned 64-bit word (top bit cleared) of the hash modulo 333 — the
     * same table and extraction as one-to-one call verification.
     *
     * @return list<string> Four emoji.
     */
    public static function emojis(string $emojiHash): array
    {
        Magic::start(light: true);
        $table = Magic::$emojis;
        $count = \count($table);
        $result = [];
        foreach (str_split(substr($emojiHash, 0, 32), 8) as $word) {
            $word[0] = \chr(\ord($word[0]) & 0x7f); // mask the top bit, like & 0x7fffffffffffffff
            $value = 0;
            for ($i = 0; $i < 8; $i++) {
                $value = $value * 256 + \ord($word[$i]);
            }
            $result[] = $table[$value % $count];
        }
        return $result;
    }
}
