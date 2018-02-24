<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

trait Crypt
{
    public function aes_calculate($msg_key, $auth_key, $to_server = true)
    {
        $x = $to_server ? 0 : 8;
        $sha256_a = hash('sha256', $msg_key.substr($auth_key, $x, 36), true);
        $sha256_b = hash('sha256', substr($auth_key, 40 + $x, 36).$msg_key, true);
        $aes_key = substr($sha256_a, 0, 8).substr($sha256_b, 8, 16).substr($sha256_a, 24, 8);
        $aes_iv = substr($sha256_b, 0, 8).substr($sha256_a, 8, 16).substr($sha256_b, 24, 8);

        return [$aes_key, $aes_iv];
    }

    public function old_aes_calculate($msg_key, $auth_key, $to_server = true)
    {
        $x = $to_server ? 0 : 8;
        $sha1_a = sha1($msg_key.substr($auth_key, $x, 32), true);
        $sha1_b = sha1(substr($auth_key, 32 + $x, 16).$msg_key.substr($auth_key, 48 + $x, 16), true);
        $sha1_c = sha1(substr($auth_key, 64 + $x, 32).$msg_key, true);
        $sha1_d = sha1($msg_key.substr($auth_key, 96 + $x, 32), true);
        $aes_key = substr($sha1_a, 0, 8).substr($sha1_b, 8, 12).substr($sha1_c, 4, 12);
        $aes_iv = substr($sha1_a, 8, 12).substr($sha1_b, 0, 8).substr($sha1_c, 16, 4).substr($sha1_d, 0, 8);

        return [$aes_key, $aes_iv];
    }

    public function ige_encrypt($message, $key, $iv)
    {
        $cipher = new \phpseclib\Crypt\AES('ige');
        $cipher->setKey($key);
        $cipher->setIV($iv);

        return @$cipher->encrypt($message);
    }

    public function ctr_encrypt($message, $key, $iv)
    {
        $cipher = new \phpseclib\Crypt\AES('ctr');
        $cipher->setKey($key);
        $cipher->setIV($iv);

        return @$cipher->encrypt($message);
    }

    public function ige_decrypt($message, $key, $iv)
    {
        $cipher = new \phpseclib\Crypt\AES('ige');
        $cipher->setKey($key);
        $cipher->setIV($iv);

        return @$cipher->decrypt($message);
    }
}
