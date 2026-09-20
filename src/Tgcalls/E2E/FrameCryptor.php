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

use Webrtc\RTP\Crypto\FrameCryptorInterface;
use Webrtc\RTP\Enum\MediaKind;

/**
 * End-to-end frame encryptor/decryptor for an encrypted conference call: wraps each media frame with
 * {@see CallPacket} using the conference's active epoch keys, and verifies the sender on the way in.
 *
 * Plugged into php-rtc's RTP senders and receivers ({@see FrameCryptorInterface}). It is a plain
 * serializable object, so it survives a serialize/resume cycle along with the rest of the call.
 *
 * @internal
 */
final class FrameCryptor implements FrameCryptorInterface
{
    /** Outgoing sequence number per channel, unique and monotonically increasing. */
    private array $seqno = [];
    /**
     * Replay windows per `"<senderPubKeyHex>:<channel>"`: the set of recently seen sequence numbers.
     *
     * @var array<string, array<int, true>>
     */
    private array $seen = [];
    private const REPLAY_WINDOW = 1024;

    /**
     * @param int $audioChannel Channel id for outgoing audio (0 is reserved for in-call messages).
     * @param int $videoChannel Channel id for outgoing video; a screen-share connection uses a
     *                          distinct one from the camera so their sequence numbers never collide.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly E2EKeyProvider $keys,
        private readonly int $audioChannel = 1,
        private readonly int $videoChannel = 2,
    ) {
    }

    #[\Override]
    public function encryptFrame(MediaKind $kind, int $ssrc, string $frame): string
    {
        $epochs = $this->keys->activeEpochs();
        if ($epochs === []) {
            // No key yet (chain not established): send in the clear rather than drop media.
            return $frame;
        }
        $channel = $this->channel($kind);
        $seqno = ($this->seqno[$channel] ?? 0) + 1;
        $this->seqno[$channel] = $seqno;
        return CallPacket::encrypt($channel, $seqno, $frame, $epochs, $this->keys->selfSeed());
    }

    #[\Override]
    public function decryptFrame(MediaKind $kind, int $ssrc, string $frame): string
    {
        $senderKey = $this->keys->publicKeyForSsrc($ssrc);
        if ($senderKey === null) {
            throw new \RuntimeException("No public key for the sender of SSRC $ssrc");
        }
        $secrets = [];
        foreach ($this->keys->activeEpochs() as $epoch) {
            $secrets[$epoch['hash']] = $epoch['secret'];
        }
        $decoded = CallPacket::decrypt($frame, $secrets, $senderKey);
        $this->checkReplay(bin2hex($senderKey), $decoded['channel_id'], $decoded['seqno']);
        return $decoded['payload'];
    }

    /**
     * @psalm-mutation-free
     */
    private function channel(MediaKind $kind): int
    {
        return $kind === MediaKind::Video ? $this->videoChannel : $this->audioChannel;
    }

    /**
     * Reject a replayed or too-old sequence number for a (sender, channel), and remember it.
     *
     * @psalm-external-mutation-free
     */
    private function checkReplay(string $senderHex, int $channel, int $seqno): void
    {
        $key = "$senderHex:$channel";
        $window = $this->seen[$key] ?? [];
        if (isset($window[$seqno])) {
            throw new \RuntimeException('Replayed conference packet');
        }
        if ($window !== []) {
            $min = min(array_keys($window));
            if ($seqno < $min && \count($window) >= self::REPLAY_WINDOW) {
                throw new \RuntimeException('Conference packet too old');
            }
        }
        $window[$seqno] = true;
        if (\count($window) > self::REPLAY_WINDOW) {
            // Drop the oldest to bound memory.
            $oldest = min(array_keys($window));
            unset($window[$oldest]);
        }
        $this->seen[$key] = $window;
    }
}
