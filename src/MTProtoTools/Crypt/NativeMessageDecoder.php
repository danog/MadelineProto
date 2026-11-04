<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;

/** @internal */
final class NativeMessageDecoder implements MessageDecoder
{
    /** At most this long between two yields to the event loop of the synchronous decodes. */
    private const YIELD_INTERVAL_NS = 1_000_000;
    private static int $lastYieldNs = 0;

    /**
     * An executor decode completes in a later loop tick, letting the timers run in between; a synchronous one
     * doesn't, so the read loop would chain the dispatch of every message it reads into a single tick (upload
     * timers 0.5-1 ms late at p99). Yielding once per millisecond restores those tick boundaries without
     * a loop iteration per message (which cost 10% of the CPU of RPC bursts).
     */
    private static function yieldToLoop(): void
    {
        $now = hrtime(true);
        if ($now - self::$lastYieldNs < self::YIELD_INTERVAL_NS) {
            return;
        }
        self::$lastYieldNs = $now;
        $suspension = EventLoop::getSuspension();
        EventLoop::defer($suspension->resume(...));
        $suspension->suspend();
    }

    #[\Override]
    public function decode(string $ciphertext, string $authKey, string $messageKey): DecodedMessage
    {
        if (\strlen($authKey) !== 256 || \strlen($messageKey) !== 16
            || \strlen($ciphertext) < 48 || \strlen($ciphertext) % 16) {
            throw new SecurityException('Invalid encrypted transport message size');
        }
        if (\strlen($ciphertext) <= Crypt::NATIVE_SYNC_MAX && Crypt::native()) {
            // The executor's Decode on the PHP thread: same checks, same failures.
            try {
                $decoded = self::decoded(\danog\MadelineProto\Native\Crypto::decodeSync($ciphertext, $authKey, $messageKey));
                self::yieldToLoop();
                return $decoded;
            } catch (\Exception $e) {
                if (NativeExecutor::failedWith($e, NativeExecutor::INVALID)) {
                    throw new SecurityException($e->getMessage(), 0, $e);
                }
                if (!NativeExecutor::failedWith($e, NativeExecutor::UNAVAILABLE)) {
                    throw $e;
                }
            }
            return (new PhpMessageDecoder)->decode($ciphertext, $authKey, $messageKey);
        }
        $executor = NativeExecutor::get();
        if ($executor) {
            try {
                $result = $executor->run(static fn (): array => \danog\MadelineProto\Native\Crypto::decode($ciphertext, $authKey, $messageKey));
                return self::decoded($result);
            } catch (\Exception $e) {
                if (NativeExecutor::failedWith($e, NativeExecutor::INVALID)) {
                    throw new SecurityException($e->getMessage(), 0, $e);
                }
                if (!NativeExecutor::failedWith($e, NativeExecutor::UNAVAILABLE)) {
                    throw $e;
                }
            }
        }
        // Not decoded natively (no executor, or it was closed or could not take the message).
        return (new PhpMessageDecoder)->decode($ciphertext, $authKey, $messageKey);
    }

    /** @param array{string, string} $result The body, and the salt, message ID and seqno. */
    private static function decoded(array $result): DecodedMessage
    {
        return new DecodedMessage($result[0], Tools::unpackSignedLong(substr($result[1], 8, 8)), (int) unpack('V', substr($result[1], 16, 4))[1], substr($result[1], 0, 8));
    }
}
