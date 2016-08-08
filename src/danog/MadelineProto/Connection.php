<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Manages connection to telegram servers.
 */
class Connection
{
    public $sock = null;
    public $protocol = null;

    public function __construct($ip, $port, $protocol = 'tcp')
    {
        switch ($protocol) {
            case 'tcp':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                $this->protocol = 'tcp';
                stream_set_timeout($this->sock, 5);
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function __destruct()
    {
        switch ($this->protocol) {
            case 'tcp':
                fclose($this->sock);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function write($what, $length = null)
    {
        if ($length != null) {
            $what = substr($what, 0, $length);
        }
        switch ($this->protocol) {
            case 'tcp':
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }

                return fwrite($this->sock, $what);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function read($length)
    {
        switch ($this->protocol) {
            case 'tcp':
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }

                return fread($this->sock, $length);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }
}
