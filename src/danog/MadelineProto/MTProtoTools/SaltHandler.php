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

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages message ids.
 */
class SaltHandler extends ResponseHandler
{
    public function add_salts($salts)
    {
        foreach ($salts as $salt) {
            $this->add_salt($salt['valid_since'], $salt['valid_until'], $salt['salt']);
        }
    }

    public function add_salt($valid_since, $valid_until, $salt)
    {
        if (!isset($this->datacenter->temp_auth_key['salts'][$salt])) {
            $this->datacenter->temp_auth_key['salts'][$salt] = ['valid_since' => $valid_since, 'valid_until' => $valid_until];
        }
    }
}
