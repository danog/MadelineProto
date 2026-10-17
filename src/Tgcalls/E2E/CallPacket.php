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
 * Frame-level packet encryption for end-to-end encrypted conference calls, ported from TDLib's tde2e
 * CallEncryption (Call.cpp). Media is encrypted per packet on top of WebRTC (this is *not* SRTP): each
 * packet is bound to the set of currently active epochs (by their block hash) and signed by the
 * sender, so any participant with the epoch key can decrypt it and every participant can verify who
 * sent it.
 *
 * This class is the pure codec; wiring it into the RTP send/receive path (encrypting each outgoing
 * payload, decrypting each incoming one, keyed by the current epochs) is done by the call engine.
 *
 * @internal
 */
final class CallPacket
{
    /** TL id of e2e.callPacket, mixed into the encrypted-data authenticated extra (magic1). */
    private const MAGIC_PACKET = 0x40a6bee9;
    /** TL id of e2e.callPacketLargeMsgId, prefixed to the signed message (magic2). */
    private const MAGIC_LARGE_MSG_ID = 0x1ce56c2d;
    /** Maximum simultaneously active epochs (tde2e MAX_ACTIVE_EPOCHS). */
    public const MAX_ACTIVE_EPOCHS = 15;
    /** Valid channel-id range (tde2e validate_channel_id). */
    public const MAX_CHANNEL_ID = 1023;

    /**
     * Encrypt one packet for the given channel and active epochs, signed with our key.
     *
     * @param int                                    $channelId  0..1023.
     * @param int                                    $seqno      Unique, monotonically increasing per (us, channel).
     * @param string                                 $plaintext  The bytes to encrypt (e.g. an RTP payload).
     * @param list<array{hash: string, secret: string}> $epochs   Active epochs: block hash + group key.
     * @param string                                 $seed       Our Ed25519 signing seed.
     * @param string                                 $unencryptedPrefix Bytes kept in the clear at the front.
     */
    public static function encrypt(int $channelId, int $seqno, string $plaintext, array $epochs, string $seed, string $unencryptedPrefix = ''): string
    {
        if ($channelId < 0 || $channelId > self::MAX_CHANNEL_ID) {
            throw new \InvalidArgumentException('channel id out of range');
        }
        if ($epochs === [] || \count($epochs) > self::MAX_ACTIVE_EPOCHS) {
            throw new \InvalidArgumentException('need 1..'.self::MAX_ACTIVE_EPOCHS.' active epochs');
        }
        $count = \count($epochs);
        // head int32 = count | version(0)<<8 | reserved(0)<<16; then one block hash per epoch.
        $headerA = pack('V', $count);
        foreach ($epochs as $epoch) {
            $headerA .= $epoch['hash'];
        }

        $packetPayload = pack('l', $channelId).pack('V', $seqno).$plaintext;
        $oneTimeKey = random_bytes(32);
        $extra = pack('V', self::MAGIC_PACKET).$headerA.$unencryptedPrefix;
        [$encryptedPayload, $largeMsgId] = Crypto::encryptData($packetPayload, $oneTimeKey, $extra);

        $signature = Crypto::sign($seed, pack('V', self::MAGIC_LARGE_MSG_ID).$largeMsgId);

        $headerB = '';
        foreach ($epochs as $epoch) {
            $headerB .= Crypto::encryptHeader($oneTimeKey, $encryptedPayload, $epoch['secret']);
        }

        return $unencryptedPrefix.$headerA.$headerB.$encryptedPayload.$signature.pack('V', \strlen($unencryptedPrefix));
    }

    /**
     * Decrypt and verify a packet.
     *
     * @param string                          $packet          The wire packet.
     * @param array<string, string>           $epochSecrets    Group key by epoch block hash (hex or raw key by raw hash).
     * @param string                          $senderPublicKey The sender's Ed25519 public key, for signature verification.
     *
     * @return array{channel_id: int, seqno: int, payload: string, unencrypted_prefix: string}
     */
    public static function decrypt(string $packet, array $epochSecrets, string $senderPublicKey): array
    {
        if (\strlen($packet) < 4) {
            throw new \RuntimeException('packet too short');
        }
        $prefixLen = unpack('V', substr($packet, -4))[1];
        if ($prefixLen > \strlen($packet) - 4) {
            throw new \RuntimeException('bad unencrypted prefix length');
        }
        $unencryptedPrefix = substr($packet, 0, $prefixLen);
        $body = substr($packet, $prefixLen, \strlen($packet) - $prefixLen - 4);

        if (\strlen($body) < 4) {
            throw new \RuntimeException('packet body too short');
        }
        $head = unpack('V', substr($body, 0, 4))[1];
        $count = $head & 0xff;
        if ((($head >> 8) & 0xff) !== 0 || ($head >> 16) !== 0) {
            throw new \RuntimeException('unsupported packet version');
        }
        if ($count < 1 || $count > self::MAX_ACTIVE_EPOCHS) {
            throw new \RuntimeException('bad epoch count');
        }
        $headerA = substr($body, 0, 4 + $count * 32);
        $offset = 4;
        $epochHashes = [];
        for ($i = 0; $i < $count; $i++) {
            $epochHashes[] = substr($body, $offset, 32);
            $offset += 32;
        }
        $headerB = [];
        for ($i = 0; $i < $count; $i++) {
            $headerB[] = substr($body, $offset, 32);
            $offset += 32;
        }
        $encryptedPacket = substr($body, $offset);
        if (\strlen($encryptedPacket) < 64 + 16) {
            throw new \RuntimeException('packet payload too short');
        }
        $signature = substr($encryptedPacket, -64);
        $encryptedPayload = substr($encryptedPacket, 0, -64);

        $extra = pack('V', self::MAGIC_PACKET).$headerA.$unencryptedPrefix;
        foreach ($epochHashes as $i => $hash) {
            $secret = $epochSecrets[$hash] ?? null;
            if ($secret === null) {
                continue;
            }
            $oneTimeKey = Crypto::decryptHeader($headerB[$i], $encryptedPayload, $secret);
            [$packetPayload, $largeMsgId] = Crypto::decryptDataWithMsgId($encryptedPayload, $oneTimeKey, $extra);
            if (!Crypto::verify($signature, pack('V', self::MAGIC_LARGE_MSG_ID).$largeMsgId, $senderPublicKey)) {
                throw new \RuntimeException('bad packet signature');
            }
            if (\strlen($packetPayload) < 8) {
                throw new \RuntimeException('decrypted packet too short');
            }
            $channelId = unpack('l', substr($packetPayload, 0, 4))[1];
            $seqno = unpack('V', substr($packetPayload, 4, 4))[1];
            return [
                'channel_id' => $channelId,
                'seqno' => $seqno,
                'payload' => substr($packetPayload, 8),
                'unencrypted_prefix' => $unencryptedPrefix,
            ];
        }
        throw new \RuntimeException('no known epoch for packet');
    }
}
