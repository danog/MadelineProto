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
    public function __construct(&$me, $current)
    {
        $this->API = $me;
        $this->current = $current;
    }

    /**
     * Reading connection and receiving message from server. Check the CRC32.
     */
    public function run()
    {
        require_once(__DIR__.'/../SecurityException.php');
        require_once(__DIR__.'/../RPCErrorException.php');
        require_once(__DIR__.'/../ResponseException.php');
        require_once(__DIR__.'/../TL/Conversion/Exception.php');
        require_once(__DIR__.'/../TL/Exception.php');
        require_once(__DIR__.'/../NothingInTheSocketException.php');
        require_once(__DIR__.'/../Exception.php');
        $this->API->handle_messages($current);
        $this->setGarbage();
    }
    
    private $garbage = false;
    public function setGarbage():void {
        $this->garbage = true;
    }
    public function isGarbage():bool {
        return $this->garbage;
    }
}
