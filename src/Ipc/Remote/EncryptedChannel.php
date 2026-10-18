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
use Amp\ByteStream\BufferException;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\Ipc\Sync\Channel;
use Amp\Ipc\Sync\ChannelException;
use Amp\Ipc\Sync\SerializationException;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\IpcCapable;
use phpseclib4\Crypt\AES;
use Throwable;
use WeakReference;

/**
 * Authenticated and encrypted IPC channel, used for remote IPC connections.
 *
 * The IPC protocol sends PHP-serialized values, which must never be unserialized if they don't come from an
 * authenticated peer: the handshake (see Handshake) proves that both peers know the shared secret, and derives
 * one AES-256-GCM key per direction; every message is then sent as one record, whose tag is checked before
 * unserializing it.
 *
 * Record format: 4-byte big-endian length of the ciphertext, ciphertext, 16-byte GCM tag.
 * The nonce is the 64-bit record counter of the direction, the length is authenticated as additional data.
 *
 * @internal
 */
final class EncryptedChannel implements Channel
{
    private const TAG_LENGTH = 16;
    /**
     * Maximum size of a message.
     */
    private const MAX_LENGTH = 1 << 30;

    private readonly AES $encrypt;
    private readonly AES $decrypt;
    private int $sendCounter = 0;
    private int $receiveCounter = 0;
    private bool $closed = false;
    /**
     * IPC client that receives the responses of the server, see IpcCapable.
     *
     * @var ?WeakReference<Client>
     */
    private ?WeakReference $owner = null;

    public function __construct(
        private readonly ReadableStream&WritableStream $socket,
        private readonly BufferedReader $reader,
        string $sendKey,
        string $receiveKey,
    ) {
        $this->encrypt = new AES('gcm');
        $this->encrypt->setKey($sendKey);
        $this->decrypt = new AES('gcm');
        $this->decrypt->setKey($receiveKey);
    }

    /**
     * Set the IPC client that receives the responses of the server.
     */
    public function setOwner(Client $owner): void
    {
        $this->owner = WeakReference::create($owner);
    }

    private static function nonce(int $counter): string
    {
        return "\0\0\0\0".pack('J', $counter);
    }

    #[\Override]
    public function send(mixed $data): void
    {
        if ($this->closed) {
            throw new ChannelException('The channel was already closed!');
        }
        try {
            $data = serialize($data);
        } catch (Throwable $e) {
            throw new SerializationException('The given data cannot be sent because it is not serializable.', 0, $e);
        }
        if (\strlen($data) > self::MAX_LENGTH) {
            throw new SerializationException('The given data cannot be sent because it is bigger than 1 GiB.');
        }
        $header = pack('N', \strlen($data));
        // Encrypt synchronously, and write each record at once: records are written in the order of the counter.
        $this->encrypt->setNonce(self::nonce($this->sendCounter++));
        $this->encrypt->setAAD($header);
        $data = $this->encrypt->encrypt($data);
        try {
            $this->socket->write($header.$data.$this->encrypt->getTag(self::TAG_LENGTH));
        } catch (StreamException $e) {
            throw new ChannelException('Sending on the channel failed. Did the context die?', 0, $e);
        }
    }

    #[\Override]
    public function receive(?Cancellation $cancellation = null): mixed
    {
        if ($this->closed) {
            return null;
        }
        try {
            $header = $this->reader->readLength(4, $cancellation);
            $length = unpack('N', $header)[1];
            if ($length > self::MAX_LENGTH) {
                // Can't be a record of the peer: don't wait for it (the tag is only checked once it's received).
                $this->disconnect();
                throw new ChannelException('Received an invalid record length, closing the IPC connection!');
            }
            $data = $this->reader->readLength($length + self::TAG_LENGTH, $cancellation);
        } catch (BufferException) {
            // The peer closed the connection.
            $this->disconnect();
            return null;
        } catch (StreamException $e) {
            throw new ChannelException('Reading from the channel failed. Did the context die?', 0, $e);
        }
        $this->decrypt->setNonce(self::nonce($this->receiveCounter++));
        $this->decrypt->setAAD($header);
        $this->decrypt->setTag(substr($data, -self::TAG_LENGTH));
        try {
            $data = $this->decrypt->decrypt(substr($data, 0, -self::TAG_LENGTH));
        } catch (Throwable $e) {
            $this->disconnect();
            throw new ChannelException('Received a record that could not be authenticated, closing the IPC connection!', 0, $e);
        }
        // IPC-capable objects sent by a remote server carry the session name of the server,
        // which doesn't identify the client of this process that received them: bind them to it.
        $prev = IpcCapable::$remoteClient;
        IpcCapable::$remoteClient = $this->owner?->get();
        try {
            $result = unserialize($data);
        } catch (Throwable $e) {
            throw new SerializationException('Exception thrown when unserializing data', 0, $e);
        } finally {
            IpcCapable::$remoteClient = $prev;
        }
        if ($result === false && $data !== serialize(false)) {
            throw new ChannelException('Received invalid data');
        }
        return $result;
    }

    /**
     * Close the connection.
     */
    public function disconnect(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->socket->close();
    }
}
