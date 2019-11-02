<?php

/**
 * Handler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Server;

/*
 * Socket handler for server
 */
class Handler extends \danog\MadelineProto\Connection
{
    use \danog\MadelineProto\TL\TL;
    use \danog\MadelineProto\TL\Conversion\BotAPI;
    use \danog\MadelineProto\TL\Conversion\BotAPIFiles;
    use \danog\MadelineProto\TL\Conversion\Extension;
    use \danog\MadelineProto\TL\Conversion\TD;
    use \danog\MadelineProto\Tools;
    private $madeline;

    public function __magic_construct($socket, $extra, $ip, $port, $protocol, $timeout, $ipv6)
    {
        \danog\MadelineProto\Magic::$pid = \getmypid();
        $this->sock = $socket;
        $this->sock->setBlocking(true);
        $this->must_open = false;
        $timeout = 2;
        $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
        $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
        $this->logger = new \danog\MadelineProto\Logger(3);
        $this->constructTL(['socket' => __DIR__.'/../TL_socket.tl']);
    }

    public function __destruct()
    {
        echo 'Closing socket in fork '.\getmypid().PHP_EOL;
        unset($this->sock);
        $this->destructMadeline();
    }

    public function destructMadeline()
    {
        if (isset($this->madeline) && $this->madeline !== null) {
            $this->madeline->API->settings['logger'] = ['logger' => 0, 'logger_param' => ''];
            $this->madeline->API->settings['updates']['callback'] = [];
            unset($this->madeline);

            return true;
        }

        return false;
    }

    public function loop()
    {
        $buffer = '';

        $first_byte = $this->sock->read(1);

        if ($first_byte === \chr(239)) {
            $this->protocol = 'tcp_abridged';
        } else {
            $first_byte .= $this->sock->read(3);
            if ($first_byte === \str_repeat(\chr(238), 4)) {
                $this->protocol = 'tcp_intermediate';
            } else {
                $this->protocol = 'tcp_full';

                $packet_length = \unpack('V', $first_byte)[1];
                $packet = $this->read($packet_length - 4);
                if (\strrev(\hash('crc32b', $first_byte.\substr($packet, 0, -4), true)) !== \substr($packet, -4)) {
                    throw new Exception('CRC32 was not correct!');
                }
                $this->in_seq_no++;
                $in_seq_no = \unpack('V', \substr($packet, 0, 4))[1];
                if ($in_seq_no != $this->in_seq_no) {
                    throw new Exception('Incoming seq_no mismatch');
                }

                $buffer = \substr($packet, 4, $packet_length - 12);
            }
        }
        while (true) {
            \pcntl_signal_dispatch();
            $request_id = 0;

            try {
                if ($buffer) {
                    $message = $buffer;
                    $buffer = '';
                } else {
                    $time = \time();
                    $message = $this->readMessage();
                }
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                echo $e;
                if (\time() - $time < 2) {
                    $this->sock = null;
                }
                continue;
            }

            try {
                $message = $this->deserialize($message, ['type' => '', 'datacenter' => '']);
                if ($message['_'] !== 'socketMessageRequest') {
                    throw new \danog\MadelineProto\Exception('Invalid object received');
                }
                $request_id = $message['request_id'];
                $this->sendResponse($request_id, $this->onRequest($request_id, $message['method'], $message['args']));
            } catch (\danog\MadelineProto\TL\Exception $e) {
                $this->sendException($request_id, $e);
                continue;
            } catch (\danog\MadelineProto\Exception $e) {
                $this->sendException($request_id, $e);
                continue;
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $this->sendException($request_id, $e);
                continue;
            } catch (\DOMException $e) {
                $this->sendException($request_id, $e);
                continue;
            }
        }
    }

    public function onRequest($request_id, $method, $args)
    {
        if (\count($method) === 0 || \count($method) > 2) {
            throw new \danog\MadelineProto\Exception('Invalid method called');
        }

        \array_walk($args, [$this, 'walker']);

        if ($method[0] === '__construct') {
            if (\count($args) === 1 && \is_array($args[0])) {
                $args[0]['logger'] = ['logger' => 4, 'logger_param' => [$this, 'logger']];
                $args[0]['updates']['callback'] = [$this, 'updateHandler'];
            } elseif (\count($args) === 2 && \is_array($args[1])) {
                $args[1]['logger'] = ['logger' => 4, 'logger_param' => [$this, 'logger']];
                $args[1]['updates']['callback'] = [$this, 'updateHandler'];
            }
            $this->madeline = new \danog\MadelineProto\API(...$args);

            return true;
        }
        if ($method[0] === '__destruct') {
            $this->__destruct();
            exit();
        }
        if ($this->madeline === null) {
            throw new \danog\MadelineProto\Exception('__construct was not called');
        }

        if (\count($method) === 1) {
            return $this->madeline->{$method[0]}(...$args);
        }
        if (\count($method) === 2) {
            return $this->madeline->{$method[0]}->{$method[1]}(...$args);
        }
    }

