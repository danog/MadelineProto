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

namespace danog\MadelineProto;

class RSA extends TL\TL
{
    public $n; // phpseclib\Math\BigInteger class
    public $e; // phpseclib\Math\BigInteger class
    public $fp; // phpseclib\Math\BigInteger class
    public $fp_bytes; // bytes

    public function __construct($rsa_key)
    {
        \danog\MadelineProto\Logger::log('Istantiating \phpseclib\Crypt\RSA...');
        $key = new \phpseclib\Crypt\RSA();

        \danog\MadelineProto\Logger::log('Loading key...');
        $key->loadKey($rsa_key);
        $this->n = $key->modulus;
        $this->e = $key->exponent;
        unset($key);

        \danog\MadelineProto\Logger::log('Computing fingerprint...');
        $this->fp_bytes = substr(
            sha1(
                $this->serialize_param(
                    'bytes',
                    null,
                    $this->n->toBytes()
                )
                .
                $this->serialize_param(
                    'bytes',
                    null,
                    $this->e->toBytes()
                ),
                true
            ),
            -8
        );

        \danog\MadelineProto\Logger::log('Generating BigInteger object for fingerprint...');
        $this->fp = new \phpseclib\Math\BigInteger(strrev($this->fp_bytes), -256);
    }

    public function encrypt($data)
    {
        $bigintdata = new \phpseclib\Math\BigInteger($data, 256);

        return $bigintdata->powMod($this->e, $this->n)->toBytes();
    }
}
