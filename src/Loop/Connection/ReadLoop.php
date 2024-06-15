<?php

declare(strict_types=1);

/**
 * Socket read loop.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\ByteStream\PendingReadError;
use Amp\ByteStream\StreamException;
use Amp\Websocket\WebsocketClosedException;
use danog\Loop\Loop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use danog\MadelineProto\TransportError;
use Error;
use Revolt\EventLoop;

use function Amp\delay;
use function substr;

/**
 * Socket read loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class ReadLoop extends Loop
{
    use Common;
    /**
     * Main loop.
     */
    protected function loop(): ?float
    {
        try {
            $this->API->logger("Reading in $this...", Logger::ULTRA_VERBOSE);
            $error = $this->readMessage();
            $this->API->logger("Finished reading in $this!", Logger::ULTRA_VERBOSE);
        } catch (NothingInTheSocketException|StreamException|PendingReadError|Error $e) {
            if ($this->connection->shouldReconnect()) {
                $this->API->logger("Stopping $this due to reconnect...", Logger::ERROR);
                return self::STOP;
            }
            EventLoop::queue(function () use ($e): void {
                if ($e instanceof NothingInTheSocketException
                    && !$this->connection->hasPendingCalls()
                    && $this->connection->isMedia()
                    && !$this->connection->isWriting()
                    && $this->shared->hasTempAuthKey()
                ) {
                    $this->API->logger("Got NothingInTheSocketException in DC {$this->datacenter}, disconnecting because we have nothing to do...", Logger::ERROR);
                    $this->connection->disconnect(true);
                } else {
                    $this->API->logger($e);
                    $this->API->logger("Got exception in DC {$this->datacenter}, reconnecting...", Logger::ERROR);
                    $this->connection->reconnect();
                }
            });
            return self::STOP;
        } catch (SecurityException $e) {
            $this->connection->resetSession("security exception {$e->getMessage()}");
            $this->API->logger("Got security exception in DC {$this->datacenter}, reconnecting...", Logger::ERROR);
            $this->connection->reconnect();
            throw $e;
        }
        if (\is_int($error)) {
            EventLoop::queue(function () use ($error): void {
                if ($error === -404) {
                    if ($this->shared->hasTempAuthKey()) {
                        $this->API->logger("WARNING: Resetting auth key in DC {$this->datacenter}...", Logger::WARNING);
                        $this->shared->setTempAuthKey(null);
                        $this->shared->resetSession("-404");
                        foreach ($this->connection->new_outgoing as $message) {
                            $message->resetSent();
                        }
                        $this->shared->reconnect();
                    } else {
                        $this->connection->reconnect();
                    }
                } elseif ($error === -1) {
                    $this->API->logger("WARNING: Got quick ack from DC {$this->datacenter}", Logger::WARNING);
                    $this->connection->reconnect();
                } elseif ($error === 0) {
                    $this->API->logger("Got NOOP from DC {$this->datacenter}", Logger::WARNING);
                    $this->connection->reconnect();
                } elseif ($error === -429) {
                    $this->API->logger("Got -429 from DC {$this->datacenter}", Logger::WARNING);
                    delay(3);
                    $this->connection->reconnect();
                } else {
                    $this->connection->reconnect();
                    throw new TransportError($error);
                }
            });
            $this->API->logger("Stopping $this due to $error...", Logger::ERROR);
            return self::STOP;
        }
        $this->connection->httpReceived();
        if ($this->connection->isHttp()) {
            $this->connection->pingHttpWaiter();
        }
        $this->connection->wakeupHandler();
        return self::CONTINUE;
    }
    public function readMessage(): ?int
    {
        if ($this->connection->shouldReconnect()) {
            $this->API->logger('Not reading because connection is old');
            throw new NothingInTheSocketException();
        }
        try {
            $buffer = $this->connection->stream->getReadBuffer($payload_length);
        } catch (WebsocketClosedException $e) {
            $this->API->logger($e->getReason());
            if (str_starts_with($e->getReason(), '       ')) {
                $payload = -((int) substr($e->getReason(), 7));
                $this->API->logger("Received {$payload} from DC ".$this->datacenter, Logger::ERROR);
                return $payload;
            }
            throw $e;
        }
        /** @var int $payload_length */
        if ($payload_length & (1 << 31)) {
            $this->API->logger("Received quick ACK $payload_length from DC ".$this->datacenter, Logger::ULTRA_VERBOSE);
            $this->connection->incomingBytesCtr?->incBy(4);
            return null;
        }
        $this->connection->incomingBytesCtr?->incBy(4+$payload_length);
        if ($payload_length <= 16) {
            $code = Tools::unpackSignedInt($buffer->bufferRead(4));
            if ($code === -1 && $payload_length >= 8) {
                $ack = unpack('V', $buffer->bufferRead(4))[1];
                $this->API->logger("Received quick ACK $ack (padded) from DC ".$this->datacenter, Logger::ULTRA_VERBOSE);
                if ($payload_length > 8) {
                    $buffer->bufferRead($payload_length-8);
                }
                return null;
            }
            if ($payload_length > 4) {
                $buffer->bufferRead($payload_length-4);
            }
            $this->API->logger("Received {$code} from DC ".$this->datacenter, Logger::ULTRA_VERBOSE);
            return $code;
        }
        $this->connection->reading(true);
        try {
            $seq_no = null;
            $auth_key_id = $buffer->bufferRead(8);
            if ($unencrypted = $auth_key_id === "\0\0\0\0\0\0\0\0") {
                if ($this->shared->hasTempAuthKey()) {
                    throw new SecurityException("Got unencrypted message from encrypted socket!");
                }
                $message_id = Tools::unpackSignedLong($buffer->bufferRead(8));
                $this->connection->msgIdHandler->checkIncomingMessageId($message_id, false);
                $message_length = unpack('V', $buffer->bufferRead(4))[1];
                $message_data = $buffer->bufferRead($message_length);
                $left = $payload_length - $message_length - 4 - 8 - 8;
                if ($left) {
                    $this->API->logger('Padded unencrypted message', Logger::ULTRA_VERBOSE);
                    if ($left < (-$message_length & 15)) {
                        $this->API->logger('Protocol padded unencrypted message', Logger::ULTRA_VERBOSE);
                    }
                    $this->connection->incomingBytesCtr?->incBy($left);
                    $buffer->bufferRead($left);
                }
            } elseif ($auth_key_id === $this->shared->getTempAuthKey()->getID()) {
                $message_key = $buffer->bufferRead(16);
                [$aes_key, $aes_iv] = Crypt::kdf($message_key, $this->shared->getTempAuthKey()->getAuthKey(), false);
                $payload_length -= 24;
                $left = $payload_length & 15;
                $payload_length -= $left;
                $decrypted_data = Crypt::igeDecrypt($buffer->bufferRead($payload_length), $aes_key, $aes_iv);
                if ($left) {
                    $this->connection->incomingBytesCtr?->incBy($left);
                    $buffer->bufferRead($left);
                }
                if ($message_key != substr(hash('sha256', substr($this->shared->getTempAuthKey()->getAuthKey(), 96, 32).$decrypted_data, true), 8, 16)) {
                    throw new SecurityException('msg_key mismatch');
                }
                /*
                                $server_salt = substr($decrypted_data, 0, 8);
                                if ($server_salt != $this->shared->getTempAuthKey()->getServerSalt()) {
                                $this->API->logger('WARNING: Server salt mismatch (my server salt '.$this->shared->getTempAuthKey()->getServerSalt().' is not equal to server server salt '.$server_salt.').', Logger::WARNING);
                                }
                */
                $session_id = substr($decrypted_data, 8, 8);
                if ($session_id !== $this->connection->session_id) {
                    $this->API->logger('Session ID mismatch', Logger::FATAL_ERROR);
                    $this->connection->resetSession("session ID mismatch");
                    throw new NothingInTheSocketException();
                }
                $message_id = Tools::unpackSignedLong(substr($decrypted_data, 16, 8));
                $this->connection->msgIdHandler->checkIncomingMessageId($message_id, false);
                $seq_no = unpack('V', substr($decrypted_data, 24, 4))[1];
                $message_data_length = unpack('V', substr($decrypted_data, 28, 4))[1];
                if ($message_data_length > \strlen($decrypted_data)) {
                    throw new SecurityException('message_data_length is too big');
                }
                if (\strlen($decrypted_data) - 32 - $message_data_length < 12) {
                    throw new SecurityException('padding is too small');
                }
                if (\strlen($decrypted_data) - 32 - $message_data_length > 1024) {
                    throw new SecurityException('padding is too big');
                }
                if ($message_data_length < 0) {
                    throw new SecurityException('message_data_length not positive');
                }
                if ($message_data_length % 4 != 0) {
                    throw new SecurityException('message_data_length not divisible by 4');
                }
                $message_data = substr($decrypted_data, 32, $message_data_length);
            } else {
                $this->API->logger('Got unknown auth_key id', Logger::ERROR);
                return -404;
            }
            $this->API->logger('Received payload from DC '.$this->datacenter, Logger::ULTRA_VERBOSE);

            try {
                $deserialized = $this->API->getTL()->deserialize($message_data, ['type' => '', 'connection' => $this->connection]);
            } catch (\Throwable $e) {
                Logger::log('Error during deserializing message (base64): ' .  base64_encode($message_data), Logger::ERROR);
                throw $e;
            } finally {
                $this->API->minDatabase->reset();
                $this->API->referenceDatabase->reset();
            }

            $message = new MTProtoIncomingMessage($deserialized, $message_id, $unencrypted);
            if (isset($seq_no)) {
                $message->setSeqNo($seq_no);
            }

            $this->connection->new_incoming->enqueue($message);
            $this->connection->incoming_messages[$message_id] = $message;
            $this->connection->incomingCtr?->inc();
        } finally {
            $this->connection->reading(false);
        }
        return null;
    }
    /**
     * Get loop name.
     */
    public function __toString(): string
    {
        return "read loop in DC {$this->datacenter}";
    }
}
