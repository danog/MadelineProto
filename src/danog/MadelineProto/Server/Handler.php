<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Server;

/*
 * Socket handler for server
 */
class Handler extends \danog\MadelineProto\Connection
{
    use \danog\MadelineProto\TL\TL;
    use \danog\MadelineProto\Tools;
    private $madeline;

    public function __magic_construct($socket, $extra, $ip, $port, $protocol, $timeout, $ipv6)
    {
        $this->sock = $socket;
        $this->sock->setBlocking(true);
        $timeout = 2;
        $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
        $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
        $this->construct_TL(['socket' => __DIR__.'/../TL_socket.tl']);
    }

    public function __destruct()
    {
        \danog\MadelineProto\Logger::log('Closing socket in fork '.getmypid());
        unset($this->sock);
        $this->destruct_madeline();
        exit();
    }

    public function destruct_madeline()
    {
        if ($this->madeline !== null) {
            $this->madeline->settings['logger'] = ['logger' => 0];
            $this->madeline->serialize();
            unset($this->madeline);

            return true;
        }

        return false;
    }

    public function loop()
    {
        $buffer = '';

        $first_byte = $this->sock->read(1);
        if ($first_byte === chr(239)) {
            $this->protocol = 'tcp_abridged';
        } else {
            $first_byte .= $this->sock->read(3);
            if ($first_byte === str_repeat(chr(238), 4)) {
                $this->protocol = 'tcp_intermediate';
            } else {
                $this->protocol = 'tcp_full';

                $packet_length = unpack('V', $first_byte)[1];
                $packet = $this->read($packet_length - 4);
                if (strrev(hash('crc32b', $first_byte.substr($packet, 0, -4), true)) !== substr($packet, -4)) {
                    throw new Exception('CRC32 was not correct!');
                }
                $this->in_seq_no++;
                $in_seq_no = unpack('V', substr($packet, 0, 4))[1];
                if ($in_seq_no != $this->in_seq_no) {
                    throw new Exception('Incoming seq_no mismatch');
                }

                $buffer = substr($packet, 4, $packet_length - 12);
            }
        }
        while (true) {
            pcntl_signal_dispatch();
            $request_id = 0;

            try {
                if ($buffer) {
                    $message = $buffer;
                    $buffer = '';
                } else {
                    $message = $this->read_message();
                }
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                continue;
            }

            try {
                $message = $this->deserialize($message, ['type' => '', 'datacenter' => '']);
                if ($message['_'] !== 'socketMessageRequest') {
                    throw new \danog\MadelineProto\Exception('Invalid object received');
                }
                $request_id = $message['request_id'];
                $this->send_response($request_id, $this->on_request($request_id, $message['method'], $message['args']));
            } catch (\danog\MadelineProto\TL\Exception $e) {
                $this->send_exception($request_id, $e);
                continue;
            } catch (\danog\MadelineProto\Exception $e) {
                $this->send_exception($request_id, $e);
                continue;
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->send_exception($request_id, $e);
                continue;
            } catch (\DOMException $e) {
                $this->send_exception($request_id, $e);
                continue;
            }
        }
    }

    public function on_request($request_id, $method, $args)
    {
        if (count($method) === 0 || count($method) > 2) {
            throw new \danog\MadelineProto\Exception('Invalid method called');
        }
        if ($method[0] === '__construct') {
            if (count($args) === 1 && is_array($args[0])) {
                $args[0]['logger'] = ['logger' => 4, 'logger_param' => [$this, 'logger']];
                $args[0]['updates']['callback'] = [$this, 'update_handler'];
            } elseif (count($args) === 2 && is_array($args[1])) {
                $args[1]['logger'] = ['logger' => 4, 'logger_param' => [$this, 'logger']];
                $args[1]['updates']['callback'] = [$this, 'update_handler'];
            }
            $this->madeline = new \danog\MadelineProto\API(...$args);

            return true;
        }
        if ($method[0] === '__destruct') {
            return $this->destruct_madeline();
        }
        if ($this->madeline === null) {
            throw new \danog\MadelineProto\Exception('__construct was not called');
        }
        foreach ($args as &$arg) {
            if (is_array($arg) && isset($arg['_'])) {
                if ($arg['_'] === 'callback' && isset($arg['callback']) && !method_exists($this, $arg['callback'])) {
                    $arg = [$this, $arg['callback']];
                }
                if ($arg['_'] === 'stream' && isset($arg['stream_id'])) {
                    $arg = fopen('madelineSocket://', 'r+b', false, self::getContext($this, $arg['stream_id']));
                }
            }
        }
        if (count($method) === 1) {
            return $this->madeline->{$method[0]}(...$args);
        }
        if (count($method) === 2) {
            return $this->madeline->{$method[0]}->{$method[1]}(...$args);
        }
    }

