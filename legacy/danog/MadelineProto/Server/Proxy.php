<?php

/**
 * Proxy module.
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
class Proxy extends \danog\MadelineProto\Connection
{
    public function __magic_construct($socket, $extra, $ip, $port, $protocol, $timeout, $ipv6)
    {
        \danog\MadelineProto\Logger::log('Got connection '.\getmypid().'!');
        \danog\MadelineProto\Magic::$pid = \getmypid();
        \danog\MadelineProto\Lang::$current_lang = [];
        $this->sock = $socket;
        $this->sock->setBlocking(true);
        $this->must_open = false;
        $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $extra['timeout']);
        $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $extra['timeout']);
        $this->logger = new \danog\MadelineProto\Logger(3);
        $this->extra = $extra;
        if ($this->extra['madeline'] instanceof \danog\MadelineProto\API) {
            $this->extra['madeline'] = $this->extra['madeline']->API->datacenter->sockets;
        }
    }

    public function __destruct()
    {
        \danog\MadelineProto\Logger::log('Closing fork '.\getmypid().'!');
        unset($this->sock);
    }

    public function loop()
    {
        $this->protocol = 'obfuscated2';
        $random = $this->sock->read(64);

        $reversed = \strrev(\substr($random, 8, 48));
        $key = \substr($random, 8, 32);
        $keyRev = \substr($reversed, 0, 32);
        if (isset($this->extra['secret'])) {
            $key = \hash('sha256', $key.$this->extra['secret'], true);
            $keyRev = \hash('sha256', $keyRev.$this->extra['secret'], true);
        }

        $this->obfuscated = ['encryption' => new \phpseclib\Crypt\AES('ctr'), 'decryption' => new \phpseclib\Crypt\AES('ctr')];
        $this->obfuscated['encryption']->enableContinuousBuffer();
        $this->obfuscated['decryption']->enableContinuousBuffer();
        $this->obfuscated['decryption']->setKey($key);
        $this->obfuscated['decryption']->setIV(\substr($random, 40, 16));
        $this->obfuscated['encryption']->setKey($keyRev);
        $this->obfuscated['encryption']->setIV(\substr($reversed, 32, 16));
        $random = \substr_replace($random, \substr(@$this->obfuscated['decryption']->encrypt($random), 56, 8), 56, 8);

        if (\substr($random, 56, 4) !== \str_repeat(\chr(0xef), 4)) {
            throw new \danog\MadelineProto\Exception('Wrong protocol version');
        }
        $dc = \abs(\unpack('s', \substr($random, 60, 2))[1]);

        $socket = $this->extra['madeline'][$dc];
        $socket->__construct($socket->proxy, $socket->extra, $socket->ip, $socket->port, $socket->protocol, $timeout = $this->extra['timeout'], $socket->ipv6);

        unset($this->extra);

        $write = [];
        $except = [];
        while (true) {
            \pcntl_signal_dispatch();

            try {
                $read = [$this->getSocket(), $socket->getSocket()];
                \Socket::select($read, $write, $except, $timeout);
                if (isset($read[0])) {
                    //\danog\MadelineProto\Logger::log("Will write to DC $dc on ".\danog\MadelineProto\Magic::$pid);
                    $socket->sendMessage($this->readMessage());
                }
                if (isset($read[1])) {
                    //\danog\MadelineProto\Logger::log("Will read from DC $dc on ".\danog\MadelineProto\Magic::$pid);
                    $this->sendMessage($socket->readMessage());
                }
                if (empty($read)) {
                    throw new \danog\MadelineProto\NothingInTheSocketException('Inactivity');
                }
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                exit();
            }
        }
    }
}
