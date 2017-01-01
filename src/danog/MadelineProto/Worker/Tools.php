<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Worker;

/**
 * Tools for the web API and the worker.
 */
trait Tools
{
    public $base_64 = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '_', '-'];

    public function check_token($token)
    {
        if (strlen($token) < $this->settings['token']['min_length'] || strlen($token) > $this->settings['token']['max_length']) {
            return false;
        }
        foreach (str_split($token) as $char) {
            if (!in_array($char, $this->base_64)) {
                return false;
            }
        }

        return true;
    }

    public function db_connect()
    {
        $this->pdo = new \PDO($this->settings['db']['connection'], $this->settings['db']['user'], $this->settings['db']['password'], [\PDO::ATTR_EMULATE_PREPARES => false, \PDO::ATTR_TIMEOUT => 2, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]);
    }

    public function send_buffer()
    {
        ob_flush();
        flush();
    }
}
