<?php
/**
 * Connection module.
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

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTools\MsgIdHandler;
use danog\MadelineProto\Stream\MTProtoTools\SeqNoHandler;
use function Amp\asyncCall;
use function Amp\call;
use function Amp\Socket\connect;

/**
 * Connection class.
 *
 * Manages connection to Telegram datacenters
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Connection
{
    use Crypt;
    use MsgIdHandler;
    use SeqNoHandler;
    use \danog\Serializable;
    use Tools;

    const API_ENDPOINT = 0;
    const VOIP_UDP_REFLECTOR_ENDPOINT = 1;
    const VOIP_TCP_REFLECTOR_ENDPOINT = 2;
    const VOIP_UDP_P2P_ENDPOINT = 3;
    const VOIP_UDP_LAN_ENDPOINT = 4;

    const PENDING_MAX = 2000000000;

    public $stream;

    public $time_delta = 0;
    public $type = 0;
    public $peer_tag;
    public $temp_auth_key;
    public $auth_key;
    public $session_id;
    public $session_out_seq_no = 0;
    public $session_in_seq_no = 0;
    public $incoming_messages = [];
    public $outgoing_messages = [];
    public $new_incoming = [];
    public $new_outgoing = [];
    public $pending_outgoing = [];
    public $pending_outgoing_key = 0;
    public $max_incoming_id;
    public $max_outgoing_id;
    public $obfuscated = [];
    public $authorized = false;
    public $call_queue = [];
    public $ack_queue = [];
    public $i = [];
    public $last_recv = 0;
    public $last_http_wait = 0;

    public $datacenter;
    public $API;
    public $resumeWriterDeferred;
    public $ctx;
    public $pendingCheckWatcherId;

    /**
     * Connect function.
     *
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters
     *
     * @param string $proxy Proxy class name
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function connect(ConnectionContext $ctx): Promise
    {
        return call([$this, 'connectAsync'], $ctx);
    }

    /**
     * Connect function.
     *
     * Connects to a telegram DC using the specified protocol, proxy and connection parameters
     *
     * @param string $proxy Proxy class name
     *
     * @internal
     *
     * @return \Amp\Promise
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->API->logger->logger("Trying connection via $ctx", \danog\MadelineProto\Logger::WARNING);

        $this->ctx = $ctx->getCtx();
        $this->datacenter = $ctx->getDc();
        $this->stream = yield $ctx->getStream();
        $this->readLoop();
        $this->writeLoop();
    }

    public function readLoop()
    {
        asyncCall([$this, 'readLoopAsync']);
    }

    public function readLoopAsync(): \Generator
    {
        $error = yield $this->readMessage();

        if (is_int($error)) {
            yield $this->reconnect();

            if ($error === -404) {
                if ($this->temp_auth_key !== null) {
                    $this->API->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                    $this->temp_auth_key = null;
                    $this->session_id = null;
                    $this->API->init_authorization();

                    return;
                }
            }

            throw new \danog\MadelineProto\RPCErrorException($error, $error);
        }

        $this->readLoop();
        $this->API->handle_messages($this->datacenter);
    }

    public function readMessage(): Promise
    {
        return call([$this, 'readMessageAsync']);
    }

    public function readMessageAsync(): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer($payload_length);

        if ($payload_length === 4) {
            $payload = $this->unpack_signed_int(yield $buffer->bufferRead(4));
            $this->API->logger->logger("Received $payload from DC " . $this->datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return $payload;
        }
        $auth_key_id = yield $buffer->bufferRead(8);
        if ($auth_key_id === "\0\0\0\0\0\0\0\0") {
            $message_id = yield $buffer->bufferRead(8);
            $this->check_message_id($message_id, ['outgoing' => false, 'container' => false]);
            $message_length = unpack('V', yield $buffer->bufferRead(4))[1];
            $message_data = yield $buffer->bufferRead($message_length);
            $left = $payload_length - $message_length - 4 - 8 - 8;
            if ($left) {
                yield $buffer->bufferRead($left);
            }
            $this->incoming_messages[$message_id] = [];
        } elseif ($auth_key_id === $this->temp_auth_key['id']) {
            $message_key = yield $buffer->bufferRead(16);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->temp_auth_key['auth_key'], false);
            $encrypted_data = yield $buffer->bufferRead($payload_length - 24);
            $decrypted_data = $this->ige_decrypt($encrypted_data, $aes_key, $aes_iv);
            /*
            $server_salt = substr($decrypted_data, 0, 8);
            if ($server_salt != $this->temp_auth_key['server_salt']) {
                $this->API->logger->logger('WARNING: Server salt mismatch (my server salt '.$this->temp_auth_key['server_salt'].' is not equal to server server salt '.$server_salt.').', \danog\MadelineProto\Logger::WARNING);
            }
             */
            $session_id = substr($decrypted_data, 8, 8);
            if ($session_id != $this->session_id) {
                throw new \danog\MadelineProto\Exception('Session id mismatch.');
            }
            $message_id = substr($decrypted_data, 16, 8);
            $this->check_message_id($message_id, ['outgoing' => false, 'container' => false]);
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
            if ($message_key != substr(hash('sha256', substr($this->temp_auth_key['auth_key'], 96, 32) . $decrypted_data, true), 8, 16)) {
                throw new \danog\MadelineProto\SecurityException('msg_key mismatch');
            }
            $this->incoming_messages[$message_id] = ['seq_no' => $seq_no];
        } else {
            throw new \danog\MadelineProto\Exception('Got unknown auth_key id');
        }
        $deserialized = $this->API->deserialize($message_data, ['type' => '', 'datacenter' => $this->datacenter]);
        $this->incoming_messages[$message_id]['content'] = $deserialized;
        $this->incoming_messages[$message_id]['response'] = -1;
        $this->new_incoming[$message_id] = $message_id;
        $this->last_recv = time();
        $this->last_http_wait = 0;

        $this->API->logger->logger('Received payload from DC ' . $this->datacenter, \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        return true;
    }

    public function sendMessage($message, $flush = true): Promise
    {
        $deferred = new Deferred();
        $message['send_promise'] = $deferred;
        $this->pending_outgoing[$this->pending_outgoing_key++] = $message;
        $this->pending_outgoing_key %= self::PENDING_MAX;
        if ($flush) {
            $this->resumeWriteLoop();
        }

        return $deferred->promise();
    }

    public function pauseWriteLoop(): Promise
    {
        $this->resumeWriterDeferred = new Deferred();

        return $this->resumeWriterDeferred->promise();
    }

    public function resumeWriteLoop()
    {
        if ($this->resumeWriterDeferred) {
            $resume = $this->resumeWriterDeferred;
            $this->resumeWriterDeferred = null;
            $resume->resolve();
        }
    }

    public function writeLoop()
    {
        asyncCall([$this, 'writeLoopAsync']);
    }

    public function writeLoopAsync(): \Generator
    {
        if (empty($this->pending_outgoing)) {
            yield $this->pauseWriteLoop();
        }

        if ($this->temp_auth_key === null) {
            yield call([$this, 'unencryptedWriteLoopAsync']);
        } else {
            yield call([$this, 'encryptedWriteLoopAsync']);
        }
        $this->writeLoop();
    }

    public function unencryptedWriteLoopAsync(): \Generator
    {
        while ($this->pending_outgoing) {
            foreach ($this->pending_outgoing as $k => $message) {
                if ($this->temp_auth_key !== null) {
                    yield new Success(0);

                    return;
                }
                if (!$message['unencrypted']) {
                    continue;
                }

                $body = is_object($message['body']) ? yield $message['body'] : $message['body'];

                $this->API->logger->logger("Sending {$message['_']} as unencrypted message to DC {$this->datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message_id = isset($message['msg_id']) ? $message['msg_id'] : $this->generate_message_id();
                $length = strlen($body);
                $buffer = yield $this->stream->getWriteBuffer(8 + 8 + 4 + $length);

                yield $buffer->bufferWrite("\0\0\0\0\0\0\0\0" . $message_id . $this->pack_unsigned_int($length) . $body);

                $this->outgoing_messages[$message_id] = $message;
                $this->outgoing_messages[$message_id]['sent'] = time();
                $this->outgoing_messages[$message_id]['tries'] = 0;
                $this->outgoing_messages[$message_id]['unencrypted'] = true;
                $this->new_outgoing[$message_id] = $message_id;

                $message['send_promise']->resolve(isset($message['promise']) ? $message['promise'] : true);

                $this->API->logger->logger("Sent {$message['_']} as unencrypted message to DC {$this->datacenter}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                unset($this->pending_outgoing[$k]);
            }
        }
        yield new Success(0);
    }

    public function encryptedWriteLoopAsync(): \Generator
    {
        do {
            if ($this->temp_auth_key === null) {
                yield new Success(0);

                return;
            }
            if (count($to_ack = $this->ack_queue)) {
                $this->pending_outgoing[$this->pending_outgoing_key++] = ['_' => 'msgs_ack', 'body' => $this->API->serialize_object(['type' => 'msgs_ack'], ['msg_ids' => $this->ack_queue], 'msgs_ack'), 'content_related' => false, 'unencrypted' => false];
                $this->pending_outgoing_key %= self::PENDING_MAX;
            }

            $has_http_wait = false;
            $messages = [];
            $keys = [];

            foreach ($this->pending_outgoing as $message) {
                if ($message['_'] === 'http_wait') {
                    $has_http_wait = true;
                    break;
                }
            }

            if ($this->API->is_http($this->datacenter) && !$has_http_wait) {
                $dc_config_number = isset($this->API->settings['connection_settings'][$this->datacenter]) ? $this->datacenter : 'all';

                $this->pending_outgoing[$this->pending_outgoing_key] = ['_' => 'http_wait', 'body' => $this->API->serialize_method('http_wait', ['max_wait' => $this->API->settings['connection_settings'][$dc_config_number]['timeout'] * 1000 - 100, 'wait_after' => 0, 'max_delay' => 0]), 'content_related' => false, 'unencrypted' => false];
                $this->pending_outgoing_key %= self::PENDING_MAX;

                $has_http_wait = true;
            }

            $total_length = 0;
            $count = 0;
            ksort($this->pending_outgoing);
            foreach ($this->pending_outgoing as $k => $message) {
                if ($message['unencrypted']) {
                    continue;
                }
                if (isset($message['container'])) {
                    unset($this->pending_outgoing[$k]);
                    continue;
                }

                if ($count > 1020 || $total_length + 32 > 512 * 1024) {
                    $this->API->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::NOTICE);
                    break;
                }

                $body = is_object($message['body']) ? yield $message['body'] : $message['body'];

                $message_id = isset($message['msg_id']) ? $message['msg_id'] : $this->generate_message_id($this->datacenter);

                $this->API->logger->logger("Sending {$message['_']} as encrypted message to DC {$this->datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                $MTmessage = ['_' => 'MTmessage', 'msg_id' => $message_id, 'body' => $body, 'seqno' => $this->generate_out_seq_no($message['content_related'])];

                if (isset($message['method']) && $message['method']) {
                    if (!isset($this->temp_auth_key['connection_inited']) || $this->temp_auth_key['connection_inited'] === false) {
                        $this->API->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['write_client_info'], $message['_']), \danog\MadelineProto\Logger::NOTICE);
                        $MTmessage['body'] = $this->API->serialize_method('invokeWithLayer', ['layer' => $this->API->settings['tl_schema']['layer'], 'query' => $this->API->serialize_method('initConnection', ['api_id' => $this->API->settings['app_info']['api_id'], 'api_hash' => $this->API->settings['app_info']['api_hash'], 'device_model' => strpos($this->datacenter, 'cdn') === false ? $this->API->settings['app_info']['device_model'] : 'n/a', 'system_version' => strpos($this->datacenter, 'cdn') === false ? $this->API->settings['app_info']['system_version'] : 'n/a', 'app_version' => $this->API->settings['app_info']['app_version'], 'system_lang_code' => $this->API->settings['app_info']['lang_code'], 'lang_code' => $this->API->settings['app_info']['lang_code'], 'lang_pack' => '', 'query' => $MTmessage['body']])]);
                    } else {
                        if (isset($message['queue'])) {
                            if (!isset($this->call_queue[$message['queue']])) {
                                $this->call_queue[$message['queue']] = [];
                            }
                            $MTmessage['body'] = $this->API->serialize_method('invokeAfterMsgs', ['msg_ids' => $this->call_queue[$message['queue']], 'query' => $MTmessage['body']]);

                            $this->call_queue[$message['queue']][$message_id] = $message_id;
                            if (count($this->call_queue[$message['queue']]) > $this->API->settings['msg_array_limit']['call_queue']) {
                                reset($this->call_queue[$message['queue']]);
                                $key = key($this->call_queue[$message['queue']]);
                                unset($this->call_queue[$message['queue']][$key]);
                            }
                        }

                        if ($this->API->settings['requests']['gzip_encode_if_gt'] !== -1 && ($l = strlen($MTmessage['body'])) > $this->API->settings['requests']['gzip_encode_if_gt']) {
                            if (($g = strlen($gzipped = gzencode($MTmessage['body']))) < $l) {
                                $MTmessage['body'] = $this->API->serialize_object(['type' => 'gzip_packed'], ['packed_data' => $gzipped], 'gzipped data');
                                $this->API->logger->logger('Using GZIP compression for ' . $message['_'] . ', saved ' . ($l - $g) . ' bytes of data, reduced call size by ' . $g * 100 / $l . '%', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                            }
                            unset($gzipped);
                        }
                    }
                }
                $body_length = strlen($MTmessage['body']);
                if ($total_length && $total_length + $body_length + 32 > 655360) {
                    $this->API->logger->logger('Length overflow, postponing part of payload', \danog\MadelineProto\Logger::NOTICE);
                    break;
                }
                $count++;
                $total_length += $body_length + 32;

                $MTmessage['bytes'] = $body_length;
                $messages[] = $MTmessage;
                $keys[$k] = $message_id;
            }

            if (count($messages) > 1) {
                $this->API->logger->logger("Wrapping in msg_container as encrypted message for DC {$this->datacenter}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                $message_id = $this->generate_message_id($this->datacenter);
                $this->pending_outgoing[$this->pending_outgoing_key] = ['_' => 'msg_container', 'container' => array_values($keys), 'content_related' => false];

                $keys[$this->pending_outgoing_key++] = $message_id;
                $this->pending_outgoing_key %= self::PENDING_MAX;

                $message_data = $this->API->serialize_object(['type' => ''], ['_' => 'msg_container', 'messages' => $messages], 'container');

                $message_data_length = strlen($message_data);
                $seq_no = $this->generate_out_seq_no(false);
            } elseif (count($messages)) {
                $message = $messages[0];
                $message_data = $message['body'];
                $message_data_length = $message['bytes'];
                $message_id = $message['msg_id'];
                $seq_no = $message['seqno'];
            } else {
                $this->API->logger->logger('NO MESSAGE SENT', \danog\MadelineProto\Logger::WARNING);
                yield new Success(0);

                return;
            }

            unset($messages);

            $plaintext = $this->temp_auth_key['server_salt'] . $this->session_id . $message_id . pack('VV', $seq_no, $message_data_length) . $message_data;
            $padding = $this->posmod(-strlen($plaintext), 16);
            if ($padding < 12) {
                $padding += 16;
            }
            $padding = $this->random($padding);
            $message_key = substr(hash('sha256', substr($this->temp_auth_key['auth_key'], 88, 32) . $plaintext . $padding, true), 8, 16);
            list($aes_key, $aes_iv) = $this->aes_calculate($message_key, $this->temp_auth_key['auth_key']);
            $message = $this->temp_auth_key['id'] . $message_key . $this->ige_encrypt($plaintext . $padding, $aes_key, $aes_iv);

            $buffer = yield $this->stream->getWriteBuffer(strlen($message));

            yield $buffer->bufferWrite($message);

            $this->API->logger->logger("Sent encrypted payload to DC {$this->datacenter}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            $sent = time();

            foreach ($keys as $key => $message_id) {
                $this->outgoing_messages[$message_id] = &$this->pending_outgoing[$key];
                if (isset($this->outgoing_messages[$message_id]['promise'])) {
                    $this->new_outgoing[$message_id] = $message_id;
                    $this->outgoing_messages[$message_id]['sent'] = $sent;
                    $this->outgoing_messages[$message_id]['tries'] = 0;
                }
                if (isset($this->outgoing_messages[$message_id]['send_promise'])) {
                    $this->outgoing_messages[$message_id]['send_promise']->resolve(isset($this->outgoing_messages[$message_id]['promise']) ? $this->outgoing_messages[$message_id]['promise'] : true);
                }
                unset($this->pending_outgoing[$key]);
            }

            if ($to_ack) {
                $this->ack_queue = [];
            }

            if ($has_http_wait) {
                $this->last_http_wait = $sent;
            } elseif ($this->API->isAltervista()) {
                $this->last_http_wait = PHP_INT_MAX;
            }
            //if (!empty($this->pending_outgoing)) $this->select();
        } while (!empty($this->pending_outgoing));

        $this->pending_outgoing_key = 0;
        yield new Success(0);
    }

    public function hasPendingCalls()
    {
        $dc_config_number = isset($this->API->settings['connection_settings'][$this->datacenter]) ? $this->datacenter : 'all';
        foreach ($this->new_outgoing as $message_id) {
            if (isset($this->outgoing_messages[$message_id]['sent'])
                && $this->outgoing_messages[$message_id]['sent'] + $this->API->settings['connection_settings'][$dc_config_number]['timeout'] < time()
                && ($this->temp_auth_key === null) === isset($this->outgoing_messages[$message_id]['unencrypted'])
                && $this->outgoing_messages[$message_id]['_'] !== 'msgs_state_req'
            ) {
                return true;
            }
        }

        return false;
    }

    public function startPendingCallsCheck()
    {
        if ($this->pendingCheckWatcherId) {
            Loop::cancel($this->pendingCheckWatcherId);
            $this->pendingCheckWatcherId = null;
        }
        $this->pendingCallsCheck();
    }
    public function pendingCallsCheck()
    {
        $this->pendingCheckWatcherId = null;
        $timeout = $this->API->settings['connection_settings'][isset($this->API->settings['connection_settings'][$this->datacenter]) ? $this->datacenter : 'all']['timeout'];


        if (!empty($this->new_outgoing) && $this->hasPendingCalls()) {
            if ($this->temp_auth_key !== null) {
                $message_ids = array_values($this->new_outgoing);
                $deferred = new Deferred;
                $deferred->onResult(
                    function ($e, $result) use ($message_ids) {
                        if ($e) {
                            throw $e;
                        }
                        $reply = [];
                        foreach (str_split($result['info']) as $key => $chr) {
                            $message_id = $message_ids[$key];
                            if (!isset($this->outgoing_messages[$message_id])) {
                                $this->API->logger->logger('Already got response for and forgot about message ID ' . $this->unpack_signed_long($message_id));
                                continue;
                            }
                            if (!isset($this->new_outgoing[$message_id])) {
                                $this->API->logger->logger('Already got response for ' . $this->outgoing_messages[$message_id]['_'] . ' with message ID ' . $this->unpack_signed_long($message_id));
                                continue;
                            }
                            $chr = ord($chr);
                            switch ($chr & 7) {
                                case 0:
                                    $this->API->logger->logger('Wrong message status 0 for ' . $this->outgoing_messages[$message_id]['_'], \danog\MadelineProto\Logger::FATAL_ERROR);
                                    break;
                                case 1:
                                case 2:
                                case 3:
                                    $this->API->logger->logger('Message ' . $this->outgoing_messages[$message_id]['_'] . ' with message ID ' . $this->unpack_signed_long($message_id) . ' not received by server, resending...', \danog\MadelineProto\Logger::ERROR);
                                    $this->API->method_recall(['message_id' => $message_id, 'datacenter' => $this->datacenter, 'postpone' => true]);
                                    break;
                                case 4:
                                    if ($chr & 32) {
                                        $this->API->logger->logger('Message ' . $this->outgoing_messages[$message_id]['_'] . ' with message ID ' . $this->unpack_signed_long($message_id) . ' received by server and is being processed, waiting...', \danog\MadelineProto\Logger::ERROR);
                                    } elseif ($chr & 64) {
                                        $this->API->logger->logger('Message ' . $this->outgoing_messages[$message_id]['_'] . ' with message ID ' . $this->unpack_signed_long($message_id) . ' received by server and was already processed, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                        $reply[] = $message_id;
                                    } elseif ($chr & 128) {
                                        $this->API->logger->logger('Message ' . $this->outgoing_messages[$message_id]['_'] . ' with message ID ' . $this->unpack_signed_long($message_id) . ' received by server and was already sent, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                        $reply[] = $message_id;
                                    } else {
                                        $this->API->logger->logger('Message ' . $this->outgoing_messages[$message_id]['_'] . ' with message ID ' . $this->unpack_signed_long($message_id) . ' received by server, requesting reply...', \danog\MadelineProto\Logger::ERROR);
                                        $reply[] = $message_id;
                                    }
                            }
                        }
                        if ($reply) {
                            $this->API->object_call('msg_resend_ans_req', ['msg_ids' => $reply], ['datacenter' => $this->datacenter, 'postpone' => true]);
                        }
                        $this->resumeWriteLoop();
                    }
                );
                $this->API->logger->logger("Still missing something on DC $this->datacenter, sending state request", \danog\MadelineProto\Logger::ERROR);
                $this->API->object_call('msgs_state_req', ['msg_ids' => $message_ids], ['datacenter' => $this->datacenter, 'promise' => $deferred]);
            } else {
                foreach ($this->new_outgoing as $message_id) {
                    if (isset($this->outgoing_messages[$message_id]['sent'])
                        && $this->outgoing_messages[$message_id]['sent'] + $timeout < time()
                        && $this->outgoing_messages[$message_id]['unencrypted']
                    ) {
                        $this->API->logger->logger('Still missing ' . $this->outgoing_messages[$message_id]['_'] . ' with message id ' . $this->unpack_signed_long($message_id) . " on DC $this->datacenter, resending", \danog\MadelineProto\Logger::ERROR);
                        $this->API->method_recall(['message_id' => $message_id, 'datacenter' => $this->datacenter, 'postpone' => true]);
                    }
                }
            }
            $this->pendingCheckWatcherId = Loop::delay($timeout * 1000, [$this, 'pendingCallsCheck']);
            $this->resumeWriteLoop();
        } else if (!empty($this->new_outgoing)) {
            $this->pendingCheckWatcherId = Loop::delay($timeout * 1000, [$this, 'pendingCallsCheck']);
        }
    }

    public function setExtra($extra)
    {
        $this->API = $extra;
    }

    public function disconnect(): Promise
    {
        return $this->stream->disconnect();
    }

    public function reconnect(): Promise
    {
        return call([$this, 'reconnectAsync']);
    }

    public function reconnectAsync(): \Generator
    {
        $this->API->logger->logger("Reconnecting");
        yield $this->disconnect();
        yield $this->connect($this->ctx);
    }

    public function getName(): string
    {
        return __CLASS__;
    }

    /**
     * Sleep function.
     *
     * @internal
     *
     * @return array
     */
    public function __sleep()
    {
        return ['proxy', 'extra', 'protocol', 'ip', 'port', 'timeout', 'parsed', 'peer_tag', 'temp_auth_key', 'auth_key', 'session_id', 'session_out_seq_no', 'session_in_seq_no', 'ipv6', 'max_incoming_id', 'max_outgoing_id', 'obfuscated', 'authorized', 'ack_queue'];
    }

    public function __wakeup()
    {
        $this->time_delta = 0;
        $this->pending_outgoing = [];
    }
}
