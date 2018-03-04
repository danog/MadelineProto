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
        $this->protocol = $protocol;
        $this->construct_TL(['socket' => __DIR__.'/../TL_socket.tl']);
    }
    public function __destruct() {
        unset($this->sock);
        $this->destruct_madeline();
        exit();
    }
    public function destruct_madeline() {
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
        while (true) {
            $request_id = 0;
            try {
                $message = $this->read_message();
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                continue;
            }
            if ($message === null) {
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
    public function on_request($method, $args) {
        if (count($method) === 0 || count($method) > 2) {
            throw new \danog\MadelineProto\Exception('Invalid method called');
        }
        if ($method[0] === '__construct') {
            if (count($args) === 1 && is_array($args[0])) {
                $args[0]['logger'] = ['logger' => 4, 'logger_param' => [$this, 'logger']];
                $args[0]['updates']['callback'] = [$this, 'update_handler'];
            } else if (count($args) === 2 && is_array($args[1])) {
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
            if (is_array($arg) && isset($arg['_'])){
                if ($arg['_'] === 'callback' && isset($arg['callback']) && !method_exists($this, $arg['callback'])) {
                    $arg = [$this, $arg['callback']];
                }
                if ($arg['_'] === 'stream' && isset($arg['stream_id'])) {
                    $arg = fopen('madelineSocket://', 'r+b', false, Handler::getContext($this, $arg['stream_id']));
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
    public function send_exception($request_id, $e) {
        echo $e;
        //$this->send_message($this->serialize_object(['type' => 'socketMessageException'], ['request_id' => $request_id, 'exception' => $e]));
    }
    public function send_response($request_id, $response) {
        $this->send_message($this->serialize_object(['type' => 'socketMessageResponse'], ['request_id' => $request_id, 'data' => $response]));
    }
    public function send_data($stream_id, $data) {
        $this->send_message($this->serialize_object(['type' => 'socketMessageRawData'], ['stream_id' => $stream_id, 'data' => $data]));
    }
    public function logger($message, $level) {

    }
    public function update_handler($update) {
        $this->send_message($this->serialize_object(['type' => 'socketMessageUpdate'], ['data' => $update]));
    }
    public function __call($method, $args) {
        $this->send_message($this->serialize_object(['type' => 'socketMessageRequest'], ['request_id' => 0, 'method' => $method, 'args' => $args]));
    }
}