    private function walker(&$arg)
    {
        if (\is_array($arg)) {
            if (isset($arg['_'])) {
                if ($arg['_'] === 'fileCallback' && isset($arg['callback']) && isset($arg['file']) && !\method_exists($this, $arg['callback']['callback'])) {
                    if (isset($arg['file']['_']) && $arg['file']['_'] === 'stream') {
                        $arg['file'] = \fopen('madelineSocket://', 'r+b', false, Stream::getContext($this, $arg['file']['stream_id']));
                    }
                    $arg = new \danog\MadelineProto\FileCallback($arg['file'], [$this, $arg['callback']['callback']]);

                    return;
                } elseif ($arg['_'] === 'callback' && isset($arg['callback']) && !\method_exists($this, $arg['callback'])) {
                    $arg = [$this, $arg['callback']];

                    return;
                } elseif ($arg['_'] === 'stream' && isset($arg['stream_id'])) {
                    $arg = \fopen('madelineSocket://', 'r+b', false, Stream::getContext($this, $arg['stream_id']));

                    return;
                } elseif ($arg['_'] === 'bytes' && isset($arg['bytes'])) {
                    $arg = \base64_decode($args['bytes']);

                    return;
                }
                \array_walk($arg, [$this, 'walker']);
            } else {
                \array_walk($arg, [$this, 'walker']);
            }
        }
    }

    public function sendException($request_id, $e)
    {
        echo $e.PHP_EOL;
        if ($e instanceof \danog\MadelineProto\RPCErrorException) {
            $exception = ['_' => 'socketRPCErrorException'];
            if ($e->getMessage() === $e->rpc) {
                $exception['rpc_message'] = $e->rpc;
            } else {
                $exception['rpc_message'] = $e->rpc;
                $exception['message'] = $e->getMessage();
            }
        } elseif ($e instanceof \danog\MadelineProto\TL\Exception) {
            $exception = ['_' => 'socketTLException', 'message' => $e->getMessage()];
        } elseif ($e instanceof \DOMException) {
            $exception = ['_' => 'socketDOMException', 'message' => $e->getMessage()];
        } else {
            $exception = ['_' => 'socketException', 'message' => $e->getMessage()];
        }
        $exception['code'] = $e->getCode();
        $exception['trace'] = ['_' => 'socketTLTrace', 'frames' => []];
        $tl = false;
        foreach (\array_reverse($e->getTrace()) as $k => $frame) {
            $tl_frame = ['_' => 'socketTLFrame'];
            if (isset($frame['function']) && \in_array($frame['function'], ['serializeParams', 'serializeObject'])) {
                if ($frame['args'][2] !== '') {
                    $tl_frame['tl_param'] = (string) $frame['args'][2];
                    $tl = true;
                }
            } else {
                if (isset($frame['function']) && ($frame['function'] === 'handle_rpc_error' && $k === \count($this->getTrace()) - 1) || $frame['function'] === 'unserialize') {
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
                    $args = \json_encode($frame['args']);
                    if ($args !== false) {
                        $tl_frame['args'] = $args;
                    }
                }
                $tl = false;
            }
            $exception['trace']['frames'][] = $tl_frame;
        }
        $this->sendMessageSafe(yield $this->serializeObject(['type' => ''], ['_' => 'socketMessageException', 'request_id' => $request_id, 'exception' => $exception], 'exception'));
    }

    public function sendResponse($request_id, $response)
    {
        $this->sendMessageSafe(yield $this->serializeObject(['type' => ''], ['_' => 'socketMessageResponse', 'request_id' => $request_id, 'data' => $response], 'exception'));
    }

    public function sendData($stream_id, $data)
    {
        $this->sendMessageSafe(yield $this->serializeObject(['type' => ''], ['_' => 'socketMessageRawData', 'stream_id' => $stream_id, 'data' => $data], 'data'));
    }

    public $logging = false;

    public function logger($message, $level)
    {
        if (!$this->logging) {
            try {
                $this->logging = true;

                $message = ['_' => 'socketMessageLog', 'data' => $message, 'level' => $level, 'thread' => \danog\MadelineProto\Magic::$has_thread && \is_object(\Thread::getCurrentThread()), 'process' => \danog\MadelineProto\Magic::isFork(), 'file' => \basename(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file'], '.php')];

                $this->sendMessageSafe(yield $this->serializeObject(['type' => ''], $message, 'log'));
            } finally {
                $this->logging = false;
            }
        }
    }

    public function sendMessageSafe($message)
    {
        if (!isset($this->sock)) {
            return false;
        }

        try {
            $this->sendMessage($message);
        } catch (\danog\MadelineProto\Exception $e) {
            $this->__destruct();
            die;
        }
    }

    public function updateHandler($update)
    {
        $this->sendMessageSafe(yield $this->serializeObject(['type' => ''], ['_' => 'socketMessageUpdate', 'data' => $update], 'update'));
    }

    public function __call($method, $args)
    {
        $this->sendMessageSafe(yield $this->serializeObject(['type' => ''], ['_' => 'socketMessageRequest', 'request_id' => 0, 'method' => [$method], 'args' => $args], 'method'));
    }
}
