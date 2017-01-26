<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

trait RSA
{
    public function loadKey($rsa_key)
    {
        \danog\MadelineProto\Logger::log('Istantiating \phpseclib\Crypt\RSA...');
        $key = new \phpseclib\Crypt\RSA();

        \danog\MadelineProto\Logger::log('Loading key...');
        if (method_exists($key, 'load')) {
            $key->load($rsa_key);
        } else {
            $key->loadKey($rsa_key);
        }
        $keydata = ['n' => $key->modulus, 'e' => $key->exponent];

        \danog\MadelineProto\Logger::log('Computing fingerprint...');
        $keydata['fp_bytes'] = substr(
            sha1(
                $this->serialize_object(
                    ['type' => 'bytes'],
                    $keydata['n']->toBytes()
                )
                .
                $this->serialize_object(
                    ['type' => 'bytes'],
                    $keydata['e']->toBytes()
                ),
                true
            ),
            -8
        );

        \danog\MadelineProto\Logger::log('Generating BigInteger object for fingerprint...');
        $keydata['fp'] = new \phpseclib\Math\BigInteger(strrev($keydata['fp_bytes']), -256);
        return $keydata;
    }

    public function RSA_encrypt($data, $keydata)
    {
        \danog\MadelineProto\Logger::log('Encrypting with rsa key...');

        return (new \phpseclib\Math\BigInteger($data, 256))->powMod($keydata['e'], $keydata['n'])->toBytes();
    }
}