    public function send_exception($request_id, $e)
    {
        if ($e instanceof \danog\MadelineProto\RPCErrorException) {
            $exception = ['_' => 'socketRPCErrorException'];
            if ($e->getMessage() === $e->rpc) {
                $exception['rpc_message'] = $e->rpc;
            } else {
                $exception['rpc_message'] = $e->rpc;
                $exception['message'] = $e->getMessage();
            }
        } else if ($e instanceof \danog\MadelineProto\TL\Exception) {
            $exception = ['_' => 'socketTLException', 'message' => $e->getMessage()];
        } else if ($e instanceof \DOMException) {
            $exception = ['_' => 'socketDOMException', 'message' => $e->getMessage()];
        } else {
            $exception = ['_' => 'socketException', 'message' => $e->getMessage()];
        }
        $exception['code'] = $e->getCode();
        $exception['trace'] = ['_' => 'socketTLTrace', 'frames' => []];
        $tl = false;
        foreach (array_reverse($e->getTrace()) as $k => $frame) {
            $tl_frame = ['_' => 'socketTLFrame'];
            if (isset($frame['function']) && in_array($frame['function'], ['serialize_params', 'serialize_object'])) {
                if ($frame['args'][2] !== '') {
                    $tl_frame['tl_param'] = $frame['args'][2];
                    $tl = true;
                }
            } else {
                if (isset($frame['function']) && ($frame['function'] === 'handle_rpc_error' && $k === count($this->getTrace()) - 1) || $frame['function'] === 'unserialize') {
                    continue;
                }
                if (isset($frame['file'])) {
                    $tl_frame['file'] = $frame['file'];
                    $tl_frame['line'] = $frame['line'];
                }
                if (isset($frame['function'])) {
                    $tl_frame['function'] = $frame['function'];
                }
                if (isset($frame['args'])) {
                    $tl_frame['args'] = json_encode($frame['args']);
                }
                $tl = false;
            }
            $exception['trace']['frames'][] = $tl_frame;
        }
        $this->send_message($this->serialize_object(['type' => 'socketMessageException'], ['request_id' => $request_id, 'exception' => $exception]));
    }

    public function send_response($request_id, $response)
    {
        $this->send_message($this->serialize_object(['type' => 'socketMessageResponse'], ['request_id' => $request_id, 'data' => $response]));
    }

    public function send_data($stream_id, $data)
    {
        $this->send_message($this->serialize_object(['type' => 'socketMessageRawData'], ['stream_id' => $stream_id, 'data' => $data]));
    }

    public function logger($message, $level)
    {
        $message = ['_' => 'socketMessageLog', 'data' => $message, 'level' => $level, 'thread' => \danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread()), 'process' => \danog\MadelineProto\Logger::is_fork(), 'file' => basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php')];
        $this->send_message($this->serialize_object(['type' => 'socketMessageLog'], $message));
    }

    public function update_handler($update)
    {
        $this->send_message($this->serialize_object(['type' => 'socketMessageUpdate'], ['data' => $update]));
    }

    public function __call($method, $args)
    {
        $this->send_message($this->serialize_object(['type' => 'socketMessageRequest'], ['request_id' => 0, 'method' => $method, 'args' => $args]));
    }
}
