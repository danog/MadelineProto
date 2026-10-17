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
 * The per-call packet counters shared by every {@see FrameCryptor} of a conference: the outgoing
 * sequence numbers (one per channel, unique and increasing for our key, as the protocol requires —
 * the camera and the screen-share connections must therefore draw from the same counter) and the
 * replay windows of what we received from each sender.
 *
 * Serialized with the call, so that a resumed process keeps counting where it left off instead of
 * re-sending sequence numbers our peers already saw (and would reject as replays).
 *
 * @internal
 */
final class FrameCryptorState
{
    /** How many recent sequence numbers per sender and channel are remembered (tde2e keeps 1024). */
    private const REPLAY_WINDOW = 1024;

    /** @var array<int, int> Last sequence number sent per channel. */
    private array $seqno = [];
    /** @var array<string, array<int, true>> Sequence numbers recently seen per `sender:channel`. */
    private array $seen = [];

    /** The next sequence number to send on a channel. */
    public function nextSeqno(int $channel): int
    {
        $seqno = ($this->seqno[$channel] ?? 0) + 1;
        if ($seqno > 0xFFFFFFFF) {
            // The protocol says a client must leave the call on overflow; four billion frames is
            // far beyond any call's lifetime, so treat it as the fatal error it is.
            throw new \RuntimeException('Conference packet sequence number overflow');
        }
        $this->seqno[$channel] = $seqno;
        return $seqno;
    }

    /**
     * Reject a sequence number already seen (or older than the whole window) from a sender on a
     * channel, and remember it otherwise — the check tde2e's CallEncryption does.
     *
     * @param string $sender The sender's public key (binary).
     */
    public function checkReplay(string $sender, int $channel, int $seqno): void
    {
        $key = bin2hex($sender).":$channel";
        $window = $this->seen[$key] ?? [];
        if ($window !== []) {
            $oldest = array_key_first($window);
            \assert(\is_int($oldest));
            if ($seqno < $oldest) {
                throw new \RuntimeException('Conference packet too old');
            }
            if (isset($window[$seqno])) {
                throw new \RuntimeException('Replayed conference packet');
            }
        }
        $window[$seqno] = true;
        ksort($window);
        // Bound the window: drop the oldest entries beyond the size, and anything a full window behind.
        while (\count($window) > self::REPLAY_WINDOW || ($window !== [] && array_key_first($window) + self::REPLAY_WINDOW < $seqno)) {
            unset($window[array_key_first($window)]);
        }
        $this->seen[$key] = $window;
    }
}
