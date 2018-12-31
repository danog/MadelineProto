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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use Amp\Loop;
use Amp\Promise;
use Amp\Websocket\ClosedException;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Impl\SignalLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\Tools;
use function Amp\call;

/**
 * Socket read loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class ReadLoop extends SignalLoop
{
    use Tools;
    use Crypt;

    public function loop(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        $this->startedLoop();
        $API->logger->logger("Entered read loop in DC {$datacenter}", Logger::ULTRA_VERBOSE);
        $timeout = $API->settings['connection_settings'][isset($API->settings['connection_settings'][$datacenter]) ? $datacenter : 'all']['timeout'];

        while (true) {
            try {
                $error = yield $this->waitSignal($this->readMessage());
            } catch (NothingInTheSocketException $e) {
                if (isset($connection->old)) {
                    $this->exitedLoop();
                    $API->logger->logger("Exiting read loop in DC $datacenter");

                    return;
                }
                $API->logger->logger("Got nothing in the socket in DC {$datacenter}, reconnecting...", Logger::ERROR);
                yield $connection->reconnect();
                continue;
            } catch (ClosedException $e) {
                $API->logger->logger($e->getMessage(), Logger::FATAL_ERROR);

                throw $e;
            }

            if (is_int($error)) {
                $this->exitedLoop();
                yield $connection->reconnect();

                if ($error === -404) {
                    if ($connection->temp_auth_key !== null) {
                        $API->logger->logger("WARNING: Resetting auth key in DC {$datacenter}...", \danog\MadelineProto\Logger::WARNING);
                        $connection->temp_auth_key = null;
                        $connection->session_id = null;
                        foreach ($connection->new_outgoing as $message_id) {
                            $connection->outgoing_messages[$message_id]['sent'] = 0;
                        }
                        yield $API->init_authorization_async();
                    } else {
                        //throw new \danog\MadelineProto\RPCErrorException($error, $error);
                    }
                } elseif ($error === -1) {
                    $API->logger->logger("WARNING: Got quick ack from DC {$datacenter}", \danog\MadelineProto\Logger::WARNING);
                } elseif ($error === 0) {
                    $API->logger->logger("Got NOOP from DC {$datacenter}", \danog\MadelineProto\Logger::WARNING);
                } else {
                    throw new \danog\MadelineProto\RPCErrorException($error, $error);
                }

                return;
            }

            $connection->http_res_count++;

            try {
                $API->handle_messages($datacenter);
            } finally {
                $this->exitedLoop();
            }
            $this->startedLoop();
            //var_dump(count($connection->incoming_messages));
//            Loop::defer(function () use ($datacenter) {
            if ($this->API->is_http($datacenter)) {
                $this->API->datacenter->sockets[$datacenter]->waiter->resume();
            } // });
        }
    }

    public function readMessage(): Promise
    {
        return call([$this, 'readMessageAsync']);
    }

    public function readMessageAsync(): \Generator
    {
        $API = $this->API;
        $datacenter = $this->datacenter;
        $connection = $this->connection;

        try {
            $buffer = yield $connection->stream->getReadBuffer($payload_length);
        } catch (ClosedException $e) {
            $API->logger->logger($e->getReason());
            if (strpos($e->getReason(), '       ') === 0) {
                $payload = -substr($e->getReason(), 7);
                $API->logger->logger("Received $payload from DC ".$datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                return $payload;
            }

            throw $e;
        }

        if ($payload_length === 4) {
            $payload = $this->unpack_signed_int(yield $buffer->bufferRead(4));
            $API->logger->logger("Received $payload from DC ".$datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return $payload;
        }
        $auth_key_id = yield $buffer->bufferRead(8);
        if ($auth_key_id === "\0\0\0\0\0\0\0\0") {
            $message_id = yield $buffer->bufferRead(8);
            if (!in_array($message_id, [1, 0])) {
                $connection->check_message_id($message_id, ['outgoing' => false, 'container' => false]);
            }
            $message_length = unpack('V', yield $buffer->bufferRead(4))[1];
            $message_data = yield $buffer->bufferRead($message_length);
            $left = $payload_length - $message_length - 4 - 8 - 8;
            if ($left) {
                $API->logger->logger('Padded unencrypted message', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                if ($left < (-$message_length & 15)) {
                    $API->logger->logger('Protocol padded unencrypted message', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                }
                yield $buffer->bufferRead($left);
            }
            $connection->incoming_messages[$message_id] = [];
        } elseif ($auth_key_id === $connection->temp_auth_key['id']) {
            $message_key = yield $buffer->bufferRead(16);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $connection->temp_auth_key['auth_key'], false);
            $encrypted_data = yield $buffer->bufferRead($payload_length - 24);
            $protocol_padding = strlen($encrypted_data) % 16;
            if ($protocol_padding) {
                $encrypted_data = substr($encrypted_data, 0, -$protocol_padding);
            }
            $decrypted_data = $this->ige_decrypt($encrypted_data, $aes_key, $aes_iv);
            /*
            $server_salt = substr($decrypted_data, 0, 8);
            if ($server_salt != $connection->temp_auth_key['server_salt']) {
            $API->logger->logger('WARNING: Server salt mismatch (my server salt '.$connection->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').', \danog\MadelineProto\Logger::WARNING);
            }
             */
            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $connection->session_id) {
                throw new \danog\MadelineProto\Exception('Session id mismatch.');
            }
            $message_id = substr($decrypted_data, 16, 8);
            $connection->check_message_id($message_id, ['outgoing' => false, 'container' => false]);
            $seq_no = unpack('V', substr($decrypted_data, 24, 4))[1];

            $message_data_length = unpack('V', substr($decrypted_data, 28, 4))[1];
            if ($message_data_length > strlen($decrypted_data)) {
                throw new \danog\MadelineProto\SecurityException('message_data_length is too big');
            }
            if (strlen($decrypted_data) - 32 - $message_data_length < 12) {
                throw new \danog\MadelineProto\SecurityException('padding is too small');
            }
            if (strlen($decrypted_data) - 32 - $message_data_length > 1024) {
                throw new \danog\MadelineProto\SecurityException('padding is too big');
            }
            if ($message_data_length < 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not positive');
            }
            if ($message_data_length % 4 != 0) {
                throw new \danog\MadelineProto\SecurityException('message_data_length not divisible by 4');
            }
            $message_data = substr($decrypted_data, 32, $message_data_length);
            if ($message_key != substr(hash('sha256', substr($connection->temp_auth_key['auth_key'], 96, 32).$decrypted_data, true), 8, 16)) {
                throw new \danog\MadelineProto\SecurityException('msg_key mismatch');
            }
            $connection->incoming_messages[$message_id] = ['seq_no' => $seq_no];
        } else {
            $API->logger->logger('Got unknown auth_key id', \danog\MadelineProto\Logger::ERROR);
            return -404;
        }
        $deserialized = $API->deserialize($message_data, ['type' => '', 'datacenter' => $datacenter]);
        $API->referenceDatabase->reset();

        $connection->incoming_messages[$message_id]['content'] = $deserialized;
        $connection->incoming_messages[$message_id]['response'] = -1;
        $connection->new_incoming[$message_id] = $message_id;
        $connection->last_recv = time();
        $connection->last_http_wait = 0;

        $API->logger->logger('Received payload from DC '.$datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        return true;
    }
}
