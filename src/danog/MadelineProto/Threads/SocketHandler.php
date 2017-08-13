<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Threads;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
class SocketHandler extends \Threaded implements \Collectable
{
    public function __construct(&$me, $current, $error)
    {
        $this->API = $me;
        $this->current = $current;
        $this->error = $error;
    }

    /**
     * Reading connection and receiving message from server. Check the CRC32.
     */
    public function run()
    {
        require __DIR__.'/../../../../vendor/autoload.php';
        if ($this->error !== true) {
            if ($this->error === -404) {
                if ($this->API->datacenter->sockets[$this->current]->temp_auth_key !== null) {
                    \danog\MadelineProto\Logger::log(['WARNING: Resetting auth key...'], \danog\MadelineProto\Logger::WARNING);
                    $this->API->datacenter->sockets[$this->current]->temp_auth_key = null;
                    $this->API->init_authorization();

                    throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                }
            }

            throw new \danog\MadelineProto\RPCErrorException($this->error, $this->error);
        }
        $this->API->handle_messages($this->current);
        $this->setGarbage();
    }

    protected $garbage = false;

    public function setGarbage():void
    {
        $this->garbage = true;
    }

    public function isGarbage():bool
    {
        return $this->garbage;
    }
}
