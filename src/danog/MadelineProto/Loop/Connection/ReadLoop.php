<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\ByteStream\PendingReadError;
use Amp\ByteStream\StreamException;
use Amp\Loop;
use Amp\Websocket\ClosedException;
use danog\Loop\SignalLoop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\IncomingMessage;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;

/**
 * Socket read loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ReadLoop extends SignalLoop
{
    use Common;
    /**
     * Main loop.
     *
     * @return \Generator
     */
    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        while (true) {
            try {
                $error = yield $this->waitSignal($this->readMessage());
            } catch (NothingInTheSocketException|StreamException|PendingReadError|\Error $e) {
                if ($connection->shouldReconnect()) {
                    return;
                }
                Tools::callForkDefer((function () use ($API, $connection, $datacenter, $e): \Generator {
                    $API->logger->logger($e);
                    $API->logger->logger("Got nothing in the socket in DC {$datacenter}, reconnecting...", Logger::ERROR);
                    yield from $connection->reconnect();
                })());
                return;
            } catch (SecurityException $e) {
                $connection->resetSession();
                $API->logger->logger("Got security exception in DC {$datacenter}, reconnecting...", Logger::ERROR);
                yield from $connection->reconnect();
                throw $e;
            }
            if (\is_int($error)) {
                //$this->exitedLoop();
                Tools::callForkDefer((function () use ($error, $shared, $connection, $datacenter, $API): \Generator {
                    if ($error === -404) {
                        if ($shared->hasTempAuthKey()) {
                            $API->logger->logger("WARNING: Resetting auth key in DC {$datacenter}...", Logger::WARNING);
                            $shared->setTempAuthKey(null);
                            $shared->resetSession();
                            foreach ($connection->new_outgoing as $message) {
                                $message->resetSent();
                            }
                            yield from $shared->reconnect();
                            yield from $API->initAuthorization();
                        } else {
                            yield from $connection->reconnect();
                        }
                    } elseif ($error === -1) {
                        $API->logger->logger("WARNING: Got quick ack from DC {$datacenter}", Logger::WARNING);
                        yield from $connection->reconnect();
                    } elseif ($error === 0) {
                        $API->logger->logger("Got NOOP from DC {$datacenter}", Logger::WARNING);
                        yield from $connection->reconnect();
                    } elseif ($error === -429) {
                        $API->logger->logger("Got -429 from DC {$datacenter}", Logger::WARNING);
                        yield Tools::sleep(3);
                        yield from $connection->reconnect();
                    } else {
                        yield from $connection->reconnect();
                        throw new \danog\MadelineProto\RPCErrorException($error, $error);
                    }
                })());
                return;
            }
            $connection->httpReceived();
            Loop::defer([$connection, 'handleMessages']);
            if ($shared->isHttp()) {
                Loop::defer([$connection, 'pingHttpWaiter']);
            }
        }
    }
    public function readMessage(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;
        $shared = $this->datacenterConnection;
        if ($connection->shouldReconnect()) {
            $API->logger->logger('Not reading because connection is old');
            throw new NothingInTheSocketException();
        }
        try {
            $buffer = yield $connection->stream->getReadBuffer($payload_length);
        } catch (ClosedException $e) {
            $API->logger->logger($e->getReason());
            if (\strpos($e->getReason(), '       ') === 0) {
                $payload = -\substr($e->getReason(), 7);
                $API->logger->logger("Received {$payload} from DC ".$datacenter, Logger::ERROR);
                return $payload;
            }
            throw $e;
        }
        if ($payload_length === 4) {
            $payload = \danog\MadelineProto\Tools::unpackSignedInt(yield $buffer->bufferRead(4));
            $API->logger->logger("Received {$payload} from DC ".$datacenter, Logger::ULTRA_VERBOSE);
            return $payload;
        }
        $connection->reading(true);
        try {
            $auth_key_id = yield $buffer->bufferRead(8);
            if ($auth_key_id === "\0\0\0\0\0\0\0\0") {
                $message_id = yield $buffer->bufferRead(8);
                $connection->msgIdHandler->checkMessageId($message_id, ['outgoing' => false, 'container' => false]);
                $message_length = \unpack('V', yield $buffer->bufferRead(4))[1];
                $message_data = yield $buffer->bufferRead($message_length);
                $left = $payload_length - $message_length - 4 - 8 - 8;
                if ($left) {
                    $API->logger->logger('Padded unencrypted message', Logger::ULTRA_VERBOSE);
                    if ($left < (-$message_length & 15)) {
                        $API->logger->logger('Protocol padded unencrypted message', Logger::ULTRA_VERBOSE);
                    }
                    yield $buffer->bufferRead($left);
                }
            } elseif ($auth_key_id === $shared->getTempAuthKey()->getID()) {
                $message_key = yield $buffer->bufferRead(16);
                list($aes_key, $aes_iv) = Crypt::aesCalculate($message_key, $shared->getTempAuthKey()->getAuthKey(), false);
                $encrypted_data = yield $buffer->bufferRead($payload_length - 24);
                $protocol_padding = \strlen($encrypted_data) % 16;
                if ($protocol_padding) {
                    $encrypted_data = \substr($encrypted_data, 0, -$protocol_padding);
                }
                $decrypted_data = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);
                if ($message_key != \substr(\hash('sha256', \substr($shared->getTempAuthKey()->getAuthKey(), 96, 32).$decrypted_data, true), 8, 16)) {
                    throw new \danog\MadelineProto\SecurityException('msg_key mismatch');
                }
                /*
                                $server_salt = substr($decrypted_data, 0, 8);
                                if ($server_salt != $shared->getTempAuthKey()->getServerSalt()) {
                                $API->logger->logger('WARNING: Server salt mismatch (my server salt '.$shared->getTempAuthKey()->getServerSalt().' is not equal to server server salt '.$server_salt.').', Logger::WARNING);
                                }
                */
                $session_id = \substr($decrypted_data, 8, 8);
                if ($session_id !== $connection->session_id) {
                    $API->logger->logger("Session ID mismatch", Logger::FATAL_ERROR);
                    $connection->resetSession();
                    throw new NothingInTheSocketException();
                }
                $message_id = \substr($decrypted_data, 16, 8);
                $connection->msgIdHandler->checkMessageId($message_id, ['outgoing' => false, 'container' => false]);
                $seq_no = \unpack('V', \substr($decrypted_data, 24, 4))[1];
                $message_data_length = \unpack('V', \substr($decrypted_data, 28, 4))[1];
                if ($message_data_length > \strlen($decrypted_data)) {
                    throw new \danog\MadelineProto\SecurityException('message_data_length is too big');
                }
                if (\strlen($decrypted_data) - 32 - $message_data_length < 12) {
                    throw new \danog\MadelineProto\SecurityException('padding is too small');
                }
                if (\strlen($decrypted_data) - 32 - $message_data_length > 1024) {
                    throw new \danog\MadelineProto\SecurityException('padding is too big');
                }
                if ($message_data_length < 0) {
                    throw new \danog\MadelineProto\SecurityException('message_data_length not positive');
                }
                if ($message_data_length % 4 != 0) {
                    throw new \danog\MadelineProto\SecurityException('message_data_length not divisible by 4');
                }
                $message_data = \substr($decrypted_data, 32, $message_data_length);
            } else {
                $API->logger->logger('Got unknown auth_key id', Logger::ERROR);
                return -404;
            }
            [$deserialized, $sideEffects] = $API->getTL()->deserialize($message_data, ['type' => '', 'connection' => $connection]);
            if (isset($API->referenceDatabase)) {
                $API->referenceDatabase->reset();
            }
            $message = new IncomingMessage($deserialized, $message_id);
            if (isset($seq_no)) {
                $message->setSeqNo($seq_no);
            }
            if ($sideEffects) {
                $message->setSideEffects($sideEffects);
            }
            $connection->new_incoming[$message_id] = $connection->incoming_messages[$message_id] = $message;
            $API->logger->logger('Received payload from DC '.$datacenter, Logger::ULTRA_VERBOSE);
        } finally {
            $connection->reading(false);
        }
        return true;
    }
    /**
     * Get loop name.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "read loop in DC {$this->datacenter}";
    }
}
