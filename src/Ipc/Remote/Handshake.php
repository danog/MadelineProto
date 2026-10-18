<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Ipc\Remote;

use Amp\ByteStream\BufferedReader;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Exception;
use Throwable;

/**
 * Handshake of remote IPC connections.
 *
 * - client → server: magic, channel type (1 byte), 32-byte client nonce
 * - server → client: magic, 32-byte server nonce
 * - both derive prk = HMAC-SHA256(secret, magic || type || client nonce || server nonce)
 * - client → server: HMAC(prk, "client proof"); if it's wrong, the server sends 32 zero bytes and closes the connection
 * - server → client: HMAC(prk, "server proof"); the client aborts if it's wrong
 * - records are then encrypted with HMAC(prk, "client key") and HMAC(prk, "server key") (see EncryptedChannel).
 *
 * Both nonces are fresh, so proofs can't be replayed, and nothing is unserialized before both sides are
 * authenticated.
 *
 * @internal
 */
final class Handshake
{
    /**
     * Main IPC channel.
     */
    public const TYPE_MAIN = 0;
    /**
     * Callback IPC channel.
     */
    public const TYPE_CALLBACK = 1;

    private const MAGIC = "MPIPC\x01";
    private const NONCE_LENGTH = 32;
    private const TIMEOUT = 10.0;
    /**
     * Sent instead of the server proof if the client proof is wrong.
     */
    private const REJECTED = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    /**
     * Run the handshake as a client.
     *
     * @param self::TYPE_* $type
     */
    public static function client(ReadableStream&WritableStream $socket, string $secret, int $type, ?Cancellation $cancellation = null): EncryptedChannel
    {
        try {
            $cancellation ??= new TimeoutCancellation(self::TIMEOUT, 'Timeout during the IPC handshake');
            $reader = new BufferedReader($socket);
            $clientNonce = random_bytes(self::NONCE_LENGTH);
            $socket->write(self::MAGIC.\chr($type).$clientNonce);

            $magic = $reader->readLength(\strlen(self::MAGIC), $cancellation);
            if ($magic !== self::MAGIC) {
                throw new Exception('The remote endpoint is not a MadelineProto IPC server!');
            }
            $serverNonce = $reader->readLength(self::NONCE_LENGTH, $cancellation);
            $prk = hash_hmac('sha256', self::MAGIC.\chr($type).$clientNonce.$serverNonce, $secret, true);

            $socket->write(hash_hmac('sha256', 'client proof', $prk, true));
            $proof = $reader->readLength(32, $cancellation);
            if ($proof === self::REJECTED) {
                throw new AuthenticationException('The IPC server rejected our IPC secret: is it the same on both sides?');
            }
            if (!hash_equals(hash_hmac('sha256', 'server proof', $prk, true), $proof)) {
                throw new AuthenticationException('The IPC server could not be authenticated: is the IPC secret the same on both sides?');
            }
        } catch (Throwable $e) {
            $socket->close();
            if ($e instanceof Exception) {
                throw $e;
            }
            throw new Exception('The IPC handshake failed (is the IPC secret the same on both sides?): '.$e->getMessage(), 0, $e);
        }
        return new EncryptedChannel(
            $socket,
            $reader,
            hash_hmac('sha256', 'client key', $prk, true),
            hash_hmac('sha256', 'server key', $prk, true),
        );
    }

    /**
     * Run the handshake as a server.
     *
     * @return array{EncryptedChannel, self::TYPE_*}
     */
    public static function server(ReadableStream&WritableStream $socket, string $secret, ?Cancellation $cancellation = null): array
    {
        try {
            $cancellation ??= new TimeoutCancellation(self::TIMEOUT, 'Timeout during the IPC handshake');
            $reader = new BufferedReader($socket);
            $magic = $reader->readLength(\strlen(self::MAGIC), $cancellation);
            if ($magic !== self::MAGIC) {
                throw new Exception('Not a MadelineProto IPC client!');
            }
            $type = \ord($reader->readLength(1, $cancellation));
            if ($type !== self::TYPE_MAIN && $type !== self::TYPE_CALLBACK) {
                throw new Exception("Unknown IPC channel type $type!");
            }
            $clientNonce = $reader->readLength(self::NONCE_LENGTH, $cancellation);
            $serverNonce = random_bytes(self::NONCE_LENGTH);
            $socket->write(self::MAGIC.$serverNonce);
            $prk = hash_hmac('sha256', self::MAGIC.\chr($type).$clientNonce.$serverNonce, $secret, true);

            $proof = $reader->readLength(32, $cancellation);
            if (!hash_equals(hash_hmac('sha256', 'client proof', $prk, true), $proof)) {
                // Tell the client, so that it doesn't keep retrying.
                $socket->write(self::REJECTED);
                throw new AuthenticationException('The IPC client could not be authenticated (wrong IPC secret)!');
            }
            $socket->write(hash_hmac('sha256', 'server proof', $prk, true));
        } catch (Throwable $e) {
            $socket->close();
            if ($e instanceof Exception) {
                throw $e;
            }
            throw new Exception('The IPC handshake failed: '.$e->getMessage(), 0, $e);
        }
        return [
            new EncryptedChannel(
                $socket,
                $reader,
                hash_hmac('sha256', 'server key', $prk, true),
                hash_hmac('sha256', 'client key', $prk, true),
            ),
            $type,
        ];
    }
}
