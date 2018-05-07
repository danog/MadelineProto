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
    use \danog\MadelineProto\Tools;
    private $madeline;

    public function __magic_construct($socket, $extra, $ip, $port, $protocol, $timeout, $ipv6)
    {
        \danog\MadelineProto\Magic::$pid = getmypid();
        $this->sock = $socket;
        $this->sock->setBlocking(true);
        $this->must_open = false;
        $timeout = 2;
        $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
        $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
        $this->logger = new \danog\MadelineProto\Logger(3);
        $this->extra = $extra;
    }

    public function __destruct()
    {
        echo 'Closing socket in fork '.getmypid().PHP_EOL;
        unset($this->sock);
    }

    public function loop()
    {
        $this->protocol = 'obfuscated2';
        $random = $this->sock->read(64);

        $reversed = strrev(substr($random, 8, 48));
        if (isset($this->extra['secret'])) {
            $random = substr_replace($random, hash('sha256', $key.$this->extra['secret'], true), 32, 8);
        }

        $this->obfuscated = ['encryption' => new \phpseclib\Crypt\AES('ctr'), 'decryption' => new \phpseclib\Crypt\AES('ctr')];
        $this->obfuscated['encryption']->enableContinuousBuffer();
        $this->obfuscated['decryption']->enableContinuousBuffer();
        $this->obfuscated['decryption']->setKey(substr($random, 8, 32));
        $this->obfuscated['decryption']->setIV(substr($random, 40, 16));
        $this->obfuscated['encryption']->setKey(substr($reversed, 0, 32));
        $this->obfuscated['encryption']->setIV(substr($reversed, 32, 16));
        $random = substr_replace($random, substr(@$this->obfuscated['encryption']->encrypt($random), 56, 8), 56, 8);

        $random[56] = $random[57] = $random[58] = $random[59] = chr(0xef);

        if (substr($random, 56, 4) !== str_repeat(chr(0xef), 4)) {
            throw new \danog\MadelineProto\Exception('Wrong protocol version');
        }
        $socket = $this->extra['madeline']->API->datacenter->sockets[unpack('v', substr($random, 60, 2)[1]];
        $socket->close_and_reopen();

        while (true) {
            pcntl_signal_dispatch();

            try {
                $time = time();
                $socket->send_message($this->read_message());
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                echo $e;
                if (time() - $time < 2) {
                    exit();
                }
                continue;
            }
        }
    }
}
