<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

class Crypt extends CallHandler
{
    public function aes_calculate($msg_key, $direction = 'to server')
    {
        $x = ($direction == 'to server') ? 0 : 8;
        $sha1_a = sha1($msg_key.substr($this->settings['authorization']['temp_auth_key']['auth_key'], $x, ($x + 32) - $x), true);
        $sha1_b = sha1(substr($this->settings['authorization']['temp_auth_key']['auth_key'], ($x + 32), ($x + 48) - ($x + 32)).$msg_key.substr($this->settings['authorization']['temp_auth_key']['auth_key'], (48 + $x), (64 + $x) - (48 + $x)), true);
        $sha1_c = sha1(substr($this->settings['authorization']['temp_auth_key']['auth_key'], ($x + 64), ($x + 96) - ($x + 64)).$msg_key, true);
        $sha1_d = sha1($msg_key.substr($this->settings['authorization']['temp_auth_key']['auth_key'], ($x + 96), ($x + 128) - ($x + 96)), true);
        $aes_key = substr($sha1_a, 0, 8 - 0).substr($sha1_b, 8, 20 - 8).substr($sha1_c, 4, 16 - 4);
        $aes_iv = substr($sha1_a, 8, 20 - 8).substr($sha1_b, 0, 8 - 0).substr($sha1_c, 16, 20 - 16).substr($sha1_d, 0, 8 - 0);

        return [$aes_key, $aes_iv];
    }
}
